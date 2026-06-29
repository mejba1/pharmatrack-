@extends('layouts.app')
@section('title', 'Reports & Analytics')

@section('content')
<div>
  <div class="page-header">
    <div>
      <h1>Reports &amp; Analytics</h1>
      <div class="page-breadcrumb">{{ $mine ? 'Your sales performance' : 'Company-wide sales performance' }}</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('reports.csv', request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
      <a href="{{ route('reports.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>PDF</a>
    </div>
  </div>

  {{-- Filter bar --}}
  <div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-2"><label class="form-label">Trend year</label>
        <select name="year" class="form-select form-select-sm">
          @foreach($years as $y)<option value="{{ $y }}" @selected($year==$y)>{{ $y }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">From</label><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
      <div class="col-md-3"><label class="form-label">To</label><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
      <div class="col-md-2 d-flex gap-1">
        <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Apply</button>
        <a href="{{ route('reports') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
      </div>
    </form>
  </div></div>

  {{-- Summary cards --}}
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-value">{{ number_format($summary['total_sales'], 0) }}</div><div class="stat-label">Total Sales (value)</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-bag-check"></i></div><div><div class="stat-value">{{ $summary['orders'] }}</div><div class="stat-label">Sales Orders</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-value">{{ number_format($summary['units']) }}</div><div class="stat-label">Units Sold</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-value">{{ $summary['customers'] }}</div><div class="stat-label">Customers</div></div></div></div>
  </div>

  {{-- Charts row 1: trend + status donut --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="card h-100"><div class="card-body">
      <h6 class="fw-bold mb-3">Monthly Sales — {{ $year }}</h6>
      <canvas id="trendChart" height="110"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body">
      <h6 class="fw-bold mb-3">Order Status</h6>
      <canvas id="statusChart" height="180"></canvas>
    </div></div></div>
  </div>

  {{-- Charts row 2: customer pie + yearly bar --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
      <h6 class="fw-bold mb-3">Top Customers by Sales</h6>
      <canvas id="customerPie" height="200"></canvas>
    </div></div></div>
    <div class="col-lg-7"><div class="card h-100"><div class="card-body">
      <h6 class="fw-bold mb-3">Yearly Sales</h6>
      <canvas id="yearlyChart" height="150"></canvas>
    </div></div></div>
  </div>

  {{-- Charts row 3: product value + qty --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
      <h6 class="fw-bold mb-3">Product-wise Sales (value)</h6>
      <canvas id="productValueChart" height="200"></canvas>
    </div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
      <h6 class="fw-bold mb-3">Product-wise Quantity</h6>
      <canvas id="productQtyChart" height="200"></canvas>
    </div></div></div>
  </div>

  {{-- Tables --}}
  <div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-body p-0">
      <div class="px-3 pt-3 pb-2"><h6 class="fw-bold mb-0">Customer-wise sales</h6></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Customer</th><th class="text-center">Orders</th><th class="text-end">Sales value</th></tr></thead>
        <tbody>
          @forelse($customerWise as $c)<tr><td class="small">{{ $c['name'] }}</td><td class="text-center">{{ $c['orders'] }}</td><td class="text-end fw-semibold">{{ number_format($c['value'], 2) }}</td></tr>
          @empty<tr><td colspan="3" class="text-center text-muted py-4">No sales in range.</td></tr>@endforelse
        </tbody>
      </table></div>
    </div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-body p-0">
      <div class="px-3 pt-3 pb-2"><h6 class="fw-bold mb-0">Product-wise sales</h6></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Sales value</th></tr></thead>
        <tbody>
          @forelse($productWise as $p)<tr><td class="small">{{ $p['name'] }}</td><td class="text-center">{{ number_format($p['qty']) }}</td><td class="text-end fw-semibold">{{ number_format($p['value'], 2) }}</td></tr>
          @empty<tr><td colspan="3" class="text-center text-muted py-4">No sales in range.</td></tr>@endforelse
        </tbody>
      </table></div>
    </div></div></div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  const monthly  = @js($monthly);
  const yearly   = @js($yearly);
  const customer = @js($customerWise);
  const product  = @js($productWise);
  const soStatus = @js($soStatus);

  const palette = ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#0dcaf0','#fd7e14','#20c997'];
  const money = v => v.toLocaleString();
  const mk = (id, cfg) => { const el = document.getElementById(id); if (el && window.Chart) new Chart(el, cfg); };

  mk('trendChart', { type:'bar', data:{ labels: monthly.map(m=>m.label), datasets:[{ label:'Sales', data: monthly.map(m=>m.value), backgroundColor:'#0d6efd' }] },
    options:{ plugins:{legend:{display:false}}, scales:{y:{ticks:{callback:money}}} } });

  mk('yearlyChart', { type:'bar', data:{ labels: yearly.map(y=>y.label), datasets:[{ label:'Sales', data: yearly.map(y=>y.value), backgroundColor:'#198754' }] },
    options:{ plugins:{legend:{display:false}}, scales:{y:{ticks:{callback:money}}} } });

  mk('customerPie', { type:'pie', data:{ labels: customer.map(c=>c.name), datasets:[{ data: customer.map(c=>c.value), backgroundColor: palette }] },
    options:{ plugins:{legend:{position:'bottom', labels:{boxWidth:12, font:{size:11}}}} } });

  mk('productValueChart', { type:'bar', data:{ labels: product.map(p=>p.name), datasets:[{ label:'Value', data: product.map(p=>p.value), backgroundColor:'#6f42c1' }] },
    options:{ indexAxis:'y', plugins:{legend:{display:false}}, scales:{x:{ticks:{callback:money}}} } });

  mk('productQtyChart', { type:'bar', data:{ labels: product.map(p=>p.name), datasets:[{ label:'Qty', data: product.map(p=>p.qty), backgroundColor:'#fd7e14' }] },
    options:{ indexAxis:'y', plugins:{legend:{display:false}} } });

  const sLabels = Object.keys(soStatus); const sData = Object.values(soStatus);
  mk('statusChart', { type:'doughnut', data:{ labels: sLabels, datasets:[{ data: sData, backgroundColor: palette }] },
    options:{ plugins:{legend:{position:'bottom', labels:{boxWidth:12, font:{size:11}}}} } });
})();
</script>
@endpush
