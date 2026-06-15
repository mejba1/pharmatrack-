<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 8mm; }
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; margin: 0; }
  .page { page-break-after: always; }
  .page:last-child { page-break-after: auto; }
  .sheet-head { margin-bottom: 4mm; }
  .sheet-head h2 { margin: 0; font-size: 13px; }
  .sheet-head .sub { color: #666; font-size: 8px; }
  table.grid { width: 100%; border-collapse: separate; border-spacing: 3mm; table-layout: fixed; }
  table.grid td.cell { width: 33.33%; height: 82mm; padding: 0; vertical-align: top; }
  .label { border: 1px dashed #888; height: 82mm; padding: 4mm 3mm; text-align: center; }
  .mc { font-family: DejaVu Sans Mono, monospace; font-weight: bold; font-size: 13px; }
  .tag { font-size: 7px; font-weight: bold; border: 1px solid #555; border-radius: 3px; padding: 0 3px; color: #333; }
  .qr-wrap { margin: 2mm 0; }
  .qr-wrap img { width: 36mm; height: 36mm; }
  .scan { font-size: 7px; color: #888; margin-bottom: 2mm; }
  table.meta { width: 100%; border-collapse: collapse; margin-top: 1mm; }
  table.meta td { font-size: 8.5px; padding: 1.2mm 1mm; border-bottom: 1px solid #eee; text-align: left; }
  table.meta td.k { color: #888; width: 34%; }
  table.meta td.v { color: #111; font-weight: bold; word-wrap: break-word; }
  .foot { font-size: 7px; color: #aaa; margin-top: 2mm; }
</style>
</head>
<body>
  @foreach($consignments->chunk(9) as $page)
  <div class="page">
    <div class="sheet-head">
      <h2>Shipment QR Labels — {{ $title }}</h2>
      <div class="sub">{{ number_format($consignments->count()) }} shipments · cut along the dashed lines · generated {{ now()->format('M d, Y H:i') }}</div>
    </div>
    <table class="grid">
      @foreach($page->chunk(3) as $row)
      <tr>
        @foreach($row as $s)
        <td class="cell">
          <div class="label">
            <div class="mc">{{ $s->consignment_number }}</div>
            <div style="margin:1mm 0"><span class="tag">{{ strtoupper($s->status_label) }}</span></div>
            <div class="qr-wrap">
              <img src="https://api.qrserver.com/v1/create-qr-code/?format=svg&size=200x200&margin=0&data={{ urlencode(route('shipment.scan', $s->qr_code)) }}" alt="QR">
            </div>
            <div class="scan">Scan to verify · {{ $s->qr_code }}</div>
            <table class="meta">
              <tr><td class="k">To</td><td class="v">{{ \Illuminate\Support\Str::limit($s->destination ?? '—', 28) }}</td></tr>
              <tr><td class="k">From</td><td class="v">{{ $s->origin }}</td></tr>
              <tr><td class="k">Cartons</td><td class="v">{{ number_format($s->carton_count) }}</td></tr>
              <tr><td class="k">Units</td><td class="v">{{ number_format($s->total_units) }}</td></tr>
            </table>
            <div class="foot">PharmaTrack · Shipment · {{ $s->created_at?->format('d M Y') }}</div>
          </div>
        </td>
        @endforeach
        @for($i = $row->count(); $i < 3; $i++)<td class="cell"></td>@endfor
      </tr>
      @endforeach
    </table>
  </div>
  @endforeach
</body>
</html>
