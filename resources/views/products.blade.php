@extends('layouts.app')
@section('title', 'Product Master')

@push('styles')
<style>
/* ── Image Upload Zone ──────────────────────────────────────────────────── */
.img-upload-zone {
  border: 2px dashed var(--border-color, #dee2e6);
  border-radius: 12px;
  padding: 24px;
  text-align: center;
  cursor: pointer;
  transition: border-color .2s, background .2s;
}
.img-upload-zone:hover,
.img-upload-zone.drag-over {
  border-color: var(--primary-color, #0d6efd);
  background: rgba(13,110,253,.04);
}
/* ── Image Preview Grid ─────────────────────────────────────────────────── */
.img-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 10px;
}
.img-preview-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid transparent;
  aspect-ratio: 1/1;
  background: #f8f9fa;
  cursor: pointer;
}
.img-preview-item.is-primary {
  border-color: #0d6efd;
}
.img-preview-item.to-remove {
  opacity: .35;
  filter: grayscale(1);
}
.img-preview-item img {
  width: 100%; height: 100%; object-fit: cover;
}
.img-preview-item .img-actions {
  position: absolute;
  top: 4px; right: 4px;
  display: flex; gap: 4px;
  opacity: 0;
  transition: opacity .15s;
}
.img-preview-item:hover .img-actions { opacity: 1; }
.img-preview-item .img-badge-primary {
  position: absolute;
  bottom: 4px; left: 4px;
  background: #0d6efd;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 4px;
  letter-spacing: .04em;
}
.img-preview-item .img-badge-remove {
  position: absolute;
  bottom: 4px; left: 4px;
  background: #dc3545;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 4px;
}
/* ── Gallery in view modal ──────────────────────────────────────────────── */
.product-gallery {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 4px;
}
.product-gallery img {
  width: 80px; height: 80px;
  object-fit: cover;
  border-radius: 8px;
  border: 2px solid transparent;
  cursor: pointer;
  flex-shrink: 0;
}
.product-gallery img.active { border-color: #0d6efd; }
.product-main-img {
  width: 100%; max-height: 260px;
  object-fit: contain;
  border-radius: 10px;
  background: #f8f9fa;
}
/* ── Searchable Therapeutic-Class Combobox ──────────────────────────────── */
.tc-combo { position: relative; }
.tc-combo-wrap { position: relative; display: flex; align-items: center; }
.tc-combo-wrap .form-control { padding-right: 28px; }
.tc-combo-clear {
  position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: #adb5bd; font-size: 14px;
  cursor: pointer; line-height: 1; padding: 0; z-index: 2;
}
.tc-combo-clear:hover { color: #495057; }
.tc-dropdown {
  position: absolute; top: calc(100% + 2px); left: 0; right: 0;
  background: #fff; border: 1px solid #dee2e6; border-radius: 8px;
  box-shadow: 0 6px 20px rgba(0,0,0,.12);
  list-style: none; margin: 0; padding: 4px 0;
  max-height: 210px; overflow-y: auto;
  z-index: 9999;
}
[data-bs-theme="dark"] .tc-dropdown { background: #2b3035; border-color: #495057; }
.tc-dropdown li { padding: 7px 14px; font-size: 13px; cursor: pointer; }
.tc-dropdown li:hover, .tc-dropdown li.tc-hl { background: rgba(13,110,253,.1); color: #0d6efd; }
.tc-dropdown .tc-empty { color: #adb5bd; font-style: italic; }
/* ── Table thumbnail ────────────────────────────────────────────────────── */
.product-thumb {
  width: 40px; height: 40px;
  object-fit: cover;
  border-radius: 6px;
  background: #f0f0f0;
}
.product-thumb-placeholder {
  width: 40px; height: 40px;
  background: #f0f0f0;
  border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  color: #adb5bd;
  font-size: 16px;
}
/* ── Scrollable modal with a <form> wrapping body + footer ───────────────── */
/* These modals are opened by toggling display:block via Alpine (not the     */
/* Bootstrap JS), so Bootstrap's percentage height chain never resolves and  */
/* the footer (Save / Update button) gets clipped out of view. Bound the     */
/* height with a viewport unit so the body scrolls and the footer stays      */
/* pinned and visible — independent of the parent height chain.              */
.modal-dialog-scrollable > .modal-content {
  max-height: calc(100vh - 3.5rem);
  overflow: hidden;
}
.modal-content > form {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}
.modal-content > form > .modal-body {
  flex: 1 1 auto;
  overflow-y: auto;
  min-height: 0;
}
.modal-content > form > .modal-footer {
  flex-shrink: 0;
}
</style>
@endpush

@section('content')
<div x-data="productsPage()">

  {{-- Shared datalist of known countries (grows as new ones are added) --}}
  <datalist id="country-list">
    @foreach($countries as $c)
      <option value="{{ $c }}"></option>
    @endforeach
  </datalist>

  {{-- ── Flash ─────────────────────────────────────────────────────────── --}}
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
  <div class="alert alert-danger alert-dismissible mb-3">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following errors:</strong>
    <ul class="mb-0 mt-1 ps-3">
      @foreach($errors->all() as $e)
        <li style="font-size:13px">{{ $e }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  {{-- ── Page Header ─────────────────────────────────────────────────── --}}
  <div class="page-header">
    <div>
      <h1>Product Master</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Product Master</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('products.index', array_merge(request()->query(), ['export'=>1])) }}"
         class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export
      </a>
      <button class="btn btn-primary btn-sm" @click="showAddModal = true">
        <i class="bi bi-plus-lg me-1"></i>Add Product
      </button>
    </div>
  </div>

  {{-- ── Stats ───────────────────────────────────────────────────────── --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="bi bi-capsule"></i></div>
        <div><div class="stat-value">{{ number_format($stats['total']) }}</div><div class="stat-label">Total Products</div></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-success">
        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
        <div><div class="stat-value">{{ number_format($stats['active']) }}</div><div class="stat-label">Active</div></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="stat-value">{{ number_format($stats['pending']) }}</div><div class="stat-label">Under Registration</div></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-danger">
        <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
        <div><div class="stat-value">{{ number_format($stats['discontinued']) }}</div><div class="stat-label">Discontinued</div></div>
      </div>
    </div>
  </div>

  {{-- ── Filter Bar ──────────────────────────────────────────────────── --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <form method="GET" action="{{ route('products.index') }}">
        <div class="row g-2 align-items-center">
          <div class="col-md-3">
            <div class="search-wrapper">
              <i class="bi bi-search search-icon"></i>
              <input type="text" name="search" class="form-control form-control-sm"
                     placeholder="Name, PRN, generic…" value="{{ $filters['search'] ?? '' }}">
            </div>
          </div>
          <div class="col-6 col-md-2">
            <select name="dosage_form" class="form-select form-select-sm">
              <option value="">All Forms</option>
              @foreach(['tablet'=>'Tablet','capsule'=>'Capsule','injection'=>'Injection','syrup'=>'Syrup','cream'=>'Cream','ointment'=>'Ointment','drops'=>'Drops','inhaler'=>'Inhaler','other'=>'Other'] as $v=>$l)
                <option value="{{ $v }}" {{ ($filters['dosage_form'] ?? '') === $v ? 'selected':'' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <input type="text" name="therapeutic_class" class="form-control form-control-sm"
                   placeholder="Therapeutic class…" value="{{ $filters['therapeutic_class'] ?? '' }}">
          </div>
          <div class="col-6 col-md-2">
            <select name="status" class="form-select form-select-sm">
              <option value="">All Status</option>
              <option value="active"           {{ ($filters['status'] ?? '') === 'active'           ? 'selected':'' }}>Active</option>
              <option value="pending_approval" {{ ($filters['status'] ?? '') === 'pending_approval' ? 'selected':'' }}>Under Registration</option>
              <option value="discontinued"     {{ ($filters['status'] ?? '') === 'discontinued'     ? 'selected':'' }}>Discontinued</option>
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
          <div class="col-12 col-md-1 text-end text-muted" style="font-size:12px">
            {{ $products->total() }} found
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ── Table ────────────────────────────────────────────────────────── --}}
  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th style="width:40px">
                <input type="checkbox" class="form-check-input" @change="toggleAll($event.target.checked)">
              </th>
              <th style="width:52px">Image</th>
              <th>PRN</th>
              <th>Product Name</th>
              <th>Generic (INN)</th>
              <th>Form</th>
              <th>Strength</th>
              <th>Therapeutic Class</th>
              <th>Cold Chain</th>
              <th>Countries</th>
              <th>Status</th>
              <th style="width:110px">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($products as $product)
            <tr>
              <td><input type="checkbox" class="form-check-input row-check" value="{{ $product->id }}" x-model="selected"></td>

              {{-- Thumbnail --}}
              <td>
                @if($product->primaryImage)
                  <img src="{{ $product->primaryImage->url }}"
                       alt="{{ $product->name }}"
                       class="product-thumb"
                       @click="openView({{ $product->id }})"
                       style="cursor:pointer">
                @else
                  <div class="product-thumb-placeholder" @click="openView({{ $product->id }})" style="cursor:pointer">
                    <i class="bi bi-image"></i>
                  </div>
                @endif
              </td>

              <td>
                <span class="text-primary fw-semibold" style="font-size:12px;cursor:pointer"
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
              <td style="font-size:12px">{{ $product->therapeutic_class ?? '—' }}</td>
              <td>
                @if($product->cold_chain)
                  <span class="text-info"><i class="bi bi-thermometer-snow"></i> Yes</span>
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
                <span class="badge-status {{ $product->status_badge_class }}">{{ $product->status_label }}</span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <button class="btn btn-outline-primary btn-sm btn-icon" title="View"
                          @click="openView({{ $product->id }})">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="btn btn-outline-secondary btn-sm btn-icon" title="Edit"
                          @click="openEdit({{ $product->id }})">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST" action="{{ route('products.destroy', $product) }}"
                        @submit.prevent="confirmDelete($event, '{{ addslashes($product->name) }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm btn-icon" title="Remove">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="12" class="text-center py-5 text-muted">
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
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top flex-wrap gap-2">
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
  {{-- VIEW MODAL                                                         --}}
  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  <div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">

        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-semibold" x-text="viewProduct?.name ?? 'Loading…'"></h5>
            <code class="text-muted-sm" x-text="viewProduct?.prn"></code>
          </div>
          <div class="ms-auto d-flex align-items-center gap-2">
            <span class="badge-status" :class="viewProduct?.status_badge_class" x-text="viewProduct?.status_label"></span>
            <button class="btn-close ms-1" @click="showViewModal=false"></button>
          </div>
        </div>

        <div class="modal-body">

          {{-- Loading spinner --}}
          <div x-show="viewLoading" class="text-center py-5 text-muted">
            <div class="spinner-border spinner-border-sm me-2"></div>Loading product details…
          </div>

          <div x-show="!viewLoading && viewProduct">
            <div class="row g-3">

              {{-- Image gallery (left) --}}
              <div class="col-md-4" x-show="viewProduct?.images?.length > 0">
                <div class="mb-2">
                  <img :src="activeImage" alt="" class="product-main-img">
                </div>
                <div class="product-gallery">
                  <template x-for="(img,i) in (viewProduct?.images||[])" :key="img.id">
                    <img :src="img.url" :alt="img.name"
                         :class="{active: activeImage===img.url}"
                         @click="activeImage=img.url">
                  </template>
                </div>
              </div>

              <div :class="viewProduct?.images?.length > 0 ? 'col-md-8' : 'col-12'">
                <div class="row g-3">

                  {{-- Identifiers --}}
                  <div class="col-md-5">
                    <div class="p-3 bg-light rounded-3">
                      <div class="text-muted-sm mb-1">Product Registration No.</div>
                      <div class="fw-semibold text-primary font-monospace" x-text="viewProduct?.prn"></div>
                    </div>
                  </div>
                  <div class="col-md-4">
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

                  {{-- Pharma --}}
                  <div class="col-md-3">
                    <label class="form-label text-muted-sm">Dosage Form</label>
                    <div x-text="viewProduct?.dosage_form_label"></div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label text-muted-sm">Strength</label>
                    <div x-text="viewProduct?.strength || '—'"></div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label text-muted-sm">Pack Size</label>
                    <div x-text="viewProduct?.pack_size || '—'"></div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label text-muted-sm">Therapeutic Class</label>
                    <div x-text="viewProduct?.therapeutic_class || '—'"></div>
                  </div>

                  {{-- Mfg --}}
                  <div class="col-md-5">
                    <label class="form-label text-muted-sm">Manufacturer</label>
                    <div x-text="viewProduct?.manufacturer_name || '—'"></div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label text-muted-sm">Manufacturing Site</label>
                    <div x-text="viewProduct?.manufacturing_site || '—'"></div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label text-muted-sm">Origin</label>
                    <div x-text="viewProduct?.country_of_origin || '—'"></div>
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
                      <span x-text="viewProduct?.temperature_sensitivity?.replace(/_/g,' ')"></span>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label text-muted-sm">Unit Cost (USD)</label>
                    <div x-text="viewProduct?.unit_cost ? '$' + parseFloat(viewProduct.unit_cost).toFixed(4) : '—'"></div>
                  </div>

                  <div class="col-12">
                    <div class="info-box info">
                      <i class="bi bi-globe2 text-primary mt-1"></i>
                      <div>
                        <strong x-text="viewProduct?.countries_count ?? 0"></strong> approved country registration(s).
                        <a href="{{ route('countries') }}" class="text-primary">Manage →</a>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6" x-show="viewProduct?.website_url">
                    <label class="form-label text-muted-sm">Website</label>
                    <div>
                      <a :href="viewProduct?.website_url" target="_blank" rel="noopener noreferrer"
                         class="text-primary text-break" x-text="viewProduct?.website_url"></a>
                    </div>
                  </div>
                  <div class="col-md-6" x-show="viewProduct?.pdf_url">
                    <label class="form-label text-muted-sm">Document</label>
                    <div>
                      <a :href="viewProduct?.pdf_url" target="_blank" rel="noopener noreferrer"
                         class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i><span x-text="viewProduct?.pdf_name"></span>
                      </a>
                    </div>
                  </div>

                  <div class="col-12" x-show="viewProduct?.notes">
                    <label class="form-label text-muted-sm">Notes</label>
                    <div style="font-size:13px" x-text="viewProduct?.notes"></div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
          <a href="{{ route('batches') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-layers me-1"></i>View Batches
          </a>
          <button class="btn btn-primary btn-sm"
                  @click="showViewModal=false; openEdit(viewProduct?.id)">
            <i class="bi bi-pencil me-1"></i>Edit
          </button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false"></div>

  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  {{-- EDIT MODAL                                                         --}}
  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  <div class="modal fade" :class="{show:showEditModal}" :style="showEditModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title fw-semibold">
            <i class="bi bi-pencil me-2 text-primary"></i>Edit Product
          </h5>
          <code class="text-muted-sm ms-2" x-text="editProduct?.prn"></code>
          <button class="btn-close ms-auto" @click="showEditModal=false"></button>
        </div>

        <div x-show="editLoading" class="text-center py-5 text-muted modal-body">
          <div class="spinner-border spinner-border-sm me-2"></div>Loading…
        </div>

        <form x-show="!editLoading" x-ref="editForm"
              method="POST"
              :action="`{{ url('products') }}/${editProduct?.id}`"
              enctype="multipart/form-data"
              @submit.prevent="submitEditForm()"
              style="display:flex;flex-direction:column;flex:1 1 auto;overflow:hidden;min-height:0">
          @csrf
          <input type="hidden" name="_method" value="PUT">

          <div class="modal-body">

            {{-- Inline errors --}}
            <template x-if="Object.keys(editErrors).length > 0">
              <div class="alert alert-danger mb-3">
                <strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the errors below.</strong>
                <ul class="mb-0 mt-1 ps-3" style="font-size:13px">
                  <template x-for="(msgs, field) in editErrors" :key="field">
                    <template x-for="msg in msgs" :key="msg">
                      <li x-text="msg"></li>
                    </template>
                  </template>
                </ul>
              </div>
            </template>

            <div class="row g-3">
              {{-- ── Core ─────────────────────────────────────────────── --}}
              <div class="col-12">
                <div class="section-label">Core Information</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" :value="editProduct?.name" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Generic Name (INN)</label>
                <input type="text" name="generic_name" class="form-control" :value="editProduct?.generic_name">
              </div>
              <div class="col-md-4">
                <label class="form-label">Dosage Form <span class="text-danger">*</span></label>
                <select name="dosage_form" class="form-select" required
                        x-effect="$el.value = editProduct?.dosage_form ?? ''">
                  <option value="">Select…</option>
                  @foreach(['tablet'=>'Tablet','capsule'=>'Capsule','injection'=>'Injection','syrup'=>'Syrup','cream'=>'Cream','ointment'=>'Ointment','drops'=>'Drops','inhaler'=>'Inhaler','other'=>'Other'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Strength</label>
                <input type="text" name="strength" class="form-control" :value="editProduct?.strength" placeholder="e.g. 500mg">
              </div>
              <div class="col-md-4">
                <label class="form-label">Pack Size</label>
                <input type="text" name="pack_size" class="form-control" :value="editProduct?.pack_size" placeholder="e.g. 10 tabs × 10 blisters">
              </div>

              {{-- ── Classification ──────────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Classification &amp; Coding</div></div>
              <div class="col-md-4">
                <label class="form-label">Therapeutic Class</label>
                <select name="therapeutic_class" class="form-select"
                        x-effect="$el.value = editProduct?.therapeutic_class ?? ''">
                  <option value="">Select…</option>
                  @foreach($therapeuticClasses as $tc)
                    <option value="{{ $tc }}">{{ $tc }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">ATC Code</label>
                <input type="text" name="atc_code" class="form-control" :value="editProduct?.atc_code" placeholder="e.g. J01CA04">
              </div>
              <div class="col-md-3">
                <label class="form-label">HS Code</label>
                <input type="text" name="hs_code" class="form-control" :value="editProduct?.hs_code" placeholder="e.g. 3004.20">
              </div>
              <div class="col-md-2">
                <label class="form-label">Controlled Substance</label>
                <select name="controlled_substance" class="form-select"
                        x-effect="$el.value = editProduct?.controlled_substance ?? 'no'">
                  <option value="no">No</option>
                  <option value="schedule_1">Schedule 1</option>
                  <option value="schedule_2">Schedule 2</option>
                  <option value="schedule_3">Schedule 3</option>
                </select>
              </div>

              {{-- Status + Mfg ─────────────────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Manufacturing &amp; Status</div></div>
              <div class="col-md-4">
                <label class="form-label">Manufacturer Name</label>
                <input type="text" name="manufacturer_name" class="form-control" :value="editProduct?.manufacturer_name">
              </div>
              <div class="col-md-3">
                <label class="form-label">Manufacturing Site</label>
                <input type="text" name="manufacturing_site" class="form-control" :value="editProduct?.manufacturing_site">
              </div>
              <div class="col-md-2">
                <label class="form-label">Country of Origin</label>
                <select name="country_of_origin_name" class="form-select"
                        x-effect="$el.value = editProduct?.country_of_origin_name ?? ''">
                  <option value="">Select…</option>
                  @foreach($countries as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select"
                        x-effect="$el.value = editProduct?.status ?? 'active'">
                  <option value="active">Active</option>
                  <option value="pending_approval">Under Registration</option>
                  <option value="discontinued">Discontinued</option>
                </select>
              </div>

              {{-- Storage & Cost ────────────────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Storage &amp; Cost</div></div>
              <div class="col-md-3">
                <label class="form-label">Shelf Life</label>
                <input type="text" name="shelf_life" class="form-control" :value="editProduct?.shelf_life" placeholder="e.g. 36 months">
              </div>
              <div class="col-md-3">
                <label class="form-label">Storage Conditions</label>
                <input type="text" name="storage_conditions" class="form-control" :value="editProduct?.storage_conditions">
              </div>
              <div class="col-md-3">
                <label class="form-label">Temperature Sensitivity</label>
                <select name="temperature_sensitivity" class="form-select"
                        x-effect="$el.value = editProduct?.temperature_sensitivity ?? 'ambient'">
                  <option value="ambient">Ambient</option>
                  <option value="cool_chain">Cool Chain (8–15°C)</option>
                  <option value="cold_chain">Cold Chain (2–8°C)</option>
                  <option value="frozen">Frozen (≤ −20°C)</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Unit Cost (USD)</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" name="unit_cost" class="form-control" :value="editProduct?.unit_cost" step="0.0001" min="0">
                </div>
              </div>

              {{-- ── Product Images ──────────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Product Images</div></div>
              <div class="col-12">

                {{-- Existing images --}}
                <div x-show="editExistingImages.length > 0" class="mb-3">
                  <div class="text-muted-sm mb-2">Existing Images
                    <span class="text-muted">(click image to set primary · click ✕ to remove)</span>
                  </div>
                  <div class="img-preview-grid">
                    <template x-for="(img, i) in editExistingImages" :key="img.id">
                      <div class="img-preview-item"
                           :class="{'is-primary': img.is_primary && !img.toRemove, 'to-remove': img.toRemove}"
                           @click="!img.toRemove && setEditExistingPrimary(i)">
                        <img :src="img.url" :alt="img.name">
                        <div class="img-actions">
                          <button type="button" class="btn btn-danger btn-sm p-0"
                                  style="width:20px;height:20px;font-size:10px;line-height:1"
                                  @click.stop="img.toRemove ? restoreEditImage(i) : removeEditImage(i)">
                            <i :class="img.toRemove ? 'bi-arrow-counterclockwise' : 'bi-x'"></i>
                          </button>
                        </div>
                        <span x-show="img.is_primary && !img.toRemove" class="img-badge-primary">PRIMARY</span>
                        <span x-show="img.toRemove" class="img-badge-remove">REMOVE</span>
                        {{-- Hidden input to flag removal --}}
                        <input type="hidden" name="remove_images[]" :value="img.id" x-show="false"
                               x-bind:disabled="!img.toRemove">
                      </div>
                    </template>
                  </div>
                </div>

                {{-- Hidden remove inputs (Alpine manages these dynamically) --}}
                <template x-for="id in removeImageIds" :key="id">
                  <input type="hidden" name="remove_images[]" :value="id">
                </template>

                {{-- Hidden primary_image_id --}}
                <template x-if="editPrimaryImageId">
                  <input type="hidden" name="primary_image_id" :value="editPrimaryImageId">
                </template>

                {{-- New image uploads --}}
                <div class="text-muted-sm mb-2">Add New Images
                  <span class="text-muted">(max 10 total · JPG, PNG, WebP · max 5 MB each)</span>
                </div>

                {{-- Drop Zone --}}
                <div class="img-upload-zone"
                     :class="{'drag-over': editDragOver}"
                     @dragover.prevent="editDragOver=true"
                     @dragleave.prevent="editDragOver=false"
                     @drop.prevent="editDragOver=false; handleEditDrop($event)"
                     @click="$refs.editFileInput.click()">
                  <i class="bi bi-cloud-upload" style="font-size:28px;color:#6c757d"></i>
                  <div class="fw-semibold mt-1" style="font-size:13px">Drop images here or click to browse</div>
                  <div class="text-muted-sm">JPG · PNG · WebP · GIF — up to 5 MB each</div>
                  <input type="file" class="d-none" x-ref="editFileInput" multiple accept="image/*"
                         @change="handleEditFileChange($event)">
                </div>

                {{-- New image previews --}}
                <div x-show="editNewPreviews.length > 0" class="img-preview-grid mt-3">
                  <template x-for="(p, i) in editNewPreviews" :key="i">
                    <div class="img-preview-item" :class="{'is-primary': p.primary}">
                      <img :src="p.url" :alt="p.name">
                      <div class="img-actions">
                        <button type="button" class="btn btn-danger btn-sm p-0"
                                style="width:20px;height:20px;font-size:10px;line-height:1"
                                @click.stop="removeEditNew(i)">
                          <i class="bi bi-x"></i>
                        </button>
                      </div>
                      <span x-show="p.primary" class="img-badge-primary">PRIMARY</span>
                    </div>
                  </template>
                </div>
              </div>

              {{-- ── Registered / Marketed Countries ──────────────────── --}}
              <div class="col-12"><div class="section-label">Registered / Marketed Countries</div></div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-1 mb-2" x-show="editRegCountries.length">
                  <template x-for="(c, i) in editRegCountries" :key="c">
                    <span class="badge bg-primary d-flex align-items-center gap-1" style="font-size:12px">
                      <span x-text="c"></span>
                      <i class="bi bi-x-lg" style="cursor:pointer" @click="removeRegCountry('edit', i)"></i>
                    </span>
                  </template>
                </div>
                <select class="form-select" x-model="editRegInput" @change="addRegCountry('edit')">
                  <option value="">+ Add a country…</option>
                  @foreach($countries as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                  @endforeach
                </select>
                <div class="form-text" style="font-size:10px">Pick a country to add it. Manage the list in Master Data → Countries.</div>
                <template x-for="c in editRegCountries" :key="'h'+c">
                  <input type="hidden" name="countries[]" :value="c">
                </template>
              </div>

              {{-- ── Documents & Links ───────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Documents &amp; Links</div></div>
              <div class="col-md-6">
                <label class="form-label">Website URL</label>
                <input type="url" name="website_url" class="form-control"
                       :value="editProduct?.website_url" placeholder="https://example.com">
              </div>
              <div class="col-md-6">
                <label class="form-label">Product Document (PDF)</label>

                {{-- Existing PDF --}}
                <div x-show="editProduct?.pdf_url" class="d-flex align-items-center gap-2 mb-2">
                  <a :href="editProduct?.pdf_url" target="_blank" rel="noopener noreferrer"
                     class="btn btn-outline-danger btn-sm text-truncate" style="max-width:60%">
                    <i class="bi bi-file-earmark-pdf me-1"></i><span x-text="editProduct?.pdf_name"></span>
                  </a>
                  <label class="form-check-label text-danger d-flex align-items-center" style="font-size:12px">
                    <input type="checkbox" name="remove_pdf" value="1" class="form-check-input me-1">Remove
                  </label>
                </div>

                <input type="file" name="pdf" class="form-control" accept="application/pdf">
                <div class="form-text" style="font-size:10px">
                  <span x-show="editProduct?.pdf_url">Upload a new PDF to replace · </span>PDF only · max 10 MB
                </div>
              </div>

              {{-- Notes --}}
              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"
                          x-effect="$el.value = editProduct?.notes ?? ''"></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showEditModal=false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
              <i class="bi bi-check-lg me-1" x-show="!saving"></i>
              <span x-text="saving ? 'Updating…' : 'Update Product'"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showEditModal" @click="showEditModal=false"></div>

  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  {{-- ADD PRODUCT MODAL                                                  --}}
  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  <div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title fw-semibold">
            <i class="bi bi-plus-circle me-2 text-primary"></i>Add New Product
          </h5>
          <button class="btn-close" @click="showAddModal=false; resetAdd()"></button>
        </div>

        <form x-ref="addForm"
              method="POST"
              action="{{ route('products.store') }}"
              enctype="multipart/form-data"
              @submit.prevent="submitAddForm()"
              style="display:flex;flex-direction:column;flex:1 1 auto;overflow:hidden;min-height:0">
          @csrf

          <div class="modal-body">

            {{-- Inline errors --}}
            <template x-if="Object.keys(addErrors).length > 0">
              <div class="alert alert-danger mb-3">
                <strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the errors below.</strong>
                <ul class="mb-0 mt-1 ps-3" style="font-size:13px">
                  <template x-for="(msgs, field) in addErrors" :key="field">
                    <template x-for="msg in msgs" :key="msg">
                      <li x-text="msg"></li>
                    </template>
                  </template>
                </ul>
              </div>
            </template>

            <div class="row g-3">

              {{-- ── Core ─────────────────────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Core Information</div></div>

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
                  @foreach(['tablet'=>'Tablet','capsule'=>'Capsule','injection'=>'Injection','syrup'=>'Syrup','cream'=>'Cream','ointment'=>'Ointment','drops'=>'Drops','inhaler'=>'Inhaler','other'=>'Other'] as $v=>$l)
                    <option value="{{ $v }}" {{ old('dosage_form') === $v ? 'selected':'' }}>{{ $l }}</option>
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

              {{-- ── Classification ──────────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Classification &amp; Coding</div></div>

              <div class="col-md-4">
                <label class="form-label">Therapeutic Class</label>
                <select name="therapeutic_class" class="form-select">
                  <option value="">Select…</option>
                  @foreach($therapeuticClasses as $tc)
                    <option value="{{ $tc }}" {{ old('therapeutic_class') === $tc ? 'selected' : '' }}>{{ $tc }}</option>
                  @endforeach
                </select>
                <div class="form-text" style="font-size:10px">Manage list in Master Data → Therapeutic Classes</div>
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
              <div class="col-md-2">
                <label class="form-label">Controlled Substance</label>
                <select name="controlled_substance" class="form-select">
                  <option value="no" {{ old('controlled_substance','no')==='no' ? 'selected':'' }}>No</option>
                  <option value="schedule_1" {{ old('controlled_substance')==='schedule_1' ? 'selected':'' }}>Schedule 1</option>
                  <option value="schedule_2" {{ old('controlled_substance')==='schedule_2' ? 'selected':'' }}>Schedule 2</option>
                  <option value="schedule_3" {{ old('controlled_substance')==='schedule_3' ? 'selected':'' }}>Schedule 3</option>
                </select>
              </div>

              {{-- ── Manufacturing & Status ──────────────────────────── --}}
              <div class="col-12"><div class="section-label">Manufacturing &amp; Status</div></div>

              <div class="col-md-4">
                <label class="form-label">Manufacturer Name</label>
                <input type="text" name="manufacturer_name" class="form-control"
                       value="{{ old('manufacturer_name') }}" placeholder="e.g. PharmaCo Mfg">
              </div>
              <div class="col-md-3">
                <label class="form-label">Manufacturing Site</label>
                <input type="text" name="manufacturing_site" class="form-control"
                       value="{{ old('manufacturing_site') }}" placeholder="e.g. Plant A, Los Angeles">
              </div>
              <div class="col-md-2">
                <label class="form-label">Country of Origin</label>
                <select name="country_of_origin_name" class="form-select">
                  <option value="">Select…</option>
                  @foreach($countries as $c)
                    <option value="{{ $c }}" {{ old('country_of_origin_name') === $c ? 'selected' : '' }}>{{ $c }}</option>
                  @endforeach
                </select>
                <div class="form-text" style="font-size:10px">Used in PRN</div>
              </div>
              <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <option value="active"           {{ old('status','active')==='active'           ? 'selected':'' }}>Active</option>
                  <option value="pending_approval" {{ old('status')==='pending_approval' ? 'selected':'' }}>Under Registration</option>
                  <option value="discontinued"     {{ old('status')==='discontinued'     ? 'selected':'' }}>Discontinued</option>
                </select>
              </div>

              {{-- ── Storage & Cost ──────────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Storage &amp; Cost</div></div>

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
                  <option value="ambient"    {{ old('temperature_sensitivity','ambient')==='ambient'    ? 'selected':'' }}>Ambient</option>
                  <option value="cool_chain" {{ old('temperature_sensitivity')==='cool_chain' ? 'selected':'' }}>Cool Chain (8–15°C)</option>
                  <option value="cold_chain" {{ old('temperature_sensitivity')==='cold_chain' ? 'selected':'' }}>Cold Chain (2–8°C)</option>
                  <option value="frozen"     {{ old('temperature_sensitivity')==='frozen'     ? 'selected':'' }}>Frozen (≤ −20°C)</option>
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

              {{-- ── Product Images ──────────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Product Images</div></div>
              <div class="col-12">

                {{-- Drop Zone --}}
                <div class="img-upload-zone mb-3"
                     :class="{'drag-over': addDragOver}"
                     @dragover.prevent="addDragOver=true"
                     @dragleave.prevent="addDragOver=false"
                     @drop.prevent="addDragOver=false; handleAddDrop($event)"
                     @click="$refs.addFileInput.click()">
                  <i class="bi bi-images" style="font-size:32px;color:#6c757d"></i>
                  <div class="fw-semibold mt-2" style="font-size:14px">Drag &amp; drop product images here</div>
                  <div class="text-muted-sm mt-1">or <span class="text-primary">click to browse</span></div>
                  <div class="text-muted-sm">JPG · PNG · WebP · GIF — up to 5 MB each · max 10 images</div>
                  <input type="file" class="d-none" x-ref="addFileInput" multiple accept="image/*"
                         @change="handleAddFileChange($event)">
                </div>

                {{-- Preview Grid --}}
                <div x-show="addPreviews.length > 0">
                  <div class="text-muted-sm mb-2">
                    <span x-text="addPreviews.length"></span> image(s) selected ·
                    <span class="text-muted">Click image to set as primary · click ✕ to remove</span>
                  </div>
                  <div class="img-preview-grid">
                    <template x-for="(p, i) in addPreviews" :key="i">
                      <div class="img-preview-item"
                           :class="{'is-primary': p.primary}"
                           @click="setAddPrimary(i)">
                        <img :src="p.url" :alt="p.name">
                        <div class="img-actions">
                          <button type="button"
                                  class="btn btn-danger btn-sm p-0"
                                  style="width:20px;height:20px;font-size:10px;line-height:1"
                                  @click.stop="removeAddPreview(i)">
                            <i class="bi bi-x"></i>
                          </button>
                        </div>
                        <span x-show="p.primary" class="img-badge-primary">PRIMARY</span>
                      </div>
                    </template>
                  </div>
                </div>
              </div>

              {{-- Notes --}}
              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="Optional internal notes…">{{ old('notes') }}</textarea>
              </div>

              {{-- ── Registered / Marketed Countries ──────────────────── --}}
              <div class="col-12"><div class="section-label">Registered / Marketed Countries</div></div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-1 mb-2" x-show="addRegCountries.length">
                  <template x-for="(c, i) in addRegCountries" :key="c">
                    <span class="badge bg-primary d-flex align-items-center gap-1" style="font-size:12px">
                      <span x-text="c"></span>
                      <i class="bi bi-x-lg" style="cursor:pointer" @click="removeRegCountry('add', i)"></i>
                    </span>
                  </template>
                </div>
                <select class="form-select" x-model="addRegInput" @change="addRegCountry('add')">
                  <option value="">+ Add a country…</option>
                  @foreach($countries as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                  @endforeach
                </select>
                <div class="form-text" style="font-size:10px">Pick a country to add it. Manage the list in Master Data → Countries.</div>
                <template x-for="c in addRegCountries" :key="'h'+c">
                  <input type="hidden" name="countries[]" :value="c">
                </template>
              </div>

              {{-- ── Documents & Links ───────────────────────────────── --}}
              <div class="col-12"><div class="section-label">Documents &amp; Links</div></div>
              <div class="col-md-6">
                <label class="form-label">Website URL</label>
                <input type="url" name="website_url"
                       class="form-control @error('website_url') is-invalid @enderror"
                       value="{{ old('website_url') }}" placeholder="https://example.com">
                @error('website_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Product Document (PDF)</label>
                <input type="file" name="pdf"
                       class="form-control @error('pdf') is-invalid @enderror" accept="application/pdf">
                <div class="form-text" style="font-size:10px">PDF only · max 10 MB</div>
                @error('pdf')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              {{-- PRN hint --}}
              <div class="col-12">
                <div class="info-box info">
                  <i class="bi bi-info-circle text-primary mt-1"></i>
                  <div style="font-size:12px">
                    <strong>PRN auto-generated</strong> from Country of Origin + Dosage Form on save.
                    Format: <code>PRN-{COUNTRY}-{FORM}-{NNNNN}</code>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showAddModal=false; resetAdd()">Cancel</button>
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
  <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false; resetAdd()"></div>

</div>
@endsection

@push('scripts')
<style>
.section-label {
  font-size:11px;
  font-weight:700;
  letter-spacing:.06em;
  text-transform:uppercase;
  color:#6c757d;
  padding-bottom:4px;
  border-bottom:1px solid var(--border-color,#dee2e6);
}
</style>
<script>
function productsPage() {
  return {
    /* ── Modal state ─────────────────────────────────────────────── */
    showAddModal:  {{ $errors->any() ? 'true' : 'false' }},
    showViewModal: false,
    showEditModal: false,
    viewProduct:  null,
    editProduct:  null,
    viewLoading:  false,
    editLoading:  false,
    saving:       false,
    addErrors:    {},
    editErrors:   {},

    /* ── View modal gallery ──────────────────────────────────────── */
    activeImage: null,

    /* ── Add modal images ────────────────────────────────────────── */
    addDragOver:  false,
    addPreviews:  [],   // {url, name, primary}
    addFiles:     [],   // File objects

    /* ── Edit modal images ───────────────────────────────────────── */
    editDragOver:        false,
    editExistingImages:  [],   // from DB: {id, url, name, is_primary, toRemove}
    editNewPreviews:     [],   // {url, name, primary}
    editFiles:           [],   // File objects
    removeImageIds:      [],   // IDs to send as remove_images[]

    /* ── Registered countries (multi-select tags) ────────────────── */
    addRegCountries:  @json(old('countries', [])),
    addRegInput:      '',
    editRegCountries: [],
    editRegInput:     '',

    addRegCountry(mode) {
      const inputKey = mode === 'add' ? 'addRegInput'     : 'editRegInput';
      const listKey  = mode === 'add' ? 'addRegCountries' : 'editRegCountries';
      const val = (this[inputKey] || '').trim();
      if (!val) return;
      if (!this[listKey].some(c => c.toLowerCase() === val.toLowerCase())) {
        this[listKey].push(val);
      }
      this[inputKey] = '';
    },
    removeRegCountry(mode, i) {
      (mode === 'add' ? this.addRegCountries : this.editRegCountries).splice(i, 1);
    },

    /* ── Bulk select ─────────────────────────────────────────────── */
    selected: [],

    /* ── Therapeutic Class Combobox (options loaded dynamically from DB) ─── */
    tcOptions: @json($therapeuticClasses),
    // Add modal
    addTcValue: '{{ old("therapeutic_class","") }}',
    addTcQuery: '{{ old("therapeutic_class","") }}',
    addTcOpen:  false,
    addTcHl:    -1,
    // Edit modal
    editTcValue: '',
    editTcQuery: '',
    editTcOpen:  false,
    editTcHl:    -1,

    get addTcFiltered() {
      const q = this.addTcQuery.trim().toLowerCase();
      return q ? this.tcOptions.filter(o => o.toLowerCase().includes(q)) : this.tcOptions;
    },
    get editTcFiltered() {
      const q = this.editTcQuery.trim().toLowerCase();
      return q ? this.tcOptions.filter(o => o.toLowerCase().includes(q)) : this.tcOptions;
    },
    selectAddTc(opt)  { this.addTcValue  = opt; this.addTcQuery  = opt; this.addTcOpen  = false; this.addTcHl  = -1; },
    selectEditTc(opt) { this.editTcValue = opt; this.editTcQuery = opt; this.editTcOpen = false; this.editTcHl = -1; },

    /* ══ VIEW MODAL ════════════════════════════════════════════════ */
    openView(id) {
      this.viewProduct  = null;
      this.viewLoading  = true;
      this.showViewModal = true;
      this._fetchProduct(id).then(data => {
        this.viewProduct  = data;
        this.activeImage  = data.images?.[0]?.url ?? null;
        this.viewLoading  = false;
      });
    },

    /* ══ EDIT MODAL ════════════════════════════════════════════════ */
    openEdit(id) {
      this.editProduct        = null;
      this.editLoading        = true;
      this.editExistingImages = [];
      this.editNewPreviews    = [];
      this.editFiles          = [];
      this.removeImageIds     = [];
      this.editErrors         = {};
      this.editTcValue        = '';
      this.editTcQuery        = '';
      this.editTcOpen         = false;
      this.editRegCountries   = [];
      this.editRegInput       = '';
      this.saving             = false;
      this.showEditModal      = true;
      this._fetchProduct(id).then(data => {
        this.editProduct        = data;
        this.editExistingImages = (data.images ?? []).map(img => ({...img, toRemove: false}));
        this.editTcValue        = data.therapeutic_class ?? '';
        this.editTcQuery        = data.therapeutic_class ?? '';
        this.editRegCountries   = data.registered_countries ?? [];
        this.editLoading        = false;
      });
    },

    /* ── Shared fetch ────────────────────────────────────────────── */
    _fetchProduct(id) {
      return fetch(`{{ url('products') }}/${id}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
      .catch(() => { alert('Could not load product details.'); return {}; });
    },

    /* ══ IMAGE HANDLING — ADD ══════════════════════════════════════ */
    handleAddDrop(e)       { this._processFiles(e.dataTransfer.files, 'add'); },
    handleAddFileChange(e) { this._processFiles(e.target.files, 'add'); },

    _processFiles(fileList, mode) {
      const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
      Array.from(fileList).forEach(file => {
        if (!allowed.includes(file.type)) return;
        const total = mode === 'add'
          ? this.addPreviews.length
          : this.editNewPreviews.length + this.editExistingImages.filter(i => !i.toRemove).length;
        if (total >= 10) { alert('Maximum 10 images allowed.'); return; }
        const url      = URL.createObjectURL(file);
        const isPrimary = mode === 'add'
          ? this.addPreviews.length === 0
          : false;
        if (mode === 'add') {
          this.addPreviews.push({ url, name: file.name, primary: isPrimary });
          this.addFiles.push(file);
        } else {
          this.editNewPreviews.push({ url, name: file.name, primary: false });
          this.editFiles.push(file);
        }
      });
      // Reset input so same file can be re-selected
      if (mode === 'add' && this.$refs.addFileInput)  this.$refs.addFileInput.value  = '';
      if (mode === 'edit' && this.$refs.editFileInput) this.$refs.editFileInput.value = '';
    },

    removeAddPreview(i) {
      URL.revokeObjectURL(this.addPreviews[i].url);
      this.addPreviews.splice(i, 1);
      this.addFiles.splice(i, 1);
      if (this.addPreviews.length && !this.addPreviews.some(p => p.primary)) {
        this.addPreviews[0].primary = true;
      }
    },

    setAddPrimary(i) {
      this.addPreviews.forEach((p, idx) => p.primary = (idx === i));
    },

    /* ══ IMAGE HANDLING — EDIT ═════════════════════════════════════ */
    handleEditDrop(e)       { this._processFiles(e.dataTransfer.files, 'edit'); },
    handleEditFileChange(e) { this._processFiles(e.target.files, 'edit'); },

    removeEditImage(i) {
      const id = this.editExistingImages[i].id;
      if (!this.removeImageIds.includes(id)) this.removeImageIds.push(id);
      this.editExistingImages[i].toRemove = true;
    },
    restoreEditImage(i) {
      const id = this.editExistingImages[i].id;
      this.removeImageIds = this.removeImageIds.filter(x => x !== id);
      this.editExistingImages[i].toRemove = false;
    },
    setEditExistingPrimary(i) {
      this.editExistingImages.forEach((img, idx) => img.is_primary = (idx === i));
      this.editNewPreviews.forEach(p => p.primary = false);
    },
    removeEditNew(i) {
      URL.revokeObjectURL(this.editNewPreviews[i].url);
      this.editNewPreviews.splice(i, 1);
      this.editFiles.splice(i, 1);
    },

    get editPrimaryImageId() {
      const p = this.editExistingImages.find(img => img.is_primary && !img.toRemove);
      return p ? p.id : null;
    },

    /* ══ FORM SUBMISSIONS ══════════════════════════════════════════ */
    async submitAddForm() {
      this.saving    = true;
      this.addErrors = {};

      await this.$nextTick(); // let Alpine flush reactive bindings into the form

      const formData = new FormData(this.$refs.addForm);
      // Replace native file input with our managed files
      formData.delete('images[]');
      this.addFiles.forEach(f => formData.append('images[]', f));
      const primaryIdx = this.addPreviews.findIndex(p => p.primary);
      if (primaryIdx >= 0) formData.set('primary_image_index', primaryIdx);

      try {
        const res  = await fetch('{{ route("products.store") }}', {
          method:  'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          body:    formData,
        });
        const data = await res.json();
        if (data.success) {
          window.location.href = data.redirect;
        } else {
          this.addErrors = data.errors ?? {};
          this.saving    = false;
        }
      } catch (e) {
        alert('Server error. Please try again.');
        this.saving = false;
      }
    },

    async submitEditForm() {
      this.saving     = true;
      this.editErrors = {};

      await this.$nextTick(); // let Alpine flush reactive bindings into the form

      const formData = new FormData(this.$refs.editForm);
      formData.set('_method', 'PUT');

      // New files
      formData.delete('images[]');
      this.editFiles.forEach(f => formData.append('images[]', f));

      // Remove IDs
      formData.delete('remove_images[]');
      this.removeImageIds.forEach(id => formData.append('remove_images[]', id));

      // Primary image
      if (this.editPrimaryImageId) formData.set('primary_image_id', this.editPrimaryImageId);

      const url = `{{ url('products') }}/${this.editProduct.id}`;
      try {
        const res  = await fetch(url, {
          method:  'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          body:    formData,
        });
        const data = await res.json();
        if (data.success) {
          window.location.href = data.redirect;
        } else {
          this.editErrors = data.errors ?? {};
          this.saving     = false;
        }
      } catch (e) {
        alert('Server error. Please try again.');
        this.saving = false;
      }
    },

    /* ── Helpers ─────────────────────────────────────────────────── */
    resetAdd() {
      this.addPreviews.forEach(p => URL.revokeObjectURL(p.url));
      this.addPreviews = [];
      this.addFiles    = [];
      this.addErrors   = {};
      this.addTcValue  = '';
      this.addTcQuery  = '';
      this.addTcOpen   = false;
      this.addTcHl     = -1;
      this.addRegCountries = [];
      this.addRegInput = '';
      this.saving      = false;
    },

    confirmDelete(event, name) {
      if (confirm(`Remove product "${name}"?\n\nThis will soft-delete the record.`)) {
        event.target.submit();
      }
    },

    toggleAll(checked) {
      const boxes = document.querySelectorAll('.row-check');
      this.selected = checked ? [...boxes].map(c => c.value) : [];
      boxes.forEach(c => c.checked = checked);
    },
  };
}
</script>
@endpush
