@extends('layouts.app')
@section('title', 'Batch & Lot Management')

@push('styles')
<style>
/* Scrollable modal with a <form> wrapping body + footer (footer stays pinned) */
.modal-dialog-scrollable > .modal-content { max-height: calc(100vh - 3.5rem); overflow: hidden; }
.modal-content > form { display:flex; flex-direction:column; flex:1 1 auto; min-height:0; overflow:hidden; }
.modal-content > form > .modal-body   { flex:1 1 auto; overflow-y:auto; min-height:0; }
.modal-content > form > .modal-footer { flex-shrink:0; }
.section-label { font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
  color:#6c757d; padding-bottom:4px; border-bottom:1px solid var(--border-color,#dee2e6); }
</style>
@endpush

@section('content')
<div x-data="batchesPage()">

  {{-- Flash --}}
  @if(session('success'))
  <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill text-success"></i><span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif
  @if(session('error'))
  <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill text-danger"></i><span>{{ session('error') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1>Batch &amp; Lot Management</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Batch &amp; Lot Mgmt</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary btn-sm" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>New Batch</button>
    </div>
  </div>

  {{-- Stats --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-layers"></i></div><div><div class="stat-value">{{ number_format($stats['total']) }}</div><div class="stat-label">Total Batches</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-value">{{ number_format($stats['released']) }}</div><div class="stat-label">QC Released</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div><div><div class="stat-value">{{ number_format($stats['expiring']) }}</div><div class="stat-label">Expiring &lt; 90 Days</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div><div class="stat-value">{{ number_format($stats['recalled']) }}</div><div class="stat-label">Recalled</div></div></div></div>
  </div>

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <form method="GET" action="{{ route('batches') }}">
        <div class="row g-2 align-items-center">
          <div class="col-md-3">
            <select name="product_id" class="form-select form-select-sm">
              <option value="">All Products</option>
              @foreach($products as $p)
                <option value="{{ $p->id }}" {{ (string)($filters['product_id'] ?? '')===(string)$p->id ? 'selected':'' }}>{{ $p->name }} ({{ $p->prn }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <div class="search-wrapper"><i class="bi bi-search search-icon"></i>
              <input type="text" name="search" class="form-control form-control-sm" placeholder="BRN / batch ref, lot no…" value="{{ $filters['search'] ?? '' }}">
            </div>
          </div>
          <div class="col-6 col-md-2">
            <select name="qc_status" class="form-select form-select-sm">
              <option value="">All QC Status</option>
              @foreach(['pending'=>'Pending','released'=>'Released','quarantine'=>'Quarantine','rejected'=>'Rejected','recalled'=>'Recalled'] as $v=>$l)
                <option value="{{ $v }}" {{ ($filters['qc_status'] ?? '')===$v ? 'selected':'' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="status" class="form-select form-select-sm">
              <option value="">All Status</option>
              @foreach(['active'=>'Active','expired'=>'Expired','recalled'=>'Recalled','quarantine'=>'Quarantine','depleted'=>'Depleted'] as $v=>$l)
                <option value="{{ $v }}" {{ ($filters['status'] ?? '')===$v ? 'selected':'' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="expiry" class="form-select form-select-sm">
              <option value="">Expiry: All</option>
              <option value="30"  {{ ($filters['expiry'] ?? '')==='30'  ? 'selected':'' }}>&lt; 30 Days</option>
              <option value="90"  {{ ($filters['expiry'] ?? '')==='90'  ? 'selected':'' }}>&lt; 90 Days</option>
              <option value="180" {{ ($filters['expiry'] ?? '')==='180' ? 'selected':'' }}>&lt; 180 Days</option>
            </select>
          </div>
          <div class="col-6 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ route('batches') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- Table --}}
  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>BRN</th><th>Product</th><th>Batch / Lot</th><th>Manufactured</th>
              <th>Expiry</th><th>Produced</th><th>Available</th><th>QC</th><th>Status</th>
              <th style="width:110px">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($batches as $batch)
            <tr>
              <td><span class="text-primary fw-semibold" style="font-size:12px;cursor:pointer" @click="openView({{ $batch->id }})">{{ $batch->brn }}</span></td>
              <td>
                <div class="fw-semibold" style="font-size:13px">{{ $batch->product?->name ?? '—' }}</div>
                <div class="text-muted-sm">{{ $batch->product?->prn }}</div>
              </td>
              <td style="font-size:13px">{{ $batch->batch_number }}<div class="text-muted-sm">{{ $batch->lot_number ?? '—' }}</div></td>
              <td style="font-size:13px">{{ optional($batch->manufacture_date)->format('M d, Y') }}</td>
              <td>
                @php $d = $batch->days_to_expiry; @endphp
                <div style="font-size:13px" class="{{ $d!==null && $d < 30 ? 'text-danger fw-semibold' : ($d!==null && $d < 90 ? 'text-warning fw-semibold' : '') }}">
                  {{ optional($batch->expiry_date)->format('M d, Y') }}
                </div>
                @if($d !== null)<div class="text-muted-sm">{{ $d < 0 ? 'expired' : $d.' days left' }}</div>@endif
              </td>
              <td style="font-size:13px">{{ number_format($batch->quantity_produced) }}</td>
              <td style="font-size:13px">{{ number_format($batch->quantity_available) }}</td>
              <td><span class="badge-status {{ $batch->qc_badge_class }}">{{ $batch->qc_status_label }}</span></td>
              <td><span class="badge-status {{ $batch->status_badge_class }}">{{ $batch->status_label }}</span></td>
              <td>
                <div class="d-flex gap-1">
                  <button class="btn btn-outline-primary btn-sm btn-icon" title="View" @click="openView({{ $batch->id }})"><i class="bi bi-eye"></i></button>
                  <a href="{{ route('batches.units', $batch) }}" class="btn btn-outline-info btn-sm btn-icon" title="Serialized units ({{ number_format($batch->units_count) }})"><i class="bi bi-upc-scan"></i></a>
                  <button class="btn btn-outline-secondary btn-sm btn-icon" title="Edit" @click="openEdit({{ $batch->id }})"><i class="bi bi-pencil"></i></button>
                  <form method="POST" action="{{ route('batches.destroy', $batch) }}" @submit.prevent="confirmDelete($event, '{{ $batch->brn }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm btn-icon" title="Remove"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center py-5 text-muted">
              <i class="bi bi-layers" style="font-size:32px;opacity:.2"></i>
              <div class="mt-2">No batches found. Click <strong>New Batch</strong> to add one.</div>
            </td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($batches->hasPages())
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top flex-wrap gap-2">
        <div class="text-muted-sm">Showing <strong>{{ $batches->firstItem() }}–{{ $batches->lastItem() }}</strong> of <strong>{{ $batches->total() }}</strong> batches</div>
        {{ $batches->links('pagination::bootstrap-5') }}
      </div>
      @else
      <div class="px-3 py-2 border-top text-muted-sm">Showing all <strong>{{ $batches->total() }}</strong> batches</div>
      @endif
    </div>
  </div>

  {{-- ═══════════ VIEW MODAL ═══════════ --}}
  <div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-semibold" x-text="viewBatch?.brn ?? 'Loading…'"></h5>
            <code class="text-muted-sm" x-text="viewBatch?.product_name"></code>
          </div>
          <button class="btn-close ms-auto" @click="showViewModal=false"></button>
        </div>
        <div class="modal-body">
          <div x-show="viewLoading" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>
          <div x-show="!viewLoading && viewBatch" class="row g-3">
            <div class="col-md-4"><label class="form-label text-muted-sm">Batch Number</label><div class="fw-semibold" x-text="viewBatch?.batch_number"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">Lot Number</label><div x-text="viewBatch?.lot_number || '—'"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">Product PRN</label><div class="font-monospace" x-text="viewBatch?.product_prn || '—'"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">Manufactured</label><div x-text="fmtDate(viewBatch?.manufacture_date)"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">Expiry</label><div x-text="fmtDate(viewBatch?.expiry_date)"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">Days to Expiry</label><div x-text="viewBatch?.days_to_expiry !== null ? viewBatch?.days_to_expiry + ' days' : '—'"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">Qty Produced</label><div x-text="(viewBatch?.quantity_produced ?? 0).toLocaleString()"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">Qty Available</label><div x-text="(viewBatch?.quantity_available ?? 0).toLocaleString()"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">QC Status</label><div><span class="badge-status" :class="viewBatch?.qc_badge_class" x-text="viewBatch?.qc_status_label"></span></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">Status</label><div><span class="badge-status" :class="viewBatch?.status_badge_class" x-text="viewBatch?.status_label"></span></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">QC Approved By</label><div x-text="viewBatch?.qc_approved_by || '—'"></div></div>
            <div class="col-md-4"><label class="form-label text-muted-sm">QC Approval Date</label><div x-text="fmtDate(viewBatch?.qc_approval_date)"></div></div>
            <div class="col-md-6"><label class="form-label text-muted-sm">Manufacturing Site</label><div x-text="viewBatch?.manufacturing_site || '—'"></div></div>
            <div class="col-md-6"><label class="form-label text-muted-sm">Manufacturing Country</label><div x-text="viewBatch?.manufacturing_country || '—'"></div></div>
            <div class="col-md-6"><label class="form-label text-muted-sm">Storage Conditions</label><div x-text="viewBatch?.storage_conditions || '—'"></div></div>
            <div class="col-md-6"><label class="form-label text-muted-sm">Storage Temp Range</label>
              <div x-text="(viewBatch?.storage_temp_min ?? '—') + ' … ' + (viewBatch?.storage_temp_max ?? '—') + ' °C'"></div></div>
            <div class="col-md-6" x-show="viewBatch?.coa_url">
              <label class="form-label text-muted-sm">Certificate of Analysis</label>
              <div><a :href="viewBatch?.coa_url" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i><span x-text="viewBatch?.coa_name"></span></a></div>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted-sm">Serialized Units</label>
              <div>
                <span class="fw-semibold" x-text="(viewBatch?.units_count ?? 0).toLocaleString()"></span>
                <a :href="`{{ url('batches') }}/${viewBatch?.id}/units`" class="ms-2 text-primary"><i class="bi bi-upc-scan me-1"></i>View units →</a>
              </div>
            </div>
            <div class="col-12" x-show="viewBatch?.notes"><label class="form-label text-muted-sm">Notes</label><div style="font-size:13px" x-text="viewBatch?.notes"></div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
          <button class="btn btn-primary btn-sm" @click="showViewModal=false; openEdit(viewBatch?.id)"><i class="bi bi-pencil me-1"></i>Edit</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  {{-- ═══════════ ADD MODAL ═══════════ --}}
  <div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>New Batch</h5>
          <button class="btn-close" @click="showAddModal=false; resetAdd()"></button>
        </div>
        <form x-ref="addForm" method="POST" action="{{ route('batches.store') }}" enctype="multipart/form-data" @submit.prevent="submitAddForm()">
          @csrf
          <div class="modal-body">
            <template x-if="Object.keys(addErrors).length">
              <div class="alert alert-danger mb-3"><strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the errors below.</strong>
                <ul class="mb-0 mt-1 ps-3" style="font-size:13px"><template x-for="(msgs,f) in addErrors" :key="f"><template x-for="m in msgs" :key="m"><li x-text="m"></li></template></template></ul></div>
            </template>
            @include('partials.batch-form-fields', ['mode' => 'add'])
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showAddModal=false; resetAdd()">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
              <i class="bi bi-check-lg me-1" x-show="!saving"></i><span x-text="saving ? 'Saving…' : 'Create Batch'"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false; resetAdd()"></div>

  {{-- ═══════════ EDIT MODAL ═══════════ --}}
  <div class="modal fade" :class="{show:showEditModal}" :style="showEditModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-semibold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Batch</h5>
          <code class="text-muted-sm ms-2" x-text="editBatch?.brn"></code>
          <button class="btn-close ms-auto" @click="showEditModal=false"></button>
        </div>
        <div x-show="editLoading" class="text-center py-5 text-muted modal-body"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>
        <form x-show="!editLoading" x-ref="editForm" method="POST" :action="`{{ url('batches') }}/${editBatch?.id}`" enctype="multipart/form-data" @submit.prevent="submitEditForm()">
          @csrf
          <input type="hidden" name="_method" value="PUT">
          <div class="modal-body">
            <template x-if="Object.keys(editErrors).length">
              <div class="alert alert-danger mb-3"><strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the errors below.</strong>
                <ul class="mb-0 mt-1 ps-3" style="font-size:13px"><template x-for="(msgs,f) in editErrors" :key="f"><template x-for="m in msgs" :key="m"><li x-text="m"></li></template></template></ul></div>
            </template>
            @include('partials.batch-form-fields', ['mode' => 'edit'])
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showEditModal=false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
              <i class="bi bi-check-lg me-1" x-show="!saving"></i><span x-text="saving ? 'Updating…' : 'Update Batch'"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showEditModal" @click="showEditModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function batchesPage() {
  return {
    showAddModal:false, showViewModal:false, showEditModal:false,
    viewBatch:null, editBatch:null, viewLoading:false, editLoading:false,
    saving:false, addErrors:{}, editErrors:{},

    fmtDate(d){ if(!d) return '—'; const x=new Date(d); return isNaN(x)? '—' : x.toLocaleDateString(undefined,{year:'numeric',month:'short',day:'numeric'}); },

    openView(id){
      this.viewBatch=null; this.viewLoading=true; this.showViewModal=true;
      this._fetch(id).then(d=>{ this.viewBatch=d; this.viewLoading=false; });
    },
    openEdit(id){
      this.editBatch=null; this.editLoading=true; this.editErrors={}; this.saving=false; this.showEditModal=true;
      this._fetch(id).then(d=>{ this.editBatch=d; this.editLoading=false; });
    },
    _fetch(id){
      return fetch(`{{ url('batches') }}/${id}`, { headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} })
        .then(r=>{ if(!r.ok) throw new Error(); return r.json(); })
        .catch(()=>{ alert('Could not load batch.'); return {}; });
    },

    async submitAddForm(){
      this.saving=true; this.addErrors={};
      await this.$nextTick();
      try {
        const res = await fetch('{{ route("batches.store") }}', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, body:new FormData(this.$refs.addForm) });
        const data = await res.json();
        if(data.success){ window.location.href = data.redirect; }
        else { this.addErrors = data.errors ?? {}; this.saving=false; }
      } catch(e){ alert('Server error. Please try again.'); this.saving=false; }
    },
    async submitEditForm(){
      this.saving=true; this.editErrors={};
      await this.$nextTick();
      const fd = new FormData(this.$refs.editForm); fd.set('_method','PUT');
      try {
        const res = await fetch(`{{ url('batches') }}/${this.editBatch.id}`, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, body:fd });
        const data = await res.json();
        if(data.success){ window.location.href = data.redirect; }
        else { this.editErrors = data.errors ?? {}; this.saving=false; }
      } catch(e){ alert('Server error. Please try again.'); this.saving=false; }
    },

    resetAdd(){ this.addErrors={}; this.saving=false; if(this.$refs.addForm) this.$refs.addForm.reset(); },
    confirmDelete(ev, brn){ if(confirm(`Remove batch "${brn}"?\n\nThis will soft-delete the record.`)) ev.target.submit(); },
  };
}
</script>
@endpush
