@extends('layouts.app')
@section('title', 'Risk Alerts')

@section('content')
<div class="page-header">
  <div>
    <h1>Risk Alerts</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Risk Alerts</div>
  </div>
  <a href="{{ route('anticounterfeit.investigations') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-search me-1"></i>Investigation Center</a>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

<div class="card mb-3"><div class="card-body">
  <form class="row g-2 align-items-end" method="GET">
    <div class="col-md-3">
      <label class="form-label">Risk Level</label>
      <select name="risk_level" class="form-select form-select-sm">
        <option value="">All</option>
        @foreach(['critical','high','medium','low'] as $r)<option value="{{ $r }}" @selected(($filters['risk_level']??'')===$r)>{{ ucfirst($r) }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">All</option>
        @foreach(['open','investigating','resolved','dismissed'] as $s)<option value="{{ $s }}" @selected(($filters['status']??'')===$s)>{{ ucfirst($s) }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Category</label>
      <select name="category" class="form-select form-select-sm">
        <option value="">All</option>
        @foreach($categories as $k=>$v)<option value="{{ $k }}" @selected(($filters['category']??'')===$k)>{{ $v }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
  </form>
</div></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead><tr><th>Alert</th><th>Category</th><th>Risk</th><th>Score</th><th>Product</th><th>UUC</th><th>Country</th><th>Status</th><th>When</th></tr></thead>
    <tbody>
      @forelse($alerts as $a)
        <tr>
          <td class="font-monospace small">{{ $a->alert_number }}</td>
          <td class="small">{{ $a->category_label }}<div class="text-muted" style="font-size:11px">{{ $a->description }}</div></td>
          <td><span class="badge-status {{ $a->risk_badge_class }}">{{ ucfirst($a->risk_level) }}</span></td>
          <td class="fw-semibold">{{ $a->risk_score }}</td>
          <td class="small">{{ $a->product?->name ?? '—' }}</td>
          <td class="font-monospace small">{{ $a->uuc_code ?? '—' }}</td>
          <td class="small">{{ $a->country ?? '—' }}</td>
          <td><span class="badge-status {{ $a->status_badge_class }}">{{ ucfirst($a->status) }}</span></td>
          <td class="text-muted small">{{ $a->created_at?->format('d M, H:i') }}</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center text-muted py-4">No risk alerts match.</td></tr>
      @endforelse
    </tbody>
  </table>
</div></div>
@if($alerts->hasPages())<div class="card-footer bg-transparent">{{ $alerts->links() }}</div>@endif
</div>
@endsection
