@extends('layouts.app')
@section('title', 'Ledger — '.$customer->name)

@section('content')
<div>
  <div class="page-header">
    <div>
      <h1>{{ $customer->name }} <span class="text-muted fs-6 font-monospace">{{ $customer->customer_code }}</span></h1>
      <div class="page-breadcrumb"><a href="{{ route('ledger.index') }}">Customer Ledger</a> / Statement</div>
    </div>
    <div><a href="{{ route('ledger.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a></div>
  </div>

  <div class="row g-3 mb-3">
    @php $cards = [
      ['Invoiced', $summary['currency'].' '.$summary['invoiced'], '#4f46e5'],
      ['Paid',     $summary['currency'].' '.$summary['paid'],     '#10b981'],
      ['Due',      $summary['currency'].' '.$summary['due'],      '#f43f5e'],
    ]; @endphp
    @foreach($cards as [$label,$value,$color])
      <div class="col-4"><div class="card-soft p-3 text-center"><div class="fw-bold" style="font-size:17px;color:{{ $color }}">{{ $value }}</div><div class="text-muted small">{{ $label }}</div></div></div>
    @endforeach
  </div>

  <form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div><label class="form-label small text-muted mb-1">From</label><input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm"></div>
    <div><label class="form-label small text-muted mb-1">To</label><input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm"></div>
    <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Apply</button>
    @if($filters['from'] || $filters['to'])<a href="{{ route('ledger.show', $customer) }}" class="btn btn-outline-secondary btn-sm">Clear</a>@endif
  </form>

  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr><th>Invoice</th><th>Date</th><th class="text-end">Invoiced</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th></tr>
          </thead>
          <tbody>
            @forelse($statement as $s)
              <tr>
                <td class="font-monospace fw-semibold">{{ $s['number'] }}</td>
                <td>{{ $s['date'] }}</td>
                <td class="text-end">{{ $s['currency'] }} {{ $s['invoiced'] }}</td>
                <td class="text-end text-success">{{ $s['currency'] }} {{ $s['paid'] }}</td>
                <td class="text-end fw-semibold text-danger">{{ $s['currency'] }} {{ $s['due'] }}</td>
                <td>@php $tone = ['paid'=>'success','partial'=>'warning','unpaid'=>'danger'][$s['status']] ?? 'secondary'; @endphp<span class="badge bg-{{ $tone }}-subtle text-{{ $tone }} text-capitalize">{{ $s['status'] }}</span></td>
              </tr>
              @foreach($s['payments'] as $p)
                <tr class="table-light">
                  <td class="ps-4 small text-muted" colspan="2"><i class="bi bi-cash-coin me-1 text-success"></i>Payment · {{ $p['method'] }}{{ $p['reference'] ? ' · '.$p['reference'] : '' }}</td>
                  <td></td><td class="text-end small text-success">{{ $s['currency'] }} {{ $p['amount'] }}</td><td></td><td class="small text-muted">{{ $p['date'] }}</td>
                </tr>
              @endforeach
            @empty
              <tr><td colspan="6" class="text-center py-5 text-muted">No invoices for this customer in the selected range.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
