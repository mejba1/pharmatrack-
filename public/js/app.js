/* =============================================
   Pharma Tracking — Alpine.js App Logic
   ============================================= */

/* ---- Apply saved theme immediately (before Alpine) to prevent FOUC ---- */
(function () {
  const dark = localStorage.getItem('darkMode') === 'true';
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
})();

document.addEventListener('alpine:init', () => {

  /* ---- Global App State ---- */
  Alpine.store('app', {
    sidebarCollapsed: false,
    mobileSidebarOpen: false,
    notifications: [
      { id: 1, type: 'warning', icon: '⚠️', message: 'Batch BRN-00142-2601-003 expires in 30 days', time: '5 min ago', read: false },
      { id: 2, type: 'danger',  icon: '🚨', message: 'Counterfeit alert: UUC scan anomaly detected in Lagos', time: '1 hr ago', read: false },
      { id: 3, type: 'info',   icon: '📦', message: 'Shipment SHP-2026-0482 delivered to Distributor PH', time: '2 hr ago', read: true },
      { id: 4, type: 'success', icon: '✅', message: 'PI-2026-0091 approved by Finance', time: '3 hr ago', read: true },
      { id: 5, type: 'warning', icon: '📋', message: 'GMP Certificate for Manufacturing Site CN-01 expiring in 15 days', time: '5 hr ago', read: false },
    ],
    get unreadCount() { return this.notifications.filter(n => !n.read).length; },
    markAllRead() { this.notifications.forEach(n => n.read = true); },
    toggleSidebar() { this.sidebarCollapsed = !this.sidebarCollapsed; },
    toggleMobileSidebar() { this.mobileSidebarOpen = !this.mobileSidebarOpen; },
  });

  /* ---- Toast Notifications ---- */
  Alpine.store('toast', {
    items: [],
    show(msg, type = 'info', duration = 4000) {
      const id = Date.now();
      this.items.push({ id, msg, type });
      setTimeout(() => this.dismiss(id), duration);
    },
    dismiss(id) { this.items = this.items.filter(t => t.id !== id); },
  });

});

/* ---- Utility Formatters ---- */
function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
}

function formatNumber(n) {
  if (n == null) return '—';
  return Number(n).toLocaleString();
}

/* ---- Demo chart sparkline (CSS only bars) ---- */
function sparkBars(selector, data) {
  const el = document.querySelector(selector);
  if (!el) return;
  const max = Math.max(...data);
  el.innerHTML = data.map(v => {
    const h = Math.round((v / max) * 100);
    return `<div style="flex:1;height:100%;display:flex;align-items:flex-end;padding:0 1px">
      <div style="width:100%;height:${h}%;background:rgba(13,110,253,0.4);border-radius:2px 2px 0 0"></div>
    </div>`;
  }).join('');
  el.style.display = 'flex';
  el.style.alignItems = 'flex-end';
}

document.addEventListener('DOMContentLoaded', () => {
  // Demo sparklines
  const sparkData = [12, 18, 14, 22, 19, 25, 21, 28, 24, 30, 26, 33];
  document.querySelectorAll('.mini-chart').forEach((el, i) => {
    const offset = i * 3;
    const d = sparkData.map((v, j) => v + offset + (j % 3));
    const max = Math.max(...d);
    el.innerHTML = d.map(v => {
      const h = Math.round((v / max) * 80) + 20;
      return `<div style="flex:1;height:100%;display:flex;align-items:flex-end;padding:0 1px">
        <div style="width:100%;height:${h}%;background:rgba(13,110,253,0.35);border-radius:2px 2px 0 0"></div>
      </div>`;
    }).join('');
    el.style.display = 'flex';
    el.style.alignItems = 'flex-end';
  });
});
