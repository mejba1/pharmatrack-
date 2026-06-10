@extends('layouts.app')
@section('title', 'Country Permissions')

@section('content')
<div x-data="countriesPage()">

  <div class="page-header">
    <div><h1>Country Permissions</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Country Permissions</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Add Country</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-globe2"></i></div><div><div class="stat-value">47</div><div class="stat-label">Active Countries</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">38</div><div class="stat-label">Import Permitted</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div><div><div class="stat-value">29</div><div class="stat-label">License Required</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-shield-check"></i></div><div><div class="stat-value">22</div><div class="stat-label">GMP Required</div></div></div></div>
  </div>

  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search country name or code..." x-model="search"></div></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm"><option value="">All Regions</option><option>Asia Pacific</option><option>Africa</option><option>Middle East</option><option>Europe</option></select></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Active</option><option>Restricted</option><option>Blocked</option></select></div>
        <div class="col-6 col-md-2 d-flex gap-1">
          <button class="btn btn-primary btn-sm flex-fill">Filter</button>
          <button class="btn btn-outline-secondary btn-sm" @click="search='';filterStatus=''"><i class="bi bi-x-lg"></i></button>
        </div>
      </div>
    </div>
  </div>

  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Country</th><th>Code</th><th>Region</th><th>Currency</th>
              <th>Import Permitted</th><th>License Req.</th><th>GMP Req.</th>
              <th>Regulatory Authority</th><th>Registered Products</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="c in filtered" :key="c.code">
              <tr>
                <td><span x-text="c.flag"></span> <span class="fw-semibold" style="font-size:13px" x-text="c.name"></span></td>
                <td><span class="badge bg-light text-secondary border" style="font-size:11px" x-text="c.code"></span></td>
                <td style="font-size:13px" x-text="c.region"></td>
                <td style="font-size:13px" x-text="c.currency"></td>
                <td class="text-center"><i :class="c.importOk ? 'bi-check-circle text-success' : 'bi-x-circle text-danger'" class="bi"></i></td>
                <td class="text-center"><i :class="c.licenseReq ? 'bi-check-circle text-warning' : 'bi-dash text-muted'" class="bi"></i></td>
                <td class="text-center"><i :class="c.gmpReq ? 'bi-check-circle text-warning' : 'bi-dash text-muted'" class="bi"></i></td>
                <td style="font-size:12px" x-text="c.authority"></td>
                <td><span class="badge bg-primary rounded-pill" style="font-size:11px" x-text="c.products + ' products'"></span></td>
                <td><span class="badge-status" :class="'badge-' + c.statusClass" x-text="c.status"></span></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function countriesPage() {
  return {
    search:'', filterStatus:'', showAddModal:false,
    countries:[
      { code:'PH', name:'Philippines', flag:'🇵🇭', region:'Asia Pacific', currency:'PHP', importOk:true, licenseReq:true,  gmpReq:true,  authority:'FDA Philippines', products:42, status:'Active',     statusClass:'approved' },
      { code:'NG', name:'Nigeria',     flag:'🇳🇬', region:'Africa',       currency:'NGN', importOk:true, licenseReq:true,  gmpReq:true,  authority:'NAFDAC',          products:31, status:'Active',     statusClass:'approved' },
      { code:'BD', name:'Bangladesh',  flag:'🇧🇩', region:'Asia Pacific', currency:'BDT', importOk:true, licenseReq:true,  gmpReq:true,  authority:'DGDA',             products:18, status:'Active',     statusClass:'approved' },
      { code:'EG', name:'Egypt',       flag:'🇪🇬', region:'Middle East',  currency:'EGP', importOk:true, licenseReq:true,  gmpReq:false, authority:'NAPI',             products:12, status:'Active',     statusClass:'approved' },
      { code:'KE', name:'Kenya',       flag:'🇰🇪', region:'Africa',       currency:'KES', importOk:true, licenseReq:false, gmpReq:false, authority:'PPB Kenya',        products:8,  status:'Active',     statusClass:'approved' },
      { code:'MM', name:'Myanmar',     flag:'🇲🇲', region:'Asia Pacific', currency:'MMK', importOk:true, licenseReq:true,  gmpReq:true,  authority:'FDA Myanmar',      products:3,  status:'Restricted', statusClass:'pending'  },
      { code:'SD', name:'Sudan',       flag:'🇸🇩', region:'Africa',       currency:'SDG', importOk:false,licenseReq:true,  gmpReq:false, authority:'NMPB Sudan',       products:0,  status:'Blocked',    statusClass:'cancelled'},
    ],
    get filtered(){
      return this.countries.filter(c=>{
        const q=this.search.toLowerCase();
        return (!q||c.name.toLowerCase().includes(q)||c.code.toLowerCase().includes(q))
            && (!this.filterStatus||c.status===this.filterStatus);
      });
    }
  };
}
</script>
@endpush
