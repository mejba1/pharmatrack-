@extends('layouts.app')
@section('title', 'Sales Orders')

@section('content')
<div x-data="soPage(@js($sos), @js($acknowledgedPos), @js($batches))">

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div>
      <h1>Sales Orders</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Orders / Sales Orders</div>
    </div>
    <div class="d-flex gap-2">
      @if(auth()->user()->canCreateSalesOrder() && auth()->user()->can('orders.create'))<button class="btn btn-primary btn-sm" @click="openCreate()"><i class="bi bi-plus-lg me-1"></i>Create SO</button>@endif
    </div>
  </div>

  <div class="info-box info mb-4">
    <i class="bi bi-diagram-2 text-primary mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px">
      <strong>Order Document Flow:</strong>
      <a href="{{ route('orders.po') }}" class="text-secondary text-decoration-none">PO</a> →
      <span class="text-primary fw-semibold">SO</span> →
      <a href="{{ route('orders.pi') }}" class="text-secondary text-decoration-none">PI</a> →
      <a href="{{ route('orders.ci') }}" class="text-secondary text-decoration-none">CI</a> → Shipment.
      A Sales Order allocates specific unit serials and can only be raised from an acknowledged PO.
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-bag-check"></i></div><div><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Total SOs</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value">{{ $stats['draft'] }}</div><div class="stat-label">Draft</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">{{ $stats['confirmed'] }}</div><div class="stat-label">Confirmed</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-value">{{ $stats['pi_issued'] }}</div><div class="stat-label">PI Issued</div></div></div></div>
  </div>

  <div class="card mb-3"><div class="card-body py-2">
    <div class="row g-2 align-items-center">
      <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search SO number, customer..." x-model="search"></div></div>
      <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Draft</option><option>Confirmed</option><option>PI Issued</option><option>Cancelled</option></select></div>
      <div class="col-6 col-md-2 d-flex gap-1"><button class="btn btn-outline-secondary btn-sm" @click="search='';filterStatus=''"><i class="bi bi-x-lg me-1"></i>Clear</button></div>
    </div>
  </div></div>

  <div class="card table-card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>SO Number</th><th>Linked PO</th><th>Customer</th><th>Products</th><th>SO Date</th><th>Total</th><th>Status</th><th>Linked PI</th><th>Actions</th></tr></thead>
      <tbody>
        <template x-for="so in filtered" :key="so.pid">
          <tr>
            <td><span class="text-primary fw-semibold cursor-pointer" style="font-size:12px" @click="viewSO(so)" x-text="so.id"></span></td>
            <td><a href="{{ route('orders.po') }}" class="text-secondary text-decoration-none" style="font-size:12px" x-text="so.linkedPo"></a></td>
            <td><div class="fw-semibold" style="font-size:13px" x-text="so.customer"></div><div class="text-muted-sm" x-text="so.country"></div></td>
            <td style="font-size:13px" x-text="so.products"></td>
            <td style="font-size:13px" x-text="so.soDate"></td>
            <td class="fw-semibold" style="font-size:13px" x-text="so.value"></td>
            <td><span class="badge-status" :class="'badge-' + so.statusClass" x-text="so.status"></span></td>
            <td><span x-show="so.linkedPi" class="text-primary" style="font-size:12px" x-text="so.linkedPi"></span><span x-show="!so.linkedPi" class="text-muted-sm">—</span></td>
            <td><div class="d-flex gap-1">
              <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewSO(so)"><i class="bi bi-eye"></i></button>
              <a :href="pdfUrl(so)" class="btn btn-outline-danger btn-sm btn-icon" title="PDF"><i class="bi bi-file-pdf"></i></a>
            </div></td>
          </tr>
        </template>
        <tr x-show="!filtered.length"><td colspan="9" class="text-center text-muted py-4">No sales orders yet. Click <strong>Create SO</strong> (needs an acknowledged PO).</td></tr>
      </tbody>
    </table>
  </div></div></div>

  @include('orders._so-modals')
</div>
@endsection

@push('scripts')
<script>
function soPage(sos, ackPos, batches){
  return {
    sos: sos || [], ackPos: ackPos || [], batches: batches || [],
    search:'', filterStatus:'', showViewModal:false, showAddModal:false, selectedSO:null, viewTab:'details',
    soTpl: '{{ url('orders/sales-orders') }}/__ID__',
    form: { purchase_order_id:'', so_date:'{{ now()->format('Y-m-d') }}', incoterms:'', payment_terms:'', estimated_delivery_date:'', status:'confirmed', remarks:'', lines:[] },

    init(){
      const q = new URLSearchParams(location.search);
      // Deep-link from a PO: /sales-orders?po={id} opens the create modal preselected.
      const po = q.get('po');
      if (po && this.ackPos.some(p => String(p.id) === String(po))) {
        this.openCreate();
        this.form.purchase_order_id = po;
        this.onPickPo();
        return;
      }
      // Drill-down: /sales-orders?view={id} opens that SO's detail modal.
      const view = q.get('view');
      if (view) { const so = this.sos.find(s => String(s.pid) === String(view)); if (so) this.viewSO(so); }
    },

    get filtered(){
      return this.sos.filter(s => {
        const q = this.search.toLowerCase();
        return (!q || s.id.toLowerCase().includes(q) || (s.customer||'').toLowerCase().includes(q)) && (!this.filterStatus || s.status === this.filterStatus);
      });
    },
    pdfUrl(so){ return this.soTpl.replace('__ID__', so.pid) + '/pdf'; },
    statusUrl(){ return this.soTpl.replace('__ID__', this.selectedSO?.pid) + '/status'; },
    docUrl(){ return this.soTpl.replace('__ID__', this.selectedSO?.pid) + '/documents'; },
    viewSO(so){ this.selectedSO = so; this.viewTab='details'; this.showViewModal=true; },

    openCreate(){
      this.form = { purchase_order_id:'', so_date:'{{ now()->format('Y-m-d') }}', incoterms:'', payment_terms:'', estimated_delivery_date:'', status:'confirmed', remarks:'', lines:[] };
      this.showAddModal = true;
    },
    get chosenPo(){ return this.ackPos.find(p => String(p.id) === String(this.form.purchase_order_id)); },
    onPickPo(){
      const po = this.chosenPo;
      this.form.incoterms = po?.incoterms || '';
      this.form.payment_terms = po?.payment || '';
      this.form.lines = (po?.lines || []).map(l => ({
        product_id: l.product_id, product: l.product, prn: l.prn,
        batch_id: l.batch_id || '', quantity: l.quantity, unit_price: l.unit_price,
        mode: 'range', start: 1, end: l.quantity, serials: '',
      }));
    },
    batchesFor(pid){ return pid ? this.batches.filter(b => String(b.product_id)===String(pid)) : []; },
    serialStr(line){
      if(line.mode === 'range'){ return (line.start>0 && line.end>=line.start) ? (line.start + '-' + line.end) : ''; }
      return (line.serials || '').trim();
    },
    parseCount(line){
      const s = this.serialStr(line); if(!/^[\d\s,\-]+$/.test(s)) return 0;
      const out = new Set();
      s.split(/[\s,]+/).filter(Boolean).forEach(t => { const m=t.match(/^(\d+)-(\d+)$/); if(m){let a=+m[1],b=+m[2]; if(b<a){const z=a;a=b;b=z;} for(let x=a;x<=b&&out.size<50000;x++) out.add(x);} else if(/^\d+$/.test(t)) out.add(+t); });
      return out.size;
    },
    get grandTotal(){ return this.form.lines.reduce((s,l)=> s + (parseFloat(l.unit_price||0)*parseInt(l.quantity||0)),0).toFixed(2); },
    submitForm(status){ this.form.status = status; this.$nextTick(()=> this.$refs.soForm.submit()); },
  };
}
</script>
@endpush
