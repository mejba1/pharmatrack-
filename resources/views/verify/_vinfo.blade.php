{{-- Shared verification-info rows. Expects $verification, $scanStats, $cfg. --}}
<div class="flex justify-between"><span class="text-slate-400">ID</span><span class="font-mono text-xs text-slate-600">{{ $verification->verification_number }}</span></div>
<div class="flex justify-between"><span class="text-slate-400">Scanned at</span><span class="text-slate-700">{{ $verification->created_at?->format('d M Y, H:i') }}</span></div>
<div class="flex justify-between"><span class="text-slate-400">Total scans</span><span class="text-slate-700 font-semibold">{{ number_format($scanStats['count']) }}</span></div>
@if($cfg['show_country'])
  <div class="flex justify-between"><span class="text-slate-400">Country</span><span class="text-slate-700">{{ $verification->country ?? 'Unknown' }}</span></div>
@endif
@if($cfg['show_city'] && $verification->city)
  <div class="flex justify-between"><span class="text-slate-400">City</span><span class="text-slate-700">{{ $verification->city }}</span></div>
@endif
@if($cfg['show_ip'] && $verification->ip_address)
  <div class="flex justify-between"><span class="text-slate-400">IP address</span><span class="font-mono text-xs text-slate-600">{{ $verification->ip_address }}</span></div>
@endif
@if($cfg['show_device'])
  @php $dev = trim(implode(' · ', array_filter([ucfirst($verification->device_type ?? ''), $verification->browser, $verification->os]))); @endphp
  @if($dev)
    <div class="flex justify-between gap-3"><span class="text-slate-400 shrink-0">Device</span><span class="text-slate-700 text-right truncate">{{ $dev }}</span></div>
  @endif
@endif
