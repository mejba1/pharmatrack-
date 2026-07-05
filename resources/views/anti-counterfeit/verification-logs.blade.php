@extends('layouts.app')
@section('title', 'Verification Logs')

@section('content')
<div class="page-header">
  <div>
    <h1>Verification Logs</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Verification Logs</div>
  </div>
</div>

<div class="card mb-3"><div class="card-body">
  <form class="row g-2 align-items-end" method="GET">
    <div class="col-md-4">
      <label class="form-label">Search UUC / Verification ID</label>
      <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="HAK33NQJCP or VER-…">
    </div>
    <div class="col-md-3">
      <label class="form-label">Result</label>
      <select name="result" class="form-select form-select-sm">
        <option value="">All</option>
        @foreach(['genuine','suspicious','blocked','locked','invalid','expired','recalled'] as $r)
          <option value="{{ $r }}" @selected(($filters['result'] ?? '')===$r)>{{ ucfirst($r) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Country</label>
      <select name="country_code" class="form-select form-select-sm">
        <option value="">All</option>
        @foreach($countries as $c)
          <option value="{{ $c->country_code }}" @selected(($filters['country_code'] ?? '')===$c->country_code)>{{ $c->country }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-1">
      <label class="form-label">Show</label>
      <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
        @foreach([10,20,50,100] as $pp)<option value="{{ $pp }}" @selected(($perPage ?? 20)===$pp)>{{ $pp }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-1">
      <label class="form-label d-block">&nbsp;</label>
      <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
    </div>
  </form>
</div></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead><tr>
      <th>Verification ID</th><th>UUC</th><th>Product</th><th>Result</th><th>Risk</th>
      <th>Country</th><th>Device</th><th>Network</th><th>When</th>
    </tr></thead>
    <tbody>
      @forelse($logs as $l)
        <tr>
          <td class="font-monospace small">{{ $l->verification_number }}</td>
          <td class="font-monospace small">{{ $l->uuc_code }}</td>
          <td class="small">{{ $l->product?->name ?? '—' }}</td>
          <td><span class="badge-status {{ $l->result_badge_class }}">{{ ucfirst($l->result) }}</span></td>
          <td><span class="fw-semibold {{ $l->risk_score>=61?'text-danger':($l->risk_score>=41?'text-warning':'text-success') }}">{{ $l->risk_score }}</span></td>
          <td class="small">{{ $l->country ? ($l->country.($l->city? ' · '.$l->city:'')) : '—' }}</td>
          <td class="small">{{ trim(($l->os ?? '').' · '.($l->browser ?? ''), ' ·') ?: '—' }}</td>
          <td class="small">@if($l->is_proxy)<span class="badge-status badge-cancelled">VPN/Proxy</span>@else <span class="text-muted">Direct</span>@endif</td>
          <td class="text-muted small">{{ $l->created_at?->format('d M, H:i') }}</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center text-muted py-4">No verification logs match.</td></tr>
      @endforelse
    </tbody>
  </table>
</div></div>
@if($logs->hasPages())<div class="card-footer bg-transparent">{{ $logs->links() }}</div>@endif
</div>
@endsection
