@extends('layouts.app')
@section('title', 'Access Control')

@push('styles')
<style>
  [x-cloak]{display:none!important}
  /* Dual-list country picker */
  .cp-label{font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6c757d;margin-bottom:4px}
  .cp-list{border:1px solid #dee2e6;border-radius:8px;height:168px;overflow-y:auto;background:#fff}
  .cp-item{display:flex;align-items:center;gap:8px;padding:7px 10px;font-size:13px;cursor:pointer;border-bottom:1px solid #f1f3f5}
  .cp-item:last-child{border-bottom:0}
  .cp-item:hover{background:rgba(13,110,253,.07)}
  .cp-item span{flex:1}
  .cp-arrow{font-size:13px;color:#adb5bd}
  .cp-empty{padding:12px;color:#9aa4b2;font-size:12px;text-align:center}
  html[data-theme="dark"] .cp-list{background:var(--dm-card,#1b2230);border-color:var(--dm-border,#2c3444)}
  html[data-theme="dark"] .cp-item{border-bottom-color:var(--dm-border,#2c3444)}
  html[data-theme="dark"] .cp-item:hover{background:rgba(110,168,254,.12)}
  .ac-shell{display:flex;flex-wrap:wrap}
  .ac-nav{width:240px;flex-shrink:0;border-right:1px solid var(--dm-border,#e9ecef)}
  .ac-nav-label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9aa4b2;padding:14px 16px 6px}
  .ac-nav-item{display:flex;align-items:center;gap:10px;padding:11px 16px;font-size:14px;font-weight:500;color:#6c757d;cursor:pointer;border:0;border-left:3px solid transparent;width:100%;background:none;text-align:left;text-decoration:none;transition:background .12s,color .12s}
  .ac-nav-item i{font-size:16px}
  .ac-nav-item:hover{background:rgba(13,110,253,.05);color:#0d6efd}
  .ac-nav-item.active{background:rgba(13,110,253,.09);color:#0d6efd;border-left-color:#0d6efd;font-weight:600}
  .ac-body{flex:1;min-width:0;padding:22px}
  .scope-card{border:1.5px solid #e9ecef;border-radius:12px;padding:14px;cursor:pointer;transition:border-color .12s,background .12s;height:100%}
  .scope-card:hover{border-color:#9ec5fe}
  .scope-card.active{border-color:#0d6efd;background:rgba(13,110,253,.05)}
  .scope-card .bi{font-size:20px}
  .perm-box{border:1px solid #e9ecef;border-radius:12px;padding:16px;background:rgba(13,110,253,.02)}
  .ac-modal{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1080;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;overflow:auto}
  .ac-modal-box{background:#fff;border-radius:16px;max-width:720px;width:100%;box-shadow:0 24px 70px rgba(0,0,0,.35)}
  html[data-theme="dark"] .ac-nav{border-right-color:var(--dm-border)}
  html[data-theme="dark"] .scope-card,html[data-theme="dark"] .perm-box,html[data-theme="dark"] .ac-modal-box{border-color:var(--dm-border);background:var(--dm-surface-2)}
  html[data-theme="dark"] .ac-modal-box{background:var(--dm-surface)}
  @media(max-width:768px){
    .ac-nav{width:100%;border-right:0;border-bottom:1px solid #e9ecef;display:flex;overflow-x:auto}
    .ac-nav-label{display:none}
    .ac-nav-item{border-left:0;border-bottom:3px solid transparent;white-space:nowrap}
    .ac-nav-item.active{border-left:0;border-bottom-color:#0d6efd}
  }
</style>
@endpush

@section('content')
<div x-data="acPage(@js(request('ftab', 'pb')), @js($policyData))">
<div class="page-header">
  <div>
    <h1>Access Control &amp; Permissions</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Access Control</div>
  </div>
</div>

@if(session('success'))
  <div x-data x-init="$nextTick(() => $store.toast.show(@js(session('success')), 'success'))"></div>
@endif
@if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card">
  <div class="ac-shell">
    {{-- Inner sidebar (route-driven via ?ftab=) --}}
    @php $activeTab = request('ftab', 'pb'); @endphp
    <div class="ac-nav">
      <div class="ac-nav-label">Global</div>
      <a href="{{ route('anticounterfeit.policies', ['ftab' => 'global']) }}" class="ac-nav-item {{ $activeTab==='global' ? 'active' : '' }}"><i class="bi bi-globe2"></i> Scan &amp; Access Setting</a>
      <a href="{{ route('anticounterfeit.policies', ['ftab' => 'intel']) }}" class="ac-nav-item {{ $activeTab==='intel' ? 'active' : '' }}"><i class="bi bi-radar"></i> Scan Intelligence</a>
      <div class="ac-nav-label">Permissions</div>
      <a href="{{ route('anticounterfeit.policies', ['ftab' => 'pb']) }}" class="ac-nav-item {{ $activeTab==='pb' ? 'active' : '' }}"><i class="bi bi-box-seam"></i> Product / Batch</a>
      <a href="{{ route('anticounterfeit.policies', ['ftab' => 'customer']) }}" class="ac-nav-item {{ $activeTab==='customer' ? 'active' : '' }}"><i class="bi bi-people"></i> Customer</a>
      <div class="ac-nav-label">Manage</div>
      <a href="{{ route('anticounterfeit.policies', ['ftab' => 'lookup']) }}" class="ac-nav-item {{ $activeTab==='lookup' ? 'active' : '' }}"><i class="bi bi-search"></i> Find by UUC</a>
      <a href="{{ route('anticounterfeit.policies', ['ftab' => 'find']) }}" class="ac-nav-item {{ $activeTab==='find' ? 'active' : '' }}"><i class="bi bi-box-seam-fill"></i> Find by Product / Batch</a>
      <a href="{{ route('anticounterfeit.policies', ['ftab' => 'list']) }}" class="ac-nav-item {{ $activeTab==='list' ? 'active' : '' }}"><i class="bi bi-list-check"></i> Active Policies</a>
    </div>

    <div class="ac-body">

      {{-- ══ TAB 1: Product / Batch / Serial permission ══ --}}
      <div x-show="tab==='pb'">
        <h5 class="fw-bold mb-1">Product / Batch Permission</h5>
        <p class="text-muted-sm mb-3">Choose what this rule applies to, then set the permissions. Most specific rule wins (serials &gt; batch &gt; product).</p>
        <form method="POST" action="{{ route('anticounterfeit.policies.store') }}">
          @csrf
          <label class="form-label">Apply to <span class="text-danger">*</span></label>
          <input type="hidden" name="scope_type" :value="scope">
          <div class="row g-2 mb-3">
            <div class="col-md-4"><div class="scope-card" :class="scope==='product'?'active':''" @click="scope='product'"><i class="bi bi-capsule text-primary"></i><div class="fw-semibold mt-1">Whole Product</div><div class="text-muted-sm">All codes of a product</div></div></div>
            <div class="col-md-4"><div class="scope-card" :class="scope==='batch'?'active':''" @click="scope='batch'"><i class="bi bi-layers text-primary"></i><div class="fw-semibold mt-1">Whole Batch</div><div class="text-muted-sm">Every code in a batch</div></div></div>
            <div class="col-md-4"><div class="scope-card" :class="scope==='codes'?'active':''" @click="scope='codes'"><i class="bi bi-upc-scan text-primary"></i><div class="fw-semibold mt-1">Specific Serial(s)</div><div class="text-muted-sm">One or many UUC codes</div></div></div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Policy name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. BD-only / Lock lot 14" required></div>
            <div class="col-md-6" x-show="scope==='product'" x-cloak><label class="form-label">Product <span class="text-danger">*</span></label><select name="product_id" class="form-select form-select-sm"><option value="">Select product…</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach</select></div>
            <div class="col-md-6" x-show="scope==='batch'" x-cloak><label class="form-label">Batch <span class="text-danger">*</span></label><select name="batch_id" class="form-select form-select-sm"><option value="">Select batch…</option>@foreach($batches as $b)<option value="{{ $b->id }}">{{ $b->brn }} — {{ $b->product?->name }}</option>@endforeach</select></div>
            <div class="col-12" x-show="scope==='codes'" x-cloak><label class="form-label">UUC serial code(s) <span class="text-danger">*</span></label><textarea name="uuc_codes" rows="2" class="form-control form-control-sm" placeholder="HAK33NQJCP, QHFMC4PAUD, 3964752518 … (comma / space / newline separated)"></textarea></div>
          </div>
          <div class="perm-box mb-3">
            <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2">Permissions</div>
            <div class="row g-3 align-items-end">
              <div class="col-md-3"><label class="form-label">Verification</label><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="locked" value="1" id="lockSwitch"><label class="form-check-label small" for="lockSwitch"><i class="bi bi-lock me-1"></i>Locked</label></div></div>
              <div class="col-md-3"><label class="form-label">Scan / hit limit</label><input type="number" min="1" name="scan_limit" class="form-control form-control-sm" placeholder="∞"></div>
              <div class="col-md-3"><label class="form-label">Device count limit</label><input type="number" min="1" name="device_limit" class="form-control form-control-sm" placeholder="∞"></div>
              <div class="col-md-3"><label class="form-label">Allowed cities</label><input type="text" name="allowed_cities" class="form-control form-control-sm" placeholder="Dhaka, Lagos"></div>
              <div class="col-12"><label class="form-label">Allowed countries <span class="text-muted-sm">(none = any · click to move left → right)</span></label>@include('anti-counterfeit._country-picker', ['countries' => $countries, 'selected' => []])</div>
              <div class="col-12"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional"></div>
            </div>
          </div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-shield-plus me-1"></i>Save Permission</button>
        </form>
      </div>

      {{-- ══ TAB: Global Scan & Access Setting — applies to ALL UUC codes ══ --}}
      <div x-show="tab==='global'" x-cloak>
        <h5 class="fw-bold mb-1">Global Scan &amp; Access Setting</h5>
        <p class="text-muted-sm mb-3">
          One baseline that applies to <strong>every UUC code</strong> on every <code>/verify</code> scan — device count, scan/hit limit, allowed countries &amp; cities, IP/geo and a master lock.
          A more specific rule (Product&nbsp;/&nbsp;Batch&nbsp;/&nbsp;Serial) always overrides this global baseline.
        </p>

        @php $g = $globalPolicy; @endphp
        @if($g)
          <div class="alert {{ $g->active ? 'alert-info' : 'alert-secondary' }} py-2 small d-flex align-items-center">
            <i class="bi bi-globe2 me-2"></i>
            Global policy is <strong class="mx-1">{{ $g->active ? 'ACTIVE' : 'disabled' }}</strong>
            @if($g->locked) · <span class="badge bg-danger ms-1">Verification locked</span>@endif
            <span class="ms-auto text-muted">Last updated {{ $g->updated_at?->diffForHumans() }}</span>
          </div>
        @else
          <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle me-1"></i>No global policy set yet — every code verifies without a baseline restriction.</div>
        @endif

        <form method="POST" action="{{ route('anticounterfeit.policies.global') }}">
          @csrf
          <div class="perm-box mb-3">
            <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2">Conditions applied to all UUC codes</div>
            <div class="row g-3 align-items-end">
              <div class="col-md-3">
                <label class="form-label">Verification</label>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="locked" value="1" id="gLockSwitch" @checked($g?->locked)><label class="form-check-label small" for="gLockSwitch"><i class="bi bi-lock me-1"></i>Locked (block all)</label></div>
              </div>
              <div class="col-md-3"><label class="form-label">Scan / hit limit</label><input type="number" min="1" name="scan_limit" class="form-control form-control-sm" placeholder="∞" value="{{ $g?->scan_limit }}"></div>
              <div class="col-md-3"><label class="form-label">Device count limit</label><input type="number" min="1" name="device_limit" class="form-control form-control-sm" placeholder="∞" value="{{ $g?->device_limit }}"></div>
              <div class="col-md-3">
                <label class="form-label">Policy state</label>
                <input type="hidden" name="active" value="0">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="active" value="1" id="gActiveSwitch" @checked($g ? $g->active : true)><label class="form-check-label small" for="gActiveSwitch">Active</label></div>
              </div>
              <div class="col-md-12"><label class="form-label">Allowed cities <span class="text-muted-sm">(none = any city)</span></label><input type="text" name="allowed_cities" class="form-control form-control-sm" placeholder="Dhaka, Lagos, Nairobi" value="{{ implode(', ', (array) ($g?->allowed_cities ?? [])) }}"></div>
              <div class="col-12"><label class="form-label">Allowed countries <span class="text-muted-sm">(none = any · click to move left → right)</span></label>@include('anti-counterfeit._country-picker', ['countries' => $countries, 'selected' => (array) ($g?->allowed_countries ?? [])])</div>
              <div class="col-12"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional" value="{{ $g?->notes }}"></div>
            </div>
          </div>

          {{-- IP & VPN / proxy controls --}}
          <div class="perm-box mb-3">
            <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2"><i class="bi bi-hdd-network me-1"></i>IP &amp; VPN / Proxy controls</div>
            <div class="row g-3 align-items-start">
              <div class="col-md-4">
                <label class="form-label">Master switch</label>
                <input type="hidden" name="allow_all" value="0">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_all" value="1" id="gAllowAll" @checked($g?->allow_all)><label class="form-check-label small" for="gAllowAll"><i class="bi bi-unlock me-1"></i>Allow all (bypass blocks)</label></div>
                <div class="text-muted-sm mt-1">When ON, every code verifies — IP/VPN/lock/limit blocks are ignored (recall &amp; expiry still apply).</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">VPN / Proxy</label>
                <input type="hidden" name="block_vpn" value="0">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="block_vpn" value="1" id="gBlockVpn" @checked($g?->block_vpn)><label class="form-check-label small" for="gBlockVpn"><i class="bi bi-shield-lock me-1"></i>Block when VPN/Proxy detected</label></div>
                <div class="text-muted-sm mt-1">Shows “not available over VPN”. Every VPN scan is recorded per UUC.</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">VPN-allowed countries <span class="text-muted-sm">(codes)</span></label>
                <input type="text" name="vpn_allowed_countries" class="form-control form-control-sm" placeholder="BD, NG, KE" value="{{ implode(', ', (array) ($g?->vpn_allowed_countries ?? [])) }}">
                <div class="text-muted-sm mt-1">VPN is permitted from these countries even when blocking is ON.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">IP whitelist <span class="text-muted-sm">(trusted — always pass)</span></label>
                <textarea name="ip_whitelist" rows="2" class="form-control form-control-sm" placeholder="203.0.113.5, 198.51.100.0/24 (comma / space / newline)">{{ implode(', ', (array) ($g?->ip_whitelist ?? [])) }}</textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">IP blacklist <span class="text-muted-sm">(always blocked)</span></label>
                <textarea name="ip_blacklist" rows="2" class="form-control form-control-sm" placeholder="203.0.113.9, 192.0.2.0/24">{{ implode(', ', (array) ($g?->ip_blacklist ?? [])) }}</textarea>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary btn-sm"><i class="bi bi-globe2 me-1"></i>Save Global Setting</button>
            <span class="text-muted-sm"><i class="bi bi-info-circle me-1"></i>Scan/verify logs, IP, device, country, city &amp; VPN use are recorded for every scan regardless of this setting.</span>
          </div>
        </form>
      </div>

      {{-- ══ TAB: Scan Intelligence — 8 configurable detection scenarios ══ --}}
      <div x-show="tab==='intel'" x-cloak>
        <h5 class="fw-bold mb-1">Scan Intelligence</h5>
        <p class="text-muted-sm mb-3">
          Configurable detection rules shown to customers on the verification page for every UUC code — multiple countries / IPs,
          repeated &amp; high-frequency scans, region mismatch, and a returning-scan genuine certificate. Toggle each rule, tune its
          thresholds, and edit the customer-facing message. Hard-block rules (repeated/same-IP, high-frequency) also block the scan.
        </p>

        <form method="POST" action="{{ route('anticounterfeit.policies.intel') }}">
          @csrf
          @foreach($intelMeta as $key => $meta)
            @php $rule = $intel[$key] ?? []; @endphp
            <div class="perm-box mb-3">
              <div class="d-flex align-items-center mb-2">
                <span style="font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#334155">{{ $meta['label'] }}</span>
                <div class="form-check form-switch ms-auto mb-0">
                  <input type="hidden" name="rules[{{ $key }}][enabled]" value="0">
                  <input class="form-check-input" type="checkbox" name="rules[{{ $key }}][enabled]" value="1" id="intel_{{ $key }}_en" @checked(!empty($rule['enabled']))>
                  <label class="form-check-label small" for="intel_{{ $key }}_en">Enabled</label>
                </div>
              </div>
              <div class="row g-3 align-items-end">
                @foreach($meta['fields'] as $fkey => $flabel)
                  <div class="col-md-3"><label class="form-label">{{ $flabel }}</label><input type="number" min="0" name="rules[{{ $key }}][{{ $fkey }}]" class="form-control form-control-sm" value="{{ $rule[$fkey] ?? '' }}"></div>
                @endforeach
                <div class="col-md-3"><label class="form-label">Records to show</label><input type="number" min="0" name="rules[{{ $key }}][records]" class="form-control form-control-sm" value="{{ $rule['records'] ?? 0 }}"></div>
                <div class="col-md-3">
                  <label class="form-label">Report form</label>
                  <input type="hidden" name="rules[{{ $key }}][report]" value="0">
                  <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="rules[{{ $key }}][report]" value="1" id="intel_{{ $key }}_rep" @checked(!empty($rule['report']))><label class="form-check-label small" for="intel_{{ $key }}_rep">Show report form</label></div>
                </div>
                <div class="col-12"><label class="form-label">Alert title</label><input type="text" name="rules[{{ $key }}][title]" class="form-control form-control-sm" value="{{ $rule['title'] ?? '' }}"></div>
                <div class="col-12"><label class="form-label">Alert message</label><textarea name="rules[{{ $key }}][text]" rows="2" class="form-control form-control-sm">{{ $rule['text'] ?? '' }}</textarea></div>
              </div>
            </div>
          @endforeach
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary btn-sm"><i class="bi bi-radar me-1"></i>Save Scan Intelligence</button>
            <span class="text-muted-sm"><i class="bi bi-info-circle me-1"></i>Applies to every UUC code on the public verification page.</span>
          </div>
        </form>
      </div>

      {{-- ══ TAB 2: Customer (design only) ══ --}}
      <div x-show="tab==='customer'" x-cloak>
        <h5 class="fw-bold mb-1">Customer Permission</h5>
        <p class="text-muted-sm mb-3">Grant or restrict verification for specific customers / distributors. <span class="badge bg-warning-subtle text-warning-emphasis">Design preview</span></p>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Customer / Distributor</label><select class="form-select form-select-sm" disabled><option>Select customer…</option></select></div>
          <div class="col-md-6"><label class="form-label">Role</label><select class="form-select form-select-sm" disabled><option>Distributor</option><option>Retailer</option><option>Patient</option></select></div>
        </div>
        <div class="perm-box my-3">
          <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2">Allowed Actions</div>
          <div class="row g-3 align-items-end">
            <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" disabled checked><label class="form-check-label small">Can verify products</label></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label small">Can forward to next party</label></div></div>
            <div class="col-md-4"><label class="form-label">Max verifications / day</label><input type="number" class="form-control form-control-sm" placeholder="∞" disabled></div>
            <div class="col-md-4"><label class="form-label">Allowed countries</label><select class="form-select form-select-sm" disabled><option>Any</option></select></div>
          </div>
        </div>
        <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-hammer me-1"></i>Coming soon</button>
      </div>

      {{-- ══ TAB: Find by UUC — lookup + per-code permission ══ --}}
      <div x-show="tab==='lookup'" x-cloak>
        <h5 class="fw-bold mb-1">Find by UUC</h5>
        <p class="text-muted-sm mb-3">Search a UUC secret code or serial to see its status &amp; effective permissions, then set lock, devices, countries, cities or scan limit for that exact code.</p>

        <form method="GET" action="{{ route('anticounterfeit.policies') }}" class="row g-2 mb-3">
          <input type="hidden" name="ftab" value="lookup">
          <div class="col-md-8"><input type="text" name="code" value="{{ $lookup['searched'] ?? '' }}" class="form-control form-control-sm" placeholder="Enter UUC secret code or serial…"></div>
          <div class="col-md-4"><button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Search</button></div>
        </form>

        @if($lookup && empty($lookup['unit']) && !empty($lookup['searched']))
          <div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i>No unit matches “{{ $lookup['searched'] }}”.</div>
        @endif

        @if($lookup && !empty($lookup['unit']))
          @php $u = $lookup['unit']; $eff = $lookup['effective']; $cp = $lookup['codePolicy']; $prod = $u->batch?->product; @endphp
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="perm-box">
                <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2">Unit</div>
                <table class="table table-sm mb-0">
                  <tr><td class="text-muted" style="width:120px">Code</td><td class="font-monospace">{{ $u->secret_code }}</td></tr>
                  <tr><td class="text-muted">Serial</td><td>#{{ $u->serial_number }}</td></tr>
                  <tr><td class="text-muted">Product</td><td>{{ $prod?->name ?? '—' }}</td></tr>
                  <tr><td class="text-muted">Batch</td><td>{{ $u->batch?->brn ?? '—' }}</td></tr>
                  <tr><td class="text-muted">Unit status</td><td>{{ ucfirst($u->status) }}</td></tr>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <div class="perm-box">
                <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2">Effective permission</div>
                @if($prod?->verify_open)
                  <div class="alert alert-info py-2 mb-2 small"><i class="bi bi-unlock me-1"></i><strong>Verify anywhere</strong> is ON for this product — all locks &amp; country rules are bypassed.</div>
                @endif
                @if($eff)
                  <table class="table table-sm mb-0">
                    <tr><td class="text-muted" style="width:140px">Status</td><td>
                      @if($eff->locked)<span class="badge-status badge-cancelled">Locked</span>@else<span class="badge-status badge-approved">Unlocked</span>@endif
                      <a href="{{ route('anticounterfeit.uuc', ['search' => $u->secret_code]) }}" class="ms-2 small text-decoration-none">
                        <i class="bi bi-{{ $eff->locked ? 'unlock' : 'lock' }} me-1"></i>{{ $eff->locked ? 'Unlock' : 'Lock' }} in UUC Management <i class="bi bi-box-arrow-up-right"></i>
                      </a>
                    </td></tr>
                    <tr><td class="text-muted">Rule</td><td>{{ $eff->scope_label }} — {{ $eff->name }}</td></tr>
                    <tr><td class="text-muted">Allowed countries</td><td>{{ implode(', ', (array) $eff->allowed_countries) ?: 'Any' }}</td></tr>
                    <tr><td class="text-muted">Allowed cities</td><td>{{ implode(', ', (array) $eff->allowed_cities) ?: 'Any' }}</td></tr>
                    <tr><td class="text-muted">Scan limit</td><td>{{ $eff->scan_limit ?? '∞' }}</td></tr>
                    <tr><td class="text-muted">Device limit</td><td>{{ $eff->device_limit ?? '∞' }}</td></tr>
                  </table>
                @else
                  <div class="d-flex align-items-center">
                    <span class="badge-status badge-approved">Unlocked</span>
                    <a href="{{ route('anticounterfeit.uuc', ['search' => $u->secret_code]) }}" class="ms-2 small text-decoration-none"><i class="bi bi-lock me-1"></i>Lock in UUC Management <i class="bi bi-box-arrow-up-right"></i></a>
                  </div>
                  <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>No access-control policy applies — verifies anywhere (Country Authorization on the product may still restrict markets).</div>
                @endif
              </div>
            </div>
          </div>

          {{-- Lock forensics — why locked + post-lock scan abuse tracking --}}
          <div class="perm-box mb-3" style="border-left:3px solid {{ $u->locked_at ? '#dc3545' : '#adb5bd' }}">
            <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2"><i class="bi bi-shield-exclamation me-1"></i>Lock forensics</div>
            <div class="row g-3">
              <div class="col-md-3">
                <div class="text-muted-sm">Scans after lock / limit</div>
                <div class="fw-bold {{ ($u->blocked_scan_count ?? 0) > 0 ? 'text-danger' : '' }}" style="font-size:20px">{{ $u->blocked_scan_count ?? 0 }}</div>
              </div>
              <div class="col-md-3">
                <div class="text-muted-sm">Last blocked scan</div>
                <div class="fw-semibold">{{ $u->last_blocked_scan_at?->diffForHumans() ?? '—' }}</div>
              </div>
              <div class="col-md-3">
                <div class="text-muted-sm">Locked at</div>
                <div class="fw-semibold">{{ $u->locked_at?->format('d M Y H:i') ?? '—' }}</div>
              </div>
              <div class="col-md-3">
                <div class="text-muted-sm">Lock reason</div>
                <div class="fw-semibold">{{ $u->lock_reason ?: '—' }}</div>
              </div>
              <div class="col-md-3">
                <div class="text-muted-sm">VPN / proxy scans</div>
                <div class="fw-bold {{ ($u->vpn_scan_count ?? 0) > 0 ? 'text-warning' : '' }}" style="font-size:20px">{{ $u->vpn_scan_count ?? 0 }}</div>
              </div>
              <div class="col-md-3">
                <div class="text-muted-sm">Last VPN scan</div>
                <div class="fw-semibold">{{ $u->last_vpn_scan_at?->diffForHumans() ?? '—' }}</div>
              </div>
            </div>
          </div>

          <form method="POST" action="{{ route('anticounterfeit.policies.unit') }}">
            @csrf
            <input type="hidden" name="code" value="{{ $u->secret_code }}">
            <div class="perm-box mb-3">
              <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2">Set permission for this code @if($cp)<span class="badge bg-primary-subtle text-primary ms-1">existing</span>@endif</div>
              @php $sel = array_map('strtoupper', (array) ($cp?->allowed_countries ?? [])); @endphp
              <div class="row g-3 align-items-end">
                <div class="col-md-3"><label class="form-label">Verification</label><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="locked" value="1" id="luLock" @checked($cp?->locked)><label class="form-check-label small" for="luLock"><i class="bi bi-lock me-1"></i>Locked</label></div></div>
                <div class="col-md-9"><label class="form-label">Lock reason <span class="text-muted-sm">(recorded against this UUC)</span></label><input type="text" name="lock_reason" class="form-control form-control-sm" placeholder="e.g. Reported counterfeit / sold out of market" value="{{ $u->lock_reason }}"></div>
                <div class="col-md-3"><label class="form-label">Scan / hit limit</label><input type="number" min="1" name="scan_limit" class="form-control form-control-sm" placeholder="∞" value="{{ $cp?->scan_limit }}"></div>
                <div class="col-md-3"><label class="form-label">Device count limit</label><input type="number" min="1" name="device_limit" class="form-control form-control-sm" placeholder="∞" value="{{ $cp?->device_limit }}"></div>
                <div class="col-md-3"><label class="form-label">Allowed cities</label><input type="text" name="allowed_cities" class="form-control form-control-sm" placeholder="Dhaka, Lagos" value="{{ implode(', ', (array) ($cp?->allowed_cities ?? [])) }}"></div>
                <div class="col-12"><label class="form-label">Allowed countries <span class="text-muted-sm">(none = any · click to move left → right)</span></label>
                  @include('anti-counterfeit._country-picker', ['countries' => $countries, 'selected' => $sel])
                </div>

                {{-- IP & VPN controls for this code --}}
                <div class="col-md-4">
                  <label class="form-label">Allow all (bypass)</label>
                  <input type="hidden" name="allow_all" value="0">
                  <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_all" value="1" id="luAllowAll" @checked($cp?->allow_all)><label class="form-check-label small" for="luAllowAll"><i class="bi bi-unlock me-1"></i>Allow all</label></div>
                </div>
                <div class="col-md-4">
                  <label class="form-label">VPN / Proxy</label>
                  <input type="hidden" name="block_vpn" value="0">
                  <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="block_vpn" value="1" id="luBlockVpn" @checked($cp?->block_vpn)><label class="form-check-label small" for="luBlockVpn"><i class="bi bi-shield-lock me-1"></i>Block VPN/Proxy</label></div>
                </div>
                <div class="col-md-4"><label class="form-label">VPN-allowed countries <span class="text-muted-sm">(codes)</span></label><input type="text" name="vpn_allowed_countries" class="form-control form-control-sm" placeholder="BD, NG" value="{{ implode(', ', (array) ($cp?->vpn_allowed_countries ?? [])) }}"></div>
                <div class="col-md-6"><label class="form-label">IP whitelist <span class="text-muted-sm">(always pass)</span></label><textarea name="ip_whitelist" rows="2" class="form-control form-control-sm" placeholder="203.0.113.5, 198.51.100.0/24">{{ implode(', ', (array) ($cp?->ip_whitelist ?? [])) }}</textarea></div>
                <div class="col-md-6"><label class="form-label">IP blacklist <span class="text-muted-sm">(always blocked)</span></label><textarea name="ip_blacklist" rows="2" class="form-control form-control-sm" placeholder="203.0.113.9">{{ implode(', ', (array) ($cp?->ip_blacklist ?? [])) }}</textarea></div>

                <div class="col-12"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional" value="{{ $cp?->notes }}"></div>
              </div>
            </div>
            <button class="btn btn-primary btn-sm"><i class="bi bi-shield-check me-1"></i>{{ $cp ? 'Update' : 'Save' }} permission</button>
            @if($cp)<span class="text-muted-sm ms-2">Editing existing code policy.</span>@endif
          </form>
        @endif
      </div>

      {{-- ══ TAB: Find by Product / Batch / Serial ══ --}}
      <div x-show="tab==='find'" x-cloak>
        <h5 class="fw-bold mb-1">Find by Product / Batch</h5>
        <p class="text-muted-sm mb-3">Look up a product, a batch, or a serial / UUC to see its scan intelligence (units, scans, hits, locked) and set a product- or batch-wide permission in one place.</p>

        @php
          $findBatchesJs = $batches->map(fn ($b) => ['id' => $b->id, 'product_id' => $b->product_id, 'label' => $b->brn . ' — ' . ($b->product?->name ?? '')])->values();
        @endphp
        <form method="GET" action="{{ route('anticounterfeit.policies') }}" class="row g-2 mb-3"
              x-data="findForm(@js($findBatchesJs), '{{ $find['fp'] ?? '' }}', '{{ $find['fb'] ?? '' }}')">
          <input type="hidden" name="ftab" value="find">
          <div class="col-md-4">
            <label class="form-label">Product</label>
            <select name="fp" class="form-select form-select-sm" x-model="fp" @change="onProduct()">
              <option value="">— Select product —</option>
              @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Batch <span class="text-muted-sm" x-show="fp">(of selected product)</span></label>
            <select name="fb" class="form-select form-select-sm" x-model="fb">
              <option value="">— Select batch —</option>
              <template x-for="b in filteredBatches" :key="b.id">
                <option :value="b.id" x-text="b.label"></option>
              </template>
            </select>
            <div class="text-muted-sm mt-1" x-show="fp && filteredBatches.length === 0" x-cloak>
              <i class="bi bi-exclamation-circle me-1"></i>No batches found for this product.
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Serial / UUC code</label>
            <div class="input-group input-group-sm">
              <input type="text" name="fs" value="{{ $find['fs'] ?? '' }}" class="form-control" placeholder="Secret code, serial or label">
              <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            </div>
          </div>
          <div class="col-12"><button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Search</button></div>
        </form>

        {{-- Serial → unit resolution --}}
        @if(!empty($find['unit']))
          @if(empty($find['unit']['found']))
            <div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i>No unit matches “{{ $find['unit']['searched'] }}”.</div>
          @else
            @php $su = $find['unit']['model']; $seff = $find['unit']['effective']; @endphp
            <div class="perm-box mb-3">
              <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2">Serial resolved</div>
              <div class="row g-3">
                <div class="col-md-7"><table class="table table-sm mb-0">
                  <tr><td class="text-muted" style="width:120px">Code</td><td class="font-monospace">{{ $su->secret_code }}</td></tr>
                  <tr><td class="text-muted">Serial</td><td>#{{ $su->serial_number }}</td></tr>
                  <tr><td class="text-muted">Product</td><td>{{ $su->batch?->product?->name ?? '—' }}</td></tr>
                  <tr><td class="text-muted">Batch</td><td>{{ $su->batch?->brn ?? '—' }}</td></tr>
                </table></div>
                <div class="col-md-5">
                  <div class="text-muted-sm mb-1">Effective rule: <strong>{{ $seff?->scope_label ?? 'None' }}</strong></div>
                  <a href="{{ route('anticounterfeit.policies', ['ftab'=>'lookup','code'=>$su->secret_code]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-upc-scan me-1"></i>Manage this code (Find by UUC)</a>
                </div>
              </div>
            </div>
          @endif
        @endif

        {{-- Product + Batch panels --}}
        @foreach(['product' => $find['product'] ?? null, 'batch' => $find['batch'] ?? null] as $scope => $info)
          @if($info)
            @php
              $isProduct = $scope === 'product';
              $m   = $info['model'];
              $pol = $info['policy'];
              $title = $isProduct ? $m->name . ' (' . $m->prn . ')' : $m->brn . ' — ' . ($m->product?->name ?? '');
              $sel = array_map('strtoupper', (array) ($pol?->allowed_countries ?? []));
            @endphp
            <div class="perm-box mb-3">
              <div class="d-flex align-items-center mb-2">
                <span style="font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#334155"><i class="bi bi-{{ $isProduct ? 'capsule' : 'layers' }} me-1"></i>{{ ucfirst($scope) }} — {{ $title }}</span>
                @if($pol)<span class="badge bg-primary-subtle text-primary ms-2">policy {{ $pol->active ? 'active' : 'inactive' }}</span>@endif
              </div>

              {{-- Stat row for instant decisions --}}
              <div class="row g-2 mb-3 text-center">
                @if($isProduct)
                  <div class="col"><div class="text-muted-sm">Batches</div><div class="fw-bold">{{ $info['batches'] }}</div></div>
                @endif
                <div class="col"><div class="text-muted-sm">Units</div><div class="fw-bold">{{ $info['units'] }}</div></div>
                <div class="col"><div class="text-muted-sm">Scans</div><div class="fw-bold">{{ $info['scans'] }}</div></div>
                <div class="col"><div class="text-muted-sm">Hits</div><div class="fw-bold text-danger">{{ $info['flagged'] }}</div></div>
                <div class="col"><div class="text-muted-sm">Locked units</div><div class="fw-bold {{ $info['locked'] > 0 ? 'text-danger' : '' }}">{{ $info['locked'] }}</div></div>
              </div>

              {{-- Product-wise batch breakdown --}}
              @if($isProduct && !empty($info['batchList']))
                <div class="mb-3">
                  <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-1">Batches ({{ count($info['batchList']) }})</div>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead><tr>
                        <th>Batch</th><th>Mfg → Exp</th><th class="text-center">Units</th><th class="text-center">Scans</th>
                        <th class="text-center">Hits</th><th class="text-center">Locked</th><th class="text-center">Policy</th><th class="text-end">Manage</th>
                      </tr></thead>
                      <tbody>
                        @foreach($info['batchList'] as $row)
                          @php $b = $row['model']; @endphp
                          <tr>
                            <td class="font-monospace small">{{ $b->brn }}@if($b->batch_number)<div class="text-muted-sm">{{ $b->batch_number }}</div>@endif</td>
                            <td class="small">{{ $b->manufacture_date?->format('M Y') ?? '—' }} → {{ $b->expiry_date?->format('M Y') ?? '—' }}</td>
                            <td class="text-center">{{ $row['units'] }}</td>
                            <td class="text-center">{{ $row['scans'] }}</td>
                            <td class="text-center {{ $row['flagged'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ $row['flagged'] }}</td>
                            <td class="text-center {{ $row['lockedUnits'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ $row['lockedUnits'] }}</td>
                            <td class="text-center">@if($row['hasPolicy'])<span class="badge bg-primary-subtle text-primary">set</span>@else<span class="text-muted-sm">—</span>@endif</td>
                            <td class="text-end"><a href="{{ route('anticounterfeit.policies', ['ftab'=>'find','fp'=>$m->id,'fb'=>$b->id]) }}" class="btn btn-outline-primary btn-sm btn-icon" title="Manage batch permission"><i class="bi bi-sliders"></i></a></td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif

              <form method="POST" action="{{ route('anticounterfeit.policies.scope') }}">
                @csrf
                <input type="hidden" name="scope_type" value="{{ $scope }}">
                <input type="hidden" name="{{ $isProduct ? 'product_id' : 'batch_id' }}" value="{{ $m->id }}">
                <div class="row g-3 align-items-end">
                  <div class="col-md-3"><label class="form-label">Verification</label><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="locked" value="1" id="sc_{{ $scope }}_lock" @checked($pol?->locked)><label class="form-check-label small" for="sc_{{ $scope }}_lock"><i class="bi bi-lock me-1"></i>Locked</label></div></div>
                  <div class="col-md-3"><label class="form-label">Allow all (bypass)</label><div class="form-check form-switch"><input type="hidden" name="allow_all" value="0"><input class="form-check-input" type="checkbox" name="allow_all" value="1" id="sc_{{ $scope }}_all" @checked($pol?->allow_all)><label class="form-check-label small" for="sc_{{ $scope }}_all">Allow all</label></div></div>
                  <div class="col-md-3"><label class="form-label">Scan / hit limit</label><input type="number" min="1" name="scan_limit" class="form-control form-control-sm" placeholder="∞" value="{{ $pol?->scan_limit }}"></div>
                  <div class="col-md-3"><label class="form-label">Device limit</label><input type="number" min="1" name="device_limit" class="form-control form-control-sm" placeholder="∞" value="{{ $pol?->device_limit }}"></div>
                  <div class="col-md-3"><label class="form-label">VPN / Proxy</label><div class="form-check form-switch"><input type="hidden" name="block_vpn" value="0"><input class="form-check-input" type="checkbox" name="block_vpn" value="1" id="sc_{{ $scope }}_vpn" @checked($pol?->block_vpn)><label class="form-check-label small" for="sc_{{ $scope }}_vpn">Block VPN</label></div></div>
                  <div class="col-md-4"><label class="form-label">VPN-allowed countries</label><input type="text" name="vpn_allowed_countries" class="form-control form-control-sm" placeholder="BD, NG" value="{{ implode(', ', (array) ($pol?->vpn_allowed_countries ?? [])) }}"></div>
                  <div class="col-md-5"><label class="form-label">Allowed cities</label><input type="text" name="allowed_cities" class="form-control form-control-sm" placeholder="Dhaka, Lagos" value="{{ implode(', ', (array) ($pol?->allowed_cities ?? [])) }}"></div>
                  <div class="col-md-6"><label class="form-label">IP whitelist <span class="text-muted-sm">(always pass)</span></label><textarea name="ip_whitelist" rows="2" class="form-control form-control-sm" placeholder="203.0.113.5, 198.51.100.0/24">{{ implode(', ', (array) ($pol?->ip_whitelist ?? [])) }}</textarea></div>
                  <div class="col-md-6"><label class="form-label">IP blacklist <span class="text-muted-sm">(always blocked)</span></label><textarea name="ip_blacklist" rows="2" class="form-control form-control-sm" placeholder="203.0.113.9">{{ implode(', ', (array) ($pol?->ip_blacklist ?? [])) }}</textarea></div>
                  <div class="col-12"><label class="form-label">Allowed countries <span class="text-muted-sm">(none = any · click to move left → right)</span></label>@include('anti-counterfeit._country-picker', ['countries' => $countries, 'selected' => $sel])</div>
                  <div class="col-12"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional" value="{{ $pol?->notes }}"></div>
                </div>
                <button class="btn btn-primary btn-sm mt-3"><i class="bi bi-shield-check me-1"></i>{{ $pol ? 'Update' : 'Save' }} {{ $scope }} permission</button>
              </form>
            </div>
          @endif
        @endforeach

        @if(empty($find['unit']) && empty($find['product']) && empty($find['batch']))
          <div class="text-muted-sm"><i class="bi bi-info-circle me-1"></i>Select a product or batch, or enter a serial / UUC, then search.</div>
        @endif
      </div>

      {{-- ══ TAB 3: Active policies (search / sort / export / edit) ══ --}}
      <div x-show="tab==='list'" x-cloak>
        <div class="d-flex align-items-center mb-3">
          <h5 class="fw-bold mb-0">Active Policies</h5>
          <a href="{{ route('anticounterfeit.policies.export', array_merge(request()->query(), ['ftab'=>'list'])) }}" class="btn btn-outline-success btn-sm ms-auto"><i class="bi bi-download me-1"></i>Export CSV</a>
        </div>

        {{-- Filter / search / sort bar --}}
        <form method="GET" class="row g-2 align-items-end mb-3">
          <input type="hidden" name="ftab" value="list">
          <div class="col-md-3">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm" placeholder="Name, product, batch, serial / secret">
          </div>
          <div class="col-md-2">
            <label class="form-label">Scope</label>
            <select name="scope" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="global" @selected(($filters['scope']??'')==='global')>Global (all codes)</option>
              <option value="product" @selected(($filters['scope']??'')==='product')>Product</option>
              <option value="batch" @selected(($filters['scope']??'')==='batch')>Batch</option>
              <option value="codes" @selected(($filters['scope']??'')==='codes')>Specific codes</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="1" @selected(($filters['status']??'')==='1')>Active</option>
              <option value="0" @selected(($filters['status']??'')==='0')>Disabled</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Date</label>
            <select name="date" class="form-select form-select-sm">
              <option value="">Any time</option>
              <option value="today" @selected(($filters['date']??'')==='today')>Today</option>
              <option value="7d" @selected(($filters['date']??'')==='7d')>Last 7 days</option>
              <option value="30d" @selected(($filters['date']??'')==='30d')>Last 30 days</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Sort</label>
            <select name="sort" class="form-select form-select-sm">
              <option value="newest" @selected(($filters['sort']??'newest')==='newest')>Newest first</option>
              <option value="oldest" @selected(($filters['sort']??'')==='oldest')>Oldest first</option>
              <option value="name" @selected(($filters['sort']??'')==='name')>Name A–Z</option>
              <option value="scope" @selected(($filters['sort']??'')==='scope')>Scope</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
          </div>
          <div class="col-md-2">
            <label class="form-label">Show</label>
            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
              @foreach([10,20,50,100] as $pp)
                <option value="{{ $pp }}" @selected((int)($filters['per_page'] ?? 10)===$pp)>{{ $pp }} / page</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Apply</button>
            <a href="{{ route('anticounterfeit.policies', ['ftab'=>'list']) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Name</th><th>Scope</th><th>Target</th><th>Lock</th><th>Countries</th><th>Cities</th><th>Scan</th><th>Device</th><th>Status</th><th>Created</th><th class="text-end">Action</th></tr></thead>
            <tbody>
              @forelse($policies as $p)
                <tr>
                  <td class="fw-semibold small">{{ $p->name }}</td>
                  <td class="small">{{ $p->scope_label }}</td>
                  <td class="small font-monospace">
                    @if($p->scope_type==='product'){{ $p->product?->name }}
                    @elseif($p->scope_type==='batch'){{ $p->batch?->brn }}
                    @else {{ collect($p->uuc_codes)->take(2)->implode(', ') }}{{ count($p->uuc_codes ?? [])>2 ? ' +'.(count($p->uuc_codes)-2) : '' }}
                    @endif
                  </td>
                  <td>@if($p->locked)<span class="badge-status badge-cancelled">Locked</span>@else<span class="text-muted">—</span>@endif</td>
                  <td class="small">{{ $p->allowed_countries ? implode(', ', $p->allowed_countries) : 'Any' }}</td>
                  <td class="small">{{ $p->allowed_cities ? implode(', ', $p->allowed_cities) : 'Any' }}</td>
                  <td class="small">{{ $p->scan_limit ?? '∞' }}</td>
                  <td class="small">{{ $p->device_limit ?? '∞' }}</td>
                  <td>@if($p->active)<span class="badge-status badge-approved">Active</span>@else<span class="badge-status badge-draft">Disabled</span>@endif</td>
                  <td class="text-muted small">{{ $p->created_at?->format('d M Y') }}</td>
                  <td class="text-end" style="white-space:nowrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" title="View" @click="openView(find({{ $p->id }}))"><i class="bi bi-eye"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-icon" title="Edit" @click="openEdit(find({{ $p->id }}))"><i class="bi bi-pencil"></i></button>
                    <form method="POST" action="{{ route('anticounterfeit.policies.toggle', $p) }}" class="d-inline">@csrf<button class="btn btn-sm {{ $p->active ? 'btn-outline-secondary' : 'btn-outline-success' }} btn-icon" title="{{ $p->active ? 'Disable' : 'Enable' }}"><i class="bi {{ $p->active ? 'bi-pause' : 'bi-play' }}"></i></button></form>
                    <form method="POST" action="{{ route('anticounterfeit.policies.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Delete this policy?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger btn-icon" title="Delete"><i class="bi bi-trash"></i></button></form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="11" class="text-center text-muted py-4">No policies match.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($policies->hasPages())<div class="pt-3">{{ $policies->links() }}</div>@endif
      </div>

    </div>{{-- /ac-body --}}
  </div>{{-- /ac-shell --}}
</div>{{-- /card --}}

{{-- ── VIEW DETAILS MODAL ── --}}
<div class="ac-modal" x-show="showView" x-cloak @keydown.escape.window="showView=false" @click.self="showView=false">
  <div class="ac-modal-box">
    <div class="d-flex align-items-center px-4 py-3 border-bottom">
      <h5 class="fw-bold mb-0"><i class="bi bi-shield-lock me-1 text-primary"></i><span x-text="view.name"></span></h5>
      <button class="btn-close ms-auto" @click="showView=false"></button>
    </div>
    <div class="p-4">
      <table class="table table-sm">
        <tr><td class="text-muted" style="width:160px">Scope</td><td class="fw-semibold" x-text="view.scope_label"></td></tr>
        <tr><td class="text-muted">Target</td><td class="font-monospace" x-text="view.scope_type==='product' ? view.product_name : (view.scope_type==='batch' ? view.batch_brn : (view.uuc_codes||[]).join(', '))"></td></tr>
        <tr><td class="text-muted">Locked</td><td><span x-show="view.locked" class="badge-status badge-cancelled">Locked</span><span x-show="!view.locked" class="text-muted">No</span></td></tr>
        <tr><td class="text-muted">Allowed countries</td><td x-text="(view.allowed_countries||[]).length ? view.allowed_countries.join(', ') : 'Any'"></td></tr>
        <tr><td class="text-muted">Allowed cities</td><td x-text="(view.allowed_cities||[]).length ? view.allowed_cities.join(', ') : 'Any'"></td></tr>
        <tr><td class="text-muted">Scan / hit limit</td><td x-text="view.scan_limit || '∞ unlimited'"></td></tr>
        <tr><td class="text-muted">Device limit</td><td x-text="view.device_limit || '∞ unlimited'"></td></tr>
        <tr><td class="text-muted">Status</td><td><span x-show="view.active" class="badge-status badge-approved">Active</span><span x-show="!view.active" class="badge-status badge-draft">Disabled</span></td></tr>
        <tr><td class="text-muted">Notes</td><td x-text="view.notes || '—'"></td></tr>
        <tr><td class="text-muted">Created</td><td x-text="view.created"></td></tr>
      </table>

      {{-- Product / batch / serial details behind this policy --}}
      <template x-if="view.details && view.details.product">
        <div class="perm-box mb-3">
          <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2"><i class="bi bi-capsule me-1"></i>Product</div>
          <div class="row g-2 small">
            <div class="col-6"><span class="text-muted">Name:</span> <span class="fw-semibold" x-text="view.details.product.name"></span></div>
            <div class="col-6"><span class="text-muted">PRN:</span> <span class="font-monospace" x-text="view.details.product.prn || '—'"></span></div>
            <div class="col-6"><span class="text-muted">Generic:</span> <span x-text="view.details.product.generic || '—'"></span></div>
            <div class="col-6"><span class="text-muted">Strength / Form:</span> <span x-text="(view.details.product.strength||'')+' '+(view.details.product.dosage||'') || '—'"></span></div>
            <div class="col-12"><span class="text-muted">Manufacturer:</span> <span x-text="view.details.product.manufacturer || '—'"></span></div>
          </div>
          <template x-if="view.details.batches && view.details.batches.length">
            <div class="mt-2">
              <div class="text-muted small mb-1">Recent batches</div>
              <table class="table table-sm mb-0"><thead><tr><th>BRN</th><th>Batch</th><th>Mfg</th><th>Exp</th></tr></thead>
              <tbody><template x-for="b in view.details.batches" :key="b.brn">
                <tr><td class="font-monospace small" x-text="b.brn"></td><td class="small" x-text="b.batch_number"></td><td class="small" x-text="b.mfg||'—'"></td><td class="small" x-text="b.exp||'—'"></td></tr>
              </template></tbody></table>
            </div>
          </template>
        </div>
      </template>

      <template x-if="view.details && view.details.batch">
        <div class="perm-box mb-3">
          <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2"><i class="bi bi-layers me-1"></i>Batch</div>
          <div class="row g-2 small">
            <div class="col-6"><span class="text-muted">Product:</span> <span class="fw-semibold" x-text="view.details.batch.product || '—'"></span></div>
            <div class="col-6"><span class="text-muted">BRN:</span> <span class="font-monospace" x-text="view.details.batch.brn"></span></div>
            <div class="col-6"><span class="text-muted">Batch No.:</span> <span x-text="view.details.batch.batch_number || '—'"></span></div>
            <div class="col-6"><span class="text-muted">Lot:</span> <span x-text="view.details.batch.lot || '—'"></span></div>
            <div class="col-6"><span class="text-muted">Mfg:</span> <span x-text="view.details.batch.mfg || '—'"></span></div>
            <div class="col-6"><span class="text-muted">Exp:</span> <span x-text="view.details.batch.exp || '—'"></span></div>
            <div class="col-6"><span class="text-muted">QC:</span> <span x-text="view.details.batch.qc || '—'"></span></div>
          </div>
        </div>
      </template>

      <template x-if="view.details && view.details.units && view.details.units.length">
        <div class="perm-box mb-3">
          <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d" class="mb-2"><i class="bi bi-upc-scan me-1"></i>Serial(s) / Products</div>
          <div class="table-responsive">
            <table class="table table-sm mb-0"><thead><tr><th>Code</th><th>Serial</th><th>Product</th><th>Batch</th><th>Mfg</th><th>Exp</th><th>Status</th></tr></thead>
            <tbody><template x-for="u in view.details.units" :key="u.code">
              <tr>
                <td class="font-monospace small" x-text="u.code"></td>
                <td class="small" x-text="'#'+u.serial"></td>
                <td class="small" x-text="u.product || '—'"></td>
                <td class="font-monospace small" x-text="u.brn || '—'"></td>
                <td class="small" x-text="u.mfg || '—'"></td>
                <td class="small" x-text="u.exp || '—'"></td>
                <td class="small" x-text="u.status"></td>
              </tr>
            </template></tbody></table>
          </div>
        </div>
      </template>

      <div class="text-end"><button class="btn btn-outline-primary btn-sm" @click="showView=false; openEdit(view)"><i class="bi bi-pencil me-1"></i>Edit</button></div>
    </div>
  </div>
</div>

{{-- ── EDIT MODAL ── --}}
<div class="ac-modal" x-show="showEdit" x-cloak @keydown.escape.window="showEdit=false" @click.self="showEdit=false">
  <div class="ac-modal-box">
    <form method="POST" :action="editAction">
      @csrf @method('PUT')
      <input type="hidden" name="scope_type" :value="eScope">
      <div class="d-flex align-items-center px-4 py-3 border-bottom">
        <h5 class="fw-bold mb-0"><i class="bi bi-pencil me-1 text-primary"></i>Edit Policy</h5>
        <button type="button" class="btn-close ms-auto" @click="showEdit=false"></button>
      </div>
      <div class="p-4">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Policy name <span class="text-danger">*</span></label><input type="text" name="name" x-model="form.name" class="form-control form-control-sm" required></div>
          <div class="col-md-6"><label class="form-label">Apply to</label>
            <select class="form-select form-select-sm" x-model="eScope">
              <option value="product">Whole Product</option>
              <option value="batch">Whole Batch</option>
              <option value="codes">Specific Serial(s)</option>
            </select>
          </div>
          <div class="col-md-6" x-show="eScope==='product'"><label class="form-label">Product</label>
            <select name="product_id" x-model="form.product_id" class="form-select form-select-sm"><option value="">Select…</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
          </div>
          <div class="col-md-6" x-show="eScope==='batch'"><label class="form-label">Batch</label>
            <select name="batch_id" x-model="form.batch_id" class="form-select form-select-sm"><option value="">Select…</option>@foreach($batches as $b)<option value="{{ $b->id }}">{{ $b->brn }}</option>@endforeach</select>
          </div>
          <div class="col-12" x-show="eScope==='codes'"><label class="form-label">UUC serial code(s)</label><textarea name="uuc_codes" x-model="form.uuc_codes" rows="2" class="form-control form-control-sm"></textarea></div>

          <div class="col-md-3"><label class="form-label">Locked</label><div class="form-check form-switch"><input type="hidden" name="locked" value="0"><input class="form-check-input" type="checkbox" name="locked" value="1" x-model="form.locked"></div></div>
          <div class="col-md-3"><label class="form-label">Scan limit</label><input type="number" min="1" name="scan_limit" x-model="form.scan_limit" class="form-control form-control-sm" placeholder="∞"></div>
          <div class="col-md-3"><label class="form-label">Device limit</label><input type="number" min="1" name="device_limit" x-model="form.device_limit" class="form-control form-control-sm" placeholder="∞"></div>
          <div class="col-md-3"><label class="form-label">Active</label><div class="form-check form-switch"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" name="active" value="1" x-model="form.active"></div></div>

          <div class="col-md-6"><label class="form-label">Allowed cities</label><input type="text" name="allowed_cities" x-model="form.allowed_cities" class="form-control form-control-sm" placeholder="Any"></div>
          <div class="col-md-6"><label class="form-label">Notes</label><input type="text" name="notes" x-model="form.notes" class="form-control form-control-sm"></div>
          <div class="col-12"><label class="form-label">Allowed countries <span class="text-muted-sm">(none = any)</span></label>
            <select name="allowed_countries[]" x-model="form.allowed_countries" class="form-select form-select-sm" multiple size="4">@foreach($countries as $c)<option value="{{ $c->code }}">{{ $c->flag }} {{ $c->name }} ({{ $c->code }})</option>@endforeach</select>
          </div>
        </div>
      </div>
      <div class="px-4 py-3 border-top d-flex gap-2 justify-content-end">
        <button type="button" class="btn btn-outline-secondary btn-sm" @click="showEdit=false">Cancel</button>
        <button class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Save Changes</button>
      </div>
    </form>
  </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function countryPicker(all, preselected){
  return {
    all: all || [],
    chosen: [...(preselected || [])],
    q: '',
    get available(){
      const q = this.q.trim().toLowerCase();
      return this.all.filter(c => !this.chosen.includes(c.code)
        && (!q || c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q)));
    },
    label(code){ const c = this.all.find(x => x.code === code); return c ? (c.flag+' '+c.name+' ('+c.code+')') : code; },
    add(code){ if(!this.chosen.includes(code)) this.chosen.push(code); },
    remove(code){ this.chosen = this.chosen.filter(c => c !== code); },
    addAll(){ this.available.forEach(c => this.chosen.push(c.code)); },
    clearAll(){ this.chosen = []; },
  };
}
function acPage(initialTab, policies){
  return {
    tab: initialTab || 'pb',
    scope: 'codes',
    policies: policies || [],
    showView:false, showEdit:false,
    view:{}, eScope:'codes',
    form:{id:null,name:'',scope_type:'codes',product_id:'',batch_id:'',uuc_codes:'',locked:false,scan_limit:'',device_limit:'',allowed_cities:'',allowed_countries:[],active:true,notes:''},
    updateTemplate: '{{ url('anti-counterfeit/access-control') }}/__ID__',
    find(id){ return this.policies.find(p => p.id === id) || {}; },
    openView(p){ this.view = p; this.showView = true; },
    openEdit(p){
      this.form = {
        id:p.id, name:p.name||'', scope_type:p.scope_type||'codes',
        product_id:p.product_id||'', batch_id:p.batch_id||'',
        uuc_codes:(p.uuc_codes||[]).join(', '),
        locked:!!p.locked, scan_limit:p.scan_limit||'', device_limit:p.device_limit||'',
        allowed_cities:(p.allowed_cities||[]).join(', '),
        allowed_countries:(p.allowed_countries||[]).map(String),
        active:!!p.active, notes:p.notes||'',
      };
      this.eScope = p.scope_type || 'codes';
      this.showEdit = true;
    },
    get editAction(){ return this.updateTemplate.replace('__ID__', this.form.id); },
  };
}
function findForm(batches, fp, fb){
  return {
    batches: batches || [],
    fp: fp || '',
    fb: fb || '',
    get filteredBatches(){
      return this.fp ? this.batches.filter(b => String(b.product_id) === String(this.fp)) : this.batches;
    },
    onProduct(){
      // Drop the chosen batch if it no longer belongs to the selected product.
      if (this.fb && !this.filteredBatches.some(b => String(b.id) === String(this.fb))) this.fb = '';
    },
  };
}
</script>
@endpush
