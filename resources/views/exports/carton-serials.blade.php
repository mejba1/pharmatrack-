<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 12mm 10mm; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; margin: 0; }
  .head { border-bottom: 2px solid #0d6efd; padding-bottom: 6px; margin-bottom: 10px; }
  .head h1 { margin: 0; font-size: 17px; }
  .head .mc { font-family: DejaVu Sans Mono, monospace; }
  .head .meta { color: #666; font-size: 9px; margin-top: 2px; }
  .product { margin-bottom: 12px; page-break-inside: avoid; }
  .product-name { font-size: 13px; font-weight: bold; background: #eef3fb; padding: 5px 8px; border-left: 3px solid #0d6efd; }
  .batch-name { font-size: 11px; font-weight: bold; color: #333; margin: 8px 0 4px; }
  .batch-name .count { color: #888; font-weight: normal; font-size: 9px; }
  table.serials { width: 100%; border-collapse: collapse; }
  table.serials td { border: 1px solid #d7dde5; padding: 3px 4px; font-family: DejaVu Sans Mono, monospace; font-size: 9px; text-align: center; width: 12.5%; }
  .foot { margin-top: 10px; padding-top: 6px; border-top: 1px solid #ddd; color: #999; font-size: 8px; }
  .tag { font-size: 8px; border: 1px solid #999; border-radius: 3px; padding: 0 3px; }
</style>
</head>
<body>
  <div class="head">
    <h1>Master Carton Serials — <span class="mc">{{ $carton->carton_number }}</span>
      @if($carton->carton_type === 'generic')<span class="tag">GENERIC</span>@endif
      @if($carton->is_mixed)<span class="tag">MIXED</span>@endif
    </h1>
    <div class="meta">
      Capacity {{ number_format($carton->capacity) }} · Packed {{ number_format($carton->packed_quantity) }} ·
      Status {{ $carton->status_label }} · QR {{ $carton->qr_code }} ·
      Generated {{ now()->format('M d, Y H:i') }}
    </div>
  </div>

  @foreach($groups as $productName => $batches)
  <div class="product">
    <div class="product-name">{{ $productName }}</div>
    @foreach($batches as $brn => $serials)
    <div class="batch-name">Batch: {{ $brn }} <span class="count">· {{ number_format(count($serials)) }} serial(s)</span></div>
    <table class="serials">
      @foreach(array_chunk($serials, 8) as $row)
      <tr>
        @foreach($row as $serial)<td>{{ $serial }}</td>@endforeach
        @for($i = count($row); $i < 8; $i++)<td></td>@endfor
      </tr>
      @endforeach
    </table>
    @endforeach
  </div>
  @endforeach

  <div class="foot">PharmaTrack · Master Carton serial manifest · {{ $carton->carton_number }} · {{ number_format($carton->packed_quantity) }} units</div>
</body>
</html>
