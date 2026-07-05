@extends('layouts.portal-auth')
@section('title', 'Forgot Password')

@section('form')
<div class="card-soft p-4 p-md-5">
  <h4 class="fw-bold mb-1">Forgot your password?</h4>
  <div class="text-muted small mb-4">Enter your email and we'll send you a reset link.</div>

  @if(session('status'))<div class="alert alert-success py-2 small">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('portal.password.email') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label small fw-semibold">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="you@company.com" required autofocus>
    </div>
    <button class="btn btn-grad w-100 py-2"><i class="bi bi-envelope-paper me-1"></i>Send reset link</button>
  </form>

  <div class="text-center small text-muted mt-4"><a href="{{ route('portal.login') }}" class="fw-semibold" style="color:var(--brand1)"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a></div>
</div>
@endsection
