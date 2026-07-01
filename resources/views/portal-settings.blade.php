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

  <form method="POST" action="{{ route('customers.portal-settings.update') }}">
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
</div>
@endsection
