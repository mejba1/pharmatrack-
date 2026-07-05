<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
  h2 { margin: 0 0 2px; }
  .muted { color: #666; font-size: 10px; margin-bottom: 10px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
  th { background: #f0f0f0; }
  td.code { font-family: DejaVu Sans Mono, monospace; }
</style>
</head>
<body>
  <h2>{{ $label }} — {{ $batch->product?->name ?? 'Product' }}</h2>
  <div class="muted">
    BRN: {{ $batch->brn }} · Batch: {{ $batch->batch_number }} · Lot: {{ $batch->lot_number ?? '—' }}
    · Total: {{ number_format(count($rows)) }} units · Generated {{ now()->format('M d, Y H:i') }}
  </div>
  <table>
    <thead><tr><th style="width:80px">Serial</th><th>{{ $label }}</th></tr></thead>
    <tbody>
      @foreach($rows as $r)
        <tr><td>{{ $r->serial_number }}</td><td class="code">{{ $r->{$field} }}</td></tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
