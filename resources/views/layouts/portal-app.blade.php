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
{{-- Apply persisted theme / collapsed state before paint to avoid a flash --}}
<script>
  (function () {
    try {
      var d = localStorage.getItem('portalDark') === '1';
      document.documentElement.setAttribute('data-bs-theme', d ? 'dark' : 'light');
      if (localStorage.getItem('portalCollapsed') === '1') {
        document.documentElement.classList.add('portal-collapsed');
      }
    } catch (e) {}
  })();
</script>
<style>
  .portal-shell{ display:flex; min-height:100vh; }
  .portal-sidebar{ width:252px; background:var(--card); border-right:1px solid var(--line);
    position:fixed; top:0; bottom:0; left:0; z-index:1040; display:flex; flex-direction:column;
    transition:transform .2s ease, width .2s ease; }
  .portal-sidebar .sb-head{ padding:18px 20px; border-bottom:1px solid var(--line); min-height:65px; }
  .portal-sidebar .sb-nav{ flex:1; overflow-y:auto; overflow-x:hidden; padding:12px 12px 20px; }
  .portal-sidebar .sb-foot{ border-top:1px solid var(--line); padding:12px; }
  .portal-nav a{ display:flex; align-items:center; gap:.65rem; padding:.6rem .85rem; border-radius:11px;
    color:var(--muted); font-weight:600; font-size:14px; margin-bottom:2px; white-space:nowrap; }
  .portal-nav a:hover{ background:var(--bg); color:var(--ink); }
  .portal-nav a.active{ background:linear-gradient(135deg,var(--brand1),var(--brand2)); color:#fff; box-shadow:0 6px 16px rgba(79,70,229,.25); }
  .portal-nav a i{ font-size:16px; width:18px; text-align:center; flex:0 0 auto; }
  .portal-nav .sec{ font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; font-weight:700; padding:14px .85rem 6px; white-space:nowrap; }
  .sb-mark{ width:32px; height:32px; flex:0 0 auto; }
  .portal-main{ flex:1; min-width:0; margin-left:252px; display:flex; flex-direction:column; transition:margin .2s ease; }
  .portal-topbar{ background:var(--card); border-bottom:1px solid var(--line); padding:.55rem 1rem;
    display:flex; align-items:center; gap:.6rem; position:sticky; top:0; z-index:1030; }
  .portal-backdrop{ position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:1035; }

  /* ── Collapsed (icon-only) sidebar — desktop only ─────────────────── */
  @media (min-width: 992px){
    html.portal-collapsed .portal-sidebar{ width:74px; }
    html.portal-collapsed .portal-main{ margin-left:74px; }
    html.portal-collapsed .portal-sidebar .sb-head{ padding-left:0; padding-right:0; justify-content:center; }
    html.portal-collapsed .portal-nav a{ justify-content:center; padding-left:.4rem; padding-right:.4rem; gap:0; }
    html.portal-collapsed .portal-nav a i{ margin:0; }
    html.portal-collapsed .sb-label,
    html.portal-collapsed .portal-nav .sec,
    html.portal-collapsed .sb-userinfo{ display:none !important; }
    html.portal-collapsed .sb-foot{ padding:10px 8px; }
    html.portal-collapsed .sb-foot .sb-user{ justify-content:center; }
    html.portal-collapsed .sb-foot .btn span{ display:none; }
    html.portal-collapsed .sb-foot .btn i{ margin:0 !important; }
  }

  /* ── Dark theme (drives both my CSS vars and Bootstrap components) ── */
  html[data-bs-theme="dark"]{
    --ink:#e6ebf5; --muted:#9aa8c2; --line:#26334b; --bg:#0b1220; --card:#141f38;
  }
  html[data-bs-theme="dark"] .pill{ background:var(--card); color:var(--muted); border-color:var(--line); }
  html[data-bs-theme="dark"] .portal-nav a:hover{ background:#1b2942; }
  html[data-bs-theme="dark"] .portal-backdrop{ background:rgba(0,0,0,.6); }

  @media (max-width: 991.98px){
    .portal-sidebar{ transform:translateX(-100%); box-shadow:0 10px 40px rgba(2,6,23,.2); }
    .portal-sidebar.open{ transform:translateX(0); }
    .portal-main{ margin-left:0; }
  }
</style>

<div class="portal-shell"
     x-data="{
       sidebar:false,
       hash:'',
       collapsed: document.documentElement.classList.contains('portal-collapsed'),
       dark: document.documentElement.getAttribute('data-bs-theme')==='dark',
       init(){
         this.hash = location.hash;
         window.addEventListener('hashchange', () => this.hash = location.hash);
       },
       toggleCollapse(){
         this.collapsed = !this.collapsed;
         document.documentElement.classList.toggle('portal-collapsed', this.collapsed);
         try { localStorage.setItem('portalCollapsed', this.collapsed ? '1' : '0'); } catch(e){}
       },
       toggleDark(){
         this.dark = !this.dark;
         document.documentElement.setAttribute('data-bs-theme', this.dark ? 'dark' : 'light');
         try { localStorage.setItem('portalDark', this.dark ? '1' : '0'); } catch(e){}
       }
     }">

  {{-- ── Sidebar ─────────────────────────────────────────────────── --}}
  <aside class="portal-sidebar" :class="{ open: sidebar }">
    <div class="sb-head d-flex align-items-center">
      <a href="{{ route('portal.dashboard') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
        <span class="grad rounded d-inline-flex align-items-center justify-content-center text-white sb-mark"><i class="bi bi-capsule-pill"></i></span>
        <span class="sb-label d-inline-flex align-items-center">@include('portal._brand')</span>
      </a>
      <span class="text-muted ms-2 sb-label" style="font-size:12px">Portal</span>
      <button class="btn btn-sm ms-auto d-lg-none sb-label" @click="sidebar=false"><i class="bi bi-x-lg"></i></button>
    </div>

    <nav class="sb-nav portal-nav" @click="sidebar=false">
      <div class="sec">Menu</div>
      <a href="{{ route('portal.dashboard') }}" title="Dashboard"
         :class="{ active: {{ $onDash ? 'true' : 'false' }} && (hash==='' || hash==='#overview') }">
        <i class="bi bi-grid-1x2"></i> <span class="sb-label">Dashboard</span>
      </a>
      @foreach($sections as [$key, $label, $icon])
        <a href="{{ route('portal.dashboard') }}#{{ $key }}" title="{{ $label }}"
           :class="{ active: {{ $onDash ? 'true' : 'false' }} && hash==='#{{ $key }}' }">
          <i class="bi {{ $icon }}"></i> <span class="sb-label">{{ $label }}</span>
        </a>
      @endforeach

      <div class="sec">Account</div>
      @if($cust->canPlaceOrders())
        <a href="{{ route('portal.order.create') }}" title="Place Order" class="{{ request()->routeIs('portal.order.create') ? 'active' : '' }}"><i class="bi bi-cart-plus"></i> <span class="sb-label">Place Order</span></a>
      @endif
      @if($ps['portal_allow_profile_edit'])
        <a href="{{ route('portal.profile') }}" title="My Profile" class="{{ request()->routeIs('portal.profile') ? 'active' : '' }}"><i class="bi bi-person-badge"></i> <span class="sb-label">My Profile</span></a>
      @endif
    </nav>

    <div class="sb-foot">
      <div class="sb-user d-flex align-items-center gap-2 mb-2 px-1">
        @if($cust->logo_url)
          <img src="{{ $cust->logo_url }}" alt="" style="width:34px;height:34px;border-radius:9px;object-fit:cover;flex:0 0 auto">
        @else
          <span class="grad rounded d-inline-flex align-items-center justify-content-center text-white" style="width:34px;height:34px;font-size:12px;font-weight:700;flex:0 0 auto">{{ $cust->initials }}</span>
        @endif
        <div class="lh-1 min-w-0 sb-userinfo"><div class="fw-semibold small text-truncate">{{ $cust->name }}</div><div class="text-muted text-truncate" style="font-size:11px">{{ $cust->customer_code }}</div></div>
      </div>
      <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm rounded-3 w-100"><i class="bi bi-box-arrow-right me-1"></i><span>Sign out</span></button></form>
    </div>
  </aside>

  <div class="portal-backdrop d-lg-none" x-show="sidebar" @click="sidebar=false" x-cloak></div>

  {{-- ── Main column ─────────────────────────────────────────────── --}}
  <div class="portal-main">
    <header class="portal-topbar">
      {{-- mobile: open drawer · desktop: collapse toggle --}}
      <button class="btn btn-outline-secondary btn-sm rounded-3 d-lg-none" @click="sidebar=true" title="Menu"><i class="bi bi-list"></i></button>
      <button class="btn btn-outline-secondary btn-sm rounded-3 d-none d-lg-inline-flex" @click="toggleCollapse()" :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
        <i class="bi" :class="collapsed ? 'bi-chevron-double-right' : 'bi-chevron-double-left'"></i>
      </button>
      <div class="fw-semibold d-none d-md-block">@yield('heading', 'Customer Portal')</div>
      <div class="ms-auto d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm rounded-3" @click="toggleDark()" :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
          <i class="bi" :class="dark ? 'bi-sun' : 'bi-moon-stars'"></i>
        </button>
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
