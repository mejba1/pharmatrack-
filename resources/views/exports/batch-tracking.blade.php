<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
  h2 { margin: 0 0 2px; }
  .muted { color: #666; font-size: 10px; margin-bottom: 4px; }
  .summary { margin: 8px 0 12px; font-size: 11px; }
  .summary strong { font-size: 13px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
  th { background: #f0f0f0; }
  td.num { text-align: right; }
  .ref { font-family: DejaVu Sans Mono, monospace; }
  .tag-original { color: #0d6efd; font-weight: bold; }
  .tag-partial  { color: #0a93b0; font-weight: bold; }
</style>
</head>
<body>
  <h2>Quantity Tracking Log — {{ $data['product_name'] ?? 'Product' }}</h2>
  <div class="muted">
    BRN: {{ $data['brn'] }} · Batch: {{ $batch->batch_number }} · Lot: {{ $batch->lot_number ?? '—' }}
    · Generated {{ now()->format('M d, Y H:i') }}
  </div>
  <div class="summary">
    Original Produced: <strong>{{ number_format($data['quantity_produced']) }}</strong> &nbsp;·&nbsp;
    Added via Partials: <strong>+{{ number_format($data['quantity_extended']) }}</strong> &nbsp;·&nbsp;
    Total Quantity: <strong>{{ number_format($data['total_quantity']) }}</strong>
  </div>
  <table>
    <thead>
      <tr>
        <th style="width:24px">#</th>
        <th style="width:80px">Type</th>
        <th>Reference</th>
        <th class="num" style="width:60px">Qty</th>
        <th style="width:90px">Serials</th>
        <th style="width:70px">Mode</th>
        <th style="width:70px">Mfg</th>
        <th style="width:70px">Expiry</th>
        <th class="num" style="width:70px">Running</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data['entries'] as $i => $e)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td class="tag-{{ $e['type'] }}">{{ $e['label'] }}</td>
          <td class="ref">{{ $e['reference'] }}</td>
          <td class="num">{{ $e['type'] === 'original' ? '' : '+' }}{{ number_format($e['quantity']) }}</td>
          <td>{{ number_format($e['serial_start']) }}–{{ number_format($e['serial_end']) }}</td>
          <td>{{ $e['serial_mode'] ?? '—' }}</td>
          <td>{{ $e['manufacture_date'] ?? '—' }}</td>
          <td>{{ $e['expiry_date'] ?? '—' }}</td>
          <td class="num">{{ number_format($e['running_total']) }}</td>
          <td>{{ $e['notes'] ?? '' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
