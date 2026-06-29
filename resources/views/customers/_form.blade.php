{{-- Shared add/edit customer fields. Bound to Alpine `form`. --}}
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Customer name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control form-control-sm" x-model="form.name" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Company name</label>
    <input type="text" name="company_name" class="form-control form-control-sm" x-model="form.company_name" placeholder="Registered company / trading name">
  </div>
  <div class="col-md-5">
    <label class="form-label">Type <span class="text-danger">*</span></label>
    <select name="type" class="form-select form-select-sm" x-model="form.type" required>
      @foreach($types as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
    </select>
  </div>
  <div class="col-md-5">
    <label class="form-label">Country <span class="text-danger">*</span></label>
    <select name="country_id" class="form-select form-select-sm" x-model="form.country_id" required>
      <option value="">Select country…</option>
      @foreach($countries as $co)<option value="{{ $co->id }}">{{ $co->flag }} {{ $co->name }}</option>@endforeach
    </select>
  </div>
  @if($isAdmin)
  <div class="col-md-4">
    <label class="form-label">Account Manager</label>
    <select name="manager_id" class="form-select form-select-sm" x-model="form.manager_id">
      <option value="">— Unassigned —</option>
      @foreach($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
    </select>
    <div class="text-muted-sm mt-1">The manager who can see &amp; sell to this customer.</div>
  </div>
  @endif
  <div class="col-md-4">
    <label class="form-label">Status</label>
    <select name="status" class="form-select form-select-sm" x-model="form.status">
      <option value="active">Active</option><option value="pending">Pending</option>
      <option value="suspended">Suspended</option><option value="expired">Expired</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">License no.</label>
    <input type="text" name="license_number" class="form-control form-control-sm" x-model="form.license_number" placeholder="optional">
  </div>

  <div class="col-md-4"><label class="form-label">Contact person</label><input type="text" name="contact_person" class="form-control form-control-sm" x-model="form.contact_person"></div>
  <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="contact_phone" class="form-control form-control-sm" x-model="form.contact_phone"></div>
  <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="contact_email" class="form-control form-control-sm" x-model="form.contact_email"></div>

  <div class="col-12"><label class="form-label">Address</label><textarea name="address" rows="2" class="form-control form-control-sm" x-model="form.address"></textarea></div>
</div>
