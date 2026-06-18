{{-- Scan-intelligence alerts (8 configurable scenarios). Self-contained &
     style-agnostic: fixed overlay banners + a record-detail modal. Uses Tailwind
     (loaded on every style) with static classes (CDN-safe) and inherits
     openSheet() from the parent vApp() Alpine scope. --}}
@php $__alerts = $alerts ?? []; @endphp
@if(!empty($__alerts) || ($certificate ?? false))
@php
  $tones = [
    'danger'  => ['bg' => 'bg-rose-500',  'text' => 'text-rose-700',  'border' => 'border-rose-200',  'soft' => 'bg-rose-50'],
    'warning' => ['bg' => 'bg-amber-500', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'soft' => 'bg-amber-50'],
    'info'    => ['bg' => 'bg-sky-500',   'text' => 'text-sky-700',   'border' => 'border-sky-200',   'soft' => 'bg-sky-50'],
  ];
  $colLabels = ['ip' => 'IP / Network', 'country' => 'Country', 'city' => 'City', 'time' => 'Date & Time'];
@endphp
<div x-data="{ rec:null, certOpen:false, dismissed:{} }">

  {{-- ── Banner stack (top) ── --}}
  <div class="fixed top-0 inset-x-0 z-[60] mx-auto w-full max-w-[440px] px-3 pt-3 space-y-2">
    @foreach($__alerts as $i => $a)
      @php $t = $tones[$a['level']] ?? $tones['warning']; @endphp
      <div x-show="!dismissed[{{ $i }}]" x-cloak
           class="rounded-2xl border {{ $t['border'] }} bg-white/95 backdrop-blur shadow-lg p-3 flex items-start gap-2.5 rise">
        <div class="w-9 h-9 rounded-xl {{ $t['bg'] }} text-white flex items-center justify-center shrink-0 font-black text-lg leading-none">!</div>
        <div class="flex-1 min-w-0">
          <div class="font-bold text-[13px] {{ $t['text'] }}">{{ $a['title'] }}</div>
          <div class="text-[12px] text-slate-600 mt-0.5 leading-snug">{{ $a['text'] }}</div>
          <div class="mt-2 flex flex-wrap items-center gap-1.5">
            @if(!empty($a['records']))
              <button type="button" @click="rec={{ $i }}"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg {{ $t['soft'] }} {{ $t['text'] }} text-[11px] font-semibold">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                View {{ count($a['records']) }} scan{{ count($a['records']) === 1 ? '' : 's' }}
              </button>
            @endif
            @if(!empty($a['report']))
              <button type="button" @click="openSheet()" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-semibold">Report this</button>
            @endif
            @if(!empty($a['certificate']))
              <button type="button" @click="certOpen=true" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-600 text-white text-[11px] font-semibold">Get certificate</button>
            @endif
          </div>
        </div>
        <button type="button" @click="dismissed[{{ $i }}]=true" class="text-slate-300 hover:text-slate-500 text-xl leading-none px-0.5" aria-label="Dismiss">&times;</button>
      </div>
    @endforeach
  </div>

  {{-- ── Record-detail modal (history-tab style) ── --}}
  <div x-show="rec!==null" x-cloak class="fixed inset-0 z-[75] flex items-end sm:items-center justify-center"
       style="background:rgba(15,23,42,.55)" @click.self="rec=null" @keydown.escape.window="rec=null">
    <div class="w-full max-w-[440px] bg-white rounded-t-3xl sm:rounded-3xl max-h-[86vh] flex flex-col overflow-hidden shadow-2xl sheet-tr"
         x-show="rec!==null" x-transition>
      @foreach($__alerts as $i => $a)
        @php $t = $tones[$a['level']] ?? $tones['warning']; @endphp
        <template x-if="rec==={{ $i }}">
          <div class="flex flex-col min-h-0">
            {{-- Header --}}
            <div class="p-4 {{ $t['soft'] }} border-b {{ $t['border'] }}">
              <div class="flex items-start gap-2.5">
                <div class="w-9 h-9 rounded-xl {{ $t['bg'] }} text-white flex items-center justify-center shrink-0 font-black text-lg leading-none">!</div>
                <div class="flex-1 min-w-0">
                  <div class="font-bold text-slate-800 text-sm">{{ $a['title'] }}</div>
                  <div class="text-[12px] text-slate-500 mt-0.5 leading-snug">{{ $a['text'] }}</div>
                  @if($a['key'] === 'multi_ip_same_country')
                    <span class="inline-block mt-1.5 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wide">Same country</span>
                  @endif
                  @if(!empty($a['intended']))
                    <div class="text-[12px] mt-1.5"><span class="font-semibold text-slate-700">Intended region:</span> {{ $a['intended'] }}</div>
                  @endif
                </div>
                <button type="button" @click="rec=null" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
              </div>
            </div>

            {{-- Records --}}
            <div class="p-3 overflow-y-auto no-sb">
              <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400 px-1 mb-2">Previous scan records</div>
              <div class="space-y-2">
                @forelse($a['records'] as $ri => $rec)
                  @php
                    $primary   = $rec['ip'] ?? $rec['country'] ?? '—';
                    $secondary = collect([$rec['city'] ?? null, $rec['country'] ?? null])->filter()->unique()->implode(' · ') ?: '—';
                  @endphp
                  <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 flex items-center gap-3">
                    <div class="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400 text-[11px] font-bold shrink-0">{{ $ri + 1 }}</div>
                    <div class="flex-1 min-w-0">
                      <div class="font-semibold text-slate-700 text-[13px] font-mono truncate">{{ $primary }}</div>
                      <div class="text-[11px] text-slate-400 truncate">{{ $secondary }}</div>
                    </div>
                    <div class="text-[11px] text-slate-500 text-right shrink-0">{{ $rec['time'] ?? '' }}</div>
                  </div>
                @empty
                  <div class="text-center text-slate-400 text-[12px] py-6">No detailed records to show.</div>
                @endforelse
              </div>

              {{-- Footer actions --}}
              <div class="flex gap-2 pt-3 pb-1">
                @if(!empty($a['report']))
                  <button type="button" @click="rec=null; openSheet()" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold">Report this</button>
                @endif
                @if(!empty($a['certificate']))
                  <button type="button" @click="rec=null; certOpen=true" class="flex-1 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-semibold">Get certificate</button>
                @endif
                @if(empty($a['report']) && empty($a['certificate']))
                  <button type="button" @click="rec=null" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold">Close</button>
                @endif
              </div>
            </div>
          </div>
        </template>
      @endforeach
    </div>
  </div>

  {{-- ── Certificate request modal (scenario 8) ── --}}
  @if($certificate ?? false)
  <div x-show="certOpen" x-cloak class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-3"
       style="background:rgba(15,23,42,.55)" @click.self="certOpen=false" @keydown.escape.window="certOpen=false">
    <form method="POST" action="{{ route('verify.certificate', $code) }}" class="w-full max-w-[440px] bg-white rounded-3xl p-4 space-y-3 shadow-2xl">
      @csrf
      <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="font-bold text-slate-800">Genuine Product Certificate</div>
      </div>
      <p class="text-[12px] text-slate-500 leading-snug">Fill in your details to download a PDF certificate confirming this product is genuine, with full product information.</p>
      <input name="name" required placeholder="Your name" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
      <input name="phone" placeholder="Phone (optional)" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
      <input name="email" type="email" placeholder="Email (optional)" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
      <div class="flex gap-2">
        <input name="country" placeholder="Country" class="w-1/2 border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
        <input name="city" placeholder="City" class="w-1/2 border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
      </div>
      <div class="flex gap-2 pt-1">
        <button type="button" @click="certOpen=false" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-sm font-semibold">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-semibold">Download PDF</button>
      </div>
    </form>
  </div>
  @endif
</div>
@endif
