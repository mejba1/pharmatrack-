@extends('layouts.portal-auth')
@section('title', 'Create Account')

@section('form')
<div class="card-soft p-4 p-md-5">
  <h4 class="fw-bold mb-1">Create your account</h4>
  <div class="text-muted small mb-4">Register as a customer. Your account is reviewed before activation.</div>

  @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('portal.register.post') }}">
    @csrf
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label small fw-semibold">Full / contact name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Customer type <span class="text-danger">*</span></label>
        <select name="type" class="form-select" required>
          @foreach($types as $k => $label)<option value="{{ $k }}" @selected(old('type')===$k)>{{ $label }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Company</label>
        <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Phone</label>
        <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
      </div>
      <div class="col-md-7">
        <label class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
        <select name="country_id" class="form-select" required>
          <option value="">Select country…</option>
          @foreach($countries as $co)<option value="{{ $co->id }}" @selected((string)old('country_id')===(string)$co->id)>{{ $co->flag }} {{ $co->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label small fw-semibold">City</label>
        <input type="text" name="city" value="{{ old('city') }}" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control" placeholder="min 8 characters" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Confirm password <span class="text-danger">*</span></label>
        <input type="password" name="password_confirmation" class="form-control" required>
      </div>
    </div>
    <button class="btn btn-grad w-100 py-2 mt-4"><i class="bi bi-person-plus me-1"></i>Create account</button>
  </form>

  <div class="text-center small text-muted mt-4">Already registered? <a href="{{ route('portal.login') }}" class="fw-semibold" style="color:var(--brand1)">Sign in</a></div>
</div>
@endsection
