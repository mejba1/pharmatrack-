{{-- Shared add/edit customer fields. Bound to Alpine `form`. --}}
<div class="row g-3">

  {{-- Logo + identity --}}
  <div class="col-md-3 text-center">
    <label class="form-label d-block">Company logo</label>
    <div class="border rounded-3 d-flex align-items-center justify-content-center mx-auto mb-2" style="width:96px;height:96px;overflow:hidden;background:var(--bs-light)">
      <template x-if="logoPreview || form.company_logo_url">
        <img :src="logoPreview || form.company_logo_url" alt="logo" style="width:100%;height:100%;object-fit:cover">
      </template>
      <template x-if="!(logoPreview || form.company_logo_url)">
        <span class="fw-bold text-muted" style="font-size:28px" x-text="(form.name||'C').slice(0,2).toUpperCase()"></span>
      </template>
    </div>
    <input type="file" name="company_logo" accept="image/*" class="form-control form-control-sm" @change="onLogo($event)">
  </div>

  <div class="col-md-9">
    <div class="row g-3">
      <div class="col-md-7">
        <label class="form-label">Customer name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control form-control-sm" x-model="form.name" required>
      </div>
      <div class="col-md-5">
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-select form-select-sm" x-model="form.type" required>
          @foreach($types as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-sm" x-model="form.email" placeholder="login &amp; contact email"></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control form-control-sm" x-model="form.phone"></div>
    </div>
  </div>

  {{-- Location --}}
  <div class="col-md-4">
    <label class="form-label">Country <span class="text-danger">*</span></label>
    <select name="country_id" class="form-select form-select-sm" x-model="form.country_id" required>
      <option value="">Select country…</option>
      @foreach($countries as $co)<option value="{{ $co->id }}">{{ $co->flag }} {{ $co->name }}</option>@endforeach
    </select>
  </div>
  <div class="col-md-4"><label class="form-label">City</label><input type="text" name="city" class="form-control form-control-sm" x-model="form.city"></div>
  <div class="col-md-4"><label class="form-label">Referenced by</label><input type="text" name="referenced_by" class="form-control form-control-sm" x-model="form.referenced_by" placeholder="who referred them"></div>
  <div class="col-12"><label class="form-label">Address</label><textarea name="address" rows="2" class="form-control form-control-sm" x-model="form.address"></textarea></div>

  {{-- Company --}}
  <div class="col-12"><hr class="my-1"><div class="text-muted-sm text-uppercase fw-bold" style="font-size:11px">Company &amp; identification</div></div>
  <div class="col-md-6"><label class="form-label">Company name</label><input type="text" name="company_name" class="form-control form-control-sm" x-model="form.company_name" placeholder="Registered / trading name"></div>
  <div class="col-md-6"><label class="form-label">Company ID</label><input type="text" name="company_id" class="form-control form-control-sm" x-model="form.company_id" placeholder="registration / trade id"></div>
  <div class="col-md-4">
    <label class="form-label">Identification type</label>
    <select name="identification_type" class="form-select form-select-sm" x-model="form.identification_type">
      <option value="">—</option>
      @foreach(\App\Models\Customer::ID_TYPES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
    </select>
  </div>
  <div class="col-md-4"><label class="form-label">Identification number</label><input type="text" name="identification_number" class="form-control form-control-sm" x-model="form.identification_number"></div>
  <div class="col-md-4"><label class="form-label">License no.</label><input type="text" name="license_number" class="form-control form-control-sm" x-model="form.license_number" placeholder="optional"></div>

  {{-- Admin / status / portal --}}
  <div class="col-12"><hr class="my-1"><div class="text-muted-sm text-uppercase fw-bold" style="font-size:11px">Account</div></div>
  @if($isAdmin)
  <div class="col-md-4">
    <label class="form-label">Account Manager</label>
    <select name="manager_id" class="form-select form-select-sm" x-model="form.manager_id">
      <option value="">— Unassigned —</option>
      @foreach($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
    </select>
  </div>
  @endif
  <div class="col-md-4">
    <label class="form-label">Status</label>
    <select name="status" class="form-select form-select-sm" x-model="form.status">
      <option value="active">Active</option><option value="pending">Pending</option>
      <option value="suspended">Suspended</option><option value="expired">Expired</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Portal password <span class="text-muted-sm" x-text="form.id ? '(blank = keep)' : '(for customer login)'"></span></label>
    <input type="text" name="password" class="form-control form-control-sm" x-model="form.password" placeholder="set to enable login">
  </div>

  {{-- Per-customer portal overrides --}}
  <div class="col-12"><hr class="my-1"><div class="text-muted-sm text-uppercase fw-bold" style="font-size:11px">Portal access (overrides)</div></div>
  <div class="col-md-4">
    <label class="form-label d-block">Portal login</label>
    <div class="form-check form-switch">
      <input type="hidden" name="portal_access" value="0">
      <input class="form-check-input" type="checkbox" name="portal_access" value="1" id="portal_access" x-model="form.portal_access">
      <label class="form-check-label small" for="portal_access">Allow this customer to sign in</label>
    </div>
  </div>
  <div class="col-md-4">
    <label class="form-label">Ordering</label>
    <select name="portal_can_order" class="form-select form-select-sm" x-model="form.portal_can_order">
      <option value="">Follow global setting</option>
      <option value="1">Always allow</option>
      <option value="0">Block ordering</option>
    </select>
  </div>
</div>
