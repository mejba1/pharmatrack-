@extends('layouts.guest')
@section('title', 'Forgot Password')

@section('content')
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-circle"><i class="bi bi-capsule-pill"></i></div>
      <h5 class="fw-bold mb-1">Forgot password</h5>
      <p class="text-muted" style="font-size:13px">We'll email you a reset link</p>
    </div>

    @if(session('status'))<div class="alert alert-success py-2 px-3 mb-3" style="font-size:13px">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('password.email') }}">
      @csrf
      <div class="mb-3">
        <label class="form-label">Email</label>
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
          <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="you@company.com" value="{{ old('email') }}" required autofocus>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Send reset link</button>
    </form>

    <p class="text-center mt-3 mb-0"><a href="{{ route('login') }}" style="font-size:13px" class="text-primary text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a></p>
  </div>
</div>
@endsection
