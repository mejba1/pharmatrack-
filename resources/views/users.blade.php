@extends('layouts.app')
@section('title', 'Users & Roles')

@section('content')
<div x-data="usersPage()">

  <div class="page-header">
    <div><h1>Users &amp; Roles</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Users &amp; Roles</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-person-plus me-1"></i>Add User</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-value" x-text="users.length">0</div><div class="stat-label">Total Users</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-person-check"></i></div><div><div class="stat-value" x-text="users.filter(u=>u.status==='Active').length">0</div><div class="stat-label">Active Users</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-person-lock"></i></div><div><div class="stat-value" x-text="users.filter(u=>u.status==='Inactive').length">0</div><div class="stat-label">Inactive</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-shield-lock"></i></div><div><div class="stat-value">5</div><div class="stat-label">Role Types</div></div></div></div>
  </div>

  <!-- Tabs -->
  <div class="pills-nav mb-3">
    <button class="pill-btn" :class="{active: tab==='users'}" @click="tab='users'"><i class="bi bi-people me-1"></i>Users</button>
    <button class="pill-btn" :class="{active: tab==='roles'}" @click="tab='roles'"><i class="bi bi-shield-lock me-1"></i>Roles &amp; Permissions</button>
  </div>

  <!-- Users Table -->
  <div x-show="tab==='users'">
    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="row g-2 align-items-center">
          <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control form-control-sm" placeholder="Search by name, email..." x-model="search"></div></div>
          <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterRole"><option value="">All Roles</option><option>Admin</option><option>Manager</option><option>Logistics</option><option>Finance</option><option>Viewer</option></select></div>
          <div class="col-6 col-md-2"><select class="form-select form-select-sm" x-model="filterStatus"><option value="">All Status</option><option>Active</option><option>Inactive</option></select></div>
          <div class="col-6 col-md-2 d-flex gap-1">
            <button class="btn btn-primary btn-sm flex-fill">Filter</button>
            <button class="btn btn-outline-secondary btn-sm" @click="search='';filterRole='';filterStatus=''"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>
      </div>
    </div>

    <div class="card table-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Name</th><th>Email</th><th>Role</th><th>Country Access</th><th>Last Login</th><th>2FA</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <template x-for="u in filteredUsers" :key="u.id">
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:13px;font-weight:600;flex-shrink:0" x-text="u.name.split(' ').map(w=>w[0]).join('').slice(0,2)"></div>
                      <div><div class="fw-semibold" style="font-size:13px" x-text="u.name"></div><div class="text-muted-sm" x-text="u.department"></div></div>
                    </div>
                  </td>
                  <td style="font-size:12px" x-text="u.email"></td>
                  <td>
                    <span class="badge" :class="{
                      'bg-danger text-white':u.role==='Admin',
                      'bg-primary text-white':u.role==='Manager',
                      'bg-info text-white':u.role==='Logistics',
                      'bg-warning text-dark':u.role==='Finance',
                      'bg-secondary text-white':u.role==='Viewer'
                    }" style="font-size:11px" x-text="u.role"></span>
                  </td>
                  <td style="font-size:12px" x-text="u.access"></td>
                  <td style="font-size:12px" x-text="u.lastLogin"></td>
                  <td>
                    <i x-show="u.twofa" class="bi bi-shield-check text-success"></i>
                    <i x-show="!u.twofa" class="bi bi-shield text-muted"></i>
                  </td>
                  <td><span class="badge-status" :class="u.status==='Active' ? 'badge-approved' : 'badge-pending'" x-text="u.status"></span></td>
                  <td>
                    <div class="d-flex gap-1">
                      <button class="btn btn-outline-primary btn-sm btn-icon" @click="viewUser(u)"><i class="bi bi-eye"></i></button>
                      <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                      <button x-show="u.status==='Active'" class="btn btn-outline-warning btn-sm btn-icon" title="Deactivate"><i class="bi bi-person-dash"></i></button>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Roles & Permissions -->
  <div x-show="tab==='roles'">
    <div class="row g-3">
      <template x-for="role in roles" :key="role.name">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="stat-icon" :class="'stat-' + role.colorClass" style="width:40px;height:40px;font-size:18px;flex-shrink:0"><i class="bi bi-shield-lock"></i></div>
                <div>
                  <div class="fw-semibold" style="font-size:15px" x-text="role.name"></div>
                  <div class="text-muted-sm" x-text="users.filter(u=>u.role===role.name).length + ' users assigned'"></div>
                </div>
                <button class="btn btn-outline-secondary btn-sm ms-auto"><i class="bi bi-pencil me-1"></i>Edit</button>
              </div>
              <div class="row g-1">
                <template x-for="perm in role.permissions" :key="perm.label">
                  <div class="col-6">
                    <div class="d-flex align-items-center gap-2" style="font-size:12px">
                      <i :class="perm.allowed ? 'bi-check-circle text-success' : 'bi-x-circle text-danger'" class="bi"></i>
                      <span x-text="perm.label"></span>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- View User Modal -->
  <div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content" x-show="selectedUser">
        <div class="modal-header"><h5 class="modal-title fw-semibold" x-text="selectedUser?.name"></h5><button class="btn-close" @click="showViewModal=false"></button></div>
        <div class="modal-body">
          <div class="text-center mb-3">
            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mx-auto" style="width:64px;height:64px;font-size:24px;font-weight:700" x-text="selectedUser?.name.split(' ').map(w=>w[0]).join('').slice(0,2)"></div>
            <div class="fw-semibold mt-2" x-text="selectedUser?.name"></div>
            <div class="text-muted-sm" x-text="selectedUser?.email"></div>
          </div>
          <div class="row g-3">
            <div class="col-6"><div class="p-3 bg-light rounded-3"><div class="text-muted-sm">Role</div><span class="badge bg-primary text-white" x-text="selectedUser?.role"></span></div></div>
            <div class="col-6"><div class="p-3 bg-light rounded-3"><div class="text-muted-sm">Status</div><span class="badge-status" :class="selectedUser?.status==='Active' ? 'badge-approved' : 'badge-pending'" x-text="selectedUser?.status"></span></div></div>
            <div class="col-12"><label class="form-label">Department</label><div x-text="selectedUser?.department"></div></div>
            <div class="col-12"><label class="form-label">Country Access</label><div x-text="selectedUser?.access"></div></div>
            <div class="col-6"><label class="form-label">Last Login</label><div style="font-size:13px" x-text="selectedUser?.lastLogin"></div></div>
            <div class="col-6"><label class="form-label">2FA Enabled</label><div><i :class="selectedUser?.twofa ? 'bi-shield-check text-success' : 'bi-shield text-muted'" class="bi me-1"></i><span x-text="selectedUser?.twofa ? 'Enabled' : 'Disabled'"></span></div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
          <button class="btn btn-outline-warning btn-sm"><i class="bi bi-key me-1"></i>Reset Password</button>
          <button class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit User</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  <!-- Add User Modal -->
  <div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-person-plus me-2 text-primary"></i>Add New User</h5><button class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Email Address <span class="text-danger">*</span></label><input type="email" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Role <span class="text-danger">*</span></label><select class="form-select"><option>Admin</option><option>Manager</option><option>Logistics</option><option>Finance</option><option>Viewer</option></select></div>
            <div class="col-md-6"><label class="form-label">Department</label><input type="text" class="form-control" placeholder="e.g. Operations"></div>
            <div class="col-md-6"><label class="form-label">Country Access</label><select class="form-select"><option>All Countries</option><option>Philippines</option><option>Nigeria</option><option>Bangladesh</option><option>Kenya</option></select></div>
            <div class="col-md-6"><label class="form-label">Initial Password</label><input type="password" class="form-control" placeholder="Temporary password"></div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="send_invite" checked>
                <label class="form-check-label" for="send_invite">Send invitation email with login credentials</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showAddModal=false">Cancel</button>
          <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create User</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function usersPage() {
  return {
    tab:'users', search:'', filterRole:'', filterStatus:'', showAddModal:false, showViewModal:false, selectedUser:null,
    users:[
      { id:1, name:'Admin User',        email:'admin@pharmatrack.com',   role:'Admin',     department:'IT / System',   access:'All Countries',  lastLogin:'Jun 10, 2026 · 09:05', twofa:true,  status:'Active'   },
      { id:2, name:'Sarah Johnson',     email:'sarah@pharmatrack.com',   role:'Manager',   department:'Operations',    access:'All Countries',  lastLogin:'Jun 10, 2026 · 08:42', twofa:true,  status:'Active'   },
      { id:3, name:'Logistics Team',    email:'ops@pharmatrack.com',     role:'Logistics', department:'Logistics',     access:'PH, NG, BD, KE', lastLogin:'Jun 9, 2026  · 14:31', twofa:false, status:'Active'   },
      { id:4, name:'Finance Team',      email:'finance@pharmatrack.com', role:'Finance',   department:'Finance',       access:'All Countries',  lastLogin:'Jun 9, 2026  · 10:14', twofa:true,  status:'Active'   },
      { id:5, name:'Compliance Officer',email:'comply@pharmatrack.com',  role:'Manager',   department:'Regulatory',    access:'All Countries',  lastLogin:'Jun 8, 2026  · 16:52', twofa:true,  status:'Active'   },
      { id:6, name:'Viewer Account',    email:'viewer@pharmatrack.com',  role:'Viewer',    department:'Guest / Audit', access:'Read-only',      lastLogin:'May 20, 2026',          twofa:false, status:'Inactive' },
    ],
    roles:[
      { name:'Admin',     colorClass:'danger', permissions:[
          {label:'All Modules',       allowed:true },{label:'User Management',  allowed:true },
          {label:'System Settings',   allowed:true },{label:'Delete Records',   allowed:true },
          {label:'Financial Data',    allowed:true },{label:'Export/Import',    allowed:true },
      ]},
      { name:'Manager',   colorClass:'primary', permissions:[
          {label:'All Modules',       allowed:true },{label:'User Management',  allowed:false},
          {label:'System Settings',   allowed:false},{label:'Delete Records',   allowed:false},
          {label:'Financial Data',    allowed:true },{label:'Export/Import',    allowed:true },
      ]},
      { name:'Logistics', colorClass:'info', permissions:[
          {label:'Orders / Shipments',allowed:true },{label:'Products/Batches', allowed:true },
          {label:'Distribution',      allowed:true },{label:'User Management',  allowed:false},
          {label:'Financial Data',    allowed:false},{label:'Export/Import',    allowed:true },
      ]},
      { name:'Finance',   colorClass:'warning', permissions:[
          {label:'PI / CI View',      allowed:true },{label:'Financial Reports', allowed:true},
          {label:'Orders View',       allowed:true },{label:'User Management',  allowed:false},
          {label:'System Settings',   allowed:false},{label:'Delete Records',   allowed:false},
      ]},
      { name:'Viewer',    colorClass:'secondary', permissions:[
          {label:'Read-only access',  allowed:true },{label:'Export',           allowed:false},
          {label:'Create Records',    allowed:false},{label:'Modify Records',   allowed:false},
          {label:'Financial Data',    allowed:false},{label:'User Management',  allowed:false},
      ]},
    ],
    viewUser(u){ this.selectedUser=u; this.showViewModal=true; },
    get filteredUsers(){
      const q=this.search.toLowerCase();
      return this.users.filter(u=>
        (!q||u.name.toLowerCase().includes(q)||u.email.toLowerCase().includes(q))
        && (!this.filterRole||u.role===this.filterRole)
        && (!this.filterStatus||u.status===this.filterStatus)
      );
    }
  };
}
</script>
@endpush
