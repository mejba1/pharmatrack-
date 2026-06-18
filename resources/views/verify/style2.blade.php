<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="{{ $cfg['primary'] ?? '#059669' }}">
  <title>Verify — {{ $cfg['brand_name'] }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Inter',sans-serif;-webkit-tap-highlight-color:transparent}
    [x-cloak]{display:none!important}
    .no-sb::-webkit-scrollbar{display:none}.no-sb{-ms-overflow-style:none;scrollbar-width:none}
    .pb-safe{padding-bottom:calc(env(safe-area-inset-bottom) + 1rem)}
    @keyframes pop{0%{transform:scale(.5);opacity:0}60%{transform:scale(1.12)}100%{transform:scale(1);opacity:1}}
    .animate-pop{animation:pop .55s cubic-bezier(.34,1.56,.64,1) both}
    @keyframes rise{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
    .rise{animation:rise .5s ease both}
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
  .border-brand{border-color:var(--brand)!important}
  .grad-brand{background:linear-gradient(135deg,var(--brand),var(--brand-d))!important}
  .bg-btn{background:var(--btn)!important}
  .text-btn{color:var(--btn)!important}
  .shadow-btn{box-shadow:0 10px 25px var(--btn-shadow)!important}
</style>

<div x-data="vApp()" class="relative mx-auto w-full max-w-[480px] min-h-screen {{ $bgImage ? '' : 'bg-slate-100' }} overflow-hidden"
  @if($bgImage) style="background-image:url('{{ $bgImage }}');background-size:cover;background-position:center" @endif>

  @include('verify._intel-alerts')

  {{-- ═══════════════ TOP BAR ═══════════════ --}}
  <div class="px-6 pt-8 pb-2 flex items-center justify-center gap-2 text-slate-700">
    @if($cfg['logo_path'])
      <img src="{{ asset('storage/'.$cfg['logo_path']) }}" alt="" class="h-7 w-auto object-contain">
    @else
      <span class="text-brand"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1l9 4v6c0 5.25-3.6 9.74-9 11-5.4-1.26-9-5.75-9-11V5l9-4z"/></svg></span>
    @endif
    <span class="font-extrabold tracking-tight text-lg">{{ $cfg['brand_name'] }}</span>
  </div>

@if($block)
  {{-- ═══════════════ BLOCKED / RESTRICTED ═══════════════ --}}
  @php
    $style = [
      'fake'=>['Not Genuine','rose'], 'recalled'=>['Recalled','rose'], 'locked'=>['Restricted','slate'],
      'expired'=>['Expired','amber'], 'invalid'=>['Invalid','rose'], 'country'=>['Warning','rose'],
      'city'=>['Warning','rose'], 'device'=>['Limit','amber'], 'hit'=>['Limit','amber'],
      'vpn'=>['VPN Blocked','rose'], 'ip'=>['Blocked','rose'], 'rate'=>['Too Many Attempts','amber'],
    ][$block] ?? ['Notice','slate'];
    $m = $cfg['messages'][$block] ?? ['title'=>'Not Verified','text'=>'This product could not be verified.'];
    $tone = ['rose'=>['bg-rose-50','text-rose-600','bg-rose-600','shadow-rose-200'],'amber'=>['bg-amber-50','text-amber-600','bg-amber-500','shadow-amber-200'],'slate'=>['bg-slate-100','text-slate-600','bg-slate-700','shadow-slate-200']][$style[1]];
  @endphp
  <div class="px-5 pt-6 pb-10">
    <div class="bg-white rounded-3xl shadow-xl ring-1 ring-slate-100 overflow-hidden rise">
      <div class="h-1.5 {{ $tone[2] }}"></div>
      <div class="p-7 text-center">
        <div class="w-20 h-20 mx-auto rounded-full {{ $tone[0] }} {{ $tone[1] }} flex items-center justify-center mb-5 animate-pop">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.34 3.94L1.7 18a1.5 1.5 0 001.3 2.25h18a1.5 1.5 0 001.3-2.25L13.66 3.94a1.5 1.5 0 00-2.62 0z"/></svg>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] {{ $tone[1] }}">{{ $style[0] }}</p>
        <h3 class="text-2xl font-black text-slate-800 mt-1">{{ $m['title'] }}</h3>
        <p class="text-[15px] leading-relaxed text-slate-500 mt-3">{{ $m['text'] }}</p>
        <button @click="openSheet()" class="mt-6 w-full py-3.5 rounded-2xl {{ $tone[2] }} text-white font-bold text-sm shadow-lg {{ $tone[3] }} active:scale-[.98] transition flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-6m0 0l2.77-.69a9 9 0 016.21.99 9 9 0 006.21.99L21 15V4l-2.81.7a9 9 0 01-6.21-.99 9 9 0 00-6.21-.99L3 4.5"/></svg>
          Report this product
        </button>
      </div>
    </div>
    @if($cfg['footer'])<p class="text-center text-xs mt-6" style="color:{{ $footerCol }}">{{ $cfg['footer'] }}</p>@endif
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

  <div class="px-5 pt-4 pb-10">
    {{-- Certificate card --}}
    <div class="bg-white rounded-3xl shadow-xl ring-1 ring-slate-100 overflow-hidden rise">
      <div class="h-1.5 grad-brand"></div>
      <div class="p-7 text-center">
        <div class="w-20 h-20 mx-auto rounded-full bg-brand-soft text-brand flex items-center justify-center mb-4 animate-pop ring-8 ring-brand-soft/40">
          <svg class="w-11 h-11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand">{{ $cfg['genuine_title'] }}</p>
        <h1 class="text-2xl font-black text-slate-800 mt-1 leading-tight">{{ $product?->name ?? 'Genuine Product' }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $cfg['genuine_subtitle'] ?: ($product?->generic_name ?? '') }}</p>

        @if($cfg['verify_button'])
        <button @click="confirmVerify()" :disabled="verified"
                class="mt-5 w-full py-3.5 rounded-2xl bg-btn text-white font-bold text-sm shadow-btn active:scale-[.98] transition flex items-center justify-center gap-2 disabled:opacity-95">
          <svg x-show="!verified" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <svg x-show="verified" x-cloak class="w-5 h-5 animate-pop" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
          <span x-text="verified ? 'Verified Authentic' : @js($cfg['verify_button_text'])"></span>
        </button>
        @endif
      </div>

      {{-- Highlight strip --}}
      <div class="border-t border-slate-100 p-4 flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 overflow-hidden flex items-center justify-center shrink-0">
          @if($images->first())
            <img src="{{ $images->first()->url }}" class="w-full h-full object-cover" alt="">
          @else
            <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
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

    {{-- Email CTA --}}
    @if($cfg['info_form'])
    <button @click="openInfo()" class="mt-3 w-full py-3.5 rounded-2xl bg-white ring-1 ring-slate-200 text-slate-700 font-bold text-sm active:scale-[.98] transition flex items-center justify-center gap-2">
      <span class="text-brand"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg></span>
      Get full details by email
    </button>
    @endif

    {{-- Product details --}}
    <div class="mt-5">
      <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 px-1">Product details</p>
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
    </div>

    {{-- Official images --}}
    @if($images->count())
    <div class="mt-5">
      <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 px-1">Official images</p>
      <div class="grid grid-cols-2 gap-3">
        @foreach($images as $img)
          <div class="rounded-2xl overflow-hidden ring-1 ring-slate-100 bg-white aspect-square">
            <img src="{{ $img->url }}" class="w-full h-full object-cover" alt="{{ $img->original_name }}">
          </div>
        @endforeach
      </div>
    </div>
    @endif
    @include('verify._leaflet')

    {{-- Supply journey --}}
    @if($cfg['show_journey'])
    <div class="mt-5 bg-white rounded-3xl ring-1 ring-slate-100 p-5">
      <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-4">Supply journey</p>
      <div class="space-y-0">
        @foreach($journey as $i => $stage)
          <div class="flex gap-3">
            <div class="flex flex-col items-center">
              <div class="w-7 h-7 rounded-full flex items-center justify-center text-white shrink-0 {{ $stage[1] ? 'bg-brand' : 'bg-slate-200 text-slate-400' }}">
                @if($stage[1])<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>@else<span class="text-[11px] font-bold">{{ $i+1 }}</span>@endif
              </div>
              @if(!$loop->last)<div class="w-px flex-1 my-1 {{ ($journey[$i+1][1] ?? false) ? 'bg-brand' : 'bg-slate-200' }}"></div>@endif
            </div>
            <div class="pb-4"><p class="text-sm font-semibold {{ $stage[1] ? 'text-slate-700' : 'text-slate-400' }}">{{ $stage[0] }}</p></div>
          </div>
        @endforeach
      </div>
    </div>
    @endif

    @if($verification && $cfg['show_verification'])
    <div class="mt-3 bg-white rounded-3xl ring-1 ring-slate-100 p-5">
      <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Verification</p>
      <div class="space-y-2 text-sm">
        @include('verify._vinfo')
      </div>
    </div>
    @endif

    {{-- History --}}
    <div class="mt-3 bg-white rounded-3xl ring-1 ring-slate-100 p-5">
      <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-4">History</p>
      @forelse($logs as $log)
        <div class="flex gap-3 {{ $loop->last ? '' : 'pb-4' }}">
          <div class="flex flex-col items-center">
            <div class="w-2.5 h-2.5 rounded-full bg-brand mt-1.5 ring-4 ring-brand-soft"></div>
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

    @if($cfg['report_form'])
    <button @click="openSheet()" class="mt-3 w-full py-3 rounded-2xl text-rose-600 font-semibold text-sm flex items-center justify-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-6m0 0l2.77-.69a9 9 0 016.21.99 9 9 0 006.21.99L21 15V4l-2.81.7a9 9 0 01-6.21-.99 9 9 0 00-6.21-.99L3 4.5"/></svg>
      Report this product
    </button>
    @endif

    @if($cfg['footer'])<p class="text-center text-xs mt-6" style="color:{{ $footerCol }}">{{ $cfg['footer'] }}</p>@endif
  </div>
@endif

  {{-- ═══════════════ VERIFY CONFIRMATION TOAST ═══════════════ --}}
  <div x-show="toast" x-cloak x-transition.opacity
       class="fixed top-4 left-1/2 -translate-x-1/2 z-[60] bg-white rounded-2xl shadow-xl ring-1 ring-slate-100 px-4 py-2.5 flex items-center gap-2">
    <span class="w-6 h-6 rounded-full bg-brand-soft text-brand flex items-center justify-center">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
    </span>
    <span class="text-sm font-bold text-slate-700">Authenticity confirmed</span>
  </div>

  {{-- ═══════════════ REPORT BOTTOM SHEET ═══════════════ --}}
  <div x-show="sheet" x-cloak class="fixed inset-0 z-50">
    <div @click="closeSheet()" x-show="sheet" x-transition.opacity class="absolute inset-0 bg-black/40"></div>
    <div x-show="sheet"
         x-transition:enter="sheet-tr" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="sheet-tr" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-white rounded-t-[2rem] shadow-2xl pb-safe">
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
            <input x-model="r.reporter_name" type="text" placeholder="Your name *" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-rose-400">
            <input x-model="r.reporter_phone" type="tel" placeholder="Phone *" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-rose-400">
            <input x-model="r.reporter_email" type="email" placeholder="Email (optional)" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-rose-400">
            <textarea x-model="r.message" rows="3" placeholder="Where did you buy it? Any details…" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-rose-400"></textarea>
            <p x-show="error" x-text="error" class="text-xs text-rose-600"></p>
            <button type="submit" :disabled="saving" class="w-full py-3.5 rounded-2xl bg-rose-600 text-white font-bold text-sm active:scale-[.98] transition disabled:opacity-60" x-text="saving ? 'Sending…' : 'Submit report'"></button>
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
         class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-white rounded-t-[2rem] shadow-2xl pb-safe max-h-[90vh] overflow-y-auto no-sb">
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
            @php $inp='w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-400'; @endphp
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
    sheet:false, sent:false, saving:false, error:'',
    verified:false, toast:false,
    confirmVerify(){ if(this.verified) return; this.verified=true; this.toast=true; setTimeout(()=>{ this.toast=false; }, 2200); },
    r:{reporter_name:'',reporter_phone:'',reporter_email:'',message:''},
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
