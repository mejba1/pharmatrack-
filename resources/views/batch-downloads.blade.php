@extends('layouts.app')
@section('title', 'Batch Downloads')

@push('styles')
<style>
  [x-cloak] { display:none !important; }
  .section-label { font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
    color:#6c757d; padding-bottom:4px; border-bottom:1px solid var(--border-color,#dee2e6); margin-bottom:4px; }
  .dl-card { border:1px solid var(--border-color,#dee2e6); border-radius:12px; padding:14px 16px; cursor:pointer;
    transition:all .12s; height:100%; text-align:center; }
  .dl-card:hover { border-color:#0d6efd; }
  .dl-card.selected { border-color:#0d6efd; background:rgba(13,110,253,.05); box-shadow:0 0 0 1px #0d6efd inset; }
  .dl-card .ic { font-size:26px; }
</style>
@endpush

@section('content')
<div x-data="batchDownloadPage()">

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1>Batch Downloads</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Batch Downloads</div>
    </div>
    <a href="{{ route('batches') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-layers me-1"></i>Batches</a>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">

          {{-- Selection --}}
          <div class="section-label">Select Product, Batch &amp; Reference</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Select Product <span class="text-danger">*</span></label>
              <select class="form-select" x-model="productId" @change="onProductChange()">
                <option value="">Select product…</option>
                @foreach($products as $p)
                  <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Select Batch <span class="text-danger">*</span></label>
              <select class="form-select" x-model="batchId" @change="onBatchChange()" :disabled="!productId || loadingBatches">
                <option value="" x-text="!productId ? 'Select a product first…' : (batches.length ? 'Select batch…' : 'No batches for this product')"></option>
                <template x-for="b in batches" :key="b.id">
                  <option :value="b.id" x-text="b.label"></option>
                </template>
              </select>
              <div class="form-text" x-show="loadingBatches"><span class="spinner-border spinner-border-sm me-1"></span>Loading batches…</div>
            </div>

            <div class="col-md-6" x-show="batchId" x-cloak>
              <label class="form-label">Batch Reference (scope) <span class="text-danger">*</span></label>
              <select class="form-select" x-model="scope" :disabled="loadingScopes">
                <template x-for="s in scopes" :key="s.value">
                  <option :value="s.value" x-text="s.label + ' · ' + (s.count||0).toLocaleString() + ' units'"></option>
                </template>
              </select>
              <div class="form-text" x-show="loadingScopes"><span class="spinner-border spinner-border-sm me-1"></span>Loading references…</div>
              <div class="form-text" x-show="!loadingScopes && info && !info.has_partials">This batch has no partial batches — only the full batch is available.</div>
            </div>

            <div class="col-md-6" x-show="batchId" x-cloak>
              <label class="form-label">Code Field <span class="text-danger">*</span></label>
              <select class="form-select" x-model="field">
                <option value="secret_code">Secret Code (QR / verify)</option>
                <option value="unique_number">Unique Number (label)</option>
              </select>
            </div>
          </div>

          {{-- Format --}}
          <div x-show="batchId" x-cloak class="mt-4">
            <div class="section-label">Download Format</div>
            <div class="row g-2">
              <div class="col-4">
                <div class="dl-card" :class="{selected: format==='txt'}" @click="format='txt'">
                  <div class="ic text-secondary"><i class="bi bi-filetype-txt"></i></div>
                  <div class="fw-semibold mt-1">Text</div><div class="text-muted-sm">.txt — one code/line</div>
                </div>
              </div>
              <div class="col-4">
                <div class="dl-card" :class="{selected: format==='excel'}" @click="format='excel'">
                  <div class="ic text-success"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                  <div class="fw-semibold mt-1">Excel</div><div class="text-muted-sm">.csv — serial + code</div>
                </div>
              </div>
              <div class="col-4">
                <div class="dl-card" :class="{selected: format==='pdf'}" @click="format='pdf'">
                  <div class="ic text-danger"><i class="bi bi-file-earmark-pdf"></i></div>
                  <div class="fw-semibold mt-1">PDF</div><div class="text-muted-sm">.pdf — printable table</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
              <div class="text-muted-sm" x-show="selectedScope">
                Downloading <strong x-text="(selectedScope?.count||0).toLocaleString()"></strong> codes
                from <span class="font-monospace" x-text="info?.brn"></span>
                <span x-show="scope!=='full'">· <span class="font-monospace" x-text="scope"></span></span>
              </div>
              <button class="btn btn-primary" @click="download()" :disabled="!canDownload">
                <i class="bi bi-download me-1"></i>Download
              </button>
            </div>
          </div>

          <div x-show="!productId" class="text-center text-muted py-5">
            <i class="bi bi-cloud-download" style="font-size:32px;opacity:.2"></i>
            <div class="mt-2">Select a product and batch to download unit codes.</div>
          </div>

        </div>
      </div>
    </div>

    {{-- Help panel --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header bg-transparent fw-semibold"><i class="bi bi-info-circle me-1"></i>About scopes</div>
        <div class="card-body">
          <ul class="mb-0 ps-3" style="font-size:13px;line-height:1.7">
            <li><strong>Full batch</strong> — every unit in the batch (original + all partial batches).</li>
            <li><strong>Original units only</strong> — units created when the batch was first registered.</li>
            <li><strong>A partial batch (PBN-…)</strong> — only the units added in that specific extension.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function batchDownloadPage() {
  return {
    productId:'', batchId:'', scope:'full', field:'secret_code', format:'txt',
    batches:[], scopes:[], info:null,
    loadingBatches:false, loadingScopes:false,

    get selectedScope(){ return this.scopes.find(s => s.value === this.scope) || null; },
    get canDownload(){ return this.batchId && this.scope && this.field && this.format; },

    async onProductChange(){
      this.batchId=''; this.batches=[]; this.scopes=[]; this.info=null;
      if(!this.productId) return;
      this.loadingBatches=true;
      try {
        const r = await fetch(`{{ url('partial-batches/products') }}/${this.productId}/batches`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        this.batches = await r.json();
      } catch(e){ this.batches=[]; }
      this.loadingBatches=false;
    },

    async onBatchChange(){
      this.scopes=[]; this.info=null; this.scope='full';
      if(!this.batchId) return;
      this.loadingScopes=true;
      try {
        const r = await fetch(`{{ url('batches') }}/${this.batchId}/export-scopes`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        this.info = await r.json();
        this.scopes = this.info.scopes;
      } catch(e){ this.scopes=[]; }
      this.loadingScopes=false;
    },

    download(){
      if(!this.canDownload) return;
      const url = `{{ url('batches') }}/${this.batchId}/export?partial_ref=${encodeURIComponent(this.scope)}&field=${this.field}&format=${this.format}`;
      window.location.href = url;
    },
  };
}
</script>
@endpush
