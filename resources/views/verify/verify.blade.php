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
@include('verify._loader')

@php
  $product = $unit?->batch?->product;
  $batch   = $unit?->batch;
  $images  = $product?->images ?? collect();
  $brand   = $cfg['primary'] ?? '#059669';
  $brandD  = \App\Models\Setting::darken($brand);
  $brandSoft = \App\Models\Setting::soft($brand, .12);
  $brandShadow = \App\Models\Setting::soft($brand, .35);
  $btn       = $cfg['button_color'] ?: $brand;
  $btnShadow = \App\Models\Setting::soft($btn, .35);
  $footerCol = $cfg['footer_color'] ?: '#94a3b8';
  $bgImage   = $cfg['bg_image'] ? asset('storage/'.$cfg['bg_image']) : null;
@endphp

<style>
  :root{--brand:{{ $brand }};--brand-d:{{ $brandD }};--brand-soft:{{ $brandSoft }};--brand-shadow:{{ $brandShadow }};--btn:{{ $btn }};--btn-shadow:{{ $btnShadow }}}
  .bg-brand{background:var(--brand)!important}
  .text-brand{color:var(--brand)!important}
  .bg-brand-soft{background:var(--brand-soft)!important}
  .grad-brand{background:linear-gradient(160deg,var(--brand),var(--brand-d))!important}
  .shadow-brand{box-shadow:0 10px 25px var(--brand-shadow)!important}
  .ring-brand{box-shadow:0 0 0 4px var(--brand-soft)}
  .bg-btn{background:var(--btn)!important}
  .text-btn{color:var(--btn)!important}
  .shadow-btn{box-shadow:0 10px 25px var(--btn-shadow)!important}
</style>

<div x-data="vApp()" class="relative mx-auto w-full max-w-[440px] min-h-screen {{ $bgImage ? '' : 'bg-slate-50' }} shadow-2xl overflow-hidden"
  @if($bgImage) style="background-image:url('{{ $bgImage }}');background-size:cover;background-position:center" @endif>

  @include('verify._intel-alerts')

@if($block)
  {{-- ═══════════════ BLOCKED / RESTRICTED ═══════════════ --}}
  @php
    $style = [
      'fake'=>['Not Genuine','rose','shield'], 'recalled'=>['Recalled','rose','alert'],
      'locked'=>['Restricted','slate','lock'], 'expired'=>['Expired','amber','clock'],
      'invalid'=>['Invalid','rose','shield'], 'country'=>['Warning','rose','globe'],
      'city'=>['Warning','rose','globe'], 'device'=>['Limit','amber','lock'], 'hit'=>['Limit','amber','ban'],
      'vpn'=>['VPN Blocked','rose','shield'], 'ip'=>['Blocked','rose','ban'], 'rate'=>['Too Many Attempts','amber','ban'],
    ][$block] ?? ['Notice','slate','shield'];
    $m = $cfg['messages'][$block] ?? ['title'=>'Not Verified','text'=>'This product could not be verified.'];
    $meta = [$style[0], $m['title'], $m['text'], $style[1], $style[2]];
    $grad = ['rose'=>'from-rose-500 to-red-600','amber'=>'from-amber-500 to-orange-500','slate'=>'from-slate-600 to-slate-800'][$meta[3]];
  @endphp

  <div class="min-h-screen flex flex-col">
    <div class="bg-gradient-to-b {{ $grad }} text-white px-6 pt-12 pb-16 text-center rounded-b-[2.5rem]">
      <div class="flex items-center justify-center gap-2 mb-8 opacity-90">
        @if($cfg['logo_path'])
          <img src="{{ asset('storage/'.$cfg['logo_path']) }}" alt="" class="h-6 w-auto object-contain">
        @else
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1l9 4v6c0 5.25-3.6 9.74-9 11-5.4-1.26-9-5.75-9-11V5l9-4z"/></svg>
        @endif
        <span class="font-extrabold tracking-tight">{{ $cfg['brand_name'] }}</span>
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
      <p class="text-center text-xs mt-6 mb-8" style="color:{{ $footerCol }}">{{ $cfg['footer'] }}</p>
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

    $cardVal = function ($key) use ($product, $batch, $unit, $monthsLeft) {
      return match ($key) {
        'product'=>$product?->name,'generic'=>$product?->generic_name,
        'strength'=>trim(($product?->strength ?? '').' '.($product?->dosage_form ? ucfirst(str_replace('_',' ',$product->dosage_form)) : '')) ?: null,
        'batch'=>$batch?->brn,'batch_number'=>$batch?->batch_number,
        'manufactured'=>optional($batch?->manufacture_date)->format('d M Y'),
        'expires'=>optional($batch?->expiry_date)->format('d M Y'),
        'expires_in'=>$monthsLeft !== null ? $monthsLeft.' mo' : null,
        'serial'=>'#'.$unit->serial_number,'manufacturer'=>$product?->manufacturer_name,'country'=>$product?->country_of_origin,
        default=>null,
      };
    };
    $cardLeftVal  = $cardVal($cfg['card_left_field'] ?? 'batch');
    $cardRightVal = $cardVal($cfg['card_right_field'] ?? 'expires_in');

    $rowMap = [
      'product'      => ['Product', $product?->name, 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
      'generic'      => ['Generic', $product?->generic_name, 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.3 24.3 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5'],
      'strength'     => ['Strength / Form', trim(($product?->strength ?? '').' '.($product?->dosage_form ? ucfirst(str_replace('_',' ',$product->dosage_form)) : '')), 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z'],
      'batch'        => ['Batch No.', $batch?->batch_number, 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z'],
      'manufactured' => ['Manufactured', optional($batch?->manufacture_date)->format('d M Y'), 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
      'expires'      => ['Expires', optional($batch?->expiry_date)->format('d M Y'), 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
      'serial'       => ['Serial No.', '#'.$unit->serial_number, 'M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5'],
      'manufacturer' => ['Manufacturer', $product?->manufacturer_name, 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
      'country'      => ['Country', $product?->country_of_origin, 'M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m-9 9h18'],
    ];
  @endphp

  <div class="pb-28">
    {{-- Hero --}}
    <div class="grad-brand text-white px-6 pt-12 pb-20 rounded-b-[2.5rem] relative">
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-2 opacity-95">
          @if($cfg['logo_path'])
            <img src="{{ asset('storage/'.$cfg['logo_path']) }}" alt="" class="h-6 w-auto object-contain">
          @else
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1l9 4v6c0 5.25-3.6 9.74-9 11-5.4-1.26-9-5.75-9-11V5l9-4z"/></svg>
          @endif
          <span class="font-extrabold tracking-tight">{{ $cfg['brand_name'] }}</span>
        </div>
        @if($cfg['report_form'])
        <button @click="openSheet()" class="text-white/80 hover:text-white text-xs flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-6m0 0l2.77-.69a9 9 0 016.21.99 9 9 0 006.21.99L21 15V4l-2.81.7a9 9 0 01-6.21-.99 9 9 0 00-6.21-.99L3 4.5"/></svg>
          Report
        </button>
        @endif
      </div>
      <div class="text-center">
        <div class="ring-pulse text-white/80 mx-auto w-20 h-20 mb-4">
          <div class="animate-pop relative w-20 h-20 rounded-full bg-white/15 backdrop-blur flex items-center justify-center ring-4 ring-white/25">
            <svg class="w-11 h-11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
          </div>
        </div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/75 rise">{{ $cfg['genuine_title'] }}</p>
        <h1 class="text-2xl font-black mt-1 rise">{{ $product?->name ?? 'Genuine Product' }}</h1>
        <p class="text-sm text-white/80 mt-1 rise">{{ $cfg['genuine_subtitle'] ?: ($product?->generic_name ?? '') }}</p>
        @if($cfg['verify_button'] && $cfg['verify_button_pos'] === 'hero')
        <button @click="confirmVerify()" :disabled="verified"
                class="mt-4 inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-white text-btn font-extrabold text-sm shadow-lg active:scale-[.97] transition rise disabled:opacity-95">
          <svg x-show="!verified" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <svg x-show="verified" x-cloak class="w-5 h-5 animate-pop" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
          <span x-text="verified ? 'Verified Authentic' : @js($cfg['verify_button_text'])"></span>
        </button>
        @endif
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
        @if($cardLeftVal !== null && $cardLeftVal !== '')
          <div class="min-w-0 flex-1">
            <p class="text-[11px] uppercase tracking-wider text-slate-400">{{ $cfg['card_left_label'] }}</p>
            <p class="text-sm font-semibold text-slate-700 truncate">{{ $cardLeftVal }}</p>
          </div>
        @endif
        @if($cardRightVal !== null && $cardRightVal !== '')
          <div class="text-right shrink-0 {{ ($cardLeftVal !== null && $cardLeftVal !== '') ? '' : 'flex-1' }}">
            <p class="text-[11px] uppercase tracking-wider text-slate-400">{{ $cfg['card_right_label'] }}</p>
            <p class="text-sm font-bold text-brand truncate">{{ $cardRightVal }}</p>
          </div>
        @endif
      </div>
    </div>

    {{-- Verify button (below product card) --}}
    @if($cfg['verify_button'] && $cfg['verify_button_pos'] === 'card')
    <div class="px-5 mt-3">
      <button @click="confirmVerify()" :disabled="verified"
              class="w-full py-3.5 rounded-2xl bg-btn text-white font-bold text-sm shadow-btn active:scale-[.98] transition flex items-center justify-center gap-2 disabled:opacity-95">
        <svg x-show="!verified" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <svg x-show="verified" x-cloak class="w-5 h-5 animate-pop" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        <span x-text="verified ? 'Verified Authentic' : @js($cfg['verify_button_text'])"></span>
      </button>
    </div>
    @endif

    {{-- Email CTA --}}
    @if($cfg['info_form'])
    <div class="px-5 mt-3">
      <button @click="openInfo()" class="w-full py-3.5 rounded-2xl bg-btn text-white font-bold text-sm shadow-btn active:scale-[.98] transition flex items-center justify-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
        Get full details by email
      </button>
    </div>
    @endif

    {{-- Tab content --}}
    <div class="px-5 mt-4">
      {{-- INFO --}}
      <div x-show="tab==='info'" class="rise">
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 divide-y divide-slate-50">
          @foreach($cfg['fields'] as $fid)
            @php $r = $rowMap[$fid] ?? null; @endphp
            @if($r && $r[1])
              <div class="flex items-center gap-3 px-4 py-3">
                <div class="w-9 h-9 rounded-xl bg-brand-soft text-brand flex items-center justify-center shrink-0">
                  <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $r[2] }}"/></svg>
                </div>
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
              <svg class="w-5 h-5 mx-auto text-brand mb-1" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chip[1] }}"/></svg>
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
        @include('verify._leaflet')
      </div>

      {{-- TRACKING --}}
      <div x-show="tab==='tracking'" x-cloak class="rise">
        @if($cfg['show_journey'])
        {{-- Journey --}}
        <div class="bg-white rounded-3xl ring-1 ring-slate-100 p-5 mb-3">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-4">Supply Journey</p>
          <div class="flex items-center justify-between">
            @foreach($journey as $i => $stage)
              <div class="flex flex-col items-center text-center flex-1 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white {{ $stage[1] ? 'bg-brand' : 'bg-slate-200 text-slate-400' }}">
                  @if($stage[1])<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>@else<span class="text-[11px]">{{ $i+1 }}</span>@endif
                </div>
                <span class="mt-1.5 text-[9px] font-semibold leading-tight {{ $stage[1] ? 'text-slate-600' : 'text-slate-400' }}">{{ $stage[0] }}</span>
              </div>
              @if(!$loop->last)<div class="h-1 flex-1 rounded mb-4 {{ ($journey[$i+1][1] ?? false) ? 'bg-brand' : 'bg-slate-200' }}"></div>@endif
            @endforeach
          </div>
        </div>
        @endif

        @if($verification && $cfg['show_verification'])
        <div class="bg-white rounded-3xl ring-1 ring-slate-100 p-5 mb-3">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Verification</p>
          <div class="space-y-2 text-sm">
            @include('verify._vinfo')
          </div>
        </div>
        @endif

        <div class="bg-white rounded-3xl ring-1 ring-slate-100 p-5">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-4">History</p>
          @forelse($logs as $log)
            <div class="flex gap-3 {{ $loop->last ? '' : 'pb-4' }}">
              <div class="flex flex-col items-center">
                <div class="w-2.5 h-2.5 rounded-full bg-brand mt-1.5 ring-4 ring-emerald-50"></div>
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

    @if($cfg['footer'])
    <p class="text-center text-xs px-6 mt-6" style="color:{{ $footerCol }}">{{ $cfg['footer'] }}</p>
    @endif
  </div>

  {{-- Bottom tab bar --}}
  <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[440px] bg-white/95 backdrop-blur-lg border-t border-slate-100 px-3 pt-2 pb-safe z-30">
    <div class="flex items-stretch gap-1">
      <template x-for="t in tabs" :key="t.k">
        <button @click="tab=t.k" class="flex-1 flex flex-col items-center gap-1 py-1 transition group focus:outline-none">
          <span class="flex items-center justify-center w-14 h-9 rounded-2xl transition duration-200"
                :class="tab===t.k ? 'bg-brand-soft text-brand' : 'text-slate-400 group-active:bg-slate-100'">
            <span x-html="t.icon" class="block transition-transform duration-200" :class="tab===t.k ? 'scale-110' : ''"></span>
          </span>
          <span class="text-[11px] transition" :class="tab===t.k ? 'font-bold text-brand' : 'font-semibold text-slate-400'" x-text="t.l"></span>
        </button>
      </template>
    </div>
  </nav>
@endif

  {{-- ═══════════════ VERIFY CONFIRMATION TOAST ═══════════════ --}}
  <div x-show="toast" x-cloak x-transition.opacity
       class="fixed top-4 left-1/2 -translate-x-1/2 z-[60] bg-white rounded-2xl shadow-xl ring-1 ring-emerald-100 px-4 py-2.5 flex items-center gap-2">
    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
    </span>
    <span class="text-sm font-bold text-slate-700">Authenticity confirmed</span>
  </div>

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

  {{-- ═══════════════ GET-PRODUCT-INFO BOTTOM SHEET ═══════════════ --}}
  <div x-show="infoSheet" x-cloak class="fixed inset-0 z-50">
    <div @click="closeInfo()" x-show="infoSheet" x-transition.opacity class="absolute inset-0 bg-black/40"></div>
    <div x-show="infoSheet"
         x-transition:enter="sheet-tr" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="sheet-tr" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[440px] bg-white rounded-t-[2rem] shadow-2xl pb-safe max-h-[90vh] overflow-y-auto no-sb">
      <div class="flex justify-center pt-3 sticky top-0 bg-white"><div class="w-10 h-1.5 rounded-full bg-slate-200"></div></div>

      <template x-if="!infoSent">
        <div class="px-6 pt-3 pb-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-brand-soft text-brand flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            </div>
            <div><h3 class="font-extrabold text-slate-800">Get product details</h3><p class="text-xs text-slate-400">We'll email you the full info</p></div>
            <button @click="closeInfo()" class="ml-auto text-slate-300 hover:text-slate-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
          </div>
          <form @submit.prevent="submitInfo()" class="space-y-3">
            @php $inp='w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 outline-none'; @endphp
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Your details</p>
            <input x-model="info.name" type="text" placeholder="Full name *" class="{{ $inp }}">
            <input x-model="info.email" type="email" placeholder="Email *" class="{{ $inp }}">
            <div class="grid grid-cols-2 gap-3">
              <input x-model="info.phone" type="tel" placeholder="Phone" class="{{ $inp }}">
              <input x-model="info.whatsapp" type="tel" placeholder="WhatsApp" class="{{ $inp }}">
            </div>
            <div class="grid grid-cols-2 gap-3">
              <input x-model="info.country" type="text" placeholder="Country" class="{{ $inp }}">
              <input x-model="info.city" type="text" placeholder="City" class="{{ $inp }}">
            </div>
            <input x-model="info.address" type="text" placeholder="Address" class="{{ $inp }}">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 pt-1">Partner / seller (optional)</p>
            <div class="grid grid-cols-2 gap-3">
              <input x-model="info.partner_name" type="text" placeholder="Partner name" class="{{ $inp }}">
              <input x-model="info.partner_phone" type="tel" placeholder="Partner phone" class="{{ $inp }}">
            </div>
            <p x-show="infoError" x-text="infoError" class="text-xs text-rose-600"></p>
            <button type="submit" :disabled="infoSaving" class="w-full py-3.5 rounded-2xl bg-btn text-white font-bold text-sm shadow-btn active:scale-[.98] transition disabled:opacity-60" x-text="infoSaving ? 'Sending…' : 'Email me the details'"></button>
            <p class="text-[11px] text-slate-400 text-center">Details are emailed only for genuine products.</p>
          </form>
        </div>
      </template>

      <template x-if="infoSent">
        <div class="px-6 pt-6 pb-10 text-center">
          <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4 animate-pop">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954 8.955c.44.439 1.152.439 1.591 0L21.75 12M4.5 9.75l6.704-6.704c.44-.44 1.152-.44 1.591 0L19.5 9.75"/></svg>
          </div>
          <h3 class="font-extrabold text-slate-800 text-lg">Check your inbox</h3>
          <p class="text-sm text-slate-500 mt-1">We've emailed the full product details to you.</p>
          <button @click="closeInfo()" class="mt-6 w-full py-3.5 rounded-2xl bg-slate-100 text-slate-600 font-bold text-sm active:scale-[.98] transition">Done</button>
        </div>
      </template>
    </div>
  </div>
</div>

<script>
function vApp(){
  return {
    tab:'info', sheet:false, sent:false, saving:false, error:'',
    verified:false, toast:false,
    confirmVerify(){ if(this.verified) return; this.verified=true; this.toast=true; setTimeout(()=>{ this.toast=false; }, 2200); },
    r:{reporter_name:'',reporter_phone:'',reporter_email:'',message:''},
    tabs:[
      {k:'info',l:'Info',icon:'<svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="6"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-4.5M12 8.25h.008v.008H12z"/></svg>'},
      {k:'images',l:'Images',icon:'<svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><rect x="3" y="4.5" width="18" height="15" rx="3"/><circle cx="8.5" cy="9.5" r="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 16.5l-4.5-4.5a2.25 2.25 0 00-3.18 0L4.5 20.25"/></svg>'},
      {k:'tracking',l:'Tracking',icon:'<svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 9.75c0 6-7.5 11.25-7.5 11.25S4.5 15.75 4.5 9.75a7.5 7.5 0 1115 0z"/><circle cx="12" cy="9.75" r="2.4"/></svg>'},
    ],
    openSheet(){ this.sheet=true; },
    closeSheet(){ this.sheet=false; },
    infoSheet:false, infoSent:false, infoSaving:false, infoError:'',
    info:{name:'',email:'',phone:'',country:'',city:'',address:'',partner_name:'',partner_phone:'',whatsapp:''},
    openInfo(){ this.infoSheet=true; },
    closeInfo(){ this.infoSheet=false; },
    async submitInfo(){
      if(!this.info.name.trim() || !this.info.email.trim()){ this.infoError='Please enter your name and email.'; return; }
      this.infoSaving=true; this.infoError='';
      const fd=new FormData();
      fd.append('_token','{{ csrf_token() }}');
      Object.entries(this.info).forEach(([k,v])=>fd.append(k,v));
      try{
        const res=await fetch('{{ route('verify.request-info', $code) }}',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json().catch(()=>({}));
        if(res.ok && d.success){ this.infoSent=true; } else { this.infoError=d.message||'Could not submit. Check your details.'; }
      }catch(e){ this.infoError='Network error. Please try again.'; }
      this.infoSaving=false;
    },
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
