@extends('layouts.app')
@section('title', 'Customers & Sales')

@push('styles')
<style>
/* ── Scrollable modal with a <form> wrapping body + footer ───────────────── */
/* These modals are opened by toggling display:block via Alpine (not the     */
/* Bootstrap JS), so Bootstrap's percentage height chain never resolves and  */
/* the footer (Save / Update button) gets clipped out of view. Bound the     */
/* height with a viewport unit so the body scrolls and the footer stays      */
/* pinned and visible — independent of the parent height chain.              */
.modal-dialog-scrollable > .modal-content {
  max-height: calc(100vh - 3.5rem);
  overflow: hidden;
}
.modal-content > form {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}
.modal-content > form > .modal-body {
  flex: 1 1 auto;
  overflow-y: auto;
  min-height: 0;
}
.modal-content > form > .modal-footer {
  flex-shrink: 0;
}
</style>
@endpush

@section('content')
@php
  $batchesJs = $batches->map(fn ($b) => ['id' => $b->id, 'product_id' => $b->product_id, 'label' => $b->brn . ($b->batch_number ? ' · ' . $b->batch_number : '')])->values();
@endphp
<div x-data="customersApp(@js($batchesJs))">

  {{-- Flash → toast --}}
  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div>
      <h1>Customer Directory</h1>
      <div class="page-breadcrumb">Customers / Directory</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm" @click="openSale()"><i class="bi bi-receipt me-1"></i>Record Sale</button>
      @can('customers.create')<button class="btn btn-primary btn-sm" @click="openAdd()"><i class="bi bi-person-plus me-1"></i>Add Customer</button>@endcan
    </div>
  </div>

  {{-- Stats --}}
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card stat-primary"><div class="stat-label">Customers</div><div class="stat-value">{{ $stats['customers'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-success"><div class="stat-label">Active</div><div class="stat-value">{{ $stats['active'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-warning"><div class="stat-label">Sales recorded</div><div class="stat-value">{{ $stats['sales'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-danger"><div class="stat-label">Units sold</div><div class="stat-value">{{ $stats['units_sold'] }}</div></div></div>
  </div>

  {{-- Pending-approval alert (new self-registrations) --}}
  @if(($stats['pending'] ?? 0) > 0 && $filters['status'] !== 'pending')
    <div class="alert alert-warning d-flex align-items-center py-2 mb-3">
      <i class="bi bi-person-exclamation me-2 fs-5"></i>
      <span><strong>{{ $stats['pending'] }}</strong> customer{{ $stats['pending'] === 1 ? '' : 's' }} awaiting approval.</span>
      <a href="{{ route('customers.index', ['status' => 'pending']) }}" class="btn btn-warning btn-sm ms-auto"><i class="bi bi-eye me-1"></i>Review pending</a>
    </div>
  @endif

  {{-- Filters --}}
  <div class="card mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4"><label class="form-label">Search</label><input type="text" name="search" value="{{ $filters['search'] }}" class="form-control form-control-sm" placeholder="Name, code, contact, email, phone"></div>
      <div class="col-md-3"><label class="form-label">Type</label>
        <select name="type" class="form-select form-select-sm"><option value="">All types</option>
          @foreach($types as $k => $label)<option value="{{ $k }}" @selected($filters['type']===$k)>{{ $label }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">Status</label>
        <select name="status" class="form-select form-select-sm"><option value="">All</option>
          @foreach(['active','suspended','expired','pending'] as $s)<option value="{{ $s }}" @selected($filters['status']===$s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
      </div>
      @if($isAdmin)
      <div class="col-md-2"><label class="form-label">Account Manager</label>
        <select name="manager_id" class="form-select form-select-sm"><option value="">All managers</option>
          <option value="none" @selected($filters['manager_id']==='none')>— Unassigned —</option>
          @foreach($managers as $m)<option value="{{ $m->id }}" @selected((string)$filters['manager_id']===(string)$m->id)>{{ $m->name }}</option>@endforeach
        </select>
      </div>
      @endif
      <div class="col-md-2"><label class="form-label">Sort</label>
        <select name="sort" class="form-select form-select-sm">
          <option value="newest" @selected($filters['sort']==='newest')>Newest</option>
          <option value="name"   @selected($filters['sort']==='name')>Name</option>
          <option value="sales"  @selected($filters['sort']==='sales')>Most sales</option>
          <option value="units"  @selected($filters['sort']==='units')>Most units</option>
        </select>
      </div>
      <div class="col-md-1 d-flex gap-1">
        <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel"></i></button>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
      </div>
    </form>
  </div></div>

  {{-- Bulk actions bar --}}
  @can('customers.edit')
  <form method="POST" action="{{ route('customers.bulk') }}" x-show="selected.length" x-cloak
        @submit="return confirm('Apply to '+selected.length+' customer(s)?')" class="card mb-2 border-primary">
    <div class="card-body py-2 d-flex align-items-center gap-2 flex-wrap">
      @csrf
      <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
      <span class="fw-semibold small"><span x-text="selected.length"></span> selected</span>
      <button name="action" value="approve" class="btn btn-success btn-sm ms-2"><i class="bi bi-check2-circle me-1"></i>Approve &amp; activate</button>
      <button name="action" value="suspend" class="btn btn-outline-warning btn-sm"><i class="bi bi-pause-circle me-1"></i>Suspend</button>
      <button type="button" class="btn btn-link btn-sm text-muted ms-auto" @click="selected=[]">Clear selection</button>
    </div>
  </form>
  @endcan

  {{-- Table --}}
  <div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr>@can('customers.edit')<th style="width:34px"><input type="checkbox" class="form-check-input" @change="toggleAll($event.target.checked)" :checked="allSelected"></th>@endcan<th>Code</th><th>Customer</th><th>Type</th><th>Country</th>@if($isAdmin)<th>Account Manager</th>@endif<th>Contact</th><th class="text-center">Sales</th><th class="text-center">Units</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        @forelse($customers as $c)
          <tr>
            @can('customers.edit')<td><input type="checkbox" class="form-check-input" value="{{ $c->id }}" x-model="selected"></td>@endcan
            <td class="font-monospace small">{{ $c->customer_code ?? '—' }}</td>
            <td class="fw-semibold">
              <div class="d-flex align-items-center gap-2">
                @if($c->logo_url)
                  <img src="{{ $c->logo_url }}" alt="" style="width:34px;height:34px;border-radius:7px;object-fit:cover">
                @else
                  <span class="rounded d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:34px;height:34px;font-size:12px;font-weight:600">{{ $c->initials }}</span>
                @endif
                <div>{{ $c->name }}@if($c->company_name)<div class="text-muted-sm fw-normal">{{ $c->company_name }}</div>@endif</div>
              </div>
            </td>
            <td class="small">{{ $c->type_label }}</td>
            <td class="small">{{ $c->country?->flag }} {{ $c->country?->name ?? '—' }}@if($c->city)<div class="text-muted-sm">{{ $c->city }}</div>@endif</td>
            @if($isAdmin)<td class="small">@if($c->manager){{ $c->manager->name }}@else<span class="text-muted">— unassigned —</span>@endif</td>@endif
            <td class="small">
              @if($c->email)<div><i class="bi bi-envelope me-1 text-muted"></i>{{ $c->email }}</div>@endif
              @if($c->phone)<div class="text-muted-sm"><i class="bi bi-telephone me-1"></i>{{ $c->phone }}</div>@endif
              @if(!$c->email && !$c->phone)—@endif
            </td>
            <td class="text-center">{{ $c->sales_count }}</td>
            <td class="text-center">{{ $c->units_sold }}</td>
            <td><span class="badge-status {{ $c->status_badge_class }}">{{ ucfirst($c->status) }}</span></td>
            <td class="text-end">
              <div class="d-inline-flex gap-1">
                @can('customers.edit')
                @if($c->status !== 'active')
                <form method="POST" action="{{ route('customers.approve', $c) }}" @submit="return confirm('Approve {{ addslashes($c->name) }}? They will be activated and emailed.')">
                  @csrf<button class="btn btn-success btn-sm btn-icon" title="Approve &amp; activate"><i class="bi bi-check2-circle"></i></button>
                </form>
                @endif
                @endcan
                <button class="btn btn-outline-primary btn-sm btn-icon" title="View" @click="openView({{ $c->id }})"><i class="bi bi-eye"></i></button>
                @can('customers.edit')<button class="btn btn-outline-secondary btn-sm btn-icon" title="Edit" @click="openEdit({{ Illuminate\Support\Js::from([
                  'id'=>$c->id,'name'=>$c->name,'type'=>$c->type,'email'=>$c->email,'phone'=>$c->phone,
                  'country_id'=>$c->country_id,'city'=>$c->city,'address'=>$c->address,'referenced_by'=>$c->referenced_by,
                  'company_name'=>$c->company_name,'company_id'=>$c->company_id,
                  'identification_type'=>$c->identification_type,'identification_number'=>$c->identification_number,
                  'license_number'=>$c->license_number,'manager_id'=>$c->manager_id,'status'=>$c->status,
                  'company_logo_url'=>$c->logo_url,
                  'portal_access'=>(bool)($c->portal_access ?? true),
                  'portal_can_order'=>is_null($c->portal_can_order) ? '' : (string)(int)$c->portal_can_order,
                ]) }})"><i class="bi bi-pencil"></i></button>@endcan
                <button class="btn btn-outline-success btn-sm btn-icon" title="Sell to this customer" @click="openSale({{ $c->id }})"><i class="bi bi-receipt"></i></button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ ($isAdmin ? 10 : 9) + (auth()->user()->can('customers.edit') ? 1 : 0) }}" class="text-center text-muted py-4">No customers yet. Click <strong>Add Customer</strong> to start.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div></div>
  @if($customers->hasPages())<div class="card-footer bg-transparent">{{ $customers->links() }}</div>@endif
  </div>

  @include('customers._modals')
</div>
@endsection

@push('scripts')
<script>
  function customersApp(batches){
    return {
      batches: batches || [],
      selected: [],
      allIds: @js($customers->pluck('id')->map(fn ($i) => (string) $i)->values()),
      get allSelected(){ return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
      toggleAll(checked){ this.selected = checked ? [...this.allIds] : []; },
      init(){ if (new URLSearchParams(location.search).get('new')) this.$nextTick(() => this.openAdd()); },
      showAdd:false, showEdit:false, showView:false, showSale:false,
      addUrl: '{{ route('customers.store') }}',
      saleUrl: '{{ route('customers.sales.store') }}',
      updateTpl: '{{ url('customers') }}/__ID__',
      viewTpl: '{{ url('customers') }}/__ID__',
      blank(){ return {id:null, name:'', type:'distributor', email:'', phone:'', country_id:'', city:'', referenced_by:'', address:'', company_name:'', company_id:'', identification_type:'', identification_number:'', license_number:'', manager_id:'', status:'active', password:'', company_logo_url:'', portal_access:true, portal_can_order:''}; },
      form: {},
      logoPreview: null,
      view: null,
      sale: {customer_id:'', sale_date:'{{ now()->format('Y-m-d') }}', currency:'USD', status:'confirmed', notes:'', items:[]},

      openAdd(){ this.form=this.blank(); this.logoPreview=null; this.showAdd=true; },
      openEdit(c){ this.form={...this.blank(), ...c, password:''}; this.logoPreview=null; this.showEdit=true; },
      onLogo(e){ const f=e.target.files?.[0]; this.logoPreview = f ? URL.createObjectURL(f) : null; },
      get editAction(){ return this.updateTpl.replace('__ID__', this.form.id); },

      async openView(id){
        this.view=null; this.showView=true;
        try { const r = await fetch(this.viewTpl.replace('__ID__', id), {headers:{'Accept':'application/json'}}); this.view = await r.json(); }
        catch(e){ this.view = {error:true}; }
      },

      openSale(customerId){
        this.sale = {customer_id: customerId || '', sale_date:'{{ now()->format('Y-m-d') }}', currency:'USD', status:'confirmed', notes:'', items:[]};
        this.addItem();
        this.showSale=true;
      },
      addItem(){ this.sale.items.push({product_id:'', batch_id:'', quantity:1, unit_price:'', uuc_codes:''}); },
      removeItem(i){ this.sale.items.splice(i,1); if(!this.sale.items.length) this.addItem(); },
      batchesFor(pid){ return pid ? this.batches.filter(b => String(b.product_id)===String(pid)) : []; },
      onItemProduct(it){ if(it.batch_id && !this.batchesFor(it.product_id).some(b=>String(b.id)===String(it.batch_id))) it.batch_id=''; },
      get saleTotal(){ return this.sale.items.reduce((s,it)=> s + (parseFloat(it.unit_price||0) * parseInt(it.quantity||0)), 0).toFixed(2); },
    };
  }
</script>
@endpush
