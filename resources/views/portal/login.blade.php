@extends('layouts.portal')
@section('title', 'Customer Login')

@section('body')
<div class="d-flex align-items-center justify-content-center min-vh-100 p-3">
  <div class="card shadow-sm border-0" style="max-width:400px;width:100%;border-radius:14px">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <div class="brand fs-3">Pharma<span>Track</span></div>
        <div class="text-muted small">Customer Portal</div>
      </div>

      @if($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('portal.login.post') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="remember" id="remember">
          <label class="form-check-label small" for="remember">Remember me</label>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</button>
      </form>

      <div class="text-center text-muted small mt-3">Need access? Contact your account manager.</div>
    </div>
  </div>
</div>
@endsection
