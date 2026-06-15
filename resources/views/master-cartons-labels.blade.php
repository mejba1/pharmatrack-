@extends('layouts.app')
@section('title', 'Download / Print Carton Labels')

@push('styles')
<style>
  [x-cloak]{display:none!important}
  .section-label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d;margin-bottom:8px}
  .mode-pill{border:1px solid #dee2e6;border-radius:10px;padding:12px 14px;cursor:pointer;transition:.15s;height:100%}
  .mode-pill:hover{border-color:#9ec5fe}
  .mode-pill.on{border-color:#0d6efd;background:rgba(13,110,253,.06);box-shadow:0 0 0 1px #0d6efd inset}
  .mode-pill .t{font-weight:600;font-size:13px}
  .mode-pill .d{font-size:11px;color:#6c757d}
</style>
@endpush

@section('content')
<div x-data="labelsCenter()">

  <div class="page-header">
    <div>
      <h1>Download / Print Carton Labels</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / <a href="{{ route('master-cartons') }}">Master Carton Mgmt</a> / Carton Labels</div>
    </div>
    <a href="{{ route('master-cartons') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-grid me-1"></i>All Cartons</a>
  </div>

  <div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="font-size:13px">
    <i class="bi bi-printer"></i>
    <span>Choose what to include, optionally narrow by status / fill / <strong>date range</strong>, then <strong>Print</strong> (9-up cut-friendly sheet) or <strong>Download PDF</strong>.</span>
  </div>

  <div class="card mb-3"><div class="card-body">
    {{-- Scope --}}
    <div class="section-label">Scope</div>
    <div class="row g-2 mb-3">
      <div class="col-6 col-md-3"><div class="mode-pill" :class="{on: scope==='all'}" @click="scope='all'"><div class="t"><i class="bi bi-grid me-1"></i>All cartons</div><div class="d">Every master carton</div></div></div>
      <div class="col-6 col-md-3"><div class="mode-pill" :class="{on: scope==='generic'}" @click="scope='generic'"><div class="t"><i class="bi bi-box me-1"></i>Generic only</div><div class="d">Unassigned generic cartons</div></div></div>
      <div class="col-6 col-md-3"><div class="mode-pill" :class="{on: scope==='product'}" @click="scope='product'; batchId=''"><div class="t"><i class="bi bi-capsule me-1"></i>By product</div><div class="d">All of one product</div></div></div>
      <div class="col-6 col-md-3"><div class="mode-pill" :class="{on: scope==='batch'}" @click="scope='batch'"><div class="t"><i class="bi bi-upc-scan me-1"></i>By product &amp; batch</div><div class="d">One batch</div></div></div>
    </div>

    <div class="row g-3" x-show="scope==='product' || scope==='batch'" x-cloak>
      <div class="col-md-4">
        <label class="form-label">Product <span class="text-danger">*</span></label>
        <select class="form-select form-select-sm" x-model="productId" @change="onProduct()">
          <option value="">Select product…</option>
          @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
        </select>
      </div>
      <div class="col-md-4" x-show="scope==='batch'">
        <label class="form-label">Batch <span class="text-danger">*</span></label>
        <select class="form-select form-select-sm" x-model="batchId" :disabled="!productId || loadingB">
          <option value="" x-text="!productId ? 'Select a product first…' : (batches.length ? 'Select batch…' : 'No batches')"></option>
          <template x-for="b in batches" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
        </select>
      </div>
    </div>

    {{-- Refine --}}
    <div class="section-label mt-4">Refine (optional)</div>
    <div class="row g-3">
      <div class="col-6 col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select form-select-sm" x-model="status">
          <option value="">Any status</option>
          @foreach(['created'=>'Created','packed'=>'Packed','dispatched'=>'Dispatched','received'=>'Received'] as $v=>$l)
            <option value="{{ $v }}">{{ $l }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Fill</label>
        <select class="form-select form-select-sm" x-model="fill">
          <option value="">Any fill</option>
          <option value="empty">Unpacked (empty)</option>
          <option value="packed">Packed (any)</option>
          <option value="partial">Partially packed</option>
          <option value="full">Fully packed</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Created from</label>
        <input type="date" class="form-control form-control-sm" x-model="dateFrom">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Created to</label>
        <input type="date" class="form-control form-control-sm" x-model="dateTo">
      </div>
    </div>
  </div></div>

  <div class="card"><div class="card-body d-flex flex-wrap align-items-center gap-2">
    <div class="text-muted-sm" x-show="!ready" x-cloak><i class="bi bi-exclamation-circle me-1"></i>Select a product/batch to continue.</div>
    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-outline-primary" :disabled="!ready" @click="go('print')"><i class="bi bi-printer me-1"></i>Print</button>
      <button class="btn btn-danger" :disabled="!ready" @click="go('pdf')"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</button>
    </div>
  </div></div>
</div>
@endsection

@push('scripts')
<script>
function labelsCenter(){
  return {
    scope:'all', productId:'', batchId:'', batches:[], loadingB:false,
    status:'', fill:'', dateFrom:'', dateTo:'',
    get ready(){ if(this.scope==='product') return !!this.productId; if(this.scope==='batch') return !!this.batchId; return true; },
    async onProduct(){ this.batchId=''; this.batches=[]; if(!this.productId) return; this.loadingB=true;
      try{ this.batches=await fetch(`{{ url('partial-batches/products') }}/${this.productId}/batches`,{headers:{'Accept':'application/json'}}).then(r=>r.json()); }catch(e){ this.batches=[]; }
      this.loadingB=false; },
    params(){
      const p=new URLSearchParams();
      if(this.scope==='generic') p.set('type','generic');
      if(this.scope==='product') p.set('product_id', this.productId);
      if(this.scope==='batch'){ p.set('product_id', this.productId); p.set('batch_id', this.batchId); }
      if(this.status) p.set('status', this.status);
      if(this.fill) p.set('fill', this.fill);
      if(this.dateFrom) p.set('date_from', this.dateFrom);
      if(this.dateTo) p.set('date_to', this.dateTo);
      return p.toString();
    },
    go(action){
      const base = action==='pdf' ? '{{ route('master-cartons.labels-pdf') }}' : '{{ route('master-cartons.labels') }}';
      const qs = this.params();
      const url = base + (qs ? ('?'+qs) : '');
      if(action==='print') window.open(url,'_blank'); else window.location.href=url;
    },
  };
}
</script>
@endpush
