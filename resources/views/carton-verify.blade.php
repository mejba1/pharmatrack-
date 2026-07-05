<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Master Carton — PharmaTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>body{background:#f6f8fb} .card{max-width:520px;margin:6vh auto;border:none;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08)}</style>
</head>
<body>
  <div class="card">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <i class="bi bi-box-seam text-primary" style="font-size:30px"></i>
        <div class="fw-bold">Pharma<span class="text-primary">Track</span> · Master Carton</div>
      </div>

      @if($carton)
        @php $expired = $carton->batch?->expiry_date && $carton->batch->expiry_date->isPast(); @endphp
        <div class="text-center mb-3">
          <div class="display-6 text-success"><i class="bi bi-box2-heart-fill"></i></div>
          <h4 class="fw-bold mb-0 font-monospace">{{ $carton->carton_number }}</h4>
          <span class="badge-status {{ $carton->status_badge_class }}">{{ $carton->status_label }}</span>
        </div>

        <div class="fw-semibold text-muted mb-1" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em">Carton Information</div>
        <table class="table table-sm mb-3">
          <tr><td class="text-muted">Carton Type</td><td class="text-end">{{ $carton->is_mixed ? 'Mixed' : ($carton->carton_type === 'generic' ? 'Generic' : 'Standard') }}</td></tr>
          <tr><td class="text-muted">Product</td><td class="fw-semibold text-end">{{ $carton->products_summary }}</td></tr>
          <tr><td class="text-muted">Batch Reference</td><td class="text-end">{{ $carton->batches_summary }}</td></tr>
          <tr><td class="text-muted">Capacity</td><td class="text-end">{{ number_format($carton->capacity) }}</td></tr>
          <tr><td class="text-muted">Packed Quantity</td><td class="text-end fw-semibold">{{ number_format($carton->packed_quantity) }}</td></tr>
        </table>

        <div class="fw-semibold text-muted mb-1" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em">Packed Contents</div>
        @if($carton->contents->count())
        <table class="table table-sm mb-3">
          <thead><tr><th>Product</th><th>Batch</th><th>Mfg / Exp</th><th class="text-end">Serials</th></tr></thead>
          <tbody>
            @foreach($carton->contents as $ct)
            <tr>
              <td style="font-size:12px">{{ $ct->product?->name ?? '—' }}</td>
              <td class="font-monospace" style="font-size:11px">{{ $ct->batch?->brn ?? '—' }}</td>
              <td style="font-size:11px">{{ optional($ct->batch?->manufacture_date)->format('M Y') ?? '—' }} / {{ optional($ct->batch?->expiry_date)->format('M Y') ?? '—' }}</td>
              <td class="text-end font-monospace" style="font-size:11px">{{ $ct->serial_start }}–{{ $ct->serial_end }} <span class="text-muted">({{ $ct->quantity }})</span></td>
            </tr>
            @endforeach
          </tbody>
          <tfoot><tr><th colspan="3" class="text-end">Total Packed Units</th><th class="text-end">{{ number_format($carton->packed_quantity) }}</th></tr></tfoot>
        </table>
        @else
        <div class="alert alert-warning py-2" style="font-size:13px"><i class="bi bi-exclamation-triangle me-1"></i>This carton has not been packed yet.</div>
        @endif

        <div class="text-muted small text-center">Carton code: <span class="font-monospace">{{ $qr }}</span></div>
      @else
        <div class="text-center">
          <div class="display-6 text-danger"><i class="bi bi-exclamation-octagon-fill"></i></div>
          <h5 class="text-danger fw-bold">Carton Not Recognised</h5>
          <p class="text-muted">The code <span class="font-monospace">{{ $qr }}</span> does not match any master carton in our system.</p>
        </div>
      @endif
    </div>
  </div>
</body>
</html>
