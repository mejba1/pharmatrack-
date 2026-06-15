@extends('layouts.app')
@section('title', 'Distribution Dashboard')

@push('styles')
<style>
  [x-cloak]{display:none!important}
  .section-label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d;padding-bottom:4px;border-bottom:1px solid #e9ecef;margin-bottom:8px}
  .trace-box{background:linear-gradient(135deg,#0d6efd,#0a58ca);border-radius:14px;padding:18px 20px;color:#fff}
  .trace-box input{border:none;border-radius:10px;padding:12px 14px}
  .hier-product{border:1px solid #e9ecef;border-radius:11px;margin-bottom:8px;overflow:hidden}
  .hier-head{display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;background:#f8fafc}
  .hier-head:hover{background:#f1f5fb}
  .chain{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#6c757d}
  .chain .sep{color:#ced4da}
</style>
@endpush

@section('content')
<div x-data="distributionPage()">

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1>Distribution Dashboard</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Distribution &amp; Traceability</div>
    </div>
  </div>

  {{-- Universal traceability search --}}
  <div class="trace-box mb-3">
    <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-search"></i><strong>Universal Traceability Search</strong></div>
    <div class="row g-2 align-items-center">
      <div class="col-12 col-lg">
        <input type="text" class="form-control" x-model="q" @keydown.enter="search()"
               placeholder="Serial(s): 12345 · 1,2,3 · 1-50  ·  or Carton / Shipment / Batch / QR">
      </div>
      <div class="col-6 col-lg-auto">
        <select class="form-select" x-model="productId" @change="onProduct()">
          <option value="">Any product</option>
          @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-6 col-lg-auto">
        <select class="form-select" x-model="batchId" :disabled="!productId || loadingB">
          <option value="" x-text="!productId ? 'Any batch' : (batches.length ? 'Any batch' : 'No batches')"></option>
          <template x-for="b in batches" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
        </select>
      </div>
      <div class="col-12 col-lg-auto"><button class="btn btn-light w-100" @click="search()" :disabled="searching"><span x-show="searching" class="spinner-border spinner-border-sm me-1"></span>Trace</button></div>
    </div>
    <div class="small mt-2 opacity-75">Find the full chain — product → batch → master carton → shipment → status. Scope a product/batch to disambiguate a serial that exists across several batches; enter multiple serials or a range (e.g. <code class="text-white">1-50</code>).</div>
  </div>

  {{-- Search results --}}
  <div class="card mb-3" x-show="searched" x-cloak><div class="card-body p-0">
    <div class="px-3 py-2 border-bottom fw-semibold"><i class="bi bi-diagram-3 me-1"></i>Trace Results <span class="text-muted" x-text="'· '+results.length"></span></div>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Match</th><th>Product</th><th>Batch</th><th>Carton</th><th>Shipment</th><th>Serial</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <template x-for="(r,i) in results" :key="i">
            <tr>
              <td><span class="badge text-bg-light" x-text="r.kind"></span></td>
              <td style="font-size:13px" x-text="r.product"></td>
              <td class="font-monospace" style="font-size:12px" x-text="r.batch"></td>
              <td class="font-monospace" style="font-size:12px" x-text="r.carton"></td>
              <td class="font-monospace" style="font-size:12px" x-text="r.shipment"></td>
              <td class="font-monospace" style="font-size:12px" x-text="r.serial"></td>
              <td><span class="badge-status" :class="r.status_badge" x-text="r.status"></span></td>
              <td><a :href="r.link" target="_blank" class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-box-arrow-up-right"></i></a></td>
            </tr>
          </template>
          <tr x-show="!results.length"><td colspan="8" class="text-center text-muted py-4">No matches found.</td></tr>
        </tbody>
      </table>
    </div>
  </div></div>

  {{-- Summary cards --}}
  <div class="row g-2 mb-3">
    <div class="col-6 col-md"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-layers"></i></div><div><div class="stat-value">{{ number_format($stats['batches']) }}</div><div class="stat-label">Batches</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-value">{{ number_format($stats['cartons']) }}</div><div class="stat-label">Master Cartons</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-purple"><div class="stat-icon"><i class="bi bi-truck"></i></div><div><div class="stat-value">{{ number_format($stats['shipments']) }}</div><div class="stat-label">Shipments</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div><div><div class="stat-value">{{ number_format($stats['in_transit']) }}</div><div class="stat-label">In Transit</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></div><div><div class="stat-value">{{ number_format($stats['received']) }}</div><div class="stat-label">Received</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-question-octagon"></i></div><div><div class="stat-value">{{ number_format($stats['missing']) }}</div><div class="stat-label">Missing</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div><div><div class="stat-value">{{ number_format($stats['damaged']) }}</div><div class="stat-label">Damaged</div></div></div></div>
  </div>

  <div class="row g-3">
    {{-- Hierarchy --}}
    <div class="col-lg-7">
      <div class="card h-100"><div class="card-header bg-transparent fw-semibold"><i class="bi bi-diagram-3 me-1"></i>Carton Hierarchy — Product → Batch → Carton → Shipment</div>
      <div class="card-body">
        @forelse($hierarchy as $i => $h)
        <div class="hier-product" x-data="{open:{{ $i===0 ? 'true':'false' }}}">
          <div class="hier-head" @click="open=!open">
            <i class="bi" :class="open?'bi-chevron-down':'bi-chevron-right'"></i>
            <i class="bi bi-capsule text-primary"></i>
            <span class="fw-semibold">{{ $h['product'] }}</span>
            <span class="ms-auto text-muted-sm">{{ $h['batches']->count() }} batches · {{ number_format($h['cartons']) }} cartons · {{ $h['shipments'] }} shipments</span>
          </div>
          <div x-show="open" x-cloak class="px-3 pb-2">
            <table class="table table-sm mb-0 align-middle">
              <thead><tr><th>Batch</th><th class="text-end">Cartons</th><th class="text-end">Units</th><th class="text-end">Shipments</th></tr></thead>
              <tbody>
                @foreach($h['batches'] as $b)
                <tr>
                  <td class="font-monospace" style="font-size:12px">{{ $b['brn'] }}</td>
                  <td class="text-end">{{ number_format($b['cartons']) }}</td>
                  <td class="text-end">{{ number_format($b['units']) }}</td>
                  <td class="text-end">{{ number_format($b['shipments']) }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        @empty
        <div class="text-center text-muted py-4"><i class="bi bi-diagram-3" style="font-size:28px;opacity:.2"></i><div class="mt-2">No carton distribution yet.</div></div>
        @endforelse
      </div></div>
    </div>

    {{-- Exception reports --}}
    <div class="col-lg-5">
      <div class="card mb-3"><div class="card-header bg-transparent">
        <div class="fw-semibold text-danger"><i class="bi bi-question-octagon me-1"></i>Missing / Short Cartons</div>
        <div class="text-muted-sm">Cartons a depot marked as <strong>never arrived</strong> when receiving a shipment (genuine shortage — not just in&nbsp;transit).</div>
      </div>
      <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead><tr><th>Carton</th><th>Product</th><th>Shipment</th><th>Marked</th></tr></thead>
          <tbody>
            @forelse($missingCartons as $c)
            <tr>
              <td class="font-monospace" style="font-size:12px">{{ $c->carton_number }}</td>
              <td style="font-size:12px">{{ $c->products_summary }}</td>
              <td class="font-monospace text-muted" style="font-size:11px">{{ $c->consignment?->consignment_number ?? '—' }}</td>
              <td class="text-muted" style="font-size:11px">{{ $c->updated_at?->format('M d') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted py-3"><i class="bi bi-check-circle text-success me-1"></i>No missing cartons — nothing reported short.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div></div></div>

      <div class="card"><div class="card-header bg-transparent fw-semibold text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Damaged Cartons</div>
      <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <tbody>
            @forelse($damagedCartons as $c)
            <tr>
              <td class="font-monospace" style="font-size:12px">{{ $c->carton_number }}</td>
              <td style="font-size:12px">{{ $c->products_summary }}</td>
              <td style="font-size:11px" class="text-muted">{{ \Illuminate\Support\Str::limit($c->condition_note, 28) ?: '—' }}</td>
              <td>@if($c->evidence_url)<a href="{{ $c->evidence_url }}" target="_blank"><i class="bi bi-image"></i></a>@endif</td>
            </tr>
            @empty
            <tr><td class="text-center text-muted py-3">No damaged cartons.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div></div></div>
    </div>
  </div>

  {{-- Recent shipments --}}
  <div class="card mt-3"><div class="card-header bg-transparent fw-semibold"><i class="bi bi-clock-history me-1"></i>Recent Shipments</div>
  <div class="card-body p-0"><div class="table-responsive">
    <table class="table table-sm mb-0 align-middle">
      <thead><tr><th>Shipment</th><th>Destination</th><th class="text-end">Cartons</th><th class="text-end">Units</th><th>Status</th><th>Created</th></tr></thead>
      <tbody>
        @forelse($recentShipments as $s)
        <tr>
          <td class="font-monospace" style="font-size:12px"><a href="{{ route('shipments', ['search'=>$s->consignment_number]) }}">{{ $s->consignment_number }}</a></td>
          <td style="font-size:12px">{{ $s->destination ?? '—' }}</td>
          <td class="text-end">{{ number_format($s->carton_count) }}</td>
          <td class="text-end">{{ number_format($s->total_units) }}</td>
          <td><span class="badge-status {{ $s->status_badge_class }}">{{ $s->status_label }}</span></td>
          <td style="font-size:12px" class="text-muted">{{ $s->created_at?->format('M d, Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-3">No shipments yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div></div></div>

</div>
@endsection

@push('scripts')
<script>
function distributionPage(){
  return {
    q:'', productId:'', batchId:'', batches:[], loadingB:false,
    results:[], searching:false, searched:false,
    async onProduct(){
      this.batchId=''; this.batches=[];
      if(!this.productId) return;
      this.loadingB=true;
      try{ this.batches=await fetch(`{{ url('partial-batches/products') }}/${this.productId}/batches`,{headers:{'Accept':'application/json'}}).then(r=>r.json()); }
      catch(e){ this.batches=[]; }
      this.loadingB=false;
    },
    async search(){
      const hasScope = this.productId || this.batchId;
      if(!this.q.trim() && !hasScope){ this.searched=false; this.results=[]; return; }
      this.searching=true;
      try{
        const p=new URLSearchParams();
        if(this.q.trim()) p.set('q', this.q.trim());
        if(this.productId) p.set('product_id', this.productId);
        if(this.batchId) p.set('batch_id', this.batchId);
        const r=await fetch(`{{ route('distribution.lookup') }}?`+p.toString(),{headers:{'Accept':'application/json'}});
        const d=await r.json(); this.results=d.results||[]; this.searched=true;
      }catch(e){ alert('Search failed.'); }
      this.searching=false;
    },
  };
}
</script>
@endpush
