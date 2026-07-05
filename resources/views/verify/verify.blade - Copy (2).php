<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#059669">
  <title>Verify — PharmaTrack</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Inter',sans-serif;-webkit-tap-highlight-color:transparent}
    [x-cloak]{display:none!important}
    .no-sb::-webkit-scrollbar{display:none}.no-sb{-ms-overflow-style:none;scrollbar-width:none}
    .pb-safe{padding-bottom:calc(env(safe-area-inset-bottom) + .5rem)}
    @keyframes pop{0%{transform:scale(.5);opacity:0}60%{transform:scale(1.12)}100%{transform:scale(1);opacity:1}}
    .animate-pop{animation:pop .55s cubic-bezier(.34,1.56,.64,1) both}
    @keyframes ringPulse{0%{transform:scale(.85);opacity:.55}100%{transform:scale(1.9);opacity:0}}
    .ring-pulse{position:relative}
    .ring-pulse::before{content:'';position:absolute;inset:0;border-radius:9999px;background:currentColor;opacity:.35;animation:ringPulse 2.2s ease-out infinite}
    @keyframes rise{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
    .rise{animation:rise .4s ease both}
    .snap-x{scroll-snap-type:x mandatory}.snap-c{scroll-snap-align:center}
    .sheet-tr{transition:transform .34s cubic-bezier(.32,.72,0,1)}
  </style>
</head>
<body class="h-full bg-slate-100">

@php
  $product = $unit?->batch?->product;
  $batch   = $unit?->batch;
  $images  = $product?->images ?? collect();
@endphp

<div x-data="vApp()" class="relative mx-auto w-full max-w-[440px] min-h-screen bg-slate-50 shadow-2xl overflow-hidden">

@if($block)
  {{-- ═══════════════ BLOCKED / RESTRICTED ═══════════════ --}}
  @php
    $meta = [
      'fake'     => ['Not Genuine', 'This is NOT a Genuine Product', "This code doesn't match any product in our records. It may be counterfeit — do not use it.", 'rose', 'shield'],
      'recalled' => ['Recalled', 'Product Recalled', 'This product has been recalled. Do not use it and contact the manufacturer immediately.', 'rose', 'alert'],
      'locked'   => ['Restricted', 'Locked by Administrator', 'This product can only be shown by the administrator. No further information is available.', 'slate', 'lock'],
      'expired'  => ['Expired', 'Product Expired', 'This product has passed its expiry date and should not be used.', 'amber', 'clock'],
      'invalid'  => ['Invalid', 'Not Valid for Sale', 'This unit is marked not valid for sale.', 'rose', 'shield'],
      'country'  => ['Warning', 'May Be Counterfeit', 'This product is authorized for sale only in other countries. If you bought it in your country, it may be counterfeit — please report it.', 'rose', 'globe'],
      'city'     => ['Warning', 'Not Sold in Your City', 'This product is not authorized for sale in your city. If you bought it here, it may be counterfeit — please report it.', 'rose', 'globe'],
      'device'   => ['Limit', 'Device Limit Reached', 'This product has reached its allowed number of verification devices.', 'amber', 'lock'],
      'hit'      => ['Limit', 'Verification Limit Reached', 'This code has reached its maximum number of verifications.', 'amber', 'ban'],
    ][$block] ?? ['Notice', 'Not Verified', 'This product could not be verified.', 'slate', 'shield'];
    $grad = ['rose'=>'from-rose-500 to-red-600','amber'=>'from-amber-500 to-orange-500','slate'=>'from-slate-600 to-slate-800'][$meta[3]];
  @endphp

  <div class="min-h-screen flex flex-col">
    <div class="bg-gradient-to-b {{ $grad }} text-white px-6 pt-12 pb-16 text-center rounded-b-[2.5rem]">
      <div class="flex items-center justify-center gap-2 mb-8 opacity-90">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1l9 4v6c0 5.25-3.6 9.74-9 11-5.4-1.26-9-5.75-9-11V5l9-4z"/></svg>
        <span class="font-extrabold tracking-tight">PharmaTrack</span>
      </div>
      <div class="ring-pulse text-white/80 mx-auto w-24 h-24 mb-6">
        <div class="animate-pop relative w-24 h-24 rounded-full bg-white/15 backdrop-blur flex items-center justify-center ring-4 ring-white/25">
          @if($meta[4]==='lock')
            <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 0h10.5a2.25 2.25 0 012.25 2.25v6.75a2.25 2.25 0 01-2.25 2.25H6.75a2.25 2.25 0 01-2.25-2.25v-6.75a2.25 2.25 0 012.25-2.25z"/></svg>
          @elseif($meta[4]==='globe')
            <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5-2.5 2.5-15 0-18m0 18c-2.5-2.5-2.5-15 0-18M3.6 9h16.8M3.6 15h16.8"/></svg>
          @elseif($meta[4]==='clock')
            <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
          @elseif($meta[4]==='ban')
            <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.36 5.64a9 9 0 11-12.72 12.72 9 9 0 0112.72-12.72zM5.64 5.64l12.72 12.72"/></svg>
          @elseif($meta[4]==='alert')
            <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.34 3.94L1.7 18a1.5 1.5 0 001.3 2.25h18a1.5 1.5 0 001.3-2.25L13.66 3.94a1.5 1.5 0 00-2.62 0z"/></svg>
          @else
            <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
          @endif
        </div>
      </div>
      <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70 rise">{{ $meta[0] }}</p>
      <h1 class="text-2xl font-black mt-1 rise">{{ $meta[1] }}</h1>
    </div>

    <div class="flex-1 px-6 -mt-8">
      <div class="bg-white rounded-3xl shadow-lg ring-1 ring-slate-100 p-6 rise">
        <p class="text-[15px] leading-relaxed text-slate-600 text-center">{{ $meta[2] }}</p>
        <button @click="openSheet()" class="mt-5 w-full py-3.5 rounded-2xl bg-rose-600 text-white font-bold text-sm shadow-lg shadow-rose-200 active:scale-[.98] transition flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-6m0 0l2.77-.69a9 9 0 016.21.99 9 9 0 006.21.99L21 15V4l-2.81.7a9 9 0 01-6.21-.99 9 9 0 00-6.21-.99L3 4.5"/></svg>
          Report this product
        </button>
      </div>
      <p class="text-center text-xs text-slate-400 mt-6 mb-8">Protected by PharmaTrack Anti-Counterfeit</p>
    </div>
  </div>

@else
  {{-- ═══════════════ GENUINE ═══════════════ --}}
  @php
    $days = $batch?->days_to_expiry;
    $monthsLeft = ($days !== null && $days > 0) ? (int) round($days / 30) : null;
    $journey = [
      ['Manufactured', (bool) $batch?->manufacture_date],
      ['Packed', in_array($unit->status, ['packed','scanned','active','dispatched','received'], true)],
      ['Released', in_array($batch?->qc_status, ['released'], true)],
      ['Distributed', in_array($unit->status, ['dispatched','received','scanned','active'], true)],
      ['Verified', true],
    ];
    $eventLabels = ['scanned'=>'Scanned / Verified','units_generated'=>'Units Generated','units_removed'=>'Units Removed','printed'=>'Label Printed','packed'=>'Packed','dispatched'=>'Dispatched','received'=>'Received'];
  @endphp

  <div class="pb-28">
    {{-- Hero --}}
    <div class="bg-gradient-to-b from-emerald-500 to-green-600 text-white px-6 pt-12 pb-20 rounded-b-[2.5rem] relative">
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-2 opacity-95">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1l9 4v6c0 5.25-3.6 9.74-9 11-5.4-1.26-9-5.75-9-11V5l9-4z"/></svg>
          <span class="font-extrabold tracking-tight">PharmaTrack</span>
        </div>
        <button @click="openSheet()" class="text-white/80 hover:text-white text-xs flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-6m0 0l2.77-.69a9 9 0 016.21.99 9 9 0 006.21.99L21 15V4l-2.81.7a9 9 0 01-6.21-.99 9 9 0 00-6.21-.99L3 4.5"/></svg>
          Report
        </button>
      </div>
      <div class="text-center">
        <div class="ring-pulse text-white/80 mx-auto w-20 h-20 mb-4">
          <div class="animate-pop relative w-20 h-20 rounded-full bg-white/15 backdrop-blur flex items-center justify-center ring-4 ring-white/25">
            <svg class="w-11 h-11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
          </div>
        </div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/75 rise">Verified Authentic</p>
        <h1 class="text-2xl font-black mt-1 rise">{{ $product?->name ?? 'Genuine Product' }}</h1>
        <p class="text-sm text-white/80 mt-1 rise">{{ $product?->generic_name ?? '' }}</p>
      </div>
    </div>

    {{-- Product card overlap --}}
    <div class="px-5 -mt-12">
      <div class="bg-white rounded-3xl shadow-lg ring-1 ring-slate-100 p-4 flex items-center gap-4 rise">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 overflow-hidden flex items-center justify-center shrink-0">
          @if($images->first())
            <img src="{{ $images->first()->url }}" class="w-full h-full object-cover" alt="">
          @else
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          @endif
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-[11px] uppercase tracking-wider text-slate-400">Batch</p>
          <p class="font-mono text-sm font-semibold text-slate-700 truncate">{{ $batch?->brn ?? '—' }}</p>
        </div>
        @if($monthsLeft !== null)
          <div class="text-right shrink-0">
            <p class="text-[11px] uppercase tracking-wider text-slate-400">Expires in</p>
            <p class="text-sm font-bold text-emerald-600">{{ $monthsLeft }} mo</p>
          </div>
        @endif
      </div>
    </div>

    {{-- Tab content --}}
    <div class="px-5 mt-4">
      {{-- INFO --}}
      <div x-show="tab==='info'" class="rise">
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 divide-y divide-slate-50">
          @php
            $rows = [
              ['Product', $product?->name, 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
              ['Generic', $product?->generic_name, 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.3 24.3 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3'],
              ['Strength / Form', trim(($product?->strength ?? '').' '.($product?->dosage_form ? ucfirst(str_replace('_',' ',$product->dosage_form)) : '')), 'M9.75 3.104v5.714'],
              ['Batch No.', $batch?->batch_number, 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026'],
              ['Manufactured', optional($batch?->manufacture_date)->format('d M Y'), 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25'],
              ['Expires', optional($batch?->expiry_date)->format('d M Y'), 'M12 6v6l4 2'],
              ['Serial No.', '#'.$unit->serial_number, 'M7 3v18M3 7h18'],
              ['Manufacturer', $product?->manufacturer_name, 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18'],
              ['Country', $product?->country_of_origin, 'M12 21a9 9 0 100-18 9 9 0 000 18z'],
            ];
          @endphp
          @foreach($rows as $r)
            @if($r[1])
              <div class="flex items-center gap-3 px-4 py-3">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $r[2] }}"/></svg>
                <span class="text-sm text-slate-400 flex-1">{{ $r[0] }}</span>
                <span class="text-sm font-semibold text-slate-700 text-right">{{ $r[1] }}</span>
              </div>
            @endif
          @endforeach
        </div>
        {{-- Trust chips --}}
        <div class="grid grid-cols-3 gap-2 mt-3">
          @foreach([['Authentic','M4.5 12.75l6 6 9-13.5'],['In Date','M6.75 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25'],['Tracked','M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25']] as $chip)
            <div class="bg-white rounded-2xl ring-1 ring-slate-100 py-3 text-center">
              <svg class="w-5 h-5 mx-auto text-emerald-500 mb-1" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chip[1] }}"/></svg>
              <p class="text-[11px] font-semibold text-slate-500">{{ $chip[0] }}</p>
            </div>
          @endforeach
        </div>
      </div>

      {{-- IMAGES --}}
      <div x-show="tab==='images'" x-cloak class="rise">
        @if($images->count())
          <div class="flex gap-3 overflow-x-auto snap-x no-sb -mx-1 px-1 pb-2">
            @foreach($images as $img)
              <div class="snap-c shrink-0 w-[82%] rounded-3xl overflow-hidden ring-1 ring-slate-100 bg-white aspect-square">
                <img src="{{ $img->url }}" class="w-full h-full object-cover" alt="{{ $img->original_name }}">
              </div>
            @endforeach
          </div>
          <p class="text-center text-xs text-slate-400 mt-2">Swipe to see all {{ $images->count() }} official images →</p>
        @else
          <div class="bg-white rounded-3xl ring-1 ring-slate-100 py-14 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-200 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.16-5.16a2.25 2.25 0 013.18 0l5.16 5.16m-1.5-1.5l1.41-1.41a2.25 2.25 0 013.18 0l2.16 2.16M21 19.5V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v13.5"/></svg>
            <p class="text-sm font-semibold text-slate-500">No images available</p>
          </div>
        @endif
      </div>

      {{-- TRACKING --}}
      <div x-show="tab==='tracking'" x-cloak class="rise">
        {{-- Journey --}}
        <div class="bg-white rounded-3xl ring-1 ring-slate-100 p-5 mb-3">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-4">Supply Journey</p>
          <div class="flex items-center justify-between">
            @foreach($journey as $i => $stage)
              <div class="flex flex-col items-center text-center flex-1 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white {{ $stage[1] ? 'bg-emerald-500' : 'bg-slate-200 text-slate-400' }}">
                  @if($stage[1])<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>@else<span class="text-[11px]">{{ $i+1 }}</span>@endif
                </div>
                <span class="mt-1.5 text-[9px] font-semibold leading-tight {{ $stage[1] ? 'text-slate-600' : 'text-slate-400' }}">{{ $stage[0] }}</span>
              </div>
              @if(!$loop->last)<div class="h-1 flex-1 rounded mb-4 {{ ($journey[$i+1][1] ?? false) ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>@endif
            @endforeach
          </div>
        </div>

        @if($verification)
        <div class="bg-white rounded-3xl ring-1 ring-slate-100 p-5 mb-3">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Verification</p>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-400">ID</span><span class="font-mono text-xs text-slate-600">{{ $verification->verification_number }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Scanned at</span><span class="text-slate-700">{{ $verification->created_at?->format('d M Y, H:i') }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Total scans</span><span class="text-slate-700 font-semibold">{{ number_format($scanStats['count']) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Location</span><span class="text-slate-700">{{ $verification->country ?? 'Unknown' }}</span></div>
          </div>
        </div>
        @endif

        <div class="bg-white rounded-3xl ring-1 ring-slate-100 p-5">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-4">History</p>
          @forelse($logs as $log)
            <div class="flex gap-3 {{ $loop->last ? '' : 'pb-4' }}">
              <div class="flex flex-col items-center">
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 mt-1.5 ring-4 ring-emerald-50"></div>
                @if(!$loop->last)<div class="w-px flex-1 bg-slate-100 my-1"></div>@endif
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-700">{{ $eventLabels[$log->event] ?? ucfirst(str_replace('_',' ',$log->event)) }}</p>
                <p class="text-xs text-slate-400">{{ $log->created_at?->format('d M Y, H:i') }} · {{ ucfirst($log->performed_by ?? 'system') }}</p>
              </div>
            </div>
          @empty
            <p class="text-sm text-slate-400 text-center py-3">No history yet.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- Bottom tab bar --}}
  <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[440px] bg-white/90 backdrop-blur border-t border-slate-100 px-2 pt-2 pb-safe z-30">
    <div class="flex">
      <template x-for="t in tabs" :key="t.k">
        <button @click="tab=t.k" class="flex-1 flex flex-col items-center gap-1 py-1.5 rounded-2xl transition"
                :class="tab===t.k ? 'text-emerald-600' : 'text-slate-400'">
          <span class="w-10 h-1 rounded-full -mt-2 mb-1 transition" :class="tab===t.k ? 'bg-emerald-500' : 'bg-transparent'"></span>
          <span x-html="t.icon" class="block"></span>
          <span class="text-[11px] font-semibold" x-text="t.l"></span>
        </button>
      </template>
    </div>
  </nav>
@endif

  {{-- ═══════════════ REPORT BOTTOM SHEET (shared) ═══════════════ --}}
  <div x-show="sheet" x-cloak class="fixed inset-0 z-50">
    <div @click="closeSheet()" x-show="sheet" x-transition.opacity class="absolute inset-0 bg-black/40"></div>
    <div x-show="sheet"
         x-transition:enter="sheet-tr" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="sheet-tr" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[440px] bg-white rounded-t-[2rem] shadow-2xl pb-safe">
      <div class="flex justify-center pt-3"><div class="w-10 h-1.5 rounded-full bg-slate-200"></div></div>

      <template x-if="!sent">
        <div class="px-6 pt-4 pb-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-6m0 0l2.77-.69a9 9 0 016.21.99 9 9 0 006.21.99L21 15V4l-2.81.7a9 9 0 01-6.21-.99 9 9 0 00-6.21-.99L3 4.5"/></svg>
            </div>
            <div><h3 class="font-extrabold text-slate-800">Report a problem</h3><p class="text-xs text-slate-400">Tell us about this product</p></div>
            <button @click="closeSheet()" class="ml-auto text-slate-300 hover:text-slate-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
          </div>
          <form @submit.prevent="submit()" class="space-y-3">
            <input x-model="r.reporter_name" type="text" placeholder="Your name *" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-rose-200 focus:border-rose-400 outline-none">
            <input x-model="r.reporter_phone" type="tel" placeholder="Phone *" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-rose-200 focus:border-rose-400 outline-none">
            <input x-model="r.reporter_email" type="email" placeholder="Email (optional)" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-rose-200 focus:border-rose-400 outline-none">
            <textarea x-model="r.message" rows="3" placeholder="Where did you buy it? Any details…" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-rose-200 focus:border-rose-400 outline-none"></textarea>
            <p x-show="error" x-text="error" class="text-xs text-rose-600"></p>
            <button type="submit" :disabled="saving" class="w-full py-3.5 rounded-2xl bg-rose-600 text-white font-bold text-sm shadow-lg shadow-rose-200 active:scale-[.98] transition disabled:opacity-60" x-text="saving ? 'Sending…' : 'Submit report'"></button>
          </form>
        </div>
      </template>

      <template x-if="sent">
        <div class="px-6 pt-6 pb-10 text-center">
          <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4 animate-pop">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
          </div>
          <h3 class="font-extrabold text-slate-800 text-lg">Report submitted</h3>
          <p class="text-sm text-slate-500 mt-1">Thank you — our team will review it.</p>
          <button @click="closeSheet()" class="mt-6 w-full py-3.5 rounded-2xl bg-slate-100 text-slate-600 font-bold text-sm active:scale-[.98] transition">Done</button>
        </div>
      </template>
    </div>
  </div>
</div>

<script>
function vApp(){
  return {
    tab:'info', sheet:false, sent:false, saving:false, error:'',
    r:{reporter_name:'',reporter_phone:'',reporter_email:'',message:''},
    tabs:[
      {k:'info',l:'Info',icon:'<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>'},
      {k:'images',l:'Images',icon:'<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.16-5.16a2.25 2.25 0 013.18 0l5.16 5.16m-1.5-1.5l1.41-1.41a2.25 2.25 0 013.18 0l2.16 2.16M21 19.5V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v13.5"/></svg>'},
      {k:'tracking',l:'Tracking',icon:'<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/></svg>'},
    ],
    openSheet(){ this.sheet=true; },
    closeSheet(){ this.sheet=false; },
    async submit(){
      if(!this.r.reporter_name.trim() || !this.r.reporter_phone.trim()){ this.error='Please enter your name and phone.'; return; }
      this.saving=true; this.error='';
      const fd=new FormData();
      fd.append('_token','{{ csrf_token() }}');
      fd.append('reason','{{ $block ?? '' }}');
      Object.entries(this.r).forEach(([k,v])=>fd.append(k,v));
      try{
        const res=await fetch('{{ route('verify.report', $code) }}',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        if(res.ok){ this.sent=true; } else { const d=await res.json().catch(()=>({})); this.error=d.message||'Could not submit. Check your details.'; }
      }catch(e){ this.error='Network error. Please try again.'; }
      this.saving=false;
    }
  };
}
</script>
</body>
</html>
