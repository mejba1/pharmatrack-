@extends('layouts.app')
@section('title', 'Product Master')

@section('content')
<div x-data="productsPage()">

  <div class="page-header">
    <div><h1>Product Master</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Product Master</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-upload me-1"></i>Import</button>
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Add Product</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-capsule"></i></div><div><div class="stat-value">248</div><div class="stat-label">Total Products</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-value">231</div><div class="stat-label">Active</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value">12</div><div class="stat-label">Under Registration</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div><div class="stat-value">5</div><div class="stat-label">Discontinued</div></div></div></div>
  </div>

  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search by name, PRN, generic name..." x-model="search"></div></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterCategory"><option value="">All Categories</option><option>Tablet</option><option>Capsule</option><option>Injection</option><option>Syrup</option><option>Cream</option><option>Vaccine</option></select></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Active</option><option>Discontinued</option><option>Under Registration</option></select></div>
        <div class="col-6 col-md-2 d-flex gap-1">
          <button class="btn btn-primary btn-sm flex-fill">Filter</button>
          <button class="btn btn-outline-secondary btn-sm" @click="search='';filterCategory='';filterStatus=''"><i class="bi bi-x-lg"></i></button>
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
              <th style="width:40px"><input type="checkbox" class="form-check-input"></th>
              <th>PRN</th><th>Product Name</th><th>Generic (INN)</th><th>Form</th>
              <th>Strength</th><th>Category</th><th>Cold Chain</th><th>Countries</th><th>Status</th><th style="width:100px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="p in filtered" :key="p.prn">
              <tr>
                <td><input type="checkbox" class="form-check-input"></td>
                <td><span class="text-primary fw-semibold" style="font-size:12px" x-text="p.prn"></span></td>
                <td><div class="fw-semibold" style="font-size:13px" x-text="p.name"></div><div class="text-muted-sm" x-text="p.manufacturer"></div></td>
                <td style="font-size:13px" x-text="p.generic"></td>
                <td><span class="badge bg-light text-secondary" style="font-size:11px" x-text="p.form"></span></td>
                <td style="font-size:13px" x-text="p.strength"></td>
                <td style="font-size:13px" x-text="p.category"></td>
                <td>
                  <span x-show="p.coldChain" class="text-info"><i class="bi bi-thermometer-snow"></i> Yes</span>
                  <span x-show="!p.coldChain" class="text-muted" style="font-size:12px">No</span>
                </td>
                <td><span class="badge bg-primary rounded-pill" style="font-size:11px" x-text="p.countries + ' countries'"></span></td>
                <td><span class="badge-status" :class="'badge-' + p.statusClass" x-text="p.status"></span></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewProduct(p)"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-info btn-sm btn-icon"><i class="bi bi-layers"></i></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
        <div class="text-muted-sm">Showing <strong x-text="filtered.length"></strong> of <strong>248</strong> products</div>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item disabled"><a class="page-link">Prev</a></li>
          <li class="page-item active"><a class="page-link">1</a></li>
          <li class="page-item"><a class="page-link">2</a></li>
          <li class="page-item"><a class="page-link">Next</a></li>
        </ul></nav>
      </div>
    </div>
  </div>

  <!-- View Product Modal -->
  <div class="modal fade" :class="{show: showViewModal}" :style="showViewModal ? 'display:block' : ''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content" x-show="selectedProduct">
        <div class="modal-header"><h5 class="modal-title fw-semibold">Product Details</h5><button class="btn-close" @click="showViewModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><div class="p-3 bg-light rounded-3"><div class="text-muted-sm mb-1">Product Reference Number</div><div class="fw-semibold text-primary" x-text="selectedProduct?.prn"></div></div></div>
            <div class="col-md-6"><div class="p-3 bg-light rounded-3"><div class="text-muted-sm mb-1">Status</div><span class="badge-status" :class="'badge-' + selectedProduct?.statusClass" x-text="selectedProduct?.status"></span></div></div>
            <div class="col-md-6"><label class="form-label">Brand Name</label><div class="fw-semibold" x-text="selectedProduct?.name"></div></div>
            <div class="col-md-6"><label class="form-label">Generic Name (INN)</label><div x-text="selectedProduct?.generic"></div></div>
            <div class="col-md-6"><label class="form-label">Manufacturer</label><div x-text="selectedProduct?.manufacturer"></div></div>
            <div class="col-md-6"><label class="form-label">Dosage Form</label><div x-text="selectedProduct?.form"></div></div>
            <div class="col-md-4"><label class="form-label">Strength</label><div x-text="selectedProduct?.strength"></div></div>
            <div class="col-md-4"><label class="form-label">Therapeutic Category</label><div x-text="selectedProduct?.category"></div></div>
            <div class="col-md-4"><label class="form-label">Cold Chain</label><div x-text="selectedProduct?.coldChain ? 'Required' : 'Not required'"></div></div>
            <div class="col-12"><div class="info-box info"><i class="bi bi-globe2 text-primary mt-1"></i><div><strong x-text="selectedProduct?.countries"></strong> countries authorized. <a href="{{ route('countries') }}" class="text-primary">Manage →</a></div></div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
          <a href="{{ route('batches') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-layers me-1"></i>View Batches</a>
          <button class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Product</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  <!-- Add Product Modal -->
  <div class="modal fade" :class="{show: showAddModal}" :style="showAddModal ? 'display:block' : ''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Product</h5><button class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Brand Name <span class="text-danger">*</span></label><input type="text" class="form-control" placeholder="e.g. Amoxil 500mg Capsules"></div>
            <div class="col-md-6"><label class="form-label">Generic Name (INN) <span class="text-danger">*</span></label><input type="text" class="form-control" placeholder="e.g. Amoxicillin"></div>
            <div class="col-md-6"><label class="form-label">Dosage Form <span class="text-danger">*</span></label><select class="form-select"><option>Tablet</option><option>Capsule</option><option>Injection</option><option>Syrup</option><option>Cream</option></select></div>
            <div class="col-md-6"><label class="form-label">Strength <span class="text-danger">*</span></label><input type="text" class="form-control" placeholder="e.g. 500mg"></div>
            <div class="col-md-6"><label class="form-label">Therapeutic Category</label><input type="text" class="form-control" placeholder="e.g. Antibiotics"></div>
            <div class="col-md-6"><label class="form-label">Cold Chain Required</label><select class="form-select"><option value="0">No</option><option value="1">Yes</option></select></div>
            <div class="col-md-6"><label class="form-label">Unit Cost (USD)</label><input type="number" class="form-control" step="0.0001" placeholder="0.0000"></div>
            <div class="col-md-6"><label class="form-label">Shelf Life (months)</label><input type="number" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showAddModal=false">Cancel</button>
          <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Add Product</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function productsPage() {
  return {
    search:'', filterCategory:'', filterStatus:'', showAddModal:false, showViewModal:false, selectedProduct:null,
    products:[
      { prn:'PRN-US-ANT-00142', name:'Amoxil 500mg Capsules',         generic:'Amoxicillin',      manufacturer:'PharmaCo Mfg', form:'Capsule',  strength:'500mg', category:'Antibiotics',     coldChain:false, countries:24, status:'Active',               statusClass:'approved' },
      { prn:'PRN-US-ANT-00178', name:'Ciprofloxacin 250mg Tablets',   generic:'Ciprofloxacin',    manufacturer:'PharmaCo Mfg', form:'Tablet',   strength:'250mg', category:'Antibiotics',     coldChain:false, countries:18, status:'Active',               statusClass:'approved' },
      { prn:'PRN-BD-INJ-00089', name:'Insulax R 100 IU/mL',          generic:'Insulin Regular',  manufacturer:'PharmaCo Mfg', form:'Injection',strength:'100IU', category:'Endocrinology',   coldChain:true,  countries:12, status:'Active',               statusClass:'approved' },
      { prn:'PRN-US-ANT-00095', name:'Paracetamol 500mg Tablets',     generic:'Paracetamol',      manufacturer:'PharmaCo Mfg', form:'Tablet',   strength:'500mg', category:'Analgesics',      coldChain:false, countries:47, status:'Active',               statusClass:'approved' },
      { prn:'PRN-US-ANT-00203', name:'Azithromycin 500mg Tablets',    generic:'Azithromycin',     manufacturer:'PharmaCo Mfg', form:'Tablet',   strength:'500mg', category:'Antibiotics',     coldChain:false, countries:31, status:'Active',               statusClass:'approved' },
      { prn:'PRN-IN-TAB-00231', name:'Metformin SR 850mg Tablets',    generic:'Metformin HCl',    manufacturer:'PharmaCo Mfg', form:'Tablet',   strength:'850mg', category:'Antidiabetics',   coldChain:false, countries:9,  status:'Under Registration',   statusClass:'pending'  },
      { prn:'PRN-US-VAC-00012', name:'Hepatitis B Vaccine 20mcg/mL', generic:'HBsAg Vaccine',    manufacturer:'PharmaCo Mfg', form:'Injection',strength:'20mcg', category:'Vaccines',        coldChain:true,  countries:6,  status:'Under Registration',   statusClass:'pending'  },
      { prn:'PRN-US-ANT-00067', name:'Tetracycline 250mg Capsules',   generic:'Tetracycline HCl', manufacturer:'PharmaCo Mfg', form:'Capsule',  strength:'250mg', category:'Antibiotics',     coldChain:false, countries:0,  status:'Discontinued',         statusClass:'cancelled'},
    ],
    viewProduct(p){ this.selectedProduct=p; this.showViewModal=true; },
    get filtered(){
      return this.products.filter(p=>{
        const q=this.search.toLowerCase();
        return (!q||p.prn.toLowerCase().includes(q)||p.name.toLowerCase().includes(q)||p.generic.toLowerCase().includes(q))
            && (!this.filterCategory||p.form===this.filterCategory)
            && (!this.filterStatus||p.status===this.filterStatus);
      });
    }
  };
}
</script>
@endpush
