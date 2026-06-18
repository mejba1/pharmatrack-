{{-- Configurable page loading effect (Verification Page Settings → Loading Screen).
     Self-contained overlay shown on load, then fades out after loader_min_ms.
     Effect colour follows the brand colour. --}}
@if(($cfg['loader_enabled'] ?? true))
@php
  $lc = $cfg['primary'] ?? '#059669';
  $ls = $cfg['loader_style'] ?? 'spinner';
  $lt = trim((string) ($cfg['loader_text'] ?? ''));
  $lm = (int) ($cfg['loader_min_ms'] ?? 700);
@endphp
<div id="vLoader" role="status" aria-live="polite"
     style="position:fixed;inset:0;z-index:9999;background:#ffffff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;transition:opacity .42s ease;--lc:{{ $lc }}">
  @php $limg = $cfg['loader_image'] ?? null; @endphp
  @switch($ls)
    @case('image')
      @if($limg)
        <img src="{{ asset('storage/'.$limg) }}" alt="Loading" class="vl-img">
      @else
        <div class="vl-spinner"></div>
      @endif
      @break
    @case('dots')
      <div class="vl-dots"><span></span><span></span><span></span></div>
      @break
    @case('pulse')
      <div class="vl-pulse"></div>
      @break
    @case('ring')
      <div class="vl-ring"><div></div><div></div></div>
      @break
    @case('bars')
      <div class="vl-bars"><span></span><span></span><span></span><span></span></div>
      @break
    @case('grow')
      <div class="vl-grow"></div>
      @break
    @case('orbit')
      <div class="vl-orbit"><span></span></div>
      @break
    @case('flip')
      <div class="vl-flip"></div>
      @break
    @case('wave')
      <div class="vl-wave"><span></span><span></span><span></span><span></span><span></span></div>
      @break
    @default
      <div class="vl-spinner"></div>
  @endswitch
  @if($lt !== '')
    <div style="color:#475569;font-family:'Inter',system-ui,sans-serif;font-weight:600;font-size:14px;letter-spacing:.2px">{{ $lt }}</div>
  @endif
</div>
<style>
  #vLoader.vl-hide{opacity:0}
  /* Spinner */
  .vl-spinner{width:48px;height:48px;border-radius:50%;border:4px solid color-mix(in srgb,var(--lc) 20%,transparent);border-top-color:var(--lc);animation:vlspin .8s linear infinite}
  @keyframes vlspin{to{transform:rotate(360deg)}}
  /* Dots */
  .vl-dots{display:flex;gap:10px}
  .vl-dots span{width:14px;height:14px;border-radius:50%;background:var(--lc);animation:vlbounce 1.1s ease-in-out infinite}
  .vl-dots span:nth-child(2){animation-delay:.16s}
  .vl-dots span:nth-child(3){animation-delay:.32s}
  @keyframes vlbounce{0%,80%,100%{transform:scale(.5);opacity:.5}40%{transform:scale(1);opacity:1}}
  /* Pulse */
  .vl-pulse{width:52px;height:52px;border-radius:50%;background:var(--lc);animation:vlpulse 1.2s ease-in-out infinite}
  @keyframes vlpulse{0%{transform:scale(.6);opacity:.7}50%{transform:scale(1);opacity:.25}100%{transform:scale(.6);opacity:.7}}
  /* Dual ring */
  .vl-ring{display:inline-block;width:52px;height:52px;position:relative}
  .vl-ring div{position:absolute;width:44px;height:44px;margin:4px;border:4px solid var(--lc);border-radius:50%;border-color:var(--lc) transparent transparent transparent;animation:vlspin 1.1s cubic-bezier(.5,0,.5,1) infinite}
  .vl-ring div:nth-child(2){animation-delay:-.55s}
  /* Bars */
  .vl-bars{display:flex;gap:6px;align-items:flex-end;height:44px}
  .vl-bars span{width:8px;height:100%;background:var(--lc);border-radius:4px;animation:vlbars 1s ease-in-out infinite}
  .vl-bars span:nth-child(2){animation-delay:.15s}
  .vl-bars span:nth-child(3){animation-delay:.3s}
  .vl-bars span:nth-child(4){animation-delay:.45s}
  @keyframes vlbars{0%,100%{transform:scaleY(.35)}50%{transform:scaleY(1)}}
  /* Grow circle */
  .vl-grow{width:48px;height:48px;border-radius:50%;background:var(--lc);animation:vlgrow 1s ease-in-out infinite}
  @keyframes vlgrow{0%{transform:scale(0);opacity:1}100%{transform:scale(1);opacity:0}}
  /* Orbit */
  .vl-orbit{width:52px;height:52px;border-radius:50%;border:3px solid color-mix(in srgb,var(--lc) 18%,transparent);position:relative;animation:vlspin 1.4s linear infinite}
  .vl-orbit span{position:absolute;top:-6px;left:50%;width:12px;height:12px;margin-left:-6px;border-radius:50%;background:var(--lc)}
  /* Flipping square */
  .vl-flip{width:44px;height:44px;background:var(--lc);animation:vlflip 1.2s ease-in-out infinite}
  @keyframes vlflip{0%{transform:perspective(120px) rotateX(0) rotateY(0)}50%{transform:perspective(120px) rotateX(-180deg) rotateY(0)}100%{transform:perspective(120px) rotateX(-180deg) rotateY(-180deg)}}
  /* Wave bars */
  .vl-wave{display:flex;gap:5px;align-items:center;height:44px}
  .vl-wave span{width:6px;height:100%;background:var(--lc);border-radius:3px;animation:vlwave 1s ease-in-out infinite}
  .vl-wave span:nth-child(2){animation-delay:.1s}
  .vl-wave span:nth-child(3){animation-delay:.2s}
  .vl-wave span:nth-child(4){animation-delay:.3s}
  .vl-wave span:nth-child(5){animation-delay:.4s}
  @keyframes vlwave{0%,40%,100%{transform:scaleY(.4)}20%{transform:scaleY(1)}}
  /* Custom image (let animated SVG/GIF play; add a subtle breathe only) */
  .vl-img{max-width:110px;max-height:110px;object-fit:contain;animation:vlbreathe 1.8s ease-in-out infinite}
  @keyframes vlbreathe{0%,100%{transform:scale(.94)}50%{transform:scale(1)}}
</style>
<script>
  (function(){
    var el = document.getElementById('vLoader');
    if(!el) return;
    var start = Date.now(), min = {{ $lm }};
    function hide(){
      var wait = Math.max(0, min - (Date.now() - start));
      setTimeout(function(){
        el.classList.add('vl-hide');
        setTimeout(function(){ if(el && el.parentNode) el.parentNode.removeChild(el); }, 460);
      }, wait);
    }
    if(document.readyState === 'complete') hide();
    else window.addEventListener('load', hide);
    // Safety: never trap the user if 'load' never fires.
    setTimeout(hide, min + 4000);
  })();
</script>
@endif
