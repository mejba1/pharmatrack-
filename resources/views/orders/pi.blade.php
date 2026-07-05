@extends('layouts.app')
@section('title', 'Proforma Invoices')

@section('content')
<div x-data="piPage(@js($pis), @js($confirmedSos), @js($managers), @js($bankAccounts))">

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div><h1>Proforma Invoices</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Orders / Proforma Invoices</div></div>
    <div class="d-flex gap-2">@can('invoices.create')<button class="btn btn-primary btn-sm" @click="openCreate()"><i class="bi bi-plus-lg me-1"></i>Issue PI</button>@endcan</div>
  </div>

  <div class="info-box info mb-4">
    <i class="bi bi-diagram-2 text-primary mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px"><strong>Order Document Flow:</strong>
      <a href="{{ route('orders.po') }}" class="text-secondary text-decoration-none">PO</a> →
      <a href="{{ route('orders.so') }}" class="text-secondary text-decoration-none">SO</a> →
      <span class="text-primary fw-semibold">PI</span> →
      <a href="{{ route('orders.ci') }}" class="text-secondary text-decoration-none">CI</a> → Shipment.
      A PI is issued from a confirmed SO and goes through finance approval before a CI can be raised.
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Total PIs</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value">{{ $stats['pending'] }}</div><div class="stat-label">Pending Approval</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">{{ $stats['approved'] }}</div><div class="stat-label">Approved</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div><div class="stat-value">{{ $stats['rejected'] }}</div><div class="stat-label">Rejected</div></div></div></div>
  </div>

  <div class="card mb-3"><div class="card-body py-2">
    <div class="row g-2 align-items-center">
      <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search PI number, customer..." x-model="search"></div></div>
      <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Draft</option><option>Pending approval</option><option>Approved</option><option>Rejected</option></select></div>
      <div class="col-6 col-md-2 d-flex gap-1"><button class="btn btn-outline-secondary btn-sm" @click="search='';filterStatus=''"><i class="bi bi-x-lg me-1"></i>Clear</button></div>
    </div>
  </div></div>

  <div class="card table-card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>PI Number</th><th>Linked SO</th><th>Customer</th><th>PI Date</th><th>Valid Until</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <template x-for="pi in filtered" :key="pi.pid">
          <tr>
            <td><span class="text-primary fw-semibold cursor-pointer" style="font-size:12px" @click="viewPI(pi)" x-text="pi.id"></span></td>
            <td><a href="{{ route('orders.so') }}" class="text-secondary text-decoration-none" style="font-size:12px" x-text="pi.linkedSo"></a></td>
            <td><div class="fw-semibold" style="font-size:13px" x-text="pi.customer"></div><div class="text-muted-sm" x-text="pi.country"></div></td>
            <td style="font-size:13px" x-text="pi.piDate"></td>
            <td style="font-size:13px" x-text="pi.validUntil"></td>
            <td class="fw-semibold" style="font-size:13px" x-text="pi.value"></td>
            <td><span class="badge-status" :class="'badge-' + pi.statusClass" x-text="pi.status"></span></td>
            <td><div class="d-flex gap-1">
              <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewPI(pi)"><i class="bi bi-eye"></i></button>
              <a :href="pdfUrl(pi)" class="btn btn-outline-danger btn-sm btn-icon" title="PDF"><i class="bi bi-file-pdf"></i></a>
            </div></td>
          </tr>
        </template>
        <tr x-show="!filtered.length"><td colspan="8" class="text-center text-muted py-4">No proforma invoices yet. Click <strong>Issue PI</strong> (needs a confirmed SO).</td></tr>
      </tbody>
    </table>
  </div></div></div>

  @include('orders._pi-modals')
</div>
@endsection

@push('scripts')
<script>
function piPage(pis, confirmedSos, managers, banks){
  return {
    pis: pis || [], confirmedSos: confirmedSos || [], managers: managers || [], banks: banks || [],
    selectedBank:'',
    search:'', filterStatus:'', showViewModal:false, showAddModal:false, selectedPI:null, viewTab:'details',
    piTpl: '{{ url('orders/proforma-invoices') }}/__ID__',
    form: { sales_order_id:'', pi_date:'{{ now()->format('Y-m-d') }}', valid_until:'{{ now()->addDays(30)->format('Y-m-d') }}', currency:'USD', incoterms:'', payment_terms:'', port_of_loading:'', bank_name:'', bank_account_number:'', bank_swift_code:'', bank_iban:'', freight:'', status:'draft', remarks:'' },

    init(){
      const q = new URLSearchParams(location.search);
      // Deep-link from an SO: /proforma-invoices?so={id} opens the create modal preselected.
      const so = q.get('so');
      if (so && this.confirmedSos.some(s => String(s.id) === String(so))) {
        this.openCreate();
        this.form.sales_order_id = so;
        this.onPickSo();
        return;
      }
      // Drill-down: /proforma-invoices?view={id} opens that PI's detail modal.
      const view = q.get('view');
      if (view) { const pi = this.pis.find(p => String(p.pid) === String(view)); if (pi) this.viewPI(pi); }
    },

    get filtered(){
      return this.pis.filter(p => { const q=this.search.toLowerCase();
        return (!q || p.id.toLowerCase().includes(q) || (p.customer||'').toLowerCase().includes(q)) && (!this.filterStatus || p.status === this.filterStatus); });
    },
    pdfUrl(pi){ return this.piTpl.replace('__ID__', pi.pid) + '/pdf'; },
    statusUrl(){ return this.piTpl.replace('__ID__', this.selectedPI?.pid) + '/status'; },
    docUrl(){ return this.piTpl.replace('__ID__', this.selectedPI?.pid) + '/documents'; },
    viewPI(pi){ this.selectedPI = pi; this.viewTab='details'; this.showViewModal=true; },
    openCreate(){ this.form = { sales_order_id:'', pi_date:'{{ now()->format('Y-m-d') }}', valid_until:'{{ now()->addDays(30)->format('Y-m-d') }}', currency:'USD', incoterms:'', payment_terms:'', port_of_loading:'', bank_name:'', bank_account_number:'', bank_swift_code:'', bank_iban:'', freight:'', status:'draft', remarks:'' }; this.selectedBank=''; this.preselectDefaultBank(); this.showAddModal=true; },
    get chosenSo(){ return this.confirmedSos.find(s => String(s.id)===String(this.form.sales_order_id)); },
    onPickSo(){ const so=this.chosenSo; if(so){ this.form.currency=so.currency||'USD'; this.form.incoterms=so.incoterms||''; this.form.payment_terms=so.payment||''; } },
    // Selecting a saved bank auto-fills the invoice bank fields.
    pickBank(){
      const b = this.banks.find(x => String(x.id) === String(this.selectedBank));
      if (!b) return;
      this.form.bank_name = b.bank_name || '';
      this.form.bank_account_number = b.account_number || '';
      this.form.bank_swift_code = b.swift_code || '';
      this.form.bank_iban = b.iban || '';
    },
    preselectDefaultBank(){
      const d = this.banks.find(x => x.is_default);
      if (d) { this.selectedBank = String(d.id); this.pickBank(); }
    },
    submitForm(status){ this.form.status = status; this.$nextTick(()=> this.$refs.piForm.submit()); },
  };
}
</script>
@endpush
