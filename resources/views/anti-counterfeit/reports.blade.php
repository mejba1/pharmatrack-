@extends('layouts.app')
@section('title', 'Reports & Analytics')

@section('content')
<div class="page-header">
  <div>
    <h1>Reports &amp; Analytics</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Reports</div>
  </div>
</div>

<div class="row g-3 mb-3">
  @foreach($alertReport->isEmpty() ? collect(['critical'=>0,'high'=>0,'medium'=>0,'low'=>0]) : $alertReport as $level=>$count)
    @php $cls = in_array($level,['critical','high'])?'stat-danger':($level==='medium'?'stat-warning':'stat-info'); @endphp
    <div class="col-6 col-md-3"><div class="stat-card {{ $cls }}">
      <div class="stat-icon"><i class="bi bi-flag"></i></div>
      <div><div class="stat-value">{{ number_format($count) }}</div><div class="stat-label">{{ ucfirst($level) }} alerts</div></div>
    </div></div>
  @endforeach
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-header bg-transparent fw-bold"><i class="bi bi-calendar3 me-1 text-primary"></i>Daily Verification Report (30 days)</div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th class="text-end">Total</th><th class="text-end text-success">Genuine</th><th class="text-end text-warning">Suspicious</th><th class="text-end text-danger">Invalid</th></tr></thead>
      <tbody>
        @forelse($daily as $d)
          <tr><td class="small">{{ \Carbon\Carbon::parse($d->d)->format('d M Y') }}</td>
            <td class="text-end fw-semibold">{{ number_format($d->total) }}</td>
            <td class="text-end">{{ number_format($d->genuine) }}</td>
            <td class="text-end">{{ number_format($d->suspicious) }}</td>
            <td class="text-end">{{ number_format($d->invalid) }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-4">No verification activity yet.</td></tr>@endforelse
      </tbody>
    </table></div></div></div>
  </div>
  <div class="col-lg-5">
    <div class="card"><div class="card-header bg-transparent fw-bold"><i class="bi bi-capsule me-1 text-primary"></i>Top Verified Products</div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>Product</th><th class="text-end">Scans</th></tr></thead>
      <tbody>
        @forelse($byProduct as $p)<tr><td class="small">{{ $p->product?->name ?? '—' }}</td><td class="text-end">{{ number_format($p->c) }}</td></tr>
        @empty<tr><td colspan="2" class="text-center text-muted py-4">No data yet.</td></tr>@endforelse
      </tbody>
    </table></div></div></div>
  </div>
</div>
<p class="text-muted-sm mt-3"><i class="bi bi-info-circle me-1"></i>Country-wise and batch-wise breakdowns are available under Geo Intelligence and Verification Logs. Downloadable PDF/Excel exports are planned for a later phase.</p>
@endsection
