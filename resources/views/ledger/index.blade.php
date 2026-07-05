@extends('layouts.app')
@section('title', 'Customer Ledger')

@section('content')
<div>
  <div class="page-header">
    <div>
      <h1>Customer Ledger</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Customer Ledger</div>
    </div>
  </div>

  {{-- Summary cards --}}
  <div class="row g-3 mb-3">
    @php $cards = [
      ['Total Invoiced', $totals['currency'].' '.number_format($totals['invoiced'],2), 'bi-receipt-cutoff', '#4f46e5'],
      ['Total Paid',     $totals['currency'].' '.number_format($totals['paid'],2),     'bi-cash-coin',      '#10b981'],
      ['Total Dues',     $totals['currency'].' '.number_format($totals['due'],2),      'bi-exclamation-circle', '#f43f5e'],
    ]; @endphp
    @foreach($cards as [$label,$value,$icon,$color])
      <div class="col-12 col-md-4"><div class="card-soft p-3 d-flex align-items-center gap-3">
        <div class="d-inline-flex align-items-center justify-content-center rounded text-white" style="width:44px;height:44px;background:{{ $color }}"><i class="bi {{ $icon }}"></i></div>
        <div class="min-w-0"><div class="fw-bold fs-5 text-truncate">{{ $value }}</div><div class="text-muted small">{{ $label }}</div></div>
      </div></div>
    @endforeach
  </div>

  {{-- Filters --}}
  <form method="GET" class="card-soft p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small text-muted mb-1">Search customer</label>
        <input type="search" name="search" value="{{ $filters['search'] }}" class="form-control form-control-sm" placeholder="Name or code…">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Customer</label>
        <select name="customer_id" class="form-select form-select-sm">
          <option value="">All my customers</option>
          @foreach($customerOptions as $c)<option value="{{ $c['id'] }}" @selected($filters['customer_id']==$c['id'])>{{ $c['name'] }} ({{ $c['code'] }})</option>@endforeach
        </select>
      </div>
      <div class="col-6 col-md-2"><label class="form-label small text-muted mb-1">From</label><input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm"></div>
      <div class="col-6 col-md-2"><label class="form-label small text-muted mb-1">To</label><input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm"></div>
      <div class="col-md-1 d-grid"><button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i></button></div>
    </div>
    @if($filters['from'] || $filters['to'] || $filters['customer_id'] || $filters['search'])
      <a href="{{ route('ledger.index') }}" class="small text-decoration-none mt-2 d-inline-block"><i class="bi bi-x-circle me-1"></i>Clear filters</a>
    @endif
  </form>

  {{-- Customer-wise ledger --}}
  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th>Customer</th><th class="text-center">Invoices</th><th class="text-end">Invoiced</th><th class="text-end">Paid</th><th class="text-end">Due</th><th style="width:60px"></th></tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              <tr>
                <td class="fw-semibold" style="font-size:13px">{{ $r['customer'] }}<div class="text-muted small font-monospace">{{ $r['code'] }}</div></td>
                <td class="text-center">{{ $r['count'] }}</td>
                <td class="text-end">{{ $r['currency'] }} {{ $r['invoiced'] }}</td>
                <td class="text-end text-success">{{ $r['currency'] }} {{ $r['paid'] }}</td>
                <td class="text-end fw-bold {{ $r['dueNum'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $r['currency'] }} {{ $r['due'] }}</td>
                <td class="text-end"><a href="{{ route('ledger.show', ['customer' => $r['id'], 'from' => $filters['from'], 'to' => $filters['to']]) }}" class="btn btn-outline-secondary btn-sm btn-icon" title="Statement"><i class="bi bi-eye"></i></a></td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-journal-text" style="font-size:32px;opacity:.2"></i><div class="mt-2">No invoiced customers in this range.</div></td></tr>
            @endforelse
          </tbody>
          @if($rows->isNotEmpty())
          <tfoot class="table-light">
            <tr class="fw-bold"><td>Totals</td><td></td><td class="text-end">{{ $totals['currency'] }} {{ number_format($totals['invoiced'],2) }}</td><td class="text-end text-success">{{ $totals['currency'] }} {{ number_format($totals['paid'],2) }}</td><td class="text-end text-danger">{{ $totals['currency'] }} {{ number_format($totals['due'],2) }}</td><td></td></tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
