@extends('layouts.app')
@section('title', 'Reports & Analytics')

@section('content')
<div x-data="reportsPage()">

  <div class="page-header">
    <div><h1>Reports &amp; Analytics</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Reports</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3 me-1"></i>Schedule Report</button>
      <button class="btn btn-primary btn-sm" @click="showRunModal=true"><i class="bi bi-play-fill me-1"></i>Run Report</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-file-bar-graph"></i></div><div><div class="stat-value">24</div><div class="stat-label">Report Definitions</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">1,842</div><div class="stat-label">Reports Run (MTD)</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-clock"></i></div><div><div class="stat-value">8</div><div class="stat-label">Scheduled Reports</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-cloud-arrow-down"></i></div><div><div class="stat-value">347</div><div class="stat-label">Downloads This Month</div></div></div></div>
  </div>

  <!-- Tabs -->
  <div class="pills-nav mb-3">
    <button class="pill-btn" :class="{active: tab==='definitions'}" @click="tab='definitions'"><i class="bi bi-file-bar-graph me-1"></i>Report Catalog</button>
    <button class="pill-btn" :class="{active: tab==='runs'}" @click="tab='runs'"><i class="bi bi-clock-history me-1"></i>Run History</button>
    <button class="pill-btn" :class="{active: tab==='scheduled'}" @click="tab='scheduled'"><i class="bi bi-calendar-check me-1"></i>Scheduled</button>
  </div>

  <!-- Report Catalog -->
  <div x-show="tab==='definitions'" class="row g-3">
    <template x-for="r in reportDefs" :key="r.id">
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex align-items-start gap-3">
              <div class="stat-icon" :class="'stat-' + r.colorClass" style="width:40px;height:40px;font-size:18px;flex-shrink:0"><i class="bi" :class="r.icon"></i></div>
              <div class="flex-fill">
                <div class="fw-semibold" style="font-size:14px" x-text="r.name"></div>
                <div class="text-muted-sm mt-1" x-text="r.description"></div>
                <div class="d-flex align-items-center gap-2 mt-2">
                  <span class="badge bg-light text-secondary border" style="font-size:11px" x-text="r.category"></span>
                  <span class="text-muted-sm" x-text="r.lastRun"></span>
                </div>
              </div>
            </div>
            <div class="d-flex gap-2 mt-3">
              <button class="btn btn-outline-primary btn-sm flex-fill" @click="showRunModal=true; selectedReport=r"><i class="bi bi-play-fill me-1"></i>Run</button>
              <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-download"></i></button>
              <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-calendar3"></i></button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>

  <!-- Run History -->
  <div x-show="tab==='runs'" class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>Report Name</th><th>Category</th><th>Run By</th><th>Parameters</th><th>Duration</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <template x-for="run in reportRuns" :key="run.id">
              <tr>
                <td><div class="fw-semibold" style="font-size:13px" x-text="run.name"></div><div class="text-muted-sm" x-text="run.date"></div></td>
                <td><span class="badge bg-light text-secondary border" style="font-size:11px" x-text="run.category"></span></td>
                <td style="font-size:13px" x-text="run.runBy"></td>
                <td style="font-size:12px" x-text="run.params"></td>
                <td style="font-size:13px" x-text="run.duration"></td>
                <td>
                  <span x-show="run.status==='Completed'" class="badge-status badge-approved">Completed</span>
                  <span x-show="run.status==='Running'" class="badge-status badge-pending">Running...</span>
                  <span x-show="run.status==='Failed'" class="badge-status badge-cancelled">Failed</span>
                </td>
                <td>
                  <button x-show="run.status==='Completed'" class="btn btn-outline-secondary btn-sm btn-icon" title="Download"><i class="bi bi-download"></i></button>
                  <button class="btn btn-outline-primary btn-sm btn-icon" title="Re-run"><i class="bi bi-arrow-clockwise"></i></button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Scheduled Reports -->
  <div x-show="tab==='scheduled'" class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>Report Name</th><th>Frequency</th><th>Next Run</th><th>Format</th><th>Recipients</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <template x-for="s in scheduledReports" :key="s.id">
              <tr>
                <td><div class="fw-semibold" style="font-size:13px" x-text="s.name"></div><div class="text-muted-sm" x-text="s.category"></div></td>
                <td><span class="badge bg-light text-secondary border" style="font-size:11px" x-text="s.frequency"></span></td>
                <td style="font-size:13px" x-text="s.nextRun"></td>
                <td><span class="badge bg-light text-danger border" style="font-size:11px" x-text="s.format"></span></td>
                <td style="font-size:12px" x-text="s.recipients"></td>
                <td><span class="badge-status" :class="s.active ? 'badge-approved' : 'badge-pending'" x-text="s.active ? 'Active' : 'Paused'"></span></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-trash"></i></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Run Report Modal -->
  <div class="modal fade" :class="{show:showRunModal}" :style="showRunModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-play-fill me-2 text-primary"></i>Run Report</h5><button class="btn-close" @click="showRunModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12"><label class="form-label">Report Type <span class="text-danger">*</span></label>
              <select class="form-select">
                <template x-for="r in reportDefs" :key="r.id"><option x-text="r.name"></option></template>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Date From</label><input type="date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Date To</label><input type="date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Country (optional)</label><select class="form-select"><option value="">All Countries</option><option>Philippines</option><option>Nigeria</option><option>Bangladesh</option></select></div>
            <div class="col-md-6"><label class="form-label">Output Format</label><select class="form-select"><option>PDF</option><option>Excel</option><option>CSV</option></select></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showRunModal=false">Cancel</button>
          <button class="btn btn-primary"><i class="bi bi-play-fill me-1"></i>Generate Report</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showRunModal" @click="showRunModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function reportsPage() {
  return {
    tab:'definitions', showRunModal:false, selectedReport:null,
    reportDefs:[
      { id:1, name:'Order Summary Report',          description:'PO/SO/PI/CI summary with totals by period.',            category:'Orders',       icon:'bi-cart3',                colorClass:'primary', lastRun:'Today' },
      { id:2, name:'Shipment Status Report',         description:'All shipments with carrier, status, ETA.',            category:'Shipments',    icon:'bi-truck',               colorClass:'info',    lastRun:'Yesterday' },
      { id:3, name:'Batch Expiry Report',            description:'Batches expiring within selected period.',             category:'Inventory',    icon:'bi-layers',              colorClass:'warning', lastRun:'Jun 8, 2026' },
      { id:4, name:'Country Regulatory Summary',     description:'Per-country registration and permit status.',          category:'Regulatory',   icon:'bi-globe2',              colorClass:'success', lastRun:'Jun 5, 2026' },
      { id:5, name:'Anti-Counterfeit Scan Report',   description:'Scan events with counterfeit/suspicious breakdown.',   category:'Compliance',   icon:'bi-shield-check',        colorClass:'danger',  lastRun:'Jun 9, 2026' },
      { id:6, name:'Document Vault Audit Trail',     description:'Upload, download and version history of vault docs.',  category:'Compliance',   icon:'bi-files',               colorClass:'purple',  lastRun:'Jun 7, 2026' },
      { id:7, name:'Distributor Performance Report', description:'Sales volume and on-time delivery per distributor.',  category:'Distribution', icon:'bi-building',            colorClass:'primary', lastRun:'Jun 1, 2026' },
      { id:8, name:'Patient Dispensing Report',      description:'Dispensing records grouped by product and country.',   category:'Patient',      icon:'bi-capsule-pill',        colorClass:'success', lastRun:'Jun 3, 2026' },
      { id:9, name:'Financial Summary Report',       description:'PI/CI totals, outstanding payments, by period.',       category:'Finance',      icon:'bi-cash-coin',           colorClass:'warning', lastRun:'Jun 10, 2026' },
    ],
    reportRuns:[
      { id:1, name:'Order Summary Report',          category:'Orders',    runBy:'admin@pharmatrack.com', params:'Jun 2026 · All Countries', duration:'2.1s',  date:'Jun 10, 2026 09:14', status:'Completed' },
      { id:2, name:'Batch Expiry Report',           category:'Inventory', runBy:'admin@pharmatrack.com', params:'< 90 days · All',          duration:'1.8s',  date:'Jun 10, 2026 08:55', status:'Completed' },
      { id:3, name:'Anti-Counterfeit Scan Report',  category:'Compliance',runBy:'admin@pharmatrack.com', params:'Jun 1–10, 2026',           duration:'3.4s',  date:'Jun 9, 2026 14:22',  status:'Completed' },
      { id:4, name:'Financial Summary Report',      category:'Finance',   runBy:'finance@pharmatrack.com',params:'May 2026',                duration:'4.2s',  date:'Jun 9, 2026 10:05',  status:'Completed' },
      { id:5, name:'Shipment Status Report',        category:'Shipments', runBy:'ops@pharmatrack.com',   params:'Active · All Carriers',    duration:'—',     date:'Jun 9, 2026 09:50',  status:'Failed'    },
    ],
    scheduledReports:[
      { id:1, name:'Order Summary Report',   category:'Orders',    frequency:'Monthly',  nextRun:'Jul 1, 2026',  format:'PDF',   recipients:'4 users',  active:true  },
      { id:2, name:'Batch Expiry Report',    category:'Inventory', frequency:'Weekly',   nextRun:'Jun 15, 2026', format:'Excel', recipients:'2 users',  active:true  },
      { id:3, name:'Financial Summary',      category:'Finance',   frequency:'Monthly',  nextRun:'Jul 1, 2026',  format:'PDF',   recipients:'3 users',  active:true  },
      { id:4, name:'Shipment Status Report', category:'Shipments', frequency:'Daily',    nextRun:'Jun 11, 2026', format:'CSV',   recipients:'1 user',   active:false },
    ]
  };
}
</script>
@endpush
