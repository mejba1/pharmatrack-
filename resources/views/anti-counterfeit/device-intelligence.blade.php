@extends('layouts.app')
@section('title', 'Device Intelligence')

@section('content')
<div class="page-header">
  <div>
    <h1>Device Intelligence</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Device Intelligence</div>
  </div>
</div>

<div class="row g-3">
  @php
    $blocks = [
      ['Browsers','bi-globe', $byBrowser, 'browser'],
      ['Operating Systems','bi-cpu', $byOs, 'os'],
      ['Device Types','bi-phone', $byDevice, 'device_type'],
    ];
  @endphp
  @foreach($blocks as $b)
    <div class="col-md-4">
      <div class="card h-100"><div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi {{ $b[1] }} me-1 text-primary"></i>{{ $b[0] }}</h6>
        @php $mx = max(1, $b[2]->max('c') ?? 1); @endphp
        @forelse($b[2] as $row)
          <div class="mb-2">
            <div class="d-flex justify-content-between small"><span>{{ $row->{$b[3]} ?: 'Unknown' }}</span><span class="fw-semibold">{{ number_format($row->c) }}</span></div>
            <div class="progress" style="height:6px"><div class="progress-bar" style="width:{{ round(($row->c/$mx)*100) }}%"></div></div>
          </div>
        @empty
          <p class="text-muted small mb-0">No data yet.</p>
        @endforelse
      </div></div>
    </div>
  @endforeach
</div>

<div class="card mt-3"><div class="card-body d-flex align-items-center gap-3">
  <div class="stat-icon" style="width:46px;height:46px;border-radius:12px;background:rgba(220,53,69,.12);color:#dc3545;display:flex;align-items:center;justify-content:center;font-size:20px"><i class="bi bi-shield-exclamation"></i></div>
  <div><div class="fw-bold" style="font-size:20px">{{ number_format($proxy) }}</div><div class="text-muted-sm">Verifications via VPN / Proxy / hosting networks</div></div>
</div></div>
@endsection
