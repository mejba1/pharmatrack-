@extends('layouts.app')
@section('title', 'Trace Purchase')

@section('content')
<div class="page-header">
  <div>
    <h1>Trace Purchase</h1>
    <div class="page-breadcrumb">Sales / Trace</div>
  </div>
</div>

<div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>Enter a <strong>UUC / serial code</strong> or a <strong>sale reference number</strong> to identify who purchased a product.</div>

<div class="card mb-3"><div class="card-body">
  <form method="GET" action="{{ route('customers.trace') }}" class="row g-2 align-items-end">
    <div class="col-md-9"><label class="form-label">UUC code or Sale reference</label><input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="e.g. XGFDTPHGQ3  or  SAL-20260621-000001" autofocus></div>
    <div class="col-md-3"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Trace</button></div>
  </form>
</div></div>

@if($result)
  @php $sale = $result['sale']; $unit = $result['unit']; @endphp

  @if(!$sale && !$unit)
    <div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i>Nothing found for “{{ $result['searched'] }}”. It may not have been sold yet, or the code is unknown.</div>
  @endif

  {{-- Unit trace --}}
  @if($unit)
    <div class="card mb-3"><div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-upc-scan me-2 text-primary"></i>Unit {{ $unit->secret_code }}</h6>
      @if($unit->sold_to_id)
        <div class="row g-3">
          <div class="col-md-6"><div class="perm-box">
            <div class="text-muted-sm text-uppercase fw-bold mb-2" style="font-size:11px">Purchased by</div>
            <div class="fs-5 fw-semibold">{{ $unit->soldTo?->name }}</div>
            <div class="font-monospace small text-muted">{{ $unit->soldTo?->customer_code }}</div>
            <div class="small mt-2">{{ $unit->soldTo?->contact_person }} @if($unit->soldTo?->contact_phone)· {{ $unit->soldTo->contact_phone }}@endif</div>
            <a href="{{ route('customers.index', ['search' => $unit->soldTo?->customer_code]) }}" class="btn btn-outline-primary btn-sm mt-2"><i class="bi bi-person-badge me-1"></i>Open customer</a>
          </div></div>
          <div class="col-md-6"><div class="perm-box">
            <div class="text-muted-sm text-uppercase fw-bold mb-2" style="font-size:11px">Unit / sale</div>
            <table class="table table-sm mb-0">
              <tr><td class="text-muted" style="width:130px">Product</td><td>{{ $unit->batch?->product?->name ?? '—' }}</td></tr>
              <tr><td class="text-muted">Batch</td><td>{{ $unit->batch?->brn ?? '—' }}</td></tr>
              <tr><td class="text-muted">Serial</td><td>#{{ $unit->serial_number }}</td></tr>
              <tr><td class="text-muted">Sale ref</td><td class="font-monospace">{{ $unit->sale?->reference_number ?? '—' }}</td></tr>
              <tr><td class="text-muted">Sold at</td><td>{{ $unit->sold_at?->format('d M Y, H:i') ?? '—' }}</td></tr>
            </table>
          </div></div>
        </div>
      @else
        <div class="alert alert-secondary mb-0 py-2"><i class="bi bi-info-circle me-1"></i>This unit exists but has <strong>not been sold</strong> to any customer yet.</div>
      @endif
    </div></div>
  @endif

  {{-- Sale reference trace --}}
  @if($sale)
    <div class="card mb-3"><div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Sale {{ $sale->reference_number }}</h6>
      <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="perm-box">
          <div class="text-muted-sm text-uppercase fw-bold mb-2" style="font-size:11px">Customer</div>
          <div class="fs-5 fw-semibold">{{ $sale->customer?->name }}</div>
          <div class="font-monospace small text-muted">{{ $sale->customer?->customer_code }}</div>
          <a href="{{ route('customers.index', ['search' => $sale->customer?->customer_code]) }}" class="btn btn-outline-primary btn-sm mt-2"><i class="bi bi-person-badge me-1"></i>Open customer</a>
        </div></div>
        <div class="col-md-8"><div class="perm-box">
          <div class="text-muted-sm text-uppercase fw-bold mb-2" style="font-size:11px">Items</div>
          <table class="table table-sm mb-0">
            <thead><tr><th>Product</th><th>Batch</th><th class="text-center">Qty</th></tr></thead>
            <tbody>
              @foreach($sale->items as $it)<tr><td>{{ $it->product?->name }}</td><td>{{ $it->batch?->brn ?? '—' }}</td><td class="text-center">{{ $it->quantity }}</td></tr>@endforeach
            </tbody>
          </table>
        </div></div>
      </div>
      <div class="perm-box">
        <div class="text-muted-sm text-uppercase fw-bold mb-2" style="font-size:11px">Assigned units ({{ $sale->units->count() }})</div>
        <div class="d-flex flex-wrap gap-1">
          @forelse($sale->units as $u)
            <a href="{{ route('customers.trace', ['q' => $u->secret_code]) }}" class="badge bg-light text-dark border font-monospace text-decoration-none">{{ $u->secret_code }}</a>
          @empty
            <span class="text-muted-sm">No specific units were assigned.</span>
          @endforelse
        </div>
      </div>
    </div></div>
  @endif
@endif
@endsection
