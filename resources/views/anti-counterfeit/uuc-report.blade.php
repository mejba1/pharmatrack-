<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
@php
  $brand     = $cfg['primary'] ?? '#059669';
  $brandName = $cfg['brand_name'] ?? 'PharmaTrack';
  $fmt  = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d M Y') : '—';
  $fmtT = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d M Y H:i') : '—';
  $resultColors = [
    'genuine' => '#16a34a', 'suspicious' => '#d97706', 'blocked' => '#dc2626',
    'locked' => '#dc2626', 'recalled' => '#dc2626', 'invalid' => '#dc2626', 'expired' => '#d97706',
  ];
@endphp
<style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { color: #1e293b; font-size: 11px; margin: 0; padding: 0; }
  .wrap { padding: 22px 26px; }
  .head { border-bottom: 3px solid {{ $brand }}; padding-bottom: 10px; margin-bottom: 14px; }
  .brand { font-size: 18px; font-weight: bold; color: {{ $brand }}; }
  .title { font-size: 13px; font-weight: bold; margin-top: 2px; color: #334155; }
  .muted { color: #94a3b8; font-size: 10px; }
  .code { font-family: DejaVu Sans Mono, monospace; }
  .section { font-size: 11px; font-weight: bold; color: {{ $brand }}; text-transform: uppercase;
             letter-spacing: 1px; margin: 16px 0 6px; }
  table { width: 100%; border-collapse: collapse; }
  .kv td { padding: 6px 9px; border: 1px solid #e2e8f0; vertical-align: top; }
  .kv td.k { background: #f8fafc; font-weight: bold; width: 24%; color: #475569; }
  .cards { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px; }
  .cards td { width: 16.6%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;
              text-align: center; padding: 8px 4px; }
  .cards .num { font-size: 17px; font-weight: bold; color: #0f172a; }
  .cards .lbl { font-size: 8.5px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
  .hist { margin-top: 4px; }
  .hist th { background: {{ $brand }}; color: #fff; text-align: left; padding: 6px 8px; font-size: 9.5px;
             text-transform: uppercase; letter-spacing: .4px; }
  .hist td { padding: 5px 8px; border-bottom: 1px solid #eef2f7; font-size: 10px; }
  .hist tr:nth-child(even) td { background: #fafbfc; }
  .pill { display: inline-block; padding: 1px 7px; border-radius: 10px; color: #fff; font-size: 9px; font-weight: bold; }
  .foot { margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 10px; color: #94a3b8; font-size: 9px; text-align: center; }
  .lock { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 7px 10px; border-radius: 6px; margin-top: 6px; font-size: 10px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="head">
    <table><tr>
      <td style="border:none;padding:0">
        <div class="brand">{{ $brandName }}</div>
        <div class="title">UUC Verification &amp; Scan Report</div>
      </td>
      <td style="border:none;padding:0;text-align:right" class="muted">
        UUC: <span class="code" style="color:#334155;font-weight:bold">{{ $unit->secret_code }}</span><br>
        Generated {{ $issued->format('d M Y, H:i') }}
      </td>
    </tr></table>
  </div>

  {{-- Summary cards --}}
  <table class="cards"><tr>
    <td><div class="num">{{ $summary['total'] }}</div><div class="lbl">Total Scans</div></td>
    <td><div class="num" style="color:{{ $summary['flagged'] ? '#dc2626' : '#0f172a' }}">{{ $summary['flagged'] }}</div><div class="lbl">Flagged Hits</div></td>
    <td><div class="num">{{ $summary['countries'] }}</div><div class="lbl">Countries</div></td>
    <td><div class="num">{{ $summary['ips'] }}</div><div class="lbl">Unique IPs</div></td>
    <td><div class="num">{{ $summary['devices'] }}</div><div class="lbl">Devices</div></td>
    <td><div class="num" style="font-size:11px">{{ $fmt($summary['last']) }}</div><div class="lbl">Last Scan</div></td>
  </tr></table>

  @if($unit->locked_at)
    <div class="lock"><strong>LOCKED</strong> {{ $unit->locked_at ? 'since ' . $fmtT($unit->locked_at) : '' }}
      @if($unit->lock_reason) — {{ $unit->lock_reason }} @endif
      @if(($unit->blocked_scan_count ?? 0) > 0) · {{ $unit->blocked_scan_count }} blocked attempts after lock @endif
    </div>
  @endif

  {{-- Product & batch --}}
  <div class="section">Product Details</div>
  <table class="kv">
    <tr><td class="k">Product</td><td>{{ $product?->name ?? '—' }}</td><td class="k">Generic</td><td>{{ $product?->generic_name ?? '—' }}</td></tr>
    <tr><td class="k">Strength / Form</td><td>{{ trim(($product?->strength ?? '') . ' ' . ($product?->dosage_form ?? '')) ?: '—' }}</td><td class="k">Manufacturer</td><td>{{ $product?->manufacturer_name ?? '—' }}</td></tr>
    <tr><td class="k">Country of Origin</td><td>{{ $product?->country_of_origin ?? '—' }}</td><td class="k">PRN</td><td class="code">{{ $product?->prn ?? '—' }}</td></tr>
    <tr><td class="k">Batch (BRN)</td><td>{{ $batch?->brn ?? '—' }} @if($batch?->batch_number)· {{ $batch->batch_number }}@endif</td><td class="k">Mfg / Exp</td><td>{{ $fmt($batch?->manufacture_date) }} — {{ $fmt($batch?->expiry_date) }}</td></tr>
    <tr><td class="k">Serial</td><td>#{{ $unit->serial_number }}</td><td class="k">Unit Status</td><td>{{ ucfirst($unit->status) }}</td></tr>
  </table>

  {{-- Scan history --}}
  <div class="section">Scan History ({{ $summary['total'] }})</div>
  <table class="hist">
    <thead><tr><th>#</th><th>Date &amp; Time</th><th>Result</th><th>IP Address</th><th>Location</th><th>Device</th><th>Risk</th></tr></thead>
    <tbody>
      @forelse($scans as $i => $s)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $fmtT($s->created_at) }}</td>
          <td><span class="pill" style="background:{{ $resultColors[$s->result] ?? '#64748b' }}">{{ strtoupper($s->result ?? '—') }}</span></td>
          <td class="code">{{ $s->ip_address ?? '—' }}@if($s->is_proxy) <span style="color:#d97706">⚠VPN</span>@endif</td>
          <td>{{ trim(($s->city ?? '') . ', ' . ($s->country ?? ''), ', ') ?: '—' }}</td>
          <td>{{ trim(($s->os ?? '') . ' / ' . ($s->browser ?? ''), ' /') ?: ($s->device_type ?? '—') }}</td>
          <td>{{ $s->risk_score ?? 0 }}</td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:14px">No scans recorded for this UUC yet.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if($alerts->isNotEmpty())
    <div class="section">Risk Alerts ({{ $alerts->count() }})</div>
    <table class="hist">
      <thead><tr><th>Date</th><th>Category</th><th>Level</th><th>Description</th></tr></thead>
      <tbody>
        @foreach($alerts as $al)
          <tr>
            <td>{{ $fmtT($al->created_at) }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $al->category)) }}</td>
            <td><span class="pill" style="background:{{ $al->risk_level === 'critical' || $al->risk_level === 'high' ? '#dc2626' : '#d97706' }}">{{ strtoupper($al->risk_level) }}</span></td>
            <td>{{ $al->description }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <div class="foot">
    {{ $cfg['footer'] ?? 'Protected by ' . $brandName . ' Anti-Counterfeit' }}<br>
    Confidential — generated from live verification records for UUC <span class="code">{{ $unit->secret_code }}</span>.
  </div>
</div>
</body>
</html>
