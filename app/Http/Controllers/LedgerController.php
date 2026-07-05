<?php

namespace App\Http\Controllers;

use App\Models\CommercialInvoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer ledger — invoiced / paid / due per customer, date-filterable.
 * Scoped to the signed-in user's own customers unless they can view all.
 */
class LedgerController extends Controller
{
    public function index(Request $request): View
    {
        $user   = $request->user();
        $mine   = ! $user->canViewAll('customers');
        $ownIds = $user->ownedCustomerIds();

        $filters = [
            'from'        => $request->query('from', ''),
            'to'          => $request->query('to', ''),
            'customer_id' => $request->query('customer_id', ''),
            'search'      => trim((string) $request->query('search', '')),
        ];

        $customers = Customer::query()
            ->when($mine, fn ($q) => $q->whereIn('id', $ownIds ?: [0]))
            ->when($filters['customer_id'], fn ($q) => $q->where('id', $filters['customer_id']))
            ->when($filters['search'], fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$filters['search']}%")->orWhere('customer_code', 'like', "%{$filters['search']}%")))
            ->orderBy('name')->get();

        $rows = $customers->map(fn ($c) => $this->summaryFor($c, $filters['from'], $filters['to']))
            ->filter(fn ($r) => $r['count'] > 0 || $filters['customer_id'])   // hide customers with no invoices unless explicitly filtered
            ->values();

        $totals = [
            'invoiced' => $rows->sum('invoicedNum'),
            'paid'     => $rows->sum('paidNum'),
            'due'      => $rows->sum('dueNum'),
            'currency' => $rows->first()['currency'] ?? 'USD',
        ];

        // Customer options for the filter (scoped).
        $customerOptions = $customers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->customer_code]);

        return view('ledger.index', compact('rows', 'totals', 'filters', 'customerOptions'));
    }

    public function show(Request $request, Customer $customer): View
    {
        $user = $request->user();
        // A scoped user may only open a customer they own.
        if (! $user->canViewAll('customers')) {
            abort_unless(in_array((int) $customer->id, $user->ownedCustomerIds(), true), 403);
        }

        $filters = ['from' => $request->query('from', ''), 'to' => $request->query('to', '')];

        $cis = $this->invoicesQuery($customer, $filters['from'], $filters['to'])
            ->with('payments.recorder', 'lines')->orderBy('ci_date')->get();

        $statement = $cis->map(fn ($ci) => [
            'number'   => $ci->ci_number,
            'date'     => $ci->ci_date?->format('d M Y'),
            'currency' => $ci->currency,
            'invoiced' => number_format($ci->payable_amount, 2),
            'paid'     => number_format($ci->paid_amount, 2),
            'due'      => number_format($ci->due_amount, 2),
            'status'   => $ci->payment_status,
            'payments' => $ci->payments->map(fn ($p) => [
                'date' => $p->paid_on?->format('d M Y'), 'amount' => number_format((float) $p->amount, 2),
                'method' => $p->method_label, 'reference' => $p->reference,
            ])->values(),
        ]);

        $summary = $this->summaryFor($customer, $filters['from'], $filters['to']);

        return view('ledger.show', compact('customer', 'statement', 'summary', 'filters'));
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    private function invoicesQuery(Customer $customer, ?string $from, ?string $to)
    {
        return CommercialInvoice::whereHas('proformaInvoice.salesOrder', fn ($s) => $s->where('customer_id', $customer->id))
            ->where('status', '!=', 'cancelled')
            ->when($from, fn ($q) => $q->whereDate('ci_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('ci_date', '<=', $to));
    }

    private function summaryFor(Customer $customer, ?string $from, ?string $to): array
    {
        $cis = $this->invoicesQuery($customer, $from, $to)->with('payments', 'lines')->get();

        $invoiced = (float) $cis->sum(fn ($ci) => $ci->payable_amount);
        $paid     = (float) $cis->sum(fn ($ci) => $ci->paid_amount);
        $due      = max(0, $invoiced - $paid);

        return [
            'id'          => $customer->id,
            'customer'    => $customer->name,
            'code'        => $customer->customer_code,
            'currency'    => $cis->first()?->currency ?? 'USD',
            'count'       => $cis->count(),
            'invoicedNum' => $invoiced, 'invoiced' => number_format($invoiced, 2),
            'paidNum'     => $paid,     'paid'     => number_format($paid, 2),
            'dueNum'      => $due,      'due'      => number_format($due, 2),
        ];
    }
}
