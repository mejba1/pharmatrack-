@extends('layouts.app')
@section('title', 'Recalled Batches')

@section('content')
<div x-data="{scope:'batch'}">
<div class="page-header">
  <div>
    <h1>Recall Management</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Recalled Batches</div>
  </div>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

<div class="card mb-3"><div class="card-body">
  <h6 class="fw-bold mb-3"><i class="bi bi-megaphone me-1 text-danger"></i>Issue a Recall</h6>
  <form method="POST" action="{{ route('anticounterfeit.recalls.store') }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-md-2">
      <label class="form-label">Scope</label>
      <select name="scope" class="form-select form-select-sm" x-model="scope">
        <option value="batch">Batch</option>
        <option value="country">Country (product)</option>
        <option value="global">Global (product)</option>
      </select>
    </div>
    <div class="col-md-3" x-show="scope==='batch'">
      <label class="form-label">Batch</label>
      <select name="batch_id" class="form-select form-select-sm">
        <option value="">Select batch…</option>
        @foreach($batches as $b)<option value="{{ $b->id }}">{{ $b->brn }} — {{ $b->product?->name }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-3" x-show="scope!=='batch'">
      <label class="form-label">Product</label>
      <select name="product_id" class="form-select form-select-sm">
        <option value="">Select product…</option>
        @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2" x-show="scope==='country'">
      <label class="form-label">Country code</label>
      <input type="text" name="country_code" maxlength="2" class="form-control form-control-sm" placeholder="BD">
    </div>
    <div class="col-md-2">
      <label class="form-label">Severity</label>
      <select name="severity" class="form-select form-select-sm">
        <option value="normal">Normal</option>
        <option value="emergency">Emergency</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Reason</label>
      <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Contamination found">
    </div>
    <div class="col-md-2"><button class="btn btn-danger btn-sm w-100"><i class="bi bi-exclamation-octagon me-1"></i>Issue Recall</button></div>
  </form>
</div></div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead><tr><th>Recall No.</th><th>Scope</th><th>Product</th><th>Batch</th><th>Severity</th><th>Reason</th><th>Status</th><th>When</th><th class="text-end">Action</th></tr></thead>
    <tbody>
      @forelse($recalls as $r)
        <tr>
          <td class="font-monospace small">{{ $r->recall_number }}</td>
          <td>{{ $r->scope_label }}{{ $r->country_code ? ' ('.$r->country_code.')' : '' }}</td>
          <td class="small">{{ $r->product?->name ?? '—' }}</td>
          <td class="font-monospace small">{{ $r->batch?->brn ?? '—' }}</td>
          <td>@if($r->severity==='emergency')<span class="badge-status badge-cancelled">Emergency</span>@else<span class="badge-status badge-pending">Normal</span>@endif</td>
          <td class="small">{{ $r->reason ?? '—' }}</td>
          <td>@if($r->active)<span class="badge-status badge-cancelled">Active</span>@else<span class="badge-status badge-draft">Lifted</span>@endif</td>
          <td class="text-muted small">{{ $r->recalled_at?->format('d M Y') }}</td>
          <td class="text-end">
            <form method="POST" action="{{ route('anticounterfeit.recalls.toggle', $r) }}">
              @csrf
              <button class="btn btn-sm {{ $r->active ? 'btn-outline-success' : 'btn-outline-danger' }}">{{ $r->active ? 'Lift' : 'Re-activate' }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center text-muted py-4">No recalls issued.</td></tr>
      @endforelse
    </tbody>
  </table>
</div></div>
@if($recalls->hasPages())<div class="card-footer bg-transparent">{{ $recalls->links() }}</div>@endif
</div>
</div>
@endsection
