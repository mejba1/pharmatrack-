@extends('layouts.guest')
@section('title', 'Reset Password')

@section('content')
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-circle"><i class="bi bi-shield-lock"></i></div>
      <h5 class="fw-bold mb-1">Reset password</h5>
      <p class="text-muted" style="font-size:13px">Choose a new password</p>
    </div>

    @if($errors->any())<div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('password.update') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ $email }}" required readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">New password</label>
        <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input type="password" name="password_confirmation" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Reset password</button>
    </form>

    <p class="text-center mt-3 mb-0"><a href="{{ route('login') }}" style="font-size:13px" class="text-primary text-decoration-none">Back to sign in</a></p>
  </div>
</div>
@endsection
