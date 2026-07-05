<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="{{ $cfg['primary'] ?? '#0ea5e9' }}">
  <title>Verify — {{ $cfg['brand_name'] }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

@php
  $product = $unit?->batch?->product;
  $batch   = $unit?->batch;
  $images  = $product?->images ?? collect();
  $brand   = $cfg['primary'] ?? '#0ea5e9';
  $brandSoft = \App\Models\Setting::soft($brand, .12);
  $btn       = $cfg['button_color'] ?: $brand;
  $btnShadow = \App\Models\Setting::soft($btn, .35);
  $footerCol = $cfg['footer_color'] ?: '#94a3b8';
  $bgImage   = $cfg['bg_image'] ? asset('storage/'.$cfg['bg_image']) : null;
@endphp

  <style>
    :root{ --brand:{{ $brand }}; --brand-soft:{{ $brandSoft }}; --btn:{{ $btn }}; --btn-shadow:{{ $btnShadow }}; --bg-pearl:#f4f7fa; }
    body{ font-family:'Plus Jakarta Sans',sans-serif; color:#1e293b; }
    [x-cloak]{display:none!important}
    .no-sb::-webkit-scrollbar{display:none}.no-sb{-ms-overflow-style:none;scrollbar-width:none}
    .text-brand{color:var(--brand)!important}.bg-brand{background:var(--brand)!important}.bg-brand-soft{background:var(--brand-soft)!important}.border-brand{border-color:var(--brand)!important}
    .bg-btn{background:var(--btn)!important}.shadow-btn{box-shadow:0 14px 30px var(--btn-shadow)!important}
    .glass-panel{ background:#fff; border-radius:32px; box-shadow:0 10px 40px -10px rgba(0,0,0,.06),0 2px 4px rgba(0,0,0,.02); border:1px solid #fff; }
    .smart-input{ transition:all .3s ease; box-shadow:inset 0 2px 4px rgba(0,0,0,.03); }
    .smart-input:focus{ background:#fff; border-color:var(--brand); box-shadow:0 0 0 3px var(--brand-soft); }
    .tab-ind::after{ content:''; position:absolute; bottom:0; left:50%; transform:translateX(-50%); width:20px; height:4px; background:var(--brand); border-radius:10px; }
    @keyframes slideIn{ from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
    .fade-view{ animation:slideIn .5s cubic-bezier(.16,1,.3,1); }
    @keyframes pop{0%{transform:scale(.5);opacity:0}60%{transform:scale(1.12)}100%{transform:scale(1);opacity:1}}
    .animate-pop{animation:pop .55s cubic-bezier(.34,1.56,.64,1) both}
    .sheet-tr{transition:transform .34s cubic-bezier(.32,.72,0,1)}
  </style>
</head>
<body class="min-h-screen flex justify-center p-4 md:p-8" style="background:{{ $bgImage ? 'transparent' : 'var(--bg-pearl)' }}">
@include('verify._loader')

@if($bgImage)
<div class="fixed inset-0 -z-10" style="background-image:url('{{ $bgImage }}');background-size:cover;background-position:center"></div>
@endif

<div x-data="vApp()" class="w-full max-w-md flex flex-col gap-6">
  @include('verify._intel-alerts')

  {{-- Header --}}
  <header class="flex items-center justify-between px-2">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center shadow-lg shadow-slate-200 overflow-hidden">
        @if($cfg['logo_path'])
          <img src="{{ asset('storage/'.$cfg['logo_path']) }}" alt="" class="w-full h-full object-contain p-1">
        @else
          <i class="fa-solid fa-shield-halved text-white text-lg"></i>
        @endif
      </div>
      <h1 class="font-extrabold text-lg tracking-tight uppercase">{{ $cfg['brand_name'] }}</h1>
    </div>
    @if($cfg['report_form'])
    <button @click="openSheet()" class="w-10 h-10 rounded-full bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:text-rose-500 transition" aria-label="Report">
      <i class="fa-solid fa-flag"></i>
    </button>
    @endif
  </header>

  {{-- Verify-another-code panel (hidden on restriction pages) --}}
  @if($cfg['verify_code_panel'] && !$block)
  <section class="glass-panel p-6">
    <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4 px-1">Verify Authenticity</h2>
    <form class="flex flex-col gap-3" @submit.prevent="validateCode()">
      <input x-model="codeInput" type="text" maxlength="40" placeholder="Enter security code"
             class="smart-input w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 px-6 text-center text-xl font-mono font-bold tracking-widest text-slate-800 outline-none uppercase">
      <button type="submit" class="w-full bg-btn text-white py-4 rounded-2xl font-bold text-sm uppercase tracking-widest hover:opacity-90 transition-all shadow-btn active:scale-[0.98]">
        Validate Security Code
      </button>
    </form>
  </section>
  @endif

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
    $tone = ['rose'=>['bg-rose-500','text-rose-500','shadow-rose-100'],'amber'=>['bg-amber-500','text-amber-500','shadow-amber-100'],'slate'=>['bg-slate-700','text-slate-600','shadow-slate-100']][$style[1]];
  @endphp
  <main class="glass-panel p-8 text-center fade-view">
    <div class="w-16 h-16 mx-auto {{ $tone[0] }} rounded-2xl flex items-center justify-center text-white shadow-lg {{ $tone[2] }} mb-5 animate-pop">
      <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
    </div>
    <p class="{{ $tone[1] }} text-[10px] font-bold uppercase tracking-widest">{{ $style[0] }}</p>
    <h3 class="text-2xl font-extrabold text-slate-800 mt-1">{{ $m['title'] }}</h3>
    <p class="text-sm text-slate-500 mt-3 leading-relaxed">{{ $m['text'] }}</p>
    <button @click="openSheet()" class="mt-6 w-full {{ $tone[0] }} text-white py-4 rounded-2xl font-bold text-[11px] uppercase tracking-widest active:scale-[0.98] transition">
      <i class="fa-solid fa-flag mr-1"></i> Report this product
    </button>
  </main>

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
    $rowMap = [
      'product'      => ['Product', $product?->name],
      'generic'      => ['Generic', $product?->generic_name],
      'strength'     => ['Strength / Form', trim(($product?->strength ?? '').' '.($product?->dosage_form ? ucfirst(str_replace('_',' ',$product->dosage_form)) : ''))],
      'batch'        => ['Batch No.', $batch?->batch_number],
      'manufactured' => ['Production', strtoupper(optional($batch?->manufacture_date)->format('M Y') ?? '')],
      'expires'      => ['Expiry', strtoupper(optional($batch?->expiry_date)->format('M Y') ?? '')],
      'serial'       => ['Serial No.', '#'.$unit->serial_number],
      'manufacturer' => ['Manufacturer', $product?->manufacturer_name],
      'country'      => ['Country', $product?->country_of_origin],
    ];
  @endphp

  <main class="glass-panel flex flex-col overflow-hidden">
    {{-- Top tabs --}}
    <nav class="flex border-b border-slate-50 bg-slate-50/30">
      @php $tabs3 = [['info','Info','fa-circle-info'],['images','Images','fa-camera'],['history','History','fa-clock-rotate-left']]; @endphp
      @foreach($tabs3 as $t)
        <button @click="tab='{{ $t[0] }}'" class="relative flex-1 py-5 text-[10px] font-bold uppercase tracking-widest transition"
                :class="tab==='{{ $t[0] }}' ? 'text-brand tab-ind' : 'text-slate-400'">
          <i class="fa-solid {{ $t[2] }} mb-1 block text-lg"></i> {{ $t[1] }}
        </button>
      @endforeach
    </nav>

    <div class="p-8">
      {{-- INFO --}}
      <div x-show="tab==='info'" class="fade-view space-y-8">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-brand rounded-2xl flex items-center justify-center text-white shadow-lg shadow-slate-100 shrink-0">
            <i class="fa-solid fa-check text-2xl"></i>
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-2xl font-extrabold text-slate-800 leading-tight truncate">{{ $product?->name ?? 'Genuine Product' }}</h3>
            <p class="text-brand text-[10px] font-bold uppercase tracking-widest mt-1">{{ $cfg['genuine_title'] }}</p>
          </div>
          @if($cfg['show_product_photo'] && $images->first())
            <div class="w-16 h-16 rounded-2xl overflow-hidden shadow-lg shadow-slate-100 shrink-0 bg-slate-50 ring-1 ring-slate-100">
              <img src="{{ $images->first()->url }}" class="w-full h-full object-cover" alt="{{ $product?->name }}">
            </div>
          @endif
        </div>

        @if($cfg['verify_button'])
        <button @click="confirmVerify()" :disabled="verified"
                class="w-full bg-btn text-white py-4 rounded-2xl font-bold text-[11px] uppercase tracking-widest shadow-btn active:scale-[0.98] transition disabled:opacity-95 -mt-3">
          <i class="fa-solid" :class="verified ? 'fa-circle-check' : 'fa-shield-halved'"></i>
          <span x-text="verified ? 'Verified Authentic' : @js($cfg['verify_button_text'])"></span>
        </button>
        @endif

        <div class="grid grid-cols-1 gap-px bg-slate-100 rounded-3xl overflow-hidden border border-slate-100">
          @foreach($cfg['fields'] as $fid)
            @php $r = $rowMap[$fid] ?? null; @endphp
            @if($r && $r[1])
              @php $isExpiry = $fid === 'expires'; $isSerial = $fid === 'serial'; @endphp
              <div class="{{ $isExpiry ? 'bg-red-50' : 'bg-white' }} p-4 flex justify-between items-center gap-3">
                <span class="text-[10px] font-bold uppercase {{ $isExpiry ? 'text-red-400' : 'text-slate-400' }}">{{ $r[0] }}</span>
                <span class="font-bold text-right truncate {{ $isExpiry ? 'text-red-600' : ($isSerial ? 'text-brand font-mono' : 'text-slate-700') }}">{{ $r[1] }}</span>
              </div>
            @endif
          @endforeach
        </div>

        @if($cfg['info_form'])
        <button @click="openInfo()" class="w-full py-4 bg-slate-50 rounded-2xl text-[10px] font-bold text-slate-600 uppercase tracking-widest border border-slate-100 active:scale-[0.98] transition -mt-3">
          <i class="fa-solid fa-envelope mr-1 text-brand"></i> Get full details by email
        </button>
        @endif
      </div>

      {{-- IMAGES --}}
      <div x-show="tab==='images'" x-cloak class="fade-view space-y-4 text-center">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Visual Verification</h3>
        @if($images->count())
          <div class="grid grid-cols-2 gap-4">
            @foreach($images as $img)
              <div class="aspect-[4/5] bg-white rounded-3xl border border-slate-100 overflow-hidden flex items-center justify-center p-2">
                <img src="{{ $img->url }}" class="max-w-full max-h-full object-contain" alt="{{ $img->original_name }}" loading="lazy">
              </div>
            @endforeach
          </div>
        @else
          <div class="grid grid-cols-2 gap-4">
            <div class="aspect-[4/5] bg-slate-50 rounded-3xl border border-slate-100 flex items-center justify-center"><i class="fa-solid fa-capsules text-slate-200 text-4xl"></i></div>
            <div class="aspect-[4/5] bg-slate-50 rounded-3xl border border-slate-100 flex items-center justify-center"><i class="fa-solid fa-box text-slate-200 text-4xl"></i></div>
          </div>
          <p class="text-xs text-slate-400 pt-2">No official images available.</p>
        @endif
        @include('verify._leaflet')
      </div>

      {{-- HISTORY --}}
      <div x-show="tab==='history'" x-cloak class="fade-view space-y-6">
        @if($cfg['show_journey'])
        <div>
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Supply Journey</p>
          <div class="space-y-0">
            @foreach($journey as $i => $stage)
              <div class="flex gap-3">
                <div class="flex flex-col items-center">
                  <div class="w-7 h-7 rounded-full flex items-center justify-center text-white shrink-0 {{ $stage[1] ? 'bg-brand' : 'bg-slate-200 text-slate-400' }}">
                    @if($stage[1])<i class="fa-solid fa-check text-[11px]"></i>@else<span class="text-[11px] font-bold">{{ $i+1 }}</span>@endif
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
        <div class="bg-slate-50 rounded-3xl border border-slate-100 p-5">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3">Verification</p>
          <div class="space-y-2 text-sm">
            @include('verify._vinfo')
          </div>
        </div>
        @endif

        <div>
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Event History</p>
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
      </div>
    </div>
  </main>
@endif

  @if($cfg['report_form'])
  <button @click="openSheet()" class="w-full text-[10px] font-bold text-slate-300 hover:text-rose-400 transition-colors uppercase tracking-[0.4em]">
    <i class="fa-solid fa-flag mr-1"></i> Report Suspicious Activity
  </button>
  @endif

  @if($cfg['footer'])<p class="text-center text-[11px] -mt-2" style="color:{{ $footerCol }}">{{ $cfg['footer'] }}</p>@endif

  {{-- ═══════════════ VERIFY CONFIRMATION TOAST ═══════════════ --}}
  <div x-show="toast" x-cloak x-transition.opacity
       class="fixed top-4 left-1/2 -translate-x-1/2 z-[60] bg-white rounded-2xl shadow-xl ring-1 ring-slate-100 px-4 py-2.5 flex items-center gap-2">
    <span class="w-6 h-6 rounded-full bg-brand-soft text-brand flex items-center justify-center"><i class="fa-solid fa-check text-xs"></i></span>
    <span class="text-sm font-bold text-slate-700">Authenticity confirmed</span>
  </div>

  {{-- ═══════════════ REPORT BOTTOM SHEET ═══════════════ --}}
  <div x-show="sheet" x-cloak class="fixed inset-0 z-50">
    <div @click="closeSheet()" x-show="sheet" x-transition.opacity class="absolute inset-0 bg-black/40"></div>
    <div x-show="sheet"
         x-transition:enter="sheet-tr" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="sheet-tr" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white rounded-t-[2rem] shadow-2xl pb-6">
      <div class="flex justify-center pt-3"><div class="w-10 h-1.5 rounded-full bg-slate-200"></div></div>
      <template x-if="!sent">
        <div class="px-6 pt-4 pb-2">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-flag"></i></div>
            <div><h3 class="font-extrabold text-slate-800">Report a problem</h3><p class="text-xs text-slate-400">Tell us about this product</p></div>
            <button @click="closeSheet()" class="ml-auto text-slate-300 hover:text-slate-500"><i class="fa-solid fa-xmark text-xl"></i></button>
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
        <div class="px-6 pt-6 pb-6 text-center">
          <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4 animate-pop"><i class="fa-solid fa-check text-2xl"></i></div>
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
         class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white rounded-t-[2rem] shadow-2xl pb-6 max-h-[90vh] overflow-y-auto no-sb">
      <div class="flex justify-center pt-3 sticky top-0 bg-white"><div class="w-10 h-1.5 rounded-full bg-slate-200"></div></div>
      <template x-if="!infoSent">
        <div class="px-6 pt-3 pb-2">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-brand-soft text-brand flex items-center justify-center shrink-0"><i class="fa-solid fa-envelope"></i></div>
            <div><h3 class="font-extrabold text-slate-800">Get product details</h3><p class="text-xs text-slate-400">We'll email you the full info</p></div>
            <button @click="closeInfo()" class="ml-auto text-slate-300 hover:text-slate-500"><i class="fa-solid fa-xmark text-xl"></i></button>
          </div>
          <form @submit.prevent="submitInfo()" class="space-y-3">
            @php $inp='w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-400'; @endphp
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Your details</p>
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
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 pt-1">Partner / seller (optional)</p>
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
        <div class="px-6 pt-6 pb-6 text-center">
          <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4 animate-pop"><i class="fa-solid fa-envelope-open-text text-2xl"></i></div>
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
    codeInput:@js($code),
    validateCode(){ const c=(this.codeInput||'').trim().replace(/\s+/g,''); if(c){ window.location.href='{{ url('/verify') }}/'+encodeURIComponent(c); } },
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
