@extends('layouts.portal')
@section('title', 'My Profile')

@section('body')
<nav class="navbar bg-white border-bottom px-3 px-md-4 py-2 sticky-top">
  <a href="{{ route('portal.dashboard') }}" class="brand fs-5 text-decoration-none">Pharma<span>Track</span> <span class="text-muted fs-6 fw-normal ms-1">Portal</span></a>
  <div class="ms-auto d-flex align-items-center gap-2">
    @include('portal._notifications')
    <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-3"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
    <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm rounded-3"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button></form>
  </div>
</nav>

<div class="container-xl py-4" style="max-width:900px">
  <h4 class="fw-bold mb-1">My Profile</h4>
  <div class="text-muted small mb-4">Update your details and password.</div>

  @if(session('status'))<div class="alert alert-success py-2 small">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" x-data="{ logoPreview:null }">
    @csrf
    <div class="card-soft p-4 mb-3">
      <div class="row g-4">
        <div class="col-md-3 text-center">
          <div class="border rounded-3 d-flex align-items-center justify-content-center mx-auto mb-2" style="width:110px;height:110px;overflow:hidden;background:var(--bg)">
            <template x-if="logoPreview || '{{ $customer->logo_url }}'.length">
              <img :src="logoPreview || '{{ $customer->logo_url }}'" style="width:100%;height:100%;object-fit:cover">
            </template>
            <template x-if="!(logoPreview || '{{ $customer->logo_url }}'.length)">
              <span class="grad rounded d-inline-flex align-items-center justify-content-center text-white" style="width:100%;height:100%;font-size:34px;font-weight:700">{{ $customer->initials }}</span>
            </template>
          </div>
          <input type="file" name="company_logo" accept="image/*" class="form-control form-control-sm" @change="logoPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
          <div class="text-muted small mt-1">Company logo</div>
        </div>
        <div class="col-md-9">
          <div class="row g-3">
            <div class="col-md-7"><label class="form-label small fw-semibold">Name</label><input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-control" required></div>
            <div class="col-md-5"><label class="form-label small fw-semibold">Type</label><input type="text" value="{{ $customer->type_label }}" class="form-control" disabled></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-control"></div>
            <div class="col-md-7"><label class="form-label small fw-semibold">Country</label>
              <select name="country_id" class="form-select" required>
                @foreach($countries as $co)<option value="{{ $co->id }}" @selected((string)old('country_id',$customer->country_id)===(string)$co->id)>{{ $co->flag }} {{ $co->name }}</option>@endforeach
              </select>
            </div>
            <div class="col-md-5"><label class="form-label small fw-semibold">City</label><input type="text" name="city" value="{{ old('city', $customer->city) }}" class="form-control"></div>
            <div class="col-12"><label class="form-label small fw-semibold">Address</label><textarea name="address" rows="2" class="form-control">{{ old('address', $customer->address) }}</textarea></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Company name</label><input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Company ID</label><input type="text" name="company_id" value="{{ old('company_id', $customer->company_id) }}" class="form-control"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-soft p-4 mb-3">
      <div class="fw-semibold mb-3"><i class="bi bi-shield-lock me-1" style="color:var(--brand1)"></i>Change password</div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label small fw-semibold">New password</label><input type="password" name="password" class="form-control" placeholder="leave blank to keep"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Confirm password</label><input type="password" name="password_confirmation" class="form-control"></div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
      <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
      <button class="btn btn-grad px-4"><i class="bi bi-check-lg me-1"></i>Save changes</button>
    </div>
  </form>
</div>
@endsection
