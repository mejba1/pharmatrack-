@extends('layouts.app')
@section('title', 'Users & Roles')

@section('content')
<div x-data="usersApp()">

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div><h1>Users &amp; Roles</h1><div class="page-breadcrumb">Admin / Users &amp; Roles</div></div>
    <div><button class="btn btn-primary btn-sm" @click="openAdd()"><i class="bi bi-person-plus me-1"></i>Add User</button></div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card stat-primary"><div class="stat-label">Users</div><div class="stat-value">{{ $stats['total'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-success"><div class="stat-label">Active</div><div class="stat-value">{{ $stats['active'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-warning"><div class="stat-label">Super Admins</div><div class="stat-value">{{ $stats['admins'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-danger"><div class="stat-label">Inactive</div><div class="stat-value">{{ $stats['inactive'] }}</div></div></div>
  </div>

  <div class="card mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-5"><label class="form-label">Search</label><input type="text" name="search" value="{{ $filters['search'] }}" class="form-control form-control-sm" placeholder="Name or email"></div>
      <div class="col-md-4"><label class="form-label">Role</label>
        <select name="role" class="form-select form-select-sm"><option value="">All roles</option>
          @foreach($roles as $k => $label)<option value="{{ $k }}" @selected($filters['role']===$k)>{{ $label }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-3 d-flex gap-1"><button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('users') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a></div>
    </form>
  </div></div>

  <div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Access</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        @forelse($users as $u)
          <tr>
            <td class="fw-semibold">{{ $u->name }}@if($u->department)<div class="text-muted-sm fw-normal">{{ $u->department }}</div>@endif</td>
            <td class="small">{{ $u->email }}</td>
            <td class="small">{{ $roles[$u->role] ?? ucfirst($u->role) }}</td>
            <td class="small">
              @if($u->role === 'super_admin')<span class="badge bg-warning-subtle text-warning-emphasis">Full access</span>
              @else
                <span class="badge bg-primary-subtle text-primary">{{ $u->roles->first()?->permissions->count() ?? 0 }} module(s)</span>
                @if($u->permissions->count())<span class="badge bg-info-subtle text-info-emphasis" title="Direct per-user permissions">+{{ $u->permissions->count() }} direct</span>@endif
              @endif
            </td>
            <td><span class="badge-status {{ $u->is_active ? 'badge-approved' : 'badge-cancelled' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-end">
              <div class="d-inline-flex gap-1">
                @php $payload = Illuminate\Support\Js::from([
                  'id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'role'=>$u->role,
                  'phone'=>$u->phone,'department'=>$u->department,'is_active'=>(bool)$u->is_active,
                  'permissions'=>$u->permissions->pluck('name')->values(),
                ]); @endphp
                <button class="btn btn-outline-primary btn-sm btn-icon" title="Set permissions" @click="openPermissions({{ $payload }})"><i class="bi bi-shield-lock"></i></button>
                <button class="btn btn-outline-secondary btn-sm btn-icon" title="Edit" @click="openEdit({{ $payload }})"><i class="bi bi-pencil"></i></button>
                @if($u->id !== auth()->id())
                <form method="POST" action="{{ route('users.destroy', $u) }}" @submit="return confirm('Remove {{ addslashes($u->name) }}?')">
                  @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm btn-icon" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted py-4">No users.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div></div>
  @if($users->hasPages())<div class="card-footer bg-transparent">{{ $users->links() }}</div>@endif
  </div>

  {{-- Add / Edit modal --}}
  <div class="modal fade" :class="{show:showModal}" :style="showModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form method="POST" :action="form.id ? editAction : '{{ route('users.store') }}'">
          @csrf
          <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
          <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-gear me-2 text-primary"></i><span x-text="form.id ? 'Edit User' : 'Add User'"></span></h5><button type="button" class="btn-close" @click="showModal=false"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Full name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control form-control-sm" x-model="form.name" required></div>
              <div class="col-md-6"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" name="email" class="form-control form-control-sm" x-model="form.email" required></div>
              <div class="col-md-3"><label class="form-label">Role</label>
                <select name="role" class="form-select form-select-sm" x-model="form.role">
                  @foreach($roles as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </select>
              </div>
              <div class="col-md-3"><label class="form-label">Status</label>
                <select name="is_active" class="form-select form-select-sm" x-model="form.is_active"><option value="1">Active</option><option value="0">Inactive</option></select>
              </div>
              <div class="col-md-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control form-control-sm" x-model="form.phone"></div>
              <div class="col-md-3"><label class="form-label">Password <span class="text-muted-sm" x-text="form.id ? '(blank=keep)' : '(optional)'"></span></label><input type="text" name="password" class="form-control form-control-sm" x-model="form.password" placeholder="Auto if blank"></div>

              <div class="col-12">
                <label class="form-label">Module access</label>
                <div x-show="form.role==='super_admin'" class="alert alert-warning py-2 small mb-0"><i class="bi bi-shield-check me-1"></i>Super Admin has full access to every module automatically.</div>
                <div x-show="form.role!=='super_admin'" class="alert alert-light border py-2 small mb-0">
                  <i class="bi bi-info-circle me-1"></i>Module access is determined by the assigned <strong>role</strong>. To change which modules a role can reach, edit it on the
                  <a href="{{ route('roles.permissions') }}">Permission Set</a> page.
                </div>
              </div>

              {{-- Per-user fine-grained permissions (in addition to the role) --}}
              <div class="col-12" x-show="form.role!=='super_admin'" x-ref="permSection">
                <label class="form-label d-flex align-items-center mb-1">
                  Direct permissions <span class="text-muted-sm ms-1">(per-user, on top of the role)</span>
                  <span class="ms-auto">
                    <button type="button" class="btn btn-link btn-sm p-0 me-2" @click="form.permissions = allPerms()">Select all</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-muted" @click="form.permissions = []">Clear</button>
                  </span>
                </label>
                <div class="border rounded-2" style="max-height:300px;overflow:auto">
                  <table class="table table-sm mb-0 align-middle">
                    <thead class="position-sticky top-0 bg-body" style="z-index:1">
                      <tr>
                        <th class="small" style="min-width:140px">Module</th>
                        @foreach($actions as $aKey => $aLabel)<th class="text-center small">{{ $aLabel }}</th>@endforeach
                        <th class="text-center small">All</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($modules as $mKey => $cfg)
                        <tr>
                          <td class="small fw-semibold">{{ $cfg['label'] }}</td>
                          @foreach($actions as $aKey => $aLabel)
                            <td class="text-center">
                              <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $mKey }}.{{ $aKey }}" x-model="form.permissions">
                            </td>
                          @endforeach
                          <td class="text-center">
                            <input class="form-check-input" type="checkbox" :checked="moduleAll('{{ $mKey }}')" @change="toggleModule('{{ $mKey }}', $event.target.checked)">
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
                <div class="text-muted-sm mt-1"><i class="bi bi-info-circle me-1"></i>Grants specific abilities (e.g. <code>products.edit</code>) directly to this user, on top of their role. Super Admins always have everything.</div>
              </div>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" @click="showModal=false">Cancel</button><button class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i><span x-text="form.id ? 'Update' : 'Save'"></span></button></div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showModal" @click="showModal=false" x-cloak></div>
</div>
@endsection

@push('scripts')
<script>
function usersApp(){
  return {
    showModal:false,
    updateTpl: '{{ url('users') }}/__ID__',
    // module key => [ "{module}.{action}", ... ]
    moduleMatrix: @js(collect($modules)->mapWithKeys(fn ($cfg, $k) => [$k => array_map(fn ($a) => "$k.$a", array_keys($actions))])->all()),
    form: {id:null, name:'', email:'', role:'distributor', phone:'', department:'', is_active:'1', password:'', permissions:[]},
    get editAction(){ return this.updateTpl.replace('__ID__', this.form.id); },
    openAdd(){ this.form={id:null, name:'', email:'', role:'distributor', phone:'', department:'', is_active:'1', password:'', permissions:[]}; this.showModal=true; },
    openEdit(u){ this.form={...u, is_active: u.is_active ? '1':'0', password:'', permissions:(u.permissions||[])}; this.showModal=true; },
    openPermissions(u){ this.openEdit(u); this.$nextTick(() => { const el=this.$refs.permSection; if(el) el.scrollIntoView({behavior:'smooth', block:'center'}); }); },
    allPerms(){ return Object.values(this.moduleMatrix).flat(); },
    moduleAll(m){ const a=this.moduleMatrix[m]||[]; return a.length>0 && a.every(p=>this.form.permissions.includes(p)); },
    toggleModule(m, on){ const a=this.moduleMatrix[m]||[]; this.form.permissions = on ? [...new Set([...this.form.permissions, ...a])] : this.form.permissions.filter(p=>!a.includes(p)); },
  };
}
</script>
@endpush
