<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Shipment QR Labels — {{ $title }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
  <style>
    :root { --cut:#9aa0a6; }
    body { background:#eef1f5; color:#1a1a1a; }
    .toolbar { padding:12px 16px; border-bottom:1px solid #e2e6ea; position:sticky; top:0; background:#fff; z-index:5; }
    .sheet { width:210mm; min-height:297mm; margin:14px auto; background:#fff; padding:8mm; box-shadow:0 2px 14px rgba(0,0,0,.12); }
    .sheet-head { text-align:center; margin-bottom:5mm; }
    .sheet-head h1 { font-size:15px; font-weight:700; margin:0; }
    .sheet-head .sub { font-size:10px; color:#777; }
    .grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:3mm; }
    .label { position:relative; border:1px dashed var(--cut); border-radius:6px; height:84mm;
             padding:5mm 3mm 3mm; text-align:center; display:flex; flex-direction:column; align-items:center; }
    .label .scissor { position:absolute; top:-9px; left:6px; font-size:12px; color:var(--cut); background:#fff; padding:0 2px; }
    .label .mc { font-family:ui-monospace,monospace; font-weight:700; font-size:15px; }
    .label .tags { margin:3px 0 5px; min-height:14px; }
    .label .tag { font-size:8px; font-weight:700; border:1px solid #666; border-radius:3px; padding:0 4px; color:#444; }
    .label .qr { line-height:0; }
    .label .qr canvas, .label .qr img { width:33mm !important; height:33mm !important; }
    .label .scan { font-size:8px; color:#999; margin:3px 0 4px; }
    .label .meta { width:100%; font-size:9.5px; text-align:left; margin-top:auto; }
    .label .meta .r { display:flex; justify-content:space-between; gap:6px; padding:1.5px 2px; border-bottom:1px solid #f0f0f0; }
    .label .meta .k { color:#999; flex-shrink:0; }
    .label .meta .v { font-weight:600; text-align:right; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .label .foot { font-size:7.5px; color:#bbb; margin-top:3px; }
    @media print {
      body { background:#fff; }
      .toolbar, .pager { display:none !important; }
      .sheet { margin:0; box-shadow:none; width:auto; min-height:auto; padding:0; page-break-after:always; }
      .sheet:last-child { page-break-after:auto; }
      .label { break-inside:avoid; }
      @page { size:A4 portrait; margin:8mm; }
    }
  </style>
</head>
<body>
  <div class="toolbar d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <strong>{{ $title }}</strong>
      <span class="text-muted">· {{ number_format($consignments->total()) }} shipments · 9 per page · cut along the dashed lines</span>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / Save PDF</button>
      <a class="btn btn-outline-danger btn-sm" href="{{ route('shipments.labels-pdf', request()->only('status','destination','search','date_from','date_to','ids')) }}"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('shipments') }}">Close</a>
    </div>
  </div>

  @foreach($consignments->getCollection()->chunk(9) as $page)
  <div class="sheet">
    <div class="sheet-head">
      <h1>Shipment QR Labels — {{ $title }}</h1>
      <div class="sub">Generated {{ now()->format('M d, Y H:i') }}</div>
    </div>
    <div class="grid">
      @foreach($page as $s)
      <div class="label">
        <span class="scissor"><i class="bi bi-scissors"></i></span>
        <div class="mc">{{ $s->consignment_number }}</div>
        <div class="tags"><span class="tag">{{ strtoupper($s->status_label) }}</span></div>
        <div class="qr" data-qr="{{ route('shipment.scan', $s->qr_code) }}"></div>
        <div class="scan">Scan to verify · {{ $s->qr_code }}</div>
        <div class="meta">
          <div class="r"><span class="k">To</span><span class="v" title="{{ $s->destination }}">{{ $s->destination ?? '—' }}</span></div>
          <div class="r"><span class="k">From</span><span class="v">{{ $s->origin }}</span></div>
          <div class="r"><span class="k">Cartons</span><span class="v">{{ number_format($s->carton_count) }}</span></div>
          <div class="r"><span class="k">Units</span><span class="v">{{ number_format($s->total_units) }}</span></div>
        </div>
        <div class="foot">PharmaTrack · Shipment · {{ $s->created_at?->format('d M Y') }}</div>
      </div>
      @endforeach
    </div>
  </div>
  @endforeach

  @if($consignments->hasPages())
  <div class="pager d-flex justify-content-center py-3">{{ $consignments->links('pagination::bootstrap-5') }}</div>
  @endif

  <script>
    document.querySelectorAll('.qr').forEach(function(el){
      new QRCode(el, { text: el.dataset.qr, width: 135, height: 135, correctLevel: QRCode.CorrectLevel.M });
    });
  </script>
</body>
</html>
