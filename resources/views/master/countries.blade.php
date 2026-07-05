@extends('layouts.app')
@section('title', 'Countries')

@section('content')
<div x-data="{ editOpen:false, ed:{id:null,name:'',code:'',dial_code:'',flag:'',region:'',regulatory_status:'approved'}, openEdit(c){ this.ed={...c}; this.editOpen=true } }">

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
      <h1>Countries</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Master Data / Countries</div>
    </div>
  </div>

  <div class="row g-3">
    {{-- Add form --}}
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-3"><i class="bi bi-plus-circle me-1 text-primary"></i>Add Country</h6>
          <form method="POST" action="{{ route('master.countries.store') }}">
            @csrf
            <div class="mb-2">
              <label class="form-label">Country Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                     placeholder="e.g. United States" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Code <span class="text-muted">(optional)</span></label>
              <input type="text" name="code" class="form-control" value="{{ old('code') }}"
                     placeholder="Auto if blank" maxlength="3" style="text-transform:uppercase">
              <div class="form-text" style="font-size:10px">Up to 3 chars · auto-generated if left blank</div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label">Dial Code</label>
                <input type="text" name="dial_code" class="form-control" value="{{ old('dial_code') }}"
                       placeholder="e.g. +1" maxlength="10">
              </div>
              <div class="col-6">
                <label class="form-label">Flag <span class="text-muted">(emoji)</span></label>
                <input type="text" name="flag" class="form-control" value="{{ old('flag') }}"
                       placeholder="Auto from code" maxlength="16">
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label">Region</label>
              <input type="text" name="region" class="form-control" value="{{ old('region') }}"
                     placeholder="e.g. North America">
            </div>
            <div class="mb-3">
              <label class="form-label">Regulatory Status</label>
              <select name="regulatory_status" class="form-select">
                <option value="approved">Approved</option>
                <option value="restricted">Restricted</option>
                <option value="pending">Pending</option>
                <option value="banned">Banned</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-check-lg me-1"></i>Add Country
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
                  <th style="width:44px">Flag</th>
                  <th>Code</th>
                  <th>Dial</th>
                  <th>Name</th>
                  <th>Region</th>
                  <th>Status</th>
                  <th>Products</th>
                  <th style="width:60px"></th>
                </tr>
              </thead>
              <tbody>
                @forelse($countries as $country)
                <tr>
                  <td style="font-size:18px">{{ $country->flag ?? '—' }}</td>
                  <td><code>{{ $country->code }}</code></td>
                  <td style="font-size:13px">{{ $country->dial_code ?? '—' }}</td>
                  <td class="fw-semibold" style="font-size:13px">{{ $country->name }}</td>
                  <td style="font-size:13px">{{ $country->region ?? '—' }}</td>
                  <td><span class="badge bg-light text-secondary border">{{ ucfirst($country->regulatory_status) }}</span></td>
                  <td><span class="badge bg-primary rounded-pill">{{ $country->product_registrations_count }}</span></td>
                  <td>
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-outline-secondary btn-sm btn-icon" title="Edit"
                              @click="openEdit({ id: {{ $country->id }}, name: @js($country->name), code: @js($country->code), dial_code: @js($country->dial_code ?? ''), flag: @js($country->flag ?? ''), region: @js($country->region ?? ''), regulatory_status: @js($country->regulatory_status) })">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <form method="POST" action="{{ route('master.countries.destroy', $country) }}"
                            onsubmit="return confirm('Remove country {{ addslashes($country->name) }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm btn-icon" title="Remove">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-globe2" style="font-size:32px;opacity:.2"></i>
                  <div class="mt-2">No countries yet. Add your first one on the left.</div>
                </td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($countries->hasPages())
          <div class="px-3 py-2 border-top">{{ $countries->links('pagination::bootstrap-5') }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Edit modal --}}
  <div class="modal fade" :class="{show:editOpen}" :style="editOpen?'display:block':''" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" :action="`{{ url('master/countries') }}/${ed.id}`">
          @csrf @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title fw-semibold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Country</h5>
            <button type="button" class="btn-close" @click="editOpen=false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Country Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" x-model="ed.name" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control" x-model="ed.code" maxlength="3"
                     style="text-transform:uppercase" required>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label">Dial Code</label>
                <input type="text" name="dial_code" class="form-control" x-model="ed.dial_code"
                       placeholder="e.g. +1" maxlength="10">
              </div>
              <div class="col-6">
                <label class="form-label">Flag <span class="text-muted">(emoji)</span></label>
                <input type="text" name="flag" class="form-control" x-model="ed.flag"
                       placeholder="Auto from code" maxlength="16">
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label">Region</label>
              <input type="text" name="region" class="form-control" x-model="ed.region">
            </div>
            <div class="mb-2">
              <label class="form-label">Regulatory Status</label>
              <select name="regulatory_status" class="form-select" x-model="ed.regulatory_status">
                <option value="approved">Approved</option>
                <option value="restricted">Restricted</option>
                <option value="pending">Pending</option>
                <option value="banned">Banned</option>
              </select>
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
