@extends('layouts.portal')

@section('body')
<div class="min-vh-100 d-flex">
  {{-- Brand panel --}}
  <div class="auth-hero d-none d-lg-flex flex-column justify-content-between p-5" style="width:42%;">
    <div>@include('portal._brand', ['white' => true, 'h' => 40, 'cls' => 'fs-3 text-white'])</div>
    <div>
      <h2 class="fw-bold mb-4" style="max-width:420px">Your pharmaceutical supply chain, in one portal.</h2>
      <div class="feat"><i class="bi bi-box-seam"></i><div><div class="fw-semibold">Track your orders</div><div class="small opacity-75">Live status on every purchase order.</div></div></div>
      <div class="feat"><i class="bi bi-receipt"></i><div><div class="fw-semibold">Invoices at a glance</div><div class="small opacity-75">Proforma &amp; commercial invoices, always available.</div></div></div>
      <div class="feat"><i class="bi bi-upc-scan"></i><div><div class="fw-semibold">Full traceability</div><div class="small opacity-75">Every unit you receive, verifiable to the source.</div></div></div>
    </div>
    <div class="small opacity-75">© {{ date('Y') }} PharmaTrack. All rights reserved.</div>
  </div>

  {{-- Form panel --}}
  <div class="flex-fill d-flex align-items-center justify-content-center p-3 p-md-5">
    <div style="max-width:420px;width:100%">
      <div class="d-lg-none text-center mb-4">@include('portal._brand', ['h' => 38, 'cls' => 'fs-3'])</div>
      @yield('form')
    </div>
  </div>
</div>
@endsection
