@extends('layouts.app')
@section('title', 'Shipment Management')

@section('content')
<div x-data="shipmentsPage()">

  <div class="page-header">
    <div><h1>Shipment Management</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Shipment Management</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>New Shipment</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2"><div class="stat-card stat-primary"><div class="stat-icon" style="width:44px;height:44px"><i class="bi bi-truck" style="font-size:20px"></i></div><div><div class="stat-value" style="font-size:22px">137</div><div class="stat-label">Active</div></div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card stat-warning"><div class="stat-icon" style="width:44px;height:44px"><i class="bi bi-hourglass" style="font-size:20px"></i></div><div><div class="stat-value" style="font-size:22px">24</div><div class="stat-label">Pending Booking</div></div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card stat-info"><div class="stat-icon" style="width:44px;height:44px"><i class="bi bi-geo-alt" style="font-size:20px"></i></div><div><div class="stat-value" style="font-size:22px">89</div><div class="stat-label">In Transit</div></div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card stat-success"><div class="stat-icon" style="width:44px;height:44px"><i class="bi bi-box-seam" style="font-size:20px"></i></div><div><div class="stat-value" style="font-size:22px">412</div><div class="stat-label">Delivered</div></div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card stat-danger"><div class="stat-icon" style="width:44px;height:44px"><i class="bi bi-clock-history" style="font-size:20px"></i></div><div><div class="stat-value" style="font-size:22px">3</div><div class="stat-label">Delayed</div></div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card stat-purple"><div class="stat-icon" style="width:44px;height:44px"><i class="bi bi-boxes" style="font-size:20px"></i></div><div><div class="stat-value" style="font-size:22px">18</div><div class="stat-label">Multi-Parcel</div></div></div></div>
  </div>

  <!-- Carrier Tabs -->
  <div class="pills-nav mb-3">
    <button class="pill-btn" :class="{active: carrierFilter===''}" @click="carrierFilter=''">All Carriers</button>
    <button class="pill-btn" :class="{active: carrierFilter==='DHL'}" @click="carrierFilter='DHL'"><i class="bi bi-truck me-1"></i>DHL Express</button>
    <button class="pill-btn" :class="{active: carrierFilter==='EMS'}" @click="carrierFilter='EMS'"><i class="bi bi-envelope me-1"></i>EMS / UPU</button>
  </div>

  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-md-3"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search shipment ID, tracking no..." x-model="search"></div></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Pending Booking</option><option>Booked</option><option>In Transit</option><option>Delivered</option><option>Delayed</option></select></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm"><option value="">All Countries</option><option>Philippines</option><option>Nigeria</option><option>Bangladesh</option></select></div>
        <div class="col-md-2 d-flex gap-1">
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
              <th>Shipment ID</th><th>Linked CI</th><th>Destination</th><th>Carrier</th>
              <th>Tracking No.</th><th>Packages</th><th>Dispatch</th><th>ETA</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="s in filtered" :key="s.id">
              <tr>
                <td><input type="checkbox" class="form-check-input"></td>
                <td><span class="text-primary fw-semibold cursor-pointer" style="font-size:12px" @click="viewShipment(s)" x-text="s.id"></span></td>
                <td><a href="{{ route('orders.ci') }}" class="text-secondary text-decoration-none" style="font-size:12px" x-text="s.ci"></a></td>
                <td><div class="fw-semibold" style="font-size:13px" x-text="s.destination"></div><div class="text-muted-sm" x-text="s.distributor"></div></td>
                <td><span x-text="s.carrier"></span><div class="text-muted-sm" style="font-size:11px" x-text="s.mode"></div></td>
                <td><code style="font-size:11px" x-text="s.tracking"></code></td>
                <td style="font-size:13px" x-text="s.packages + ' pkgs'"></td>
                <td style="font-size:13px" x-text="s.departure"></td>
                <td style="font-size:13px" x-text="s.eta"></td>
                <td><span class="badge-status" :class="'badge-' + s.statusClass" x-text="s.status"></span></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewShipment(s)"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-info btn-sm btn-icon" title="Track"><i class="bi bi-geo-alt"></i></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
        <div class="text-muted-sm">Showing <strong x-text="filtered.length"></strong> of <strong>137</strong> active shipments</div>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item disabled"><a class="page-link">Prev</a></li>
          <li class="page-item active"><a class="page-link">1</a></li>
          <li class="page-item"><a class="page-link">2</a></li>
          <li class="page-item"><a class="page-link">Next</a></li>
        </ul></nav>
      </div>
    </div>
  </div>

  <!-- View Shipment Modal -->
  <div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content" x-show="selectedShipment">
        <div class="modal-header">
          <div><h5 class="modal-title fw-semibold" x-text="selectedShipment?.id"></h5><div class="text-muted-sm">Shipment Tracking</div></div>
          <div class="ms-auto d-flex gap-2 align-items-center">
            <span class="badge-status" :class="'badge-' + selectedShipment?.statusClass" x-text="selectedShipment?.status"></span>
            <button class="btn-close ms-2" @click="showViewModal=false"></button>
          </div>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-2 fw-semibold">ORIGIN</div><div class="fw-semibold">Port of Los Angeles, USA</div></div></div>
            <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-2 fw-semibold">DESTINATION</div><div class="fw-semibold" x-text="selectedShipment?.destination"></div><div class="text-muted-sm" x-text="selectedShipment?.distributor"></div></div></div>
            <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-2 fw-semibold">CARRIER</div><div class="fw-semibold" x-text="selectedShipment?.carrier"></div><div class="text-muted-sm"><code x-text="selectedShipment?.tracking"></code></div></div></div>
            <!-- Timeline -->
            <div class="col-12">
              <h6 class="fw-semibold mb-3">Tracking Timeline</h6>
              <div class="d-flex flex-column gap-2">
                <template x-for="(ev, i) in (selectedShipment?.events||[])" :key="i">
                  <div class="d-flex gap-3 align-items-start">
                    <div class="flex-shrink-0" style="width:10px;height:10px;border-radius:50%;margin-top:4px;" :class="i===0 ? 'bg-primary' : 'bg-secondary'"></div>
                    <div>
                      <div class="fw-semibold" style="font-size:13px" x-text="ev.title"></div>
                      <div class="text-muted-sm" x-text="ev.location + ' · ' + ev.date"></div>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
          <button class="btn btn-outline-info btn-sm"><i class="bi bi-geo-alt me-1"></i>Update Location</button>
          <button class="btn btn-primary btn-sm" x-show="selectedShipment?.status==='In Transit'"><i class="bi bi-check2 me-1"></i>Mark Delivered</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  <!-- New Shipment Modal -->
  <div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-truck me-2 text-primary"></i>Create New Shipment</h5><button class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Linked Commercial Invoice <span class="text-danger">*</span></label><select class="form-select"><option>Select approved CI...</option><option>CI-2026-0072 — PharmaDist PH</option></select></div>
            <div class="col-md-6"><label class="form-label">Booking Date</label><input type="date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Carrier</label><select class="form-select"><option>DHL Express</option><option>EMS / UPU</option><option>Maersk</option><option>MSC</option></select></div>
            <div class="col-md-6"><label class="form-label">Transport Mode</label><select class="form-select"><option>Air</option><option>Sea</option><option>Road</option><option>Courier</option></select></div>
            <div class="col-md-6"><label class="form-label">Departure Date</label><input type="date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Estimated Arrival</label><input type="date" class="form-control"></div>
            <div class="col-12"><label class="form-label">Tracking Number</label><input type="text" class="form-control" placeholder="Enter carrier tracking number"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showAddModal=false">Cancel</button>
          <button class="btn btn-primary"><i class="bi bi-truck me-1"></i>Create Shipment</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function shipmentsPage() {
  return {
    search:'', filterStatus:'', carrierFilter:'', showAddModal:false, showViewModal:false, selectedShipment:null,
    shipments:[
      { id:'SHP-2026-0498', ci:'CI-2026-0072', destination:'Manila, Philippines', distributor:'PharmaDist PH Ltd.',  carrier:'DHL Express', mode:'Air', tracking:'1234567890',   packages:12, departure:'Jun 8, 2026',  eta:'Jun 14, 2026', status:'In Transit',      statusClass:'shipped',
        events:[{title:'Departed Port of Los Angeles',date:'Jun 8, 2026',location:'Los Angeles, USA'},{title:'Arrived at DHL Hub — Tokyo',date:'Jun 10, 2026',location:'Tokyo, Japan'},{title:'Customs Cleared — Manila',date:'Jun 12, 2026',location:'Manila, Philippines'}]},
      { id:'SHP-2026-0495', ci:'CI-2026-0069', destination:'Lagos, Nigeria',      distributor:'West Africa Pharma',  carrier:'EMS',         mode:'Air', tracking:'EE9876543NG',   packages:8,  departure:'Jun 5, 2026',  eta:'Jun 10, 2026', status:'Delivered',       statusClass:'delivered', events:[]},
      { id:'SHP-2026-0493', ci:'CI-2026-0065', destination:'Dhaka, Bangladesh',   distributor:'BD MedCo Ltd.',       carrier:'DHL Express', mode:'Sea', tracking:'9988776655',   packages:24, departure:'Jun 1, 2026',  eta:'Jun 18, 2026', status:'Booked',          statusClass:'pending',   events:[]},
      { id:'SHP-2026-0491', ci:'CI-2026-0061', destination:'Cairo, Egypt',        distributor:'EG Pharma Group',     carrier:'DHL Express', mode:'Air', tracking:'4455667788',   packages:5,  departure:'Jun 7, 2026',  eta:'Jun 12, 2026', status:'Delayed',         statusClass:'cancelled', events:[]},
      { id:'SHP-2026-0488', ci:'CI-2026-0058', destination:'Nairobi, Kenya',      distributor:'EA Health Supplies',  carrier:'EMS',         mode:'Air', tracking:'EE1122334KE',  packages:10, departure:'Jun 3, 2026',  eta:'Jun 9, 2026',  status:'Delivered',       statusClass:'delivered', events:[]},
    ],
    viewShipment(s){ this.selectedShipment=s; this.showViewModal=true; },
    get filtered(){
      return this.shipments.filter(s=>{
        const q=this.search.toLowerCase();
        return (!q||s.id.toLowerCase().includes(q)||s.tracking.toLowerCase().includes(q))
            && (!this.filterStatus||s.status===this.filterStatus)
            && (!this.carrierFilter||s.carrier.includes(this.carrierFilter));
      });
    }
  };
}
</script>
@endpush
