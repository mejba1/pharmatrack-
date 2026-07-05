<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Country;
use App\Models\Customer;
use App\Models\OrderDocument;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    // ── List ───────────────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $mine    = !$request->user()->canViewAll('orders');
        $uid     = $request->user()->id;
        $ownIds  = $request->user()->ownedCustomerIds();
        // A scoped user sees POs they created OR that belong to their customers.
        $own = fn ($q) => $mine
            ? $q->where(fn ($w) => $w->where('created_by', $uid)->orWhereIn('buyer_id', $ownIds ?: [0]))
            : $q;

        $orders = $own(PurchaseOrder::with([
            'buyer.country', 'lines.product', 'documents',
            'salesOrder.proformaInvoice.commercialInvoices:id,proforma_invoice_id',
        ]))->latest()->limit(300)->get();

        $pos = $orders->map(fn ($po) => $this->payload($po))->values();

        $stats = [
            'total'        => $own(PurchaseOrder::query())->count(),
            'pending'      => $own(PurchaseOrder::where('status', 'sent'))->count(),
            'acknowledged' => $own(PurchaseOrder::where('status', 'acknowledged'))->count(),
            'cancelled'    => $own(PurchaseOrder::where('status', 'cancelled'))->count(),
        ];

        // Customers that already have a live (non-cancelled) PO — a country
        // manager may only open a new PO for a customer that has none yet.
        $withPo = PurchaseOrder::where('status', '!=', 'cancelled')->distinct()->pluck('buyer_id')->map(fn ($i) => (int) $i)->all();

        // Managers can only raise POs for their own customers.
        $customers = Customer::with('country')
            ->when($mine, fn ($q) => $q->whereIn('id', $ownIds ?: [0]))
            ->orderBy('name')->get(['id', 'name', 'type', 'customer_code', 'country_id'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'code' => $c->customer_code, 'country_id' => $c->country_id, 'country' => $c->country?->name, 'has_po' => in_array((int) $c->id, $withPo, true)]);
        $types      = Customer::TYPES;
        $products   = Product::orderBy('name')->get(['id', 'name', 'prn']);
        $restrictPo = $request->user()->role === 'country_manager';   // one PO per customer for managers

        return view('orders.po', compact('pos', 'stats', 'customers', 'types', 'products', 'restrictPo'));
    }

    // ── Create ─────────────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'buyer_id'         => 'required|exists:customers,id',
            'po_date'          => 'nullable|date',
            'required_by_date' => 'required|date',
            'currency'         => 'nullable|string|max:3',
            'payment_terms'    => 'required|string|max:120',
            'incoterms'        => 'nullable|string|max:10',
            'port_of_loading'  => 'nullable|string|max:120',
            'port_of_discharge' => 'nullable|string|max:120',
            'freight'          => 'nullable|numeric|min:0',
            'status'           => 'nullable|in:draft,sent',
            'remarks'          => 'nullable|string|max:2000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        // A country manager may open only one PO per customer — if the customer
        // already has a live PO (their own or a manager's), work with that one.
        if ($request->user()->role === 'country_manager'
            && PurchaseOrder::where('buyer_id', $data['buyer_id'])->where('status', '!=', 'cancelled')->exists()) {
            return back()->with('error', 'This customer already has a purchase order — process the existing one into a Sales Order instead of creating another.');
        }

        $po = DB::transaction(function () use ($data, $request) {
            $po = PurchaseOrder::create([
                'po_number'         => PurchaseOrder::nextNumber(),
                'buyer_id'          => $data['buyer_id'],
                'created_by'        => $request->user()->id,
                'po_date'           => $data['po_date'] ?? now()->toDateString(),
                'required_by_date'  => $data['required_by_date'],
                'currency'          => strtoupper($data['currency'] ?? 'USD'),
                'payment_terms'     => $data['payment_terms'],
                'incoterms'         => $data['incoterms'] ?? null,
                'port_of_loading'   => $data['port_of_loading'] ?? null,
                'port_of_discharge' => $data['port_of_discharge'] ?? null,
                'freight'           => $data['freight'] ?? 0,
                'status'            => $data['status'] ?? 'draft',
                'remarks'           => $data['remarks'] ?? null,
            ]);

            $subtotal = 0;
            foreach (array_values($data['items']) as $n => $item) {
                $qty   = (int) $item['quantity'];
                $price = (float) ($item['unit_price'] ?? 0);
                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $item['product_id'],
                    'line_number'       => $n + 1,
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'line_total'        => $qty * $price,
                ]);
                $subtotal += $qty * $price;
            }

            $po->update(['subtotal' => $subtotal, 'total_value' => $subtotal + (float) ($data['freight'] ?? 0)]);
            return $po;
        });

        return back()->with('success', "Purchase Order {$po->po_number} created.");
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['buyer.country', 'lines.product', 'documents.uploader', 'creator']);
        return response()->json($this->payload($purchaseOrder));
    }

    // ── Status workflow ────────────────────────────────────────────────────
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $request->validate([
            'action'          => 'required|in:send,acknowledge,cancel',
            'acknowledged_by' => 'nullable|exists:users,id',
        ]);

        switch ($data['action']) {
            case 'send':
                if ($purchaseOrder->status === 'draft') $purchaseOrder->update(['status' => 'sent']);
                $msg = 'sent to the buyer';
                break;
            case 'acknowledge':
                if (in_array($purchaseOrder->status, ['draft', 'sent'], true)) {
                    $purchaseOrder->update([
                        'status'            => 'acknowledged',
                        'acknowledged_date' => now()->toDateString(),
                        'acknowledged_by'   => $data['acknowledged_by'] ?? $purchaseOrder->created_by,
                    ]);
                }
                $msg = 'acknowledged';
                break;
            default:
                $purchaseOrder->update(['status' => 'cancelled']);
                $msg = 'cancelled';
        }

        return back()->with('success', "Purchase Order {$purchaseOrder->po_number} {$msg}.");
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft purchase orders can be deleted.');
        }
        $num = $purchaseOrder->po_number;
        $purchaseOrder->delete();
        return back()->with('success', "Purchase Order {$num} deleted.");
    }

    // ── Reference documents ────────────────────────────────────────────────
    public function storeDoc(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $request->validate([
            'file'     => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,webp',
            'category' => 'nullable|string|max:80',
        ]);

        $file = $data['file'];
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->store("order-docs/po/{$purchaseOrder->id}", 'public');

        $purchaseOrder->documents()->create([
            'name'        => $file->getClientOriginalName(),
            'category'    => $data['category'] ?: 'Reference',
            'file_path'   => $path,
            'disk'        => 'public',
            'file_type'   => $file->getClientMimeType(),
            'extension'   => $ext,
            'file_size'   => $file->getSize(),
            'icon_class'  => OrderDocument::iconFor($ext),
            'uploaded_by' => $request->input('uploaded_by') ?: $purchaseOrder->created_by,
            'is_active'   => true,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function destroyDoc(OrderDocument $document): RedirectResponse
    {
        $document->deleteFile();
        $document->delete();
        return back()->with('success', 'Document removed.');
    }

    public function downloadDoc(OrderDocument $document)
    {
        return Storage::disk($document->disk ?: 'public')->download($document->file_path, $document->name);
    }

    // ── PDF ────────────────────────────────────────────────────────────────
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['buyer.country', 'lines.product', 'creator']);
        $pdf = Pdf::loadView('orders.po-pdf', ['po' => $purchaseOrder])->setPaper('a4');
        return $pdf->download($purchaseOrder->po_number . '.pdf');
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    /** Shape one PO into the structure the Alpine list/view modal consumes. */
    private function payload(PurchaseOrder $po): array
    {
        $units = $po->lines->sum('quantity');
        return [
            'pid'        => $po->id,
            'id'         => $po->po_number,
            'chain'      => $po->chainStages(),
            'buyer'      => $po->buyer?->name ?? '—',
            'country'    => $po->buyer?->country?->name ?? '',
            'products'   => $po->lines->count() . ' SKU' . ($po->lines->count() === 1 ? '' : 's') . ' / ' . number_format($units) . ' units',
            'poDate'     => $po->po_date?->format('d M Y'),
            'requiredBy' => $po->required_by_date?->format('d M Y'),
            'value'      => $po->currency . ' ' . number_format((float) $po->total_value, 2),
            'status'     => $po->status_label,
            'statusClass' => $po->status_badge_class,
            'manager'    => $po->creator?->name,
            'payTerms'   => $po->payment_terms,
            'incoterms'  => $po->incoterms,
            'canDelete'  => $po->status === 'draft',
            'lines'      => $po->lines->map(fn ($l) => [
                'product' => $l->product?->name,
                'prn'     => $l->product?->prn,
                'qty'     => $l->quantity,
                'unitPrice' => $po->currency . ' ' . number_format((float) $l->unit_price, 2),
                'total'   => $po->currency . ' ' . number_format((float) $l->line_total, 2),
            ])->values(),
            'docs'       => $po->documents->map(fn ($d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'category'   => $d->category,
                'size'       => $d->size_human,
                'uploadedBy' => $d->uploader?->name ?? '—',
                'date'       => $d->created_at?->format('d M Y'),
                'iconClass'  => $d->icon_class,
                'icon'       => 'bi-file-earmark',
                'url'        => route('orders.po.documents.download', $d->id),
                'del'        => route('orders.po.documents.destroy', $d->id),
            ])->values(),
        ];
    }
}
