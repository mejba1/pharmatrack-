@extends('layouts.app')
@section('title', 'Customers & Sales')

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
      <h1>Customers &amp; Sales</h1>
      <div class="page-breadcrumb">Sales / Customers</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm" @click="openSale()"><i class="bi bi-receipt me-1"></i>Record Sale</button>
      <button class="btn btn-primary btn-sm" @click="openAdd()"><i class="bi bi-person-plus me-1"></i>Add Customer</button>
    </div>
  </div>

  {{-- Stats --}}
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card stat-primary"><div class="stat-label">Customers</div><div class="stat-value">{{ $stats['customers'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-success"><div class="stat-label">Active</div><div class="stat-value">{{ $stats['active'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-warning"><div class="stat-label">Sales recorded</div><div class="stat-value">{{ $stats['sales'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-danger"><div class="stat-label">Units sold</div><div class="stat-value">{{ $stats['units_sold'] }}</div></div></div>
  </div>

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

  {{-- Table --}}
  <div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Code</th><th>Customer</th><th>Type</th><th>Country</th>@if($isAdmin)<th>Account Manager</th>@endif<th>Contact</th><th class="text-center">Sales</th><th class="text-center">Units</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        @forelse($customers as $c)
          <tr>
            <td class="font-monospace small">{{ $c->customer_code ?? '—' }}</td>
            <td class="fw-semibold">{{ $c->name }}@if($c->company_name)<div class="text-muted-sm fw-normal">{{ $c->company_name }}</div>@endif</td>
            <td class="small">{{ $c->type_label }}</td>
            <td class="small">{{ $c->country?->flag }} {{ $c->country?->name ?? '—' }}</td>
            @if($isAdmin)<td class="small">@if($c->manager){{ $c->manager->name }}@else<span class="text-muted">— unassigned —</span>@endif</td>@endif
            <td class="small">
              {{ $c->contact_person ?? '—' }}
              @if($c->contact_phone)<div class="text-muted-sm"><i class="bi bi-telephone me-1"></i>{{ $c->contact_phone }}</div>@endif
            </td>
            <td class="text-center">{{ $c->sales_count }}</td>
            <td class="text-center">{{ $c->units_sold }}</td>
            <td><span class="badge-status {{ $c->status_badge_class }}">{{ ucfirst($c->status) }}</span></td>
            <td class="text-end">
              <div class="d-inline-flex gap-1">
                <button class="btn btn-outline-primary btn-sm btn-icon" title="View" @click="openView({{ $c->id }})"><i class="bi bi-eye"></i></button>
                <button class="btn btn-outline-secondary btn-sm btn-icon" title="Edit" @click="openEdit({{ Illuminate\Support\Js::from([
                  'id'=>$c->id,'name'=>$c->name,'company_name'=>$c->company_name,'type'=>$c->type,'country_id'=>$c->country_id,'manager_id'=>$c->manager_id,'contact_person'=>$c->contact_person,
                  'contact_email'=>$c->contact_email,'contact_phone'=>$c->contact_phone,'address'=>$c->address,
                  'license_number'=>$c->license_number,'status'=>$c->status,
                ]) }})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-success btn-sm btn-icon" title="Sell to this customer" @click="openSale({{ $c->id }})"><i class="bi bi-receipt"></i></button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ $isAdmin ? 10 : 9 }}" class="text-center text-muted py-4">No customers yet. Click <strong>Add Customer</strong> to start.</td></tr>
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
      showAdd:false, showEdit:false, showView:false, showSale:false,
      addUrl: '{{ route('customers.store') }}',
      saleUrl: '{{ route('customers.sales.store') }}',
      updateTpl: '{{ url('customers') }}/__ID__',
      viewTpl: '{{ url('customers') }}/__ID__',
      form: {id:null, name:'', company_name:'', type:'retailer', country_id:'', manager_id:'', contact_person:'', contact_email:'', contact_phone:'', address:'', license_number:'', status:'active'},
      view: null,
      sale: {customer_id:'', sale_date:'{{ now()->format('Y-m-d') }}', currency:'USD', status:'confirmed', notes:'', items:[]},

      openAdd(){ this.form={id:null, name:'', company_name:'', type:'retailer', country_id:'', manager_id:'', contact_person:'', contact_email:'', contact_phone:'', address:'', license_number:'', status:'active'}; this.showAdd=true; },
      openEdit(c){ this.form={...c}; this.showEdit=true; },
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
