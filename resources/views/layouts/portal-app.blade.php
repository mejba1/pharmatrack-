@extends('layouts.portal')

@php
  $ps   = \App\Support\PortalSettings::all();
  $cust = auth('customer')->user();
  $onDash = request()->routeIs('portal.dashboard');
  // Sidebar section links (dashboard tabs, driven by the URL hash)
  $sections = array_filter([
    $ps['portal_show_orders']    ? ['orders',    'Orders',          'bi-cart3']        : null,
    $ps['portal_show_invoices']  ? ['invoices',  'Invoices',        'bi-receipt']      : null,
    $ps['portal_show_documents'] ? ['documents', 'Documents',       'bi-folder2-open'] : null,
    $ps['portal_show_units']     ? ['units',     'Traceable Units', 'bi-upc-scan']     : null,
  ]);
@endphp

@section('body')
<style>
  .portal-shell{ display:flex; min-height:100vh; }
  .portal-sidebar{ width:252px; background:var(--card); border-right:1px solid var(--line);
    position:fixed; top:0; bottom:0; left:0; z-index:1040; display:flex; flex-direction:column;
    transition:transform .2s ease; }
  .portal-sidebar .sb-head{ padding:18px 20px; border-bottom:1px solid var(--line); }
  .portal-sidebar .sb-nav{ flex:1; overflow-y:auto; padding:12px 12px 20px; }
  .portal-sidebar .sb-foot{ border-top:1px solid var(--line); padding:12px; }
  .portal-nav a{ display:flex; align-items:center; gap:.65rem; padding:.6rem .85rem; border-radius:11px;
    color:var(--muted); font-weight:600; font-size:14px; margin-bottom:2px; }
  .portal-nav a:hover{ background:var(--bg); color:var(--ink); }
  .portal-nav a.active{ background:linear-gradient(135deg,var(--brand1),var(--brand2)); color:#fff; box-shadow:0 6px 16px rgba(79,70,229,.25); }
  .portal-nav a i{ font-size:16px; width:18px; text-align:center; }
  .portal-nav .sec{ font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; font-weight:700; padding:14px .85rem 6px; }
  .portal-main{ flex:1; min-width:0; margin-left:252px; display:flex; flex-direction:column; }
  .portal-topbar{ background:var(--card); border-bottom:1px solid var(--line); padding:.55rem 1rem;
    display:flex; align-items:center; gap:.6rem; position:sticky; top:0; z-index:1030; }
  .portal-backdrop{ position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:1035; }
  @media (max-width: 991.98px){
    .portal-sidebar{ transform:translateX(-100%); box-shadow:0 10px 40px rgba(2,6,23,.2); }
    .portal-sidebar.open{ transform:translateX(0); }
    .portal-main{ margin-left:0; }
  }
</style>

<div class="portal-shell"
     x-data="{ sidebar:false, hash:'' }"
     x-init="hash = location.hash; window.addEventListener('hashchange', () => hash = location.hash)">

  {{-- ── Sidebar ─────────────────────────────────────────────────── --}}
  <aside class="portal-sidebar" :class="{ open: sidebar }">
    <div class="sb-head d-flex align-items-center">
      <a href="{{ route('portal.dashboard') }}" class="d-inline-flex align-items-center">@include('portal._brand')</a>
      <span class="text-muted ms-2" style="font-size:12px">Portal</span>
      <button class="btn btn-sm btn-icon ms-auto d-lg-none" @click="sidebar=false"><i class="bi bi-x-lg"></i></button>
    </div>

    <nav class="sb-nav portal-nav" @click="sidebar=false">
      <div class="sec">Menu</div>
      <a href="{{ route('portal.dashboard') }}"
         :class="{ active: {{ $onDash ? 'true' : 'false' }} && (hash==='' || hash==='#overview') }">
        <i class="bi bi-grid-1x2"></i> Dashboard
      </a>
      @foreach($sections as [$key, $label, $icon])
        <a href="{{ route('portal.dashboard') }}#{{ $key }}"
           :class="{ active: {{ $onDash ? 'true' : 'false' }} && hash==='#{{ $key }}' }">
          <i class="bi {{ $icon }}"></i> {{ $label }}
        </a>
      @endforeach

      <div class="sec">Account</div>
      @if($cust->canPlaceOrders())
        <a href="{{ route('portal.order.create') }}" class="{{ request()->routeIs('portal.order.create') ? 'active' : '' }}"><i class="bi bi-cart-plus"></i> Place Order</a>
      @endif
      @if($ps['portal_allow_profile_edit'])
        <a href="{{ route('portal.profile') }}" class="{{ request()->routeIs('portal.profile') ? 'active' : '' }}"><i class="bi bi-person-badge"></i> My Profile</a>
      @endif
    </nav>

    <div class="sb-foot">
      <div class="d-flex align-items-center gap-2 mb-2 px-1">
        @if($cust->logo_url)
          <img src="{{ $cust->logo_url }}" alt="" style="width:34px;height:34px;border-radius:9px;object-fit:cover">
        @else
          <span class="grad rounded d-inline-flex align-items-center justify-content-center text-white" style="width:34px;height:34px;font-size:12px;font-weight:700">{{ $cust->initials }}</span>
        @endif
        <div class="lh-1 min-w-0"><div class="fw-semibold small text-truncate">{{ $cust->name }}</div><div class="text-muted text-truncate" style="font-size:11px">{{ $cust->customer_code }}</div></div>
      </div>
      <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm rounded-3 w-100"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button></form>
    </div>
  </aside>

  <div class="portal-backdrop d-lg-none" x-show="sidebar" @click="sidebar=false" x-cloak></div>

  {{-- ── Main column ─────────────────────────────────────────────── --}}
  <div class="portal-main">
    <header class="portal-topbar">
      <button class="btn btn-outline-secondary btn-sm rounded-3 d-lg-none" @click="sidebar=true"><i class="bi bi-list"></i></button>
      <div class="fw-semibold d-none d-md-block">@yield('heading', 'Customer Portal')</div>
      <div class="ms-auto d-flex align-items-center gap-2">
        @if($cust->canPlaceOrders())<a href="{{ route('portal.order.create') }}" class="btn btn-grad btn-sm rounded-3"><i class="bi bi-cart-plus me-1"></i><span class="d-none d-sm-inline">Place Order</span></a>@endif
        @include('portal._notifications')
      </div>
    </header>

    <main class="p-3 p-md-4">
      @yield('content')
    </main>
  </div>
</div>
@endsection
