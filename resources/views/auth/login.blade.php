@extends('layouts.guest')
@section('title', 'Login')

@section('content')
<div class="login-page" x-data="loginPage()">

  <div class="login-card">
    <!-- Logo -->
    <div class="login-logo">
      <div class="logo-circle"><i class="bi bi-capsule-pill"></i></div>
      <h5 class="fw-bold mb-1">PharmaTrack</h5>
      <p class="text-muted" style="font-size:13px">Order &amp; Shipment Tracking System</p>
    </div>

    <!-- Alert -->
    <div x-show="error" class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px" x-text="error"></div>

    <form @submit.prevent="submit">
      <div class="mb-3">
        <label class="form-label">Email / Username</label>
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
          <input type="text" class="form-control border-start-0 ps-0"
                 placeholder="Enter your email" x-model="form.email" autocomplete="username">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
          <input :type="showPass ? 'text' : 'password'" class="form-control border-start-0 ps-0 border-end-0"
                 placeholder="Enter your password" x-model="form.password" autocomplete="current-password">
          <span class="input-group-text bg-white cursor-pointer" @click="showPass = !showPass">
            <i :class="showPass ? 'bi-eye-slash' : 'bi-eye'" class="bi text-muted"></i>
          </span>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="form-check mb-0">
          <input class="form-check-input" type="checkbox" id="remember" x-model="form.remember">
          <label class="form-check-label" for="remember" style="font-size:13px">Remember me</label>
        </div>
        <a href="#" style="font-size:13px" class="text-primary text-decoration-none">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" :disabled="loading">
        <span x-show="loading" class="spinner-border spinner-border-sm me-2"></span>
        <span x-text="loading ? 'Signing in...' : 'Sign In'"></span>
      </button>
    </form>

    <hr class="my-3">

    <!-- Demo Role Shortcuts -->
    <div class="mb-1" style="font-size:12px;color:#6c757d;text-align:center;margin-bottom:8px!important">
      Demo Accounts (click to auto-fill)
    </div>
    <div class="d-flex flex-wrap gap-2 justify-content-center">
      <template x-for="role in demoRoles" :key="role.email">
        <button type="button" class="btn btn-outline-secondary btn-sm"
                style="font-size:11px;border-radius:6px"
                @click="fillDemo(role)" x-text="role.label"></button>
      </template>
    </div>

    <p class="text-center text-muted mt-4 mb-0" style="font-size:11px">
      Pharmaceutical Order Shipment Tracking System v1.0 &copy; 2026
    </p>
  </div>

</div>
@endsection

@push('scripts')
<script>
function loginPage() {
  return {
    form: { email: '', password: '', remember: false },
    loading: false, error: '', showPass: false,
    demoRoles: [
      { label: 'Super Admin',  email: 'admin@pharmatrack.com',    role: 'super_admin' },
      { label: 'Manufacturer', email: 'mfg@pharmatrack.com',      role: 'manufacturer' },
      { label: 'Distributor',  email: 'dist@pharmatrack.com',     role: 'distributor' },
      { label: 'Finance',      email: 'finance@pharmatrack.com',  role: 'finance' },
      { label: 'Logistics',    email: 'logistics@pharmatrack.com',role: 'logistics' },
      { label: 'QC Officer',   email: 'qc@pharmatrack.com',       role: 'qc' },
    ],
    fillDemo(role) {
      this.form.email    = role.email;
      this.form.password = 'demo1234';
    },
    submit() {
      this.error = '';
      if (!this.form.email || !this.form.password) {
        this.error = 'Please enter your email and password.';
        return;
      }
      this.loading = true;
      setTimeout(() => {
        this.loading = false;
        window.location.href = '{{ route('dashboard') }}';
      }, 900);
    }
  };
}
</script>
@endpush
