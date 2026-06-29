<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchUnit;
use App\Models\OrderDocument;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    // ── List ───────────────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $mine = !$request->user()->seesAllData();
        $uid  = $request->user()->id;
        $own  = fn ($q) => $mine ? $q->where('created_by', $uid) : $q;

        $orders = $own(SalesOrder::with(['customer.country', 'purchaseOrder', 'lines.product', 'lines.batch', 'proformaInvoice.commercialInvoices', 'documents']))
            ->latest()->limit(300)->get();

        $sos = $orders->map(fn ($so) => $this->payload($so))->values();

        $stats = [
            'total'     => $own(SalesOrder::query())->count(),
            'draft'     => $own(SalesOrder::where('status', 'draft'))->count(),
            'confirmed' => $own(SalesOrder::where('status', 'confirmed'))->count(),
            'pi_issued' => $own(SalesOrder::where('status', 'pi_issued'))->count(),
        ];

        // Acknowledged POs not yet converted to an SO — source for "Create SO"
        // (a manager only converts their own POs).
        $acknowledgedPos = $own(PurchaseOrder::with(['buyer', 'lines.product', 'lines.batch']))
            ->where('status', 'acknowledged')
            ->whereDoesntHave('salesOrder')
            ->latest()->get()
            ->map(fn ($po) => [
                'id'       => $po->id,
                'number'   => $po->po_number,
                'buyer'    => $po->buyer?->name,
                'currency' => $po->currency,
                'incoterms' => $po->incoterms,
                'payment'  => $po->payment_terms,
                'lines'    => $po->lines->map(fn ($l) => [
                    'product_id' => $l->product_id,
                    'product'    => $l->product?->name,
                    'prn'        => $l->product?->prn,
                    'batch_id'   => $l->batch_id,
                    'quantity'   => $l->quantity,
                    'unit_price' => (float) $l->unit_price,
                ])->values(),
            ]);

        $batches = Batch::orderByDesc('id')->limit(1000)->get(['id', 'brn', 'batch_number', 'product_id'])
            ->map(fn ($b) => ['id' => $b->id, 'product_id' => $b->product_id, 'label' => $b->brn . ($b->batch_number ? ' · ' . $b->batch_number : '')]);

        return view('orders.so', compact('sos', 'stats', 'acknowledgedPos', 'batches'));
    }

    // ── Create (from an acknowledged PO, with serial allocation) ───────────
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'so_date'           => 'required|date',
            'incoterms'         => 'nullable|string|max:10',
            'payment_terms'     => 'nullable|string|max:120',
            'estimated_delivery_date' => 'nullable|date',
            'status'            => 'nullable|in:draft,confirmed',
            'remarks'           => 'nullable|string|max:2000',
            'lines'               => 'required|array|min:1',
            'lines.*.product_id'  => 'required|exists:products,id',
            'lines.*.batch_id'    => 'nullable|exists:batches,id',
            'lines.*.quantity'    => 'required|integer|min:1',
            'lines.*.unit_price'  => 'nullable|numeric|min:0',
            'lines.*.serials'     => 'nullable|string|max:20000',
        ]);

        $po = PurchaseOrder::findOrFail($data['purchase_order_id']);
        if ($po->status !== 'acknowledged') {
            return back()->with('error', 'The linked PO must be acknowledged.');
        }
        if ($po->salesOrder()->exists()) {
            return back()->with('error', 'This PO already has a Sales Order.');
        }

        $result = DB::transaction(function () use ($data, $po) {
            $so = SalesOrder::create([
                'so_number'         => SalesOrder::nextNumber(),
                'purchase_order_id' => $po->id,
                'created_by'        => $po->created_by,
                'customer_id'       => $po->buyer_id,
                'so_date'           => $data['so_date'],
                'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
                'currency'          => $po->currency,
                'payment_terms'     => $data['payment_terms'] ?? $po->payment_terms,
                'incoterms'         => $data['incoterms'] ?? $po->incoterms,
                'status'            => $data['status'] ?? 'confirmed',
                'remarks'           => $data['remarks'] ?? null,
            ]);

            $subtotal = 0;
            $warnings = [];
            foreach (array_values($data['lines']) as $n => $line) {
                $price = (float) ($line['unit_price'] ?? 0);
                $units = $this->allocateUnits($line, $so->customer_id, $so->id);
                $qty   = count($units) ?: (int) $line['quantity'];

                if (!empty($line['batch_id']) && count($units) < (int) $line['quantity']) {
                    $warnings[] = 'Line ' . ($n + 1) . ': allocated ' . count($units) . ' of ' . $line['quantity'] . ' requested units (others unavailable).';
                }

                SalesOrderLine::create([
                    'sales_order_id' => $so->id,
                    'product_id'     => $line['product_id'],
                    'batch_id'       => $line['batch_id'] ?? null,
                    'line_number'    => $n + 1,
                    'quantity'       => $qty,
                    'unit_price'     => $price,
                    'line_total'     => $qty * $price,
                ]);
                $subtotal += $qty * $price;
            }

            $so->update(['subtotal' => $subtotal, 'total_value' => $subtotal]);
            return ['so' => $so, 'warnings' => $warnings];
        });

        $msg = "Sales Order {$result['so']->so_number} created.";
        return $result['warnings']
            ? back()->with('success', $msg)->with('warning', implode(' ', $result['warnings']))
            : back()->with('success', $msg);
    }

    public function show(SalesOrder $salesOrder): JsonResponse
    {
        $salesOrder->load(['customer.country', 'purchaseOrder', 'lines.product', 'lines.batch', 'documents.uploader', 'units.batch']);
        return response()->json($this->payload($salesOrder));
    }

    public function updateStatus(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $data = $request->validate(['action' => 'required|in:confirm,cancel']);
        if ($data['action'] === 'confirm' && $salesOrder->status === 'draft') {
            $salesOrder->update(['status' => 'confirmed']);
            $msg = 'confirmed';
        } else {
            // Release allocated units back to stock on cancel.
            $salesOrder->units()->update(['sales_order_id' => null, 'sold_to_id' => null, 'sold_at' => null]);
            $salesOrder->update(['status' => 'cancelled']);
            $msg = 'cancelled (allocated units released)';
        }
        return back()->with('success', "Sales Order {$salesOrder->so_number} {$msg}.");
    }

    // ── Reference documents ────────────────────────────────────────────────
    public function storeDoc(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $data = $request->validate([
            'file'     => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,webp',
            'category' => 'nullable|string|max:80',
        ]);
        $file = $data['file'];
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->store("order-docs/so/{$salesOrder->id}", 'public');
        $salesOrder->documents()->create([
            'name' => $file->getClientOriginalName(), 'category' => $data['category'] ?: 'Reference',
            'file_path' => $path, 'disk' => 'public', 'file_type' => $file->getClientMimeType(),
            'extension' => $ext, 'file_size' => $file->getSize(), 'icon_class' => OrderDocument::iconFor($ext),
            'uploaded_by' => $salesOrder->created_by, 'is_active' => true,
        ]);
        return back()->with('success', 'Document uploaded.');
    }

    public function pdf(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer.country', 'purchaseOrder', 'lines.product', 'lines.batch', 'creator']);
        return Pdf::loadView('orders.so-pdf', ['so' => $salesOrder])->setPaper('a4')
            ->download($salesOrder->so_number . '.pdf');
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    private function payload(SalesOrder $so): array
    {
        $units = $so->lines->sum('quantity');
        return [
            'pid'        => $so->id,
            'id'         => $so->so_number,
            'linkedPo'   => $so->purchaseOrder?->po_number,
            'customer'   => $so->customer?->name ?? '—',
            'country'    => $so->customer?->country?->name ?? '',
            'products'   => $so->lines->count() . ' SKU' . ($so->lines->count() === 1 ? '' : 's') . ' / ' . number_format($units) . ' units',
            'soDate'     => $so->so_date?->format('d M Y'),
            'value'      => $so->currency . ' ' . number_format((float) $so->total_value, 2),
            'status'     => $so->status_label,
            'statusClass' => $so->status_badge_class,
            'linkedPi'   => $so->proformaInvoice?->pi_number,
            'piStatus'   => $so->proformaInvoice?->status_label,
            'ciCount'    => $so->proformaInvoice?->commercialInvoices?->count() ?? 0,
            'ciStatus'   => $so->proformaInvoice?->commercialInvoices?->sortByDesc('id')->first()?->status_label,
            'allocated'  => $so->relationLoaded('units') ? $so->units->count() : null,
            'lines'      => $so->lines->map(fn ($l) => [
                'product' => $l->product?->name,
                'prn'     => $l->product?->prn,
                'batch'   => $l->batch?->brn ?? '—',
                'qty'     => $l->quantity,
                'unitPrice' => $so->currency . ' ' . number_format((float) $l->unit_price, 2),
                'total'   => $so->currency . ' ' . number_format((float) $l->line_total, 2),
            ])->values(),
            'docs'       => $so->documents->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name, 'category' => $d->category, 'size' => $d->size_human,
                'uploadedBy' => $d->uploader?->name ?? '—', 'date' => $d->created_at?->format('d M Y'),
                'iconClass' => $d->icon_class, 'url' => route('orders.so.documents.download', $d->id),
                'del' => route('orders.so.documents.destroy', $d->id),
            ])->values(),
        ];
    }

    /**
     * Allocate units for a sales-order line: by explicit serial range/specific
     * (e.g. "1-50" or "1,3,6") within the batch, else the first available
     * `quantity` unsold units of the batch. Stamps each unit with the SO + buyer.
     */
    private function allocateUnits(array $line, int $customerId, int $soId): array
    {
        if (empty($line['batch_id'])) {
            return [];
        }
        $q = BatchUnit::where('batch_id', $line['batch_id'])
            ->whereNull('sales_order_id')->whereNull('customer_sale_id');

        $serials = $this->parseSerialList($line['serials'] ?? '');
        if ($serials) {
            $q->whereIn('serial_number', $serials);
        } else {
            $q->orderBy('id')->limit((int) $line['quantity']);
        }

        $units = $q->get();
        foreach ($units as $u) {
            $u->forceFill([
                'sales_order_id' => $soId,
                'sold_to_id'     => $customerId,
                'sold_at'        => now(),
            ])->save();
        }
        return $units->all();
    }

    /** Parse "1,3,6,8" / "1-5,10-12" into a sorted unique serial list (cap 50000). */
    private function parseSerialList(string $q): array
    {
        $q = trim($q);
        if ($q === '' || !preg_match('/^[\d\s,\-]+$/', $q)) {
            return [];
        }
        $out = [];
        foreach (preg_split('/[\s,]+/', $q, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (preg_match('/^(\d+)-(\d+)$/', $token, $m)) {
                [$a, $b] = [(int) $m[1], (int) $m[2]];
                if ($b < $a) [$a, $b] = [$b, $a];
                for ($i = $a; $i <= $b && count($out) < 50000; $i++) $out[] = $i;
            } elseif (ctype_digit($token)) {
                $out[] = (int) $token;
            }
            if (count($out) >= 50000) break;
        }
        return array_values(array_unique($out));
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
}
