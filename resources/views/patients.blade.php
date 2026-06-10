@extends('layouts.app')
@section('title', 'Patient Portal')

@section('content')
<div x-data="patientsPage()">

  <div class="page-header">
    <div><h1>Patient Portal</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Patient Portal</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Add Patient</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-value">12,841</div><div class="stat-label">Total Patients</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-file-earmark-medical"></i></div><div><div class="stat-value">3,420</div><div class="stat-label">Active Prescriptions</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-clock"></i></div><div><div class="stat-value">187</div><div class="stat-label">Pending Dispensing</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-capsule-pill"></i></div><div><div class="stat-value">8,904</div><div class="stat-label">Dispensing Records</div></div></div></div>
  </div>

  <!-- Sub-tabs -->
  <div class="pills-nav mb-3">
    <button class="pill-btn" :class="{active: tab==='patients'}" @click="tab='patients'"><i class="bi bi-people me-1"></i>Patients</button>
    <button class="pill-btn" :class="{active: tab==='prescriptions'}" @click="tab='prescriptions'"><i class="bi bi-file-earmark-medical me-1"></i>Prescriptions</button>
    <button class="pill-btn" :class="{active: tab==='dispensing'}" @click="tab='dispensing'"><i class="bi bi-capsule-pill me-1"></i>Dispensing</button>
  </div>

  <!-- Patients Tab -->
  <div x-show="tab==='patients'">
    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="row g-2 align-items-center">
          <div class="col-md-5"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search by name, patient ID..." x-model="search"></div></div>
          <div class="col-6 col-md-2"><select class="form-select form-select-sm"><option>All Countries</option><option>Philippines</option><option>Nigeria</option></select></div>
          <div class="col-6 col-md-2 d-flex gap-1">
            <button class="btn btn-primary btn-sm flex-fill">Filter</button>
            <button class="btn btn-outline-secondary btn-sm" @click="search=''"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>
      </div>
    </div>

    <div class="card table-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Patient ID</th><th>Patient Name</th><th>DOB</th><th>Gender</th><th>Country</th><th>Active Rx</th><th>Last Visit</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <template x-for="p in filteredPatients" :key="p.id">
                <tr>
                  <td><code style="font-size:11px" x-text="p.patId"></code></td>
                  <td><div class="fw-semibold" style="font-size:13px" x-text="p.name"></div><div class="text-muted-sm" x-text="p.phone"></div></td>
                  <td style="font-size:13px" x-text="p.dob"></td>
                  <td style="font-size:13px" x-text="p.gender"></td>
                  <td><span x-text="p.flag"></span> <span style="font-size:13px" x-text="p.country"></span></td>
                  <td><span class="badge bg-primary rounded-pill" style="font-size:11px" x-text="p.activeRx"></span></td>
                  <td style="font-size:12px" x-text="p.lastVisit"></td>
                  <td><span class="badge-status" :class="'badge-' + p.statusClass" x-text="p.status"></span></td>
                  <td>
                    <div class="d-flex gap-1">
                      <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewPatient(p)"><i class="bi bi-eye"></i></button>
                      <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-file-earmark-plus"></i></button>
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

  <!-- Prescriptions Tab -->
  <div x-show="tab==='prescriptions'">
    <div class="card table-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Rx No.</th><th>Patient</th><th>Physician</th><th>Product</th><th>Qty</th><th>Date</th><th>Valid Until</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <template x-for="rx in prescriptions" :key="rx.id">
                <tr>
                  <td><code style="font-size:11px" x-text="rx.rxNo"></code></td>
                  <td style="font-size:13px" x-text="rx.patient"></td>
                  <td style="font-size:13px" x-text="rx.physician"></td>
                  <td><div style="font-size:13px" x-text="rx.product"></div><div class="text-muted-sm" x-text="rx.strength + ' · ' + rx.dosage"></div></td>
                  <td style="font-size:13px" x-text="rx.qty"></td>
                  <td style="font-size:12px" x-text="rx.date"></td>
                  <td style="font-size:12px" x-text="rx.validUntil"></td>
                  <td><span class="badge-status" :class="'badge-' + rx.statusClass" x-text="rx.status"></span></td>
                  <td>
                    <button class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-eye"></i></button>
                    <button x-show="rx.status==='Active'" class="btn btn-outline-success btn-sm btn-icon" title="Dispense"><i class="bi bi-capsule-pill"></i></button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Dispensing Tab -->
  <div x-show="tab==='dispensing'">
    <div class="card table-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Dispensing ID</th><th>Patient</th><th>Product</th><th>Batch</th><th>Qty Dispensed</th><th>Dispensed By</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <template x-for="d in dispensing" :key="d.id">
                <tr>
                  <td><code style="font-size:11px" x-text="d.dispId"></code></td>
                  <td style="font-size:13px" x-text="d.patient"></td>
                  <td style="font-size:13px" x-text="d.product"></td>
                  <td><code style="font-size:11px" x-text="d.batch"></code></td>
                  <td style="font-size:13px" x-text="d.qty"></td>
                  <td style="font-size:13px" x-text="d.dispensedBy"></td>
                  <td style="font-size:12px" x-text="d.date"></td>
                  <td><button class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-eye"></i></button></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Patient View Modal -->
  <div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content" x-show="selectedPatient">
        <div class="modal-header"><h5 class="modal-title fw-semibold" x-text="selectedPatient?.name"></h5><button class="btn-close" @click="showViewModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4"><div class="p-3 bg-light rounded-3"><div class="text-muted-sm">Patient ID</div><code x-text="selectedPatient?.patId"></code></div></div>
            <div class="col-md-4"><div class="p-3 bg-light rounded-3"><div class="text-muted-sm">Country</div><div x-text="selectedPatient?.flag + ' ' + selectedPatient?.country"></div></div></div>
            <div class="col-md-4"><div class="p-3 bg-light rounded-3"><div class="text-muted-sm">Status</div><span class="badge-status" :class="'badge-' + selectedPatient?.statusClass" x-text="selectedPatient?.status"></span></div></div>
            <div class="col-md-6"><label class="form-label">Date of Birth</label><div x-text="selectedPatient?.dob"></div></div>
            <div class="col-md-6"><label class="form-label">Gender</label><div x-text="selectedPatient?.gender"></div></div>
            <div class="col-md-6"><label class="form-label">Phone</label><div x-text="selectedPatient?.phone"></div></div>
            <div class="col-md-6"><label class="form-label">Last Visit</label><div x-text="selectedPatient?.lastVisit"></div></div>
            <div class="col-12"><div class="info-box info"><i class="bi bi-file-earmark-medical text-primary mt-1"></i><div><strong x-text="selectedPatient?.activeRx"></strong> active prescription(s) on file.</div></div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
          <button class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-plus me-1"></i>New Prescription</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  <!-- Add Patient Modal -->
  <div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-person-plus me-2 text-primary"></i>Add New Patient</h5><button class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Date of Birth <span class="text-danger">*</span></label><input type="date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Gender</label><select class="form-select"><option>Male</option><option>Female</option><option>Other</option></select></div>
            <div class="col-md-6"><label class="form-label">Phone Number</label><input type="tel" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Country</label><select class="form-select"><option>Philippines</option><option>Nigeria</option><option>Bangladesh</option><option>Kenya</option></select></div>
            <div class="col-md-6"><label class="form-label">Distributor / Facility</label><input type="text" class="form-control" placeholder="Linked distributor or pharmacy"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showAddModal=false">Cancel</button>
          <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Add Patient</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function patientsPage() {
  return {
    tab:'patients', search:'', showAddModal:false, showViewModal:false, selectedPatient:null,
    patients:[
      { id:1, patId:'PAT-2026-000121', name:'Maria Santos',     dob:'Jun 12, 1985', gender:'Female', flag:'🇵🇭', country:'Philippines', phone:'+63 912 345 6789', activeRx:2, lastVisit:'Jun 9, 2026',  status:'Active',   statusClass:'approved' },
      { id:2, patId:'PAT-2026-000244', name:'Chukwuemeka Obi',  dob:'Mar 7, 1972',  gender:'Male',   flag:'🇳🇬', country:'Nigeria',     phone:'+234 803 456 7890',activeRx:1, lastVisit:'Jun 6, 2026',  status:'Active',   statusClass:'approved' },
      { id:3, patId:'PAT-2026-000389', name:'Fatima Rahman',    dob:'Sep 22, 1990', gender:'Female', flag:'🇧🇩', country:'Bangladesh',  phone:'+880 171 234 5678',activeRx:3, lastVisit:'May 28, 2026', status:'Active',   statusClass:'approved' },
      { id:4, patId:'PAT-2026-000512', name:'Ahmed Hassan',     dob:'Jan 14, 1960', gender:'Male',   flag:'🇪🇬', country:'Egypt',       phone:'+20 100 234 5678', activeRx:0, lastVisit:'Apr 14, 2026', status:'Inactive', statusClass:'pending'  },
      { id:5, patId:'PAT-2026-000678', name:'Grace Njoroge',    dob:'Jul 30, 1995', gender:'Female', flag:'🇰🇪', country:'Kenya',       phone:'+254 712 345 678', activeRx:1, lastVisit:'Jun 1, 2026',  status:'Active',   statusClass:'approved' },
    ],
    prescriptions:[
      { id:1, rxNo:'RX-2026-004211', patient:'Maria Santos',  physician:'Dr. Reyes',  product:'Amoxil 500mg Capsules',       strength:'500mg', dosage:'TID x 7 days', qty:'21 capsules', date:'Jun 9, 2026',  validUntil:'Jun 30, 2026', status:'Active',   statusClass:'approved' },
      { id:2, rxNo:'RX-2026-004108', patient:'Grace Njoroge', physician:'Dr. Kamau',  product:'Ciprofloxacin 250mg Tablets', strength:'250mg', dosage:'BD x 5 days',  qty:'10 tablets',  date:'Jun 1, 2026',  validUntil:'Jun 15, 2026', status:'Active',   statusClass:'approved' },
      { id:3, rxNo:'RX-2026-003901', patient:'Fatima Rahman', physician:'Dr. Ahmed',  product:'Insulax R 100 IU/mL',        strength:'100IU', dosage:'Daily',        qty:'1 vial/30d',  date:'May 15, 2026', validUntil:'Aug 15, 2026', status:'Active',   statusClass:'approved' },
      { id:4, rxNo:'RX-2026-003440', patient:'Ahmed Hassan',  physician:'Dr. Mostafa',product:'Paracetamol 500mg Tablets',   strength:'500mg', dosage:'PRN',          qty:'20 tablets',  date:'Apr 14, 2026', validUntil:'May 14, 2026', status:'Expired',  statusClass:'cancelled'},
    ],
    dispensing:[
      { id:1, dispId:'DISP-2026-008841', patient:'Maria Santos',  product:'Amoxil 500mg',        batch:'BRN-00142-2601-003', qty:21, dispensedBy:'PharmaDist PH Ltd.', date:'Jun 9, 2026'  },
      { id:2, dispId:'DISP-2026-008712', patient:'Fatima Rahman', product:'Insulax R 100 IU/mL', batch:'BRN-00089-2603-001', qty:1,  dispensedBy:'BD MedCo Ltd.',       date:'Jun 1, 2026'  },
      { id:3, dispId:'DISP-2026-008590', patient:'Grace Njoroge', product:'Ciproflox 250mg',     batch:'BRN-00178-2601-007', qty:10, dispensedBy:'EA Health Supplies',  date:'Jun 1, 2026'  },
    ],
    viewPatient(p){ this.selectedPatient=p; this.showViewModal=true; },
    get filteredPatients(){
      const q=this.search.toLowerCase();
      return this.patients.filter(p=>!q||p.name.toLowerCase().includes(q)||p.patId.toLowerCase().includes(q));
    }
  };
}
</script>
@endpush
