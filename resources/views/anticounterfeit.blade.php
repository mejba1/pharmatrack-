@extends('layouts.app')
@section('title', 'Anti-Counterfeit')

@section('content')
<div x-data="anticounterfeitPage()">

  <div class="page-header">
    <div><h1>Anti-Counterfeit Tracking</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Anti-Counterfeit</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export Scans</button>
      <button class="btn btn-primary btn-sm" @click="showScanModal=true"><i class="bi bi-qr-code-scan me-1"></i>Scan Code</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-qr-code"></i></div><div><div class="stat-value">284,719</div><div class="stat-label">Total Codes Issued</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-shield-check"></i></div><div><div class="stat-value">281,304</div><div class="stat-label">Verified Authentic</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-shield-x"></i></div><div><div class="stat-value">47</div><div class="stat-label">Counterfeit Detected</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-exclamation-diamond"></i></div><div><div class="stat-value">12</div><div class="stat-label">Suspicious Scans</div></div></div></div>
  </div>

  <!-- Alert Banner -->
  <div class="info-box danger mb-4">
    <i class="bi bi-shield-exclamation text-danger mt-1" style="font-size:18px;flex-shrink:0"></i>
    <div style="font-size:13px"><strong>Counterfeit Alert:</strong> 3 new counterfeit detections in Lagos, Nigeria (Jun 8–10, 2026). <a href="#" class="text-danger fw-semibold">Investigate →</a></div>
  </div>

  <!-- View Tabs -->
  <div class="pills-nav mb-3">
    <button class="pill-btn" :class="{active: tab==='scans'}" @click="tab='scans'"><i class="bi bi-list-ul me-1"></i>Scan Log</button>
    <button class="pill-btn" :class="{active: tab==='codes'}" @click="tab='codes'"><i class="bi bi-qr-code me-1"></i>Code Registry</button>
  </div>

  <!-- Scan Log -->
  <div x-show="tab==='scans'">
    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="row g-2 align-items-center">
          <div class="col-md-3"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search code, batch, product..." x-model="search"></div></div>
          <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterResult"><option value="">All Results</option><option>Authentic</option><option>Counterfeit</option><option>Suspicious</option></select></div>
          <div class="col-6 col-md-2"><select class="form-select form-select-sm"><option>All Countries</option><option>Philippines</option><option>Nigeria</option><option>Bangladesh</option></select></div>
          <div class="col-6 col-md-2 d-flex gap-1">
            <button class="btn btn-primary btn-sm flex-fill">Filter</button>
            <button class="btn btn-outline-secondary btn-sm" @click="search='';filterResult=''"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>
      </div>
    </div>

    <div class="card table-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Code</th><th>Product</th><th>Batch</th><th>Scanned By</th><th>Location</th><th>Scan Date</th><th>Scan Count</th><th>Result</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <template x-for="s in filteredScans" :key="s.id">
                <tr>
                  <td><code style="font-size:11px" x-text="s.code"></code></td>
                  <td style="font-size:13px" x-text="s.product"></td>
                  <td><code style="font-size:11px" x-text="s.batch"></code></td>
                  <td style="font-size:13px" x-text="s.scanner"></td>
                  <td><div style="font-size:13px" x-text="s.city"></div><div class="text-muted-sm" x-text="s.country"></div></td>
                  <td style="font-size:12px" x-text="s.date"></td>
                  <td>
                    <span :class="s.scanCount > 3 ? 'text-danger fw-semibold' : 'text-muted'" style="font-size:13px" x-text="s.scanCount + 'x'"></span>
                  </td>
                  <td>
                    <span x-show="s.result==='Authentic'" class="badge-status badge-approved">✓ Authentic</span>
                    <span x-show="s.result==='Counterfeit'" class="badge-status badge-cancelled">✗ Counterfeit</span>
                    <span x-show="s.result==='Suspicious'" class="badge-status badge-pending">⚠ Suspicious</span>
                  </td>
                  <td>
                    <button class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-eye"></i></button>
                    <button x-show="s.result==='Counterfeit'||s.result==='Suspicious'" class="btn btn-outline-danger btn-sm btn-icon" title="Flag"><i class="bi bi-flag"></i></button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Code Registry -->
  <div x-show="tab==='codes'">
    <div class="card table-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Code</th><th>Product</th><th>Batch</th><th>Issued On</th><th>Expiry</th><th>Total Scans</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <template x-for="c in codes" :key="c.code">
                <tr>
                  <td><code style="font-size:11px" x-text="c.code"></code></td>
                  <td style="font-size:13px" x-text="c.product"></td>
                  <td><code style="font-size:11px" x-text="c.batch"></code></td>
                  <td style="font-size:12px" x-text="c.issued"></td>
                  <td style="font-size:12px" x-text="c.expiry"></td>
                  <td style="font-size:13px" x-text="c.scans"></td>
                  <td><span class="badge-status" :class="'badge-' + c.statusClass" x-text="c.status"></span></td>
                  <td><button class="btn btn-outline-primary btn-sm btn-icon"><i class="bi bi-eye"></i></button></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Scan Modal -->
  <div class="modal fade" :class="{show:showScanModal}" :style="showScanModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Verify Anti-Counterfeit Code</h5><button class="btn-close" @click="showScanModal=false;scanResult=null"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Enter or Scan Code</label><input type="text" class="form-control" placeholder="e.g. AC-2026-PHM-000142" x-model="scanInput" @keyup.enter="verifyScan()"></div>
          <button class="btn btn-primary w-100" @click="verifyScan()"><i class="bi bi-search me-1"></i>Verify Code</button>
          <div x-show="scanResult" class="mt-3 p-3 rounded-3" :class="scanResult==='Authentic' ? 'bg-success bg-opacity-10 border border-success' : 'bg-danger bg-opacity-10 border border-danger'">
            <div class="d-flex gap-2 align-items-center">
              <i :class="scanResult==='Authentic' ? 'bi-shield-check text-success' : 'bi-shield-x text-danger'" class="bi" style="font-size:24px"></i>
              <div><div class="fw-semibold" :class="scanResult==='Authentic' ? 'text-success' : 'text-danger'" x-text="scanResult==='Authentic' ? '✓ Product Verified Authentic' : '✗ Counterfeit Detected!'"></div><div style="font-size:12px" class="text-muted">Amoxil 500mg · BRN-00142-2601-003 · PharmaCo Mfg</div></div>
            </div>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" @click="showScanModal=false;scanResult=null">Close</button></div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showScanModal" @click="showScanModal=false;scanResult=null"></div>

</div>
@endsection

@push('scripts')
<script>
function anticounterfeitPage() {
  return {
    tab:'scans', search:'', filterResult:'', showScanModal:false, scanInput:'', scanResult:null,
    scans:[
      { id:1, code:'AC-2026-PHM-000142', product:'Amoxil 500mg',        batch:'BRN-00142-2601-003', scanner:'PharmaDist PH Ltd.',  city:'Manila',  country:'Philippines', date:'Jun 10, 2026', scanCount:1, result:'Authentic'   },
      { id:2, code:'AC-2026-PHM-000897', product:'Ciproflox 250mg',     batch:'BRN-00178-2601-007', scanner:'West Africa Pharma',  city:'Lagos',   country:'Nigeria',     date:'Jun 10, 2026', scanCount:7, result:'Counterfeit' },
      { id:3, code:'AC-2026-PHM-000543', product:'Paracetamol 500mg',   batch:'BRN-00095-2602-011', scanner:'BD MedCo Ltd.',       city:'Dhaka',   country:'Bangladesh',  date:'Jun 9, 2026',  scanCount:2, result:'Suspicious'  },
      { id:4, code:'AC-2026-PHM-001204', product:'Amoxil 500mg',        batch:'BRN-00142-2601-003', scanner:'EA Health Supplies',  city:'Nairobi', country:'Kenya',       date:'Jun 8, 2026',  scanCount:1, result:'Authentic'   },
      { id:5, code:'AC-2026-PHM-000331', product:'Insulax R 100 IU/mL', batch:'BRN-00089-2603-001', scanner:'EG Pharma Group',     city:'Cairo',   country:'Egypt',       date:'Jun 8, 2026',  scanCount:1, result:'Authentic'   },
      { id:6, code:'AC-2026-PHM-000912', product:'Ciproflox 250mg',     batch:'BRN-00178-2601-007', scanner:'Unknown Vendor',      city:'Lagos',   country:'Nigeria',     date:'Jun 8, 2026',  scanCount:9, result:'Counterfeit' },
    ],
    codes:[
      { code:'AC-2026-PHM-000142', product:'Amoxil 500mg',        batch:'BRN-00142-2601-003', issued:'Jan 15, 2026', expiry:'Jul 10, 2026', scans:1, status:'Active',   statusClass:'approved' },
      { code:'AC-2026-PHM-000897', product:'Ciproflox 250mg',     batch:'BRN-00178-2601-007', issued:'Jan 20, 2026', expiry:'Sep 15, 2026', scans:7, status:'Flagged',  statusClass:'cancelled'},
      { code:'AC-2026-PHM-000543', product:'Paracetamol 500mg',   batch:'BRN-00095-2602-011', issued:'Feb 10, 2026', expiry:'Feb 10, 2028', scans:2, status:'Suspect',  statusClass:'pending'  },
      { code:'AC-2026-PHM-001204', product:'Amoxil 500mg',        batch:'BRN-00142-2601-003', issued:'Jan 15, 2026', expiry:'Jul 10, 2026', scans:1, status:'Active',   statusClass:'approved' },
    ],
    verifyScan(){
      if(!this.scanInput.trim()) return;
      this.scanResult = this.scanInput.includes('000897') || this.scanInput.includes('000912') ? 'Counterfeit' : 'Authentic';
    },
    get filteredScans(){
      const q = this.search.toLowerCase();
      return this.scans.filter(s =>
        (!q || s.code.toLowerCase().includes(q) || s.product.toLowerCase().includes(q) || s.batch.toLowerCase().includes(q))
        && (!this.filterResult || s.result === this.filterResult)
      );
    }
  };
}
</script>
@endpush
