@extends('layouts.app')
@section('title', 'Product Master')

@section('content')
<div x-data="productsPage()">

  {{-- ── Flash Messages ──────────────────────────────────────────────── --}}
  @if(session('success'))
  <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="bi bi-check-circle-fill text-success"></i>
    <span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
    <span>{{ session('error') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif

  {{-- ── Validation Errors ───────────────────────────────────────────── --}}
  @if($errors->any())
  <div class="alert alert-danger alert-dismissible mb-3">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following errors:</strong>
    <ul class="mb-0 mt-1">
      @foreach($errors->all() as $error)
        <li style="font-size:13px">{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  {{-- ── Page Header ─────────────────────────────────────────────────── --}}
  <div class="page-header">
    <div>
      <h1>Product Master</h1>
      <div class="page-breadcrumb">
        <a href="{{ route('dashboard') }}">Home</a> / Product Master
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('products.index', array_merge(request()->query(), ['export' => 1])) }}"
         class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export
      </a>
      <button class="btn btn-primary btn-sm" @click="showAddModal = true">
        <i class="bi bi-plus-lg me-1"></i>Add Product
      </button>
    </div>
  </div>

  {{-- ── Stat Cards ──────────────────────────────────────────────────── --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="bi bi-capsule"></i></div>
        <div>
          <div class="stat-value">{{ number_format($stats['total']) }}</div>
          <div class="stat-label">Total Products</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-success">
        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
        <div>
          <div class="stat-value">{{ number_format($stats['active']) }}</div>
          <div class="stat-label">Active</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="stat-value">{{ number_format($stats['pending']) }}</div>
          <div class="stat-label">Under Registration</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-danger">
        <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
        <div>
          <div class="stat-value">{{ number_format($stats['discontinued']) }}</div>
          <div class="stat-label">Discontinued</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Filter Bar ──────────────────────────────────────────────────── --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <form method="GET" action="{{ route('products.index') }}">
        <div class="row g-2 align-items-center">
          <div class="col-md-4">
            <div class="search-wrapper">
              <i class="bi bi-search search-icon"></i>
              <input type="text" name="search" class="form-control form-control-sm"
                     placeholder="Search by name, PRN, generic name..."
                     value="{{ $filters['search'] ?? '' }}">
            </div>
          </div>
          <div class="col-6 col-md-2">
            <select name="dosage_form" class="form-select form-select-sm">
              <option value="">All Forms</option>
              @foreach(['tablet'=>'Tablet','capsule'=>'Capsule','injection'=>'Injection','syrup'=>'Syrup','cream'=>'Cream','ointment'=>'Ointment','drops'=>'Drops','inhaler'=>'Inhaler','other'=>'Other'] as $val => $label)
                <option value="{{ $val }}" {{ ($filters['dosage_form'] ?? '') === $val ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="status" class="form-select form-select-sm">
              <option value="">All Status</option>
              <option value="active"           {{ ($filters['status'] ?? '') === 'active'           ? 'selected' : '' }}>Active</option>
              <option value="pending_approval" {{ ($filters['status'] ?? '') === 'pending_approval' ? 'selected' : '' }}>Under Registration</option>
              <option value="discontinued"     {{ ($filters['status'] ?? '') === 'discontinued'     ? 'selected' : '' }}>Discontinued</option>
            </select>
          </div>
          <div class="col-6 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
              <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-x-lg"></i>
            </a>
          </div>
          <div class="col-6 col-md-2 text-end text-muted" style="font-size:12px">
            {{ $products->total() }} product(s) found
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ── Product Table ────────────────────────────────────────────────── --}}
  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:40px"><input type="checkbox" class="form-check-input" id="selectAll"
                  @change="toggleAll($event.target.checked)"></th>
              <th>PRN</th>
              <th>Product Name</th>
              <th>Generic (INN)</th>
              <th>Form</th>
              <th>Strength</th>
              <th>Manufacturer</th>
              <th>Cold Chain</th>
              <th>Countries</th>
              <th>Status</th>
              <th style="width:110px">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($products as $product)
            <tr>
              <td>
                <input type="checkbox" class="form-check-input row-check"
                       value="{{ $product->id }}" x-model="selected">
              </td>
              <td>
                <span class="text-primary fw-semibold" style="font-size:12px"
                      role="button"
                      @click="openView({{ $product->id }})">
                  {{ $product->prn }}
                </span>
              </td>
              <td>
                <div class="fw-semibold" style="font-size:13px">{{ $product->name }}</div>
                @if($product->manufacturer_name)
                  <div class="text-muted-sm">{{ $product->manufacturer_name }}</div>
                @endif
              </td>
              <td style="font-size:13px">{{ $product->generic_name ?? '—' }}</td>
              <td>
                <span class="badge bg-light text-secondary border" style="font-size:11px">
                  {{ $product->dosage_form_label }}
                </span>
              </td>
              <td style="font-size:13px">{{ $product->strength ?? '—' }}</td>
              <td style="font-size:12px">{{ $product->manufacturer_name ?? '—' }}</td>
              <td>
                @if($product->cold_chain)
                  <span class="text-info" title="Cold chain required">
                    <i class="bi bi-thermometer-snow"></i> Yes
                  </span>
                @else
                  <span class="text-muted" style="font-size:12px">No</span>
                @endif
              </td>
              <td>
                <span class="badge bg-primary rounded-pill" style="font-size:11px">
                  {{ $product->countries_count }} {{ Str::plural('country', $product->countries_count) }}
                </span>
              </td>
              <td>
                <span class="badge-status {{ $product->status_badge_class }}">
                  {{ $product->status_label }}
                </span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  {{-- View --}}
                  <button class="btn btn-outline-primary btn-sm btn-icon"
                          title="View details"
                          @click="openView({{ $product->id }})">
                    <i class="bi bi-eye"></i>
                  </button>
                  {{-- Edit --}}
                  <button class="btn btn-outline-secondary btn-sm btn-icon"
                          title="Edit product"
                          @click="openEdit({{ $product->toJson() }})">
                    <i class="bi bi-pencil"></i>
                  </button>
                  {{-- Delete --}}
                  <form method="POST"
                        action="{{ route('products.destroy', $product) }}"
                        @submit.prevent="confirmDelete($event, '{{ addslashes($product->name) }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="btn btn-outline-danger btn-sm btn-icon"
                            title="Remove product">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="11" class="text-center py-5 text-muted">
                <i class="bi bi-capsule" style="font-size:32px;opacity:.2"></i>
                <div class="mt-2">No products found.
                  @if(array_filter($filters ?? []))
                    <a href="{{ route('products.index') }}" class="text-primary">Clear filters</a>
                  @endif
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if($products->hasPages())
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
        <div class="text-muted-sm">
          Showing <strong>{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong>
          of <strong>{{ $products->total() }}</strong> products
        </div>
        {{ $products->links('pagination::bootstrap-5') }}
      </div>
      @else
      <div class="px-3 py-2 border-top text-muted-sm">
        Showing all <strong>{{ $products->total() }}</strong> products
      </div>
      @endif
    </div>
  </div>

  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  {{-- VIEW MODAL (AJAX-loaded JSON → Alpine)                            --}}
  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  <div class="modal fade"
       :class="{show: showViewModal}"
       :style="showViewModal ? 'display:block' : ''"
       tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content" x-show="viewProduct">

        {{-- Header --}}
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-semibold" x-text="viewProduct?.name ?? '...'"></h5>
            <div class="text-muted-sm">
              <code x-text="viewProduct?.prn"></code>
            </div>
          </div>
          <div class="ms-auto d-flex align-items-center gap-2">
            <span x-show="viewProduct?.status_badge_class"
                  class="badge-status"
                  :class="viewProduct?.status_badge_class"
                  x-text="viewProduct?.status_label"></span>
            <button class="btn-close ms-1" @click="showViewModal = false"></button>
          </div>
        </div>

        {{-- Body --}}
        <div class="modal-body">
          <div x-show="viewLoading" class="text-center py-4 text-muted">
            <div class="spinner-border spinner-border-sm me-2"></div>Loading…
          </div>
          <div x-show="!viewLoading">
            <div class="row g-3">
              {{-- Identifiers --}}
              <div class="col-md-6">
                <div class="p-3 bg-light rounded-3">
                  <div class="text-muted-sm mb-1">Product Registration No.</div>
                  <div class="fw-semibold text-primary font-monospace" x-text="viewProduct?.prn"></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-light rounded-3">
                  <div class="text-muted-sm mb-1">ATC Code</div>
                  <div class="fw-semibold font-monospace" x-text="viewProduct?.atc_code || '—'"></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-light rounded-3">
                  <div class="text-muted-sm mb-1">HS Code</div>
                  <div class="fw-semibold font-monospace" x-text="viewProduct?.hs_code || '—'"></div>
                </div>
              </div>

              {{-- Names --}}
              <div class="col-md-6">
                <label class="form-label text-muted-sm">Brand Name</label>
                <div class="fw-semibold" x-text="viewProduct?.name"></div>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted-sm">Generic Name (INN)</label>
                <div x-text="viewProduct?.generic_name || '—'"></div>
              </div>

              {{-- Pharmaceutical --}}
              <div class="col-md-4">
                <label class="form-label text-muted-sm">Dosage Form</label>
                <div x-text="viewProduct?.dosage_form_label"></div>
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted-sm">Strength</label>
                <div x-text="viewProduct?.strength || '—'"></div>
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted-sm">Pack Size</label>
                <div x-text="viewProduct?.pack_size || '—'"></div>
              </div>

              {{-- Manufacturing --}}
              <div class="col-md-6">
                <label class="form-label text-muted-sm">Manufacturer</label>
                <div x-text="viewProduct?.manufacturer_name || '—'"></div>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted-sm">Country of Origin</label>
                <div x-text="viewProduct?.country_of_origin || '—'"></div>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted-sm">Unit Cost (USD)</label>
                <div x-text="viewProduct?.unit_cost ? '$' + parseFloat(viewProduct.unit_cost).toFixed(4) : '—'"></div>
              </div>

              {{-- Storage --}}
              <div class="col-md-4">
                <label class="form-label text-muted-sm">Shelf Life</label>
                <div x-text="viewProduct?.shelf_life || '—'"></div>
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted-sm">Storage Conditions</label>
                <div x-text="viewProduct?.storage_conditions || '—'"></div>
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted-sm">Temperature</label>
                <div class="d-flex align-items-center gap-1">
                  <i x-show="viewProduct?.cold_chain" class="bi bi-thermometer-snow text-info"></i>
                  <span x-text="viewProduct?.temperature_sensitivity?.replace('_',' ')"></span>
                </div>
              </div>

              {{-- Countries info --}}
              <div class="col-12">
                <div class="info-box info">
                  <i class="bi bi-globe2 text-primary mt-1"></i>
                  <div>
                    <strong x-text="viewProduct?.countries_count ?? 0"></strong>
                    approved country registration(s) on file.
                    <a href="{{ route('countries') }}" class="text-primary">Manage Countries →</a>
                  </div>
                </div>
              </div>

              {{-- Notes --}}
              <div class="col-12" x-show="viewProduct?.notes">
                <label class="form-label text-muted-sm">Notes</label>
                <div style="font-size:13px" x-text="viewProduct?.notes"></div>
              </div>
            </div>
          </div>
        </div>

        {{-- Footer --}}
        <div class="modal-footer">
          <button class="btn btn-outline-secondary btn-sm" @click="showViewModal = false">Close</button>
          <a href="{{ route('batches') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-layers me-1"></i>View Batches
          </a>
          <button class="btn btn-primary btn-sm"
                  @click="showViewModal = false; openEdit(viewProduct)">
            <i class="bi bi-pencil me-1"></i>Edit Product
          </button>
        </div>

      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal = false"></div>

  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  {{-- EDIT MODAL                                                         --}}
  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  <div class="modal fade"
       :class="{show: showEditModal}"
       :style="showEditModal ? 'display:block' : ''"
       tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content" x-show="editProduct">

        <div class="modal-header">
          <h5 class="modal-title fw-semibold">
            <i class="bi bi-pencil me-2 text-primary"></i>Edit Product
          </h5>
          <div class="text-muted-sm ms-2">
            <code x-text="editProduct?.prn"></code>
          </div>
          <button class="btn-close ms-auto" @click="showEditModal = false"></button>
        </div>

        {{-- Form dynamically targets PUT /products/{id} --}}
        <form method="POST"
              :action="`{{ url('products') }}/${editProduct?.id}`"
              @submit="saving = true">
          @csrf
          @method('PUT')

          <div class="modal-body">
            <div class="row g-3">

              {{-- Section: Core --}}
              <div class="col-12">
                <div class="fw-semibold text-muted-sm mb-1" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase">Core Information</div>
                <hr class="my-1">
              </div>

              <div class="col-md-6">
                <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       :value="editProduct?.name" required placeholder="e.g. Amoxil 500mg Capsules">
              </div>
              <div class="col-md-6">
                <label class="form-label">Generic Name (INN)</label>
                <input type="text" name="generic_name" class="form-control"
                       :value="editProduct?.generic_name" placeholder="e.g. Amoxicillin">
              </div>
              <div class="col-md-4">
                <label class="form-label">Dosage Form <span class="text-danger">*</span></label>
                <select name="dosage_form" class="form-select" required
                        :value="editProduct?.dosage_form">
                  <option value="">Select form…</option>
                  @foreach(['tablet'=>'Tablet','capsule'=>'Capsule','injection'=>'Injection','syrup'=>'Syrup','cream'=>'Cream','ointment'=>'Ointment','drops'=>'Drops','inhaler'=>'Inhaler','other'=>'Other'] as $val => $label)
                    <option value="{{ $val }}" :selected="editProduct?.dosage_form === '{{ $val }}'">{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Strength</label>
                <input type="text" name="strength" class="form-control"
                       :value="editProduct?.strength" placeholder="e.g. 500mg">
              </div>
              <div class="col-md-4">
                <label class="form-label">Pack Size</label>
                <input type="text" name="pack_size" class="form-control"
                       :value="editProduct?.pack_size" placeholder="e.g. 10 tabs/blister × 10">
              </div>

              {{-- Section: Classification --}}
              <div class="col-12 mt-2">
                <div class="fw-semibold text-muted-sm mb-1" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase">Classification &amp; Coding</div>
                <hr class="my-1">
              </div>
              <div class="col-md-3">
                <label class="form-label">ATC Code</label>
                <input type="text" name="atc_code" class="form-control"
                       :value="editProduct?.atc_code" placeholder="e.g. J01CA04">
              </div>
              <div class="col-md-3">
                <label class="form-label">HS Code</label>
                <input type="text" name="hs_code" class="form-control"
                       :value="editProduct?.hs_code" placeholder="e.g. 3004.20">
              </div>
              <div class="col-md-3">
                <label class="form-label">Controlled Substance</label>
                <select name="controlled_substance" class="form-select">
                  <option value="no"         :selected="editProduct?.controlled_substance === 'no'">No</option>
                  <option value="schedule_1" :selected="editProduct?.controlled_substance === 'schedule_1'">Schedule 1</option>
                  <option value="schedule_2" :selected="editProduct?.controlled_substance === 'schedule_2'">Schedule 2</option>
                  <option value="schedule_3" :selected="editProduct?.controlled_substance === 'schedule_3'">Schedule 3</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <option value="active"           :selected="editProduct?.status === 'active'">Active</option>
                  <option value="pending_approval" :selected="editProduct?.status === 'pending_approval'">Under Registration</option>
                  <option value="discontinued"     :selected="editProduct?.status === 'discontinued'">Discontinued</option>
                </select>
              </div>

              {{-- Section: Manufacturing --}}
              <div class="col-12 mt-2">
                <div class="fw-semibold text-muted-sm mb-1" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase">Manufacturing</div>
                <hr class="my-1">
              </div>
              <div class="col-md-5">
                <label class="form-label">Manufacturer Name</label>
                <input type="text" name="manufacturer_name" class="form-control"
                       :value="editProduct?.manufacturer_name">
              </div>
              <div class="col-md-5">
                <label class="form-label">Manufacturing Site</label>
                <input type="text" name="manufacturing_site" class="form-control"
                       :value="editProduct?.manufacturing_site">
              </div>
              <div class="col-md-2">
                <label class="form-label">Country of Origin</label>
                <input type="text" name="country_of_origin" class="form-control"
                       :value="editProduct?.country_of_origin" placeholder="US" maxlength="5">
              </div>

              {{-- Section: Storage & Cost --}}
              <div class="col-12 mt-2">
                <div class="fw-semibold text-muted-sm mb-1" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase">Storage &amp; Cost</div>
                <hr class="my-1">
              </div>
              <div class="col-md-3">
                <label class="form-label">Shelf Life</label>
                <input type="text" name="shelf_life" class="form-control"
                       :value="editProduct?.shelf_life" placeholder="e.g. 36 months">
              </div>
              <div class="col-md-3">
                <label class="form-label">Storage Conditions</label>
                <input type="text" name="storage_conditions" class="form-control"
                       :value="editProduct?.storage_conditions" placeholder="e.g. Below 25°C">
              </div>
              <div class="col-md-3">
                <label class="form-label">Temperature Sensitivity</label>
                <select name="temperature_sensitivity" class="form-select">
                  <option value="ambient"    :selected="editProduct?.temperature_sensitivity === 'ambient'">Ambient</option>
                  <option value="cool_chain" :selected="editProduct?.temperature_sensitivity === 'cool_chain'">Cool Chain (8–15°C)</option>
                  <option value="cold_chain" :selected="editProduct?.temperature_sensitivity === 'cold_chain'">Cold Chain (2–8°C)</option>
                  <option value="frozen"     :selected="editProduct?.temperature_sensitivity === 'frozen'">Frozen (≤ −20°C)</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Unit Cost (USD)</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" name="unit_cost" class="form-control"
                         :value="editProduct?.unit_cost" step="0.0001" min="0" placeholder="0.0000">
                </div>
              </div>

              {{-- Notes --}}
              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"
                          x-text="editProduct?.notes" placeholder="Optional internal notes…"></textarea>
              </div>

            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showEditModal = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
              <i class="bi bi-check-lg me-1" x-show="!saving"></i>
              <span x-text="saving ? 'Saving…' : 'Save Changes'"></span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showEditModal" @click="showEditModal = false"></div>

  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  {{-- ADD PRODUCT MODAL                                                  --}}
  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  <div class="modal fade"
       :class="{show: showAddModal}"
       :style="showAddModal ? 'display:block' : ''"
       tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title fw-semibold">
            <i class="bi bi-plus-circle me-2 text-primary"></i>Add New Product
          </h5>
          <button class="btn-close" @click="showAddModal = false"></button>
        </div>

        <form method="POST" action="{{ route('products.store') }}" @submit="saving = true">
          @csrf

          <div class="modal-body">
            <div class="row g-3">

              <div class="col-12">
                <div class="fw-semibold text-muted-sm mb-1" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase">Core Information</div>
                <hr class="my-1">
              </div>

              <div class="col-md-6">
                <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" required placeholder="e.g. Amoxil 500mg Capsules">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Generic Name (INN)</label>
                <input type="text" name="generic_name" class="form-control"
                       value="{{ old('generic_name') }}" placeholder="e.g. Amoxicillin">
              </div>
              <div class="col-md-4">
                <label class="form-label">Dosage Form <span class="text-danger">*</span></label>
                <select name="dosage_form" class="form-select @error('dosage_form') is-invalid @enderror" required>
                  <option value="">Select form…</option>
                  @foreach(['tablet'=>'Tablet','capsule'=>'Capsule','injection'=>'Injection','syrup'=>'Syrup','cream'=>'Cream','ointment'=>'Ointment','drops'=>'Drops','inhaler'=>'Inhaler','other'=>'Other'] as $val => $label)
                    <option value="{{ $val }}" {{ old('dosage_form') === $val ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
                @error('dosage_form')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4">
                <label class="form-label">Strength</label>
                <input type="text" name="strength" class="form-control"
                       value="{{ old('strength') }}" placeholder="e.g. 500mg">
              </div>
              <div class="col-md-4">
                <label class="form-label">Pack Size</label>
                <input type="text" name="pack_size" class="form-control"
                       value="{{ old('pack_size') }}" placeholder="e.g. 10 tabs × 10 blisters">
              </div>

              <div class="col-12 mt-2">
                <div class="fw-semibold text-muted-sm mb-1" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase">Classification &amp; Coding</div>
                <hr class="my-1">
              </div>
              <div class="col-md-3">
                <label class="form-label">ATC Code</label>
                <input type="text" name="atc_code" class="form-control"
                       value="{{ old('atc_code') }}" placeholder="e.g. J01CA04">
              </div>
              <div class="col-md-3">
                <label class="form-label">HS Code</label>
                <input type="text" name="hs_code" class="form-control"
                       value="{{ old('hs_code') }}" placeholder="e.g. 3004.20">
              </div>
              <div class="col-md-3">
                <label class="form-label">Controlled Substance</label>
                <select name="controlled_substance" class="form-select">
                  <option value="no"         {{ old('controlled_substance','no') === 'no'         ? 'selected':'' }}>No</option>
                  <option value="schedule_1" {{ old('controlled_substance')       === 'schedule_1' ? 'selected':'' }}>Schedule 1</option>
                  <option value="schedule_2" {{ old('controlled_substance')       === 'schedule_2' ? 'selected':'' }}>Schedule 2</option>
                  <option value="schedule_3" {{ old('controlled_substance')       === 'schedule_3' ? 'selected':'' }}>Schedule 3</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <option value="active"           {{ old('status','active') === 'active'           ? 'selected':'' }}>Active</option>
                  <option value="pending_approval" {{ old('status')           === 'pending_approval' ? 'selected':'' }}>Under Registration</option>
                  <option value="discontinued"     {{ old('status')           === 'discontinued'     ? 'selected':'' }}>Discontinued</option>
                </select>
              </div>

              <div class="col-12 mt-2">
                <div class="fw-semibold text-muted-sm mb-1" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase">Manufacturing</div>
                <hr class="my-1">
              </div>
              <div class="col-md-5">
                <label class="form-label">Manufacturer Name</label>
                <input type="text" name="manufacturer_name" class="form-control"
                       value="{{ old('manufacturer_name') }}" placeholder="e.g. PharmaCo Mfg">
              </div>
              <div class="col-md-5">
                <label class="form-label">Manufacturing Site</label>
                <input type="text" name="manufacturing_site" class="form-control"
                       value="{{ old('manufacturing_site') }}" placeholder="e.g. Plant A, Los Angeles">
              </div>
              <div class="col-md-2">
                <label class="form-label">Country of Origin</label>
                <input type="text" name="country_of_origin" class="form-control"
                       value="{{ old('country_of_origin') }}" placeholder="US" maxlength="5">
                <div class="form-text" style="font-size:10px">Used in PRN auto-generation</div>
              </div>

              <div class="col-12 mt-2">
                <div class="fw-semibold text-muted-sm mb-1" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase">Storage &amp; Cost</div>
                <hr class="my-1">
              </div>
              <div class="col-md-3">
                <label class="form-label">Shelf Life</label>
                <input type="text" name="shelf_life" class="form-control"
                       value="{{ old('shelf_life') }}" placeholder="e.g. 36 months">
              </div>
              <div class="col-md-3">
                <label class="form-label">Storage Conditions</label>
                <input type="text" name="storage_conditions" class="form-control"
                       value="{{ old('storage_conditions') }}" placeholder="e.g. Below 25°C">
              </div>
              <div class="col-md-3">
                <label class="form-label">Temperature Sensitivity</label>
                <select name="temperature_sensitivity" class="form-select">
                  <option value="ambient"    {{ old('temperature_sensitivity','ambient') === 'ambient'    ? 'selected':'' }}>Ambient</option>
                  <option value="cool_chain" {{ old('temperature_sensitivity')            === 'cool_chain' ? 'selected':'' }}>Cool Chain (8–15°C)</option>
                  <option value="cold_chain" {{ old('temperature_sensitivity')            === 'cold_chain' ? 'selected':'' }}>Cold Chain (2–8°C)</option>
                  <option value="frozen"     {{ old('temperature_sensitivity')            === 'frozen'     ? 'selected':'' }}>Frozen (≤ −20°C)</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Unit Cost (USD)</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" name="unit_cost" class="form-control"
                         value="{{ old('unit_cost') }}" step="0.0001" min="0" placeholder="0.0000">
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="Optional internal notes…">{{ old('notes') }}</textarea>
              </div>

              {{-- PRN hint --}}
              <div class="col-12">
                <div class="info-box info">
                  <i class="bi bi-info-circle text-primary mt-1"></i>
                  <div style="font-size:12px">
                    <strong>PRN is auto-generated</strong> from Country of Origin + Dosage Form on save.
                    Format: <code>PRN-{COUNTRY}-{FORM}-{NNNNN}</code>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showAddModal = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
              <i class="bi bi-check-lg me-1" x-show="!saving"></i>
              <span x-text="saving ? 'Saving…' : 'Add Product'"></span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal = false"></div>

</div>
@endsection

@push('scripts')
<script>
function productsPage() {
  return {
    // ── Modal state ─────────────────────────────────────────────────────
    showAddModal:  {{ $errors->any() ? 'true' : 'false' }},   // re-open if validation failed
    showViewModal: false,
    showEditModal: false,

    viewProduct:  null,
    editProduct:  null,
    viewLoading:  false,
    saving:       false,

    // ── Bulk selection ───────────────────────────────────────────────────
    selected: [],

    toggleAll(checked) {
      const checkboxes = document.querySelectorAll('.row-check');
      this.selected = checked ? [...checkboxes].map(c => c.value) : [];
      checkboxes.forEach(c => c.checked = checked);
    },

    // ── View modal — loads product via AJAX ──────────────────────────────
    openView(id) {
      this.viewProduct  = null;
      this.viewLoading  = true;
      this.showViewModal = true;

      fetch(`{{ url('products') }}/${id}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(data => { this.viewProduct = data; this.viewLoading = false; })
      .catch(() => { this.viewLoading = false; alert('Could not load product details.'); });
    },

    // ── Edit modal — product data already available from table row ───────
    openEdit(product) {
      this.editProduct  = product;
      this.saving       = false;
      this.showEditModal = true;
    },

    // ── Delete confirmation ──────────────────────────────────────────────
    confirmDelete(event, name) {
      if (confirm(`Remove product "${name}"?\n\nThis will soft-delete the record and can be restored by an administrator.`)) {
        event.target.submit();
      }
    },
  };
}
</script>
@endpush
