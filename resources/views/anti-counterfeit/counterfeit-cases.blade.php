@extends('layouts.app')
@section('title', 'Counterfeit Cases')

@section('content')
<div class="page-header">
  <div>
    <h1>Counterfeit Cases</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Counterfeit Cases</div>
  </div>
  <a href="{{ route('anticounterfeit.investigations') }}" class="btn btn-outline-primary btn-sm">Investigation Center</a>
</div>

<div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>Cases are risk alerts escalated to a confirmed counterfeit investigation. Escalate an alert from the Investigation Center.</div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead><tr><th>Alert</th><th>Category</th><th>Risk</th><th>Product</th><th>UUC</th><th>Country</th><th>Status</th><th>Investigator</th><th>When</th></tr></thead>
    <tbody>
      @forelse($cases as $a)
        <tr>
          <td class="font-monospace small">{{ $a->alert_number }}</td>
          <td class="small">{{ $a->category_label }}</td>
          <td><span class="badge-status {{ $a->risk_badge_class }}">{{ ucfirst($a->risk_level) }}</span></td>
          <td class="small">{{ $a->product?->name ?? '—' }}</td>
          <td class="font-monospace small">{{ $a->uuc_code ?? '—' }}</td>
          <td class="small">{{ $a->country ?? '—' }}</td>
          <td><span class="badge-status {{ $a->status_badge_class }}">{{ ucfirst($a->status) }}</span></td>
          <td class="small">{{ $a->assigned_to ?? '—' }}</td>
          <td class="text-muted small">{{ $a->created_at?->format('d M, H:i') }}</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center text-muted py-4">No counterfeit cases yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div></div>
@if($cases->hasPages())<div class="card-footer bg-transparent">{{ $cases->links() }}</div>@endif
</div>
@endsection
