@if($summary->count())
<div class="table-responsive">
  <table class="table table-sm mb-0 align-middle">
    <thead><tr>
      <th>Batch</th><th>Product</th><th class="text-end">Total Qty</th><th class="text-end">Cartons</th>
      <th class="text-end">Remaining Cartons</th><th class="text-end">Packed Units</th><th class="text-end">Unpacked</th><th style="width:150px"></th>
    </tr></thead>
    <tbody>
      @foreach($summary as $s)
      <tr>
        <td class="font-monospace" style="font-size:12px">{{ $s['batch']?->brn ?? '—' }}</td>
        <td style="font-size:13px">{{ $s['batch']?->product?->name ?? '—' }}</td>
        <td class="text-end">{{ number_format($s['total']) }}</td>
        <td class="text-end">{{ number_format($s['cartons']) }}</td>
        <td class="text-end {{ $s['remaining_cartons'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">{{ number_format($s['remaining_cartons']) }}</td>
        <td class="text-end text-success fw-semibold">{{ number_format($s['packed']) }}</td>
        <td class="text-end {{ $s['unpacked'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">{{ number_format($s['unpacked']) }}</td>
        <td class="text-end">
          @if($s['batch'])
          <a href="{{ route('master-cartons', ['batch_id'=>$s['batch']->id]) }}" class="btn btn-outline-primary btn-sm" title="View these cartons"><i class="bi bi-box-seam"></i></a>
          <a href="{{ route('master-cartons.labels', ['batch_id'=>$s['batch']->id]) }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Print labels"><i class="bi bi-printer"></i></a>
          <a href="{{ route('master-cartons.labels-pdf', ['batch_id'=>$s['batch']->id]) }}" class="btn btn-outline-danger btn-sm" title="Labels PDF"><i class="bi bi-file-earmark-pdf"></i></a>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
<div class="text-muted-sm px-3 py-2 border-top">Showing the {{ $summary->count() }} most recent active batches. Use the filters above to find a specific batch's cartons.</div>
@else
<div class="text-center text-muted py-4"><i class="bi bi-clipboard-data" style="font-size:28px;opacity:.2"></i><div class="mt-2">No packed batches yet.</div></div>
@endif
