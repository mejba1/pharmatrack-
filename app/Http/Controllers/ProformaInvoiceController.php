<?php

namespace App\Http\Controllers;

use App\Models\OrderDocument;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceLine;
use App\Models\SalesOrder;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProformaInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $mine   = !$request->user()->canViewAll('invoices');
        $uid    = $request->user()->id;
        $ownIds = $request->user()->ownedCustomerIds();
        // Scoped users see PIs they created OR raised against their customers.
        $own = fn ($q) => $mine
            ? $q->where(fn ($w) => $w->where('created_by', $uid)->orWhereHas('salesOrder', fn ($s) => $s->whereIn('customer_id', $ownIds ?: [0])))
            : $q;

        $invoices = $own(ProformaInvoice::with(['salesOrder.customer.country', 'lines.product', 'lines.batch', 'documents']))
            ->latest()->limit(300)->get();

        $pis = $invoices->map(fn ($pi) => $this->payload($pi))->values();

        $stats = [
            'total'    => $own(ProformaInvoice::query())->count(),
            'pending'  => $own(ProformaInvoice::whereIn('status', ['sent', 'pending_approval']))->count(),
            'approved' => $own(ProformaInvoice::where('status', 'approved'))->count(),
            'rejected' => $own(ProformaInvoice::where('status', 'rejected'))->count(),
        ];

        // Confirmed SOs not yet invoiced — source for "Issue PI" (own SOs only).
        $soOwn = fn ($q) => $mine ? $q->where(fn ($w) => $w->where('created_by', $uid)->orWhereIn('customer_id', $ownIds ?: [0])) : $q;
        $confirmedSos = $soOwn(SalesOrder::with(['customer', 'lines.product']))
            ->where('status', 'confirmed')->whereDoesntHave('proformaInvoice')
            ->latest()->get()->map(fn ($so) => [
                'id'       => $so->id,
                'number'   => $so->so_number,
                'customer' => $so->customer?->name,
                'currency' => $so->currency,
                'incoterms' => $so->incoterms,
                'payment'  => $so->payment_terms,
                'total'    => (float) $so->total_value,
                'lineCount' => $so->lines->count(),
                'units'    => $so->lines->sum('quantity'),
            ]);

        $managers = User::orderBy('name')->get(['id', 'name', 'role']);

        // Saved bank accounts for the invoice bank picker (auto-fills bank fields).
        $bankAccounts = \App\Models\BankAccount::where('is_active', true)
            ->orderByDesc('is_default')->orderBy('bank_name')
            ->get(['id', 'bank_name', 'account_name', 'account_number', 'swift_code', 'iban', 'branch', 'address', 'currency', 'is_default']);

        return view('orders.pi', compact('pis', 'stats', 'confirmedSos', 'managers', 'bankAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sales_order_id'      => 'required|exists:sales_orders,id',
            'pi_date'             => 'required|date',
            'valid_until'         => 'required|date',
            'currency'            => 'nullable|string|max:3',
            'incoterms'           => 'nullable|string|max:10',
            'payment_terms'       => 'nullable|string|max:120',
            'port_of_loading'     => 'nullable|string|max:120',
            'bank_name'           => 'nullable|string|max:160',
            'bank_account_number' => 'nullable|string|max:60',
            'bank_swift_code'     => 'nullable|string|max:15',
            'bank_iban'           => 'nullable|string|max:60',
            'freight'             => 'nullable|numeric|min:0',
            'status'              => 'nullable|in:draft,sent',
            'remarks'             => 'nullable|string|max:2000',
        ]);

        $so = SalesOrder::with('lines')->findOrFail($data['sales_order_id']);
        if ($so->status !== 'confirmed') {
            return back()->with('error', 'Only a confirmed Sales Order can be invoiced.');
        }
        if ($so->proformaInvoice()->exists()) {
            return back()->with('error', 'This Sales Order already has a Proforma Invoice.');
        }

        $pi = DB::transaction(function () use ($data, $so) {
            $freight  = (float) ($data['freight'] ?? 0);
            $subtotal = (float) $so->lines->sum('line_total');

            $pi = ProformaInvoice::create([
                'pi_number'           => ProformaInvoice::nextNumber(),
                'sales_order_id'      => $so->id,
                'created_by'          => $so->created_by,
                'pi_date'             => $data['pi_date'],
                'valid_until'         => $data['valid_until'],
                'currency'            => strtoupper($data['currency'] ?? $so->currency),
                'incoterms'           => $data['incoterms'] ?? $so->incoterms,
                'payment_terms'       => $data['payment_terms'] ?? $so->payment_terms,
                'port_of_loading'     => $data['port_of_loading'] ?? null,
                'bank_name'           => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_swift_code'     => $data['bank_swift_code'] ?? null,
                'bank_iban'           => $data['bank_iban'] ?? null,
                'subtotal'            => $subtotal,
                'tax_amount'          => 0,
                'freight'             => $freight,
                'total_value'         => $subtotal + $freight,
                'status'              => $data['status'] ?? 'draft',
                'remarks'             => $data['remarks'] ?? null,
            ]);

            foreach ($so->lines as $n => $l) {
                ProformaInvoiceLine::create([
                    'proforma_invoice_id' => $pi->id,
                    'product_id'          => $l->product_id,
                    'batch_id'            => $l->batch_id,
                    'line_number'         => $n + 1,
                    'quantity'            => $l->quantity,
                    'unit_price'          => $l->unit_price,
                    'line_total'          => $l->line_total,
                ]);
            }

            $so->update(['status' => 'pi_issued']);
            return $pi;
        });

        return back()->with('success', "Proforma Invoice {$pi->pi_number} issued.");
    }

    public function show(ProformaInvoice $proformaInvoice): JsonResponse
    {
        $proformaInvoice->load(['salesOrder.customer.country', 'lines.product', 'lines.batch', 'documents.uploader', 'approver']);
        return response()->json($this->payload($proformaInvoice));
    }

    public function updateStatus(Request $request, ProformaInvoice $proformaInvoice): RedirectResponse
    {
        $data = $request->validate([
            'action'           => 'required|in:send,approve,reject',
            'approved_by'      => 'nullable|exists:users,id',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        switch ($data['action']) {
            case 'send':
                if (in_array($proformaInvoice->status, ['draft', 'rejected'], true)) {
                    $proformaInvoice->update(['status' => 'pending_approval']);
                }
                $msg = 'sent for finance approval';
                break;
            case 'approve':
                $proformaInvoice->update([
                    'status'      => 'approved',
                    'approved_by' => $data['approved_by'] ?? $proformaInvoice->created_by,
                    'approved_at' => now(),
                ]);
                $msg = 'approved';
                break;
            default:
                $proformaInvoice->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason'] ?? null]);
                $msg = 'rejected';
        }

        return back()->with('success', "Proforma Invoice {$proformaInvoice->pi_number} {$msg}.");
    }

    public function storeDoc(Request $request, ProformaInvoice $proformaInvoice): RedirectResponse
    {
        $data = $request->validate([
            'file'     => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,webp',
            'category' => 'nullable|string|max:80',
        ]);
        $file = $data['file'];
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->store("order-docs/pi/{$proformaInvoice->id}", 'public');
        $proformaInvoice->documents()->create([
            'name' => $file->getClientOriginalName(), 'category' => $data['category'] ?: 'Reference',
            'file_path' => $path, 'disk' => 'public', 'file_type' => $file->getClientMimeType(),
            'extension' => $ext, 'file_size' => $file->getSize(), 'icon_class' => OrderDocument::iconFor($ext),
            'uploaded_by' => $proformaInvoice->created_by, 'is_active' => true,
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

    public function pdf(ProformaInvoice $proformaInvoice)
    {
        $proformaInvoice->load(['salesOrder.customer.country', 'lines.product', 'lines.batch', 'creator']);
        return Pdf::loadView('orders.pi-pdf', ['pi' => $proformaInvoice])->setPaper('a4')
            ->download($proformaInvoice->pi_number . '.pdf');
    }

    private function payload(ProformaInvoice $pi): array
    {
        $so = $pi->salesOrder;
        return [
            'pid'        => $pi->id,
            'id'         => $pi->pi_number,
            'linkedSo'   => $so?->so_number,
            'customer'   => $so?->customer?->name ?? '—',
            'country'    => $so?->customer?->country?->name ?? '',
            'piDate'     => $pi->pi_date?->format('d M Y'),
            'validUntil' => $pi->valid_until?->format('d M Y'),
            'value'      => $pi->currency . ' ' . number_format((float) $pi->total_value, 2),
            'status'     => $pi->status_label,
            'statusClass' => $pi->status_badge_class,
            'bank'       => $pi->bank_name,
            'swift'      => $pi->bank_swift_code,
            'payTerms'   => $pi->payment_terms,
            'rejection'  => $pi->rejection_reason,
            'lines'      => $pi->lines->map(fn ($l) => [
                'product' => $l->product?->name, 'prn' => $l->product?->prn, 'batch' => $l->batch?->brn ?? '—',
                'qty' => $l->quantity, 'unitPrice' => $pi->currency . ' ' . number_format((float) $l->unit_price, 2),
                'total' => $pi->currency . ' ' . number_format((float) $l->line_total, 2),
            ])->values(),
            'docs'       => $pi->documents->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name, 'category' => $d->category, 'size' => $d->size_human,
                'uploadedBy' => $d->uploader?->name ?? '—', 'date' => $d->created_at?->format('d M Y'),
                'iconClass' => $d->icon_class, 'url' => route('orders.pi.documents.download', $d->id),
                'del' => route('orders.pi.documents.destroy', $d->id),
            ])->values(),
        ];
    }
}
