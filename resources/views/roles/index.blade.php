@extends('layouts.app')
@section('title', 'Create Role')

@section('content')
<div x-data="rolesApp()">

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div><h1>Create Role</h1><div class="page-breadcrumb">Admin / Users &amp; Roles / Create Role</div></div>
    <a href="{{ route('roles.permissions') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shield-lock me-1"></i>Set Permissions</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
  @endif

  <div class="row g-3">
    {{-- Create form --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header bg-transparent fw-semibold"><i class="bi bi-shield-plus me-1 text-primary"></i>New Role</div>
        <div class="card-body">
          <form method="POST" action="{{ route('roles.store') }}" x-data="{name:''}">
            @csrf
            <label class="form-label">Role Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Regional Auditor" x-model="name" required>
            <div class="form-text">Saved as a key, e.g. <code x-text="(name.trim().toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'')) || 'regional_auditor'"></code></div>
            <button class="btn btn-primary btn-sm w-100 mt-3"><i class="bi bi-check-lg me-1"></i>Create Role</button>
            <div class="form-text mt-2">After creating, assign modules on the <a href="{{ route('roles.permissions') }}">Permission Set</a> page.</div>
          </form>
        </div>
      </div>
    </div>

    {{-- Roles list --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header bg-transparent fw-semibold d-flex justify-content-between align-items-center">
          <span><i class="bi bi-list-ul me-1"></i>Existing Roles</span><span class="badge bg-secondary">{{ $roles->count() }}</span>
        </div>
        <div class="card-body p-0"><div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Role</th><th class="text-center">Modules</th><th class="text-center">Users</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
              @forelse($roles as $role)
                @php $isProtected = $role->name === 'super_admin'; @endphp
                <tr>
                  <td>
                    <span class="fw-semibold text-capitalize">{{ str_replace('_',' ',$role->name) }}</span>
                    @if($isProtected)<i class="bi bi-lock-fill text-muted ms-1" title="Protected"></i>@endif
                    <div class="text-muted-sm"><code>{{ $role->name }}</code></div>
                  </td>
                  <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $role->permissions_count }}</span></td>
                  <td class="text-center"><span class="badge bg-light text-dark border">{{ $role->users_count }}</span></td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <a href="{{ route('roles.permissions') }}?role={{ $role->id }}" class="btn btn-outline-primary btn-sm btn-icon" title="Set modules"><i class="bi bi-shield-lock"></i></a>
                      @unless($isProtected)
                        <button class="btn btn-outline-secondary btn-sm btn-icon" title="Rename" @click="openRename({{ $role->id }}, @js($role->name))"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('roles.destroy', $role) }}" @submit="return confirm('Delete the {{ addslashes(str_replace('_',' ',$role->name)) }} role?')">
                          @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm btn-icon" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                      @endunless
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No roles yet — create your first one.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div></div>
      </div>
    </div>
  </div>

  {{-- Rename modal --}}
  <div class="modal fade" :class="{show:showRename}" :style="showRename?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
      <form method="POST" :action="renameAction">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Rename Role</h5><button type="button" class="btn-close" @click="showRename=false"></button></div>
        <div class="modal-body"><label class="form-label">Role Name</label><input type="text" name="name" class="form-control form-control-sm" x-model="renameName" required></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" @click="showRename=false">Cancel</button><button class="btn btn-primary btn-sm">Save</button></div>
      </form>
    </div></div>
  </div>
  <div class="modal-backdrop fade show" x-show="showRename" @click="showRename=false" x-cloak></div>
</div>
@endsection

@push('scripts')
<script>
function rolesApp(){
  return {
    showRename:false, renameId:null, renameName:'',
    updateTpl: '{{ url('roles') }}/__ID__',
    get renameAction(){ return this.updateTpl.replace('__ID__', this.renameId); },
    openRename(id, name){ this.renameId=id; this.renameName=name; this.showRename=true; },
  };
}
</script>
@endpush
