<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { color:#1e293b; font-size:12px; margin:0; }
  .wrap { padding:26px 30px; }
  .head { display:flex; border-bottom:2px solid #0d6efd; padding-bottom:12px; margin-bottom:16px; }
  .brand { font-size:20px; font-weight:bold; color:#0d6efd; }
  .title { text-align:right; } .title .t { font-size:16px; font-weight:bold; letter-spacing:1px; }
  .cards { width:100%; border-collapse:collapse; margin-bottom:16px; }
  .cards td { width:25%; border:1px solid #e2e8f0; padding:10px; text-align:center; }
  .cards .v { font-size:18px; font-weight:bold; } .cards .l { font-size:10px; color:#64748b; text-transform:uppercase; }
  .sec { font-size:11px; font-weight:bold; color:#0d6efd; text-transform:uppercase; letter-spacing:1px; margin:14px 0 6px; }
  table.data { width:100%; border-collapse:collapse; } table.data th { background:#f1f5f9; text-align:left; padding:6px 8px; font-size:11px; border-bottom:1px solid #e2e8f0; }
  table.data td { padding:6px 8px; border-bottom:1px solid #eef2f7; } .r { text-align:right; }
  .foot { margin-top:22px; color:#94a3b8; font-size:10px; text-align:center; border-top:1px solid #e2e8f0; padding-top:10px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="head">
    <div style="flex:1"><div class="brand">PharmaTrack</div><div style="font-size:11px;color:#64748b">Sales Report — {{ $mine ? 'My data' : 'Company-wide' }}</div></div>
    <div class="title"><div class="t">SALES REPORT</div>
      <div style="font-size:11px">Trend year: {{ $year }}@if($from || $to) · {{ $from ?: '…' }} → {{ $to ?: '…' }}@endif</div>
      <div style="font-size:10px;color:#94a3b8">Generated {{ $issued->format('d M Y, H:i') }}</div></div>
  </div>

  <table class="cards"><tr>
    <td><div class="v">{{ number_format($summary['total_sales'], 2) }}</div><div class="l">Total Sales</div></td>
    <td><div class="v">{{ $summary['orders'] }}</div><div class="l">Sales Orders</div></td>
    <td><div class="v">{{ number_format($summary['units']) }}</div><div class="l">Units Sold</div></td>
    <td><div class="v">{{ $summary['customers'] }}</div><div class="l">Customers</div></td>
  </tr></table>

  <div class="sec">Customer-wise Sales</div>
  <table class="data"><thead><tr><th>Customer</th><th class="r">Orders</th><th class="r">Sales Value</th></tr></thead><tbody>
    @forelse($customerWise as $c)<tr><td>{{ $c['name'] }}</td><td class="r">{{ $c['orders'] }}</td><td class="r">{{ number_format($c['value'], 2) }}</td></tr>
    @empty<tr><td colspan="3">No data in range.</td></tr>@endforelse
  </tbody></table>

  <div class="sec">Product-wise Sales</div>
  <table class="data"><thead><tr><th>Product</th><th class="r">Quantity</th><th class="r">Sales Value</th></tr></thead><tbody>
    @forelse($productWise as $p)<tr><td>{{ $p['name'] }}</td><td class="r">{{ number_format($p['qty']) }}</td><td class="r">{{ number_format($p['value'], 2) }}</td></tr>
    @empty<tr><td colspan="3">No data in range.</td></tr>@endforelse
  </tbody></table>

  <div class="sec">Monthly Sales — {{ $year }}</div>
  <table class="data"><thead><tr><th>Month</th><th class="r">Sales Value</th></tr></thead><tbody>
    @foreach($monthly as $m)<tr><td>{{ $m['label'] }}</td><td class="r">{{ number_format($m['value'], 2) }}</td></tr>@endforeach
  </tbody></table>

  <div class="foot">PharmaTrack Sales Report · {{ $issued->format('d M Y, H:i') }}</div>
</div>
</body>
</html>
