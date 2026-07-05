@extends('layouts.app')
@section('title', 'Anti-Counterfeit Dashboard')

@section('content')
<div class="page-header">
  <div>
    <h1>Anti-Counterfeit Dashboard</h1>
    <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Anti-Counterfeit</div>
  </div>
  <a href="{{ route('anticounterfeit.alerts') }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-exclamation-triangle me-1"></i>Open Alerts ({{ $stats['open_alerts'] }})</a>
</div>

{{-- Stat cards --}}
<div class="row g-3 mb-3">
  @php
    $cards = [
      ['Total Verifications', number_format($stats['total']), 'bi-patch-check', 'stat-primary'],
      ['Scans Today', number_format($stats['today']), 'bi-calendar-day', 'stat-info'],
      ['Genuine', number_format($stats['genuine']), 'bi-shield-check', 'stat-success'],
      ['Suspicious', number_format($stats['suspicious']), 'bi-exclamation-triangle', 'stat-warning'],
      ['Invalid / Expired / Recalled', number_format($stats['invalid']), 'bi-x-octagon', 'stat-danger'],
      ['Open Risk Alerts', number_format($stats['open_alerts']), 'bi-bell', 'stat-warning'],
      ['Critical Alerts', number_format($stats['critical']), 'bi-radioactive', 'stat-danger'],
      ['Active Recalls', number_format($stats['recalls']), 'bi-arrow-counterclockwise', 'stat-purple'],
    ];
  @endphp
  @foreach($cards as $c)
    <div class="col-6 col-lg-3">
      <div class="stat-card {{ $c[3] }}">
        <div class="stat-icon"><i class="bi {{ $c[2] }}"></i></div>
        <div><div class="stat-value">{{ $c[1] }}</div><div class="stat-label">{{ $c[0] }}</div></div>
      </div>
    </div>
  @endforeach
</div>

<div class="row g-3">
  {{-- Verification trend --}}
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-graph-up me-1 text-primary"></i>Verifications — last 14 days</h6>
      @php $max = max(1, collect($trend)->max() ?? 1); @endphp
      <div class="d-flex align-items-end gap-1" style="height:140px">
        @for($i=13;$i>=0;$i--)
          @php $d = now()->subDays($i)->toDateString(); $v = $trend[$d] ?? 0; $h = round(($v/$max)*100); @endphp
          <div class="flex-fill d-flex flex-column align-items-center justify-content-end" style="height:100%" title="{{ $d }}: {{ $v }}">
            <div style="width:100%;height:{{ max(2,$h) }}%;background:linear-gradient(180deg,#0d6efd,#3b82f6);border-radius:4px 4px 0 0"></div>
          </div>
        @endfor
      </div>
      <div class="d-flex justify-content-between text-muted-sm mt-2"><span>{{ now()->subDays(13)->format('M d') }}</span><span>Today</span></div>
    </div></div>

    {{-- Recent alerts --}}
    <div class="card"><div class="card-header bg-transparent d-flex align-items-center">
      <span class="fw-bold"><i class="bi bi-exclamation-triangle me-1 text-danger"></i>Recent Risk Alerts</span>
      <a href="{{ route('anticounterfeit.alerts') }}" class="ms-auto text-decoration-none small">View all</a>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>Alert</th><th>Category</th><th>Risk</th><th>Product</th><th>Country</th><th>When</th></tr></thead>
      <tbody>
        @forelse($recentAlerts as $a)
          <tr>
            <td class="font-monospace small">{{ $a->alert_number }}</td>
            <td class="small">{{ $a->category_label }}</td>
            <td><span class="badge-status {{ $a->risk_badge_class }}">{{ ucfirst($a->risk_level) }}</span></td>
            <td class="small">{{ $a->product?->name ?? '—' }}</td>
            <td class="small">{{ $a->country ?? '—' }}</td>
            <td class="text-muted small">{{ $a->created_at?->diffForHumans() }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted py-4">No alerts yet.</td></tr>
        @endforelse
      </tbody>
    </table></div></div></div>
  </div>

  {{-- Side column --}}
  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-globe2 me-1 text-primary"></i>Top Countries</h6>
      @php $cmax = max(1, $topCountries->max('c') ?? 1); @endphp
      @forelse($topCountries as $tc)
        <div class="mb-2">
          <div class="d-flex justify-content-between small"><span>{{ $tc->country }}</span><span class="fw-semibold">{{ number_format($tc->c) }}</span></div>
          <div class="progress" style="height:6px"><div class="progress-bar" style="width:{{ round(($tc->c/$cmax)*100) }}%"></div></div>
        </div>
      @empty
        <p class="text-muted small mb-0">No location data yet.</p>
      @endforelse
    </div></div>

    <div class="card"><div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-diagram-3 me-1 text-primary"></i>Alerts by Category</h6>
      @forelse($byCategory as $bc)
        <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
          <span>{{ \App\Models\RiskAlert::categoryLabels()[$bc->category] ?? $bc->category }}</span>
          <span class="badge bg-light text-dark">{{ $bc->c }}</span>
        </div>
      @empty
        <p class="text-muted small mb-0">No alerts yet.</p>
      @endforelse
    </div></div>
  </div>
</div>
@endsection
