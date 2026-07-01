@php
  $pb = \App\Support\PortalSettings::all();
  $pbl = \App\Support\PortalSettings::logoUrl();
  $white = $white ?? false;
  $h = $h ?? 30;
  $cls = $cls ?? 'fs-5';
@endphp
@if($pbl)
  <img src="{{ $pbl }}" alt="{{ $pb['portal_brand_name'] }}" style="height:{{ $h }}px;max-width:180px;object-fit:contain">
@else
  <span class="brand {{ $cls }}" @if($white) style="color:#fff" @endif>{{ $pb['portal_brand_name'] }}</span>
@endif
