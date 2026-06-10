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
    <div class="logo-text" x-show="!sidebarCollapsed">Pharma<span>Track</span></div>
    <button class="sidebar-collapse-btn"
            :class="{'ms-auto': !sidebarCollapsed}"
            @click="toggleSidebar()"
            :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
      <i class="bi" :class="sidebarCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
    </button>
  </div>

  <nav class="sidebar-nav">

    {{-- Main --}}
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Main</div>
    <a href="{{ route('dashboard') }}" class="nav-item-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-grid-1x2-fill"></i></span>
      <span x-show="!sidebarCollapsed">Dashboard</span>
    </a>

    {{-- Products --}}
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Products</div>
    <a href="{{ route('products.index') }}" class="nav-item-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-capsule"></i></span>
      <span x-show="!sidebarCollapsed">Product Master</span>
    </a>
    <a href="{{ route('batches') }}" class="nav-item-link {{ request()->routeIs('batches') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-layers"></i></span>
      <span x-show="!sidebarCollapsed">Batch &amp; Lot Mgmt</span>
    </a>

    {{-- Order Documents --}}
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Orders</div>
    <div x-data="{open: {{ request()->routeIs('orders.*') ? 'true' : 'false' }}}">
      <button class="nav-item-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" @click="open = !open">
        <span class="nav-icon"><i class="bi bi-file-text"></i></span>
        <span x-show="!sidebarCollapsed">Order Documents</span>
        <i class="bi bi-chevron-right nav-caret" x-show="!sidebarCollapsed" :class="{open: open}"></i>
      </button>
      <div class="nav-submenu" :class="{open: open}">
        <a href="{{ route('orders.po') }}" class="nav-item-link {{ request()->routeIs('orders.po') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-cart3"></i></span>
          <span x-show="!sidebarCollapsed">Purchase Orders</span>
        </a>
        <a href="{{ route('orders.so') }}" class="nav-item-link {{ request()->routeIs('orders.so') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-bag-check"></i></span>
          <span x-show="!sidebarCollapsed">Sales Orders</span>
        </a>
        <a href="{{ route('orders.pi') }}" class="nav-item-link {{ request()->routeIs('orders.pi') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-receipt"></i></span>
          <span x-show="!sidebarCollapsed">Proforma Invoice</span>
        </a>
        <a href="{{ route('orders.ci') }}" class="nav-item-link {{ request()->routeIs('orders.ci') ? 'active' : '' }}">
          <span class="nav-icon"><i class="bi bi-file-earmark-check"></i></span>
          <span x-show="!sidebarCollapsed">Commercial Invoice</span>
        </a>
      </div>
    </div>

    {{-- Logistics --}}
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Logistics</div>
    <a href="{{ route('shipments') }}" class="nav-item-link {{ request()->routeIs('shipments') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-truck"></i></span>
      <span x-show="!sidebarCollapsed">Shipment Management</span>
      <span class="nav-badge" x-show="!sidebarCollapsed">3</span>
    </a>
    <a href="{{ route('distribution') }}" class="nav-item-link {{ request()->routeIs('distribution') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-diagram-3"></i></span>
      <span x-show="!sidebarCollapsed">Distribution Hierarchy</span>
    </a>

    {{-- Compliance --}}
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Compliance</div>
    <a href="{{ route('countries') }}" class="nav-item-link {{ request()->routeIs('countries') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-globe2"></i></span>
      <span x-show="!sidebarCollapsed">Country Permissions</span>
    </a>
    <a href="{{ route('anticounterfeit') }}" class="nav-item-link {{ request()->routeIs('anticounterfeit') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-shield-check"></i></span>
      <span x-show="!sidebarCollapsed">Anti-Counterfeit</span>
      <span class="nav-badge nav-badge-danger" x-show="!sidebarCollapsed">!</span>
    </a>
    <a href="{{ route('vault') }}" class="nav-item-link {{ request()->routeIs('vault') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-safe2"></i></span>
      <span x-show="!sidebarCollapsed">Document Vault</span>
    </a>

    {{-- Portal --}}
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Portal</div>
    <a href="{{ route('patients') }}" class="nav-item-link {{ request()->routeIs('patients') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-person-heart"></i></span>
      <span x-show="!sidebarCollapsed">Patient Portal</span>
    </a>

    {{-- Admin --}}
    <div class="sidebar-section-label" x-show="!sidebarCollapsed">Admin</div>
    <a href="{{ route('reports') }}" class="nav-item-link {{ request()->routeIs('reports') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-bar-chart-line"></i></span>
      <span x-show="!sidebarCollapsed">Reports &amp; Analytics</span>
    </a>
    <a href="{{ route('notifications') }}" class="nav-item-link {{ request()->routeIs('notifications') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-bell"></i></span>
      <span x-show="!sidebarCollapsed">Notifications</span>
      <span class="nav-badge" x-show="!sidebarCollapsed && unreadCount > 0" x-text="unreadCount"></span>
    </a>
    <a href="{{ route('users') }}" class="nav-item-link {{ request()->routeIs('users') ? 'active' : '' }}">
      <span class="nav-icon"><i class="bi bi-people"></i></span>
      <span x-show="!sidebarCollapsed">Users &amp; Roles</span>
    </a>

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
        <div class="user-avatar" x-text="currentUser.initials"></div>
        <div class="d-none d-md-block">
          <div class="topbar-user-name" style="font-size:13px;font-weight:600;line-height:1.2" x-text="currentUser.name"></div>
          <div class="topbar-user-role" style="font-size:11px" x-text="currentUser.role"></div>
        </div>
        <i class="bi bi-chevron-down text-muted" style="font-size:10px"></i>
      </div>
      <div x-show="showUserMenu" x-transition
           class="position-absolute end-0 mt-2 rounded-3 shadow-lg py-1 topbar-dropdown"
           style="width:180px;z-index:1050;top:100%">
        <a href="#" class="dropdown-item py-2 px-3" style="font-size:13px"><i class="bi bi-person me-2"></i>My Profile</a>
        <a href="#" class="dropdown-item py-2 px-3" style="font-size:13px"><i class="bi bi-gear me-2"></i>Settings</a>
        <div class="dropdown-divider my-1"></div>
        <a href="{{ route('login') }}" class="dropdown-item py-2 px-3 text-danger" style="font-size:13px">
          <i class="bi bi-box-arrow-right me-2"></i>Sign Out
        </a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="{{ asset('js/layout.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
