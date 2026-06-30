<?php

namespace App\Http\Controllers;

use App\Models\CommercialInvoice;
use App\Models\CommercialInvoiceLine;
use App\Models\OrderDocument;
use App\Models\ProformaInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CommercialInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $mine = !$request->user()->canViewAll('invoices');
        $uid  = $request->user()->id;
        $own  = fn ($q) => $mine ? $q->where('created_by', $uid) : $q;

        $invoices = $own(CommercialInvoice::with(['proformaInvoice.salesOrder.customer.country', 'lines.product', 'lines.batch', 'documents']))
            ->latest()->limit(300)->get();

        $cis = $invoices->map(fn ($ci) => $this->payload($ci))->values();

        $stats = [
            'total'    => $own(CommercialInvoice::query())->count(),
            'pending'  => $own(CommercialInvoice::where('status', 'pending_approval'))->count(),
            'approved' => $own(CommercialInvoice::whereIn('status', ['approved', 'shipment_created']))->count(),
            'partial'  => $this->partiallyInvoicedPiCount($own),
        ];

        // Approved PIs with quantity still left to invoice — source for "Raise CI" (own PIs).
        $approvedPis = $own(ProformaInvoice::with(['salesOrder.customer', 'lines.product', 'commercialInvoices.lines']))
            ->where('status', 'approved')->latest()->get()
            ->map(function ($pi) {
                $remaining = $this->remainingByLine($pi);
                $lines = $pi->lines->map(fn ($l) => [
                    'pi_line_id'  => $l->id,
                    'product_id'  => $l->product_id,
                    'product'     => $l->product?->name,
                    'prn'         => $l->product?->prn,
                    'batch_id'    => $l->batch_id,
                    'ordered'     => $l->quantity,
                    'remaining'   => $remaining[$l->id] ?? 0,
                    'unit_price'  => (float) $l->unit_price,
                ])->filter(fn ($l) => $l['remaining'] > 0)->values();
                return [
                    'id'       => $pi->id,
                    'number'   => $pi->pi_number,
                    'customer' => $pi->salesOrder?->customer?->name,
                    'currency' => $pi->currency,
                    'incoterms' => $pi->incoterms,
                    'lines'    => $lines,
                ];
            })
            ->filter(fn ($pi) => count($pi['lines']) > 0)->values();

        return view('orders.ci', compact('cis', 'stats', 'approvedPis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'proforma_invoice_id'  => 'required|exists:proforma_invoices,id',
            'ci_date'              => 'required|date',
            'hs_code'              => 'nullable|string|max:20',
            'country_of_origin'    => 'nullable|string|max:5',
            'incoterms'            => 'nullable|string|max:10',
            'port_of_loading'      => 'nullable|string|max:120',
            'port_of_discharge'    => 'nullable|string|max:120',
            'freight'              => 'nullable|numeric|min:0',
            'insurance'            => 'nullable|numeric|min:0',
            'status'               => 'nullable|in:draft,pending_approval',
            'remarks'              => 'nullable|string|max:2000',
            'lines'                  => 'required|array|min:1',
            'lines.*.pi_line_id'     => 'required|exists:proforma_invoice_lines,id',
            'lines.*.quantity'       => 'required|integer|min:0',
            'lines.*.unit_price'     => 'nullable|numeric|min:0',
            'lines.*.net_weight_kg'  => 'nullable|numeric|min:0',
            'lines.*.gross_weight_kg' => 'nullable|numeric|min:0',
        ]);

        $pi = ProformaInvoice::with(['lines', 'commercialInvoices.lines'])->findOrFail($data['proforma_invoice_id']);
        if ($pi->status !== 'approved') {
            return back()->with('error', 'The Proforma Invoice must be approved before raising a Commercial Invoice.');
        }

        $remaining = $this->remainingByLine($pi);
        $piLines   = $pi->lines->keyBy('id');

        // Validate each line against its remaining quantity.
        foreach ($data['lines'] as $i => $line) {
            $qty = (int) $line['quantity'];
            if ($qty <= 0) continue;
            $rem = $remaining[$line['pi_line_id']] ?? 0;
            if (!$piLines->has($line['pi_line_id'])) {
                return back()->with('error', 'A line does not belong to this Proforma Invoice.');
            }
            if ($qty > $rem) {
                return back()->with('error', "Line " . ($i + 1) . ": quantity {$qty} exceeds the {$rem} units still to be invoiced.");
            }
        }

        $ci = DB::transaction(function () use ($data, $pi, $piLines) {
            $ci = CommercialInvoice::create([
                'ci_number'           => CommercialInvoice::nextNumber(),
                'proforma_invoice_id' => $pi->id,
                'created_by'          => $pi->created_by,
                'ci_date'             => $data['ci_date'],
                'hs_code'             => $data['hs_code'] ?? '',
                'country_of_origin'   => strtoupper($data['country_of_origin'] ?? 'US'),
                'incoterms'           => $data['incoterms'] ?? $pi->incoterms,
                'port_of_loading'     => $data['port_of_loading'] ?? $pi->port_of_loading,
                'port_of_discharge'   => $data['port_of_discharge'] ?? null,
                'currency'            => $pi->currency,
                'payment_terms'       => $pi->payment_terms,
                'bank_name'           => $pi->bank_name,
                'bank_account_number' => $pi->bank_account_number,
                'bank_swift_code'     => $pi->bank_swift_code,
                'freight'             => $data['freight'] ?? 0,
                'insurance'           => $data['insurance'] ?? 0,
                'status'              => $data['status'] ?? 'draft',
                'remarks'             => $data['remarks'] ?? null,
            ]);

            $subtotal = 0; $n = 0;
            foreach ($data['lines'] as $line) {
                $qty = (int) $line['quantity'];
                if ($qty <= 0) continue;
                $piLine = $piLines->get($line['pi_line_id']);
                $price  = $line['unit_price'] !== null && $line['unit_price'] !== '' ? (float) $line['unit_price'] : (float) $piLine->unit_price;
                $n++;
                CommercialInvoiceLine::create([
                    'commercial_invoice_id'    => $ci->id,
                    'proforma_invoice_line_id' => $piLine->id,
                    'product_id'               => $piLine->product_id,
                    'batch_id'                 => $piLine->batch_id,
                    'line_number'              => $n,
                    'product_description'      => $piLine->product?->name,
                    'quantity'                 => $qty,
                    'unit_price'               => $price,
                    'line_total'               => $qty * $price,
                    'net_weight_kg'            => $line['net_weight_kg'] ?? null,
                    'gross_weight_kg'          => $line['gross_weight_kg'] ?? null,
                ]);
                $subtotal += $qty * $price;
            }

            $total = $subtotal + (float) ($data['freight'] ?? 0) + (float) ($data['insurance'] ?? 0);
            $ci->update(['subtotal' => $subtotal, 'total_value' => $total]);
            return $ci;
        });

        // If everything on the PI is now invoiced, complete the chain.
        $pi->load('commercialInvoices.lines');
        $fullyInvoiced = collect($this->remainingByLine($pi))->every(fn ($r) => $r <= 0);
        if ($fullyInvoiced) {
            $pi->salesOrder?->update(['status' => 'completed']);
        }

        $note = $fullyInvoiced ? ' PI fully invoiced.' : ' (partial — balance remains on the PI).';
        return back()->with('success', "Commercial Invoice {$ci->ci_number} raised." . $note);
    }

    public function show(CommercialInvoice $commercialInvoice): JsonResponse
    {
        $commercialInvoice->load(['proformaInvoice.salesOrder.customer.country', 'lines.product', 'lines.batch', 'documents.uploader']);
        return response()->json($this->payload($commercialInvoice));
    }

    public function updateStatus(Request $request, CommercialInvoice $commercialInvoice): RedirectResponse
    {
        $data = $request->validate(['action' => 'required|in:send,approve,cancel']);
        switch ($data['action']) {
            case 'send':
                if ($commercialInvoice->status === 'draft') $commercialInvoice->update(['status' => 'pending_approval']);
                $msg = 'sent for approval';
                break;
            case 'approve':
                $commercialInvoice->update(['status' => 'approved', 'approved_by' => $commercialInvoice->created_by, 'approved_at' => now()]);
                $msg = 'approved';
                break;
            default:
                $commercialInvoice->update(['status' => 'cancelled']);
                $msg = 'cancelled';
        }
        return back()->with('success', "Commercial Invoice {$commercialInvoice->ci_number} {$msg}.");
    }

    public function storeDoc(Request $request, CommercialInvoice $commercialInvoice): RedirectResponse
    {
        $data = $request->validate([
            'file'     => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,webp',
            'category' => 'nullable|string|max:80',
        ]);
        $file = $data['file'];
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->store("order-docs/ci/{$commercialInvoice->id}", 'public');
        $commercialInvoice->documents()->create([
            'name' => $file->getClientOriginalName(), 'category' => $data['category'] ?: 'Reference',
            'file_path' => $path, 'disk' => 'public', 'file_type' => $file->getClientMimeType(),
            'extension' => $ext, 'file_size' => $file->getSize(), 'icon_class' => OrderDocument::iconFor($ext),
            'uploaded_by' => $commercialInvoice->created_by, 'is_active' => true,
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

    public function pdf(CommercialInvoice $commercialInvoice)
    {
        $commercialInvoice->load(['proformaInvoice.salesOrder.customer.country', 'lines.product', 'lines.batch', 'creator']);
        return Pdf::loadView('orders.ci-pdf', ['ci' => $commercialInvoice])->setPaper('a4')
            ->download($commercialInvoice->ci_number . '.pdf');
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    /** Remaining (un-invoiced) quantity per PI line id, across non-cancelled CIs. */
    private function remainingByLine(ProformaInvoice $pi): array
    {
        $invoiced = [];
        foreach ($pi->commercialInvoices as $ci) {
            if ($ci->status === 'cancelled') continue;
            foreach ($ci->lines as $cl) {
                $invoiced[$cl->proforma_invoice_line_id] = ($invoiced[$cl->proforma_invoice_line_id] ?? 0) + $cl->quantity;
            }
        }
        $remaining = [];
        foreach ($pi->lines as $l) {
            $remaining[$l->id] = max(0, $l->quantity - ($invoiced[$l->id] ?? 0));
        }
        return $remaining;
    }

    private function partiallyInvoicedPiCount(?\Closure $own = null): int
    {
        $q = ProformaInvoice::with(['lines', 'commercialInvoices.lines'])->whereHas('commercialInvoices');
        if ($own) {
            $q = $own($q);
        }
        return $q->get()
            ->filter(fn ($pi) => collect($this->remainingByLine($pi))->sum() > 0)
            ->count();
    }

    private function payload(CommercialInvoice $ci): array
    {
        $pi = $ci->proformaInvoice;
        $cust = $pi?->salesOrder?->customer;
        return [
            'pid'        => $ci->id,
            'id'         => $ci->ci_number,
            'linkedPi'   => $pi?->pi_number,
            'customer'   => $cust?->name ?? '—',
            'country'    => $cust?->country?->name ?? '',
            'ciDate'     => $ci->ci_date?->format('d M Y'),
            'value'      => $ci->currency . ' ' . number_format((float) $ci->total_value, 2),
            'status'     => $ci->status_label,
            'statusClass' => $ci->status_badge_class,
            'hsCode'     => $ci->hs_code,
            'origin'     => $ci->country_of_origin,
            'incoterms'  => $ci->incoterms,
            'lines'      => $ci->lines->map(fn ($l) => [
                'product' => $l->product?->name, 'prn' => $l->product?->prn, 'batch' => $l->batch?->brn ?? '—',
                'qty' => $l->quantity, 'unitPrice' => $ci->currency . ' ' . number_format((float) $l->unit_price, 2),
                'total' => $ci->currency . ' ' . number_format((float) $l->line_total, 2),
            ])->values(),
            'docs'       => $ci->documents->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name, 'category' => $d->category, 'size' => $d->size_human,
                'uploadedBy' => $d->uploader?->name ?? '—', 'date' => $d->created_at?->format('d M Y'),
                'iconClass' => $d->icon_class, 'url' => route('orders.ci.documents.download', $d->id),
                'del' => route('orders.ci.documents.destroy', $d->id),
            ])->values(),
        ];
    }
}
