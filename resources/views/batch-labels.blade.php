<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Labels — {{ $batch->brn }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
  <style>
    body { background:#fff; }
    .toolbar { padding:12px 16px; border-bottom:1px solid #eee; position:sticky; top:0; background:#fff; z-index:5; }
    .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px,1fr)); gap:10px; padding:16px; }
    .label { border:1px solid #ccc; border-radius:8px; padding:8px; text-align:center; page-break-inside:avoid; }
    .label .qr { display:flex; justify-content:center; }
    .label .num { font-family:monospace; font-size:12px; margin-top:4px; font-weight:600; }
    .label .sec { font-family:monospace; font-size:10px; color:#555; }
    .label .ser { font-size:10px; color:#888; }
    @media print { .toolbar { display:none; } .grid { padding:0; } .label { border:1px solid #999; } }
  </style>
</head>
<body>
  <div class="toolbar d-flex align-items-center justify-content-between">
    <div>
      <strong>{{ $batch->product?->name ?? 'Product' }}</strong> — Batch {{ $batch->batch_number }}
      <span class="text-muted">({{ $batch->brn }})</span>
      <span class="text-muted">· page {{ $units->currentPage() }}/{{ $units->lastPage() }} · {{ number_format($units->total()) }} units</span>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi"></i>Print / Save PDF</button>
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('batches.units', $batch) }}">Close</a>
    </div>
  </div>

  <div class="grid">
    @foreach($units as $unit)
      <div class="label">
        <div class="qr" data-qr="{{ url('verify/'.$unit->secret_code) }}"></div>
        <div class="num">{{ $unit->unique_number }}</div>
        <div class="sec">{{ $unit->secret_code }}</div>
        <div class="ser">#{{ $unit->serial_number }}</div>
      </div>
    @endforeach
  </div>

  @if($units->hasPages())
  <div class="d-flex justify-content-center py-3">{{ $units->links('pagination::bootstrap-5') }}</div>
  @endif

  <script>
    document.querySelectorAll('.qr').forEach(function(el){
      new QRCode(el, { text: el.dataset.qr, width: 110, height: 110, correctLevel: QRCode.CorrectLevel.M });
    });
  </script>
</body>
</html>
