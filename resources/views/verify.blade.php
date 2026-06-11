<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Product Verification — PharmaTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>body{background:#f6f8fb} .card{max-width:480px;margin:6vh auto;border:none;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08)}</style>
</head>
<body>
  <div class="card">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <i class="bi bi-capsule-pill text-primary" style="font-size:30px"></i>
        <div class="fw-bold">Pharma<span class="text-primary">Track</span> Verification</div>
      </div>

      @if($unit)
        @php
          $expired = $unit->batch?->expiry_date && $unit->batch->expiry_date->isPast();
          $blocked = in_array($unit->status, ['blocked','inactive']);
          $ok = !$expired && !$blocked;
        @endphp
        <div class="text-center mb-3">
          @if($ok)
            <div class="display-6 text-success"><i class="bi bi-patch-check-fill"></i></div>
            <h5 class="text-success fw-bold mb-0">Authentic Product</h5>
          @elseif($expired)
            <div class="display-6 text-danger"><i class="bi bi-clock-history"></i></div>
            <h5 class="text-danger fw-bold mb-0">Expired Product</h5>
          @else
            <div class="display-6 text-danger"><i class="bi bi-x-octagon-fill"></i></div>
            <h5 class="text-danger fw-bold mb-0">Not Valid for Sale ({{ ucfirst($unit->status) }})</h5>
          @endif
        </div>
        <table class="table table-sm">
          <tr><td class="text-muted">Product</td><td class="fw-semibold text-end">{{ $unit->batch?->product?->name ?? '—' }}</td></tr>
          <tr><td class="text-muted">PRN</td><td class="text-end font-monospace">{{ $unit->batch?->product?->prn ?? '—' }}</td></tr>
          <tr><td class="text-muted">Batch</td><td class="text-end">{{ $unit->batch?->batch_number }}</td></tr>
          <tr><td class="text-muted">Label No.</td><td class="text-end font-monospace">{{ $unit->unique_number }}</td></tr>
          <tr><td class="text-muted">Serial</td><td class="text-end">#{{ $unit->serial_number }}</td></tr>
          <tr><td class="text-muted">Expiry</td><td class="text-end">{{ optional($unit->batch?->expiry_date)->format('M d, Y') ?? '—' }}</td></tr>
          <tr><td class="text-muted">Unit Status</td><td class="text-end">{{ ucfirst($unit->status) }}</td></tr>
        </table>
        <div class="text-muted small text-center">Verified code: <span class="font-monospace">{{ $code }}</span></div>
      @else
        <div class="text-center">
          <div class="display-6 text-danger"><i class="bi bi-exclamation-octagon-fill"></i></div>
          <h5 class="text-danger fw-bold">Code Not Recognised</h5>
          <p class="text-muted">The code <span class="font-monospace">{{ $code }}</span> does not match any product in our system. This item may be counterfeit.</p>
        </div>
      @endif
    </div>
  </div>
</body>
</html>
