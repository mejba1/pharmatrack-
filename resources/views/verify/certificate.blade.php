<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
@php
  $brand     = $cfg['primary'] ?? '#059669';
  $brandName = $cfg['brand_name'] ?? 'PharmaTrack';
  $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d M Y') : '—';
@endphp
<style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { color: #1e293b; font-size: 12px; margin: 0; padding: 0; }
  .wrap { border: 3px solid {{ $brand }}; padding: 26px 30px; margin: 18px; }
  .head { text-align: center; border-bottom: 2px solid {{ $brand }}; padding-bottom: 14px; margin-bottom: 18px; }
  .brand { font-size: 20px; font-weight: bold; color: {{ $brand }}; letter-spacing: .5px; }
  .title { font-size: 15px; font-weight: bold; margin-top: 6px; text-transform: uppercase; letter-spacing: 2px; }
  .badge { display: inline-block; margin-top: 10px; background: {{ $brand }}; color: #fff; font-weight: bold;
           padding: 6px 18px; border-radius: 20px; font-size: 12px; letter-spacing: 1px; }
  .statement { text-align: center; font-size: 12.5px; margin: 14px 0 20px; color: #334155; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  td { padding: 7px 10px; border: 1px solid #e2e8f0; vertical-align: top; }
  td.k { background: #f8fafc; font-weight: bold; width: 32%; color: #475569; }
  .section { font-size: 11px; font-weight: bold; color: {{ $brand }}; text-transform: uppercase;
             letter-spacing: 1px; margin: 6px 0; }
  .foot { margin-top: 22px; border-top: 1px solid #e2e8f0; padding-top: 12px; color: #94a3b8;
          font-size: 10px; text-align: center; }
  .vn { font-family: DejaVu Sans Mono, monospace; }
</style>
</head>
<body>
<div class="wrap">
  <div class="head">
    <div class="brand">{{ $brandName }}</div>
    <div class="title">Certificate of Authenticity</div>
    <div class="badge">&#10003; GENUINE PRODUCT</div>
  </div>

  <div class="statement">
    This certifies that the product bearing the unique verification code below has been
    checked against the manufacturer's records and is confirmed to be a <strong>genuine, authentic product</strong>.
  </div>

  <div class="section">Product Information</div>
  <table>
    <tr><td class="k">Product</td><td>{{ $product?->name ?? '—' }}</td></tr>
    <tr><td class="k">Generic</td><td>{{ $product?->generic_name ?? '—' }}</td></tr>
    <tr><td class="k">Strength / Form</td><td>{{ trim(($product?->strength ?? '') . ' ' . ($product?->dosage_form ?? '')) ?: '—' }}</td></tr>
    <tr><td class="k">Manufacturer</td><td>{{ $product?->manufacturer_name ?? '—' }}</td></tr>
    <tr><td class="k">Country of Origin</td><td>{{ $product?->country_of_origin ?? '—' }}</td></tr>
  </table>

  <div class="section">Batch &amp; Code</div>
  <table>
    <tr><td class="k">Verification Code (UUC)</td><td class="vn">{{ $code }}</td></tr>
    <tr><td class="k">Verification No.</td><td class="vn">{{ $log?->verification_number ?? '—' }}</td></tr>
    <tr><td class="k">Batch (BRN)</td><td>{{ $batch?->brn ?? '—' }} @if($batch?->batch_number) · {{ $batch->batch_number }} @endif</td></tr>
    <tr><td class="k">Manufactured</td><td>{{ $fmt($batch?->manufacture_date) }}</td></tr>
    <tr><td class="k">Expires</td><td>{{ $fmt($batch?->expiry_date) }}</td></tr>
  </table>

  <div class="section">Verification Details</div>
  <table>
    <tr><td class="k">Issued To</td><td>{{ $visitor['name'] ?? '—' }}@if(!empty($visitor['phone'])) · {{ $visitor['phone'] }}@endif</td></tr>
    <tr><td class="k">Location</td><td>{{ trim(($visitor['city'] ?? '') . ', ' . ($visitor['country'] ?? ''), ', ') ?: ($log?->city . ', ' . $log?->country) }}</td></tr>
    <tr><td class="k">Scan Origin</td><td>{{ $log?->city ?? '—' }}{{ $log?->country ? ', ' . $log->country : '' }}</td></tr>
    <tr><td class="k">Issued On</td><td>{{ $issued->format('d M Y, H:i') }}</td></tr>
  </table>

  <div class="foot">
    {{ $cfg['footer'] ?? 'Protected by ' . $brandName . ' Anti-Counterfeit' }}<br>
    This document is computer-generated from a live product verification. Verification code: <span class="vn">{{ $code }}</span>
  </div>
</div>
</body>
</html>
