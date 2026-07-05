@extends('layouts.app')
@section('title', 'Geo Intelligence')

@section('content')
<div class="page-header">
  <div>
    <h1>Geo Intelligence</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Geo Intelligence</div>
  </div>
  <a href="{{ route('anticounterfeit.map') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-geo-alt me-1"></i>Live Map</a>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card"><div class="card-header bg-transparent fw-bold"><i class="bi bi-globe2 me-1 text-primary"></i>By Country</div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>Country</th><th class="text-end">Scans</th><th class="text-end">Suspicious</th></tr></thead>
      <tbody>
        @forelse($byCountry as $r)
          <tr><td class="small">{{ $r->country }}</td><td class="text-end">{{ number_format($r->c) }}</td>
            <td class="text-end">@if($r->sus)<span class="badge-status badge-cancelled">{{ $r->sus }}</span>@else 0 @endif</td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-4">No data yet.</td></tr>@endforelse
      </tbody>
    </table></div></div></div>
  </div>
  <div class="col-lg-6">
    <div class="card mb-3"><div class="card-header bg-transparent fw-bold"><i class="bi bi-building me-1 text-primary"></i>Top Cities</div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>City</th><th>Country</th><th class="text-end">Scans</th></tr></thead>
      <tbody>
        @forelse($byCity as $r)<tr><td class="small">{{ $r->city }}</td><td class="small">{{ $r->country }}</td><td class="text-end">{{ number_format($r->c) }}</td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-4">No data yet.</td></tr>@endforelse
      </tbody>
    </table></div></div></div>
    <div class="card"><div class="card-header bg-transparent fw-bold"><i class="bi bi-hdd-network me-1 text-primary"></i>Top ISPs</div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>ISP</th><th class="text-end">Scans</th></tr></thead>
      <tbody>
        @forelse($byIsp as $r)<tr><td class="small">{{ $r->isp }}</td><td class="text-end">{{ number_format($r->c) }}</td></tr>
        @empty<tr><td colspan="2" class="text-center text-muted py-4">No data yet.</td></tr>@endforelse
      </tbody>
    </table></div></div></div>
  </div>
</div>
@endsection
