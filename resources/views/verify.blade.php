<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Product Verification — PharmaTrack</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <style>
    [data-theme="dark"]{
      --bg:#050C1A; --bg-mid:#091120; --card:#0D1829; --card-hover:#102035;
      --border:#1A2D4A; --border-soft:#142240; --text:#E8F0FE; --text-muted:#5A7AA8; --text-soft:#8BA4C8;
      --accent:#3B82F6; --accent-dim:#2563EB; --accent-glow:rgba(59,130,246,0.18); --accent-ring:rgba(59,130,246,0.35);
      --gold:#F59E0B; --gold-bg:rgba(245,158,11,0.1); --gold-border:rgba(245,158,11,0.25);
      --success:#10B981; --success-bg:rgba(16,185,129,0.1); --success-border:rgba(16,185,129,0.3);
      --danger:#EF4444; --danger-bg:rgba(239,68,68,0.1); --danger-border:rgba(239,68,68,0.3);
      --shadow-card:0 4px 24px rgba(0,0,0,0.4); --grid-line:rgba(26,45,74,0.5);
    }
    [data-theme="light"]{
      --bg:#F0F4FF; --bg-mid:#E8EEFF; --card:#FFFFFF; --card-hover:#F5F8FF;
      --border:#C7D7F0; --border-soft:#D8E5F5; --text:#0D1829; --text-muted:#6B85AA; --text-soft:#4A6285;
      --accent:#2563EB; --accent-dim:#1D4ED8; --accent-glow:rgba(37,99,235,0.1); --accent-ring:rgba(37,99,235,0.3);
      --gold:#D97706; --gold-bg:rgba(217,119,6,0.08); --gold-border:rgba(217,119,6,0.25);
      --success:#059669; --success-bg:rgba(5,150,105,0.08); --success-border:rgba(5,150,105,0.3);
      --danger:#DC2626; --danger-bg:rgba(220,38,38,0.08); --danger-border:rgba(220,38,38,0.3);
      --shadow-card:0 4px 24px rgba(13,24,41,0.1); --grid-line:rgba(199,215,240,0.6);
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{
      font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); min-height:100vh;
      transition:background .4s,color .4s;
      background-image:
        radial-gradient(ellipse 70% 45% at 60% -10%, var(--accent-glow) 0%, transparent 65%),
        repeating-linear-gradient(0deg, transparent, transparent 47px, var(--grid-line) 47px, var(--grid-line) 48px),
        repeating-linear-gradient(90deg, transparent, transparent 47px, var(--grid-line) 47px, var(--grid-line) 48px);
    }
    .mono{font-family:'DM Mono',monospace}
    .card{background:var(--card); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow-card); transition:background .3s,border-color .3s}
    .accent-text{color:var(--accent)} .success-text{color:var(--success)} .gold-text{color:var(--gold)}
    .danger-text{color:var(--danger)} .muted{color:var(--text-muted)} .soft{color:var(--text-soft)}

    .tab-bar{display:flex; gap:4px; padding:6px 6px 0; border-bottom:1px solid var(--border); overflow-x:auto}
    .tab-btn{position:relative; padding:10px 18px; border-radius:10px 10px 0 0; font-weight:700; font-size:.76rem;
      letter-spacing:.05em; text-transform:uppercase; cursor:pointer; color:var(--text-muted); background:transparent;
      border:none; transition:color .2s,background .2s; display:flex; align-items:center; gap:7px; white-space:nowrap}
    .tab-btn.active{color:var(--accent); background:var(--accent-glow)}
    .tab-btn.active::after{content:''; position:absolute; bottom:-1px; left:8px; right:8px; height:2px; background:var(--accent); border-radius:2px 2px 0 0}
    .tab-btn:hover:not(.active){color:var(--text-soft); background:var(--accent-glow)}
    .tab-panel{display:none; animation:fadeIn .25s ease}
    .tab-panel.active{display:block}
    @keyframes fadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}

    .info-row{display:flex; align-items:flex-start; gap:16px; padding:13px 0; border-bottom:1px solid var(--border-soft)}
    .info-row:last-of-type{border-bottom:none}
    .info-label{font-size:.68rem; text-transform:uppercase; letter-spacing:.1em; color:var(--text-muted); min-width:118px; padding-top:2px}
    .info-value{font-weight:700; color:var(--text); font-size:.88rem; text-align:right; flex:1}

    .badge{display:inline-flex; align-items:center; gap:5px; font-size:.66rem; font-weight:800; letter-spacing:.08em;
      text-transform:uppercase; padding:3px 11px; border-radius:99px}
    .badge.success{background:var(--success-bg); color:var(--success); border:1px solid var(--success-border)}
    .badge.accent{background:var(--accent-glow); color:var(--accent); border:1px solid var(--accent-ring)}
    .badge.gold{background:var(--gold-bg); color:var(--gold); border:1px solid var(--gold-border)}
    .badge.danger{background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border)}

    @keyframes pulse-ring{0%{transform:scale(1);opacity:.5}100%{transform:scale(1.4);opacity:0}}
    .verify-wrap{position:relative; display:inline-flex}
    .verify-wrap::before{content:''; position:absolute; inset:0; border-radius:14px; background:var(--accent);
      animation:pulse-ring 2.2s ease-out infinite; pointer-events:none}
    .verify-btn{position:relative; z-index:1; display:flex; align-items:center; gap:10px; padding:14px 36px; border-radius:14px;
      font-weight:800; font-size:.82rem; letter-spacing:.1em; text-transform:uppercase; color:#fff; border:none; cursor:pointer;
      background:linear-gradient(135deg,#3B82F6,#1D4ED8); box-shadow:0 0 28px rgba(59,130,246,0.4), inset 0 1px 0 rgba(255,255,255,0.15);
      transition:transform .15s,box-shadow .15s}
    .verify-btn:hover{transform:scale(1.04); box-shadow:0 0 36px rgba(59,130,246,0.55)}
    .verify-btn:active{transform:scale(.97)}

    .chip{background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px 10px; text-align:center;
      transition:border-color .2s,transform .2s}
    .chip:hover{border-color:var(--accent); transform:translateY(-2px)}

    .img-thumb{border-radius:12px; overflow:hidden; border:2px solid var(--border); cursor:pointer;
      transition:border-color .2s,transform .2s,box-shadow .2s; background:var(--bg-mid); aspect-ratio:1;
      display:flex; align-items:center; justify-content:center}
    .img-thumb:hover{border-color:var(--accent); transform:scale(1.04); box-shadow:0 0 12px var(--accent-ring)}
    .img-thumb.selected{border-color:var(--accent); box-shadow:0 0 14px var(--accent-ring)}
    .img-thumb img{width:100%; height:100%; object-fit:cover}

    @keyframes scan{0%{top:8%}50%{top:85%}100%{top:8%}}
    .scan-line{position:absolute; left:0; right:0; height:2px; background:linear-gradient(90deg,transparent,var(--accent),transparent);
      animation:scan 3s ease-in-out infinite; box-shadow:0 0 12px var(--accent)}

    .tl-item{position:relative; padding-left:36px; padding-bottom:24px}
    .tl-item::before{content:''; position:absolute; left:9px; top:22px; bottom:0; width:2px;
      background:linear-gradient(to bottom,var(--accent),transparent)}
    .tl-item:last-child::before{display:none}
    .tl-dot{position:absolute; left:0; top:5px; width:20px; height:20px; border-radius:50%; background:var(--accent);
      border:3px solid var(--card); box-shadow:0 0 0 2px var(--accent),0 0 10px var(--accent-ring);
      display:flex; align-items:center; justify-content:center; font-size:9px; color:#fff}
    .tl-dot.dim{background:var(--border); box-shadow:0 0 0 2px var(--border)}

    .toggle-track{width:52px; height:28px; border-radius:99px; background:var(--border); position:relative; cursor:pointer;
      border:2px solid var(--border); transition:background .3s}
    .toggle-track.on{background:var(--accent); border-color:var(--accent)}
    .toggle-thumb{position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff;
      transition:transform .3s cubic-bezier(.34,1.56,.64,1); display:flex; align-items:center; justify-content:center; font-size:11px}
    .toggle-track.on .toggle-thumb{transform:translateX(24px)}

    .modal-overlay{position:fixed; inset:0; background:rgba(5,12,26,0.75); backdrop-filter:blur(10px); z-index:100;
      display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity .3s}
    .modal-overlay.open{opacity:1; pointer-events:all}
    .modal-box{background:var(--card); border:1px solid var(--border); border-radius:24px; padding:38px 34px; max-width:420px; width:92%;
      transform:scale(.88) translateY(16px); transition:transform .35s cubic-bezier(.34,1.2,.64,1); box-shadow:0 20px 60px rgba(0,0,0,0.5)}
    .modal-overlay.open .modal-box{transform:scale(1) translateY(0)}
    @keyframes spin{to{transform:rotate(360deg)}}
    .spin{animation:spin 1s linear infinite}

    .header-stripe{background:linear-gradient(135deg,var(--card) 0%,var(--bg-mid) 100%); border:1px solid var(--border);
      border-radius:20px; padding:18px 20px; position:relative; overflow:hidden}
    .header-stripe::before{content:''; position:absolute; top:0; left:0; right:0; height:2px;
      background:linear-gradient(90deg,transparent,var(--accent),transparent)}
    .header-stripe.bad::before{background:linear-gradient(90deg,transparent,var(--danger),transparent)}
    .stat-pill{background:var(--bg-mid); border:1px solid var(--border); border-radius:12px; padding:14px 12px; text-align:center}
  </style>
</head>
<body class="p-4 md:p-8">

@php
  $unitOk = false; $expired = false; $blocked = false; $suspicious = false; $locked = false; $recalled = (bool) ($recall ?? null);
  if ($unit) {
      $expired = $unit->batch?->expiry_date && $unit->batch->expiry_date->isPast();
      $blocked = in_array($unit->status, ['blocked','inactive','expired']);
      $result  = $verification?->result ?? ($recalled ? 'recalled' : ($expired ? 'expired' : ($blocked ? 'invalid' : 'genuine')));
      $suspicious = $result === 'suspicious';
      $locked  = $result === 'locked';
      $unitOk  = $result === 'genuine';
  }
  $product = $unit?->batch?->product;
  $batch   = $unit?->batch;
  $days    = $batch?->days_to_expiry;
  $monthsLeft = ($days !== null && $days > 0) ? (int) round($days / 30) : null;
  $images  = $product?->images ?? collect();

  // Product journey lifecycle stages (reached?).
  $unitState = $unit?->status;
  $journey = $unit ? [
      ['Manufactured', 'bi-building',      (bool) $batch?->manufacture_date],
      ['Packed',       'bi-box-seam',      in_array($unitState, ['packed','scanned','active','dispatched','received'], true)],
      ['Released',     'bi-clipboard2-check', in_array($batch?->qc_status, ['released'], true)],
      ['Distributed',  'bi-truck',         in_array($unitState, ['dispatched','received','scanned','active'], true)],
      ['Verified',     'bi-patch-check',   true],
  ] : [];
@endphp

@if($unit)
  @php
    $statusBadge = match(true) {
        $recalled   => ['class'=>'danger','icon'=>'bi-exclamation-octagon-fill','text'=>'Recalled'],
        $locked     => ['class'=>'danger','icon'=>'bi-lock-fill','text'=>'Locked'],
        $unitOk     => ['class'=>'success','icon'=>'bi-patch-check-fill','text'=>'Genuine'],
        $suspicious => ['class'=>'gold','icon'=>'bi-exclamation-triangle-fill','text'=>'Requires Investigation'],
        $expired    => ['class'=>'danger','icon'=>'bi-clock-history','text'=>'Expired'],
        default     => ['class'=>'danger','icon'=>'bi-x-octagon-fill','text'=>ucfirst($unit->status)],
    };
  @endphp

  @if($locked)
    <div class="max-w-2xl mx-auto mb-4">
      <div class="card p-4" style="border-color:var(--danger-border); background:var(--danger-bg);">
        <div class="flex items-start gap-3">
          <i class="bi bi-lock-fill danger-text" style="font-size:1.6rem;"></i>
          <div>
            <p class="font-extrabold danger-text" style="font-size:.95rem; text-transform:uppercase; letter-spacing:.05em;">Verification Locked</p>
            <p style="font-size:.8rem; margin-top:4px; line-height:1.5;">Verification for this item has been restricted by the manufacturer's access policy. If you believe this is an error, contact your supplier.</p>
          </div>
        </div>
      </div>
    </div>
  @endif

  @if($recalled)
    <div class="max-w-2xl mx-auto mb-4">
      <div class="card p-4" style="border-color:var(--danger-border); background:var(--danger-bg);">
        <div class="flex items-start gap-3">
          <i class="bi bi-exclamation-octagon-fill danger-text" style="font-size:1.6rem;"></i>
          <div>
            <p class="font-extrabold danger-text" style="font-size:.95rem; text-transform:uppercase; letter-spacing:.05em;">Product Recall — Do Not Use</p>
            <p style="font-size:.8rem; margin-top:4px; line-height:1.5;">
              This product belongs to a <strong>recalled batch</strong> ({{ $recall->scope_label }}). Do not consume this product. Contact the manufacturer immediately.
              @if($recall->reason)<br><span class="muted">Reason: {{ $recall->reason }}</span>@endif
            </p>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- ═══ HEADER ═══ -->
  <div class="max-w-2xl mx-auto mb-5">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0"
             style="background:var(--accent-glow); border:1.5px solid var(--accent-ring);">
          <i class="bi bi-capsule-pill" style="color:var(--accent); font-size:1.2rem;"></i>
        </div>
        <div>
          <p class="mono muted" style="font-size:.65rem; letter-spacing:.12em;">PHARMATRACK · AUTHENTICATION</p>
          <h1 class="font-bold leading-tight" style="font-size:1.1rem;">Product Verification</h1>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="muted" style="font-size:.7rem; letter-spacing:.06em; text-transform:uppercase;"><span id="themeLabel">Dark</span></span>
        <div class="toggle-track on" id="themeToggle" onclick="toggleTheme()"><div class="toggle-thumb">🌙</div></div>
      </div>
    </div>

    <!-- Product header stripe -->
    <div class="header-stripe {{ $unitOk ? '' : 'bad' }}">
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl flex-shrink-0 flex items-center justify-center overflow-hidden"
               style="background:linear-gradient(135deg,var(--accent-glow),transparent); border:1.5px solid var(--accent-ring);">
            @if($images->first())
              <img src="{{ $images->first()->url }}" alt="" style="width:100%;height:100%;object-fit:cover;">
            @else
              <i class="bi bi-capsule" style="color:var(--accent); font-size:1.7rem;"></i>
            @endif
          </div>
          <div>
            <p class="muted" style="font-size:.68rem; letter-spacing:.1em; text-transform:uppercase; margin-bottom:2px;">
              {{ $unitOk ? 'Authenticated Product' : 'Verification Alert' }}
            </p>
            <h2 class="font-extrabold" style="font-size:1.15rem;">{{ $product?->name ?? 'Unknown Product' }}</h2>
            <p class="soft" style="font-size:.8rem; margin-top:2px;">
              {{ $product?->generic_name ?? '—' }}{{ $product?->therapeutic_class ? ' · '.$product->therapeutic_class : '' }}
            </p>
          </div>
        </div>
        <div class="flex flex-col items-end gap-2 flex-shrink-0">
          <span class="badge {{ $statusBadge['class'] }}"><i class="bi {{ $statusBadge['icon'] }}"></i>{{ $statusBadge['text'] }}</span>
          <p class="mono muted" style="font-size:.65rem;">{{ $unit->unique_number ?? $batch?->brn }}</p>
        </div>
      </div>

      <!-- Scan ID bar -->
      <div class="mt-4 flex items-center justify-between gap-3 rounded-xl px-4 py-3"
           style="background:var(--bg-mid); border:1px solid var(--border-soft);">
        <div style="min-width:0">
          <p class="muted" style="font-size:.65rem; letter-spacing:.1em; text-transform:uppercase;">Verified Code</p>
          <p class="mono accent-text font-semibold" style="font-size:.82rem; margin-top:2px; word-break:break-all;">{{ $code }}</p>
        </div>
        <div class="relative w-11 h-11 rounded-xl overflow-hidden flex-shrink-0" style="background:var(--border); border:1px solid var(--border);">
          <div class="scan-line"></div>
          <i class="bi bi-qr-code" style="color:var(--accent); font-size:1.4rem; display:flex; align-items:center; justify-content:center; height:100%; opacity:.75;"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ MAIN CARD ═══ -->
  <div class="max-w-2xl mx-auto card overflow-hidden">
    <div class="tab-bar">
      <button class="tab-btn active" onclick="switchTab('info',this)"><i class="bi bi-file-earmark-text"></i>Product Info</button>
      <button class="tab-btn" onclick="switchTab('images',this)"><i class="bi bi-images"></i>Images</button>
      <button class="tab-btn" onclick="switchTab('tracking',this)"><i class="bi bi-diagram-3"></i>Tracking</button>
    </div>

    <!-- ══ PRODUCT INFO ══ -->
    <div class="tab-panel active p-6" id="tab-info">
      <div class="card p-5 mb-5">
        <div class="info-row"><span class="info-label">Product</span><span class="info-value">{{ $product?->name ?? '—' }}</span></div>
        @if($product?->generic_name)<div class="info-row"><span class="info-label">Generic</span><span class="info-value">{{ $product->generic_name }}</span></div>@endif
        @if($product?->strength || $product?->dosage_form)
        <div class="info-row"><span class="info-label">Strength / Form</span><span class="info-value">{{ trim(($product->strength ?? '').' '.($product->dosage_form ? $product->dosage_form_label : '')) ?: '—' }}</span></div>
        @endif
        <div class="info-row"><span class="info-label">PRN</span><span class="info-value mono accent-text">{{ $product?->prn ?? '—' }}</span></div>
        <div class="info-row"><span class="info-label">Batch No.</span><span class="info-value mono">{{ $batch?->batch_number ?? '—' }}</span></div>
        <div class="info-row"><span class="info-label">BRN</span><span class="info-value mono">{{ $batch?->brn ?? '—' }}</span></div>
        @if($batch?->lot_number)<div class="info-row"><span class="info-label">Lot</span><span class="info-value mono">{{ $batch->lot_number }}</span></div>@endif
        <div class="info-row"><span class="info-label">Manufactured</span><span class="info-value">{{ optional($batch?->manufacture_date)->format('d M Y') ?? '—' }}</span></div>
        <div class="info-row">
          <span class="info-label">Expires</span>
          <div class="flex items-center gap-2 flex-wrap justify-end" style="flex:1">
            <span class="info-value" style="flex:none">{{ optional($batch?->expiry_date)->format('d M Y') ?? '—' }}</span>
            @if($expired)
              <span class="badge danger"><i class="bi bi-clock-history"></i>Expired</span>
            @elseif($monthsLeft !== null)
              <span class="badge gold"><i class="bi bi-hourglass-split"></i>{{ $monthsLeft }} mo left</span>
            @endif
          </div>
        </div>
        <div class="info-row"><span class="info-label">Serial No.</span><span class="info-value mono">#{{ $unit->serial_number }}</span></div>
        <div class="info-row"><span class="info-label">Label No.</span><span class="info-value mono" style="font-size:.8rem;">{{ $unit->unique_number ?? '—' }}</span></div>
        <div class="info-row"><span class="info-label">Manufacturer</span><span class="info-value">{{ $product?->manufacturer_name ?? '—' }}</span></div>
        @if($product?->country_of_origin)<div class="info-row"><span class="info-label">Country</span><span class="info-value">{{ $product->country_of_origin }}</span></div>@endif
        @if($batch?->storage_conditions || $product?->storage_conditions)<div class="info-row"><span class="info-label">Storage</span><span class="info-value" style="font-size:.8rem;">{{ $batch?->storage_conditions ?: $product?->storage_conditions }}</span></div>@endif
      </div>

      <!-- Integrity chips (real) -->
      <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="chip">
          <i class="bi {{ $blocked ? 'bi-shield-exclamation danger-text' : 'bi-shield-check success-text' }}" style="font-size:1.4rem;"></i>
          <p class="font-bold" style="font-size:.74rem; margin-top:6px;">Unit Status</p>
          <p class="{{ $blocked ? 'danger-text' : 'success-text' }} font-semibold" style="font-size:.7rem; margin-top:3px;">{{ ucfirst($unit->status) }}</p>
        </div>
        <div class="chip">
          <i class="bi bi-clipboard2-check success-text" style="font-size:1.4rem;"></i>
          <p class="font-bold" style="font-size:.74rem; margin-top:6px;">QC Status</p>
          <p class="success-text font-semibold" style="font-size:.7rem; margin-top:3px;">{{ $batch?->qc_status_label ?? '—' }}</p>
        </div>
        <div class="chip">
          <i class="bi {{ $expired ? 'bi-calendar-x danger-text' : 'bi-calendar-check success-text' }}" style="font-size:1.4rem;"></i>
          <p class="font-bold" style="font-size:.74rem; margin-top:6px;">Validity</p>
          <p class="{{ $expired ? 'danger-text' : 'success-text' }} font-semibold" style="font-size:.7rem; margin-top:3px;">{{ $expired ? 'Expired' : 'In Date' }}</p>
        </div>
      </div>

      <div style="border-top:1px solid var(--border-soft); margin-bottom:24px;"></div>

      <div class="flex justify-center mb-5">
        <div class="verify-wrap">
          <button class="verify-btn" onclick="openVerifyModal()">
            <i class="bi bi-patch-check-fill" style="font-size:1.1rem;"></i> Verify Authenticity
          </button>
        </div>
      </div>
      <p class="text-center muted" style="font-size:.7rem;">Secured by PharmaTrack · Scan recorded {{ now()->format('d M Y, H:i') }}</p>
    </div>

    <!-- ══ IMAGES ══ -->
    <div class="tab-panel p-6" id="tab-images">
      @if($images->count())
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-bold" style="font-size:1rem;">Product Images</h3>
            <p class="muted" style="font-size:.72rem; margin-top:2px;">Official authenticated visuals — tap to preview</p>
          </div>
          <span class="badge accent">{{ $images->count() }} Photo{{ $images->count()>1?'s':'' }}</span>
        </div>
        <div class="card mb-4 relative overflow-hidden flex items-center justify-center" style="height:260px;">
          <img id="mainPreviewImg" src="{{ $images->first()->url }}" alt="" style="width:100%;height:100%;object-fit:contain;background:var(--bg-mid);">
          <div class="absolute top-3 right-3"><span class="badge success"><i class="bi bi-patch-check-fill"></i>Authenticated</span></div>
          <div class="scan-line absolute"></div>
        </div>
        <div class="grid grid-cols-4 gap-3">
          @foreach($images as $i => $img)
            <div class="img-thumb {{ $i===0?'selected':'' }}" onclick="selectThumb(this,'{{ $img->url }}')">
              <img src="{{ $img->url }}" alt="{{ $img->original_name }}">
            </div>
          @endforeach
        </div>
      @else
        <div class="text-center py-12">
          <div class="w-20 h-20 mx-auto mb-4 rounded-2xl flex items-center justify-center" style="background:var(--bg-mid); border:1px solid var(--border);">
            <i class="bi bi-image muted" style="font-size:2.2rem;"></i>
          </div>
          <h3 class="font-bold" style="font-size:1rem;">No Images Available</h3>
          <p class="muted" style="font-size:.78rem; margin-top:4px;">No official product images have been published for this item yet.</p>
        </div>
      @endif
    </div>

    <!-- ══ TRACKING ══ -->
    <div class="tab-panel p-6" id="tab-tracking">
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="font-bold" style="font-size:1rem;">Verification History</h3>
          <p class="muted" style="font-size:.72rem; margin-top:2px;">Recorded journey & scan log for this unit</p>
        </div>
        <div class="flex items-center gap-2 rounded-xl px-3 py-1.5" style="background:var(--success-bg); border:1px solid var(--success-border);">
          <span class="w-2 h-2 rounded-full inline-block" style="background:var(--success);"></span>
          <span class="success-text font-bold" style="font-size:.72rem;">Tracked</span>
        </div>
      </div>

      <!-- Product journey lifecycle -->
      <div class="card p-4 mb-5">
        <p class="font-extrabold muted mb-4" style="font-size:.65rem; letter-spacing:.12em; text-transform:uppercase;">Product Journey</p>
        <div class="flex items-center justify-between" style="gap:4px;">
          @foreach($journey as $i => $stage)
            <div class="flex flex-col items-center text-center" style="flex:1; min-width:0;">
              <div class="rounded-full flex items-center justify-center mb-2" style="width:38px;height:38px;
                   background:{{ $stage[2] ? 'var(--accent)' : 'var(--bg-mid)' }};
                   border:2px solid {{ $stage[2] ? 'var(--accent)' : 'var(--border)' }};
                   color:{{ $stage[2] ? '#fff' : 'var(--text-muted)' }};
                   box-shadow:{{ $stage[2] ? '0 0 10px var(--accent-ring)' : 'none' }};">
                <i class="bi {{ $stage[1] }}" style="font-size:1rem;"></i>
              </div>
              <p style="font-size:.62rem; font-weight:700; color:{{ $stage[2] ? 'var(--text)' : 'var(--text-muted)' }};">{{ $stage[0] }}</p>
            </div>
            @if(!$loop->last)
              <div style="height:2px; flex:.5; background:{{ $journey[$i+1][2] ?? false ? 'var(--accent)' : 'var(--border)' }}; margin-bottom:18px; border-radius:2px;"></div>
            @endif
          @endforeach
        </div>
      </div>

      <!-- Verification information -->
      @if($verification)
      <div class="card p-4 mb-5">
        <p class="font-extrabold muted mb-1" style="font-size:.65rem; letter-spacing:.12em; text-transform:uppercase;">Verification Information</p>
        <div class="info-row"><span class="info-label">Verification ID</span><span class="info-value mono accent-text">{{ $verification->verification_number }}</span></div>
        <div class="info-row"><span class="info-label">Verified At</span><span class="info-value">{{ $verification->created_at?->format('d M Y, h:i A') }}</span></div>
        <div class="info-row"><span class="info-label">Total Scans</span><span class="info-value">{{ number_format($scanStats['count']) }}</span></div>
        <div class="info-row"><span class="info-label">First Verified</span><span class="info-value">{{ optional($scanStats['first'])->format('d M Y') ?? '—' }}</span></div>
        <div class="info-row"><span class="info-label">Last Verified</span><span class="info-value">{{ optional($scanStats['last'])->format('d M Y, h:i A') ?? '—' }}</span></div>
        <div class="info-row"><span class="info-label">Country</span><span class="info-value">{{ $verification->country ?? 'Unknown' }}{{ $verification->city ? ' · '.$verification->city : '' }}</span></div>
        @if($verification->is_proxy)<div class="info-row"><span class="info-label">Network</span><span class="info-value gold-text"><i class="bi bi-shield-exclamation me-1"></i>VPN / Proxy detected</span></div>@endif
      </div>
      @endif

      <!-- summary -->
      <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="stat-pill"><p class="font-extrabold accent-text" style="font-size:1.5rem;">{{ $logs->count() }}</p><p class="muted" style="font-size:.66rem; margin-top:2px;">Events</p></div>
        <div class="stat-pill"><p class="font-extrabold success-text" style="font-size:1.5rem;">{{ $logs->where('event','scanned')->count() }}</p><p class="muted" style="font-size:.66rem; margin-top:2px;">Scans</p></div>
        @php $flagCount = $verification ? $verification->alerts()->count() : (($blocked||$expired)?1:0); @endphp
        <div class="stat-pill"><p class="font-extrabold {{ $flagCount?'danger-text':'success-text' }}" style="font-size:1.5rem;">{{ $flagCount }}</p><p class="muted" style="font-size:.66rem; margin-top:2px;">Flags</p></div>
      </div>

      <div class="flex items-center gap-2 mb-4">
        <i class="bi bi-clock-history accent-text"></i>
        <p class="accent-text font-extrabold" style="font-size:.67rem; letter-spacing:.12em; text-transform:uppercase;">Journey Log</p>
      </div>

      @if($logs->count())
        <div>
          @foreach($logs as $log)
            @php
              $map = [
                'scanned'         => ['bi-qr-code-scan','Scanned / Verified'],
                'units_generated' => ['bi-plus-circle','Units Generated'],
                'units_removed'   => ['bi-dash-circle','Units Removed'],
                'printed'         => ['bi-printer','Label Printed'],
                'packed'          => ['bi-box-seam','Packed'],
                'dispatched'      => ['bi-truck','Dispatched'],
                'received'        => ['bi-box-arrow-in-down','Received'],
              ];
              $ev = $map[$log->event] ?? ['bi-dot', ucfirst(str_replace('_',' ',$log->event))];
            @endphp
            <div class="tl-item">
              <div class="tl-dot {{ $log->event==='scanned' ? '' : 'dim' }}"><i class="bi {{ $ev[0] }}"></i></div>
              <div class="card p-3">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                  <p class="font-bold" style="font-size:.82rem;">{{ $ev[1] }}</p>
                  <p class="mono muted" style="font-size:.66rem;">{{ $log->created_at?->format('d M Y, H:i') }}</p>
                </div>
                @if($log->note)<p class="muted" style="font-size:.72rem; margin-top:4px;">{{ $log->note }}</p>@endif
                <p class="soft" style="font-size:.66rem; margin-top:4px;"><i class="bi bi-person-circle me-1"></i>{{ ucfirst($log->performed_by ?? 'system') }}</p>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <p class="muted text-center py-6" style="font-size:.8rem;">No history recorded yet.</p>
      @endif
    </div>
  </div>

  <!-- ═══ VERIFY MODAL ═══ -->
  <div class="modal-overlay" id="verifyModal" onclick="closeBg(event)">
    <div class="modal-box">
      <div id="modalVerifying" class="text-center">
        <div class="w-16 h-16 mx-auto mb-5 rounded-full flex items-center justify-center" style="background:var(--accent-glow); border:2px solid var(--accent-ring);">
          <i class="bi bi-arrow-repeat spin" style="color:var(--accent); font-size:2rem;"></i>
        </div>
        <h3 class="font-extrabold mb-1" style="font-size:1.15rem;">Verifying Product</h3>
        <p class="muted" style="font-size:.82rem;" id="progressText">Checking secure records…</p>
        <div class="mt-5 rounded-full overflow-hidden" style="height:6px; background:var(--border);">
          <div id="progressBar" class="h-full rounded-full" style="background:linear-gradient(90deg,var(--accent),#60A5FA); width:0%; transition:width .3s;"></div>
        </div>
        <p class="mono muted mt-2" id="progressPct" style="font-size:.7rem;">0%</p>
      </div>
      <div id="modalResult" class="text-center hidden">
        <div class="w-16 h-16 mx-auto mb-5 rounded-full flex items-center justify-center"
             style="background:var(--{{ $unitOk ? 'success' : 'danger' }}-bg); border:2px solid var(--{{ $unitOk ? 'success' : 'danger' }}-border);">
          <i class="bi {{ $unitOk ? 'bi-check-lg success-text' : 'bi-exclamation-triangle danger-text' }}" style="font-size:2rem;"></i>
        </div>
        <span class="badge {{ $unitOk ? 'success' : 'danger' }} mb-3">{{ $unitOk ? 'Authentic Product' : 'Verification Warning' }}</span>
        <h3 class="font-extrabold mb-1" style="font-size:1.2rem;">{{ $unitOk ? 'Verification Passed' : ($expired ? 'Product Expired' : 'Not Valid for Sale') }}</h3>
        <p class="muted mb-5" style="font-size:.8rem;">
          {{ $unitOk
              ? 'This unit matches our secure records and is recorded as genuine.'
              : ($expired ? 'This product has passed its expiry date and should not be used.' : 'This unit is marked '.$unit->status.' and should not be sold.') }}
        </p>
        <div class="card p-4 text-left mb-5">
          <div class="info-row" style="padding:10px 0;"><span class="info-label" style="font-size:.68rem;">Code Match</span><span class="font-bold success-text">✓ Passed</span></div>
          <div class="info-row" style="padding:10px 0;"><span class="info-label" style="font-size:.68rem;">Unit Status</span><span class="font-bold {{ $blocked?'danger-text':'success-text' }}">{{ ucfirst($unit->status) }}</span></div>
          <div class="info-row" style="padding:10px 0; border:none;"><span class="info-label" style="font-size:.68rem;">Expiry Check</span><span class="font-bold {{ $expired?'danger-text':'success-text' }}">{{ $expired ? 'Failed' : 'Passed' }}</span></div>
        </div>
        <button onclick="closeModal()" class="w-full py-3.5 rounded-2xl font-extrabold text-white"
          style="font-size:.82rem; letter-spacing:.08em; text-transform:uppercase; background:linear-gradient(135deg,#3B82F6,#1D4ED8); box-shadow:0 0 20px rgba(59,130,246,0.35);">Close</button>
      </div>
    </div>
  </div>

@else
  <!-- ═══ NOT RECOGNISED ═══ -->
  <div class="max-w-md mx-auto" style="margin-top:8vh;">
    <div class="flex items-center justify-end mb-4">
      <div class="flex items-center gap-2">
        <span class="muted" style="font-size:.7rem; text-transform:uppercase;"><span id="themeLabel">Dark</span></span>
        <div class="toggle-track on" id="themeToggle" onclick="toggleTheme()"><div class="toggle-thumb">🌙</div></div>
      </div>
    </div>
    <div class="card p-8 text-center" style="border-color:var(--danger-border);">
      <div class="w-20 h-20 mx-auto mb-5 rounded-full flex items-center justify-center" style="background:var(--danger-bg); border:2px solid var(--danger-border);">
        <i class="bi bi-exclamation-octagon-fill danger-text" style="font-size:2.4rem;"></i>
      </div>
      <span class="badge danger mb-3">Not Recognised</span>
      <h2 class="font-extrabold mb-2" style="font-size:1.3rem;">Code Not Found</h2>
      <p class="muted mb-4" style="font-size:.85rem; line-height:1.6;">
        The code <span class="mono accent-text">{{ $code }}</span> does not match any product in our system.
        This item may be <strong class="danger-text">counterfeit</strong> — do not use it and report it to your supplier.
      </p>
      <div class="rounded-xl p-3" style="background:var(--bg-mid); border:1px solid var(--border-soft);">
        <p class="muted" style="font-size:.72rem;"><i class="bi bi-shield-exclamation me-1"></i>Always buy from authorised pharmacies and verify the QR code on the pack.</p>
      </div>
    </div>
    <p class="text-center muted mt-4" style="font-size:.7rem;">Secured by PharmaTrack</p>
  </div>
@endif

  <script>
    /* THEME */
    (function(){ try{ var t=localStorage.getItem('verifyTheme'); if(t) applyTheme(t); }catch(e){} })();
    function applyTheme(theme){
      document.documentElement.setAttribute('data-theme', theme);
      var track=document.getElementById('themeToggle'); if(!track) return;
      var label=document.getElementById('themeLabel'), thumb=track.querySelector('.toggle-thumb');
      if(theme==='dark'){ track.classList.add('on'); thumb.textContent='🌙'; if(label) label.textContent='Dark'; }
      else { track.classList.remove('on'); thumb.textContent='☀️'; if(label) label.textContent='Light'; }
    }
    function toggleTheme(){
      var isDark=document.documentElement.getAttribute('data-theme')==='dark';
      var next=isDark?'light':'dark'; applyTheme(next);
      try{ localStorage.setItem('verifyTheme', next); }catch(e){}
    }

    /* TABS */
    function switchTab(name, btn){
      document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      document.getElementById('tab-'+name).classList.add('active');
      btn.classList.add('active');
    }

    /* IMAGES */
    function selectThumb(el, url){
      document.querySelectorAll('.img-thumb').forEach(t=>t.classList.remove('selected'));
      el.classList.add('selected');
      var img=document.getElementById('mainPreviewImg'); if(img) img.src=url;
    }

    /* VERIFY MODAL */
    function openVerifyModal(){
      var m=document.getElementById('verifyModal'); if(!m) return;
      document.getElementById('modalVerifying').classList.remove('hidden');
      document.getElementById('modalResult').classList.add('hidden');
      var bar=document.getElementById('progressBar'), pct=document.getElementById('progressPct'), txt=document.getElementById('progressText');
      bar.style.width='0%'; pct.textContent='0%';
      m.classList.add('open');
      var steps=[['Checking secure records…',30],['Matching unit serial…',65],['Confirming expiry & status…',100]];
      var i=0;
      var iv=setInterval(function(){
        if(i<steps.length){ txt.textContent=steps[i][0]; bar.style.width=steps[i][1]+'%'; pct.textContent=steps[i][1]+'%'; i++; }
        else { clearInterval(iv); setTimeout(function(){
          document.getElementById('modalVerifying').classList.add('hidden');
          document.getElementById('modalResult').classList.remove('hidden');
        },350); }
      },520);
    }
    function closeModal(){ document.getElementById('verifyModal').classList.remove('open'); }
    function closeBg(e){ if(e.target.id==='verifyModal') closeModal(); }
  </script>
</body>
</html>
