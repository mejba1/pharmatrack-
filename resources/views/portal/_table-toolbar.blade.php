{{-- Shared data-table toolbar: text search + date range. Bound to a portalTable() scope. --}}
<div class="row g-2 mb-3 align-items-end">
  <div class="col-12 col-md-5">
    <label class="form-label text-muted mb-1" style="font-size:11px">Search</label>
    <div class="input-group input-group-sm">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="search" class="form-control" placeholder="{{ $placeholder ?? 'Search…' }}" x-model="q" @input="page=1">
    </div>
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label text-muted mb-1" style="font-size:11px">From date</label>
    <input type="date" class="form-control form-control-sm" x-model="from" @change="page=1">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label text-muted mb-1" style="font-size:11px">To date</label>
    <input type="date" class="form-control form-control-sm" x-model="to" @change="page=1">
  </div>
  <div class="col-12 col-md-1 text-md-end">
    <button class="btn btn-outline-secondary btn-sm" @click="reset()" x-show="q || from || to" title="Clear filters"><i class="bi bi-x-circle"></i></button>
  </div>
</div>
