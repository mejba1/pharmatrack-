@extends('layouts.app')
@section('title', 'Investigation Center')

@section('content')
<div class="page-header">
  <div>
    <h1>Investigation Center</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Investigation Center</div>
  </div>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

<div class="card mb-3"><div class="card-body">
  <form class="row g-2 align-items-end" method="GET">
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">All</option>
        @foreach(['open','investigating','resolved','dismissed'] as $s)<option value="{{ $s }}" @selected(($filters['status']??'')===$s)>{{ ucfirst($s) }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Risk Level</label>
      <select name="risk_level" class="form-select form-select-sm">
        <option value="">All</option>
        @foreach(['critical','high','medium','low'] as $r)<option value="{{ $r }}" @selected(($filters['risk_level']??'')===$r)>{{ ucfirst($r) }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Filter</button></div>
  </form>
</div></div>

@forelse($alerts as $a)
  <div class="card mb-2" x-data="{open:false}">
    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
      <span class="badge-status {{ $a->risk_badge_class }}">{{ ucfirst($a->risk_level) }}</span>
      <span class="fw-semibold">{{ $a->category_label }}</span>
      <span class="font-monospace text-muted small">{{ $a->alert_number }}</span>
      <span class="small text-muted">{{ $a->product?->name }} · {{ $a->country ?? '—' }} · {{ $a->created_at?->format('d M, H:i') }}</span>
      <span class="badge-status {{ $a->status_badge_class }} ms-auto">{{ ucfirst($a->status) }}</span>
      @if($a->is_case)<span class="badge-status badge-cancelled">Case</span>@endif
      <button class="btn btn-outline-primary btn-sm" @click="open=!open"><i class="bi bi-pencil me-1"></i>Manage</button>
    </div>
    <div class="card-body border-top pt-3" x-show="open" x-cloak>
      <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i>{{ $a->description }}
        @if($a->verificationLog)<br>UUC <span class="font-monospace">{{ $a->uuc_code }}</span> · IP {{ $a->verificationLog->ip_address ?? '—' }} · {{ $a->verificationLog->isp ?? '—' }}@endif
      </p>
      <form method="POST" action="{{ route('anticounterfeit.alerts.update', $a) }}" class="row g-2 align-items-end">
        @csrf @method('PUT')
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select form-select-sm">
            @foreach(['open','investigating','resolved','dismissed'] as $s)<option value="{{ $s }}" @selected($a->status===$s)>{{ ucfirst($s) }}</option>@endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Assigned Investigator</label>
          <input type="text" name="assigned_to" class="form-control form-control-sm" value="{{ $a->assigned_to }}" placeholder="Name">
        </div>
        <div class="col-md-4">
          <label class="form-label">Notes</label>
          <input type="text" name="notes" class="form-control form-control-sm" value="{{ $a->notes }}" placeholder="Investigation notes">
        </div>
        <div class="col-md-2">
          <label class="form-label d-block">&nbsp;</label>
          <div class="form-check"><input type="hidden" name="is_case" value="0"><input class="form-check-input" type="checkbox" name="is_case" value="1" id="case{{ $a->id }}" @checked($a->is_case)><label class="form-check-label small" for="case{{ $a->id }}">Counterfeit case</label></div>
        </div>
        <div class="col-12"><button class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Save</button></div>
      </form>
    </div>
  </div>
@empty
  <div class="card"><div class="card-body text-center text-muted py-5">No investigations to show. 🎉</div></div>
@endforelse

@if($alerts->hasPages())<div class="mt-3">{{ $alerts->links() }}</div>@endif
@endsection
