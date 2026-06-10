@extends('layouts.app')
@section('title', 'Proforma Invoices')

@section('content')
<div x-data="piPage()">

  <div class="page-header">
    <div>
      <h1>Proforma Invoices</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Orders / Proforma Invoice</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Issue PI</button>
    </div>
  </div>

  <!-- Doc Flow Banner -->
  <div class="info-box info mb-4">
    <i class="bi bi-diagram-2 text-primary mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px">
      <strong>Order Document Flow:</strong>
      <a href="{{ route('orders.po') }}" class="text-secondary text-decoration-none">PO</a> →
      <a href="{{ route('orders.so') }}" class="text-secondary text-decoration-none">SO</a> →
      <span class="text-primary fw-semibold">PI</span> →
      <a href="{{ route('orders.ci') }}" class="text-secondary text-decoration-none">CI</a> →
      <a href="{{ route('shipments') }}" class="text-secondary text-decoration-none">Shipment</a>.
      Finance must approve the PI before a Commercial Invoice can be raised.
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-value">91</div><div class="stat-label">Total PIs</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value">23</div><div class="stat-label">Pending Approval</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">58</div><div class="stat-label">Approved</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div><div class="stat-value">10</div><div class="stat-label">Rejected</div></div></div></div>
  </div>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search PI number, customer..." x-model="search"></div></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Draft</option><option>Sent</option><option>Pending Approval</option><option>Approved</option><option>Rejected</option></select></div>
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
              <th>PI Number</th><th>Linked SO</th><th>Customer</th>
              <th>PI Date</th><th>Valid Until</th><th>Total Value</th>
              <th>Status</th><th>Linked CI</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="pi in filtered" :key="pi.id">
              <tr>
                <td><input type="checkbox" class="form-check-input"></td>
                <td><span class="text-primary fw-semibold cursor-pointer" style="font-size:12px" @click="viewPI(pi)" x-text="pi.id"></span></td>
                <td><a href="{{ route('orders.so') }}" class="text-secondary text-decoration-none" style="font-size:12px" x-text="pi.linkedSo"></a></td>
                <td><div class="fw-semibold" style="font-size:13px" x-text="pi.customer"></div><div class="text-muted-sm" x-text="pi.country"></div></td>
                <td style="font-size:13px" x-text="pi.piDate"></td>
                <td style="font-size:13px" x-text="pi.validUntil"></td>
                <td class="fw-semibold" style="font-size:13px" x-text="pi.value"></td>
                <td><span class="badge-status" :class="'badge-' + pi.statusClass" x-text="pi.status"></span></td>
                <td>
                  <a x-show="pi.linkedCi" href="{{ route('orders.ci') }}" class="text-primary text-decoration-none" style="font-size:12px" x-text="pi.linkedCi"></a>
                  <span x-show="!pi.linkedCi" class="text-muted-sm">—</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewPI(pi)"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-success btn-sm btn-icon" title="Approve" x-show="pi.status==='Pending Approval'"><i class="bi bi-check2"></i></button>
                    <button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-file-pdf"></i></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
        <div class="text-muted-sm">Showing <strong x-text="filtered.length"></strong> of <strong>91</strong> proforma invoices</div>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item disabled"><a class="page-link">Prev</a></li>
          <li class="page-item active"><a class="page-link">1</a></li>
          <li class="page-item"><a class="page-link">2</a></li>
          <li class="page-item"><a class="page-link">Next</a></li>
        </ul></nav>
      </div>
    </div>
  </div>

  <!-- View PI Modal -->
  <div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content" x-show="selectedPI">
        <div class="modal-header">
          <div><h5 class="modal-title fw-semibold" x-text="selectedPI?.id"></h5><div class="text-muted-sm">Proforma Invoice</div></div>
          <div class="ms-auto d-flex gap-2 align-items-center">
            <span class="badge-status" :class="'badge-' + selectedPI?.statusClass" x-text="selectedPI?.status"></span>
            <button class="btn-close ms-2" @click="showViewModal=false"></button>
          </div>
        </div>
        <div class="modal-doc-tabs">
          <button :class="{active: viewTab==='details'}" @click="viewTab='details'"><i class="bi bi-file-text"></i>Invoice Details</button>
          <button :class="{active: viewTab==='docs'}" @click="viewTab='docs'">
            <i class="bi bi-paperclip"></i>Reference Documents
            <span class="badge bg-primary rounded-pill py-0 px-1" style="font-size:10px" x-show="(selectedPI?.docs||[]).length>0" x-text="(selectedPI?.docs||[]).length"></span>
          </button>
        </div>
        <div class="modal-body pi-doc-preview" x-show="viewTab==='details'">
          <div class="row g-3">
            <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">BILL TO</div><div class="fw-semibold" x-text="selectedPI?.customer"></div><div class="text-muted-sm" x-text="selectedPI?.country"></div></div></div>
            <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">SHIP TO</div><div class="fw-semibold" x-text="selectedPI?.customer"></div><div class="text-muted-sm" x-text="selectedPI?.country"></div></div></div>
            <div class="col-md-4">
              <div class="p-3 border rounded-3">
                <div class="text-muted-sm mb-1 fw-semibold">PI DETAILS</div>
                <div style="font-size:13px">Date: <span x-text="selectedPI?.piDate"></span></div>
                <div style="font-size:13px">Valid Until: <span x-text="selectedPI?.validUntil"></span></div>
                <div style="font-size:13px">SO Ref: <span x-text="selectedPI?.linkedSo"></span></div>
              </div>
            </div>
            <div class="col-12">
              <table class="table table-sm border rounded-3 overflow-hidden">
                <thead class="table-light"><tr><th>#</th><th>Product</th><th>PRN</th><th>Batch</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>
                  <template x-for="(line,i) in (selectedPI?.lines||[])" :key="i">
                    <tr><td x-text="i+1"></td><td class="fw-semibold" style="font-size:13px" x-text="line.product"></td><td class="text-muted-sm" x-text="line.prn"></td><td class="text-muted-sm" x-text="line.batch"></td><td x-text="line.qty.toLocaleString()"></td><td x-text="line.unitPrice"></td><td class="fw-semibold" x-text="line.total"></td></tr>
                  </template>
                  <tr class="table-light"><td colspan="6" class="text-end fw-semibold">Grand Total</td><td class="fw-bold text-primary" x-text="selectedPI?.value"></td></tr>
                </tbody>
              </table>
            </div>
            <div class="col-12">
              <div class="p-3 border rounded-3 bg-light">
                <div class="fw-semibold mb-2" style="font-size:13px">Bank Wire Transfer Details</div>
                <div class="row g-2" style="font-size:12px">
                  <div class="col-md-3"><div class="text-muted-sm">Bank Name</div><div class="fw-semibold">Citibank N.A.</div></div>
                  <div class="col-md-3"><div class="text-muted-sm">Account No.</div><div class="fw-semibold">4021-887-001</div></div>
                  <div class="col-md-3"><div class="text-muted-sm">SWIFT Code</div><div class="fw-semibold">CITIUS33XXX</div></div>
                  <div class="col-md-3"><div class="text-muted-sm">IBAN</div><div class="fw-semibold">US29 CITI 0001 4021 887 001</div></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-body" x-show="viewTab==='docs'">
          <div class="doc-upload-zone mb-3" :class="{dragging:dragging}" @dragover.prevent="dragging=true" @dragleave.prevent="dragging=false" @drop.prevent="dragging=false" @click="$refs.piFile.click()">
            <input type="file" x-ref="piFile" class="d-none" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
            <i class="bi bi-cloud-arrow-up doc-upload-icon"></i>
            <div class="fw-semibold mb-1" style="font-size:14px">Drag &amp; drop reference documents here</div>
            <div class="text-muted-sm mb-2">Finance approvals, COAs, purchase confirmations · Max 10 MB</div>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold" style="font-size:13px">Attached Documents <span class="text-muted-sm" x-text="'(' + (selectedPI?.docs||[]).length + ' files)'"></span></div>
            <button class="btn btn-outline-primary btn-sm" @click="$refs.piFile.click()"><i class="bi bi-cloud-upload me-1"></i>Upload File</button>
          </div>
          <div x-show="!(selectedPI?.docs||[]).length" class="text-center py-5">
            <i class="bi bi-folder2-open" style="font-size:40px;opacity:0.3;display:block;margin-bottom:10px"></i>
            <div class="text-muted-sm">No reference documents attached yet.</div>
          </div>
          <div class="d-flex flex-column gap-2">
            <template x-for="(doc,i) in (selectedPI?.docs||[])" :key="i">
              <div class="doc-item">
                <div class="doc-type-icon" :class="doc.iconClass"><i class="bi" :class="doc.icon"></i></div>
                <div class="flex-grow-1" style="min-width:0">
                  <div class="fw-semibold text-truncate" style="font-size:13px" x-text="doc.name"></div>
                  <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                    <span class="badge bg-light text-secondary border" style="font-size:10px" x-text="doc.category"></span>
                    <span class="text-muted-sm" x-text="doc.size + ' · ' + doc.uploadedBy + ' · ' + doc.date"></span>
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
          <button class="btn btn-warning btn-sm" x-show="selectedPI?.status==='Draft'"><i class="bi bi-send me-1"></i>Send to Finance</button>
          <button class="btn btn-success btn-sm" x-show="selectedPI?.status==='Pending Approval'"><i class="bi bi-check2 me-1"></i>Approve PI</button>
          <a href="{{ route('orders.ci') }}" class="btn btn-primary btn-sm" x-show="selectedPI?.status==='Approved'"><i class="bi bi-file-earmark-check me-1"></i>Raise Commercial Invoice</a>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  <!-- Issue PI Modal -->
  <div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-receipt me-2 text-primary"></i>Issue Proforma Invoice</h5><button class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Linked Sales Order <span class="text-danger">*</span></label><select class="form-select"><option>Select confirmed SO...</option><option>SO-2026-0241 — PharmaDist PH</option><option>SO-2026-0221 — EA Health Supplies</option></select></div>
            <div class="col-md-6"><label class="form-label">PI Date</label><input type="date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Valid Until</label><input type="date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Payment Terms</label><select class="form-select"><option>T/T 30 days</option><option>Letter of Credit</option><option>Advance Payment</option></select></div>
            <div class="col-md-6"><label class="form-label">Incoterms</label><select class="form-select"><option>FOB</option><option>CIF</option><option>EXW</option></select></div>
            <div class="col-md-6"><label class="form-label">Currency</label><select class="form-select"><option>USD</option><option>EUR</option></select></div>
            <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showAddModal=false">Cancel</button>
          <button class="btn btn-outline-primary"><i class="bi bi-floppy me-1"></i>Save Draft</button>
          <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Send for Approval</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function piPage() {
  return {
    search:'', filterStatus:'', showAddModal:false, showViewModal:false, selectedPI:null,
    viewTab:'details', dragging:false,
    pis:[
      { id:'PI-2026-0091', linkedSo:'SO-2026-0241', customer:'PharmaDist PH Ltd.', country:'Philippines', piDate:'Jun 5, 2026', validUntil:'Jul 5, 2026', value:'$48,500', status:'Approved', statusClass:'approved', linkedCi:'CI-2026-0072',
        lines:[
          {product:'Amoxil 500mg Capsules',        prn:'PRN-US-ANT-00142', batch:'BRN-00142-2601-003', qty:5000,  unitPrice:'$1.20', total:'$6,000'},
          {product:'Ciprofloxacin 250mg Tablets',  prn:'PRN-US-ANT-00178', batch:'BRN-00178-2601-007', qty:4000,  unitPrice:'$2.80', total:'$11,200'},
          {product:'Paracetamol 500mg Tablets',    prn:'PRN-US-ANT-00095', batch:'BRN-00095-2602-011', qty:20000, unitPrice:'$0.65', total:'$13,000'},
          {product:'Azithromycin 500mg Tablets',   prn:'PRN-US-ANT-00203', batch:'BRN-00203-2601-002', qty:3000,  unitPrice:'$6.10', total:'$18,300'},
        ],
        docs:[
          {name:'Finance-Approval-Email.pdf', category:'Finance Approval', size:'82 KB',  uploadedBy:'Finance Dept',     date:'Jun 6, 2026', icon:'bi-file-pdf',  iconClass:'pdf'},
          {name:'COA-BRN-00142.pdf',          category:'COA',              size:'198 KB', uploadedBy:'QC Dept',          date:'Jun 4, 2026', icon:'bi-file-pdf',  iconClass:'pdf'},
          {name:'COA-BRN-00178.pdf',          category:'COA',              size:'211 KB', uploadedBy:'QC Dept',          date:'Jun 4, 2026', icon:'bi-file-pdf',  iconClass:'pdf'},
        ]},
      { id:'PI-2026-0088', linkedSo:'SO-2026-0238', customer:'BD MedCo Ltd.',    country:'Bangladesh',  piDate:'Jun 2, 2026', validUntil:'Jul 2, 2026', value:'$22,400', status:'Pending Approval', statusClass:'pending',  linkedCi:'', lines:[], docs:[]},
      { id:'PI-2026-0081', linkedSo:'SO-2026-0215', customer:'EG Pharma Group',  country:'Egypt',       piDate:'May 12, 2026',validUntil:'Jun 12, 2026',value:'$11,000', status:'Rejected',        statusClass:'cancelled',linkedCi:'', lines:[], docs:[]},
    ],
    viewPI(pi){ this.selectedPI=pi; this.showViewModal=true; this.viewTab='details'; },
    get filtered(){
      return this.pis.filter(p=>{
        const q=this.search.toLowerCase();
        return (!q||p.id.toLowerCase().includes(q)||p.customer.toLowerCase().includes(q))
            && (!this.filterStatus||p.status===this.filterStatus);
      });
    }
  };
}
</script>
@endpush
