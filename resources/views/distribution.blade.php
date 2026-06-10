@extends('layouts.app')
@section('title', 'Distribution Hierarchy')

@section('content')
<div x-data="distributionPage()">

  <div class="page-header">
    <div><h1>Distribution Hierarchy</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Distribution Hierarchy</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Add Distributor</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-diagram-3"></i></div><div><div class="stat-value">94</div><div class="stat-label">Total Distributors</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-building"></i></div><div><div class="stat-value">12</div><div class="stat-label">Primary Distributors</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-shop"></i></div><div><div class="stat-value">47</div><div class="stat-label">Sub-Distributors</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-hospital"></i></div><div><div class="stat-value">35</div><div class="stat-label">Retail Pharmacies</div></div></div></div>
  </div>

  <!-- View Tabs -->
  <div class="pills-nav mb-3">
    <button class="pill-btn" :class="{active: viewMode==='table'}" @click="viewMode='table'"><i class="bi bi-table me-1"></i>Table View</button>
    <button class="pill-btn" :class="{active: viewMode==='tree'}" @click="viewMode='tree'"><i class="bi bi-diagram-3 me-1"></i>Hierarchy Tree</button>
  </div>

  <!-- Table View -->
  <div x-show="viewMode==='table'" class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Distributor Name</th><th>Type</th><th>Country</th><th>License No.</th>
              <th>GMP Certificate</th><th>Parent Distributor</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="d in distributors" :key="d.id">
              <tr>
                <td><div class="fw-semibold" style="font-size:13px" x-text="d.name"></div><div class="text-muted-sm" x-text="d.contact"></div></td>
                <td><span class="badge bg-light text-secondary border" style="font-size:11px" x-text="d.type"></span></td>
                <td><span x-text="d.flag"></span> <span style="font-size:13px" x-text="d.country"></span></td>
                <td><code style="font-size:11px" x-text="d.license"></code></td>
                <td>
                  <span x-show="d.gmp" class="badge-status badge-approved">Valid</span>
                  <span x-show="!d.gmp" class="badge-status badge-pending">N/A</span>
                </td>
                <td style="font-size:12px" x-text="d.parent || '— (Primary)'"></td>
                <td><span class="badge-status" :class="'badge-' + d.statusClass" x-text="d.status"></span></td>
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

  <!-- Tree View -->
  <div x-show="viewMode==='tree'">
    <div class="card">
      <div class="card-body">
        <p class="text-muted-sm mb-3">Showing distribution hierarchy for all regions. Click a node to view details.</p>
        <template x-for="d in distributors.filter(x=>!x.parent)" :key="d.id">
          <div class="mb-3">
            <div class="d-flex align-items-center gap-2 p-3 border rounded-3">
              <div class="stat-icon stat-primary" style="width:36px;height:36px;font-size:16px;flex-shrink:0"><i class="bi bi-building"></i></div>
              <div>
                <div class="fw-semibold" x-text="d.name"></div>
                <div class="text-muted-sm" x-text="d.type + ' · ' + d.country"></div>
              </div>
              <span class="ms-auto badge-status" :class="'badge-' + d.statusClass" x-text="d.status"></span>
            </div>
            <div class="ps-4 mt-2 d-flex flex-column gap-2">
              <template x-for="sub in distributors.filter(x=>x.parent===d.name)" :key="sub.id">
                <div class="d-flex align-items-center gap-2 p-2 border rounded-3 bg-light">
                  <i class="bi bi-arrow-return-right text-muted"></i>
                  <div class="fw-semibold" style="font-size:13px" x-text="sub.name"></div>
                  <span class="badge bg-light text-secondary border" style="font-size:11px" x-text="sub.type"></span>
                  <span class="text-muted-sm ms-auto" x-text="sub.country"></span>
                </div>
              </template>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function distributionPage() {
  return {
    viewMode: 'table', showAddModal: false,
    distributors:[
      { id:1, name:'PharmaDist PH Ltd.',      type:'Primary Distributor', country:'Philippines', flag:'🇵🇭', license:'FDA-PH-DIS-20230142', gmp:true, parent:'',                   contact:'contact@pharmadistph.com', status:'Active',   statusClass:'approved' },
      { id:2, name:'MedRex Pharma Inc.',       type:'Sub-Distributor',     country:'Philippines', flag:'🇵🇭', license:'FDA-PH-SUB-20240089', gmp:false,parent:'PharmaDist PH Ltd.', contact:'info@medrex.ph',           status:'Active',   statusClass:'approved' },
      { id:3, name:'West Africa Pharma',       type:'Primary Distributor', country:'Nigeria',     flag:'🇳🇬', license:'NAFDAC-DIS-2023-0041', gmp:true, parent:'',                   contact:'ops@wapharma.ng',          status:'Active',   statusClass:'approved' },
      { id:4, name:'Lagos Med Supplies Ltd.',  type:'Sub-Distributor',     country:'Nigeria',     flag:'🇳🇬', license:'NAFDAC-SUB-2024-0112', gmp:false,parent:'West Africa Pharma', contact:'orders@lagosmed.ng',       status:'Active',   statusClass:'approved' },
      { id:5, name:'BD MedCo Ltd.',            type:'Primary Distributor', country:'Bangladesh',  flag:'🇧🇩', license:'DGDA-DIS-2022-0078',   gmp:true, parent:'',                   contact:'admin@bdmedco.com',        status:'Active',   statusClass:'approved' },
      { id:6, name:'EG Pharma Group',          type:'Primary Distributor', country:'Egypt',       flag:'🇪🇬', license:'NAPI-DIS-2021-0033',   gmp:true, parent:'',                   contact:'pharma@egpharma.eg',       status:'Active',   statusClass:'approved' },
      { id:7, name:'EA Health Supplies',       type:'Primary Distributor', country:'Kenya',       flag:'🇰🇪', license:'PPB-DIS-2023-0021',    gmp:false,parent:'',                   contact:'supply@eahealthke.co.ke',  status:'Active',   statusClass:'approved' },
      { id:8, name:'MM Pharma Co.',            type:'Primary Distributor', country:'Myanmar',     flag:'🇲🇲', license:'FDA-MM-DIS-2024-0005', gmp:false,parent:'',                   contact:'info@mmpharma.mm',         status:'Suspended',statusClass:'cancelled'},
    ]
  };
}
</script>
@endpush
