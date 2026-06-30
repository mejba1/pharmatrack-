@extends('layouts.portal')
@section('title', 'My Dashboard')

@section('body')
<nav class="navbar navbar-expand bg-white border-bottom px-3 px-md-4">
  <span class="brand fs-5">Pharma<span>Track</span> <span class="text-muted fs-6 fw-normal">Portal</span></span>
  <div class="ms-auto d-flex align-items-center gap-3">
    <div class="d-flex align-items-center gap-2">
      @if($customer->logo_url)
        <img src="{{ $customer->logo_url }}" alt="" style="width:34px;height:34px;border-radius:7px;object-fit:cover">
      @else
        <span class="rounded d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:34px;height:34px;font-size:12px;font-weight:600">{{ $customer->initials }}</span>
      @endif
      <div class="lh-1"><div class="fw-semibold small">{{ $customer->name }}</div><div class="text-muted" style="font-size:11px">{{ $customer->type_label }}</div></div>
    </div>
    <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button></form>
  </div>
</nav>

<div class="container-xl py-4">
  <h4 class="mb-1">Welcome, {{ $customer->name }}</h4>
  <div class="text-muted small mb-4">{{ $customer->customer_code }} · {{ $customer->country?->name }}{{ $customer->city ? ', '.$customer->city : '' }}</div>

  {{-- Stats --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4"><div class="stat-card"><div class="stat-label">Purchase Orders</div><div class="stat-value">{{ $stats['orders'] }}</div></div></div>
    <div class="col-6 col-md-4"><div class="stat-card"><div class="stat-label">Units Purchased</div><div class="stat-value">{{ $stats['units'] }}</div></div></div>
    <div class="col-6 col-md-4"><div class="stat-card"><div class="stat-label">Sales Recorded</div><div class="stat-value">{{ $stats['sales'] }}</div></div></div>
  </div>

  <div class="row g-3">
    {{-- Profile --}}
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100"><div class="card-body">
        <div class="fw-semibold mb-3"><i class="bi bi-person-badge me-1 text-primary"></i>My Profile</div>
        <table class="table table-sm mb-0">
          <tr><td class="text-muted" style="width:120px">Type</td><td>{{ $customer->type_label }}</td></tr>
          <tr><td class="text-muted">Email</td><td>{{ $customer->email ?? '—' }}</td></tr>
          <tr><td class="text-muted">Phone</td><td>{{ $customer->phone ?? '—' }}</td></tr>
          <tr><td class="text-muted">Company</td><td>{{ $customer->company_name ?? '—' }}</td></tr>
          <tr><td class="text-muted">Company ID</td><td>{{ $customer->company_id ?? '—' }}</td></tr>
          <tr><td class="text-muted">Account Mgr</td><td>{{ $customer->manager?->name ?? '—' }}</td></tr>
        </table>
      </div></div>
    </div>

    {{-- Orders + units --}}
    <div class="col-md-8">
      <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="fw-semibold mb-2"><i class="bi bi-cart3 me-1 text-primary"></i>Recent Purchase Orders</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>PO #</th><th>Date</th><th>Status</th><th class="text-end">Value</th></tr></thead>
            <tbody>
              @forelse($orders as $po)
                <tr>
                  <td class="font-monospace small">{{ $po->po_number }}</td>
                  <td class="small">{{ $po->po_date?->format('d M Y') }}</td>
                  <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $po->status_label ?? $po->status }}</span></td>
                  <td class="text-end small">{{ $po->currency }} {{ number_format((float) $po->total_value, 2) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No purchase orders yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div></div>

      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="fw-semibold mb-2"><i class="bi bi-upc-scan me-1 text-primary"></i>My Traceable Units ({{ $units->count() }})</div>
        <div class="table-responsive" style="max-height:280px;overflow:auto">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Product</th><th>Batch</th><th>Serial</th><th>Sold</th></tr></thead>
            <tbody>
              @forelse($units as $u)
                <tr><td class="small">{{ $u->batch?->product?->name ?? '—' }}</td><td class="small">{{ $u->batch?->brn }}</td><td class="font-monospace small">{{ $u->serial_number }}</td><td class="small">{{ $u->sold_at?->format('d M Y') }}</td></tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No units assigned yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div></div>
    </div>
  </div>
</div>
@endsection
