@extends('layouts.app')
@section('title', 'Permission Set')

@section('content')
<div x-data="permissionSet()">

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div><h1>Permission Set</h1><div class="page-breadcrumb">Admin / Users &amp; Roles / Permission Set</div></div>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shield-plus me-1"></i>Create Role</a>
  </div>

  <div class="card"><div class="card-body">

    <form method="POST" :action="submitAction">
      @csrf @method('PUT')

      <div class="row g-3 align-items-end mb-3">
        <div class="col-md-5">
          <label class="form-label">Select Role <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" x-model="roleId" @change="loadRole()">
            <option value="">— Choose a role —</option>
            @foreach($roles as $role)
              <option value="{{ $role->id }}">{{ ucwords(str_replace('_',' ',$role->name)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-7" x-show="roleId" x-cloak>
          <div class="d-flex gap-2 flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="selectAll()" :disabled="protectedRole"><i class="bi bi-check-all me-1"></i>Select all</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="selected=[]" :disabled="protectedRole"><i class="bi bi-x-lg me-1"></i>Clear</button>
            <span class="ms-auto text-muted-sm"><span class="badge bg-secondary" x-text="selected.length"></span> module(s)</span>
          </div>
        </div>
      </div>

      <template x-if="!roleId">
        <div class="text-center text-muted py-5"><i class="bi bi-hand-index-thumb d-block mb-2" style="font-size:26px"></i>Select a role above to set its module access.</div>
      </template>

      <div x-show="roleId" x-cloak>
        <div class="alert alert-light border" x-show="protectedRole" x-cloak>
          <span class="d-flex align-items-center"><i class="bi bi-lock-fill me-2"></i><strong class="text-capitalize" x-text="roleLabel"></strong><span class="ms-1">always has full access — changes are disabled.</span></span>
        </div>

        <div class="row g-2">
          @foreach($modules as $key => $cfg)
            <div class="col-6 col-md-4">
              <div class="form-check border rounded-2 p-2 ps-4">
                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="m_{{ $key }}" x-model="selected" :disabled="protectedRole">
                <label class="form-check-label small" for="m_{{ $key }}">{{ $cfg['label'] }}</label>
              </div>
            </div>
          @endforeach
        </div>

        <div class="d-flex justify-content-end mt-3">
          <button class="btn btn-primary btn-sm" :disabled="protectedRole"><i class="bi bi-check-lg me-1"></i>Save Permissions</button>
        </div>
      </div>
    </form>

  </div></div>
</div>
@endsection

@push('scripts')
<script>
function permissionSet(){
  return {
    roleId: '',
    selected: [],
    rolePermissions: @js($rolePermissions),
    rolesMeta: @js($roles->mapWithKeys(fn ($r) => [$r->id => ['label' => ucwords(str_replace('_',' ',$r->name)), 'protected' => $r->name === 'super_admin']])),
    submitTpl: '{{ url('roles') }}/__ID__/permissions',

    init(){
      const pre = new URLSearchParams(window.location.search).get('role');
      if (pre && this.rolePermissions[pre]) { this.roleId = pre; this.loadRole(); }
    },
    get submitAction(){ return this.submitTpl.replace('__ID__', this.roleId); },
    get protectedRole(){ return this.roleId ? !!(this.rolesMeta[this.roleId]?.protected) : false; },
    get roleLabel(){ return this.roleId ? (this.rolesMeta[this.roleId]?.label ?? '') : ''; },
    loadRole(){ this.selected = this.roleId ? [...(this.rolePermissions[this.roleId] ?? [])] : []; },
    selectAll(){ this.selected = @js(array_keys($modules)); },
  };
}
</script>
@endpush
