@extends('layouts.portal')
@section('title', 'My Dashboard')

@section('body')
@php
  $defaultTab = $portal['portal_show_orders'] ? 'orders'
    : ($portal['portal_show_invoices'] ? 'invoices'
    : ($portal['portal_show_documents'] ? 'documents'
    : ($portal['portal_show_units'] ? 'units' : 'profile')));
@endphp
<div x-data="{ tab: '{{ $defaultTab }}' }">

  {{-- Topbar --}}
  <nav class="navbar bg-white border-bottom px-3 px-md-4 py-2 sticky-top">
    <span class="d-inline-flex align-items-center">@include('portal._brand') <span class="text-muted fs-6 fw-normal ms-2">Portal</span></span>
    <div class="ms-auto d-flex align-items-center gap-3">
      <div class="d-flex align-items-center gap-2">
        @if($customer->logo_url)
          <img src="{{ $customer->logo_url }}" alt="" style="width:36px;height:36px;border-radius:9px;object-fit:cover">
        @else
          <span class="grad rounded d-inline-flex align-items-center justify-content-center text-white" style="width:36px;height:36px;font-size:13px;font-weight:700">{{ $customer->initials }}</span>
        @endif
        <div class="lh-1 d-none d-sm-block"><div class="fw-semibold small">{{ $customer->name }}</div><div class="text-muted" style="font-size:11px">{{ $customer->type_label }} · {{ $customer->customer_code }}</div></div>
      </div>
      @if($portal['portal_allow_ordering'])<a href="{{ route('portal.order.create') }}" class="btn btn-grad btn-sm rounded-3"><i class="bi bi-cart-plus me-1"></i><span class="d-none d-sm-inline">Place Order</span></a>@endif
      @include('portal._notifications')
      @if($portal['portal_allow_profile_edit'])<a href="{{ route('portal.profile') }}" class="btn btn-outline-secondary btn-sm rounded-3"><i class="bi bi-gear me-1"></i><span class="d-none d-sm-inline">Profile</span></a>@endif
      <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm rounded-3"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button></form>
    </div>
  </nav>

  <div class="container-xl py-4">
    <h4 class="fw-bold mb-1">Welcome back, {{ $customer->name }} 👋</h4>
    <div class="text-muted small mb-4">{{ $customer->country?->flag }} {{ $customer->country?->name }}{{ $customer->city ? ', '.$customer->city : '' }}</div>

    @if(session('status'))<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}</div>@endif
    @if(trim($portal['portal_welcome_message']) !== '')
      <div class="card-soft p-3 mb-4 d-flex flex-row align-items-start gap-2" style="border-left:4px solid var(--brand1)">
        <i class="bi bi-megaphone-fill" style="color:var(--brand1)"></i>
        <div class="small">{{ $portal['portal_welcome_message'] }}</div>
      </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
      @php $cards = [
        ['Purchase Orders', $stats['orders'], 'bi-cart3', 'linear-gradient(135deg,#4f46e5,#6366f1)'],
        ['Units Purchased', $stats['units'], 'bi-upc-scan', 'linear-gradient(135deg,#0ea5e9,#38bdf8)'],
        ['Invoices', $stats['invoices'], 'bi-receipt', 'linear-gradient(135deg,#f59e0b,#fbbf24)'],
        ['Documents', $stats['documents'], 'bi-folder2-open', 'linear-gradient(135deg,#10b981,#34d399)'],
      ]; @endphp
      @foreach($cards as [$label,$value,$icon,$grad])
        <div class="col-6 col-lg-3">
          <div class="stat d-flex align-items-center gap-3">
            <div class="ic text-white" style="background:{{ $grad }}"><i class="bi {{ $icon }}"></i></div>
            <div><div class="v">{{ $value }}</div><div class="l">{{ $label }}</div></div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Tabs --}}
    <div class="d-flex gap-2 mb-3 flex-wrap">
      @if($portal['portal_show_orders'])<span class="pill" :class="{active: tab==='orders'}" @click="tab='orders'"><i class="bi bi-cart3 me-1"></i>Orders</span>@endif
      @if($portal['portal_show_invoices'])<span class="pill" :class="{active: tab==='invoices'}" @click="tab='invoices'"><i class="bi bi-receipt me-1"></i>Invoices</span>@endif
      @if($portal['portal_show_documents'])<span class="pill" :class="{active: tab==='documents'}" @click="tab='documents'"><i class="bi bi-folder2-open me-1"></i>Documents</span>@endif
      @if($portal['portal_show_units'])<span class="pill" :class="{active: tab==='units'}" @click="tab='units'"><i class="bi bi-upc-scan me-1"></i>Traceable Units</span>@endif
      <span class="pill" :class="{active: tab==='profile'}" @click="tab='profile'"><i class="bi bi-person-badge me-1"></i>Profile</span>
    </div>

    {{-- Orders --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='orders' && {{ $portal['portal_show_orders'] ? '1' : '0' }}" x-cloak>
      <div class="d-flex align-items-center mb-3">
        <div class="fw-semibold"><i class="bi bi-cart3 me-1" style="color:var(--brand1)"></i>Purchase Orders</div>
        @if($portal['portal_allow_ordering'])<a href="{{ route('portal.order.create') }}" class="btn btn-grad btn-sm ms-auto"><i class="bi bi-cart-plus me-1"></i>Place Order</a>@endif
      </div>
      <div class="table-responsive">
        <table class="table table-clean mb-0">
          <thead><tr><th>PO #</th><th>Date</th><th>Items</th><th style="min-width:170px">Progress</th><th>Status</th><th class="text-end">Value</th></tr></thead>
          <tbody>
            @forelse($orders as $po)
              @php $chain = $po->chainStages(); @endphp
              <tr>
                <td class="font-monospace fw-semibold">{{ $po->po_number }}</td>
                <td>{{ $po->po_date?->format('d M Y') }}</td>
                <td>{{ $po->lines->count() }} SKU{{ $po->lines->count() === 1 ? '' : 's' }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    @foreach(['PO','SO','PI','CI'] as $i => $label)
                      @php $n = $i + 1; $done = $chain['step'] >= $n; @endphp
                      <div class="text-center" style="width:38px">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center"
                             style="width:24px;height:24px;font-size:9px;font-weight:700;{{ $done ? 'background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff' : 'background:#fff;border:1px solid #e2e8f0;color:#94a3b8' }}">
                          @if($done)<i class="bi bi-check-lg"></i>@else{{ $label }}@endif
                        </div>
                        <div style="font-size:9px;{{ $done ? 'color:#16a34a;font-weight:600' : 'color:#94a3b8' }}">{{ $label }}@if($label==='CI' && $chain['ci_count'] > 1) ×{{ $chain['ci_count'] }}@endif</div>
                      </div>
                      @if($i < 3)<div class="flex-fill" style="height:2px;{{ $chain['step'] > $n ? 'background:#16a34a' : 'background:#e2e8f0' }}"></div>@endif
                    @endforeach
                  </div>
                </td>
                <td><span class="chip" style="background:#eef2ff;color:#4f46e5">{{ $po->status_label ?? $po->status }}</span></td>
                <td class="text-end fw-semibold">{{ $po->currency }} {{ number_format((float) $po->total_value, 2) }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-4">No purchase orders yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Invoices --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='invoices' && {{ $portal['portal_show_invoices'] ? '1' : '0' }}" x-cloak>
      <div class="fw-semibold mb-3"><i class="bi bi-receipt me-1" style="color:var(--brand1)"></i>Invoices</div>
      <div class="table-responsive">
        <table class="table table-clean mb-0">
          <thead><tr><th>Type</th><th>Number</th><th>Date</th><th>Status</th><th class="text-end">Total</th></tr></thead>
          <tbody>
            @forelse($invoices as $inv)
              <tr>
                <td><span class="chip" style="background:{{ $inv['type']==='Proforma' ? '#fff7ed;color:#c2410c' : '#ecfdf5;color:#047857' }}">{{ $inv['type'] }}</span></td>
                <td class="font-monospace fw-semibold">{{ $inv['number'] }}</td>
                <td>{{ $inv['date'] }}</td>
                <td class="small text-capitalize">{{ $inv['status'] }}</td>
                <td class="text-end fw-semibold">{{ $inv['currency'] }} {{ number_format($inv['total'], 2) }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-4">No invoices yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Documents --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='documents' && {{ $portal['portal_show_documents'] ? '1' : '0' }}" x-cloak>
      <div class="fw-semibold mb-3"><i class="bi bi-folder2-open me-1" style="color:var(--brand1)"></i>Documents</div>
      @if($documents->isEmpty())
        <div class="text-center text-muted py-4">No documents shared yet.</div>
      @else
        <div class="row g-2">
          @foreach($documents as $doc)
            <div class="col-md-6">
              <a href="{{ $doc['url'] }}" target="_blank" class="d-flex align-items-center gap-3 p-3 border rounded-3 text-body" style="border-color:var(--line)!important">
                <i class="bi bi-file-earmark-text fs-4 {{ $doc['icon'] }}"></i>
                <div class="min-w-0 flex-fill">
                  <div class="fw-semibold text-truncate">{{ $doc['name'] }}</div>
                  <div class="text-muted small">{{ $doc['category'] ?? 'Document' }} · {{ $doc['size'] }} · {{ $doc['date'] }}</div>
                </div>
                <i class="bi bi-download text-muted"></i>
              </a>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Units --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='units' && {{ $portal['portal_show_units'] ? '1' : '0' }}" x-cloak>
      <div class="fw-semibold mb-3"><i class="bi bi-upc-scan me-1" style="color:var(--brand1)"></i>My Traceable Units ({{ $units->count() }})</div>
      <div class="table-responsive" style="max-height:420px;overflow:auto">
        <table class="table table-clean mb-0">
          <thead><tr><th>Product</th><th>Batch</th><th>Serial</th><th>Received</th></tr></thead>
          <tbody>
            @forelse($units as $u)
              <tr><td>{{ $u->batch?->product?->name ?? '—' }}</td><td>{{ $u->batch?->brn }}</td><td class="font-monospace">{{ $u->serial_number }}</td><td>{{ $u->sold_at?->format('d M Y') }}</td></tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted py-4">No units assigned yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Profile --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='profile'" x-cloak>
      <div class="d-flex align-items-center mb-3">
        <div class="fw-semibold"><i class="bi bi-person-badge me-1" style="color:var(--brand1)"></i>My Profile</div>
        @if($portal['portal_allow_profile_edit'])<a href="{{ route('portal.profile') }}" class="btn btn-grad btn-sm ms-auto"><i class="bi bi-pencil me-1"></i>Edit profile</a>@endif
      </div>
      <div class="row g-4">
        <div class="col-md-6"><table class="table table-clean mb-0">
          <tr><th style="width:150px">Customer code</th><td class="font-monospace">{{ $customer->customer_code }}</td></tr>
          <tr><th>Type</th><td>{{ $customer->type_label }}</td></tr>
          <tr><th>Email</th><td>{{ $customer->email ?? '—' }}</td></tr>
          <tr><th>Phone</th><td>{{ $customer->phone ?? '—' }}</td></tr>
          <tr><th>Country / City</th><td>{{ $customer->country?->name ?? '—' }}{{ $customer->city ? ', '.$customer->city : '' }}</td></tr>
        </table></div>
        <div class="col-md-6"><table class="table table-clean mb-0">
          <tr><th style="width:150px">Company</th><td>{{ $customer->company_name ?? '—' }}</td></tr>
          <tr><th>Company ID</th><td>{{ $customer->company_id ?? '—' }}</td></tr>
          <tr><th>Identification</th><td>{{ $customer->id_type_label ?: '—' }}{{ $customer->identification_number ? ' · '.$customer->identification_number : '' }}</td></tr>
          <tr><th>Address</th><td>{{ $customer->address ?? '—' }}</td></tr>
          <tr><th>Account Manager</th><td>{{ $customer->manager?->name ?? '—' }}</td></tr>
        </table></div>
      </div>
    </div>

  </div>
</div>
@endsection
