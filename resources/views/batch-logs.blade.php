@extends('layouts.app')
@section('title', 'Trace Logs — ' . $batch->brn)

@section('content')
<div>

  <div class="page-header">
    <div>
      <h1>Trace Logs</h1>
      <div class="page-breadcrumb">
        <a href="{{ route('dashboard') }}">Home</a> /
        <a href="{{ route('batches') }}">Batch &amp; Lot Mgmt</a> /
        <a href="{{ route('batches.units', $batch) }}" class="font-monospace">{{ $batch->brn }}</a> / Trace Logs
      </div>
    </div>
    <a href="{{ route('batches.units', $batch) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Units</a>
  </div>

  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>When</th><th>Event</th><th>Unit</th><th>From → To</th><th>Qty</th><th>Note</th><th>By</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $log)
            <tr>
              <td style="font-size:12px" class="text-muted">{{ $log->created_at?->format('M d, Y H:i:s') }}</td>
              <td><span class="badge bg-light text-secondary border">{{ str_replace('_',' ', $log->event) }}</span></td>
              <td class="font-monospace" style="font-size:12px">{{ $log->unit?->unique_number ?? '—' }}</td>
              <td style="font-size:12px">{{ $log->from_status ?? '—' }} → {{ $log->to_status ?? '—' }}</td>
              <td style="font-size:13px">{{ $log->quantity !== null ? number_format($log->quantity) : '—' }}</td>
              <td style="font-size:12px">{{ $log->note ?? '—' }}</td>
              <td style="font-size:12px">{{ $log->performed_by ?? 'system' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-5 text-muted">
              <i class="bi bi-clock-history" style="font-size:32px;opacity:.2"></i>
              <div class="mt-2">No trace logs yet for this batch.</div>
            </td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($logs->hasPages())
      <div class="px-3 py-2 border-top">{{ $logs->links('pagination::bootstrap-5') }}</div>
      @endif
    </div>
  </div>

</div>
@endsection
