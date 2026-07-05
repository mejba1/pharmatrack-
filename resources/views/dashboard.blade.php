@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
  $roleLabels = ['super_admin'=>'Super Admin','manufacturer'=>'Manufacturer','logistics'=>'Logistics','finance'=>'Finance','qc_officer'=>'QC Officer','distributor'=>'Country Manager'];
@endphp

<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <div class="page-breadcrumb">
      Welcome back, <strong>{{ $user->name }}</strong> —
      {{ $mine ? 'your activity overview' : 'system-wide overview (Super Admin)' }}
    </div>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge bg-primary-subtle text-primary">{{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</span>
  </div>
</div>

@if($mine && !$stats['po_total'] && !$stats['so_total'] && !$stats['customers'])
  <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>You don't have any activity yet. Orders you create and customers in your assigned countries will appear here.</div>
@endif

{{-- Headline stats --}}
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3">
    <a href="{{ route('orders.po') }}" class="stat-card stat-primary text-decoration-none text-body"><div class="stat-icon"><i class="bi bi-cart3"></i></div>
      <div><div class="stat-value">{{ $stats['po_total'] }}</div><div class="stat-label">{{ $mine ? 'My' : 'Total' }} Purchase Orders</div>
        <div class="text-muted-sm">{{ $stats['po_pending'] }} pending · {{ $stats['po_acknowledged'] }} acknowledged</div></div>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="{{ route('orders.so') }}" class="stat-card stat-info text-decoration-none text-body"><div class="stat-icon"><i class="bi bi-bag-check"></i></div>
      <div><div class="stat-value">{{ $stats['so_total'] }}</div><div class="stat-label">{{ $mine ? 'My' : 'Total' }} Sales Orders</div>
        <div class="text-muted-sm">{{ $stats['so_confirmed'] }} confirmed</div></div>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="{{ route('orders.pi') }}" class="stat-card stat-warning text-decoration-none text-body"><div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div><div class="stat-value">{{ $stats['pi_total'] }}</div><div class="stat-label">Proforma Invoices</div>
        <div class="text-muted-sm">{{ $stats['pi_pending'] }} pending · {{ $stats['pi_approved'] }} approved</div></div>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <a href="{{ route('customers.index') }}" class="stat-card stat-success text-decoration-none text-body"><div class="stat-icon"><i class="bi bi-people-fill"></i></div>
      <div><div class="stat-value">{{ $stats['customers'] }}</div><div class="stat-label">{{ $mine ? 'My' : 'Total' }} Customers</div>
        <div class="text-muted-sm">{{ $stats['ci_total'] }} commercial invoices</div></div>
    </a>
  </div>
</div>

{{-- Order value + pipeline --}}
<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-body">
      <div class="text-muted-sm text-uppercase fw-bold mb-1" style="font-size:11px">{{ $mine ? 'My' : 'Total' }} order value</div>
      <div class="fw-bold" style="font-size:26px">USD {{ number_format($stats['order_value'], 2) }}</div>
      <div class="text-muted-sm">Across {{ $stats['po_total'] }} purchase order(s)</div>
      <hr>
      <div class="d-flex flex-column gap-2">
        @foreach($funnel as $f)
          <a href="{{ $f['route'] }}" class="d-flex align-items-center gap-2 text-decoration-none text-body">
            <span class="badge bg-{{ $f['tone'] }}-subtle text-{{ $f['tone'] }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px"><i class="bi {{ $f['icon'] }}"></i></span>
            <span class="flex-fill" style="font-size:13px">{{ $f['label'] }}</span>
            <span class="fw-bold">{{ $f['count'] }}</span>
          </a>
        @endforeach
      </div>
    </div></div>
  </div>

  {{-- Recent purchase orders --}}
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-body p-0">
      <div class="d-flex align-items-center px-3 pt-3 pb-2"><h6 class="fw-bold mb-0">{{ $mine ? 'My recent' : 'Recent' }} POs</h6><a href="{{ route('orders.po') }}" class="ms-auto small text-decoration-none">View all</a></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <tbody>
          @forelse($recentPos as $po)
            <tr><td class="font-monospace small">{{ $po->po_number }}</td><td class="small">{{ $po->buyer?->name }}</td>
              <td class="text-end"><span class="badge-status badge-{{ $po->status_badge_class }}">{{ $po->status_label }}</span></td></tr>
          @empty
            <tr><td class="text-center text-muted py-4">No purchase orders yet.</td></tr>
          @endforelse
        </tbody>
      </table></div>
    </div></div>
  </div>

  {{-- Recent sales orders --}}
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-body p-0">
      <div class="d-flex align-items-center px-3 pt-3 pb-2"><h6 class="fw-bold mb-0">{{ $mine ? 'My recent' : 'Recent' }} SOs</h6><a href="{{ route('orders.so') }}" class="ms-auto small text-decoration-none">View all</a></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <tbody>
          @forelse($recentSos as $so)
            <tr><td class="font-monospace small">{{ $so->so_number }}</td><td class="small">{{ $so->customer?->name }}</td>
              <td class="text-end"><span class="badge-status badge-{{ $so->status_badge_class }}">{{ $so->status_label }}</span></td></tr>
          @empty
            <tr><td class="text-center text-muted py-4">No sales orders yet.</td></tr>
          @endforelse
        </tbody>
      </table></div>
    </div></div>
  </div>
</div>

{{-- Recent customers --}}
<div class="card"><div class="card-body p-0">
  <div class="d-flex align-items-center px-3 pt-3 pb-2"><h6 class="fw-bold mb-0">{{ $mine ? 'My' : 'Recent' }} customers</h6><a href="{{ route('customers.index') }}" class="ms-auto small text-decoration-none">View all</a></div>
  <div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Code</th><th>Customer</th><th>Type</th><th>Country</th><th>Contact</th></tr></thead>
    <tbody>
      @forelse($recentCustomers as $c)
        <tr><td class="font-monospace small">{{ $c->customer_code ?? '—' }}</td>
          <td class="fw-semibold">{{ $c->name }}</td><td class="small">{{ $c->type_label }}</td>
          <td class="small">{{ $c->country?->flag }} {{ $c->country?->name ?? '—' }}</td>
          <td class="small">{{ $c->contact_person ?? '—' }}</td></tr>
      @empty
        <tr><td colspan="5" class="text-center text-muted py-4">{{ $mine ? 'No customers in your assigned countries yet.' : 'No customers yet.' }}</td></tr>
      @endforelse
    </tbody>
  </table></div>
</div></div>
@endsection
