{{-- Shared data-table footer: range summary + per-page + pager. Bound to a portalTable() scope. --}}
<div class="d-flex flex-wrap align-items-center gap-2 mt-3">
  <div class="text-muted small me-auto">
    Showing <span x-text="total ? ((page-1)*perPage + 1) : 0"></span>–<span x-text="Math.min(page*perPage, total)"></span> of <span x-text="total"></span>
  </div>
  <div class="d-flex align-items-center gap-1">
    <span class="text-muted small">Per page</span>
    <select class="form-select form-select-sm" style="width:auto" x-model.number="perPage" @change="page=1">
      <option :value="10">10</option>
      <option :value="25">25</option>
      <option :value="50">50</option>
    </select>
  </div>
  <div class="btn-group btn-group-sm">
    <button class="btn btn-outline-secondary" :disabled="page<=1" @click="if(page>1)page--"><i class="bi bi-chevron-left"></i></button>
    <button class="btn btn-outline-secondary" disabled><span x-text="page"></span> / <span x-text="pages"></span></button>
    <button class="btn btn-outline-secondary" :disabled="page>=pages" @click="if(page<pages)page++"><i class="bi bi-chevron-right"></i></button>
  </div>
</div>
