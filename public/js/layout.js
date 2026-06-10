/* =============================================
   Shared Layout — Sidebar + Topbar Alpine Data
   ============================================= */

function layoutData(pageTitle) {
  return {
    pageTitle,
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    mobileSidebarOpen: false,
    showNotifPanel: false,
    showUserMenu: false,
    openMenus: JSON.parse(localStorage.getItem('openMenus') || '{}'),
    darkMode: localStorage.getItem('darkMode') === 'true',
    notifications: [
      { id: 1, type: 'warning', icon: 'bi-exclamation-triangle-fill text-warning', message: 'Batch BRN-00142-2601-003 expires in 30 days', time: '5 min ago', read: false },
      { id: 2, type: 'danger',  icon: 'bi-shield-exclamation text-danger',         message: 'Counterfeit alert: UUC scan anomaly in Lagos', time: '1 hr ago', read: false },
      { id: 3, type: 'info',   icon: 'bi-box-seam text-primary',                  message: 'Shipment SHP-2026-0482 delivered to PH distributor', time: '2 hr ago', read: true },
      { id: 4, type: 'success', icon: 'bi-check-circle-fill text-success',         message: 'Proforma Invoice PI-2026-0091 approved by Finance', time: '3 hr ago', read: true },
      { id: 5, type: 'warning', icon: 'bi-file-earmark-text text-warning',         message: 'GMP Certificate for Site CN-01 expiring in 15 days', time: '5 hr ago', read: false },
    ],
    get unreadCount() { return this.notifications.filter(n => !n.read).length; },
    markAllRead() { this.notifications.forEach(n => n.read = true); },
    init() {
      // Re-apply theme on Alpine init (belt-and-suspenders with the IIFE in app.js)
      document.documentElement.setAttribute('data-theme', this.darkMode ? 'dark' : 'light');
    },
    toggleDarkMode() {
      this.darkMode = !this.darkMode;
      localStorage.setItem('darkMode', this.darkMode);
      document.documentElement.setAttribute('data-theme', this.darkMode ? 'dark' : 'light');
    },
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed;
      localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
    },
    toggleMenu(key) {
      this.openMenus[key] = !this.openMenus[key];
      localStorage.setItem('openMenus', JSON.stringify(this.openMenus));
    },
    isMenuOpen(key) { return !!this.openMenus[key]; },
    isActive(href) { return window.location.pathname.endsWith(href); },
    currentUser: { name: 'Dr. Sarah Admin', role: 'Super Admin', initials: 'SA' },
  };
}
