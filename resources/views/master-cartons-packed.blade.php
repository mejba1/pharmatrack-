@extends('layouts.app')
@section('title', 'Create Packed Cartons')

@push('styles')
<style>
  [x-cloak]{display:none!important}
  .section-label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d;margin-bottom:6px}
  .pl-row{border:1px solid #e9ecef;border-radius:12px;padding:12px;margin-bottom:10px;position:relative}
  .pl-row .rownum{position:absolute;top:-9px;left:10px;background:#0d6efd;color:#fff;font-size:11px;font-weight:700;border-radius:10px;padding:1px 8px}
  .calc-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(13,110,253,.07);border:1px solid #cfe2ff;border-radius:20px;padding:3px 10px;font-size:12px}
  .pf-toast{position:fixed;top:20px;right:20px;z-index:1080;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 6px 24px rgba(0,0,0,.15);background:#fff;border-left:4px solid #0d6efd;color:#0d6efd}
  .pf-toast-warning{border-left-color:#fd7e14;color:#a65a00}
  .pf-toast-danger{border-left-color:#dc3545;color:#b02a37}
  .pf-toast-success{border-left-color:#198754;color:#0f5132}
</style>
@endpush

@section('content')
<div x-data="packedForm()">

  {{-- Toast / inline notifier --}}
  <div x-cloak x-show="toast.show" x-transition class="pf-toast" :class="'pf-toast-'+toast.type">
    <i class="bi" :class="toast.type==='warning'?'bi-exclamation-triangle-fill':(toast.type==='danger'?'bi-x-circle-fill':'bi-check-circle-fill')"></i>
    <span x-text="toast.msg"></span>
  </div>

  <div class="page-header">
    <div>
      <h1>Create Packed Cartons</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / <a href="{{ route('master-cartons') }}">Master Carton Mgmt</a> / Create Packed Cartons</div>
    </div>
    <a href="{{ route('master-cartons') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to cartons</a>
  </div>

  {{-- Created cartons result --}}
  <template x-if="result">
    <div class="card mb-3 border-success">
      <div class="card-header bg-transparent d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold text-success"><i class="bi bi-check-circle-fill me-1"></i><span x-text="result.message"></span></span>
        <div class="ms-auto d-flex gap-2">
          <a :href="labelsUrl('print')+'&print=1'" target="_blank" class="btn btn-success btn-sm"><i class="bi bi-printer-fill me-1"></i>Print Now</a>
          <a :href="labelsUrl('print')" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer me-1"></i>Preview Labels</a>
          <a :href="labelsUrl('pdf')" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
          <button class="btn btn-outline-secondary btn-sm" @click="createMore()"><i class="bi bi-plus-lg me-1"></i>Create more</button>
        </div>
      </div>
      <div class="px-3 pb-2"><span class="text-muted-sm"><i class="bi bi-info-circle me-1"></i><a :href="labelsUrl('print')+'&print=1'" target="_blank">Click here to print directly</a> — the label sheet opens and the print dialog appears automatically.</span></div>
      <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th style="width:48px">#</th><th>MC No</th><th>Product</th><th>Batch</th><th class="text-end">QTY</th><th class="text-end" style="width:120px">Download</th></tr></thead>
          <tbody>
            <template x-for="(c,i) in result.cartons" :key="c.id">
              <tr>
                <td x-text="i+1"></td>
                <td class="font-monospace fw-semibold" style="font-size:12px" x-text="c.carton_number"></td>
                <td style="font-size:13px" x-text="c.product"></td>
                <td class="font-monospace" style="font-size:12px" x-text="c.batch"></td>
                <td class="text-end fw-semibold" x-text="c.qty"></td>
                <td class="text-end">
                  <a :href="`{{ url('master-cartons') }}/${c.id}/serials-pdf`" class="btn btn-outline-danger btn-sm btn-icon" title="Download this carton's serials PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                </td>
              </tr>
            </template>
          </tbody>
          <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end" x-text="result.cartons.reduce((s,c)=>s+c.qty,0).toLocaleString()"></th><th></th></tr></tfoot>
        </table>
      </div></div>
    </div>
  </template>

  <div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="font-size:13px">
    <i class="bi bi-info-circle"></i>
    <span>Add a row per product/batch and the serials to pack. Each row creates packed master carton(s) — set a <strong>capacity</strong> to split a big serial set into several cartons of that size.</span>
  </div>

  <form @submit.prevent="submit()">
    <div class="card mb-3"><div class="card-body">

      <template x-for="(line, i) in lines" :key="i">
        <div class="pl-row">
          <span class="rownum" x-text="'Row '+(i+1)"></span>
          <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 btn-icon" @click="removeLine(i)" x-show="lines.length>1" title="Remove row"><i class="bi bi-x-lg"></i></button>
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Product <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm" x-model="line.productId" @change="onProduct(i)">
                <option value="">Select product…</option>
                @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Batch <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm" x-model="line.batchId" @change="onBatch(i)" :disabled="!line.productId || line.loadingB">
                <option value="" x-text="!line.productId ? 'Product first…' : (line.batches.length ? 'Select batch…' : 'No batches')"></option>
                <template x-for="b in line.batches" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Capacity / carton</label>
              <input type="number" min="1" class="form-control form-control-sm" x-model.number="line.capacity" placeholder="all in one">
            </div>
            <div class="col-md-4">
              <label class="form-label">Label (optional)</label>
              <input type="text" class="form-control form-control-sm" x-model="line.label" placeholder="e.g. Zone A">
            </div>

            {{-- Serials: Range or Specific --}}
            <div class="col-12">
              <div class="d-flex align-items-center gap-2 mb-1">
                <label class="form-label mb-0">Serials <span class="text-danger">*</span></label>
                <div class="btn-group btn-group-sm" role="group">
                  <button type="button" class="btn" :class="line.mode==='range' ? 'btn-info text-white' : 'btn-outline-secondary'" @click="line.mode='range'">Range</button>
                  <button type="button" class="btn" :class="line.mode==='specific' ? 'btn-info text-white' : 'btn-outline-secondary'" @click="line.mode='specific'">Specific</button>
                </div>
                <span class="text-muted-sm ms-1" x-show="line.batchId && line.range && !line.fullyPacked" x-cloak>· Available <strong x-text="line.range"></strong></span>
                <span class="badge bg-danger-subtle text-danger ms-1" x-show="line.batchId && line.fullyPacked" x-cloak><i class="bi bi-exclamation-triangle me-1"></i>Fully packed — no units available</span>
              </div>
              <div class="row g-2 align-items-center">
                {{-- range --}}
                <div class="col-6 col-md-2" x-show="line.mode==='range'">
                  <input type="number" min="1" class="form-control form-control-sm" x-model.number="line.start" placeholder="Start e.g. 1">
                </div>
                <div class="col-6 col-md-2" x-show="line.mode==='range'">
                  <input type="number" min="1" class="form-control form-control-sm" x-model.number="line.end" placeholder="End e.g. 50">
                </div>
                {{-- specific --}}
                <div class="col-md-4" x-show="line.mode==='specific'">
                  <input type="text" class="form-control form-control-sm" x-model="line.serials" placeholder="1,3,6,8 or 1-5,10-12">
                </div>
                <div class="col-md text-md-end">
                  <span class="calc-pill" x-show="serialCount(line)>0">
                    <i class="bi bi-box-seam"></i>
                    <span><strong x-text="serialCount(line).toLocaleString()"></strong> serial(s) → <strong x-text="cartonCount(line)"></strong> carton(s)</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <button type="button" class="btn btn-outline-primary btn-sm" @click="addLine()"><i class="bi bi-plus-lg me-1"></i>Add another row</button>
    </div></div>

    <template x-if="Object.keys(errors).length">
      <div class="alert alert-danger"><ul class="mb-0 ps-3" style="font-size:13px"><template x-for="(m,f) in errors" :key="f"><template x-for="x in m" :key="x"><li x-text="x"></li></template></template></ul></div>
    </template>

    <div class="card"><div class="card-body d-flex flex-wrap align-items-center gap-3">
      <div class="calc-pill"><i class="bi bi-123"></i>Total serials <strong x-text="totalSerials.toLocaleString()"></strong></div>
      <div class="calc-pill"><i class="bi bi-box-seam"></i>Total cartons <strong x-text="totalCartons.toLocaleString()"></strong></div>
      <button type="submit" class="btn btn-primary ms-auto" :disabled="saving || !canSubmit">
        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
        <span x-text="saving ? 'Creating…' : ('Create '+totalCartons+' Packed Carton(s)')"></span>
      </button>
    </div></div>
  </form>

  {{-- Recently created master cartons (last 100) --}}
  <div class="card mt-3">
    <div class="card-header bg-transparent d-flex flex-wrap align-items-center gap-2">
      <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i>Recent Master Cartons <span class="text-muted-sm fw-normal">(last 100)</span></span>
      <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
        <span class="text-muted-sm">Select:</span>
        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" @click="selectRecent('all')">All</button>
        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-success" @click="selectRecent('packed')">Packed</button>
        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-secondary" @click="selectRecent('unpacked')">Unpacked</button>
        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" @click="selectRecent('none')">None</button>
        <span class="text-muted">|</span>
        <label class="text-muted-sm mb-0">Show</label>
        <select class="form-select form-select-sm" style="width:auto" x-model.number="pageSize">
          <option :value="10">10</option><option :value="20">20</option><option :value="30">30</option>
          <option :value="50">50</option><option :value="100">100</option>
        </select>
      </div>
    </div>

    {{-- Bulk download bar --}}
    <div class="px-3 py-2 border-bottom d-flex flex-wrap align-items-center gap-2" style="background:linear-gradient(180deg,rgba(13,110,253,.07),rgba(13,110,253,.02))">
      <span class="fw-semibold" :class="selRecent.length ? '' : 'text-muted'"><i class="bi bi-check2-square me-1 text-primary"></i><span x-text="selRecent.length"></span> selected</span>
      <button class="btn btn-outline-primary btn-sm" @click="recentLabels('print')"><i class="bi bi-printer me-1"></i>Print QR Labels</button>
      <button class="btn btn-danger btn-sm" @click="recentLabels('pdf')"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</button>
      <button class="btn btn-link btn-sm text-muted ms-auto text-decoration-none" @click="selectRecent('none')" x-show="selRecent.length" x-cloak>Clear</button>
    </div>

    <div class="card-body p-0"><div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr>
          <th style="width:36px" class="text-center"><input type="checkbox" class="form-check-input" :checked="allPagedChecked" @change="togglePaged($event)"></th>
          <th style="cursor:pointer" @click="sortBy('carton_number')">MC No <i class="bi" :class="caret('carton_number')"></i></th>
          <th>Product</th><th>Batch</th>
          <th class="text-end" style="cursor:pointer" @click="sortBy('qty')">QTY <i class="bi" :class="caret('qty')"></i></th>
          <th class="text-end" style="cursor:pointer" @click="sortBy('capacity')">Capacity <i class="bi" :class="caret('capacity')"></i></th>
          <th>Status</th>
          <th style="cursor:pointer" @click="sortBy('id')">Created <i class="bi" :class="caret('id')"></i></th>
          <th class="text-end" style="width:100px">Download</th>
        </tr></thead>
        <tbody>
          <template x-for="c in pagedRecent" :key="c.id">
            <tr :class="selRecent.includes(c.id) ? 'table-active' : ''">
              <td class="text-center"><input type="checkbox" class="form-check-input" :checked="selRecent.includes(c.id)" @change="toggleRow(c.id)"></td>
              <td class="font-monospace fw-semibold" style="font-size:12px" x-text="c.carton_number"></td>
              <td style="font-size:13px" x-text="c.product"></td>
              <td class="font-monospace" style="font-size:12px" x-text="c.batch"></td>
              <td class="text-end fw-semibold" x-text="c.qty.toLocaleString()"></td>
              <td class="text-end text-muted" x-text="c.capacity.toLocaleString()"></td>
              <td><span class="badge-status" :class="c.status_badge" x-text="c.status"></span></td>
              <td class="text-muted" style="font-size:12px" x-text="c.created"></td>
              <td class="text-end">
                <a x-show="c.packed" :href="`{{ url('master-cartons') }}/${c.id}/serials-pdf`" class="btn btn-outline-danger btn-sm btn-icon" title="Serials PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                <span x-show="!c.packed" class="text-muted-sm">—</span>
              </td>
            </tr>
          </template>
          <tr x-show="!recent.length"><td colspan="9" class="text-center text-muted py-4">No master cartons yet.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="text-muted-sm px-3 py-2 border-top" x-show="recent.length">Showing <strong x-text="Math.min(pageSize, recent.length)"></strong> of <strong x-text="recent.length"></strong> recent cartons.</div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function packedForm(){
  return {
    saving:false, errors:{}, result:null,
    lines:[],
    // lightweight toast notifier
    toast:{show:false,msg:'',type:'info',_t:null},
    notify(msg,type='info'){ this.toast.msg=msg; this.toast.type=type; this.toast.show=true; clearTimeout(this.toast._t); this.toast._t=setTimeout(()=>this.toast.show=false,3200); },
    // recent cartons table
    recent: @json($recent),
    pageSize:10, sortKey:'id', sortDir:'desc',
    sortBy(k){ if(this.sortKey===k){ this.sortDir = this.sortDir==='asc'?'desc':'asc'; } else { this.sortKey=k; this.sortDir = k==='carton_number'?'asc':'desc'; } },
    caret(k){ if(this.sortKey!==k) return 'bi-chevron-expand text-muted'; return this.sortDir==='asc'?'bi-chevron-up':'bi-chevron-down'; },
    get sortedRecent(){
      const k=this.sortKey, dir=this.sortDir==='asc'?1:-1;
      return [...this.recent].sort((a,b)=>{
        if(k==='carton_number'){ return a.carton_number.localeCompare(b.carton_number)*dir; }
        return ((a[k]||0)-(b[k]||0))*dir;
      });
    },
    get pagedRecent(){ return this.sortedRecent.slice(0, this.pageSize); },
    // recent selection + bulk QR download
    selRecent:[],
    get allPagedChecked(){ const ids=this.pagedRecent.map(c=>c.id); return ids.length>0 && ids.every(id=>this.selRecent.includes(id)); },
    togglePaged(e){ const ids=this.pagedRecent.map(c=>c.id);
      this.selRecent = e.target.checked ? [...new Set([...this.selRecent,...ids])] : this.selRecent.filter(id=>!ids.includes(id)); },
    toggleRow(id){ const i=this.selRecent.indexOf(id); if(i<0) this.selRecent.push(id); else this.selRecent.splice(i,1); },
    selectRecent(kind){
      if(kind==='all') this.selRecent=this.recent.map(c=>c.id);
      else if(kind==='packed') this.selRecent=this.recent.filter(c=>c.packed).map(c=>c.id);
      else if(kind==='unpacked') this.selRecent=this.recent.filter(c=>!c.packed).map(c=>c.id);
      else this.selRecent=[];
    },
    recentLabels(action){
      if(!this.selRecent.length){ this.notify('Select at least one carton first.','warning'); return; }
      const base = action==='pdf' ? '{{ route('master-cartons.labels-pdf') }}' : '{{ route('master-cartons.labels') }}';
      const url = base + '?ids=' + this.selRecent.join(',');
      // Recent table = browse/reprint previous cartons → open the sheet (no auto-print).
      if(action==='print'){ window.open(url,'_blank'); } else { window.location.href=url; }
    },
    init(){ this.addLine(); },
    blank(){ return {productId:'', batchId:'', batches:[], loadingB:false, mode:'range', start:'', end:'', serials:'', capacity:'', label:'', range:'', available:0, fullyPacked:false}; },
    specStr(line){
      if(line.mode==='range'){ return (line.start>0 && line.end>=line.start) ? (line.start+'-'+line.end) : ''; }
      return (line.serials||'').trim();
    },
    addLine(){ this.lines.push(this.blank()); },
    removeLine(i){ this.lines.splice(i,1); if(!this.lines.length) this.addLine(); },
    _batches(pid){ return fetch(`{{ url('partial-batches/products') }}/${pid}/batches`,{headers:{'Accept':'application/json'}}).then(r=>r.json()); },
    async onProduct(i){ const l=this.lines[i]; l.batchId=''; l.batches=[]; l.range=''; if(!l.productId) return; l.loadingB=true; l.batches=await this._batches(l.productId); l.loadingB=false; },
    async onBatch(i){ const l=this.lines[i]; l.range=''; l.start=''; l.end=''; l.available=0; l.fullyPacked=false; if(!l.batchId) return;
      try{ const d=await fetch(`{{ url('master-cartons/batches') }}/${l.batchId}/pack-info`,{headers:{'Accept':'application/json'}}).then(r=>r.json());
        l.available = d.available||0;
        // No generated units left, or every serial already packed → nothing to pack.
        if(!d.max_serial || l.available===0 || d.next_serial>d.max_serial){
          l.fullyPacked = true;
          this.notify('This batch is fully packed — no units available to pack.','warning');
          return;
        }
        // Show only the *available* range (from the next free serial onward),
        // not the full serial span which would include already-packed units.
        l.range = d.next_serial+'–'+d.max_serial+' · '+l.available.toLocaleString()+' unit(s) free';
        // Auto-fill Start/End like the pack modal: from the next free serial to
        // the end of the batch. Capacity then splits it into multiple cartons.
        l.start = d.next_serial;
        l.end   = d.max_serial;
      }catch(e){}
    },
    parse(serials){
      const q=(serials||'').trim(); if(!/^[\d\s,\-]+$/.test(q)) return [];
      const out=[];
      q.split(/[\s,]+/).filter(Boolean).forEach(t=>{
        const m=t.match(/^(\d+)-(\d+)$/);
        if(m){ let a=+m[1],b=+m[2]; if(b<a){const z=a;a=b;b=z;} for(let x=a;x<=b&&out.length<5000;x++) out.push(x); }
        else if(/^\d+$/.test(t)) out.push(+t);
      });
      return [...new Set(out)];
    },
    serialCount(line){ return this.parse(this.specStr(line)).length; },
    cartonCount(line){ const n=this.serialCount(line); if(!n) return 0; const cap=line.capacity>0?line.capacity:n; return Math.ceil(n/cap); },
    get totalSerials(){ return this.lines.reduce((s,l)=>s+this.serialCount(l),0); },
    get totalCartons(){ return this.lines.reduce((s,l)=>s+this.cartonCount(l),0); },
    get canSubmit(){ return this.lines.length>0 && this.lines.every(l=>l.productId && l.batchId && !l.fullyPacked && this.serialCount(l)>0); },
    async submit(){
      if(this.lines.some(l=>l.fullyPacked)){ this.notify('Remove or change the fully-packed batch row before creating cartons.','warning'); return; }
      if(!this.canSubmit){ this.notify('Complete every row (product, batch, serials) first.','warning'); return; }
      this.saving=true; this.errors={};
      const fd=new FormData(); fd.append('_token','{{ csrf_token() }}');
      this.lines.forEach((l,i)=>{
        fd.append(`lines[${i}][product_id]`, l.productId);
        fd.append(`lines[${i}][batch_id]`, l.batchId);
        fd.append(`lines[${i}][serials]`, this.specStr(l));
        fd.append(`lines[${i}][capacity]`, l.capacity||'');
        fd.append(`lines[${i}][label]`, l.label||'');
      });
      try{ const res=await fetch('{{ route('master-cartons.store-packed') }}',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json();
        if(d.success){ this.result={cartons:d.cartons||[], ids:d.ids||[], message:d.message}; this.lines=[this.blank()]; this.errors={}; window.scrollTo({top:0,behavior:'smooth'}); }
        else { this.errors=d.errors??{}; window.scrollTo({top:0,behavior:'smooth'}); }
      }catch(e){ this.notify('Server error — please try again.','danger'); }
      this.saving=false;
    },
    labelsUrl(action){
      const base = action==='pdf' ? '{{ route('master-cartons.labels-pdf') }}' : '{{ route('master-cartons.labels') }}';
      return base + '?ids=' + (this.result?.ids||[]).join(',');
    },
    createMore(){ this.result=null; },
  };
}
</script>
@endpush
