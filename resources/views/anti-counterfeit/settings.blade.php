@extends('layouts.app')
@section('title', 'Verification Settings')

@section('content')
<div class="page-header">
  <div>
    <h1>Verification Page Settings</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Settings</div>
  </div>
  <a href="{{ route('verify', 'PREVIEW') }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Preview page</a>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

{{-- Templates & reset (separate from the main form) --}}
<div class="card mb-3"><div class="card-body">
  <div class="d-flex align-items-center mb-2">
    <h6 class="fw-bold mb-0"><i class="bi bi-palette2 me-1 text-primary"></i>Quick Templates</h6>
    <form method="POST" action="{{ route('anticounterfeit.verification-page.reset') }}" class="ms-auto" onsubmit="return confirm('Restore all settings to the default design? Uploaded logo &amp; background image will be removed.');">
      @csrf
      <button class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset to default</button>
    </form>
  </div>
  <p class="text-muted-sm mb-3">One-click colour themes. Applies the primary, button &amp; footer colours and the verify-button position — your texts, logo, fields and messages are kept.</p>
  <div class="row g-2">
    @foreach(\App\Models\Setting::templates() as $key => $tpl)
      @php $v = $tpl['values']; @endphp
      <div class="col-6 col-md-4 col-lg-3">
        <form method="POST" action="{{ route('anticounterfeit.verification-page.template') }}">
          @csrf
          <input type="hidden" name="template" value="{{ $key }}">
          <button class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center gap-2 text-start py-2">
            <span class="d-inline-flex rounded overflow-hidden border" style="flex:0 0 auto">
              <span style="width:16px;height:22px;background:{{ $v['primary'] }}"></span>
              <span style="width:16px;height:22px;background:{{ $v['button_color'] }}"></span>
            </span>
            <span class="small">{{ $tpl['label'] }}</span>
          </button>
        </form>
      </div>
    @endforeach
  </div>
</div></div>

<div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>Everything below controls how the public <strong>/verify</strong> page looks and behaves — brand, colour, texts, which product fields show, the two forms, and every restriction message.</div>

<form method="POST" action="{{ route('anticounterfeit.verification-page.update') }}" enctype="multipart/form-data">
  @csrf
  <div class="row g-3">
    {{-- Branding --}}
    <div class="col-lg-6">
      <div class="card h-100"><div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-palette me-1 text-primary"></i>Branding</h6>
        <label class="form-label">Page style</label>
        <select name="verify_style" class="form-select form-select-sm mb-3">
          <option value="style1" @selected(($cfg['verify_style'] ?? 'style1')==='style1')>Style 1 — App (gradient hero + bottom tabs)</option>
          <option value="style2" @selected(($cfg['verify_style'] ?? 'style1')==='style2')>Style 2 — Certificate (single scroll)</option>
          <option value="style3" @selected(($cfg['verify_style'] ?? 'style1')==='style3')>Style 3 — Beacon (premium glass + code panel)</option>
          <option value="style4" @selected(($cfg['verify_style'] ?? 'style1')==='style4')>Style 4 — Aurora (beautiful header + footer tabs)</option>
          <option value="style5" @selected(($cfg['verify_style'] ?? 'style1')==='style5')>Style 5 — Beacon Pro (glass + footer tabs)</option>
        </select>
        <label class="form-label">Brand name</label>
        <input type="text" name="brand_name" class="form-control form-control-sm mb-3" value="{{ $cfg['brand_name'] }}" required>

        <div class="row g-2 mb-3">
          <div class="col-4">
            <label class="form-label">Primary colour</label>
            <input type="color" name="primary" class="form-control form-control-color w-100" value="{{ $cfg['primary'] }}" title="Genuine hero &amp; accents">
          </div>
          <div class="col-4">
            <label class="form-label">Button colour</label>
            <input type="color" name="button_color" class="form-control form-control-color w-100" value="{{ $cfg['button_color'] }}" title="All primary buttons">
          </div>
          <div class="col-4">
            <label class="form-label">Footer colour</label>
            <input type="color" name="footer_color" class="form-control form-control-color w-100" value="{{ $cfg['footer_color'] }}" title="Footer text">
          </div>
        </div>
        <p class="text-muted-sm mb-3">Primary drives the genuine hero &amp; accents. Button colour applies to all primary action buttons. Footer colour styles the footer text.</p>

        <label class="form-label">Logo</label>
        <div class="d-flex align-items-center gap-3">
          <div style="width:56px;height:56px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;overflow:hidden">
            @if($cfg['logo_path'])<img src="{{ asset('storage/'.$cfg['logo_path']) }}" style="width:100%;height:100%;object-fit:contain">@else<i class="bi bi-image text-muted"></i>@endif
          </div>
          <div>
            <input type="file" name="logo" accept="image/*" class="form-control form-control-sm">
            @if($cfg['logo_path'])<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rmLogo"><label class="form-check-label small" for="rmLogo">Remove current logo</label></div>@endif
          </div>
        </div>

        <label class="form-label mt-3">Background image <span class="text-muted-sm">(shown behind the verify page)</span></label>
        <div class="d-flex align-items-center gap-3">
          <div style="width:56px;height:56px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;overflow:hidden">
            @if($cfg['bg_image'])<img src="{{ asset('storage/'.$cfg['bg_image']) }}" style="width:100%;height:100%;object-fit:cover">@else<i class="bi bi-image text-muted"></i>@endif
          </div>
          <div>
            <input type="file" name="bg_image" accept="image/*" class="form-control form-control-sm">
            @if($cfg['bg_image'])<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_bg_image" value="1" id="rmBg"><label class="form-check-label small" for="rmBg">Remove background image</label></div>@endif
          </div>
        </div>
      </div></div>
    </div>

    {{-- Forms + texts --}}
    <div class="col-lg-6">
      <div class="card h-100"><div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-toggles me-1 text-primary"></i>Forms &amp; Texts</h6>
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="report_form" value="0">
          <input class="form-check-input" type="checkbox" name="report_form" value="1" id="rf" @checked($cfg['report_form'])>
          <label class="form-check-label" for="rf">Enable “Report this product” form</label>
        </div>
        <div class="form-check form-switch mb-3">
          <input type="hidden" name="info_form" value="0">
          <input class="form-check-input" type="checkbox" name="info_form" value="1" id="if" @checked($cfg['info_form'])>
          <label class="form-check-label" for="if">Enable “Get product details by email” form</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="verify_button" value="0">
          <input class="form-check-input" type="checkbox" name="verify_button" value="1" id="vb" @checked($cfg['verify_button'])>
          <label class="form-check-label" for="vb">Show “Verify” button on the genuine page</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="show_product_photo" value="0">
          <input class="form-check-input" type="checkbox" name="show_product_photo" value="1" id="spp" @checked($cfg['show_product_photo'])>
          <label class="form-check-label" for="spp">Show product photo in the verified header</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="verify_code_panel" value="0">
          <input class="form-check-input" type="checkbox" name="verify_code_panel" value="1" id="vcp" @checked($cfg['verify_code_panel'])>
          <label class="form-check-label" for="vcp">Show “Verify Authenticity” code panel <span class="text-muted-sm">(Style 3)</span></label>
        </div>
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="show_journey" value="0">
          <input class="form-check-input" type="checkbox" name="show_journey" value="1" id="sj" @checked($cfg['show_journey'])>
          <label class="form-check-label" for="sj">Show “Supply Journey” timeline</label>
        </div>
        <div class="mb-3">
          <label class="form-label">History events to show</label>
          @php $hl = (string) ($cfg['history_limit'] ?? 10); @endphp
          <select name="history_limit" class="form-select form-select-sm">
            <option value="5"  @selected($hl==='5')>Last 5</option>
            <option value="10" @selected($hl==='10')>Last 10</option>
            <option value="20" @selected($hl==='20')>Last 20</option>
            <option value="50" @selected($hl==='50')>Last 50</option>
            <option value="0"  @selected($hl==='0')>All</option>
          </select>
        </div>
        <label class="form-label">Verification info to show</label>
        <div class="row g-1 mb-3">
          <div class="col-6"><div class="form-check form-switch">
            <input type="hidden" name="show_country" value="0">
            <input class="form-check-input" type="checkbox" name="show_country" value="1" id="sc" @checked($cfg['show_country'])>
            <label class="form-check-label small" for="sc">Country</label>
          </div></div>
          <div class="col-6"><div class="form-check form-switch">
            <input type="hidden" name="show_city" value="0">
            <input class="form-check-input" type="checkbox" name="show_city" value="1" id="sci" @checked($cfg['show_city'])>
            <label class="form-check-label small" for="sci">City</label>
          </div></div>
          <div class="col-6"><div class="form-check form-switch">
            <input type="hidden" name="show_ip" value="0">
            <input class="form-check-input" type="checkbox" name="show_ip" value="1" id="sip" @checked($cfg['show_ip'])>
            <label class="form-check-label small" for="sip">IP address</label>
          </div></div>
          <div class="col-6"><div class="form-check form-switch">
            <input type="hidden" name="show_device" value="0">
            <input class="form-check-input" type="checkbox" name="show_device" value="1" id="sdev" @checked($cfg['show_device'])>
            <label class="form-check-label small" for="sdev">Device</label>
          </div></div>
        </div>
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="show_verification" value="0">
          <input class="form-check-input" type="checkbox" name="show_verification" value="1" id="sver" @checked($cfg['show_verification'])>
          <label class="form-check-label" for="sver">Show “Verification” info section</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="show_leaflet" value="0">
          <input class="form-check-input" type="checkbox" name="show_leaflet" value="1" id="slf" @checked($cfg['show_leaflet'])>
          <label class="form-check-label" for="slf">Show insert / leaflet download <span class="text-muted-sm">(in Images)</span></label>
        </div>
        <p class="text-muted-sm mb-2">Uses each <strong>product’s own PDF</strong> (Product Master → PDF). The file below is only a fallback for products without one.</p>
        <label class="form-label">Leaflet button text</label>
        <input type="text" name="leaflet_label" class="form-control form-control-sm mb-2" value="{{ $cfg['leaflet_label'] }}" maxlength="40" placeholder="Download insert / leaflet">
        <label class="form-label">Fallback leaflet file <span class="text-muted-sm">(PDF / DOC)</span></label>
        <input type="file" name="leaflet" accept=".pdf,.doc,.docx" class="form-control form-control-sm">
        @if($cfg['leaflet_path'])
          <div class="d-flex align-items-center gap-2 mt-1">
            <a href="{{ asset('storage/'.$cfg['leaflet_path']) }}" target="_blank" class="small"><i class="bi bi-file-earmark-arrow-down me-1"></i>Current file</a>
            <div class="form-check ms-2"><input class="form-check-input" type="checkbox" name="remove_leaflet" value="1" id="rmLf"><label class="form-check-label small" for="rmLf">Remove</label></div>
          </div>
        @endif
        <div class="row g-2 mb-3">
          <div class="col-7">
            <label class="form-label">Verify button text</label>
            <input type="text" name="verify_button_text" class="form-control form-control-sm" value="{{ $cfg['verify_button_text'] }}" maxlength="40" placeholder="Verify authenticity">
          </div>
          <div class="col-5">
            <label class="form-label">Position</label>
            <select name="verify_button_pos" class="form-select form-select-sm">
              <option value="hero" @selected($cfg['verify_button_pos']==='hero')>In hero</option>
              <option value="card" @selected($cfg['verify_button_pos']==='card')>Below product card</option>
            </select>
          </div>
        </div>
        <label class="form-label">Genuine title</label>
        <input type="text" name="genuine_title" class="form-control form-control-sm mb-2" value="{{ $cfg['genuine_title'] }}" required>
        <label class="form-label">Genuine subtitle <span class="text-muted-sm">(blank = product generic name)</span></label>
        <input type="text" name="genuine_subtitle" class="form-control form-control-sm mb-2" value="{{ $cfg['genuine_subtitle'] }}">
        <label class="form-label">Footer text</label>
        <input type="text" name="footer" class="form-control form-control-sm" value="{{ $cfg['footer'] }}">
      </div></div>
    </div>

    {{-- Loading screen --}}
    <div class="col-12">
      <div class="card"><div class="card-body"
           x-data="{ on: {{ ($cfg['loader_enabled'] ?? true) ? 'true' : 'false' }}, fx: '{{ $cfg['loader_style'] ?? 'spinner' }}' }">
        <h6 class="fw-bold mb-1"><i class="bi bi-hourglass-split me-1 text-primary"></i>Loading Screen</h6>
        <p class="text-muted-sm mb-3">A short branded loading effect shown while the verification page opens. Effect colour follows your brand colour. Choose a built-in effect or upload your own SVG / animated GIF.</p>
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Enable</label>
            <div class="form-check form-switch">
              <input type="hidden" name="loader_enabled" value="0">
              <input class="form-check-input" type="checkbox" name="loader_enabled" value="1" id="ldr" x-model="on" @checked($cfg['loader_enabled'] ?? true)>
              <label class="form-check-label small" for="ldr">Show loader</label>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Effect</label>
            @php $ls = $cfg['loader_style'] ?? 'spinner'; @endphp
            <select name="loader_style" class="form-select form-select-sm" x-model="fx" :disabled="!on">
              <optgroup label="Built-in">
                <option value="spinner" @selected($ls==='spinner')>Spinner (default)</option>
                <option value="dots"    @selected($ls==='dots')>Bouncing dots</option>
                <option value="pulse"   @selected($ls==='pulse')>Pulse</option>
                <option value="ring"    @selected($ls==='ring')>Dual ring</option>
                <option value="bars"    @selected($ls==='bars')>Equalizer bars</option>
                <option value="grow"    @selected($ls==='grow')>Grow circle</option>
                <option value="orbit"   @selected($ls==='orbit')>Orbit</option>
                <option value="flip"    @selected($ls==='flip')>Flipping square</option>
                <option value="wave"    @selected($ls==='wave')>Wave bars</option>
              </optgroup>
              <optgroup label="Custom">
                <option value="image" @selected($ls==='image')>Custom image (SVG / GIF / PNG)</option>
              </optgroup>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Minimum show (ms)</label>
            <input type="number" min="0" max="5000" step="100" name="loader_min_ms" class="form-control form-control-sm" value="{{ $cfg['loader_min_ms'] ?? 700 }}" :disabled="!on">
          </div>
          <div class="col-md-3">
            <label class="form-label">Loading text <span class="text-muted-sm">(blank = none)</span></label>
            <input type="text" name="loader_text" class="form-control form-control-sm" value="{{ $cfg['loader_text'] ?? '' }}" placeholder="Verifying authenticity…" maxlength="60" :disabled="!on">
          </div>

          {{-- Custom image upload (only for the "Custom image" effect) --}}
          <div class="col-12" x-show="fx === 'image'" x-cloak>
            <div class="border rounded p-3 bg-light">
              <label class="form-label">Custom loader image <span class="text-muted-sm">(SVG, animated GIF, PNG · max 1 MB)</span></label>
              <div class="d-flex align-items-center gap-3">
                @if(!empty($cfg['loader_image']))
                  <img src="{{ asset('storage/'.$cfg['loader_image']) }}" alt="loader" style="height:48px;width:48px;object-fit:contain;background:#fff;border:1px solid #e2e8f0;border-radius:8px">
                @endif
                <input type="file" name="loader_image" accept=".svg,.png,.gif,.jpg,.jpeg,.webp,image/svg+xml,image/png,image/gif" class="form-control form-control-sm" :disabled="!on">
              </div>
              @if(!empty($cfg['loader_image']))
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="remove_loader_image" value="1" id="rmldrimg" :disabled="!on">
                  <label class="form-check-label small" for="rmldrimg">Remove current image</label>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div></div>
    </div>

    {{-- Product fields to show --}}
    <div class="col-12">
      <div class="card"><div class="card-body">
        <h6 class="fw-bold mb-1"><i class="bi bi-check2-square me-1 text-primary"></i>Product Info Fields</h6>
        <p class="text-muted-sm mb-3">Tick which fields appear on the genuine page.</p>
        <div class="row g-2">
          @foreach(\App\Models\Setting::fieldLabels() as $key => $label)
            <div class="col-6 col-md-4 col-lg-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="fields[]" value="{{ $key }}" id="f_{{ $key }}" @checked(in_array($key, $cfg['fields']))>
                <label class="form-check-label" for="f_{{ $key }}">{{ $label }}</label>
              </div>
            </div>
          @endforeach
        </div>
      </div></div>
    </div>

    {{-- Highlight card (the two slots beside the product image) --}}
    <div class="col-12">
      <div class="card"><div class="card-body">
        <h6 class="fw-bold mb-1"><i class="bi bi-card-heading me-1 text-primary"></i>Highlight Card</h6>
        <p class="text-muted-sm mb-3">The card beside the product image shows two slots. Set a label and pick what data each shows — or hide it.</p>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Left slot label</label>
            <input type="text" name="card_left_label" class="form-control form-control-sm mb-2" value="{{ $cfg['card_left_label'] }}" maxlength="30" placeholder="e.g. Batch">
            <label class="form-label">Left slot data</label>
            <select name="card_left_field" class="form-select form-select-sm">
              @foreach(\App\Models\Setting::cardFieldOptions() as $k => $label)
                <option value="{{ $k }}" @selected(($cfg['card_left_field'] ?? '')===$k)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Right slot label</label>
            <input type="text" name="card_right_label" class="form-control form-control-sm mb-2" value="{{ $cfg['card_right_label'] }}" maxlength="30" placeholder="e.g. Expires in">
            <label class="form-label">Right slot data</label>
            <select name="card_right_field" class="form-select form-select-sm">
              @foreach(\App\Models\Setting::cardFieldOptions() as $k => $label)
                <option value="{{ $k }}" @selected(($cfg['card_right_field'] ?? '')===$k)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div></div>
    </div>

    {{-- Messages --}}
    <div class="col-12">
      <div class="card"><div class="card-body">
        <h6 class="fw-bold mb-1"><i class="bi bi-chat-left-text me-1 text-primary"></i>Restriction Messages</h6>
        <p class="text-muted-sm mb-3">Customise the title &amp; message shown for each block reason.</p>
        @php $labels = ['fake'=>'Fake / Not recognised','recalled'=>'Recalled','locked'=>'Locked','expired'=>'Expired','invalid'=>'Invalid','country'=>'Wrong country','city'=>'Wrong city','device'=>'Device limit','hit'=>'Scan / hit limit']; @endphp
        @foreach($labels as $key => $label)
          <div class="row g-2 align-items-center mb-2">
            <div class="col-md-2"><span class="badge bg-light text-dark w-100 text-start py-2">{{ $label }}</span></div>
            <div class="col-md-4"><input type="text" name="messages[{{ $key }}][title]" class="form-control form-control-sm" value="{{ $cfg['messages'][$key]['title'] ?? '' }}" placeholder="Title"></div>
            <div class="col-md-6"><input type="text" name="messages[{{ $key }}][text]" class="form-control form-control-sm" value="{{ $cfg['messages'][$key]['text'] ?? '' }}" placeholder="Message"></div>
          </div>
        @endforeach
      </div></div>
    </div>
  </div>

  <div class="mt-3 d-flex">
    <button class="btn btn-primary ms-auto"><i class="bi bi-save me-1"></i>Save Settings</button>
  </div>
</form>

{{-- Reference (read-only) --}}
<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-speedometer me-1 text-primary"></i>Risk Score Bands</h6>
      <table class="table table-sm mb-0">
        <tbody>
          <tr><td>0 – 40</td><td><span class="badge-status badge-approved">Safe / Low</span></td></tr>
          <tr><td>41 – 60</td><td><span class="badge-status badge-pending">Medium</span></td></tr>
          <tr><td>61 – 100</td><td><span class="badge-status badge-cancelled">High / Critical</span></td></tr>
        </tbody>
      </table>
      <p class="text-muted-sm mt-2 mb-0">A verification scoring 41+ is flagged <strong>Suspicious</strong>.</p>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h6 class="fw-bold mb-2"><i class="bi bi-hdd-network me-1 text-primary"></i>Geo-IP Provider</h6>
      <p class="text-muted-sm mb-0">Geo-location, ISP and VPN/proxy detection use the free <span class="font-monospace">ip-api.com</span> service (cached 1 hour per IP). Local/private IPs are skipped.</p>
    </div></div>
  </div>
</div>
@endsection
