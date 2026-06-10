@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1>Dashboard</h1>
      <div class="page-breadcrumb">Welcome back, <strong>Dr. Sarah Admin</strong> — here's your system overview</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
      <button class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Export Report</button>
    </div>
  </div>

  <!-- Stat Cards Row 1 -->
  <div class="row g-3 mb-4" x-data="dashboardPage()">
    <div class="col-6 col-lg-3">
      <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="bi bi-capsule"></i></div>
        <div><div class="stat-value">248</div><div class="stat-label">Active Products</div>
          <div class="stat-trend text-success"><i class="bi bi-arrow-up-short"></i>12 this month</div></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card stat-success">
        <div class="stat-icon"><i class="bi bi-layers"></i></div>
        <div><div class="stat-value">1,842</div><div class="stat-label">Active Batches</div>
          <div class="stat-trend text-success"><i class="bi bi-arrow-up-short"></i>94 new batches</div></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="bi bi-truck"></i></div>
        <div><div class="stat-value">137</div><div class="stat-label">Active Shipments</div>
          <div class="stat-trend text-warning"><i class="bi bi-clock"></i>3 delayed</div></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card stat-danger">
        <div class="stat-icon"><i class="bi bi-shield-exclamation"></i></div>
        <div><div class="stat-value">7</div><div class="stat-label">Counterfeit Alerts</div>
          <div class="stat-trend text-danger"><i class="bi bi-arrow-up-short"></i>2 new today</div></div>
      </div>
    </div>
  </div>

  <!-- Stat Cards Row 2 -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-2"><div class="stat-card stat-info"><div class="stat-icon" style="width:40px;height:40px"><i class="bi bi-cart3" style="font-size:18px"></i></div><div><div class="stat-value" style="font-size:20px">318</div><div class="stat-label">Open POs</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="stat-card stat-success"><div class="stat-icon" style="width:40px;height:40px"><i class="bi bi-bag-check" style="font-size:18px"></i></div><div><div class="stat-value" style="font-size:20px">241</div><div class="stat-label">Sales Orders</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="stat-card stat-warning"><div class="stat-icon" style="width:40px;height:40px"><i class="bi bi-receipt" style="font-size:18px"></i></div><div><div class="stat-value" style="font-size:20px">58</div><div class="stat-label">Pending PIs</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="stat-card stat-primary"><div class="stat-icon" style="width:40px;height:40px"><i class="bi bi-diagram-3" style="font-size:18px"></i></div><div><div class="stat-value" style="font-size:20px">94</div><div class="stat-label">Distributors</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="stat-card stat-purple"><div class="stat-icon" style="width:40px;height:40px"><i class="bi bi-globe2" style="font-size:18px"></i></div><div><div class="stat-value" style="font-size:20px">47</div><div class="stat-label">Countries</div></div></div></div>
    <div class="col-6 col-lg-2"><div class="stat-card stat-success"><div class="stat-icon" style="width:40px;height:40px"><i class="bi bi-qr-code" style="font-size:18px"></i></div><div><div class="stat-value" style="font-size:20px">24.5K</div><div class="stat-label">QR Scans Today</div></div></div></div>
  </div>

  <!-- Main Grid -->
  <div class="row g-3 mb-4" x-data="dashboardPage()">

    <!-- Recent Shipments -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Recent Shipments</h6>
            <a href="{{ route('shipments') }}" class="btn btn-outline-primary btn-sm">View All</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Shipment ID</th><th>Destination</th><th>Carrier</th>
                  <th>Products</th><th>Status</th><th>ETA</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="s in recentShipments" :key="s.id">
                  <tr>
                    <td><a href="{{ route('shipments') }}" class="text-primary fw-semibold text-decoration-none" x-text="s.id"></a></td>
                    <td><div x-text="s.destination"></div><div class="text-muted-sm" x-text="s.distributor"></div></td>
                    <td><span class="fw-semibold" x-text="s.carrier"></span><div class="text-muted-sm" x-text="s.trackingNo"></div></td>
                    <td x-text="s.products"></td>
                    <td><span class="badge-status" :class="'badge-' + s.statusClass" x-text="s.status"></span></td>
                    <td class="text-muted-sm" x-text="s.eta"></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Alerts -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Active Alerts</h6>
            <span class="badge bg-danger rounded-pill" x-text="alerts.length"></span>
          </div>
          <div class="d-flex flex-column gap-2">
            <template x-for="a in alerts" :key="a.id">
              <div class="info-box" :class="a.type" style="font-size:13px">
                <i :class="a.icon" class="mt-1" style="font-size:15px;flex-shrink:0"></i>
                <div>
                  <div class="fw-semibold" x-text="a.title"></div>
                  <div class="text-muted-sm" x-text="a.desc"></div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Lower Grid -->
  <div class="row g-3" x-data="dashboardPage()">

    <!-- Document Flow Pipeline -->
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Order Document Flow</h6>
          <div class="d-flex flex-column gap-3">
            <template x-for="flow in docFlows" :key="flow.id">
              <div class="border rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="fw-semibold text-primary" style="font-size:13px" x-text="flow.orderId"></span>
                  <span class="badge-status" :class="'badge-' + flow.statusClass" x-text="flow.status"></span>
                </div>
                <div class="step-tracker mb-1">
                  <template x-for="(step, si) in flow.steps" :key="si">
                    <div class="step-item">
                      <div class="step-circle" :class="step.state" x-text="si + 1"></div>
                      <div class="step-line" :class="step.state" x-show="si < flow.steps.length - 1"></div>
                    </div>
                  </template>
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <template x-for="(step, si) in flow.steps" :key="'l'+si">
                    <div class="step-label" :class="step.state === 'active' ? 'active' : ''"
                         x-text="step.label" style="flex:1;font-size:10px;text-align:center"></div>
                  </template>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Expiring Batches -->
    <div class="col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Expiring Batches</h6>
            <a href="{{ route('batches') }}" class="text-primary text-decoration-none" style="font-size:12px">View all</a>
          </div>
          <div class="d-flex flex-column gap-2">
            <template x-for="b in expiringBatches" :key="b.brn">
              <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light">
                <div class="flex-grow-1">
                  <div class="fw-semibold" style="font-size:12px" x-text="b.brn"></div>
                  <div class="text-muted-sm" x-text="b.product"></div>
                </div>
                <div class="text-end">
                  <div :class="b.urgent ? 'text-danger' : 'text-warning'" class="fw-semibold" style="font-size:12px" x-text="b.expiry"></div>
                  <div class="text-muted-sm" x-text="b.daysLeft + ' days'"></div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Countries -->
    <div class="col-lg-3">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Top Destination Countries</h6>
          <div class="d-flex flex-column gap-2">
            <template x-for="c in topCountries" :key="c.code">
              <div>
                <div class="d-flex justify-content-between mb-1">
                  <span style="font-size:13px" x-text="c.flag + ' ' + c.name"></span>
                  <span class="fw-semibold" style="font-size:12px" x-text="c.shipments + ' shipments'"></span>
                </div>
                <div class="progress" style="height:5px;border-radius:3px">
                  <div class="progress-bar" :style="'width:' + c.pct + '%'" style="border-radius:3px"></div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection

@push('scripts')
<script>
function dashboardPage() {
  return {
    recentShipments: [
      { id:'SHP-2026-0498', destination:'Manila, Philippines', distributor:'PharmaDist PH Ltd.',   carrier:'DHL Express', trackingNo:'1234567890',   products:'3 SKUs / 5,000 units',  status:'In Transit', statusClass:'shipped',   eta:'Jun 14' },
      { id:'SHP-2026-0495', destination:'Lagos, Nigeria',      distributor:'West Africa Pharma',    carrier:'EMS',         trackingNo:'EE9876543NG',   products:'2 SKUs / 2,400 units',  status:'Delivered',  statusClass:'delivered', eta:'Jun 10' },
      { id:'SHP-2026-0493', destination:'Dhaka, Bangladesh',   distributor:'BD MedCo Ltd.',         carrier:'DHL Express', trackingNo:'9988776655',   products:'5 SKUs / 12,000 units', status:'Pending',    statusClass:'pending',   eta:'Jun 18' },
      { id:'SHP-2026-0491', destination:'Cairo, Egypt',        distributor:'EG Pharma Group',       carrier:'DHL Express', trackingNo:'4455667788',   products:'1 SKU / 800 units',     status:'Delayed',    statusClass:'cancelled', eta:'Jun 12' },
      { id:'SHP-2026-0488', destination:'Nairobi, Kenya',      distributor:'EA Health Supplies',    carrier:'EMS',         trackingNo:'EE1122334KE',  products:'2 SKUs / 3,200 units',  status:'Delivered',  statusClass:'delivered', eta:'Jun 9'  },
    ],
    alerts: [
      { id:1, type:'danger', icon:'bi-shield-exclamation text-danger',        title:'Counterfeit Scan Anomaly', desc:'2 duplicate UUC scans in Lagos — batch BRN-00089-2603-001' },
      { id:2, type:'warn',   icon:'bi-exclamation-triangle-fill text-warning', title:'Batch Expiry in 30 Days', desc:'BRN-00142-2601-003 · Amoxicillin 500mg · 12,000 units' },
      { id:3, type:'warn',   icon:'bi-file-earmark-x text-warning',            title:'GMP Cert Expiring',       desc:'Manufacturing Site CN-01 · expires in 15 days' },
      { id:4, type:'info',   icon:'bi-globe2 text-primary',                    title:'Country Approval Pending',desc:'PRN-BD-INJ-00089 awaiting Bangladesh DGDA approval' },
    ],
    docFlows: [
      { id:1, orderId:'PO-2026-0318', status:'Shipped',     statusClass:'shipped',
        steps:[{label:'PO',state:'done'},{label:'SO',state:'done'},{label:'PI',state:'done'},{label:'CI',state:'done'},{label:'Shipment',state:'active'}]},
      { id:2, orderId:'PO-2026-0315', status:'Awaiting CI', statusClass:'pending',
        steps:[{label:'PO',state:'done'},{label:'SO',state:'done'},{label:'PI',state:'done'},{label:'CI',state:'active'},{label:'Shipment',state:'pending'}]},
      { id:3, orderId:'PO-2026-0312', status:'Draft',       statusClass:'draft',
        steps:[{label:'PO',state:'done'},{label:'SO',state:'active'},{label:'PI',state:'pending'},{label:'CI',state:'pending'},{label:'Shipment',state:'pending'}]},
    ],
    expiringBatches: [
      { brn:'BRN-00142-2601-003', product:'Amoxicillin 500mg', expiry:'Jul 10, 2026', daysLeft:30, urgent:false },
      { brn:'BRN-00089-2510-001', product:'Metformin 850mg',   expiry:'Jun 28, 2026', daysLeft:18, urgent:true  },
      { brn:'BRN-00231-2602-002', product:'Vitamin C 1000mg',  expiry:'Jul 5, 2026',  daysLeft:25, urgent:false },
      { brn:'BRN-00067-2511-001', product:'Insulin Glargine',  expiry:'Jun 22, 2026', daysLeft:12, urgent:true  },
    ],
    topCountries: [
      { code:'PH', name:'Philippines', flag:'🇵🇭', shipments:42, pct:90 },
      { code:'NG', name:'Nigeria',     flag:'🇳🇬', shipments:31, pct:66 },
      { code:'BD', name:'Bangladesh',  flag:'🇧🇩', shipments:27, pct:57 },
      { code:'EG', name:'Egypt',       flag:'🇪🇬', shipments:19, pct:40 },
      { code:'KE', name:'Kenya',       flag:'🇰🇪', shipments:14, pct:30 },
    ],
  };
}
</script>
@endpush
