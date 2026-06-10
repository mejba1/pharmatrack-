@extends('layouts.app')
@section('title', 'Document Vault')

@section('content')
<div x-data="vaultPage()">

  <div class="page-header">
    <div><h1>Document Vault</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Document Vault</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-folder-plus me-1"></i>New Folder</button>
      <button class="btn btn-primary btn-sm" @click="showUploadModal=true"><i class="bi bi-cloud-upload me-1"></i>Upload Document</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-files"></i></div><div><div class="stat-value">1,247</div><div class="stat-label">Total Documents</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-folder2"></i></div><div><div class="stat-value">38</div><div class="stat-label">Folders</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-clock-history"></i></div><div><div class="stat-value">84</div><div class="stat-label">Expiring 90 Days</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-hdd"></i></div><div><div class="stat-value">4.7 GB</div><div class="stat-label">Storage Used</div></div></div></div>
  </div>

  <div class="row g-3">

    <!-- Folder Sidebar -->
    <div class="col-md-3">
      <div class="card">
        <div class="card-body p-0">
          <div class="p-3 border-bottom"><div class="fw-semibold" style="font-size:14px"><i class="bi bi-folder2 me-2 text-warning"></i>Folders</div></div>
          <div class="py-1">
            <template x-for="f in folders" :key="f.id">
              <button class="w-100 text-start px-3 py-2 border-0 d-flex align-items-center gap-2" :class="selectedFolder===f.id ? 'bg-primary bg-opacity-10 text-primary' : 'bg-transparent'" style="font-size:13px" @click="selectedFolder=f.id">
                <i class="bi" :class="selectedFolder===f.id ? 'bi-folder2-open text-warning' : 'bi-folder2 text-warning'"></i>
                <span x-text="f.name" class="flex-fill"></span>
                <span class="badge bg-light text-secondary border" style="font-size:10px" x-text="f.count"></span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Document List -->
    <div class="col-md-9">
      <div class="card mb-3">
        <div class="card-body py-2">
          <div class="row g-2 align-items-center">
            <div class="col-md-5"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search documents..." x-model="search"></div></div>
            <div class="col-6 col-md-2"><select class="form-select form-select-sm"><option>All Types</option><option>PDF</option><option>Word</option><option>Excel</option><option>Image</option></select></div>
            <div class="col-6 col-md-3 d-flex gap-2 align-items-center">
              <button class="btn btn-outline-secondary btn-sm btn-icon" :class="{active: viewMode==='list'}" @click="viewMode='list'"><i class="bi bi-list-ul"></i></button>
              <button class="btn btn-outline-secondary btn-sm btn-icon" :class="{active: viewMode==='grid'}" @click="viewMode='grid'"><i class="bi bi-grid-3x3-gap"></i></button>
            </div>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div x-show="viewMode==='list'" class="card table-card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr><th>Document Name</th><th>Type</th><th>Folder</th><th>Version</th><th>Uploaded</th><th>Expiry</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <template x-for="d in filteredDocs" :key="d.id">
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <span class="doc-type-icon" :class="'doc-icon-' + d.iconType" style="width:30px;height:30px;font-size:13px"><i class="bi" :class="d.icon"></i></span>
                        <div><div class="fw-semibold" style="font-size:13px" x-text="d.name"></div><div class="text-muted-sm" x-text="d.size"></div></div>
                      </div>
                    </td>
                    <td><span class="badge bg-light text-secondary border" style="font-size:11px" x-text="d.type"></span></td>
                    <td style="font-size:12px" x-text="d.folder"></td>
                    <td><span class="badge-status badge-pending" style="font-size:11px" x-text="'v' + d.version"></span></td>
                    <td style="font-size:12px" x-text="d.uploaded"></td>
                    <td>
                      <span x-show="d.expiry" style="font-size:12px" :class="d.expiring ? 'text-warning fw-semibold' : ''" x-text="d.expiry"></span>
                      <span x-show="!d.expiry" class="text-muted" style="font-size:12px">No expiry</span>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <button class="btn btn-outline-primary btn-sm btn-icon" title="Preview"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-outline-secondary btn-sm btn-icon" title="Download"><i class="bi bi-download"></i></button>
                        <button class="btn btn-outline-info btn-sm btn-icon" title="Version history"><i class="bi bi-clock-history"></i></button>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Grid View -->
      <div x-show="viewMode==='grid'" class="row g-3">
        <template x-for="d in filteredDocs" :key="d.id">
          <div class="col-6 col-md-4">
            <div class="card h-100" style="cursor:pointer">
              <div class="card-body text-center p-3">
                <span class="doc-type-icon mx-auto mb-2" :class="'doc-icon-' + d.iconType" style="width:48px;height:48px;font-size:22px;display:flex;align-items:center;justify-content:center"><i class="bi" :class="d.icon"></i></span>
                <div class="fw-semibold text-truncate" style="font-size:12px" x-text="d.name"></div>
                <div class="text-muted-sm mt-1" x-text="d.size + ' · v' + d.version"></div>
                <div class="d-flex justify-content-center gap-1 mt-2">
                  <button class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-eye"></i></button>
                  <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-download"></i></button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

    </div>
  </div>

  <!-- Upload Modal -->
  <div class="modal fade" :class="{show:showUploadModal}" :style="showUploadModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-cloud-upload me-2 text-primary"></i>Upload Document</h5><button class="btn-close" @click="showUploadModal=false"></button></div>
        <div class="modal-body">
          <div class="doc-upload-zone mb-3" @dragover.prevent @drop.prevent>
            <i class="bi bi-cloud-upload" style="font-size:36px;color:var(--primary-light)"></i>
            <div class="fw-semibold mt-2" style="font-size:14px">Drag & drop files here</div>
            <div class="text-muted-sm">or <label class="text-primary cursor-pointer"><u>browse files</u><input type="file" class="d-none" multiple></label></div>
            <div class="text-muted-sm">PDF, Word, Excel, Images — max 50 MB</div>
          </div>
          <div class="row g-3">
            <div class="col-12"><label class="form-label">Folder</label><select class="form-select"><template x-for="f in folders" :key="f.id"><option x-text="f.name"></option></template></select></div>
            <div class="col-md-6"><label class="form-label">Document Type</label><select class="form-select"><option>Certificate</option><option>Registration</option><option>Permit</option><option>License</option><option>Report</option><option>Other</option></select></div>
            <div class="col-md-6"><label class="form-label">Expiry Date (optional)</label><input type="date" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showUploadModal=false">Cancel</button>
          <button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Upload</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showUploadModal" @click="showUploadModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function vaultPage() {
  return {
    search:'', viewMode:'list', selectedFolder:null, showUploadModal:false,
    folders:[
      { id:1, name:'GMP Certificates',      count:24 },
      { id:2, name:'Import Permits',         count:88 },
      { id:3, name:'Product Registrations',  count:312 },
      { id:4, name:'Quality Documents',      count:147 },
      { id:5, name:'Shipping Documents',     count:421 },
      { id:6, name:'Supplier Agreements',    count:38 },
      { id:7, name:'Regulatory Submissions', count:55 },
      { id:8, name:'Audit Reports',          count:162 },
    ],
    docs:[
      { id:1, name:'FDA Philippines GMP Certificate 2026',  type:'PDF',  folder:'GMP Certificates',      iconType:'pdf',   icon:'bi-file-earmark-pdf',   size:'1.2 MB', version:'1', uploaded:'Jan 5, 2026',  expiry:'Jan 4, 2027',  expiring:false },
      { id:2, name:'NAFDAC Import Permit — Nigeria Q2 2026',type:'PDF',  folder:'Import Permits',         iconType:'pdf',   icon:'bi-file-earmark-pdf',   size:'890 KB', version:'2', uploaded:'Mar 18, 2026', expiry:'Jun 30, 2026', expiring:true  },
      { id:3, name:'Amoxil 500mg Product Registration PH',  type:'PDF',  folder:'Product Registrations',  iconType:'pdf',   icon:'bi-file-earmark-pdf',   size:'2.4 MB', version:'3', uploaded:'Feb 1, 2026',  expiry:'Feb 1, 2029',  expiring:false },
      { id:4, name:'Quality Agreement — PharmaCo Mfg',      type:'Word', folder:'Supplier Agreements',    iconType:'word',  icon:'bi-file-earmark-word',  size:'340 KB', version:'1', uploaded:'Nov 10, 2025', expiry:null,          expiring:false },
      { id:5, name:'Batch Testing Report BRN-00142',        type:'Excel',folder:'Quality Documents',      iconType:'excel', icon:'bi-file-earmark-excel', size:'520 KB', version:'1', uploaded:'Jan 16, 2026', expiry:null,          expiring:false },
      { id:6, name:'Packing List SHP-2026-0498',            type:'PDF',  folder:'Shipping Documents',     iconType:'pdf',   icon:'bi-file-earmark-pdf',   size:'180 KB', version:'1', uploaded:'Jun 7, 2026',  expiry:null,          expiring:false },
      { id:7, name:'COA Ciprofloxacin LOT-2601-007',        type:'PDF',  folder:'Quality Documents',      iconType:'pdf',   icon:'bi-file-earmark-pdf',   size:'670 KB', version:'1', uploaded:'Jan 21, 2026', expiry:null,          expiring:false },
      { id:8, name:'DGDA Bangladesh Import License 2026',   type:'PDF',  folder:'Import Permits',         iconType:'pdf',   icon:'bi-file-earmark-pdf',   size:'430 KB', version:'1', uploaded:'Dec 5, 2025',  expiry:'Dec 5, 2026',  expiring:false },
    ],
    get filteredDocs(){
      const q = this.search.toLowerCase();
      return this.docs.filter(d => !q || d.name.toLowerCase().includes(q) || d.folder.toLowerCase().includes(q));
    }
  };
}
</script>
@endpush
