@extends('layouts.app')
@section('title', 'Purchase Orders')

@section('content')
@php
  $customersJs = $customers; // [{id,name,type,code,country}]
@endphp
<style>.cm-opt:hover{background:#f1f5f9}</style>
<div x-data="poPage(@js($pos), @js($customersJs), @js($products))">

  {{-- Flash → toast --}}
  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div>
      <h1>Purchase Orders</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Orders / Purchase Orders</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary btn-sm" @click="openCreate()"><i class="bi bi-plus-lg me-1"></i>Create PO</button>
    </div>
  </div>

  {{-- Doc Flow Banner --}}
  <div class="info-box info mb-4">
    <i class="bi bi-diagram-2 text-primary mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px">
      <strong>Order Document Flow:</strong>
      <span class="text-primary fw-semibold">PO</span> →
      <a href="{{ route('orders.so') }}" class="text-secondary text-decoration-none">SO</a> →
      <a href="{{ route('orders.pi') }}" class="text-secondary text-decoration-none">PI</a> →
      <a href="{{ route('orders.ci') }}" class="text-secondary text-decoration-none">CI</a> →
      <a href="{{ route('shipments') }}" class="text-secondary text-decoration-none">Shipment</a>.
      A Sales Order cannot be raised without an acknowledged PO.
    </div>
  </div>

  {{-- Stats --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-cart3"></i></div><div><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Total POs</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value">{{ $stats['pending'] }}</div><div class="stat-label">Pending Acknowledgment</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">{{ $stats['acknowledged'] }}</div><div class="stat-label">Acknowledged</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div><div class="stat-value">{{ $stats['cancelled'] }}</div><div class="stat-label">Cancelled</div></div></div></div>
  </div>

  {{-- Filters --}}
  <div class="card mb-3"><div class="card-body py-2">
    <div class="row g-2 align-items-center">
      <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i>
        <input type="text" class="form-control form-control-sm" placeholder="Search PO number, buyer..." x-model="search"></div></div>
      <div class="col-6 col-md-2">
        <select class="form-select form-select-sm" x-model="filterStatus">
          <option value="">All Status</option><option>Draft</option><option>Sent</option><option>Acknowledged</option><option>Cancelled</option>
        </select>
      </div>
      <div class="col-6 col-md-2 d-flex gap-1">
        <button class="btn btn-outline-secondary btn-sm" @click="search='';filterStatus=''"><i class="bi bi-x-lg me-1"></i>Clear</button>
      </div>
    </div>
  </div></div>

  {{-- Table --}}
  <div class="card table-card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>PO Number</th><th>Buyer / Distributor</th><th>Products</th><th>PO Date</th>
        <th>Required By</th><th>Total Value</th><th>Manager</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <template x-for="po in filtered" :key="po.pid">
          <tr>
            <td><span class="text-primary fw-semibold cursor-pointer" style="font-size:12px" @click="viewPO(po)" x-text="po.id"></span></td>
            <td><div class="fw-semibold" style="font-size:13px" x-text="po.buyer"></div><div class="text-muted-sm" x-text="po.country"></div></td>
            <td style="font-size:13px" x-text="po.products"></td>
            <td style="font-size:13px" x-text="po.poDate"></td>
            <td style="font-size:13px" x-text="po.requiredBy"></td>
            <td class="fw-semibold" style="font-size:13px" x-text="po.value"></td>
            <td style="font-size:12px" x-text="po.manager || '—'"></td>
            <td><span class="badge-status" :class="'badge-' + po.statusClass" x-text="po.status"></span></td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewPO(po)" title="View"><i class="bi bi-eye"></i></button>
                <a :href="pdfUrl(po)" class="btn btn-outline-danger btn-sm btn-icon" title="Download PDF"><i class="bi bi-file-pdf"></i></a>
              </div>
            </td>
          </tr>
        </template>
        <tr x-show="!filtered.length"><td colspan="9" class="text-center text-muted py-4">No purchase orders yet. Click <strong>Create PO</strong>.</td></tr>
      </tbody>
    </table>
  </div></div></div>

  @include('orders._po-modals')
</div>
@endsection

@push('scripts')
<script>
function poPage(pos, customers, products){
  return {
    pos: pos || [], customers: customers || [], products: products || [],
    search:'', filterStatus:'', showViewModal:false, showAddModal:false, selectedPO:null, viewTab:'details', dragging:false,
    storeUrl: '{{ route('orders.po.store') }}',
    poTpl: '{{ url('orders/purchase-orders') }}/__ID__',

    form: { buyer_type:'', buyer_id:'', required_by_date:'', po_date:'{{ now()->format('Y-m-d') }}', currency:'USD', payment_terms:'30 days net', incoterms:'', port_of_loading:'', port_of_discharge:'', freight:'', remarks:'', status:'sent', items:[] },

    get filtered(){
      return this.pos.filter(p => {
        const q = this.search.toLowerCase();
        return (!q || p.id.toLowerCase().includes(q) || (p.buyer||'').toLowerCase().includes(q))
            && (!this.filterStatus || p.status === this.filterStatus);
      });
    },
    pdfUrl(po){ return this.poTpl.replace('__ID__', po.pid) + '/pdf'; },
    statusUrl(){ return this.poTpl.replace('__ID__', this.selectedPO?.pid) + '/status'; },
    deleteUrl(){ return this.poTpl.replace('__ID__', this.selectedPO?.pid); },
    docUrl(){ return this.poTpl.replace('__ID__', this.selectedPO?.pid) + '/documents'; },

    viewPO(po){ this.selectedPO = po; this.viewTab='details'; this.showViewModal=true; },

    openCreate(){
      this.form = { buyer_type:'', buyer_id:'', required_by_date:'', po_date:'{{ now()->format('Y-m-d') }}', currency:'USD', payment_terms:'30 days net', incoterms:'', port_of_loading:'', port_of_discharge:'', freight:'', remarks:'', status:'sent', items:[] };
      this.addItem(); this.showAddModal=true;
    },
    get filteredCustomers(){ return this.form.buyer_type ? this.customers.filter(c => c.type === this.form.buyer_type) : this.customers; },
    onType(){ if(this.form.buyer_id && !this.filteredCustomers.some(c => String(c.id)===String(this.form.buyer_id))) this.form.buyer_id=''; },
    addItem(){ this.form.items.push({product_id:'', quantity:1, unit_price:''}); },
    removeItem(i){ this.form.items.splice(i,1); if(!this.form.items.length) this.addItem(); },
    get grandTotal(){ return (this.form.items.reduce((s,it)=> s + (parseFloat(it.unit_price||0)*parseInt(it.quantity||0)),0) + parseFloat(this.form.freight||0)).toFixed(2); },
    submitForm(status){ this.form.status = status; this.$nextTick(()=> this.$refs.poForm.submit()); },
  };
}
</script>
@endpush
