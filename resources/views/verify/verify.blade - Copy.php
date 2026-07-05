<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Verification — PharmaTrack</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>body{font-family:'Inter',sans-serif}[x-cloak]{display:none!important}</style>
</head>
<body class="h-full bg-slate-50 text-slate-800">

@php
  $product = $unit?->batch?->product;
  $batch   = $unit?->batch;
  $images  = $product?->images ?? collect();
@endphp

@if($block)
  {{-- ════════ RESTRICTED / BLOCKED — single clean message, no other info ════════ --}}
  @php
    $meta = [
      'fake'     => ['This is NOT a Genuine Product', 'This code does not match any product in our records. It may be counterfeit — do not use it.', 'rose', 'shield'],
      'recalled' => ['Product Recalled', 'This product has been recalled. Do not use it and contact the manufacturer immediately.', 'rose', 'alert'],
      'locked'   => ['Locked by Administrator', 'This product can only be shown by the administrator. No further information is available.', 'slate', 'lock'],
      'expired'  => ['Product Expired', 'This product has passed its expiry date and should not be used.', 'amber', 'clock'],
      'invalid'  => ['Not Valid for Sale', 'This unit is marked not valid for sale.', 'rose', 'shield'],
      'country'  => ['Possibly Counterfeit — Not Sold in Your Country', 'This product is authorized for sale only in other countries. If you bought it in your country, it may be counterfeit — please report it below.', 'rose', 'shield'],
      'city'     => ['Not Available in Your City', 'This product is not authorized for sale in your city. If you bought it here, it may be counterfeit — please report it below.', 'rose', 'globe'],
      'device'   => ['Device Limit Reached', 'This product has reached its allowed number of verification devices.', 'amber', 'lock'],
      'hit'      => ['Verification Limit Reached', 'This code has reached its maximum number of verifications.', 'amber', 'ban'],
    ][$block] ?? ['Not Verified', 'This product could not be verified.', 'slate', 'shield'];

    $tone = [
      'rose'  => ['bg' => 'bg-rose-50',  'ring' => 'ring-rose-200',  'icon' => 'bg-rose-100 text-rose-600',  'title' => 'text-rose-700'],
      'amber' => ['bg' => 'bg-amber-50', 'ring' => 'ring-amber-200', 'icon' => 'bg-amber-100 text-amber-600','title' => 'text-amber-700'],
      'slate' => ['bg' => 'bg-slate-100','ring' => 'ring-slate-200', 'icon' => 'bg-slate-200 text-slate-600','title' => 'text-slate-700'],
    ][$meta[2]];
  @endphp

  <div class="min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-3xl shadow-xl ring-1 ring-slate-100 overflow-hidden">
        <div class="{{ $tone['bg'] }} px-8 pt-10 pb-8 text-center">
          <div class="mx-auto mb-5 w-20 h-20 rounded-2xl flex items-center justify-center {{ $tone['icon'] }} ring-8 ring-white/60">
            @if($meta[3]==='lock')
              <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 0h10.5a2.25 2.25 0 012.25 2.25v6.75a2.25 2.25 0 01-2.25 2.25H6.75a2.25 2.25 0 01-2.25-2.25v-6.75a2.25 2.25 0 012.25-2.25z"/></svg>
            @elseif($meta[3]==='globe')
              <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5-2.5 2.5-15 0-18m0 18c-2.5-2.5-2.5-15 0-18M3.6 9h16.8M3.6 15h16.8"/></svg>
            @elseif($meta[3]==='clock')
              <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
            @elseif($meta[3]==='ban')
              <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.36 5.64a9 9 0 11-12.72 12.72 9 9 0 0112.72-12.72zM5.64 5.64l12.72 12.72"/></svg>
            @elseif($meta[3]==='alert')
              <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.34 3.94L1.7 18a1.5 1.5 0 001.3 2.25h18a1.5 1.5 0 001.3-2.25L13.66 3.94a1.5 1.5 0 00-2.62 0z"/></svg>
            @else
              <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.96 11.96 0 013.6 6.04 11.99 11.99 0 003 9.75c0 5.59 3.82 10.29 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75a11.96 11.96 0 01-8.4-3.04z"/></svg>
            @endif
          </div>
          <h1 class="text-xl font-extrabold {{ $tone['title'] }}">{{ $meta[0] }}</h1>
          <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $meta[1] }}</p>
        </div>
        <div class="px-8 py-4 border-t border-slate-100 flex items-center justify-between">
          <span class="text-[11px] uppercase tracking-wider text-slate-400">Scanned code</span>
          <span class="font-mono text-xs text-slate-500">{{ $code }}</span>
        </div>
      </div>

      {{-- Report form --}}
      <div x-data="reportForm()" class="mt-4">
        <template x-if="!sent">
          <div class="bg-white rounded-2xl shadow ring-1 ring-slate-100 overflow-hidden">
            <button x-show="!open" @click="open=true" class="w-full px-5 py-4 flex items-center justify-center gap-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.69a9 9 0 016.21.99 9 9 0 006.21.99L21 15V4l-2.81.7a9 9 0 01-6.21-.99 9 9 0 00-6.21-.99L3 4.5"/></svg>
              Report this product
            </button>
            <form x-show="open" x-cloak @submit.prevent="submit()" class="p-5 space-y-3">
              <p class="text-sm font-bold text-slate-700">Report a suspected counterfeit</p>
              <input x-model="f.reporter_name" type="text" placeholder="Your name *" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-rose-200 focus:border-rose-400 outline-none">
              <input x-model="f.reporter_phone" type="tel" placeholder="Phone *" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-rose-200 focus:border-rose-400 outline-none">
              <input x-model="f.reporter_email" type="email" placeholder="Email (optional)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-rose-200 focus:border-rose-400 outline-none">
              <textarea x-model="f.message" rows="3" placeholder="Where did you buy it? Any details…" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-rose-200 focus:border-rose-400 outline-none"></textarea>
              <p x-show="error" x-text="error" class="text-xs text-rose-600"></p>
              <div class="flex gap-2">
                <button type="button" @click="open=false" class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-slate-500 bg-slate-100 hover:bg-slate-200">Cancel</button>
                <button type="submit" :disabled="saving" class="flex-1 py-2.5 rounded-lg text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-60" x-text="saving ? 'Sending…' : 'Submit report'"></button>
              </div>
            </form>
          </div>
        </template>
        <template x-if="sent">
          <div class="bg-emerald-50 ring-1 ring-emerald-200 rounded-2xl p-5 text-center">
            <svg class="w-8 h-8 mx-auto text-emerald-600 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-bold text-emerald-700">Thank you — your report has been submitted.</p>
            <p class="text-xs text-emerald-600 mt-1">Our team will review it.</p>
          </div>
        </template>
      </div>

      <p class="text-center text-xs text-slate-400 mt-5">Secured by PharmaTrack</p>
    </div>
  </div>

  <script>
    function reportForm(){
      return {
        open:false, sent:false, saving:false, error:'',
        f:{reporter_name:'',reporter_phone:'',reporter_email:'',message:''},
        async submit(){
          if(!this.f.reporter_name.trim() || !this.f.reporter_phone.trim()){ this.error='Please enter your name and phone.'; return; }
          this.saving=true; this.error='';
          const fd=new FormData();
          fd.append('_token','{{ csrf_token() }}');
          fd.append('reason','{{ $block }}');
          Object.entries(this.f).forEach(([k,v])=>fd.append(k,v));
          try{
            const r=await fetch('{{ route('verify.report', $code) }}',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
            if(r.ok){ this.sent=true; } else { const d=await r.json().catch(()=>({})); this.error=d.message||'Could not submit. Check your details.'; }
          }catch(e){ this.error='Network error. Please try again.'; }
          this.saving=false;
        }
      };
    }
  </script>

@else
  {{-- ════════ GENUINE — full product info / images / tracking ════════ --}}
  @php
    $days = $batch?->days_to_expiry;
    $monthsLeft = ($days !== null && $days > 0) ? (int) round($days / 30) : null;
    $journey = [
      ['Manufactured', (bool) $batch?->manufacture_date],
      ['Packed',       in_array($unit->status, ['packed','scanned','active','dispatched','received'], true)],
      ['Released',     in_array($batch?->qc_status, ['released'], true)],
      ['Distributed',  in_array($unit->status, ['dispatched','received','scanned','active'], true)],
      ['Verified',     true],
    ];
    $eventLabels = [
      'scanned' => 'Scanned / Verified', 'units_generated' => 'Units Generated',
      'units_removed' => 'Units Removed', 'printed' => 'Label Printed',
      'packed' => 'Packed', 'dispatched' => 'Dispatched', 'received' => 'Received',
    ];
  @endphp

  <div x-data="{tab:'info'}" class="max-w-2xl mx-auto p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.96 11.96 0 013.6 6.04 11.99 11.99 0 003 9.75c0 5.59 3.82 10.29 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75a11.96 11.96 0 01-8.4-3.04z"/></svg>
      </div>
      <div>
        <p class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">PharmaTrack · Authentication</p>
        <h1 class="text-lg font-bold text-slate-800 leading-tight">Product Verification</h1>
      </div>
    </div>

    {{-- Hero --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5 mb-4">
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden flex items-center justify-center shrink-0">
          @if($images->first())
            <img src="{{ $images->first()->url }}" alt="" class="w-full h-full object-cover">
          @else
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          @endif
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Authenticated Product</p>
          <h2 class="text-lg font-extrabold text-slate-800 truncate">{{ $product?->name ?? '—' }}</h2>
          <p class="text-sm text-slate-500 truncate">{{ $product?->generic_name ?? '—' }}</p>
        </div>
        <span class="ml-auto shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold ring-1 ring-emerald-200">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Genuine
        </span>
      </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 overflow-hidden">
      <div class="flex border-b border-slate-100">
        <template x-for="t in [{k:'info',l:'Product Info'},{k:'images',l:'Images'},{k:'tracking',l:'Tracking'}]" :key="t.k">
          <button @click="tab=t.k" class="flex-1 px-4 py-3 text-sm font-semibold transition"
                  :class="tab===t.k ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/50' : 'text-slate-400 hover:text-slate-600'"
                  x-text="t.l"></button>
        </template>
      </div>

      {{-- Product info --}}
      <div x-show="tab==='info'" class="p-5">
        <dl class="divide-y divide-slate-100">
          @php
            $rows = [
              ['Product', $product?->name],
              ['Generic', $product?->generic_name],
              ['Strength / Form', trim(($product?->strength ?? '').' '.($product?->dosage_form ? ucfirst(str_replace('_',' ',$product->dosage_form)) : ''))],
              ['Batch No.', $batch?->batch_number],
              ['BRN', $batch?->brn],
              ['Manufactured', optional($batch?->manufacture_date)->format('d M Y')],
              ['Serial No.', '#'.$unit->serial_number],
              ['Label No.', $unit->unique_number],
              ['Manufacturer', $product?->manufacturer_name],
              ['Country', $product?->country_of_origin],
            ];
          @endphp
          @foreach($rows as $r)
            @if($r[1])
              <div class="flex items-start gap-4 py-2.5">
                <dt class="text-[11px] uppercase tracking-wider text-slate-400 w-32 shrink-0 pt-0.5">{{ $r[0] }}</dt>
                <dd class="text-sm font-semibold text-slate-700 text-right ml-auto">{{ $r[1] }}</dd>
              </div>
            @endif
          @endforeach
          <div class="flex items-start gap-4 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 w-32 shrink-0 pt-0.5">Expires</dt>
            <dd class="text-sm font-semibold text-slate-700 text-right ml-auto flex items-center gap-2 justify-end flex-wrap">
              <span>{{ optional($batch?->expiry_date)->format('d M Y') ?? '—' }}</span>
              @if($monthsLeft !== null)<span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[11px] font-bold ring-1 ring-amber-200">{{ $monthsLeft }} mo left</span>@endif
            </dd>
          </div>
        </dl>
      </div>

      {{-- Images --}}
      <div x-show="tab==='images'" x-cloak class="p-5">
        @if($images->count())
          <div x-data="{main:'{{ $images->first()->url }}'}">
            <div class="rounded-xl overflow-hidden bg-slate-100 mb-3 aspect-video flex items-center justify-center">
              <img :src="main" alt="" class="w-full h-full object-contain">
            </div>
            <div class="grid grid-cols-4 gap-2">
              @foreach($images as $img)
                <button @click="main='{{ $img->url }}'" class="rounded-lg overflow-hidden ring-1 ring-slate-200 aspect-square hover:ring-blue-400 transition">
                  <img src="{{ $img->url }}" alt="{{ $img->original_name }}" class="w-full h-full object-cover">
                </button>
              @endforeach
            </div>
          </div>
        @else
          <div class="text-center py-10">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.16-5.16a2.25 2.25 0 013.18 0l5.16 5.16m-1.5-1.5l1.41-1.41a2.25 2.25 0 013.18 0l2.16 2.16M21 19.5V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v13.5M21 19.5a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 19.5"/></svg>
            <p class="text-sm font-semibold text-slate-600">No images available</p>
            <p class="text-xs text-slate-400 mt-1">No official product images have been published for this item.</p>
          </div>
        @endif
      </div>

      {{-- Tracking --}}
      <div x-show="tab==='tracking'" x-cloak class="p-5">
        {{-- Journey --}}
        <div class="flex items-center justify-between mb-6">
          @foreach($journey as $i => $stage)
            <div class="flex flex-col items-center text-center flex-1 min-w-0">
              <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs {{ $stage[1] ? 'bg-blue-600' : 'bg-slate-200 text-slate-400' }}">
                @if($stage[1])
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                @else {{ $i+1 }} @endif
              </div>
              <span class="mt-1 text-[10px] font-semibold {{ $stage[1] ? 'text-slate-700' : 'text-slate-400' }}">{{ $stage[0] }}</span>
            </div>
            @if(!$loop->last)<div class="h-0.5 flex-1 mb-4 {{ ($journey[$i+1][1] ?? false) ? 'bg-blue-600' : 'bg-slate-200' }}"></div>@endif
          @endforeach
        </div>

        {{-- Verification info --}}
        @if($verification)
        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-100 p-4 mb-5">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Verification Information</p>
          <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-slate-400">Verification ID</dt><dd class="font-mono text-xs text-slate-700 text-right">{{ $verification->verification_number }}</dd>
            <dt class="text-slate-400">Verified at</dt><dd class="text-slate-700 text-right">{{ $verification->created_at?->format('d M Y, H:i') }}</dd>
            <dt class="text-slate-400">Total scans</dt><dd class="text-slate-700 text-right">{{ number_format($scanStats['count']) }}</dd>
            <dt class="text-slate-400">First verified</dt><dd class="text-slate-700 text-right">{{ optional($scanStats['first'])->format('d M Y') ?? '—' }}</dd>
            <dt class="text-slate-400">Country</dt><dd class="text-slate-700 text-right">{{ $verification->country ?? 'Unknown' }}</dd>
          </dl>
        </div>
        @endif

        {{-- History --}}
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Journey Log</p>
        @forelse($logs as $log)
          <div class="flex gap-3 pb-4 relative">
            <div class="flex flex-col items-center">
              <div class="w-2.5 h-2.5 rounded-full bg-blue-600 mt-1.5"></div>
              @if(!$loop->last)<div class="w-px flex-1 bg-slate-200 my-1"></div>@endif
            </div>
            <div class="pb-1">
              <p class="text-sm font-semibold text-slate-700">{{ $eventLabels[$log->event] ?? ucfirst(str_replace('_',' ',$log->event)) }}</p>
              <p class="text-xs text-slate-400">{{ $log->created_at?->format('d M Y, H:i') }} · {{ ucfirst($log->performed_by ?? 'system') }}</p>
            </div>
          </div>
        @empty
          <p class="text-sm text-slate-400 text-center py-4">No history recorded yet.</p>
        @endforelse
      </div>
    </div>

    <p class="text-center text-xs text-slate-400 mt-5">Secured by PharmaTrack · {{ now()->format('d M Y, H:i') }}</p>
  </div>
@endif

</body>
</html>
