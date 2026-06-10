@extends('layouts.app')
@section('title', 'Batch & Lot Management')

@section('content')
<div x-data="batchesPage()">

  <div class="page-header">
    <div><h1>Batch &amp; Lot Management</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Batch &amp; Lot Mgmt</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>New Batch</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-layers"></i></div><div><div class="stat-value">1,842</div><div class="stat-label">Total Batches</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">1,640</div><div class="stat-label">QC Passed</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div><div><div class="stat-value">48</div><div class="stat-label">Expiring &lt; 90 Days</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div><div class="stat-value">12</div><div class="stat-label">Recalled</div></div></div></div>
  </div>

  <!-- Expiry Alert -->
  <div class="info-box warn mb-4">
    <i class="bi bi-exclamation-triangle-fill text-warning mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px"><strong>Expiry Alert:</strong> 4 batches expire within 30 days. <a href="#" class="text-warning fw-semibold">Review now →</a></div>
  </div>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search BRN, product name, lot number..." x-model="search"></div></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All QC Status</option><option>Passed</option><option>Pending</option><option>Failed</option><option>Recalled</option></select></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm"><option value="">Expiry: All</option><option>&lt; 30 Days</option><option>&lt; 90 Days</option><option>&lt; 180 Days</option></select></div>
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
              <th><input type="checkbox" class="form-check-input"></th>
              <th>BRN</th><th>Product</th><th>Lot No.</th><th>Manufactured</th>
              <th>Expiry</th><th>Qty Produced</th><th>Qty Available</th><th>QC Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="b in filtered" :key="b.brn">
              <tr>
                <td><input type="checkbox" class="form-check-input"></td>
                <td><span class="text-primary fw-semibold" style="font-size:12px" x-text="b.brn"></span></td>
                <td><div class="fw-semibold" style="font-size:13px" x-text="b.product"></div><div class="text-muted-sm" x-text="b.prn"></div></td>
                <td style="font-size:13px" x-text="b.lotNo"></td>
                <td style="font-size:13px" x-text="b.mfgDate"></td>
                <td>
                  <div :class="b.daysLeft < 30 ? 'text-danger fw-semibold' : b.daysLeft < 90 ? 'text-warning fw-semibold' : ''" style="font-size:13px" x-text="b.expiry"></div>
                  <div class="text-muted-sm" x-text="b.daysLeft + ' days left'"></div>
                </td>
                <td style="font-size:13px" x-text="b.qtyProduced.toLocaleString()"></td>
                <td style="font-size:13px" x-text="b.qtyAvail.toLocaleString()"></td>
                <td><span class="badge-status" :class="'badge-' + b.qcClass" x-text="b.qcStatus"></span></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-info btn-sm btn-icon" title="COA"><i class="bi bi-file-earmark-check"></i></button>
                    <button class="btn btn-outline-danger btn-sm btn-icon" title="Recall"><i class="bi bi-exclamation-triangle"></i></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
        <div class="text-muted-sm">Showing <strong x-text="filtered.length"></strong> of <strong>1,842</strong> batches</div>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item disabled"><a class="page-link">Prev</a></li>
          <li class="page-item active"><a class="page-link">1</a></li>
          <li class="page-item"><a class="page-link">2</a></li>
          <li class="page-item"><a class="page-link">Next</a></li>
        </ul></nav>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function batchesPage() {
  return {
    search:'', filterStatus:'', showAddModal:false,
    batches:[
      { brn:'BRN-00142-2601-003', product:'Amoxil 500mg Capsules',        prn:'PRN-US-ANT-00142', lotNo:'LOT-2601-003', mfgDate:'Jan 15, 2026', expiry:'Jul 10, 2026', daysLeft:30, qtyProduced:50000, qtyAvail:32000, qcStatus:'Passed', qcClass:'approved' },
      { brn:'BRN-00178-2601-007', product:'Ciprofloxacin 250mg Tablets',  prn:'PRN-US-ANT-00178', lotNo:'LOT-2601-007', mfgDate:'Jan 20, 2026', expiry:'Sep 15, 2026', daysLeft:97, qtyProduced:40000, qtyAvail:28000, qcStatus:'Passed', qcClass:'approved' },
      { brn:'BRN-00089-2603-001', product:'Insulax R 100 IU/mL',         prn:'PRN-BD-INJ-00089', lotNo:'LOT-2603-001', mfgDate:'Mar 1, 2026',  expiry:'Mar 1, 2027',  daysLeft:264,qtyProduced:20000, qtyAvail:14000, qcStatus:'Passed', qcClass:'approved' },
      { brn:'BRN-00095-2602-011', product:'Paracetamol 500mg Tablets',   prn:'PRN-US-ANT-00095', lotNo:'LOT-2602-011', mfgDate:'Feb 10, 2026', expiry:'Feb 10, 2028', daysLeft:610,qtyProduced:200000,qtyAvail:168000,qcStatus:'Passed', qcClass:'approved' },
      { brn:'BRN-00067-2511-001', product:'Insulin Glargine 100U/mL',   prn:'PRN-BD-INJ-00067', lotNo:'LOT-2511-001', mfgDate:'Nov 5, 2025',  expiry:'Jun 22, 2026', daysLeft:12, qtyProduced:8000,  qtyAvail:3500,  qcStatus:'Passed', qcClass:'approved' },
      { brn:'BRN-00231-2602-002', product:'Vitamin C 1000mg Tablets',    prn:'PRN-IN-TAB-00231', lotNo:'LOT-2602-002', mfgDate:'Feb 20, 2026', expiry:'Jul 5, 2026',  daysLeft:25, qtyProduced:100000,qtyAvail:84000, qcStatus:'Passed', qcClass:'approved' },
      { brn:'BRN-00033-2510-001', product:'Amoxicillin 250mg Capsules',  prn:'PRN-US-ANT-00033', lotNo:'LOT-2510-001', mfgDate:'Oct 1, 2025',  expiry:'Apr 1, 2026',  daysLeft:0,  qtyProduced:30000, qtyAvail:0,     qcStatus:'Recalled',qcClass:'cancelled'},
    ],
    get filtered(){
      return this.batches.filter(b=>{
        const q=this.search.toLowerCase();
        return (!q||b.brn.toLowerCase().includes(q)||b.product.toLowerCase().includes(q))
            && (!this.filterStatus||b.qcStatus===this.filterStatus);
      });
    }
  };
}
</script>
@endpush
