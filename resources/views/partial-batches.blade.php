@extends('layouts.app')
@section('title', 'Partial Batch Quantity')

@push('styles')
<style>
  [x-cloak] { display:none !important; }
  .section-label { font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
    color:#6c757d; padding-bottom:4px; border-bottom:1px solid var(--border-color,#dee2e6); margin-bottom:4px; }
  .info-tile { display:flex; align-items:center; gap:10px; padding:10px 12px;
    border:1px solid var(--border-color,#e9ecef); border-radius:11px; height:100%; min-width:0; }
  .info-ic { width:36px; height:36px; border-radius:9px; flex-shrink:0; font-size:16px;
    display:flex; align-items:center; justify-content:center; background:rgba(13,110,253,.08); color:#0d6efd; }
  .info-txt { min-width:0; }
  .serial-preview { border:1px dashed var(--border-color,#cdd3da); border-radius:12px; padding:14px 16px;
    background:linear-gradient(180deg, rgba(13,110,253,.04), rgba(13,110,253,.01)); }
  .serial-chip { font-size:22px; font-weight:700; color:#0d6efd; font-variant-numeric:tabular-nums; }
  .opt-card { border:1px solid var(--border-color,#dee2e6); border-radius:12px; padding:12px 14px; cursor:pointer;
    transition:all .12s; height:100%; }
  .opt-card:hover { border-color:#0d6efd; }
  .opt-card.selected { border-color:#0d6efd; background:rgba(13,110,253,.05); box-shadow:0 0 0 1px #0d6efd inset; }
</style>
@endpush

@section('content')
<div x-data="partialBatchPage()">

  {{-- Flash --}}
  @if(session('success'))
  <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill text-success"></i><span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1>Partial Batch Quantity</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Partial Batch Quantity</div>
    </div>
    <a href="{{ route('batches') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-layers me-1"></i>Batches</a>
  </div>

  <div class="row g-3">
    {{-- ═══════════ FORM ═══════════ --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="submit()">
            @csrf

            <template x-if="Object.keys(errors).length">
              <div class="alert alert-danger mb-3"><strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the errors below.</strong>
                <ul class="mb-0 mt-1 ps-3" style="font-size:13px"><template x-for="(msgs,f) in errors" :key="f"><template x-for="m in msgs" :key="m"><li x-text="m"></li></template></template></ul></div>
            </template>

            {{-- Selection --}}
            <div class="section-label">Select Product &amp; Batch</div>
            <div class="row g-3 mb-2">
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
                <label class="form-label">Select Existing Batch <span class="text-danger">*</span></label>
                <select class="form-select" x-model="batchId" @change="onBatchChange()" :disabled="!productId || loadingBatches">
                  <option value="" x-text="!productId ? 'Select a product first…' : (batches.length ? 'Select batch…' : 'No batches for this product')"></option>
                  <template x-for="b in batches" :key="b.id">
                    <option :value="b.id" x-text="b.label"></option>
                  </template>
                </select>
                <div class="form-text" x-show="loadingBatches"><span class="spinner-border spinner-border-sm me-1"></span>Loading batches…</div>
              </div>
            </div>

            {{-- Batch info (auto display) --}}
            <div x-show="batchId" x-cloak class="mt-3">
              <div class="section-label">Batch Information</div>
              <div x-show="loadingInfo" class="text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading batch information…</div>
              <div x-show="!loadingInfo && info" class="row g-2">
                <div class="col-6 col-md-4"><div class="info-tile"><div class="info-ic"><i class="bi bi-upc"></i></div>
                  <div class="info-txt"><div class="text-muted-sm">Existing Batch Ref</div><div class="fw-semibold text-truncate font-monospace" x-text="info?.brn"></div></div></div></div>
                <div class="col-6 col-md-4"><div class="info-tile"><div class="info-ic"><i class="bi bi-calendar-check"></i></div>
                  <div class="info-txt"><div class="text-muted-sm">Manufacturing Date</div><div class="fw-semibold" x-text="fmtDate(info?.manufacture_date)"></div></div></div></div>
                <div class="col-6 col-md-4"><div class="info-tile"><div class="info-ic"><i class="bi bi-calendar-x"></i></div>
                  <div class="info-txt"><div class="text-muted-sm">Expiry Date</div><div class="fw-semibold" x-text="fmtDate(info?.expiry_date)"></div></div></div></div>
                <div class="col-6 col-md-4"><div class="info-tile"><div class="info-ic"><i class="bi bi-box-seam"></i></div>
                  <div class="info-txt"><div class="text-muted-sm">Current Batch Quantity</div><div class="fw-semibold" x-text="(info?.total_quantity ?? 0).toLocaleString()"></div></div></div></div>
                <div class="col-6 col-md-4"><div class="info-tile"><div class="info-ic"><i class="bi bi-upc-scan"></i></div>
                  <div class="info-txt"><div class="text-muted-sm">Current Generated Units</div><div class="fw-semibold" x-text="(info?.generated_units ?? 0).toLocaleString()"></div></div></div></div>
                <div class="col-6 col-md-4"><div class="info-tile"><div class="info-ic"><i class="bi bi-bag-check"></i></div>
                  <div class="info-txt"><div class="text-muted-sm">Quantity Available</div><div class="fw-semibold" x-text="(info?.quantity_available ?? 0).toLocaleString()"></div></div></div></div>
              </div>
            </div>

            {{-- Partial batch details --}}
            <div x-show="info" x-cloak class="mt-4">
              <div class="section-label">Partial Batch Details</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">New Partial Batch Reference Number</label>
                  <input type="text" class="form-control font-monospace" x-model="partialRef" readonly>
                  <div class="form-text">Auto-generated for traceability &amp; reporting.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Additional Quantity to Generate <span class="text-danger">*</span></label>
                  <input type="number" min="1" class="form-control" x-model.number="additionalQuantity" placeholder="e.g. 300">
                </div>
              </div>

              {{-- Serial number options --}}
              <div class="mt-3">
                <label class="form-label">Serial Number Starting Point <span class="text-danger">*</span></label>
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="opt-card" :class="{selected: serialMode==='continue'}" @click="serialMode='continue'">
                      <div class="d-flex align-items-start gap-2">
                        <input class="form-check-input mt-1" type="radio" value="continue" x-model="serialMode">
                        <div>
                          <div class="fw-semibold">Continue from next serial</div>
                          <div class="text-muted-sm">Start at <strong x-text="(info?.next_serial ?? 1).toLocaleString()"></strong>
                            (e.g. current size <span x-text="(info?.total_quantity ?? 0).toLocaleString()"></span> → start <span x-text="(info?.next_serial ?? 1).toLocaleString()"></span>).</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="opt-card" :class="{selected: serialMode==='restart'}" @click="serialMode='restart'">
                      <div class="d-flex align-items-start gap-2">
                        <input class="form-check-input mt-1" type="radio" value="restart" x-model="serialMode">
                        <div>
                          <div class="fw-semibold">Start again from 1</div>
                          <div class="text-muted-sm">Numbers this partial batch from <strong>1</strong>
                            to <strong x-text="additionalQuantity ? additionalQuantity.toLocaleString() : '—'"></strong>.</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Manufacturing / expiry dates (optional override for this partial batch) --}}
              <div class="mt-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="overrideDates" x-model="overrideDates">
                  <label class="form-check-label" for="overrideDates">
                    Use different manufacturing / expiry dates for this partial batch
                  </label>
                </div>
                <div class="text-muted-sm ms-4" x-show="!overrideDates">
                  Inherits batch dates — Mfg <strong x-text="fmtDate(info?.manufacture_date)"></strong>,
                  Exp <strong x-text="fmtDate(info?.expiry_date)"></strong>.
                </div>
                <div class="row g-3 mt-1" x-show="overrideDates" x-cloak>
                  <div class="col-md-6">
                    <label class="form-label">Partial Batch Manufacturing Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" x-model="mfgDate">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Partial Batch Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" x-model="expDate">
                  </div>
                </div>
              </div>

              {{-- Serial preview --}}
              <div class="serial-preview mt-3 d-flex flex-wrap align-items-center justify-content-between gap-3" x-show="additionalQuantity > 0">
                <div>
                  <div class="text-muted-sm">This partial batch will generate</div>
                  <div><span class="serial-chip" x-text="(additionalQuantity || 0).toLocaleString()"></span> <span class="text-muted">units</span>
                    <span class="ms-2 text-muted">serials</span> <span class="fw-semibold font-monospace" x-text="serialStart.toLocaleString() + '–' + serialEnd.toLocaleString()"></span></div>
                </div>
                <div class="text-end">
                  <div class="text-muted-sm">Updated total batch quantity</div>
                  <div class="serial-chip" x-text="newTotal.toLocaleString()"></div>
                </div>
              </div>

              <div class="mt-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" rows="2" x-model="notes" placeholder="Optional — reason for the additional quantity"></textarea>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-outline-secondary" @click="resetForm()">Reset</button>
                <button type="submit" class="btn btn-primary" :disabled="saving || !canSubmit">
                  <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                  <i class="bi bi-plus-lg me-1" x-show="!saving"></i>
                  <span x-text="saving ? 'Generating…' : 'Generate Additional Units'"></span>
                </button>
              </div>
            </div>

            <div x-show="!productId" class="text-center text-muted py-5">
              <i class="bi bi-layer-forward" style="font-size:32px;opacity:.2"></i>
              <div class="mt-2">Select a product and batch to extend its quantity.</div>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- ═══════════ RECENT EXTENSIONS ═══════════ --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header bg-transparent fw-semibold"><i class="bi bi-clock-history me-1"></i>Recent Partial Batches</div>
        <div class="card-body p-0">
          @forelse($recent as $ext)
          <div class="px-3 py-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
              <span class="font-monospace fw-semibold text-primary" style="font-size:13px">{{ $ext->partial_ref }}</span>
              <span class="badge text-bg-light">+{{ number_format($ext->additional_quantity) }}</span>
            </div>
            <div class="text-muted-sm">{{ $ext->product?->name ?? '—' }}</div>
            <div class="text-muted-sm">
              <span class="font-monospace">{{ $ext->batch?->brn }}</span> ·
              serials {{ number_format($ext->serial_start) }}–{{ number_format($ext->serial_end) }}
            </div>
            <div class="text-muted-sm">{{ $ext->serial_mode_label }} · {{ $ext->created_at?->diffForHumans() }}</div>
            @if($ext->manufacture_date || $ext->expiry_date)
            <div class="text-muted-sm">
              <i class="bi bi-calendar-event me-1"></i>Mfg {{ optional($ext->manufacture_date)->format('M d, Y') ?? '—' }}
              · Exp {{ optional($ext->expiry_date)->format('M d, Y') ?? '—' }}
              @if($ext->batch && ( optional($ext->manufacture_date)->ne($ext->batch->manufacture_date) || optional($ext->expiry_date)->ne($ext->batch->expiry_date) ))
                <span class="badge text-bg-warning ms-1" style="font-size:10px">custom dates</span>
              @endif
            </div>
            @endif
            @if($ext->batch)
            <div class="d-flex align-items-center gap-2 mt-1">
              <span class="text-muted-sm"><i class="bi bi-box-arrow-down me-1"></i>Export:</span>
              <a href="{{ route('batches.export', $ext->batch) }}?partial_ref={{ urlencode($ext->partial_ref) }}&field=secret_code&format=txt"
                 class="text-decoration-none" style="font-size:12px" title="Download secret codes as text">Text</a>
              <a href="{{ route('batches.export', $ext->batch) }}?partial_ref={{ urlencode($ext->partial_ref) }}&field=secret_code&format=excel"
                 class="text-decoration-none" style="font-size:12px" title="Download secret codes as Excel/CSV">Excel</a>
              <a href="{{ route('batches.units', $ext->batch) }}" class="text-decoration-none text-muted" style="font-size:12px" title="View units">Units →</a>
            </div>
            @endif
          </div>
          @empty
          <div class="text-center text-muted py-4">
            <i class="bi bi-inbox" style="font-size:28px;opacity:.2"></i>
            <div class="mt-1 text-muted-sm">No partial batches yet.</div>
          </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function partialBatchPage() {
  return {
    productId:'', batchId:'',
    batches:[], info:null,
    loadingBatches:false, loadingInfo:false,
    partialRef:'', additionalQuantity:null, serialMode:'continue', notes:'',
    overrideDates:false, mfgDate:'', expDate:'',
    saving:false, errors:{},

    get serialStart(){ return this.serialMode==='restart' ? 1 : (this.info ? this.info.next_serial : 1); },
    get serialEnd(){ const q = this.additionalQuantity || 0; return q > 0 ? this.serialStart + q - 1 : this.serialStart; },
    get newTotal(){ return (this.info ? this.info.total_quantity : 0) + (this.additionalQuantity || 0); },
    get canSubmit(){ return this.batchId && this.additionalQuantity > 0; },

    fmtDate(d){ if(!d) return '—'; const x=new Date(d); return isNaN(x)?'—':x.toLocaleDateString(undefined,{year:'numeric',month:'short',day:'numeric'}); },

    async onProductChange(){
      this.batchId=''; this.info=null; this.batches=[]; this.errors={};
      if(!this.productId) return;
      this.loadingBatches=true;
      try {
        const r = await fetch(`{{ url('partial-batches/products') }}/${this.productId}/batches`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        this.batches = await r.json();
      } catch(e){ this.batches=[]; }
      this.loadingBatches=false;
    },

    async onBatchChange(){
      this.info=null; this.errors={};
      if(!this.batchId) return;
      this.loadingInfo=true;
      try {
        const r = await fetch(`{{ url('partial-batches/batches') }}/${this.batchId}/info`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        this.info = await r.json();
        this.partialRef = this.info.suggested_ref;
        this.mfgDate = this.info.manufacture_date ?? '';
        this.expDate = this.info.expiry_date ?? '';
      } catch(e){ this.info=null; }
      this.loadingInfo=false;
    },

    resetForm(){
      this.additionalQuantity=null; this.serialMode='continue'; this.notes=''; this.errors={};
      this.overrideDates=false;
      if(this.info){
        this.partialRef=this.info.suggested_ref;
        this.mfgDate=this.info.manufacture_date ?? '';
        this.expDate=this.info.expiry_date ?? '';
      }
    },

    async submit(){
      this.saving=true; this.errors={};
      const fd = new FormData();
      fd.append('_token', '{{ csrf_token() }}');
      fd.append('product_id', this.productId);
      fd.append('batch_id', this.batchId);
      fd.append('additional_quantity', this.additionalQuantity ?? '');
      fd.append('serial_mode', this.serialMode);
      fd.append('override_dates', this.overrideDates ? '1' : '0');
      if(this.overrideDates){
        fd.append('manufacture_date', this.mfgDate ?? '');
        fd.append('expiry_date', this.expDate ?? '');
      }
      fd.append('notes', this.notes ?? '');
      try {
        const res = await fetch('{{ route('partial-batches.store') }}', {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, body:fd});
        const data = await res.json();
        if(data.success){ window.location.href = data.redirect; }
        else { this.errors = data.errors ?? {}; this.saving=false; }
      } catch(e){ alert('Server error. Please try again.'); this.saving=false; }
    },
  };
}
</script>
@endpush
