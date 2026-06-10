/* =============================================
   Injects sidebar + topbar HTML into every page
   Usage: <div id="layout-root"> ... </div>
   Call: injectLayout('Page Title', 'active-key')
   ============================================= */

function injectLayout(pageTitle, activeKey) {

  const navItems = [
    { key:'dashboard',     label:'Dashboard',          icon:'bi-grid-1x2-fill',      href:'dashboard.html',     section:'Main' },
    { key:'products',      label:'Product Master',     icon:'bi-capsule',            href:'products.html',      section:'Products' },
    { key:'batches',       label:'Batch & Lot Mgmt',   icon:'bi-layers',             href:'batches.html',       section:null },
    { key:'orders-po',     label:'Purchase Orders',    icon:'bi-cart3',              href:'orders-po.html',     section:'Orders', parent:'orders' },
    { key:'orders-so',     label:'Sales Orders',       icon:'bi-bag-check',          href:'orders-so.html',     section:null, parent:'orders' },
    { key:'orders-pi',     label:'Proforma Invoice',   icon:'bi-receipt',            href:'orders-pi.html',     section:null, parent:'orders' },
    { key:'orders-ci',     label:'Commercial Invoice', icon:'bi-file-earmark-check', href:'orders-ci.html',     section:null, parent:'orders' },
    { key:'shipments',     label:'Shipment Management',icon:'bi-truck',              href:'shipments.html',     section:'Logistics', badge:'3' },
    { key:'distribution',  label:'Distribution Hierarchy', icon:'bi-diagram-3',     href:'distribution.html',  section:null },
    { key:'countries',     label:'Country Permissions',icon:'bi-globe2',             href:'countries.html',     section:'Compliance' },
    { key:'anticounterfeit',label:'Anti-Counterfeit',  icon:'bi-shield-check',       href:'anticounterfeit.html',section:null, badge:'!' },
    { key:'vault',         label:'Document Vault',     icon:'bi-safe2',              href:'vault.html',         section:null },
    { key:'patient-portal',label:'Patient Portal',     icon:'bi-person-heart',       href:'patient-portal.html',section:'Portal' },
    { key:'reports',       label:'Reports & Analytics',icon:'bi-bar-chart-line',     href:'reports.html',       section:'Admin' },
    { key:'notifications', label:'Notifications',      icon:'bi-bell',               href:'notifications.html', section:null, badge:'notif' },
    { key:'users',         label:'Users & Roles',      icon:'bi-people',             href:'users.html',         section:null },
  ];

  const groups = { orders: { label:'Order Documents', icon:'bi-file-text', keys:['orders-po','orders-so','orders-pi','orders-ci'] } };

  function buildSidebarNav() {
    let html = '';
    const rendered = new Set();

    navItems.forEach(item => {
      if (rendered.has(item.key)) return;

      // Section label
      if (item.section) {
        html += `<div class="sidebar-section-label" x-show="!sidebarCollapsed">${item.section}</div>`;
      }

      // Grouped submenu
      if (item.parent && !rendered.has(item.parent)) {
        const grp = groups[item.parent];
        const children = navItems.filter(n => n.parent === item.parent);
        const isGroupActive = children.some(c => c.key === activeKey);
        children.forEach(c => rendered.add(c.key));
        rendered.add(item.parent);

        html += `
        <div x-data="{open: ${isGroupActive}}">
          <button class="nav-item-link ${isGroupActive ? 'active' : ''}" @click="open = !open">
            <span class="nav-icon"><i class="bi ${grp.icon}"></i></span>
            <span x-show="!sidebarCollapsed">${grp.label}</span>
            <i class="bi bi-chevron-right nav-caret" x-show="!sidebarCollapsed" :class="{open: open}"></i>
          </button>
          <div class="nav-submenu" :class="{open: open}">
            ${children.map(c => `
              <a href="${c.href}" class="nav-item-link ${c.key === activeKey ? 'active' : ''}">
                <span class="nav-icon"><i class="bi ${c.icon}"></i></span>
                <span x-show="!sidebarCollapsed">${c.label}</span>
              </a>`).join('')}
          </div>
        </div>`;
        return;
      }

      if (rendered.has(item.key)) return;
      rendered.add(item.key);

      const badgeHtml = item.badge === 'notif'
        ? `<span class="nav-badge" x-show="!sidebarCollapsed && unreadCount > 0" x-text="unreadCount"></span>`
        : item.badge ? `<span class="nav-badge" x-show="!sidebarCollapsed">${item.badge}</span>` : '';

      html += `
        <a href="${item.href}" class="nav-item-link ${item.key === activeKey ? 'active' : ''}">
          <span class="nav-icon"><i class="bi ${item.icon}"></i></span>
          <span x-show="!sidebarCollapsed">${item.label}</span>
          ${badgeHtml}
        </a>`;
    });
    return html;
  }

  const sidebarHTML = `
  <aside class="sidebar" :class="{'collapsed': sidebarCollapsed, 'mobile-open': mobileSidebarOpen}">
    <div class="sidebar-logo">
      <div class="logo-icon" x-show="!sidebarCollapsed"><i class="bi bi-capsule-pill"></i></div>
      <div class="logo-text" x-show="!sidebarCollapsed">Pharma<span>Track</span></div>
      <button class="sidebar-collapse-btn" :class="{'ms-auto': !sidebarCollapsed}" @click="toggleSidebar()" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
        <i class="bi" :class="sidebarCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
      </button>
    </div>
    <nav class="sidebar-nav">${buildSidebarNav()}</nav>
  </aside>`;

  const topbarHTML = `
  <header class="topbar" :class="{'collapsed': sidebarCollapsed}">
    <button class="btn btn-link p-0 me-2 d-md-none text-secondary" @click="mobileSidebarOpen = !mobileSidebarOpen">
      <i class="bi bi-list fs-5"></i>
    </button>
    <div>
      <div class="topbar-title">${pageTitle}</div>
      <nav style="font-size:11px;color:#6c757d">
        <a href="dashboard.html" class="text-primary text-decoration-none">Home</a>
        <span class="mx-1">/</span><span>${pageTitle}</span>
      </nav>
    </div>
    <div class="topbar-actions ms-auto">
      <div class="search-wrapper d-none d-lg-block">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="form-control form-control-sm" placeholder="Search..." style="width:240px">
      </div>
      <div class="position-relative" @click.outside="showNotifPanel=false">
        <button class="topbar-btn" @click="showNotifPanel=!showNotifPanel">
          <i class="bi bi-bell"></i>
          <span class="badge-dot" x-show="unreadCount>0"></span>
        </button>
        <div x-show="showNotifPanel" x-transition class="position-absolute end-0 mt-2 rounded-3 shadow-lg topbar-dropdown" style="width:320px;z-index:1050;top:100%">
          <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <span class="fw-semibold" style="font-size:14px">Notifications <span class="badge bg-danger rounded-pill ms-1" x-text="unreadCount" x-show="unreadCount>0"></span></span>
            <button class="btn btn-link btn-sm p-0 text-primary" style="font-size:12px" @click="markAllRead()">Mark all read</button>
          </div>
          <div style="max-height:300px;overflow-y:auto">
            <template x-for="n in notifications" :key="n.id">
              <div class="px-3 py-2 border-bottom d-flex gap-2 cursor-pointer" :class="n.read?'':'bg-light-primary'" @click="n.read=true">
                <i :class="n.icon" class="mt-1 flex-shrink-0" style="font-size:14px"></i>
                <div style="min-width:0">
                  <div style="font-size:12px;line-height:1.4" x-text="n.message"></div>
                  <div style="font-size:11px;color:#adb5bd" x-text="n.time"></div>
                </div>
                <span x-show="!n.read" class="ms-auto mt-1 flex-shrink-0" style="width:7px;height:7px;background:#0d6efd;border-radius:50%"></span>
              </div>
            </template>
          </div>
          <div class="text-center p-2 border-top">
            <a href="notifications.html" class="text-primary text-decoration-none" style="font-size:12px">View all notifications</a>
          </div>
        </div>
      </div>
      <div class="position-relative" @click.outside="showUserMenu=false">
        <div class="d-flex align-items-center gap-2 cursor-pointer" @click="showUserMenu=!showUserMenu">
          <div class="user-avatar" x-text="currentUser.initials"></div>
          <div class="d-none d-md-block">
            <div class="topbar-user-name" style="font-size:13px;font-weight:600;line-height:1.2" x-text="currentUser.name"></div>
            <div class="topbar-user-role" style="font-size:11px" x-text="currentUser.role"></div>
          </div>
          <i class="bi bi-chevron-down text-muted" style="font-size:10px"></i>
        </div>
        <div x-show="showUserMenu" x-transition class="position-absolute end-0 mt-2 rounded-3 shadow-lg py-1 topbar-dropdown" style="width:180px;z-index:1050;top:100%">
          <a href="#" class="dropdown-item py-2 px-3" style="font-size:13px"><i class="bi bi-person me-2"></i>My Profile</a>
          <a href="#" class="dropdown-item py-2 px-3" style="font-size:13px"><i class="bi bi-gear me-2"></i>Settings</a>
          <div class="dropdown-divider my-1"></div>
          <a href="../login.html" class="dropdown-item py-2 px-3 text-danger" style="font-size:13px"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a>
        </div>
      </div>
      <button class="theme-toggle-btn" @click="toggleDarkMode()" :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
        <i class="bi" :class="darkMode ? 'bi-sun-fill' : 'bi-moon-fill'"></i>
      </button>
    </div>
  </header>`;

  document.body.insertAdjacentHTML('afterbegin', sidebarHTML + topbarHTML);
}
