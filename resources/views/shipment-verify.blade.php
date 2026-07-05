<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shipment — PharmaTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body{background:#f6f8fb}
    .card{max-width:640px;margin:5vh auto;border:none;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
    .kpi{border:1px solid #eef0f3;border-radius:12px;padding:12px;text-align:center}
    .kpi .n{font-size:22px;font-weight:700;line-height:1}
    .kpi .l{font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.04em}
    .badge-status{font-size:12px;padding:4px 10px;border-radius:20px;font-weight:600}
    .badge-approved{background:rgba(13,202,240,.12);color:#0a9ab7}
    .badge-pending{background:rgba(255,193,7,.15);color:#c97b00}
    .badge-cancelled{background:rgba(108,117,125,.12);color:#6c757d}
  </style>
</head>
<body>
  <div class="card">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <i class="bi bi-truck text-primary" style="font-size:30px"></i>
        <div class="fw-bold">Pharma<span class="text-primary">Track</span> · Shipment</div>
      </div>

      @if($consignment)
        @php
          $statusClass = $consignment->status_badge_class;
          $missing = $consignment->missing_cartons;
        @endphp
        <div class="text-center mb-3">
          <h4 class="fw-bold mb-1 font-monospace">{{ $consignment->consignment_number }}</h4>
          <span class="badge-status {{ $statusClass }}">{{ $consignment->status_label }}</span>
          <div class="text-muted mt-2" style="font-size:14px">
            <i class="bi bi-geo-alt me-1"></i>{{ $consignment->origin }} → {{ $consignment->destination ?? '—' }}
          </div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-3"><div class="kpi"><div class="n text-primary">{{ number_format($consignment->carton_count) }}</div><div class="l">Cartons</div></div></div>
          <div class="col-3"><div class="kpi"><div class="n">{{ number_format($consignment->total_units) }}</div><div class="l">Units</div></div></div>
          <div class="col-3"><div class="kpi"><div class="n">{{ $consignment->product_list->count() }}</div><div class="l">Products</div></div></div>
          <div class="col-3"><div class="kpi"><div class="n">{{ $consignment->batch_list->count() }}</div><div class="l">Batches</div></div></div>
        </div>

        @if($consignment->dispatched_at)
        <div class="d-flex gap-2 mb-3">
          <div class="kpi flex-fill"><div class="n text-success">{{ $consignment->received_carton_count }}</div><div class="l">Received</div></div>
          <div class="kpi flex-fill"><div class="n {{ $missing->count() ? 'text-danger' : 'text-muted' }}">{{ $missing->count() }}</div><div class="l">Missing</div></div>
        </div>
        @if($missing->count())
        <div class="alert alert-danger py-2" style="font-size:13px">
          <i class="bi bi-exclamation-triangle me-1"></i><strong>Missing cartons:</strong>
          {{ $missing->join(', ') }}
        </div>
        @endif
        @endif

        <div class="fw-semibold text-muted mb-1" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em">Products</div>
        <div class="mb-3">
          @forelse($consignment->product_list as $p)<span class="badge text-bg-light border me-1 mb-1">{{ $p }}</span>@empty<span class="text-muted">—</span>@endforelse
        </div>

        <div class="fw-semibold text-muted mb-1" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em">Cartons</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Carton</th><th>Product</th><th>Batch</th><th class="text-end">Units</th><th>Status</th></tr></thead>
            <tbody>
              @foreach($consignment->cartons as $c)
              <tr>
                <td class="font-monospace" style="font-size:12px">{{ $c->carton_number }}</td>
                <td style="font-size:12px">{{ $c->products_summary }}</td>
                <td class="font-monospace" style="font-size:11px">{{ $c->batches_summary }}</td>
                <td class="text-end">{{ number_format($c->packed_quantity) }}</td>
                <td><span class="badge-status {{ $c->status_badge_class }}">{{ $c->status_label }}</span></td>
              </tr>
              @endforeach
            </tbody>
            <tfoot><tr><th colspan="3" class="text-end">Total Units</th><th class="text-end">{{ number_format($consignment->total_units) }}</th><th></th></tr></tfoot>
          </table>
        </div>

        @if($consignment->dispatched_at)
        <div class="text-muted small">Dispatched: {{ $consignment->dispatched_at->format('M d, Y H:i') }}</div>
        @endif
        @if($consignment->received_at)
        <div class="text-muted small">Received: {{ $consignment->received_at->format('M d, Y H:i') }}</div>
        @endif
        <div class="text-muted small text-center mt-2">Shipment code: <span class="font-monospace">{{ $qr }}</span></div>
      @else
        <div class="text-center">
          <div class="display-6 text-danger"><i class="bi bi-exclamation-octagon-fill"></i></div>
          <h5 class="text-danger fw-bold">Shipment Not Recognised</h5>
          <p class="text-muted">The code <span class="font-monospace">{{ $qr }}</span> does not match any shipment in our system.</p>
        </div>
      @endif
    </div>
  </div>
</body>
</html>
