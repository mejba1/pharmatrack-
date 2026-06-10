@extends('layouts.app')
@section('title', 'Commercial Invoices')

@section('content')
<div x-data="ciPage()">

  <div class="page-header">
    <div>
      <h1>Commercial Invoices</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Orders / Commercial Invoice</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Raise CI</button>
    </div>
  </div>

  <!-- Doc Flow Banner -->
  <div class="info-box info mb-4">
    <i class="bi bi-diagram-2 text-primary mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px">
      <strong>Order Document Flow:</strong>
      <a href="{{ route('orders.po') }}" class="text-secondary text-decoration-none">PO</a> →
      <a href="{{ route('orders.so') }}" class="text-secondary text-decoration-none">SO</a> →
      <a href="{{ route('orders.pi') }}" class="text-secondary text-decoration-none">PI</a> →
      <span class="text-primary fw-semibold">CI</span> →
      <a href="{{ route('shipments') }}" class="text-secondary text-decoration-none">Shipment</a>.
      An approved CI is required before a Shipment can be created.
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div><div><div class="stat-value">72</div><div class="stat-label">Total CIs</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value">18</div><div class="stat-label">Pending Approval</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">41</div><div class="stat-label">Approved</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-truck"></i></div><div><div class="stat-value">13</div><div class="stat-label">Shipment Created</div></div></div></div>
  </div>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search CI number, customer..." x-model="search"></div></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Draft</option><option>Pending Approval</option><option>Approved</option><option>Shipment Created</option><option>Cancelled</option></select></div>
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
              <th>CI Number</th><th>Linked PI</th><th>Customer</th>
              <th>CI Date</th><th>HS Code</th><th>Total Value</th>
              <th>Status</th><th>Shipment</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="ci in filtered" :key="ci.id">
              <tr>
                <td><input type="checkbox" class="form-check-input"></td>
                <td><span class="text-primary fw-semibold cursor-pointer" style="font-size:12px" @click="viewCI(ci)" x-text="ci.id"></span></td>
                <td><a href="{{ route('orders.pi') }}" class="text-secondary text-decoration-none" style="font-size:12px" x-text="ci.linkedPi"></a></td>
                <td><div class="fw-semibold" style="font-size:13px" x-text="ci.customer"></div><div class="text-muted-sm" x-text="ci.country"></div></td>
                <td style="font-size:13px" x-text="ci.ciDate"></td>
                <td><code style="font-size:11px" x-text="ci.hsCode"></code></td>
                <td class="fw-semibold" style="font-size:13px" x-text="ci.value"></td>
                <td><span class="badge-status" :class="'badge-' + ci.statusClass" x-text="ci.status"></span></td>
                <td>
                  <a x-show="ci.linkedShipment" href="{{ route('shipments') }}" class="text-primary text-decoration-none" style="font-size:12px" x-text="ci.linkedShipment"></a>
                  <span x-show="!ci.linkedShipment" class="text-muted-sm">—</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewCI(ci)"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-success btn-sm btn-icon" title="Approve" x-show="ci.status==='Pending Approval'"><i class="bi bi-check2"></i></button>
                    <button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-file-pdf"></i></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
        <div class="text-muted-sm">Showing <strong x-text="filtered.length"></strong> of <strong>72</strong> commercial invoices</div>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item disabled"><a class="page-link">Prev</a></li>
          <li class="page-item active"><a class="page-link">1</a></li>
          <li class="page-item"><a class="page-link">2</a></li>
          <li class="page-item"><a class="page-link">Next</a></li>
        </ul></nav>
      </div>
    </div>
  </div>

  <!-- View CI Modal -->
  <div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content" x-show="selectedCI">
        <div class="modal-header">
          <div><h5 class="modal-title fw-semibold" x-text="selectedCI?.id"></h5><div class="text-muted-sm">Commercial Invoice</div></div>
          <div class="ms-auto d-flex gap-2 align-items-center">
            <span class="badge-status" :class="'badge-' + selectedCI?.statusClass" x-text="selectedCI?.status"></span>
            <button class="btn-close ms-2" @click="showViewModal=false"></button>
          </div>
        </div>
        <div class="modal-doc-tabs">
          <button :class="{active: viewTab==='details'}" @click="viewTab='details'"><i class="bi bi-file-text"></i>Invoice Details</button>
          <button :class="{active: viewTab==='docs'}" @click="viewTab='docs'">
            <i class="bi bi-paperclip"></i>Reference Documents
            <span class="badge bg-primary rounded-pill py-0 px-1" style="font-size:10px" x-show="(selectedCI?.docs||[]).length>0" x-text="(selectedCI?.docs||[]).length"></span>
          </button>
        </div>
        <div class="modal-body pi-doc-preview" x-show="viewTab==='details'">
          <div class="row g-3">
            <div class="col-md-6"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">EXPORTER</div><div class="fw-semibold">PharmaCo Manufacturing Ltd.</div><div class="text-muted-sm">123 Industrial Park, Newark, NJ 07101, USA</div></div></div>
            <div class="col-md-6"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">CONSIGNEE</div><div class="fw-semibold" x-text="selectedCI?.customer"></div><div class="text-muted-sm" x-text="selectedCI?.country"></div></div></div>
            <div class="col-md-3"><label class="form-label">CI Date</label><div x-text="selectedCI?.ciDate"></div></div>
            <div class="col-md-3"><label class="form-label">HS Code</label><code x-text="selectedCI?.hsCode"></code></div>
            <div class="col-md-3"><label class="form-label">Country of Origin</label><div>United States</div></div>
            <div class="col-md-3"><label class="form-label">Incoterms</label><div>FOB</div></div>
            <div class="col-12">
              <table class="table table-sm border rounded-3 overflow-hidden">
                <thead class="table-light"><tr><th>#</th><th>Product Description</th><th>PRN</th><th>Batch</th><th>Qty</th><th>Unit Price</th><th>Net Wt (kg)</th><th>Total</th></tr></thead>
                <tbody>
                  <template x-for="(line,i) in (selectedCI?.lines||[])" :key="i">
                    <tr><td x-text="i+1"></td><td class="fw-semibold" style="font-size:13px" x-text="line.product"></td><td class="text-muted-sm" x-text="line.prn"></td><td class="text-muted-sm" x-text="line.batch"></td><td x-text="line.qty.toLocaleString()"></td><td x-text="line.unitPrice"></td><td x-text="line.netWt"></td><td class="fw-semibold" x-text="line.total"></td></tr>
                  </template>
                  <tr class="table-light"><td colspan="7" class="text-end fw-semibold">Total Invoice Value</td><td class="fw-bold text-primary" x-text="selectedCI?.value"></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-body" x-show="viewTab==='docs'">
          <div class="doc-upload-zone mb-3" :class="{dragging:dragging}" @dragover.prevent="dragging=true" @dragleave.prevent="dragging=false" @drop.prevent="dragging=false" @click="$refs.ciFile.click()">
            <input type="file" x-ref="ciFile" class="d-none" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
            <i class="bi bi-cloud-arrow-up doc-upload-icon"></i>
            <div class="fw-semibold mb-1" style="font-size:14px">Drag &amp; drop reference documents here</div>
            <div class="text-muted-sm mb-2">Packing list, COA, certificate of origin, customs docs · Max 10 MB</div>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold" style="font-size:13px">Attached Documents <span class="text-muted-sm" x-text="'(' + (selectedCI?.docs||[]).length + ' files)'"></span></div>
            <button class="btn btn-outline-primary btn-sm" @click="$refs.ciFile.click()"><i class="bi bi-cloud-upload me-1"></i>Upload File</button>
          </div>
          <div x-show="!(selectedCI?.docs||[]).length" class="text-center py-5">
            <i class="bi bi-folder2-open" style="font-size:40px;opacity:0.3;display:block;margin-bottom:10px"></i>
            <div class="text-muted-sm">No reference documents attached yet.</div>
          </div>
          <div class="d-flex flex-column gap-2">
            <template x-for="(doc,i) in (selectedCI?.docs||[])" :key="i">
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
          <button class="btn btn-success btn-sm" x-show="selectedCI?.status==='Pending Approval'"><i class="bi bi-check2 me-1"></i>Approve CI</button>
          <a href="{{ route('shipments') }}" class="btn btn-primary btn-sm" x-show="selectedCI?.status==='Approved'"><i class="bi bi-truck me-1"></i>Create Shipment</a>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  <!-- Raise CI Modal -->
  <div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Raise Commercial Invoice</h5><button class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Linked Proforma Invoice <span class="text-danger">*</span></label><select class="form-select"><option>Select approved PI...</option><option>PI-2026-0091 — PharmaDist PH</option></select></div>
            <div class="col-md-6"><label class="form-label">CI Date</label><input type="date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">HS Code <span class="text-danger">*</span></label><input type="text" class="form-control" placeholder="e.g. 3004.20.10"></div>
            <div class="col-md-4"><label class="form-label">Country of Origin</label><input type="text" class="form-control" value="US"></div>
            <div class="col-md-4"><label class="form-label">Incoterms</label><select class="form-select"><option>FOB</option><option>CIF</option><option>EXW</option></select></div>
            <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showAddModal=false">Cancel</button>
          <button class="btn btn-outline-primary"><i class="bi bi-floppy me-1"></i>Save Draft</button>
          <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit for Approval</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function ciPage() {
  return {
    search:'', filterStatus:'', showAddModal:false, showViewModal:false, selectedCI:null,
    viewTab:'details', dragging:false,
    cis:[
      { id:'CI-2026-0072', linkedPi:'PI-2026-0091', customer:'PharmaDist PH Ltd.', country:'Philippines', ciDate:'Jun 7, 2026', hsCode:'3004.20.10', value:'$48,500', status:'Approved', statusClass:'approved', linkedShipment:'SHP-2026-0498',
        lines:[
          {product:'Amoxil 500mg Capsules',       prn:'PRN-US-ANT-00142', batch:'BRN-00142-2601-003', qty:5000,  unitPrice:'$1.20', netWt:'22.5 kg',  total:'$6,000'},
          {product:'Ciprofloxacin 250mg Tablets', prn:'PRN-US-ANT-00178', batch:'BRN-00178-2601-007', qty:4000,  unitPrice:'$2.80', netWt:'18.0 kg',  total:'$11,200'},
          {product:'Paracetamol 500mg Tablets',   prn:'PRN-US-ANT-00095', batch:'BRN-00095-2602-011', qty:20000, unitPrice:'$0.65', netWt:'90.0 kg',  total:'$13,000'},
          {product:'Azithromycin 500mg Tablets',  prn:'PRN-US-ANT-00203', batch:'BRN-00203-2601-002', qty:3000,  unitPrice:'$6.10', netWt:'12.0 kg',  total:'$18,300'},
        ],
        docs:[
          {name:'CI-2026-0072-Signed.pdf',          category:'Commercial Invoice', size:'312 KB', uploadedBy:'Finance Dept',   date:'Jun 7, 2026',  icon:'bi-file-pdf',    iconClass:'pdf'},
          {name:'Packing-List-SHP-PH.xlsx',         category:'Packing List',       size:'88 KB',  uploadedBy:'Logistics Dept', date:'Jun 7, 2026',  icon:'bi-file-excel',  iconClass:'excel'},
          {name:'Certificate-of-Origin-US.pdf',     category:'Cert. of Origin',    size:'198 KB', uploadedBy:'Legal Dept',     date:'Jun 6, 2026',  icon:'bi-file-pdf',    iconClass:'pdf'},
          {name:'COA-Batch-BRN00142.pdf',           category:'COA',                size:'211 KB', uploadedBy:'QC Dept',        date:'Jun 5, 2026',  icon:'bi-file-pdf',    iconClass:'pdf'},
          {name:'Customs-Declaration-Form.pdf',     category:'Customs',            size:'156 KB', uploadedBy:'Logistics Dept', date:'Jun 8, 2026',  icon:'bi-file-pdf',    iconClass:'pdf'},
        ]},
      { id:'CI-2026-0069', linkedPi:'PI-2026-0088', customer:'BD MedCo Ltd.',     country:'Bangladesh',  ciDate:'Jun 4, 2026', hsCode:'3004.39.10', value:'$22,400', status:'Pending Approval', statusClass:'pending',  linkedShipment:'', lines:[], docs:[]},
      { id:'CI-2026-0061', linkedPi:'PI-2026-0081', customer:'EG Pharma Group',   country:'Egypt',       ciDate:'May 14, 2026',hsCode:'3004.10.10', value:'$11,000', status:'Cancelled',        statusClass:'cancelled',linkedShipment:'', lines:[], docs:[]},
    ],
    viewCI(ci){ this.selectedCI=ci; this.showViewModal=true; this.viewTab='details'; },
    get filtered(){
      return this.cis.filter(c=>{
        const q=this.search.toLowerCase();
        return (!q||c.id.toLowerCase().includes(q)||c.customer.toLowerCase().includes(q))
            && (!this.filterStatus||c.status===this.filterStatus);
      });
    }
  };
}
</script>
@endpush
