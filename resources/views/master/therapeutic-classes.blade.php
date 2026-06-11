@extends('layouts.app')
@section('title', 'Therapeutic Classes')

@section('content')
<div x-data="{ editOpen:false, ed:{id:null,name:'',description:''}, openEdit(c){ this.ed={...c}; this.editOpen=true } }">

  {{-- Flash --}}
  @if(session('success'))
  <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill text-success"></i>
    <span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif
  @if(session('error'))
  <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
    <span>{{ session('error') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif
  @if($errors->any())
  <div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">
      @foreach($errors->all() as $e)<li style="font-size:13px">{{ $e }}</li>@endforeach
    </ul>
  </div>
  @endif

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1>Therapeutic Classes</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Master Data / Therapeutic Classes</div>
    </div>
  </div>

  <div class="row g-3">
    {{-- Add form --}}
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-3"><i class="bi bi-plus-circle me-1 text-primary"></i>Add Therapeutic Class</h6>
          <form method="POST" action="{{ route('master.tclasses.store') }}">
            @csrf
            <div class="mb-2">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                     placeholder="e.g. Antibiotics / Antimicrobials" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Description <span class="text-muted">(optional)</span></label>
              <input type="text" name="description" class="form-control" value="{{ old('description') }}"
                     placeholder="Short note">
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-check-lg me-1"></i>Add Class
            </button>
          </form>
        </div>
      </div>
    </div>

    {{-- List --}}
    <div class="col-md-8">
      <div class="card table-card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Description</th>
                  <th style="width:60px"></th>
                </tr>
              </thead>
              <tbody>
                @forelse($classes as $class)
                <tr>
                  <td class="fw-semibold" style="font-size:13px">{{ $class->name }}</td>
                  <td style="font-size:13px">{{ $class->description ?? '—' }}</td>
                  <td>
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-outline-secondary btn-sm btn-icon" title="Edit"
                              @click="openEdit({ id: {{ $class->id }}, name: @js($class->name), description: @js($class->description ?? '') })">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <form method="POST" action="{{ route('master.tclasses.destroy', $class) }}"
                            onsubmit="return confirm('Remove therapeutic class {{ addslashes($class->name) }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm btn-icon" title="Remove">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-5 text-muted">
                  <i class="bi bi-tags" style="font-size:32px;opacity:.2"></i>
                  <div class="mt-2">No therapeutic classes yet. Add your first one on the left.</div>
                </td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($classes->hasPages())
          <div class="px-3 py-2 border-top">{{ $classes->links('pagination::bootstrap-5') }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Edit modal --}}
  <div class="modal fade" :class="{show:editOpen}" :style="editOpen?'display:block':''" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" :action="`{{ url('master/therapeutic-classes') }}/${ed.id}`">
          @csrf @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title fw-semibold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Therapeutic Class</h5>
            <button type="button" class="btn-close" @click="editOpen=false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" x-model="ed.name" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Description</label>
              <input type="text" name="description" class="form-control" x-model="ed.description">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="editOpen=false">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="editOpen" @click="editOpen=false"></div>

</div>
@endsection
