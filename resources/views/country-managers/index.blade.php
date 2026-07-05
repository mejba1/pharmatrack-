@extends('layouts.app')
@section('title', 'Country Managers')

@section('content')
<style>.cm-opt:hover{background:#f1f5f9}</style>
@php $countriesJs = $countries->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->name, 'flag' => $c->flag])->values(); @endphp
<div x-data="cmApp(@js($countriesJs))">

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div><h1>Country Managers</h1><div class="page-breadcrumb">Sales / Country Managers</div></div>
    <div>@can('country_managers.create')<button class="btn btn-primary btn-sm" @click="openAdd()"><i class="bi bi-person-plus me-1"></i>Add Manager</button>@endcan</div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card stat-primary"><div class="stat-label">Managers</div><div class="stat-value">{{ $stats['total'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-success"><div class="stat-label">Active</div><div class="stat-value">{{ $stats['active'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-warning"><div class="stat-label">Country-assigned</div><div class="stat-value">{{ $stats['assigned'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card stat-info"><div class="stat-label">Countries covered</div><div class="stat-value">{{ $stats['countries'] }}</div></div></div>
  </div>

  <div class="card mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4"><label class="form-label">Search</label><input type="text" name="search" value="{{ $filters['search'] }}" class="form-control form-control-sm" placeholder="Name, email, phone"></div>
      <div class="col-md-3"><label class="form-label">Role</label>
        <select name="role" class="form-select form-select-sm"><option value="">All roles</option>
          @foreach($roles as $k => $label)<option value="{{ $k }}" @selected($filters['role']===$k)>{{ $label }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">Country</label>
        <select name="country_id" class="form-select form-select-sm"><option value="">All countries</option>
          @foreach($countries as $co)<option value="{{ $co->id }}" @selected((string)$filters['country_id']===(string)$co->id)>{{ $co->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-2 d-flex gap-1">
        <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel"></i></button>
        <a href="{{ route('country-managers.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
      </div>
    </form>
  </div></div>

  <div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Manager</th><th>Email</th><th>Role</th><th>Managed Country</th><th>Phone</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        @forelse($managers as $m)
          <tr>
            <td class="fw-semibold">{{ $m->name }}@if($m->department)<div class="text-muted-sm fw-normal">{{ $m->department }}</div>@endif</td>
            <td class="small">{{ $m->email }}</td>
            <td class="small">{{ $roles[$m->role] ?? ucfirst($m->role) }}</td>
            <td class="small">
              @forelse($m->countries as $co)<span class="badge bg-light text-dark border me-1 mb-1">{{ $co->flag }} {{ $co->name }}</span>@empty<span class="text-muted">—</span>@endforelse
            </td>
            <td class="small">{{ $m->phone ?? '—' }}</td>
            <td><span class="badge-status {{ $m->is_active ? 'badge-approved' : 'badge-cancelled' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-end">
              <div class="d-inline-flex gap-1">
                @can('country_managers.edit')<button class="btn btn-outline-secondary btn-sm btn-icon" title="Edit" @click="openEdit({{ Illuminate\Support\Js::from([
                  'id'=>$m->id,'name'=>$m->name,'email'=>$m->email,'role'=>$m->role,
                  'country_ids'=>$m->countries->pluck('id')->map(fn($i)=>(string)$i)->all(),
                  'phone'=>$m->phone,'department'=>$m->department,'is_active'=>(bool)$m->is_active,
                ]) }})"><i class="bi bi-pencil"></i></button>@endcan
                @can('country_managers.delete')
                <form method="POST" action="{{ route('country-managers.destroy', $m) }}" @submit="return confirm('Remove {{ addslashes($m->name) }}? (Deactivated instead if linked to orders.)')">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm btn-icon" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
                @endcan
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted py-4">No managers yet. Click <strong>Add Manager</strong>.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div></div>
  @if($managers->hasPages())<div class="card-footer bg-transparent">{{ $managers->links() }}</div>@endif
  </div>

  {{-- Add / Edit modal --}}
  <div class="modal fade" :class="{show:showModal}" :style="showModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" :action="form.id ? editAction : '{{ route('country-managers.store') }}'">
          @csrf
          <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
          <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-gear me-2 text-primary"></i><span x-text="form.id ? 'Edit Manager' : 'Add Country Manager'"></span></h5><button type="button" class="btn-close" @click="showModal=false"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Full name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control form-control-sm" x-model="form.name" required></div>
              <div class="col-md-6"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" name="email" class="form-control form-control-sm" x-model="form.email" required></div>
              <div class="col-md-6">
                <label class="form-label">Managed countries <span class="text-muted-sm">(add one or more)</span></label>
                <div class="border rounded-2 p-2" @click.away="cOpen=false">
                  <div class="d-flex flex-wrap gap-1" :class="form.country_ids.length ? 'mb-2' : ''">
                    <template x-for="cid in form.country_ids" :key="cid">
                      <span class="badge bg-primary-subtle text-primary border d-inline-flex align-items-center gap-1" style="font-size:12px">
                        <span x-text="countryLabel(cid)"></span>
                        <i class="bi bi-x-lg" style="cursor:pointer;font-size:10px" @click="removeCountry(cid)"></i>
                      </span>
                    </template>
                    <span x-show="!form.country_ids.length" class="text-muted-sm">No countries — global manager</span>
                  </div>
                  <div class="position-relative">
                    <input type="text" class="form-control form-control-sm" placeholder="Type a country to add…" x-model="cSearch" @focus="cOpen=true">
                    <div x-show="cOpen && availableCountries.length" x-cloak class="position-absolute bg-white border rounded-2 shadow-sm w-100 mt-1" style="max-height:170px;overflow:auto;z-index:5">
                      <template x-for="c in availableCountries" :key="c.id">
                        <div class="px-2 py-1 cm-opt" style="cursor:pointer;font-size:13px" @click="addCountry(c.id)" x-text="(c.flag ? c.flag + ' ' : '') + c.name"></div>
                      </template>
                    </div>
                  </div>
                </div>
                <template x-for="cid in form.country_ids" :key="'h'+cid"><input type="hidden" name="country_ids[]" :value="cid"></template>
                <div class="d-flex gap-2 mt-1">
                  <button type="button" class="btn btn-link btn-sm p-0 text-muted" @click="form.country_ids = countries.map(c=>c.id)">Select all</button>
                  <button type="button" class="btn btn-link btn-sm p-0 text-muted" x-show="form.country_ids.length" @click="form.country_ids=[]">Clear</button>
                </div>
              </div>
              <div class="col-md-3"><label class="form-label">Role</label>
                <select name="role" class="form-select form-select-sm" x-model="form.role">
                  @foreach($roles as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </select>
              </div>
              <div class="col-md-3"><label class="form-label">Status</label>
                <select name="is_active" class="form-select form-select-sm" x-model="form.is_active"><option value="1">Active</option><option value="0">Inactive</option></select>
              </div>
              <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control form-control-sm" x-model="form.phone"></div>
              <div class="col-md-4"><label class="form-label">Department</label><input type="text" name="department" class="form-control form-control-sm" x-model="form.department"></div>
              <div class="col-md-4"><label class="form-label">Password <span class="text-muted-sm" x-text="form.id ? '(blank = unchanged)' : '(optional)'"></span></label><input type="text" name="password" class="form-control form-control-sm" x-model="form.password" placeholder="Auto-generated if blank"></div>
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
function cmApp(countries){
  return {
    showModal:false, countries: countries || [], cSearch:'', cOpen:false,
    updateTpl: '{{ url('country-managers') }}/__ID__',
    form: {id:null, name:'', email:'', country_ids:[], role:'distributor', phone:'', department:'', is_active:'1', password:''},
    get editAction(){ return this.updateTpl.replace('__ID__', this.form.id); },
    openAdd(){ this.form={id:null, name:'', email:'', country_ids:[], role:'distributor', phone:'', department:'', is_active:'1', password:''}; this.cSearch=''; this.showModal=true; },
    openEdit(m){ this.form={...m, is_active: m.is_active ? '1' : '0', country_ids: (m.country_ids||[]).map(String), password:''}; this.cSearch=''; this.showModal=true; },
    countryLabel(id){ const c = this.countries.find(c => c.id === String(id)); return c ? ((c.flag ? c.flag + ' ' : '') + c.name) : id; },
    get availableCountries(){
      const q = this.cSearch.toLowerCase();
      return this.countries.filter(c => !this.form.country_ids.includes(c.id) && (!q || c.name.toLowerCase().includes(q))).slice(0, 50);
    },
    addCountry(id){ if(!this.form.country_ids.includes(id)) this.form.country_ids.push(id); this.cSearch=''; },
    removeCountry(id){ this.form.country_ids = this.form.country_ids.filter(c => c !== id); },
  };
}
</script>
@endpush
