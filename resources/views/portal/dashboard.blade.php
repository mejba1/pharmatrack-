@extends('layouts.portal-app')
@section('title', 'My Dashboard')
@section('heading', 'Dashboard')

@section('content')
@php
  $defaultTab = $portal['portal_show_orders'] ? 'orders'
    : ($portal['portal_show_invoices'] ? 'invoices'
    : ($portal['portal_show_documents'] ? 'documents'
    : ($portal['portal_show_units'] ? 'units' : 'profile')));
@endphp
<div x-data="{ tab: (location.hash ? location.hash.slice(1) : '{{ $defaultTab }}') }"
     x-init="window.addEventListener('hashchange', () => tab = location.hash ? location.hash.slice(1) : '{{ $defaultTab }}')">

  <div class="container-xl px-0">
    <h4 class="fw-bold mb-1">Welcome back, {{ $customer->name }} 👋</h4>
    <div class="text-muted small mb-4">{{ $customer->country?->flag }} {{ $customer->country?->name }}{{ $customer->city ? ', '.$customer->city : '' }}</div>

    @if(session('status'))<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}</div>@endif
    @if(trim($portal['portal_welcome_message']) !== '')
      <div class="card-soft p-3 mb-4 d-flex flex-row align-items-start gap-2" style="border-left:4px solid var(--brand1)">
        <i class="bi bi-megaphone-fill" style="color:var(--brand1)"></i>
        <div class="small">{{ $portal['portal_welcome_message'] }}</div>
      </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
      @php $cards = [
        ['Purchase Orders', $stats['orders'], 'bi-cart3', 'linear-gradient(135deg,#4f46e5,#6366f1)'],
        ['Units Purchased', $stats['units'], 'bi-upc-scan', 'linear-gradient(135deg,#0ea5e9,#38bdf8)'],
        ['Invoices', $stats['invoices'], 'bi-receipt', 'linear-gradient(135deg,#f59e0b,#fbbf24)'],
        ['Documents', $stats['documents'], 'bi-folder2-open', 'linear-gradient(135deg,#10b981,#34d399)'],
      ]; @endphp
      @foreach($cards as [$label,$value,$icon,$grad])
        <div class="col-6 col-lg-3">
          <div class="stat d-flex align-items-center gap-3">
            <div class="ic text-white" style="background:{{ $grad }}"><i class="bi {{ $icon }}"></i></div>
            <div><div class="v">{{ $value }}</div><div class="l">{{ $label }}</div></div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Tabs --}}
    <div class="d-flex gap-2 mb-3 flex-wrap">
      @if($portal['portal_show_orders'])<span class="pill" :class="{active: tab==='orders'}" @click="tab='orders'; location.hash='orders'"><i class="bi bi-cart3 me-1"></i>Orders</span>@endif
      @if($portal['portal_show_invoices'])<span class="pill" :class="{active: tab==='invoices'}" @click="tab='invoices'; location.hash='invoices'"><i class="bi bi-receipt me-1"></i>Invoices</span>@endif
      @if($portal['portal_show_invoices'])<span class="pill" :class="{active: tab==='accounts'}" @click="tab='accounts'; location.hash='accounts'"><i class="bi bi-wallet2 me-1"></i>Accounts</span>@endif
      @if($portal['portal_show_documents'])<span class="pill" :class="{active: tab==='documents'}" @click="tab='documents'; location.hash='documents'"><i class="bi bi-folder2-open me-1"></i>Documents</span>@endif
      @if($portal['portal_show_units'])<span class="pill" :class="{active: tab==='units'}" @click="tab='units'; location.hash='units'"><i class="bi bi-upc-scan me-1"></i>Traceable Units</span>@endif
      <span class="pill" :class="{active: tab==='profile'}" @click="tab='profile'; location.hash='profile'"><i class="bi bi-person-badge me-1"></i>Profile</span>
    </div>

    {{-- Orders --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='orders' && {{ $portal['portal_show_orders'] ? '1' : '0' }}" x-cloak
         x-data="portalTable(@js($ordersData), { searchFields:['po_number','status','date'], defaultSort:'dateISO' })"
         x-effect="if (page > pages) page = pages">
      <div class="d-flex align-items-center mb-3">
        <div class="fw-semibold"><i class="bi bi-cart3 me-1" style="color:var(--brand1)"></i>Purchase Orders</div>
        @if($portal['portal_allow_ordering'])<a href="{{ route('portal.order.create') }}" class="btn btn-grad btn-sm ms-auto"><i class="bi bi-cart-plus me-1"></i>Place Order</a>@endif
      </div>

      @include('portal._table-toolbar', ['placeholder' => 'Search PO # or status…'])

      <div class="table-responsive">
        <table class="table table-clean mb-0 align-middle">
          <thead><tr>
            <th role="button" @click="sortBy('po_number')">PO # <span x-html="caret('po_number')"></span></th>
            <th role="button" @click="sortBy('dateISO')">Date <span x-html="caret('dateISO')"></span></th>
            <th role="button" @click="sortBy('items')">Items <span x-html="caret('items')"></span></th>
            <th style="min-width:170px">Progress</th>
            <th role="button" @click="sortBy('status')">Status <span x-html="caret('status')"></span></th>
            <th class="text-end" role="button" @click="sortBy('totalNum')">Value <span x-html="caret('totalNum')"></span></th>
            <th class="text-end">Actions</th>
          </tr></thead>
          <tbody>
            <template x-for="o in paged" :key="o.id">
              <tr>
                <td class="font-monospace fw-semibold"><a :href="o.show_url" class="text-decoration-none" style="color:var(--brand1)" x-text="o.po_number"></a></td>
                <td x-text="o.date"></td>
                <td><span x-text="o.items"></span> <span x-text="o.items===1?'SKU':'SKUs'"></span></td>
                <td x-html="chainHtml(o)"></td>
                <td><span class="chip" style="background:#eef2ff;color:#4f46e5" x-text="o.status"></span></td>
                <td class="text-end fw-semibold"><span x-text="o.currency"></span> <span x-text="o.total"></span></td>
                <td class="text-end text-nowrap">
                  <a :href="o.show_url" class="btn btn-outline-secondary btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                  @if($portal['portal_allow_ordering'])
                    <a :href="o.edit_url" x-show="o.editable" class="btn btn-outline-primary btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                  @endif
                </td>
              </tr>
            </template>
            <tr x-show="!paged.length"><td colspan="7" class="text-center text-muted py-4" x-text="rows.length ? 'No orders match your filters.' : 'No purchase orders yet.'"></td></tr>
          </tbody>
        </table>
      </div>

      @include('portal._table-footer')
    </div>

    {{-- Invoices --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='invoices' && {{ $portal['portal_show_invoices'] ? '1' : '0' }}" x-cloak
         x-data="portalTable(@js($invoices), { searchFields:['number','type','status','reference'], defaultSort:'dateISO', statusField:'payStatus' })"
         x-effect="if (page > pages) page = pages">
      <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <div class="fw-semibold"><i class="bi bi-receipt me-1" style="color:var(--brand1)"></i>Invoices</div>
        <div class="ms-auto d-flex align-items-center gap-2">
          <span class="text-muted small">Payment</span>
          <select class="form-select form-select-sm" style="width:auto" x-model="statusFilter" @change="page=1">
            <option value="">All</option>
            <option value="paid">Paid</option>
            <option value="partial">Partial</option>
            <option value="unpaid">Unpaid</option>
          </select>
        </div>
      </div>

      @include('portal._table-toolbar', ['placeholder' => 'Search number, type, PO…'])

      <div class="table-responsive">
        <table class="table table-clean mb-0 align-middle">
          <thead><tr>
            <th role="button" @click="sortBy('type')">Type <span x-html="caret('type')"></span></th>
            <th role="button" @click="sortBy('number')">Number <span x-html="caret('number')"></span></th>
            <th role="button" @click="sortBy('reference')">Order <span x-html="caret('reference')"></span></th>
            <th role="button" @click="sortBy('dateISO')">Date <span x-html="caret('dateISO')"></span></th>
            <th class="text-end" role="button" @click="sortBy('totalNum')">Total <span x-html="caret('totalNum')"></span></th>
            <th class="text-end">Paid</th>
            <th class="text-end">Due</th>
            <th>Payment</th>
            <th class="text-end">Actions</th>
          </tr></thead>
          <tbody>
            <template x-for="(inv, i) in paged" :key="i">
              <tr>
                <td><span class="chip" :style="inv.type==='Proforma' ? 'background:#fff7ed;color:#c2410c' : 'background:#ecfdf5;color:#047857'" x-text="inv.type"></span></td>
                <td class="font-monospace fw-semibold" x-text="inv.number"></td>
                <td class="font-monospace small text-muted" x-text="inv.reference || '—'"></td>
                <td x-text="inv.date"></td>
                <td class="text-end fw-semibold"><span x-text="inv.currency"></span> <span x-text="inv.total"></span></td>
                <td class="text-end text-success"><span x-show="inv.paid!=='—'" x-text="inv.currency + ' ' + inv.paid"></span><span x-show="inv.paid==='—'" class="text-muted">—</span></td>
                <td class="text-end fw-semibold text-danger"><span x-show="inv.due!=='—'" x-text="inv.currency + ' ' + inv.due"></span><span x-show="inv.due==='—'" class="text-muted">—</span></td>
                <td><span class="chip text-capitalize" x-show="inv.payStatus!=='n/a'" :style="payStyle(inv.payStatus)" x-text="inv.payStatus"></span><span x-show="inv.payStatus==='n/a'" class="text-muted small">—</span></td>
                <td class="text-end text-nowrap">
                  <button type="button" class="btn btn-outline-secondary btn-sm btn-icon" title="View" @click="open(inv)"><i class="bi bi-eye"></i></button>
                  <a :href="inv.pdf_url" class="btn btn-outline-danger btn-sm btn-icon" title="Download PDF"><i class="bi bi-file-pdf"></i></a>
                  <template x-if="inv.doc_url"><a :href="inv.doc_url" target="_blank" class="btn btn-outline-primary btn-sm btn-icon" title="Attached document"><i class="bi bi-paperclip"></i></a></template>
                </td>
              </tr>
            </template>
            <tr x-show="!paged.length"><td colspan="9" class="text-center text-muted py-4" x-text="rows.length ? 'No invoices match your filters.' : 'No invoices yet.'"></td></tr>
          </tbody>
        </table>
      </div>

      @include('portal._table-footer')

      {{-- Invoice detail modal --}}
      <div class="modal fade" :class="{show:showModal}" :style="showModal?'display:block':''" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content" x-show="selected">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-receipt me-2" style="color:var(--brand1)"></i><span x-text="selected?.type + ' Invoice'"></span></h5>
              <button type="button" class="btn-close" @click="close()"></button>
            </div>
            <div class="modal-body">
              <table class="table table-clean mb-0">
                <tr><th style="width:150px">Number</th><td class="font-monospace fw-semibold" x-text="selected?.number"></td></tr>
                <tr><th>Order</th><td class="font-monospace" x-text="selected?.reference || '—'"></td></tr>
                <tr><th>Status</th><td class="text-capitalize" x-text="selected?.status"></td></tr>
                <tr><th>Date</th><td x-text="selected?.date"></td></tr>
                <tr x-show="selected?.valid_until"><th>Valid until</th><td x-text="selected?.valid_until"></td></tr>
                <tr><th>Subtotal</th><td><span x-text="selected?.currency"></span> <span x-text="selected?.subtotal"></span></td></tr>
                <tr><th>Freight</th><td><span x-text="selected?.currency"></span> <span x-text="selected?.freight"></span></td></tr>
                <tr><th x-text="selected?.extra_label"></th><td><span x-text="selected?.currency"></span> <span x-text="selected?.extra"></span></td></tr>
                <tr x-show="selected?.discount && selected?.discount!=='0.00'"><th>Discount</th><td class="text-danger">− <span x-text="selected?.currency"></span> <span x-text="selected?.discount"></span></td></tr>
                <tr><th>Total</th><td class="fw-bold"><span x-text="selected?.currency"></span> <span x-text="selected?.total"></span></td></tr>
                <tr x-show="selected?.payStatus!=='n/a'"><th>Paid</th><td class="text-success"><span x-text="selected?.currency"></span> <span x-text="selected?.paid"></span></td></tr>
                <tr x-show="selected?.payStatus!=='n/a'"><th>Due</th><td class="fw-bold text-danger"><span x-text="selected?.currency"></span> <span x-text="selected?.due"></span></td></tr>
                <tr x-show="selected?.payStatus!=='n/a'"><th>Payment status</th><td><span class="chip text-capitalize" :style="payStyle(selected?.payStatus)" x-text="selected?.payStatus"></span></td></tr>
              </table>
            </div>
            <div class="modal-footer">
              <a :href="selected?.pdf_url" class="btn btn-grad btn-sm"><i class="bi bi-file-pdf me-1"></i>Download PDF</a>
              <template x-if="selected?.doc_url"><a :href="selected?.doc_url" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-paperclip me-1"></i>Attached doc</a></template>
              <button type="button" class="btn btn-outline-secondary btn-sm" @click="close()">Close</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show" x-show="showModal" @click="close()" x-cloak></div>
    </div>

    {{-- Accounts --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='accounts' && {{ $portal['portal_show_invoices'] ? '1' : '0' }}" x-cloak
         x-data="portalTable(@js($productSummary), { searchFields:['product','prn'], defaultSort:'netNum' })"
         x-effect="if (page > pages) page = pages">
      <div class="fw-semibold mb-3"><i class="bi bi-wallet2 me-1" style="color:var(--brand1)"></i>Accounts &amp; Payments</div>

      {{-- Financial summary --}}
      <div class="row g-3 mb-4">
        @php $fcards = [
          ['Total Invoiced', $financials['currency'].' '.$financials['invoiced'], 'bi-receipt-cutoff', 'linear-gradient(135deg,#4f46e5,#6366f1)'],
          ['Total Paid',     $financials['currency'].' '.$financials['paid'],     'bi-cash-coin',      'linear-gradient(135deg,#10b981,#34d399)'],
          ['Total Dues',     $financials['currency'].' '.$financials['due'],      'bi-exclamation-circle', 'linear-gradient(135deg,#f43f5e,#fb7185)'],
          ['Total Discount', $financials['currency'].' '.$financials['discount'], 'bi-percent',        'linear-gradient(135deg,#f59e0b,#fbbf24)'],
        ]; @endphp
        @foreach($fcards as [$label,$value,$icon,$grad])
          <div class="col-6 col-lg-3"><div class="stat d-flex align-items-center gap-3">
            <div class="ic text-white" style="background:{{ $grad }}"><i class="bi {{ $icon }}"></i></div>
            <div class="min-w-0"><div class="v text-truncate" style="font-size:17px">{{ $value }}</div><div class="l">{{ $label }}</div></div>
          </div></div>
        @endforeach
      </div>

      {{-- Product-wise order value --}}
      <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
        <div class="fw-semibold"><i class="bi bi-box-seam me-1" style="color:var(--brand1)"></i>Product-wise order value</div>
        <div class="input-group input-group-sm ms-auto" style="width:240px">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="search" class="form-control" placeholder="Search product…" x-model="q" @input="page=1">
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-clean mb-0 align-middle">
          <thead><tr>
            <th role="button" @click="sortBy('product')">Product <span x-html="caret('product')"></span></th>
            <th class="text-end" role="button" @click="sortBy('qty')">Qty <span x-html="caret('qty')"></span></th>
            <th class="text-end" role="button" @click="sortBy('grossNum')">Order value <span x-html="caret('grossNum')"></span></th>
            <th class="text-end" role="button" @click="sortBy('discountNum')">Discount <span x-html="caret('discountNum')"></span></th>
            <th class="text-end" role="button" @click="sortBy('netNum')">Net <span x-html="caret('netNum')"></span></th>
          </tr></thead>
          <tbody>
            <template x-for="(p, i) in paged" :key="i">
              <tr>
                <td><span class="fw-semibold" x-text="p.product"></span> <span class="text-muted small font-monospace" x-text="p.prn"></span></td>
                <td class="text-end" x-text="Number(p.qty).toLocaleString()"></td>
                <td class="text-end"><span x-text="p.currency"></span> <span x-text="p.gross"></span></td>
                <td class="text-end text-danger" x-text="p.discount"></td>
                <td class="text-end fw-semibold"><span x-text="p.currency"></span> <span x-text="p.net"></span></td>
              </tr>
            </template>
            <tr x-show="!paged.length"><td colspan="5" class="text-center text-muted py-4" x-text="rows.length ? 'No products match.' : 'No invoiced products yet.'"></td></tr>
          </tbody>
        </table>
      </div>
      @include('portal._table-footer')
    </div>

    {{-- Documents --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='documents' && {{ $portal['portal_show_documents'] ? '1' : '0' }}" x-cloak>
      <div class="fw-semibold mb-3"><i class="bi bi-folder2-open me-1" style="color:var(--brand1)"></i>Documents</div>
      @if($documents->isEmpty())
        <div class="text-center text-muted py-4">No documents shared yet.</div>
      @else
        <div class="row g-2">
          @foreach($documents as $doc)
            <div class="col-md-6">
              <a href="{{ $doc['url'] }}" target="_blank" class="d-flex align-items-center gap-3 p-3 border rounded-3 text-body" style="border-color:var(--line)!important">
                <i class="bi bi-file-earmark-text fs-4 {{ $doc['icon'] }}"></i>
                <div class="min-w-0 flex-fill">
                  <div class="fw-semibold text-truncate">{{ $doc['name'] }}</div>
                  <div class="text-muted small">{{ $doc['category'] ?? 'Document' }} · {{ $doc['size'] }} · {{ $doc['date'] }}</div>
                </div>
                <i class="bi bi-download text-muted"></i>
              </a>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Units --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='units' && {{ $portal['portal_show_units'] ? '1' : '0' }}" x-cloak>
      <div class="fw-semibold mb-3"><i class="bi bi-upc-scan me-1" style="color:var(--brand1)"></i>My Traceable Units ({{ $units->count() }})</div>
      <div class="table-responsive" style="max-height:420px;overflow:auto">
        <table class="table table-clean mb-0">
          <thead><tr><th>Product</th><th>Batch</th><th>Serial</th><th>Received</th></tr></thead>
          <tbody>
            @forelse($units as $u)
              <tr><td>{{ $u->batch?->product?->name ?? '—' }}</td><td>{{ $u->batch?->brn }}</td><td class="font-monospace">{{ $u->serial_number }}</td><td>{{ $u->sold_at?->format('d M Y') }}</td></tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted py-4">No units assigned yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Profile --}}
    <div class="card-soft p-3 p-md-4" x-show="tab==='profile'" x-cloak>
      <div class="d-flex align-items-center mb-3">
        <div class="fw-semibold"><i class="bi bi-person-badge me-1" style="color:var(--brand1)"></i>My Profile</div>
        @if($portal['portal_allow_profile_edit'])<a href="{{ route('portal.profile') }}" class="btn btn-grad btn-sm ms-auto"><i class="bi bi-pencil me-1"></i>Edit profile</a>@endif
      </div>
      <div class="row g-4">
        <div class="col-md-6"><table class="table table-clean mb-0">
          <tr><th style="width:150px">Customer code</th><td class="font-monospace">{{ $customer->customer_code }}</td></tr>
          <tr><th>Type</th><td>{{ $customer->type_label }}</td></tr>
          <tr><th>Email</th><td>{{ $customer->email ?? '—' }}</td></tr>
          <tr><th>Phone</th><td>{{ $customer->phone ?? '—' }}</td></tr>
          <tr><th>Country / City</th><td>{{ $customer->country?->name ?? '—' }}{{ $customer->city ? ', '.$customer->city : '' }}</td></tr>
        </table></div>
        <div class="col-md-6"><table class="table table-clean mb-0">
          <tr><th style="width:150px">Company</th><td>{{ $customer->company_name ?? '—' }}</td></tr>
          <tr><th>Company ID</th><td>{{ $customer->company_id ?? '—' }}</td></tr>
          <tr><th>Identification</th><td>{{ $customer->id_type_label ?: '—' }}{{ $customer->identification_number ? ' · '.$customer->identification_number : '' }}</td></tr>
          <tr><th>Address</th><td>{{ $customer->address ?? '—' }}</td></tr>
          <tr><th>Account Manager</th><td>{{ $customer->manager?->name ?? '—' }}</td></tr>
        </table></div>
      </div>
    </div>

  </div>
</div>

<script>
// Client-side data table: search, date-range filter, column sort, pagination.
function portalTable(rows, config){
  config = config || {};
  return {
    rows: rows || [],
    q: '', from: '', to: '', statusFilter: '',
    sortKey: config.defaultSort || 'dateISO',
    sortDir: 'desc',
    page: 1,
    perPage: 10,
    showModal: false,
    selected: null,

    get filtered(){
      const q = this.q.trim().toLowerCase();
      const fields = config.searchFields || [];
      const r = this.rows.filter(row => {
        if (q && !fields.some(f => String(row[f] ?? '').toLowerCase().includes(q))) return false;
        if (this.from && row.dateISO && row.dateISO < this.from) return false;
        if (this.to   && row.dateISO && row.dateISO > this.to)   return false;
        if (config.statusField && this.statusFilter && String(row[config.statusField]) !== this.statusFilter) return false;
        return true;
      });
      const dir = this.sortDir === 'asc' ? 1 : -1;
      const key = this.sortKey;
      return r.slice().sort((a, b) => {
        const av = a[key], bv = b[key];
        if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
        return String(av ?? '').localeCompare(String(bv ?? '')) * dir;
      });
    },
    get total(){ return this.filtered.length; },
    get pages(){ return Math.max(1, Math.ceil(this.total / this.perPage)); },
    get paged(){ const s = (this.page - 1) * this.perPage; return this.filtered.slice(s, s + this.perPage); },

    sortBy(k){
      if (this.sortKey === k) { this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'; }
      else { this.sortKey = k; this.sortDir = 'asc'; }
      this.page = 1;
    },
    caret(k){
      if (this.sortKey !== k) return '<i class="bi bi-arrow-down-up" style="opacity:.35;font-size:11px"></i>';
      return this.sortDir === 'asc'
        ? '<i class="bi bi-caret-up-fill" style="font-size:11px"></i>'
        : '<i class="bi bi-caret-down-fill" style="font-size:11px"></i>';
    },
    reset(){ this.q = ''; this.from = ''; this.to = ''; this.statusFilter = ''; this.page = 1; },
    open(row){ this.selected = row; this.showModal = true; },
    close(){ this.showModal = false; },
    payStyle(s){ return ({paid:'background:#ecfdf5;color:#047857', partial:'background:#fffbeb;color:#b45309', unpaid:'background:#fef2f2;color:#b91c1c'})[s] || 'background:#f1f5f9;color:#64748b'; },

    // PO → SO → PI → CI progress chain markup for a row.
    chainHtml(o){
      const labels = ['PO','SO','PI','CI'];
      let h = '<div class="d-flex align-items-center">';
      labels.forEach((label, i) => {
        const done = o.step >= (i + 1);
        h += '<div class="text-center" style="width:38px">'
          + '<div class="rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width:24px;height:24px;font-size:9px;font-weight:700;'
          + (done ? 'background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff' : 'background:#fff;border:1px solid #e2e8f0;color:#94a3b8') + '">'
          + (done ? '<i class="bi bi-check-lg"></i>' : label) + '</div>'
          + '<div style="font-size:9px;' + (done ? 'color:#16a34a;font-weight:600' : 'color:#94a3b8') + '">' + label
          + ((label === 'CI' && o.ci_count > 1) ? ('&times;' + o.ci_count) : '') + '</div>'
          + '</div>';
        if (i < 3) h += '<div class="flex-fill" style="height:2px;' + (o.step > (i + 1) ? 'background:#16a34a' : 'background:#e2e8f0') + '"></div>';
      });
      return h + '</div>';
    },
  };
}
</script>
@endsection
