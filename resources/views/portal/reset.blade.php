@extends('layouts.portal-auth')
@section('title', 'Reset Password')

@section('form')
<div class="card-soft p-4 p-md-5">
  <h4 class="fw-bold mb-1">Set a new password</h4>
  <div class="text-muted small mb-4">Choose a strong password for your account.</div>

  @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('portal.password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
      <label class="form-label small fw-semibold">Email</label>
      <input type="email" name="email" value="{{ $email }}" class="form-control" required readonly>
    </div>
    <div class="mb-3">
      <label class="form-label small fw-semibold">New password</label>
      <input type="password" name="password" class="form-control" placeholder="min 8 characters" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label small fw-semibold">Confirm password</label>
      <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <button class="btn btn-grad w-100 py-2"><i class="bi bi-shield-lock me-1"></i>Reset password</button>
  </form>

  <div class="text-center small text-muted mt-4"><a href="{{ route('portal.login') }}" class="fw-semibold" style="color:var(--brand1)">Back to sign in</a></div>
</div>
@endsection
