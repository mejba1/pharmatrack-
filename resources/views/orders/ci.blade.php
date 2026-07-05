@extends('layouts.app')
@section('title', 'Commercial Invoices')

@section('content')
<div x-data="ciPage(@js($cis), @js($approvedPis))">

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div><h1>Commercial Invoices</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Orders / Commercial Invoices</div></div>
    <div class="d-flex gap-2">@can('invoices.create')<button class="btn btn-primary btn-sm" @click="openCreate()"><i class="bi bi-plus-lg me-1"></i>Raise CI</button>@endcan</div>
  </div>

  <div class="info-box info mb-4">
    <i class="bi bi-diagram-2 text-primary mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px"><strong>Order Document Flow:</strong>
      <a href="{{ route('orders.po') }}" class="text-secondary text-decoration-none">PO</a> →
      <a href="{{ route('orders.so') }}" class="text-secondary text-decoration-none">SO</a> →
      <a href="{{ route('orders.pi') }}" class="text-secondary text-decoration-none">PI</a> →
      <span class="text-primary fw-semibold">CI</span> → Shipment.
      One approved PI can be invoiced across <strong>multiple partial CIs</strong> for split shipments.
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div><div><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Total CIs</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value">{{ $stats['pending'] }}</div><div class="stat-label">Pending Approval</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">{{ $stats['approved'] }}</div><div class="stat-label">Approved</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-layers-half"></i></div><div><div class="stat-value">{{ $stats['partial'] }}</div><div class="stat-label">Partially Invoiced PIs</div></div></div></div>
  </div>

  <div class="card mb-3"><div class="card-body py-2">
    <div class="row g-2 align-items-center">
      <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search CI number, customer..." x-model="search"></div></div>
      <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Draft</option><option>Pending approval</option><option>Approved</option><option>Shipment created</option><option>Cancelled</option></select></div>
      <div class="col-6 col-md-2 d-flex gap-1"><button class="btn btn-outline-secondary btn-sm" @click="search='';filterStatus=''"><i class="bi bi-x-lg me-1"></i>Clear</button></div>
    </div>
  </div></div>

  <div class="card table-card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>CI Number</th><th>Linked PI</th><th>Customer</th><th>CI Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <template x-for="ci in filtered" :key="ci.pid">
          <tr>
            <td><span class="text-primary fw-semibold cursor-pointer" style="font-size:12px" @click="viewCI(ci)" x-text="ci.id"></span></td>
            <td><a href="{{ route('orders.pi') }}" class="text-secondary text-decoration-none" style="font-size:12px" x-text="ci.linkedPi"></a></td>
            <td><div class="fw-semibold" style="font-size:13px" x-text="ci.customer"></div><div class="text-muted-sm" x-text="ci.country"></div></td>
            <td style="font-size:13px" x-text="ci.ciDate"></td>
            <td class="fw-semibold" style="font-size:13px" x-text="ci.value"></td>
            <td><span class="badge-status" :class="'badge-' + ci.statusClass" x-text="ci.status"></span></td>
            <td><div class="d-flex gap-1">
              <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewCI(ci)"><i class="bi bi-eye"></i></button>
              <a :href="pdfUrl(ci)" class="btn btn-outline-danger btn-sm btn-icon" title="PDF"><i class="bi bi-file-pdf"></i></a>
            </div></td>
          </tr>
        </template>
        <tr x-show="!filtered.length"><td colspan="7" class="text-center text-muted py-4">No commercial invoices yet. Click <strong>Raise CI</strong> (needs an approved PI).</td></tr>
      </tbody>
    </table>
  </div></div></div>

  @include('orders._ci-modals')
</div>
@endsection

@push('scripts')
<script>
function ciPage(cis, approvedPis){
  return {
    cis: cis || [], approvedPis: approvedPis || [],
    search:'', filterStatus:'', showViewModal:false, showAddModal:false, selectedCI:null, viewTab:'details',
    ciTpl: '{{ url('orders/commercial-invoices') }}/__ID__',
    form: { proforma_invoice_id:'', ci_date:'{{ now()->format('Y-m-d') }}', hs_code:'', country_of_origin:'US', incoterms:'', port_of_loading:'', port_of_discharge:'', freight:'', insurance:'', status:'pending_approval', remarks:'', lines:[] },

    init(){
      // Deep-link from a PI: /commercial-invoices?pi={id} opens the create modal preselected.
      const pi = new URLSearchParams(location.search).get('pi');
      if (pi && this.approvedPis.some(p => String(p.id) === String(pi))) {
        this.openCreate();
        this.form.proforma_invoice_id = pi;
        this.onPickPi();
      }
    },

    get filtered(){
      return this.cis.filter(c => { const q=this.search.toLowerCase();
        return (!q || c.id.toLowerCase().includes(q) || (c.customer||'').toLowerCase().includes(q)) && (!this.filterStatus || c.status === this.filterStatus); });
    },
    pdfUrl(ci){ return this.ciTpl.replace('__ID__', ci.pid) + '/pdf'; },
    statusUrl(){ return this.ciTpl.replace('__ID__', this.selectedCI?.pid) + '/status'; },
    docUrl(){ return this.ciTpl.replace('__ID__', this.selectedCI?.pid) + '/documents'; },
    viewCI(ci){ this.selectedCI = ci; this.viewTab='details'; this.showViewModal=true; },

    openCreate(){ this.form = { proforma_invoice_id:'', ci_date:'{{ now()->format('Y-m-d') }}', hs_code:'', country_of_origin:'US', incoterms:'', port_of_loading:'', port_of_discharge:'', freight:'', insurance:'', status:'pending_approval', remarks:'', lines:[] }; this.showAddModal=true; },
    get chosenPi(){ return this.approvedPis.find(p => String(p.id)===String(this.form.proforma_invoice_id)); },
    onPickPi(){
      const pi = this.chosenPi;
      this.form.incoterms = pi?.incoterms || '';
      this.form.lines = (pi?.lines || []).map(l => ({ pi_line_id:l.pi_line_id, product:l.product, prn:l.prn, remaining:l.remaining, ordered:l.ordered, quantity:l.remaining, unit_price:l.unit_price, discount_amount:0, net_weight_kg:'', gross_weight_kg:'' }));
    },
    lineNet(l){ return Math.max(0, parseFloat(l.unit_price||0)*parseInt(l.quantity||0) - parseFloat(l.discount_amount||0)); },
    get grandTotal(){ return (this.form.lines.reduce((s,l)=> s + this.lineNet(l),0) + parseFloat(this.form.freight||0) + parseFloat(this.form.insurance||0)).toFixed(2); },
    overLimit(l){ return parseInt(l.quantity||0) > l.remaining; },
    get anyOver(){ return this.form.lines.some(l => this.overLimit(l)); },
    submitForm(status){ if(this.anyOver) return; this.form.status = status; this.$nextTick(()=> this.$refs.ciForm.submit()); },
  };
}
</script>
@endpush
