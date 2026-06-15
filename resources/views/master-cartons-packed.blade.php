@extends('layouts.app')
@section('title', 'Create Packed Cartons')

@push('styles')
<style>
  [x-cloak]{display:none!important}
  .section-label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d;margin-bottom:6px}
  .pl-row{border:1px solid #e9ecef;border-radius:12px;padding:12px;margin-bottom:10px;position:relative}
  .pl-row .rownum{position:absolute;top:-9px;left:10px;background:#0d6efd;color:#fff;font-size:11px;font-weight:700;border-radius:10px;padding:1px 8px}
  .calc-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(13,110,253,.07);border:1px solid #cfe2ff;border-radius:20px;padding:3px 10px;font-size:12px}
</style>
@endpush

@section('content')
<div x-data="packedForm()">

  <div class="page-header">
    <div>
      <h1>Create Packed Cartons</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / <a href="{{ route('master-cartons') }}">Master Carton Mgmt</a> / Create Packed Cartons</div>
    </div>
    <a href="{{ route('master-cartons') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to cartons</a>
  </div>

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
            <div class="col-md-3">
              <label class="form-label">Serials <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" x-model="line.serials" placeholder="1-50 or 1,3,6,8">
              <div class="text-muted-sm mt-1" x-show="line.batchId && line.range" x-cloak>Available: <strong x-text="line.range"></strong></div>
            </div>
            <div class="col-md-2">
              <label class="form-label">Capacity / carton</label>
              <input type="number" min="1" class="form-control form-control-sm" x-model.number="line.capacity" placeholder="all in one">
            </div>
            <div class="col-md-1">
              <label class="form-label">&nbsp;</label>
              <div class="calc-pill w-100 justify-content-center" :title="serialCount(line)+' serials → '+cartonCount(line)+' carton(s)'">
                <i class="bi bi-box-seam"></i><strong x-text="cartonCount(line)"></strong>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Label (optional)</label>
              <input type="text" class="form-control form-control-sm" x-model="line.label" placeholder="e.g. Zone A">
            </div>
            <div class="col-md-9 text-md-end">
              <span class="text-muted-sm" x-show="serialCount(line)>0"><span x-text="serialCount(line).toLocaleString()"></span> serial(s) → <strong x-text="cartonCount(line)"></strong> carton(s) of up to <span x-text="(line.capacity||serialCount(line))"></span></span>
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
</div>
@endsection

@push('scripts')
<script>
function packedForm(){
  return {
    saving:false, errors:{},
    lines:[],
    init(){ this.addLine(); },
    blank(){ return {productId:'', batchId:'', batches:[], loadingB:false, serials:'', capacity:'', label:'', range:''}; },
    addLine(){ this.lines.push(this.blank()); },
    removeLine(i){ this.lines.splice(i,1); if(!this.lines.length) this.addLine(); },
    _batches(pid){ return fetch(`{{ url('partial-batches/products') }}/${pid}/batches`,{headers:{'Accept':'application/json'}}).then(r=>r.json()); },
    async onProduct(i){ const l=this.lines[i]; l.batchId=''; l.batches=[]; l.range=''; if(!l.productId) return; l.loadingB=true; l.batches=await this._batches(l.productId); l.loadingB=false; },
    async onBatch(i){ const l=this.lines[i]; l.range=''; if(!l.batchId) return;
      try{ const d=await fetch(`{{ url('master-cartons/batches') }}/${l.batchId}/pack-info`,{headers:{'Accept':'application/json'}}).then(r=>r.json());
        if(d.max_serial) l.range=d.min_serial+'–'+d.max_serial+' (next free '+d.next_serial+')'; }catch(e){}
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
    serialCount(line){ return this.parse(line.serials).length; },
    cartonCount(line){ const n=this.serialCount(line); if(!n) return 0; const cap=line.capacity>0?line.capacity:n; return Math.ceil(n/cap); },
    get totalSerials(){ return this.lines.reduce((s,l)=>s+this.serialCount(l),0); },
    get totalCartons(){ return this.lines.reduce((s,l)=>s+this.cartonCount(l),0); },
    get canSubmit(){ return this.lines.every(l=>l.productId && l.batchId && this.serialCount(l)>0); },
    async submit(){
      this.saving=true; this.errors={};
      const fd=new FormData(); fd.append('_token','{{ csrf_token() }}');
      this.lines.forEach((l,i)=>{
        fd.append(`lines[${i}][product_id]`, l.productId);
        fd.append(`lines[${i}][batch_id]`, l.batchId);
        fd.append(`lines[${i}][serials]`, l.serials);
        fd.append(`lines[${i}][capacity]`, l.capacity||'');
        fd.append(`lines[${i}][label]`, l.label||'');
      });
      try{ const res=await fetch('{{ route('master-cartons.store-packed') }}',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json(); if(d.success){ window.location.href=d.redirect; } else { this.errors=d.errors??{}; this.saving=false; window.scrollTo({top:0,behavior:'smooth'}); }
      }catch(e){ alert('Server error.'); this.saving=false; }
    },
  };
}
</script>
@endpush
