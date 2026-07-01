@extends('layouts.app')
@section('title', 'Customer Portal Settings')

@section('content')
<div>
  <div class="page-header">
    <div><h1>Customer Portal</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Customers / Portal Settings</div></div>
    <a href="{{ route('portal.login') }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Open portal</a>
  </div>

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="alert alert-light border small"><i class="bi bi-info-circle me-1 text-primary"></i>Control what your customers see and can do in the self-service portal. Changes take effect immediately.</div>

  <form method="POST" action="{{ route('customers.portal-settings.update') }}" enctype="multipart/form-data">
    @csrf
    @php
      $toggle = function ($key, $label, $desc) use ($portal) {
        return ['key' => $key, 'label' => $label, 'desc' => $desc, 'on' => $portal[$key]];
      };
    @endphp

    {{-- Master switch --}}
    <div class="card mb-3"><div class="card-body d-flex align-items-center">
      <div><div class="fw-semibold">Portal enabled</div><div class="text-muted-sm">Master switch — when off, customers see a "temporarily unavailable" message and can't sign in.</div></div>
      <div class="form-check form-switch ms-auto fs-5">
        <input type="hidden" name="portal_enabled" value="0">
        <input class="form-check-input" type="checkbox" name="portal_enabled" value="1" @checked($portal['portal_enabled'])>
      </div>
    </div></div>

    <div class="row g-3">
      {{-- Visible sections --}}
      <div class="col-lg-6">
        <div class="card h-100"><div class="card-header bg-transparent fw-semibold"><i class="bi bi-window me-1 text-primary"></i>Dashboard tabs</div>
          <div class="card-body">
            @foreach([
              $toggle('portal_show_orders','Orders','Customer can see their purchase orders'),
              $toggle('portal_show_invoices','Invoices','Proforma &amp; commercial invoices'),
              $toggle('portal_show_documents','Documents','Documents you shared with them'),
              $toggle('portal_show_units','Traceable Units','Serialized units they received'),
            ] as $t)
              <div class="d-flex align-items-center py-2 border-bottom">
                <div><div class="fw-semibold" style="font-size:14px">{{ $t['label'] }}</div><div class="text-muted-sm">{!! $t['desc'] !!}</div></div>
                <div class="form-check form-switch ms-auto">
                  <input type="hidden" name="{{ $t['key'] }}" value="0">
                  <input class="form-check-input" type="checkbox" name="{{ $t['key'] }}" value="1" @checked($t['on'])>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Allowed actions --}}
      <div class="col-lg-6">
        <div class="card h-100"><div class="card-header bg-transparent fw-semibold"><i class="bi bi-toggles me-1 text-primary"></i>What customers can do</div>
          <div class="card-body">
            @foreach([
              $toggle('portal_allow_ordering','Place orders','Show the "Place Order" flow (creates a PO)'),
              $toggle('portal_allow_registration','Self-registration','Allow new customers to register (pending approval)'),
              $toggle('portal_allow_profile_edit','Edit profile','Let customers update their own details &amp; password'),
            ] as $t)
              <div class="d-flex align-items-center py-2 border-bottom">
                <div><div class="fw-semibold" style="font-size:14px">{{ $t['label'] }}</div><div class="text-muted-sm">{!! $t['desc'] !!}</div></div>
                <div class="form-check form-switch ms-auto">
                  <input type="hidden" name="{{ $t['key'] }}" value="0">
                  <input class="form-check-input" type="checkbox" name="{{ $t['key'] }}" value="1" @checked($t['on'])>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Branding --}}
      <div class="col-12" x-data="{ logoPreview:null }">
        <div class="card"><div class="card-header bg-transparent fw-semibold"><i class="bi bi-palette me-1 text-primary"></i>Branding</div>
          <div class="card-body">
            <div class="row g-3 align-items-end">
              <div class="col-md-3 text-center">
                <label class="form-label d-block">Portal logo</label>
                <div class="border rounded-3 d-flex align-items-center justify-content-center mx-auto mb-2" style="width:120px;height:70px;overflow:hidden;background:var(--bs-light)">
                  <template x-if="logoPreview || '{{ \App\Support\PortalSettings::logoUrl() }}'.length">
                    <img :src="logoPreview || '{{ \App\Support\PortalSettings::logoUrl() }}'" style="max-width:100%;max-height:100%;object-fit:contain">
                  </template>
                  <template x-if="!(logoPreview || '{{ \App\Support\PortalSettings::logoUrl() }}'.length)"><span class="text-muted-sm">No logo</span></template>
                </div>
                <input type="file" name="portal_logo" accept="image/*" class="form-control form-control-sm" @change="logoPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
              </div>
              <div class="col-md-5">
                <label class="form-label">Brand name</label>
                <input type="text" name="portal_brand_name" class="form-control" value="{{ $portal['portal_brand_name'] }}" placeholder="PharmaTrack">
              </div>
              <div class="col-md-2">
                <label class="form-label">Primary</label>
                <input type="color" name="portal_primary" class="form-control form-control-color w-100" value="{{ $portal['portal_primary'] }}">
              </div>
              <div class="col-md-2">
                <label class="form-label">Accent</label>
                <input type="color" name="portal_accent" class="form-control form-control-color w-100" value="{{ $portal['portal_accent'] }}">
              </div>
            </div>
            <div class="text-muted-sm mt-2"><i class="bi bi-info-circle me-1"></i>Used for the portal login, dashboard and emails-facing pages.</div>
          </div>
        </div>
      </div>

      {{-- Messaging --}}
      <div class="col-12">
        <div class="card"><div class="card-header bg-transparent fw-semibold"><i class="bi bi-megaphone me-1 text-primary"></i>Messaging</div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Welcome / announcement banner</label>
                <textarea name="portal_welcome_message" rows="2" class="form-control" maxlength="500" placeholder="Shown on the customer dashboard, e.g. holiday shipping notice…">{{ $portal['portal_welcome_message'] }}</textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label">Support email</label>
                <input type="email" name="portal_support_email" class="form-control" value="{{ $portal['portal_support_email'] }}" placeholder="support@yourco.com">
                <div class="text-muted-sm mt-1">Shown to customers who need help.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
      <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save settings</button>
    </div>
  </form>

  {{-- Audit trail --}}
  <div class="card mt-4"><div class="card-header bg-transparent fw-semibold"><i class="bi bi-clock-history me-1 text-primary"></i>Change history</div>
    <div class="card-body p-0"><div class="list-group list-group-flush">
      @forelse($logs as $log)
        <div class="list-group-item">
          <div class="d-flex align-items-center">
            <span class="fw-semibold small">{{ $log->user?->name ?? 'Someone' }}</span>
            <span class="text-muted-sm ms-2">{{ $log->created_at->diffForHumans() }}</span>
            <span class="text-muted-sm ms-auto">{{ $log->created_at->format('d M Y, H:i') }}</span>
          </div>
          <div class="small text-muted mt-1">
            @foreach($log->changes as $key => $c)
              <span class="badge bg-light text-dark border me-1 mb-1" style="font-weight:500">
                {{ \Illuminate\Support\Str::of($key)->after('portal_')->replace('_',' ')->title() }}:
                <span class="text-danger">{{ \Illuminate\Support\Str::limit((string) ($c['from'] === true ? 'on' : ($c['from'] === false ? 'off' : $c['from'])), 20) ?: '—' }}</span>
                →
                <span class="text-success">{{ \Illuminate\Support\Str::limit((string) ($c['to'] === true ? 'on' : ($c['to'] === false ? 'off' : $c['to'])), 20) ?: '—' }}</span>
              </span>
            @endforeach
          </div>
        </div>
      @empty
        <div class="text-center text-muted py-4 small">No changes recorded yet.</div>
      @endforelse
    </div></div>
  </div>
</div>
@endsection
