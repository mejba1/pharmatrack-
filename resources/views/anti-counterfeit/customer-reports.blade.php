@extends('layouts.app')
@section('title', 'Customer Reports')

@section('content')
<div class="page-header">
  <div>
    <h1>Customer Reports</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Customer Reports</div>
  </div>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

{{-- Status filter chips --}}
<div class="d-flex gap-2 mb-3 flex-wrap">
  <a href="{{ route('anticounterfeit.reports-list') }}" class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
  <a href="{{ route('anticounterfeit.reports-list', ['status'=>'new']) }}" class="btn btn-sm {{ $status==='new' ? 'btn-danger' : 'btn-outline-danger' }}">New ({{ $counts['new'] }})</a>
  <a href="{{ route('anticounterfeit.reports-list', ['status'=>'reviewing']) }}" class="btn btn-sm {{ $status==='reviewing' ? 'btn-warning' : 'btn-outline-warning' }}">Reviewing ({{ $counts['reviewing'] }})</a>
  <a href="{{ route('anticounterfeit.reports-list', ['status'=>'resolved']) }}" class="btn btn-sm {{ $status==='resolved' ? 'btn-success' : 'btn-outline-success' }}">Resolved ({{ $counts['resolved'] }})</a>
</div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead><tr><th>When</th><th>Reporter</th><th>Contact</th><th>UUC</th><th>Product</th><th>Reason</th><th>Message</th><th>Location</th><th>Status</th><th class="text-end">Action</th></tr></thead>
    <tbody>
      @forelse($reports as $r)
        <tr>
          <td class="text-muted small">{{ $r->created_at?->format('d M Y, H:i') }}</td>
          <td class="small fw-semibold">{{ $r->reporter_name }}</td>
          <td class="small">{{ $r->reporter_phone }}@if($r->reporter_email)<div class="text-muted">{{ $r->reporter_email }}</div>@endif</td>
          <td class="font-monospace small">{{ $r->uuc_code }}</td>
          <td class="small">{{ $r->product?->name ?? '—' }}</td>
          <td class="small">{{ $r->reason ? ucfirst($r->reason) : '—' }}</td>
          <td class="small" style="max-width:240px">{{ $r->message ?: '—' }}</td>
          <td class="small">{{ trim(($r->city ? $r->city.', ' : '').($r->country ?? '')) ?: '—' }}</td>
          <td><span class="badge-status {{ $r->status_badge_class }}">{{ ucfirst($r->status) }}</span></td>
          <td class="text-end">
            <form method="POST" action="{{ route('anticounterfeit.reports-list.update', $r) }}" class="d-flex gap-1">
              @csrf @method('PUT')
              <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                @foreach(['new','reviewing','resolved'] as $s)<option value="{{ $s }}" @selected($r->status===$s)>{{ ucfirst($s) }}</option>@endforeach
              </select>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="10" class="text-center text-muted py-4">No reports yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div></div>
@if($reports->hasPages())<div class="card-footer bg-transparent">{{ $reports->links() }}</div>@endif
</div>
@endsection
