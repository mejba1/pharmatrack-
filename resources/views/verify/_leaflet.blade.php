{{-- Insert / leaflet download button (shared). Expects $cfg, $product.
     Prefers the product's own uploaded PDF (pdf_path); falls back to the
     global leaflet uploaded in settings. --}}
@php
  $leafletUrl = $product?->pdf_url
    ?: ($cfg['leaflet_path'] ? asset('storage/'.$cfg['leaflet_path']) : null);
@endphp
@if($cfg['show_leaflet'] && $leafletUrl)
  <a href="{{ $leafletUrl }}" target="_blank" rel="noopener"
     class="mt-4 w-full py-3.5 rounded-2xl bg-brand-soft text-brand font-bold text-sm flex items-center justify-center gap-2 active:scale-[.98] transition">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
    {{ $cfg['leaflet_label'] ?: 'Download insert / leaflet' }}
  </a>
@endif
