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

  /* Dashed border = cut guide */
  .label { border: 1px dashed #888; height: 82mm; padding: 4mm 3mm; text-align: center; }

  .mc { font-family: DejaVu Sans Mono, monospace; font-weight: bold; font-size: 13px; letter-spacing: .5px; }
  .tags { margin: 1mm 0 2mm; }
  .tag { font-size: 7px; font-weight: bold; border: 1px solid #555; border-radius: 3px; padding: 0 3px; color: #333; }

  .qr-wrap { margin: 1mm 0 2mm; }
  .qr-wrap img { width: 38mm; height: 38mm; }
  .scan { font-size: 7px; color: #888; margin-bottom: 2mm; }

  table.meta { width: 100%; border-collapse: collapse; margin-top: 1mm; }
  table.meta td { font-size: 8.5px; padding: 1.2mm 1mm; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
  table.meta td.k { color: #888; width: 32%; }
  table.meta td.v { color: #111; font-weight: bold; word-wrap: break-word; }

  .foot { font-size: 7px; color: #aaa; margin-top: 2mm; }
</style>
</head>
<body>
  @foreach($cartons->chunk(9) as $pageIndex => $page)
  <div class="page">
    <div class="sheet-head">
      <h2>Master Carton Labels — {{ $title }}</h2>
      <div class="sub">{{ number_format($cartons->count()) }} cartons · cut along the dashed lines · generated {{ now()->format('M d, Y H:i') }}</div>
    </div>

    <table class="grid">
      @foreach($page->chunk(3) as $row)
      <tr>
        @foreach($row as $c)
        <td class="cell">
          <div class="label">
            <div class="mc">{{ $c->carton_number }}</div>
            <div class="tags">
              @if($c->carton_type === 'generic')<span class="tag">GENERIC</span>@endif
              @if($c->is_mixed)<span class="tag">MIXED</span>@endif
            </div>
            <div class="qr-wrap">
              <img src="https://api.qrserver.com/v1/create-qr-code/?format=svg&size=200x200&margin=0&data={{ urlencode(route('carton.scan', $c->qr_code)) }}" alt="QR">
            </div>
            <div class="scan">Scan to verify · {{ $c->qr_code }}</div>
            <table class="meta">
              <tr><td class="k">Product</td><td class="v">{{ \Illuminate\Support\Str::limit($c->products_summary, 40) }}</td></tr>
              <tr><td class="k">Batch</td><td class="v">{{ $c->batches_summary }}</td></tr>
              <tr><td class="k">Qty</td><td class="v">{{ number_format($c->packed_quantity) }} / {{ number_format($c->capacity) }}</td></tr>
              @if($c->serial_range !== '—')
              <tr><td class="k">Serials</td><td class="v">{{ $c->serial_range }}</td></tr>
              @endif
            </table>
            <div class="foot">PharmaTrack · Master Carton</div>
          </div>
        </td>
        @endforeach
        {{-- pad short rows so the 3-column layout stays aligned --}}
        @for($i = $row->count(); $i < 3; $i++)<td class="cell"></td>@endfor
      </tr>
      @endforeach
    </table>
  </div>
  @endforeach
</body>
</html>
