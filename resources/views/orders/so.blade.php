@extends('layouts.app')
@section('title', 'Sales Orders')

@section('content')
<div x-data="soPage()">

  <div class="page-header">
    <div>
      <h1>Sales Orders</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Orders / Sales Orders</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Create SO</button>
    </div>
  </div>

  <!-- Doc Flow Banner -->
  <div class="info-box info mb-4">
    <i class="bi bi-diagram-2 text-primary mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px">
      <strong>Order Document Flow:</strong>
      <a href="{{ route('orders.po') }}" class="text-secondary text-decoration-none">PO</a> →
      <span class="text-primary fw-semibold">SO</span> →
      <a href="{{ route('orders.pi') }}" class="text-secondary text-decoration-none">PI</a> →
      <a href="{{ route('orders.ci') }}" class="text-secondary text-decoration-none">CI</a> →
      <a href="{{ route('shipments') }}" class="text-secondary text-decoration-none">Shipment</a>.
      A PI cannot be issued without a confirmed Sales Order.
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-bag-check"></i></div><div><div class="stat-value">241</div><div class="stat-label">Total SOs</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value">48</div><div class="stat-label">Draft</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">168</div><div class="stat-label">Confirmed</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-value">25</div><div class="stat-label">PI Issued</div></div></div></div>
  </div>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search SO number, customer..." x-model="search"></div></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Draft</option><option>Confirmed</option><option>PI Issued</option><option>Cancelled</option></select></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm"><option value="">Date: All</option><option>This Week</option><option>This Month</option></select></div>
        <div class="col-6 col-md-2 d-flex gap-1">
          <button class="btn btn-primary btn-sm flex-fill">Filter</button>
          <button class="btn btn-outline-secondary btn-sm" @click="search='';filterStatus=''"><i class="bi bi-x-lg"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th><input type="checkbox" class="form-check-input"></th>
              <th>SO Number</th><th>Linked PO</th><th>Customer</th>
              <th>Products</th><th>SO Date</th><th>Total Value</th>
              <th>Status</th><th>Linked PI</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="so in filtered" :key="so.id">
              <tr>
                <td><input type="checkbox" class="form-check-input"></td>
                <td><span class="text-primary fw-semibold cursor-pointer" style="font-size:12px" @click="viewSO(so)" x-text="so.id"></span></td>
                <td><a href="{{ route('orders.po') }}" class="text-secondary text-decoration-none" style="font-size:12px" x-text="so.linkedPo"></a></td>
                <td><div class="fw-semibold" style="font-size:13px" x-text="so.customer"></div><div class="text-muted-sm" x-text="so.country"></div></td>
                <td style="font-size:13px" x-text="so.products"></td>
                <td style="font-size:13px" x-text="so.soDate"></td>
                <td class="fw-semibold" style="font-size:13px" x-text="so.value"></td>
                <td><span class="badge-status" :class="'badge-' + so.statusClass" x-text="so.status"></span></td>
                <td>
                  <a x-show="so.linkedPi" href="{{ route('orders.pi') }}" class="text-primary text-decoration-none" style="font-size:12px" x-text="so.linkedPi"></a>
                  <span x-show="!so.linkedPi" class="text-muted-sm">—</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewSO(so)"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-success btn-sm btn-icon" title="Issue PI" x-show="so.status==='Confirmed'"><i class="bi bi-receipt"></i></button>
                    <button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-file-pdf"></i></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
        <div class="text-muted-sm">Showing <strong x-text="filtered.length"></strong> of <strong>241</strong> sales orders</div>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item disabled"><a class="page-link">Prev</a></li>
          <li class="page-item active"><a class="page-link">1</a></li>
          <li class="page-item"><a class="page-link">2</a></li>
          <li class="page-item"><a class="page-link">Next</a></li>
        </ul></nav>
      </div>
    </div>
  </div>

  <!-- View SO Modal -->
  <div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content" x-show="selectedSO">
        <div class="modal-header">
          <div><h5 class="modal-title fw-semibold" x-text="selectedSO?.id"></h5><div class="text-muted-sm">Sales Order</div></div>
          <div class="ms-auto d-flex gap-2 align-items-center">
            <span class="badge-status" :class="'badge-' + selectedSO?.statusClass" x-text="selectedSO?.status"></span>
            <button class="btn-close ms-2" @click="showViewModal=false"></button>
          </div>
        </div>
        <div class="modal-doc-tabs">
          <button :class="{active: viewTab==='details'}" @click="viewTab='details'"><i class="bi bi-file-text"></i>Order Details</button>
          <button :class="{active: viewTab==='docs'}" @click="viewTab='docs'">
            <i class="bi bi-paperclip"></i>Reference Documents
            <span class="badge bg-primary rounded-pill py-0 px-1" style="font-size:10px" x-show="(selectedSO?.docs||[]).length>0" x-text="(selectedSO?.docs||[]).length"></span>
          </button>
        </div>
        <div class="modal-body" x-show="viewTab==='details'">
          <div class="row g-3">
            <div class="col-md-6"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">SOLD TO</div><div class="fw-semibold" x-text="selectedSO?.customer"></div><div class="text-muted-sm" x-text="selectedSO?.country"></div></div></div>
            <div class="col-md-6"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">REFERENCE</div><div style="font-size:13px">PO: <a href="{{ route('orders.po') }}" class="text-primary text-decoration-none" x-text="selectedSO?.linkedPo"></a></div><div style="font-size:13px">SO Date: <span x-text="selectedSO?.soDate"></span></div></div></div>
            <div class="col-12">
              <table class="table table-sm border rounded-3 overflow-hidden">
                <thead class="table-light"><tr><th>#</th><th>Product</th><th>PRN</th><th>Batch</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>
                  <template x-for="(line,i) in (selectedSO?.lines||[])" :key="i">
                    <tr><td x-text="i+1"></td><td class="fw-semibold" style="font-size:13px" x-text="line.product"></td><td class="text-muted-sm" x-text="line.prn"></td><td class="text-muted-sm" x-text="line.batch"></td><td x-text="line.qty.toLocaleString()"></td><td x-text="line.unitPrice"></td><td class="fw-semibold" x-text="line.total"></td></tr>
                  </template>
                  <tr class="table-light"><td colspan="6" class="text-end fw-semibold">Grand Total</td><td class="fw-bold text-primary" x-text="selectedSO?.value"></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-body" x-show="viewTab==='docs'">
          <div class="doc-upload-zone mb-3" :class="{dragging:dragging}" @dragover.prevent="dragging=true" @dragleave.prevent="dragging=false" @drop.prevent="dragging=false" @click="$refs.soFile.click()">
            <input type="file" x-ref="soFile" class="d-none" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
            <i class="bi bi-cloud-arrow-up doc-upload-icon"></i>
            <div class="fw-semibold mb-1" style="font-size:14px">Drag &amp; drop reference documents here</div>
            <div class="text-muted-sm mb-2">or click to browse · PDF, Word, Excel, Images · Max 10 MB</div>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold" style="font-size:13px">Attached Documents <span class="text-muted-sm" x-text="'(' + (selectedSO?.docs||[]).length + ' files)'"></span></div>
            <button class="btn btn-outline-primary btn-sm" @click="$refs.soFile.click()"><i class="bi bi-cloud-upload me-1"></i>Upload File</button>
          </div>
          <div x-show="!(selectedSO?.docs||[]).length" class="text-center py-5">
            <i class="bi bi-folder2-open" style="font-size:40px;opacity:0.3;display:block;margin-bottom:10px"></i>
            <div class="text-muted-sm">No reference documents attached yet.</div>
          </div>
          <div class="d-flex flex-column gap-2">
            <template x-for="(doc,i) in (selectedSO?.docs||[])" :key="i">
              <div class="doc-item">
                <div class="doc-type-icon" :class="doc.iconClass"><i class="bi" :class="doc.icon"></i></div>
                <div class="flex-grow-1" style="min-width:0">
                  <div class="fw-semibold text-truncate" style="font-size:13px" x-text="doc.name"></div>
                  <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                    <span class="badge bg-light text-secondary border" style="font-size:10px" x-text="doc.category"></span>
                    <span class="text-muted-sm" x-text="doc.size"></span>
                    <span class="text-muted-sm">· <span x-text="doc.uploadedBy"></span> · <span x-text="doc.date"></span></span>
                  </div>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                  <button class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-eye"></i></button>
                  <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-download"></i></button>
                  <button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-trash"></i></button>
                </div>
              </div>
            </template>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
          <button class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>Download PDF</button>
          <a href="{{ route('orders.pi') }}" class="btn btn-primary btn-sm" x-show="selectedSO?.status==='Confirmed'"><i class="bi bi-receipt me-1"></i>Issue Proforma Invoice</a>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  <!-- Create SO Modal -->
  <div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-bag-check me-2 text-primary"></i>Create Sales Order</h5><button class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12"><div class="info-box warn"><i class="bi bi-exclamation-triangle text-warning mt-1"></i><div style="font-size:13px">A Sales Order requires a linked and acknowledged Purchase Order.</div></div></div>
            <div class="col-md-6"><label class="form-label">Linked PO <span class="text-danger">*</span></label><select class="form-select"><option>Select acknowledged PO...</option><option>PO-2026-0318 — PharmaDist PH</option><option>PO-2026-0299 — EA Health Supplies</option></select></div>
            <div class="col-md-6"><label class="form-label">SO Date <span class="text-danger">*</span></label><input type="date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Incoterms</label><select class="form-select"><option>FOB</option><option>CIF</option><option>EXW</option><option>DDP</option></select></div>
            <div class="col-md-6"><label class="form-label">Payment Terms</label><select class="form-select"><option>30 days net</option><option>LC</option><option>Prepaid</option></select></div>
            <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showAddModal=false">Cancel</button>
          <button class="btn btn-outline-primary"><i class="bi bi-floppy me-1"></i>Save Draft</button>
          <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Confirm SO</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function soPage() {
  return {
    search:'', filterStatus:'', showAddModal:false, showViewModal:false, selectedSO:null,
    viewTab:'details', dragging:false,
    sos:[
      { id:'SO-2026-0241', linkedPo:'PO-2026-0318', customer:'PharmaDist PH Ltd.',  country:'Philippines', products:'3 SKUs / 15,000 units', soDate:'Jun 3, 2026',  value:'$48,500', status:'Confirmed',  statusClass:'approved', linkedPi:'PI-2026-0091',
        lines:[{product:'Amoxil 500',prn:'PRN-US-ANT-00142',batch:'BRN-00142-2601-003',qty:5000,unitPrice:'$1.20',total:'$6,000'}],
        docs:[
          {name:'SO-2026-0241-Confirmed.pdf',  category:'SO Confirmation', size:'192 KB', uploadedBy:'Sarah Admin',    date:'Jun 3, 2026',  icon:'bi-file-pdf',  iconClass:'pdf'},
          {name:'Export-Licence-PH-2026.pdf',  category:'Export Licence',  size:'310 KB', uploadedBy:'Legal Dept',    date:'Jun 2, 2026',  icon:'bi-file-pdf',  iconClass:'pdf'},
          {name:'Customer-Compliance-Cert.pdf',category:'Compliance Cert', size:'245 KB', uploadedBy:'Compliance Dept',date:'Jun 3, 2026', icon:'bi-file-pdf',  iconClass:'pdf'},
        ]},
      { id:'SO-2026-0238', linkedPo:'PO-2026-0315', customer:'BD MedCo Ltd.',        country:'Bangladesh',  products:'2 SKUs / 8,000 units',  soDate:'Jun 1, 2026',  value:'$22,400', status:'PI Issued',  statusClass:'shipped',  linkedPi:'PI-2026-0088',
        lines:[{product:'Insulax R',prn:'PRN-BD-INJ-00089',batch:'BRN-00089-2603-001',qty:8000,unitPrice:'$2.80',total:'$22,400'}],
        docs:[{name:'SO-2026-0238-Confirmed.pdf',category:'SO Confirmation',size:'175 KB',uploadedBy:'Sarah Admin',date:'Jun 1, 2026',icon:'bi-file-pdf',iconClass:'pdf'}]},
      { id:'SO-2026-0221', linkedPo:'PO-2026-0299', customer:'EA Health Supplies',   country:'Kenya',       products:'2 SKUs / 12,000 units', soDate:'May 15, 2026', value:'$29,800', status:'Confirmed',  statusClass:'approved', linkedPi:'', lines:[], docs:[]},
      { id:'SO-2026-0215', linkedPo:'PO-2026-0291', customer:'EG Pharma Group',      country:'Egypt',       products:'1 SKU / 5,000 units',   soDate:'May 10, 2026', value:'$11,000', status:'Draft',      statusClass:'draft',    linkedPi:'', lines:[], docs:[]},
    ],
    viewSO(so){ this.selectedSO=so; this.showViewModal=true; this.viewTab='details'; },
    get filtered(){
      return this.sos.filter(s=>{
        const q=this.search.toLowerCase();
        return (!q||s.id.toLowerCase().includes(q)||s.customer.toLowerCase().includes(q))
            && (!this.filterStatus||s.status===this.filterStatus);
      });
    }
  };
}
</script>
@endpush
