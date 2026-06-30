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
            <span class="ms-auto text-muted-sm"><span class="badge bg-secondary" x-text="selected.length"></span> permission(s)</span>
          </div>
        </div>
      </div>

      <template x-if="!roleId">
        <div class="text-center text-muted py-5"><i class="bi bi-hand-index-thumb d-block mb-2" style="font-size:26px"></i>Select a role above to set its permissions.</div>
      </template>

      <div x-show="roleId" x-cloak>
        <div class="alert alert-light border" x-show="protectedRole" x-cloak>
          <span class="d-flex align-items-center"><i class="bi bi-lock-fill me-2"></i><strong class="text-capitalize" x-text="roleLabel"></strong><span class="ms-1">always has full access — changes are disabled.</span></span>
        </div>

        <div class="alert alert-light border py-2 small">
          <i class="bi bi-info-circle me-1"></i><strong>Access</strong> lets the role open a module; the action columns
          (View/Create/Edit/Delete/Export) control what its users may do. Users inherit these from their role.
        </div>

        <div class="table-responsive border rounded-2" style="max-height:460px;overflow:auto">
          <table class="table table-sm align-middle mb-0">
            <thead class="position-sticky top-0 bg-body" style="z-index:1">
              <tr>
                <th class="small" style="min-width:160px">Module</th>
                <th class="text-center small">Access</th>
                @foreach($actions as $aKey => $aLabel)<th class="text-center small">{{ $aLabel }}</th>@endforeach
                <th class="text-center small">All</th>
              </tr>
            </thead>
            <tbody>
              @foreach($modules as $mKey => $cfg)
                <tr>
                  <td class="small fw-semibold">{{ $cfg['label'] }}</td>
                  <td class="text-center">
                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $mKey }}" x-model="selected" :disabled="protectedRole" title="Module access">
                  </td>
                  @foreach($actions as $aKey => $aLabel)
                    <td class="text-center">
                      <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $mKey }}.{{ $aKey }}" x-model="selected" :disabled="protectedRole">
                    </td>
                  @endforeach
                  <td class="text-center">
                    <input class="form-check-input" type="checkbox" :checked="moduleAll('{{ $mKey }}')" @change="toggleModule('{{ $mKey }}', $event.target.checked)" :disabled="protectedRole">
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
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
    // module key => [ "{module}", "{module}.{action}", ... ]
    moduleMatrix: @js(collect($modules)->mapWithKeys(fn ($cfg, $k) => [$k => array_merge([$k], array_map(fn ($a) => "$k.$a", array_keys($actions)))])->all()),
    submitTpl: '{{ url('roles') }}/__ID__/permissions',

    init(){
      const pre = new URLSearchParams(window.location.search).get('role');
      if (pre && this.rolePermissions[pre]) { this.roleId = pre; this.loadRole(); }
    },
    get submitAction(){ return this.submitTpl.replace('__ID__', this.roleId); },
    get protectedRole(){ return this.roleId ? !!(this.rolesMeta[this.roleId]?.protected) : false; },
    get roleLabel(){ return this.roleId ? (this.rolesMeta[this.roleId]?.label ?? '') : ''; },
    loadRole(){ this.selected = this.roleId ? [...(this.rolePermissions[this.roleId] ?? [])] : []; },
    moduleAll(m){ const a=this.moduleMatrix[m]||[]; return a.length>0 && a.every(p=>this.selected.includes(p)); },
    toggleModule(m, on){ const a=this.moduleMatrix[m]||[]; this.selected = on ? [...new Set([...this.selected, ...a])] : this.selected.filter(p=>!a.includes(p)); },
    selectAll(){ this.selected = Object.values(this.moduleMatrix).flat(); },
  };
}
</script>
@endpush
