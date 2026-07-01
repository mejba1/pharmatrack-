@extends('layouts.portal-auth')
@section('title', 'Customer Login')

@section('form')
<div class="card-soft p-4 p-md-5">
  <h4 class="fw-bold mb-1">Welcome back</h4>
  <div class="text-muted small mb-4">Sign in to your customer portal.</div>

  @if(session('status'))<div class="alert alert-success py-2 small">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('portal.login.post') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small fw-semibold">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="you@company.com" required autofocus>
    </div>
    <div class="mb-2">
      <label class="form-label small fw-semibold">Password</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label small" for="remember">Remember me</label></div>
      <a href="{{ route('portal.password.request') }}" class="small fw-semibold" style="color:var(--brand1)">Forgot password?</a>
    </div>
    <button class="btn btn-grad w-100 py-2"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</button>
  </form>

  <div class="text-center small text-muted mt-4">New customer? <a href="{{ route('portal.register') }}" class="fw-semibold" style="color:var(--brand1)">Create an account</a></div>
</div>
@endsection
