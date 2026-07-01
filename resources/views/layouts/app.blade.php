<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'PharmaTrack') — PharmaTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  {{-- Prevent flash of unstyled content --}}
  <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('darkMode')==='true'?'dark':'light');</script>
  @stack('styles')
</head>
<body x-data="layoutData()" :class="{'sidebar-collapsed-body': sidebarCollapsed}">

{{-- ===== SIDEBAR ===== --}}
<aside class="sidebar" :class="{'collapsed': sidebarCollapsed, 'mobile-open': mobileSidebarOpen}">

  {{-- Logo row with collapse toggle --}}
  <div class="sidebar-logo">
    <div class="logo-icon" x-show="!sidebarCollapsed"><i class="bi bi-capsule-pill"></i></div>
    <div class="logo-text" x-show="!sidebarCollapsed">Beacon Pharma<span>Track</span></div>
    <button class="sidebar-collapse-btn"
            :class="{'ms-auto': !sidebarCollapsed}"
            @click="toggleSidebar()"
            :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
      <i class="bi" :class="sidebarCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
    </button>
  </div>

  <nav class="sidebar-nav">

    @php $can = fn (string $k) => optional(auth()->user())->canModule($k); @endphp
    {{-- Main --}}
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Main</div>
    <a href="{{ route('dashboard') }}" class="nav-item-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-grid-1x2-fill"></i></span>
      <span x-show="!sidebarCollapsed">Dashboard</span>
    </a>

    {{-- Products --}}
    @if($can('products') || $can('batches') || $can('master_cartons'))
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Products</div>
    @endif
    @if($can('products'))
    <a href="{{ route('products.index') }}" class="nav-item-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-capsule"></i></span>
      <span x-show="!sidebarCollapsed">Product Master</span>
    </a>
    @endif
    @if($can('batches'))
    <a href="{{ route('batches') }}" class="nav-item-link {{ request()->routeIs('batches') || request()->routeIs('batches.*') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-layers"></i></span>
      <span x-show="!sidebarCollapsed">Batch &amp; Lot Mgmt</span>
    </a>
    <a href="{{ route('partial-batches') }}" class="nav-item-link {{ request()->routeIs('partial-batches') || request()->routeIs('partial-batches.*') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-layer-forward"></i></span>
      <span x-show="!sidebarCollapsed">Partial Batch Qty</span>
    </a>
    <a href="{{ route('batch-downloads') }}" class="nav-item-link {{ request()->routeIs('batch-downloads') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-cloud-download"></i></span>
      <span x-show="!sidebarCollapsed">Batch Downloads</span>
    </a>
    @endif
    @if($can('master_cartons'))
    <div x-data="{open: {{ request()->routeIs('master-cartons') || request()->routeIs('master-cartons.*') ? 'true' : 'false' }}}">
      <button class="nav-item-link {{ request()->routeIs('master-cartons') || request()->routeIs('master-cartons.*') ? 'active' : '' }}" @click="open = !open">
        <span class="nav-icon"><i class="bi bi-box-seam"></i></span>
        <span x-show="!sidebarCollapsed">Master Carton Mgmt</span>
        <i class="bi bi-chevron-right nav-caret" x-show="!sidebarCollapsed" :class="{open: open}"></i>
      </button>
      <div class="nav-submenu" :class="{open: open}">
        <a href="{{ route('master-cartons') }}" class="nav-item-link {{ request()->routeIs('master-cartons') && !request()->routeIs('master-cartons.create-packed') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-grid"></i></span>
          <span x-show="!sidebarCollapsed">All Cartons</span>
        </a>
        <a href="{{ route('master-cartons.create-packed') }}" class="nav-item-link {{ request()->routeIs('master-cartons.create-packed') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-box2-heart"></i></span>
          <span x-show="!sidebarCollapsed">Create Packed Cartons</span>
        </a>
        <a href="{{ route('master-cartons.batch-summary-page') }}" class="nav-item-link {{ request()->routeIs('master-cartons.batch-summary-page') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-clipboard-data"></i></span>
          <span x-show="!sidebarCollapsed">Batch-wise Summary</span>
        </a>
        <a href="{{ route('master-cartons.labels-center') }}" class="nav-item-link {{ request()->routeIs('master-cartons.labels-center') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-printer"></i></span>
          <span x-show="!sidebarCollapsed">Carton Labels</span>
        </a>
      </div>
    </div>
    @endif

    {{-- Master Data --}}
    @if($can('master_data'))
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Master Data</div>
    <a href="{{ route('master.countries.index') }}" class="nav-item-link {{ request()->routeIs('master.countries.*') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-globe2"></i></span>
      <span x-show="!sidebarCollapsed">Countries</span>
    </a>
    <a href="{{ route('master.tclasses.index') }}" class="nav-item-link {{ request()->routeIs('master.tclasses.*') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-tags"></i></span>
      <span x-show="!sidebarCollapsed">Therapeutic Classes</span>
    </a>
    @endif

    {{-- Order Documents --}}
    @if($can('orders') || $can('invoices'))
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Orders</div>
    <div x-data="{open: {{ request()->routeIs('orders.*') ? 'true' : 'false' }}}">
      <button class="nav-item-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" @click="open = !open">
        <span class="nav-icon"><i class="bi bi-file-text"></i></span>
        <span x-show="!sidebarCollapsed">Order Documents</span>
        <i class="bi bi-chevron-right nav-caret" x-show="!sidebarCollapsed" :class="{open: open}"></i>
      </button>
      <div class="nav-submenu" :class="{open: open}">
        @if($can('orders'))
        <a href="{{ route('orders.po') }}" class="nav-item-link {{ request()->routeIs('orders.po') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-cart3"></i></span>
          <span x-show="!sidebarCollapsed">Purchase Orders</span>
        </a>
        <a href="{{ route('orders.so') }}" class="nav-item-link {{ request()->routeIs('orders.so') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-bag-check"></i></span>
          <span x-show="!sidebarCollapsed">Sales Orders</span>
        </a>
        @endif
        @if($can('invoices'))
        <a href="{{ route('orders.pi') }}" class="nav-item-link {{ request()->routeIs('orders.pi') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-receipt"></i></span>
          <span x-show="!sidebarCollapsed">Proforma Invoice</span>
        </a>
        <a href="{{ route('orders.ci') }}" class="nav-item-link {{ request()->routeIs('orders.ci') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-file-earmark-check"></i></span>
          <span x-show="!sidebarCollapsed">Commercial Invoice</span>
        </a>
        @endif
      </div>
    </div>
    @endif

    {{-- Logistics --}}
    @if($can('logistics'))
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Logistics</div>
    <div x-data="{open: {{ request()->routeIs('shipments') || request()->routeIs('shipments.*') || request()->routeIs('distribution') ? 'true' : 'false' }}}">
      <button class="nav-item-link {{ request()->routeIs('shipments') || request()->routeIs('shipments.*') || request()->routeIs('distribution') ? 'active' : '' }}" @click="open = !open">
        <span class="nav-icon"><i class="bi bi-truck"></i></span>
        <span x-show="!sidebarCollapsed">Shipment Management</span>
        <i class="bi bi-chevron-right nav-caret" x-show="!sidebarCollapsed" :class="{open: open}"></i>
      </button>
      <div class="nav-submenu" :class="{open: open}">
        <a href="{{ route('shipments') }}" class="nav-item-link {{ request()->routeIs('shipments') && !request()->routeIs('shipments.receiving') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-box-seam"></i></span>
          <span x-show="!sidebarCollapsed">All Shipments</span>
        </a>
        <a href="{{ route('shipments.receiving') }}" class="nav-item-link {{ request()->routeIs('shipments.receiving') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-box-arrow-in-down"></i></span>
          <span x-show="!sidebarCollapsed">Receiving / Verification</span>
        </a>
        <a href="{{ route('distribution') }}" class="nav-item-link {{ request()->routeIs('distribution') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-diagram-3"></i></span>
          <span x-show="!sidebarCollapsed">Distribution Dashboard</span>
        </a>
        <a href="{{ route('shipments.labels') }}" target="_blank" class="nav-item-link">
          <span class="nav-icon"><i class="bi bi-qr-code"></i></span>
          <span x-show="!sidebarCollapsed">Traceability &amp; QR Labels</span>
        </a>
      </div>
    </div>
    @endif

    {{-- Compliance --}}
    @if($can('compliance') || $can('anti_counterfeit') || $can('vault'))
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Compliance</div>
    @endif
    @if($can('compliance'))
    <a href="{{ route('countries') }}" class="nav-item-link {{ request()->routeIs('countries') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-globe2"></i></span>
      <span x-show="!sidebarCollapsed">Country Permissions</span>
    </a>
    @endif
    @if($can('anti_counterfeit'))
    <div x-data="{open: {{ request()->routeIs('anticounterfeit.*') ? 'true' : 'false' }}}">
      <button class="nav-item-link {{ request()->routeIs('anticounterfeit.*') ? 'active' : '' }}" @click="open = !open">
        <span class="nav-icon"><i class="bi bi-shield-check"></i></span>
        <span x-show="!sidebarCollapsed">Anti-Counterfeit</span>
        <span class="nav-badge nav-badge-danger" x-show="!sidebarCollapsed">!</span>
        <i class="bi bi-chevron-right nav-caret" x-show="!sidebarCollapsed" :class="{open: open}"></i>
      </button>
      <div class="nav-submenu" :class="{open: open}">
        <a href="{{ route('anticounterfeit.dashboard') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.dashboard') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span x-show="!sidebarCollapsed">Dashboard</span>
        </a>
        <a href="{{ route('products.index') }}" class="nav-item-link">
          <span class="nav-icon"><i class="bi bi-capsule"></i></span><span x-show="!sidebarCollapsed">Products</span>
        </a>
        <a href="{{ route('batches') }}" class="nav-item-link">
          <span class="nav-icon"><i class="bi bi-layers"></i></span><span x-show="!sidebarCollapsed">Batches</span>
        </a>
        <a href="{{ route('anticounterfeit.uuc') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.uuc') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-upc-scan"></i></span><span x-show="!sidebarCollapsed">UUC Management</span>
        </a>
        <a href="{{ route('anticounterfeit.logs') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.logs') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-list-check"></i></span><span x-show="!sidebarCollapsed">Verification Logs</span>
        </a>
        <a href="{{ route('anticounterfeit.map') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.map') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-geo-alt"></i></span><span x-show="!sidebarCollapsed">Live Scan Map</span>
        </a>
        <a href="{{ route('anticounterfeit.alerts') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.alerts') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-exclamation-triangle"></i></span><span x-show="!sidebarCollapsed">Risk Alerts</span>
        </a>
        <a href="{{ route('anticounterfeit.cases') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.cases') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-folder2-open"></i></span><span x-show="!sidebarCollapsed">Counterfeit Cases</span>
        </a>
        <a href="{{ route('anticounterfeit.reports-list') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.reports-list') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-flag"></i></span><span x-show="!sidebarCollapsed">Customer Reports</span>
        </a>
        <a href="{{ route('anticounterfeit.recalls') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.recalls') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-arrow-counterclockwise"></i></span><span x-show="!sidebarCollapsed">Recalled Batches</span>
        </a>
        <a href="{{ route('anticounterfeit.countries') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.countries') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-globe-americas"></i></span><span x-show="!sidebarCollapsed">Country Authorization</span>
        </a>
		 <a href="{{ route('anticounterfeit.verification-page') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.verification-page') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-window-desktop"></i></span><span x-show="!sidebarCollapsed">Verification Page Design</span>
        </a>
        <a href="{{ route('anticounterfeit.policies') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.policies') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-lock"></i></span><span x-show="!sidebarCollapsed">Access Control</span>
        </a>
       
        <a href="{{ route('anticounterfeit.devices') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.devices') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-phone"></i></span><span x-show="!sidebarCollapsed">Device Intelligence</span>
        </a>
        <a href="{{ route('anticounterfeit.geo') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.geo') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-pin-map"></i></span><span x-show="!sidebarCollapsed">Geo Intelligence</span>
        </a>
        <a href="{{ route('anticounterfeit.investigations') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.investigations') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-search"></i></span><span x-show="!sidebarCollapsed">Investigation Center</span>
        </a>
        <a href="{{ route('anticounterfeit.reports') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit.reports') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-bar-chart"></i></span><span x-show="!sidebarCollapsed">Reports &amp; Analytics</span>
        </a>
      </div>
    </div>
    @endif
    @if($can('vault'))
    <a href="{{ route('vault') }}" class="nav-item-link {{ request()->routeIs('vault') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-safe2"></i></span>
      <span x-show="!sidebarCollapsed">Document Vault</span>
    </a>
    @endif

    {{-- Customers --}}
    @if($can('customers') || $can('country_managers'))
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Customers</div>
    @endif
    @if($can('customers'))
    @php
      $pendingCustomers = \App\Models\Customer::where('status', 'pending')
        ->when(! auth()->user()->canViewAll('customers'), fn ($q) => $q->where('manager_id', auth()->id()))
        ->count();
    @endphp
    <div x-data="{open: {{ request()->routeIs('customers.*') ? 'true' : 'false' }}}">
      <button class="nav-item-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" @click="open = !open">
        <span class="nav-icon"><i class="bi bi-people-fill"></i></span>
        <span x-show="!sidebarCollapsed">Customer Management</span>
        @if($pendingCustomers)<span class="nav-badge nav-badge-danger" x-show="!sidebarCollapsed">{{ $pendingCustomers }}</span>@endif
        <i class="bi bi-chevron-right nav-caret" x-show="!sidebarCollapsed" :class="{open: open}"></i>
      </button>
      <div class="nav-submenu" :class="{open: open}">
        <a href="{{ route('customers.index') }}" class="nav-item-link {{ request()->routeIs('customers.index') || request()->routeIs('customers.show') || request()->routeIs('customers.store') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-card-list"></i></span>
          <span x-show="!sidebarCollapsed">Customer Directory</span>
          @if($pendingCustomers)<span class="nav-badge nav-badge-danger" x-show="!sidebarCollapsed">{{ $pendingCustomers }}</span>@endif
        </a>
        <a href="{{ route('customers.trace') }}" class="nav-item-link {{ request()->routeIs('customers.trace') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-upc-scan"></i></span>
          <span x-show="!sidebarCollapsed">Trace a Purchase</span>
        </a>
        <a href="{{ route('portal.login') }}" target="_blank" class="nav-item-link">
          <span class="nav-icon"><i class="bi bi-box-arrow-up-right"></i></span>
          <span x-show="!sidebarCollapsed">Customer Portal</span>
        </a>
      </div>
    </div>
    @endif
    @if($can('country_managers'))
    <a href="{{ route('country-managers.index') }}" class="nav-item-link {{ request()->routeIs('country-managers.*') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-person-gear"></i></span>
      <span x-show="!sidebarCollapsed">Country Managers</span>
    </a>
    @endif

    {{-- Portal --}}
    @if($can('patients'))
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Portal</div>
    <a href="{{ route('patients') }}" class="nav-item-link {{ request()->routeIs('patients') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-person-heart"></i></span>
      <span x-show="!sidebarCollapsed">Patient Portal</span>
    </a>
    @endif

    {{-- Admin --}}
    @if($can('reports') || $can('notifications') || $can('users'))
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Admin</div>
    @endif
    @if($can('reports'))
    <a href="{{ route('reports') }}" class="nav-item-link {{ request()->routeIs('reports') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-bar-chart-line"></i></span>
      <span x-show="!sidebarCollapsed">Reports &amp; Analytics</span>
    </a>
    @endif
    @if($can('notifications'))
    <a href="{{ route('notifications') }}" class="nav-item-link {{ request()->routeIs('notifications') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-bell"></i></span>
      <span x-show="!sidebarCollapsed">Notifications</span>
      <span class="nav-badge" x-show="!sidebarCollapsed && unreadCount > 0" x-text="unreadCount"></span>
    </a>
    @endif
    @if($can('users'))
    <div x-data="{open: {{ request()->routeIs('users') || request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'true' : 'false' }}}">
      <button class="nav-item-link {{ request()->routeIs('users') || request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'active' : '' }}" @click="open = !open">
        <span class="nav-icon"><i class="bi bi-people"></i></span>
        <span x-show="!sidebarCollapsed">Users &amp; Roles</span>
        <i class="bi bi-chevron-right nav-caret" x-show="!sidebarCollapsed" :class="{open: open}"></i>
      </button>
      <div class="nav-submenu" :class="{open: open}">
        <a href="{{ route('roles.index') }}" class="nav-item-link {{ request()->routeIs('roles.index') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-shield-plus"></i></span>
          <span x-show="!sidebarCollapsed">Create Role</span>
        </a>
        <a href="{{ route('roles.permissions') }}" class="nav-item-link {{ request()->routeIs('roles.permissions') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-shield-lock"></i></span>
          <span x-show="!sidebarCollapsed">Permission Set</span>
        </a>
        <a href="{{ route('users') }}" class="nav-item-link {{ request()->routeIs('users') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-person-lines-fill"></i></span>
          <span x-show="!sidebarCollapsed">Users</span>
        </a>
      </div>
    </div>
    @endif

  </nav>
</aside>

{{-- ===== TOPBAR ===== --}}
<header class="topbar" :class="{'collapsed': sidebarCollapsed}">

  {{-- Mobile hamburger --}}
  <button class="btn btn-link p-0 me-2 d-md-none text-secondary" @click="mobileSidebarOpen = !mobileSidebarOpen">
    <i class="bi bi-list fs-5"></i>
  </button>

  <div>
    <div class="topbar-title">@yield('title', 'PharmaTrack')</div>
    <nav style="font-size:11px;color:#6c757d">
      <a href="{{ route('dashboard') }}" class="text-primary text-decoration-none">Home</a>
      <span class="mx-1">/</span>
      <span>@yield('title', 'PharmaTrack')</span>
    </nav>
  </div>

  <div class="topbar-actions ms-auto">

    {{-- Search --}}
    <div class="search-wrapper d-none d-lg-block">
      <i class="bi bi-search search-icon"></i>
      <input type="text" class="form-control form-control-sm" placeholder="Search..." style="width:240px">
    </div>

    {{-- Notifications bell --}}
    <div class="position-relative" @click.outside="showNotifPanel=false">
      <button class="topbar-btn" @click="showNotifPanel=!showNotifPanel">
        <i class="bi bi-bell"></i>
        <span class="badge-dot" x-show="unreadCount>0"></span>
      </button>
      <div x-show="showNotifPanel" x-transition
           class="position-absolute end-0 mt-2 rounded-3 shadow-lg topbar-dropdown"
           style="width:320px;z-index:1050;top:100%">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
          <span class="fw-semibold" style="font-size:14px">
            Notifications
            <span class="badge bg-danger rounded-pill ms-1" x-text="unreadCount" x-show="unreadCount>0"></span>
          </span>
          <button class="btn btn-link btn-sm p-0 text-primary" style="font-size:12px" @click="markAllRead()">Mark all read</button>
        </div>
        <div style="max-height:300px;overflow-y:auto">
          <template x-for="n in notifications" :key="n.id">
            <div class="px-3 py-2 border-bottom d-flex gap-2 cursor-pointer"
                 :class="n.read?'':'bg-light-primary'" @click="n.read=true">
              <i :class="n.icon" class="mt-1 flex-shrink-0" style="font-size:14px"></i>
              <div style="min-width:0">
                <div style="font-size:12px;line-height:1.4" x-text="n.message"></div>
                <div style="font-size:11px;color:#adb5bd" x-text="n.time"></div>
              </div>
              <span x-show="!n.read" class="ms-auto mt-1 flex-shrink-0"
                    style="width:7px;height:7px;background:#0d6efd;border-radius:50%"></span>
            </div>
          </template>
        </div>
        <div class="text-center p-2 border-top">
          <a href="{{ route('notifications') }}" class="text-primary text-decoration-none" style="font-size:12px">View all notifications</a>
        </div>
      </div>
    </div>

    {{-- User menu --}}
    <div class="position-relative" @click.outside="showUserMenu=false">
      <div class="d-flex align-items-center gap-2 cursor-pointer" @click="showUserMenu=!showUserMenu">
        @php $authUser = auth()->user(); $roleLabels = ['super_admin'=>'Super Admin','manufacturer'=>'Manufacturer','logistics'=>'Logistics','finance'=>'Finance','qc_officer'=>'QC Officer','distributor'=>'Country Manager']; @endphp
        <div class="user-avatar">{{ $authUser?->initials ?: strtoupper(substr($authUser?->name ?? 'U', 0, 2)) }}</div>
        <div class="d-none d-md-block">
          <div class="topbar-user-name" style="font-size:13px;font-weight:600;line-height:1.2">{{ $authUser?->name }}</div>
          <div class="topbar-user-role" style="font-size:11px">{{ $roleLabels[$authUser?->role] ?? ucfirst((string)$authUser?->role) }}</div>
        </div>
        <i class="bi bi-chevron-down text-muted" style="font-size:10px"></i>
      </div>
      <div x-show="showUserMenu" x-transition
           class="position-absolute end-0 mt-2 rounded-3 shadow-lg py-1 topbar-dropdown"
           style="width:180px;z-index:1050;top:100%">
        <a href="#" class="dropdown-item py-2 px-3" style="font-size:13px"><i class="bi bi-person me-2"></i>My Profile</a>
        @if($can('users'))<a href="{{ route('users') }}" class="dropdown-item py-2 px-3" style="font-size:13px"><i class="bi bi-people me-2"></i>Users &amp; Roles</a>@endif
        <div class="dropdown-divider my-1"></div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="dropdown-item py-2 px-3 text-danger w-100 text-start border-0 bg-transparent" style="font-size:13px">
            <i class="bi bi-box-arrow-right me-2"></i>Sign Out
          </button>
        </form>
      </div>
    </div>

    {{-- Dark mode toggle --}}
    <button class="theme-toggle-btn" @click="toggleDarkMode()"
            :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
      <i class="bi" :class="darkMode ? 'bi-sun-fill' : 'bi-moon-fill'"></i>
    </button>

  </div>
</header>

{{-- ===== PAGE CONTENT ===== --}}
<main class="main-content" :class="{'collapsed': sidebarCollapsed}">
  @yield('content')
</main>

{{-- Mobile sidebar overlay --}}
<div class="sidebar-overlay" x-show="mobileSidebarOpen" @click="mobileSidebarOpen=false"></div>

{{-- Global toast notifications — call from any page: $store.toast.show('msg','warning') --}}
<div class="app-toast-wrap" x-data>
  <template x-for="t in $store.toast.items" :key="t.id">
    <div class="app-toast" :class="'app-toast-'+t.type" x-transition>
      <i class="bi" :class="t.type==='warning'?'bi-exclamation-triangle-fill':(t.type==='danger'?'bi-x-circle-fill':(t.type==='success'?'bi-check-circle-fill':'bi-info-circle-fill'))"></i>
      <span x-text="t.msg"></span>
      <button type="button" class="app-toast-close" @click="$store.toast.dismiss(t.id)" aria-label="Dismiss">&times;</button>
    </div>
  </template>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="{{ asset('js/layout.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
