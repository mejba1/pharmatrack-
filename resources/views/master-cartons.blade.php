@extends('layouts.app')
@section('title', 'Master Carton Management')

@push('styles')
<style>
  [x-cloak] { display:none !important; }
  .modal-dialog-scrollable > .modal-content { max-height: calc(100vh - 3.5rem); overflow: hidden; }
  .modal-content > form, .modal-content > .modal-flex { display:flex; flex-direction:column; flex:1 1 auto; min-height:0; overflow:hidden; }
  .modal-content > form > .modal-body, .modal-content > .modal-flex > .modal-body { flex:1 1 auto; overflow-y:auto; min-height:0; }
  .section-label { font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
    color:#6c757d; padding-bottom:4px; border-bottom:1px solid var(--border-color,#dee2e6); margin-bottom:4px; }
  .carton-qr { width:46px; height:46px; }
  .carton-qr canvas, .carton-qr img { width:46px !important; height:46px !important; border-radius:4px; }
  .info-tile { display:flex; align-items:center; gap:10px; padding:9px 11px; border:1px solid var(--border-color,#e9ecef); border-radius:11px; height:100%; min-width:0; }
  .info-ic { width:34px; height:34px; border-radius:9px; flex-shrink:0; font-size:15px; display:flex; align-items:center; justify-content:center; background:rgba(13,110,253,.08); color:#0d6efd; }
  .calc-box { border:1px dashed var(--border-color,#cdd3da); border-radius:12px; padding:14px 16px; background:linear-gradient(180deg, rgba(13,110,253,.04), rgba(13,110,253,.01)); }
  .calc-num { font-size:26px; font-weight:700; color:#0d6efd; }
  .mode-pill { border:1px solid var(--border-color,#dee2e6); border-radius:10px; padding:8px 12px; cursor:pointer; flex:1; text-align:center; transition:all .15s ease; }
  .mode-pill:hover { border-color:#9ec5fe; }
  .mode-pill.on { border-color:#0d6efd; background:rgba(13,110,253,.06); box-shadow:0 0 0 1px #0d6efd inset; }
  .cap-meter { height:8px; border-radius:5px; background:#e9ecef; overflow:hidden; }
  .cap-meter > span { display:block; height:100%; background:#0d6efd; transition:width .3s ease; }

  /* ── Workflow guide strip ── */
  .flow-strip { display:flex; align-items:stretch; gap:0; flex-wrap:wrap; }
  .flow-step { display:flex; align-items:center; gap:10px; padding:10px 16px 10px 14px; position:relative; flex:1 1 0; min-width:170px; }
  .flow-step:not(:last-child)::after { content:'\F285'; font-family:'bootstrap-icons'; position:absolute; right:-7px; top:50%;
    transform:translateY(-50%); color:#ced4da; font-size:14px; z-index:1; }
  .flow-num { width:30px; height:30px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:13px; background:rgba(13,110,253,.1); color:#0d6efd; }
  .flow-step .flow-t { font-weight:600; font-size:13px; line-height:1.1; }
  .flow-step .flow-d { font-size:11px; color:#6c757d; }

  /* ── Carton table polish ── */
  .table-card thead th { font-size:11px; letter-spacing:.04em; text-transform:uppercase; color:#6c757d; font-weight:700; }
  .table-card tbody tr { transition:background .12s ease; }
  .fill-cell { min-width:130px; }
  .fill-meter { height:7px; border-radius:5px; background:#eef1f5; overflow:hidden; }
  .fill-meter > span { display:block; height:100%; border-radius:5px; transition:width .3s ease; }
  .fill-0 { background:#ced4da; }
  .fill-mid { background:#ffc107; }
  .fill-full { background:#198754; }
  .status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; vertical-align:middle; }
  .act-btn { width:34px; height:34px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:9px; }
  .bulk-bar { background:linear-gradient(180deg, rgba(13,110,253,.07), rgba(13,110,253,.02)); }
</style>
@endpush

@section('content')
<div x-data="cartonPage()">

  @if(session('success'))
  <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill text-success"></i><span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1>Master Carton Management</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Master Carton Management</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" @click="openLabels()"><i class="bi bi-printer me-1"></i>Labels</button>
      <button class="btn btn-outline-primary btn-sm" @click="openPack()"><i class="bi bi-box-seam me-1"></i>Pack Carton</button>
      <button class="btn btn-primary btn-sm" @click="openCreate()"><i class="bi bi-plus-lg me-1"></i>Create Cartons</button>
    </div>
  </div>

  {{-- Workflow guide --}}
  <div class="card mb-3"><div class="card-body p-1">
    <div class="flow-strip">
      <div class="flow-step"><span class="flow-num">1</span><div><div class="flow-t">Create</div><div class="flow-d">Generate empty cartons</div></div></div>
      <div class="flow-step"><span class="flow-num">2</span><div><div class="flow-t">Pack</div><div class="flow-d">Add serial ranges</div></div></div>
      <div class="flow-step"><span class="flow-num">3</span><div><div class="flow-t">Dispatch</div><div class="flow-d">Out from factory</div></div></div>
      <div class="flow-step"><span class="flow-num">4</span><div><div class="flow-t">Receive</div><div class="flow-d">In at the depot</div></div></div>
    </div>
  </div></div>

  {{-- Stats --}}
  <div class="row g-2 mb-3">
    <div class="col-6 col-md"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-value">{{ number_format($stats['total']) }}</div><div class="stat-label">Total Cartons</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-box2-heart"></i></div><div><div class="stat-value">{{ number_format($stats['packed']) }}</div><div class="stat-label">Packed</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-secondary"><div class="stat-icon"><i class="bi bi-box"></i></div><div><div class="stat-value">{{ number_format($stats['empty']) }}</div><div class="stat-label">Empty / Unpacked</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-box-arrow-up"></i></div><div><div class="stat-value">{{ number_format($stats['dispatched']) }}</div><div class="stat-label">Dispatched</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></div><div><div class="stat-value">{{ number_format($stats['received']) }}</div><div class="stat-label">Received</div></div></div></div>
  </div>

  {{-- Batch-wise summary --}}
  @if($summary->count())
  <div class="card mb-3">
    <div class="card-header bg-transparent fw-semibold"><i class="bi bi-clipboard-data me-1"></i>Batch-wise Carton Summary</div>
    <div class="card-body p-0"><div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead><tr>
          <th>Batch</th><th>Product</th><th class="text-end">Total Qty</th><th class="text-end">Cartons</th>
          <th class="text-end">Remaining Cartons</th><th class="text-end">Packed Units</th><th class="text-end">Unpacked</th><th style="width:120px"></th>
        </tr></thead>
        <tbody>
          @foreach($summary as $s)
          @php $bid = $s['batch']?->id; @endphp
          <tr>
            <td class="font-monospace" style="font-size:12px">
              @if($s['cartons_list']->count())
              <button class="btn btn-link btn-sm p-0 me-1 text-decoration-none" @click="toggleBatch({{ $bid }})" title="Show packed cartons">
                <i class="bi" :class="expandedBatch==={{ $bid }} ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
              </button>
              @endif
              {{ $s['batch']?->brn ?? '—' }}
            </td>
            <td style="font-size:13px">{{ $s['batch']?->product?->name ?? '—' }}</td>
            <td class="text-end">{{ number_format($s['total']) }}</td>
            <td class="text-end">{{ number_format($s['cartons']) }}</td>
            <td class="text-end {{ $s['remaining_cartons'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">{{ number_format($s['remaining_cartons']) }}</td>
            <td class="text-end text-success fw-semibold">{{ number_format($s['packed']) }}</td>
            <td class="text-end {{ $s['unpacked'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">{{ number_format($s['unpacked']) }}</td>
            <td class="text-end">
              @if($s['batch'])
              <a href="{{ route('master-cartons.labels', ['batch_id'=>$s['batch']->id]) }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Print labels"><i class="bi bi-printer"></i></a>
              <a href="{{ route('master-cartons.labels-pdf', ['batch_id'=>$s['batch']->id]) }}" class="btn btn-outline-danger btn-sm" title="Labels PDF"><i class="bi bi-file-earmark-pdf"></i></a>
              @endif
            </td>
          </tr>
          @if($s['cartons_list']->count())
          <tr x-show="expandedBatch==={{ $bid }}" x-cloak>
            <td colspan="8" class="bg-light">
              <div class="d-flex flex-wrap gap-2 py-1">
                <span class="text-muted-sm align-self-center me-1"><i class="bi bi-box-seam me-1"></i>Packed cartons:</span>
                @foreach($s['cartons_list'] as $cl)
                <span class="badge bg-white border text-dark d-inline-flex align-items-center gap-2 py-1 px-2">
                  <span class="font-monospace fw-semibold">{{ $cl['carton_number'] }}</span>
                  <span class="text-muted">· qty {{ number_format($cl['qty']) }}</span>
                  @if($cl['mixed'])<span class="badge text-bg-warning" style="font-size:9px" title="This carton also holds other products/batches">mixed</span>@endif
                  <button class="btn btn-link btn-sm p-0 text-primary" title="View packing details" @click="openView({{ $cl['id'] }})"><i class="bi bi-eye"></i></button>
                </span>
                @endforeach
              </div>
            </td>
          </tr>
          @endif
          @endforeach
        </tbody>
      </table>
    </div></div>
  </div>
  @endif

  {{-- Filters --}}
  <div class="card mb-3"><div class="card-body py-2">
    <form method="GET" action="{{ route('master-cartons') }}" class="row g-2 align-items-center"
          x-data="{ fp: '{{ $filters['product_id'] ?? '' }}' }">
      <div class="col-6 col-md-3">
        <select name="product_id" class="form-select form-select-sm" x-model="fp">
          <option value="">All Products</option>
          @foreach($products as $p)<option value="{{ $p->id }}" {{ (string)($filters['product_id'] ?? '')===(string)$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="batch_id" class="form-select form-select-sm">
          <option value="">All Batches</option>
          @foreach($batches as $b)
            <option value="{{ $b->id }}"
                    x-show="!fp || fp==='{{ $b->product_id }}'"
                    {{ (string)($filters['batch_id'] ?? '')===(string)$b->id?'selected':'' }}>{{ $b->brn }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4"><div class="search-wrapper"><i class="bi bi-search search-icon"></i>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Carton no / QR / batch / product…" value="{{ $filters['search'] ?? '' }}"></div></div>
      <div class="col-6 col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          @foreach(['created'=>'Created','packed'=>'Packed','dispatched'=>'Dispatched','received'=>'Received'] as $v=>$l)
            <option value="{{ $v }}" {{ ($filters['status'] ?? '')===$v?'selected':'' }}>{{ $l }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="fill" class="form-select form-select-sm" title="Pack fill">
          <option value="">Any Fill</option>
          @foreach(['empty'=>'Unpacked (empty)','partial'=>'Partially packed','full'=>'Fully packed'] as $v=>$l)
            <option value="{{ $v }}" {{ ($filters['fill'] ?? '')===$v?'selected':'' }}>{{ $l }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-2">
        <div class="input-group input-group-sm" title="Created from">
          <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
          <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="input-group input-group-sm" title="Created to">
          <span class="input-group-text">→</span>
          <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
        </div>
      </div>
      <div class="col-6 col-md-2 d-flex gap-1">
        <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="{{ route('master-cartons') }}" class="btn btn-outline-secondary btn-sm" title="Clear all filters"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div></div>

  {{-- Cartons table (Master Carton Report) --}}
  <div class="card table-card"><div class="card-body p-0">

    {{-- Bulk action toolbar --}}
    <div class="bulk-bar px-3 py-2 d-flex flex-wrap align-items-center gap-2 border-bottom" x-show="selected.length" x-cloak>
      <span class="fw-semibold"><i class="bi bi-check2-square me-1 text-primary"></i><span x-text="selected.length"></span> selected</span>
      <button class="btn btn-outline-primary btn-sm" @click="downloadSelected('print')"><i class="bi bi-printer me-1"></i>Print labels</button>
      <button class="btn btn-danger btn-sm" @click="downloadSelected('pdf')"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</button>
      <button class="btn btn-link btn-sm text-muted ms-auto text-decoration-none" @click="clearSel()">Clear selection</button>
    </div>

    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr>
        <th style="width:38px" class="text-center">
          <input type="checkbox" class="form-check-input" title="Select all on this page"
                 :checked="allChecked" @change="toggleAll($event)">
        </th>
        <th style="width:64px">QR</th><th>Carton No</th><th>Product / Batch</th>
        <th class="fill-cell">Fill</th><th>Serial Range</th>
        <th>Status</th><th>Created</th><th class="text-end" style="width:130px">Actions</th>
      </tr></thead>
      <tbody>
        @forelse($cartons as $c)
        @php
          $pct  = $c->capacity > 0 ? min(100, round($c->packed_quantity / $c->capacity * 100)) : 0;
          $fill = $pct === 0 ? 'fill-0' : ($pct >= 100 ? 'fill-full' : 'fill-mid');
          $dot  = match($c->status) { 'received'=>'#0a9ab7', 'dispatched'=>'#c97b00', 'packed'=>'#198754', default=>'#adb5bd' };
        @endphp
        <tr :class="selected.includes('{{ $c->id }}') ? 'table-active' : ''">
          <td class="text-center">
            <input type="checkbox" class="form-check-input" value="{{ $c->id }}" x-model="selected">
          </td>
          <td><div class="carton-qr mx-auto" data-qr="{{ route('carton.scan', $c->qr_code) }}"></div></td>
          <td>
            <span class="fw-semibold font-monospace" style="font-size:12px">{{ $c->carton_number }}</span>
            @if($c->carton_type === 'generic')<span class="badge text-bg-light ms-1" style="font-size:9px">generic</span>@endif
            @if($c->is_mixed)<span class="badge text-bg-warning ms-1" style="font-size:9px">mixed</span>@endif
          </td>
          <td>
            <div style="font-size:13px">{{ $c->products_summary }}</div>
            <div class="text-muted-sm font-monospace">{{ $c->batches_summary }}</div>
          </td>
          <td class="fill-cell">
            <div class="d-flex justify-content-between mb-1" style="font-size:11px">
              <span class="{{ $c->packed_quantity>0 ? 'text-success fw-semibold' : 'text-muted' }}">{{ number_format($c->packed_quantity) }} / {{ number_format($c->capacity) }}</span>
              <span class="text-muted">{{ $pct }}%</span>
            </div>
            <div class="fill-meter"><span class="{{ $fill }}" style="width:{{ max($pct, $c->packed_quantity>0 ? 4 : 0) }}%"></span></div>
          </td>
          <td class="font-monospace" style="font-size:12px">{{ $c->serial_range }}</td>
          <td><span class="badge-status {{ $c->status_badge_class }}"><span class="status-dot" style="background:{{ $dot }}"></span>{{ $c->status_label }}</span></td>
          <td style="font-size:12px" class="text-muted">{{ $c->created_at?->format('M d, Y') }}</td>
          <td class="text-end"><div class="d-flex gap-1 justify-content-end">
            <button class="btn btn-outline-primary btn-sm act-btn" title="View / track" @click="openView({{ $c->id }})"><i class="bi bi-eye"></i></button>
            <button class="btn btn-outline-info btn-sm act-btn" title="Pack serials" @click="openPack({{ $c->id }})" :disabled="false"><i class="bi bi-box-seam"></i></button>
            <form method="POST" action="{{ route('master-cartons.destroy', $c) }}" @submit.prevent="confirmDelete($event, '{{ $c->carton_number }}')">
              @csrf @method('DELETE')
              <button class="btn btn-outline-danger btn-sm act-btn" title="Remove"><i class="bi bi-trash"></i></button>
            </form>
          </div></td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center py-5 text-muted">
          <i class="bi bi-box-seam" style="font-size:32px;opacity:.2"></i>
          <div class="mt-2">No master cartons match. Adjust the filters or click <strong>Create Cartons</strong> to start.</div>
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($cartons->hasPages())
  <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top flex-wrap gap-2">
    <div class="text-muted-sm">Showing <strong>{{ $cartons->firstItem() }}–{{ $cartons->lastItem() }}</strong> of <strong>{{ number_format($cartons->total()) }}</strong> cartons</div>
    {{ $cartons->links('pagination::bootstrap-5') }}
  </div>
  @endif
  </div></div>

  {{-- ═══════════ LABELS MODAL ═══════════ --}}
  <div class="modal fade" :class="{show:showLabels}" :style="showLabels?'display:block':''" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-printer me-2 text-primary"></i>Download / Print Carton Labels</h5>
        <button class="btn-close" @click="showLabels=false"></button>
      </div>
      <div class="modal-body">
        <div class="section-label">Scope</div>
        <div class="row g-2 mb-3">
          <div class="col-6"><div class="mode-pill" :class="{on: lblScope==='all'}" @click="lblScope='all'"><i class="bi bi-grid me-1"></i>All cartons</div></div>
          <div class="col-6"><div class="mode-pill" :class="{on: lblScope==='generic'}" @click="lblScope='generic'"><i class="bi bi-box me-1"></i>Generic only</div></div>
          <div class="col-6"><div class="mode-pill" :class="{on: lblScope==='product'}" @click="lblScope='product'; lblBatchId=''"><i class="bi bi-capsule me-1"></i>By product</div></div>
          <div class="col-6"><div class="mode-pill" :class="{on: lblScope==='batch'}" @click="lblScope='batch'"><i class="bi bi-upc-scan me-1"></i>By product &amp; batch</div></div>
        </div>

        <div x-show="lblScope==='product' || lblScope==='batch'" x-cloak class="mb-2">
          <label class="form-label">Product <span class="text-danger">*</span></label>
          <select class="form-select" x-model="lblProductId" @change="lblOnProduct()">
            <option value="">Select product…</option>
            @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
          </select>
        </div>
        <div x-show="lblScope==='batch'" x-cloak class="mb-2">
          <label class="form-label">Batch <span class="text-danger">*</span></label>
          <select class="form-select" x-model="lblBatchId" :disabled="!lblProductId || lblLoadingB">
            <option value="" x-text="!lblProductId ? 'Select a product first…' : (lblBatches.length ? 'Select batch…' : 'No batches')"></option>
            <template x-for="b in lblBatches" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
          </select>
        </div>
        <div class="text-muted-sm" x-show="lblScope==='all'">Labels for every master carton (generic + product/batch).</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" @click="showLabels=false">Cancel</button>
        <button class="btn btn-outline-primary" :disabled="!lblReady" @click="doLabels('print')"><i class="bi bi-printer me-1"></i>Print</button>
        <button class="btn btn-danger" :disabled="!lblReady" @click="doLabels('pdf')"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</button>
      </div>
    </div></div>
  </div>
  <div class="modal-backdrop fade show" x-show="showLabels" @click="showLabels=false"></div>

  {{-- ═══════════ CREATE MODAL ═══════════ --}}
  <div class="modal fade" :class="{show:showCreate}" :style="showCreate?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>Create Master Cartons</h5>
        <button class="btn-close" @click="showCreate=false"></button>
      </div>
      <form @submit.prevent="submitCreate()">
        @csrf
        <div class="modal-body">
          <template x-if="Object.keys(cErrors).length">
            <div class="alert alert-danger mb-3"><ul class="mb-0 ps-3" style="font-size:13px"><template x-for="(m,f) in cErrors" :key="f"><template x-for="x in m" :key="x"><li x-text="x"></li></template></template></ul></div>
          </template>

          {{-- Mode --}}
          <div class="section-label">Carton Type</div>
          <div class="d-flex gap-2 mb-3">
            <div class="mode-pill" :class="{on: cType==='standard'}" @click="cType='standard'">
              <div class="fw-semibold"><i class="bi bi-upc-scan me-1"></i>For a specific batch</div>
              <div class="text-muted-sm">Auto-calculates carton count from batch quantity.</div>
            </div>
            <div class="mode-pill" :class="{on: cType==='generic'}" @click="cType='generic'">
              <div class="fw-semibold"><i class="bi bi-box me-1"></i>Generic (no product/batch)</div>
              <div class="text-muted-sm">Empty cartons; assign products/batches when packing.</div>
            </div>
          </div>

          {{-- Standard: product + batch --}}
          <div x-show="cType==='standard'">
            <div class="section-label">Product &amp; Batch</div>
            <div class="row g-3 mb-2">
              <div class="col-md-6">
                <label class="form-label">Select Product <span class="text-danger">*</span></label>
                <select class="form-select" x-model="cProductId" @change="cOnProduct()">
                  <option value="">Select product…</option>
                  @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Select Batch <span class="text-danger">*</span></label>
                <select class="form-select" x-model="cBatchId" @change="cOnBatch()" :disabled="!cProductId || cLoadingB">
                  <option value="" x-text="!cProductId ? 'Select a product first…' : (cBatches.length ? 'Select batch…' : 'No batches')"></option>
                  <template x-for="b in cBatches" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
                </select>
              </div>
            </div>
            <div class="row g-2 mb-3" x-show="cInfo" x-cloak>
              <div class="col-6 col-md-3"><div class="info-tile"><div class="info-ic"><i class="bi bi-upc"></i></div><div><div class="text-muted-sm">Batch Ref</div><div class="fw-semibold font-monospace text-truncate" x-text="cInfo?.brn"></div></div></div></div>
              <div class="col-6 col-md-3"><div class="info-tile"><div class="info-ic"><i class="bi bi-calendar-check"></i></div><div><div class="text-muted-sm">Mfg</div><div class="fw-semibold" x-text="fmtDate(cInfo?.manufacture_date)"></div></div></div></div>
              <div class="col-6 col-md-3"><div class="info-tile"><div class="info-ic"><i class="bi bi-calendar-x"></i></div><div><div class="text-muted-sm">Expiry</div><div class="fw-semibold" x-text="fmtDate(cInfo?.expiry_date)"></div></div></div></div>
              <div class="col-6 col-md-3"><div class="info-tile"><div class="info-ic"><i class="bi bi-box"></i></div><div><div class="text-muted-sm">Total / Avail</div><div class="fw-semibold"><span x-text="(cInfo?.total_quantity??0).toLocaleString()"></span> / <span x-text="(cInfo?.quantity_available??0).toLocaleString()"></span></div></div></div></div>
            </div>
          </div>

          {{-- Generic: label --}}
          <div x-show="cType==='generic'" x-cloak>
            <div class="section-label">Generic Carton</div>
            <div class="mb-3">
              <label class="form-label">Label / Reference (optional)</label>
              <input type="text" class="form-control" x-model="cLabel" placeholder="e.g. Depot transfer – Zone A">
            </div>
          </div>

          {{-- Capacity & count --}}
          <div class="section-label">Carton Capacity</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Products per Master Carton <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" x-model.number="cCapacity" @input="cManual=false" placeholder="e.g. 50">
            </div>
            <div class="col-md-8">
              <div class="calc-box d-flex align-items-center justify-content-between gap-3">
                <div x-show="cType==='standard'">
                  <div class="text-muted-sm">Required Master Cartons (auto)</div>
                  <div class="calc-num" x-text="(cAutoCount||0).toLocaleString()"></div>
                  <div class="text-muted-sm" x-show="cCapacity>0 && cInfo">= ceil(<span x-text="(cInfo?.total_quantity??0).toLocaleString()"></span> ÷ <span x-text="cCapacity"></span>)</div>
                </div>
                <div x-show="cType==='generic'" class="text-muted-sm">Generic cartons have no batch quantity — set the number of cartons directly.</div>
                <div style="width:180px">
                  <label class="form-check mb-1" style="font-size:13px" x-show="cType==='standard'">
                    <input type="checkbox" class="form-check-input me-1" x-model="cManual"> Manual count
                  </label>
                  <label class="form-label" x-show="cType==='generic'">Number of Cartons <span class="text-danger">*</span></label>
                  <input type="number" min="1" class="form-control" :disabled="cType==='standard' && !cManual" x-model.number="cCount" :placeholder="cType==='standard' ? cAutoCount : 'e.g. 10'">
                </div>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <input type="text" class="form-control" x-model="cNotes" placeholder="Optional">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" @click="showCreate=false">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="cSaving || !cCanSubmit">
            <span x-show="cSaving" class="spinner-border spinner-border-sm me-1"></span>
            <span x-text="cSaving ? 'Generating…' : ('Generate ' + (cEffectiveCount||0) + ' Carton(s)')"></span>
          </button>
        </div>
      </form>
    </div></div>
  </div>
  <div class="modal-backdrop fade show" x-show="showCreate" @click="showCreate=false"></div>

  {{-- ═══════════ PACK MODAL (multi-segment) ═══════════ --}}
  <div class="modal fade" :class="{show:showPack}" :style="showPack?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-flex">
        <div class="modal-header">
          <h5 class="modal-title fw-semibold"><i class="bi bi-box-seam me-2 text-info"></i>Pack Carton Contents</h5>
          <button class="btn-close" @click="closePack()"></button>
        </div>
        <div class="modal-body">
          {{-- Carton availability mode --}}
          <div class="section-label">Carton Availability</div>
          <div class="d-flex gap-2 mb-3">
            <div class="mode-pill" :class="{on: pkMode==='standard'}" @click="setPkMode('standard')">
              <div class="fw-semibold"><i class="bi bi-upc-scan me-1"></i>Product / Batch wise</div>
              <div class="text-muted-sm">Cartons created for a specific batch.</div>
            </div>
            <div class="mode-pill" :class="{on: pkMode==='generic'}" @click="setPkMode('generic')">
              <div class="fw-semibold"><i class="bi bi-box me-1"></i>Generic cartons</div>
              <div class="text-muted-sm">Empty cartons; pack any product/batch.</div>
            </div>
          </div>

          {{-- Standard: choose product + batch first --}}
          <div class="row g-3 mb-2" x-show="pkMode==='standard'">
            <div class="col-md-6">
              <label class="form-label">Select Product <span class="text-danger">*</span></label>
              <select class="form-select" x-model="pmProductId" @change="pmOnProduct()">
                <option value="">Select product…</option>
                @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Select Batch <span class="text-danger">*</span></label>
              <select class="form-select" x-model="pmBatchId" @change="pmOnBatch()" :disabled="!pmProductId || pmLoadingB">
                <option value="" x-text="!pmProductId ? 'Select a product first…' : (pmBatches.length ? 'Select batch…' : 'No batches')"></option>
                <template x-for="b in pmBatches" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
              </select>
            </div>
          </div>

          {{-- Carton select --}}
          <div class="row g-3 mb-2">
            <div class="col-md-7">
              <label class="form-label">Select Master Carton <span class="text-danger">*</span></label>
              <select class="form-select" x-model="pkCartonId" @change="pkOnCarton()" :disabled="pkLoading || (pkMode==='standard' && !pmBatchId)">
                <option value="" x-text="cartonPlaceholder()"></option>
                <template x-for="c in pkCartonOptions" :key="c.id"><option :value="c.id" x-text="c.label"></option></template>
              </select>
            </div>
            <div class="col-md-5" x-show="pkCarton" x-cloak>
              <label class="form-label">Capacity</label>
              <div class="cap-meter mb-1"><span :style="`width:${pkCarton ? Math.round(pkCarton.packed_quantity/pkCarton.capacity*100) : 0}%`"></span></div>
              <div class="text-muted-sm"><span x-text="pkCarton?.packed_quantity"></span> / <span x-text="pkCarton?.capacity"></span> packed ·
                <strong x-text="pkCarton?.remaining"></strong> free</div>
            </div>
          </div>

          <template x-if="pkCartonId">
            <div>
              {{-- Current contents --}}
              <div class="section-label mt-3">Carton Contents <span x-show="pkContents.length" x-text="'· '+pkContents.length+' segment(s)'"></span></div>
              <div x-show="!pkContents.length" class="text-muted-sm py-2">No serials packed yet. Add a segment below.</div>
              <div class="table-responsive" x-show="pkContents.length">
                <table class="table table-sm align-middle mb-2">
                  <thead><tr><th>Product</th><th>Batch</th><th>Serials</th><th class="text-end">Qty</th><th></th></tr></thead>
                  <tbody>
                    <template x-for="ct in pkContents" :key="ct.id">
                      <tr>
                        <td style="font-size:13px" x-text="ct.product_name"></td>
                        <td class="font-monospace" style="font-size:12px" x-text="ct.brn"></td>
                        <td class="font-monospace" style="font-size:12px" x-text="ct.serial_start+'–'+ct.serial_end"></td>
                        <td class="text-end" x-text="ct.quantity"></td>
                        <td class="text-end"><button class="btn btn-outline-danger btn-sm btn-icon" @click="removeSeg(ct.id)" :disabled="pkBusy"><i class="bi bi-x-lg"></i></button></td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>

              {{-- Add a segment --}}
              <div class="section-label mt-2">Add Serials <span class="text-muted-sm" x-show="pkMode==='generic'">(choose product · batch)</span></div>
              <template x-if="Object.keys(pErrors).length">
                <div class="alert alert-danger py-2 mb-2"><ul class="mb-0 ps-3" style="font-size:13px"><template x-for="(m,f) in pErrors" :key="f"><template x-for="x in m" :key="x"><li x-text="x"></li></template></template></ul></div>
              </template>
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">Product</label>
                  <select class="form-select form-select-sm" x-model="segProductId" @change="segOnProduct()" :disabled="pkMode==='standard'">
                    <option value="">Select…</option>
                    @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Batch</label>
                  <select class="form-select form-select-sm" x-model="segBatchId" @change="segOnBatch()" :disabled="pkMode==='standard' || !segProductId || segLoadingB">
                    <option value="" x-text="!segProductId ? 'Product first…' : (segBatches.length ? 'Select…' : 'No batches')"></option>
                    <template x-for="b in segBatches" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
                  </select>
                </div>
                <div class="col-6 col-md-2">
                  <label class="form-label">Start <i class="bi bi-magic text-primary" title="auto-filled with the next available serial"></i></label>
                  <input type="number" min="1" class="form-control form-control-sm" x-model.number="segStart" @input="prefillEnd()">
                </div>
                <div class="col-6 col-md-2">
                  <label class="form-label">End <span class="text-danger">*</span></label>
                  <input type="number" min="1" class="form-control form-control-sm" x-model.number="segEnd">
                </div>
              </div>
              <div class="d-flex align-items-center justify-content-between mt-2">
                <div class="text-muted-sm" x-show="segStart>0 && segEnd>=segStart">
                  Qty <strong x-text="(segEnd-segStart+1).toLocaleString()"></strong>
                  <span x-show="segMaxSerial && segEnd>segMaxSerial" class="text-danger">· batch ends at <span x-text="segMaxSerial"></span></span>
                  <span x-show="pkCarton && (segEnd-segStart+1) > pkCarton.remaining" class="text-danger">· exceeds remaining (<span x-text="pkCarton?.remaining"></span>)</span>
                </div>
                <button class="btn btn-info text-white btn-sm" @click="addSeg()" :disabled="pkBusy || !segValid">
                  <span x-show="pkBusy" class="spinner-border spinner-border-sm me-1"></span><i class="bi bi-plus-lg me-1" x-show="!pkBusy"></i>Add to carton
                </button>
              </div>
            </div>
          </template>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="closePack()">Done</button>
        </div>
      </div>
    </div></div>
  </div>
  <div class="modal-backdrop fade show" x-show="showPack" @click="closePack()"></div>

  {{-- ═══════════ VIEW / TRACK MODAL ═══════════ --}}
  <div class="modal fade" :class="{show:showView}" :style="showView?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title fw-semibold"><i class="bi bi-box-seam me-2 text-primary"></i><span x-text="vCarton?.carton_number ?? 'Loading…'"></span></h5>
          <code class="text-muted-sm" x-text="vCarton ? (vCarton.products_summary + ' · ' + vCarton.batches_summary) : ''"></code>
        </div>
        <button class="btn-close ms-auto" @click="showView=false"></button>
      </div>
      <div class="modal-body">
        <div x-show="vLoading" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>
        <template x-if="!vLoading && vCarton">
          <div class="row g-3">
            <div class="col-md-4 text-center">
              <div class="d-inline-block p-2 border rounded"><div id="viewCartonQr"></div></div>
              <div class="font-monospace text-muted-sm mt-1" x-text="vCarton?.qr_code"></div>
              <div class="mt-1">
                <span class="badge text-bg-light" x-show="vCarton?.carton_type==='generic'">generic</span>
                <span class="badge text-bg-warning" x-show="vCarton?.is_mixed">mixed</span>
              </div>
            </div>
            <div class="col-md-8"><div class="row g-2">
              <div class="col-6"><div class="info-tile"><div class="info-ic"><i class="bi bi-capsule"></i></div><div><div class="text-muted-sm">Product</div><div class="fw-semibold" x-text="vCarton?.products_summary"></div></div></div></div>
              <div class="col-6"><div class="info-tile"><div class="info-ic"><i class="bi bi-upc"></i></div><div><div class="text-muted-sm">Batch</div><div class="fw-semibold font-monospace" x-text="vCarton?.batches_summary"></div></div></div></div>
              <div class="col-6"><div class="info-tile"><div class="info-ic"><i class="bi bi-box2"></i></div><div><div class="text-muted-sm">Capacity / Packed</div><div class="fw-semibold"><span x-text="vCarton?.capacity"></span> / <span x-text="vCarton?.packed_quantity"></span></div></div></div></div>
              <div class="col-6"><div class="info-tile"><div class="info-ic"><i class="bi bi-upc-scan"></i></div><div><div class="text-muted-sm">Serial Range</div><div class="fw-semibold font-monospace" x-text="vCarton?.serial_range"></div></div></div></div>
            </div></div>

            {{-- Contents --}}
            <div class="col-12">
              <div class="section-label">Packed Contents (<span x-text="vCarton?.contents?.length||0"></span> segment(s))</div>
              <div x-show="!vCarton?.contents?.length" class="text-muted-sm">Not packed yet.</div>
              <div class="table-responsive" x-show="vCarton?.contents?.length">
                <table class="table table-sm align-middle mb-0">
                  <thead><tr><th>Product</th><th>Batch</th><th>Serial Range</th><th class="text-end">Qty</th></tr></thead>
                  <tbody>
                    <template x-for="ct in vCarton?.contents" :key="ct.id">
                      <tr><td style="font-size:13px" x-text="ct.product_name"></td><td class="font-monospace" style="font-size:12px" x-text="ct.brn"></td>
                        <td class="font-monospace" style="font-size:12px" x-text="ct.serial_start+'–'+ct.serial_end"></td><td class="text-end" x-text="ct.quantity"></td></tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </div>

            {{-- Movement --}}
            <div class="col-12">
              <div class="section-label">Movement</div>
              <div class="alert alert-warning py-2 mb-2" style="font-size:13px" x-show="!vPacked">
                <i class="bi bi-exclamation-triangle me-1"></i>This carton is not packed yet. Pack serials into it before it can be dispatched or received.
              </div>
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" class="form-control form-control-sm" style="max-width:200px" placeholder="Location (optional)" x-model="vLocation" :disabled="!vPacked">
                <button class="btn btn-warning btn-sm" @click="move('dispatched')" :disabled="vMoving || !canDispatch"
                        :title="!vPacked ? 'Pack the carton first' : (vDispatched ? 'Already dispatched' : 'Mark as dispatched from factory')"><i class="bi bi-box-arrow-up me-1"></i>Factory Dispatch</button>
                <button class="btn btn-info text-white btn-sm" @click="move('received')" :disabled="vMoving || !canReceive"
                        :title="!vPacked ? 'Pack the carton first' : (!vDispatched ? 'Dispatch first' : (vReceived ? 'Already received' : 'Mark as received at depot'))"><i class="bi bi-box-arrow-in-down me-1"></i>Depot Received</button>
                <span class="text-success small" x-show="vReceived"><i class="bi bi-check-circle me-1"></i>Completed Factory → Depot</span>
              </div>
            </div>

            {{-- Scan history --}}
            <div class="col-12">
              <div class="section-label">Scan &amp; Movement History</div>
              <div x-show="!vCarton?.scans?.length" class="text-muted-sm">No scans yet.</div>
              <ul class="list-unstyled mb-0">
                <template x-for="(s,i) in vCarton?.scans" :key="i">
                  <li class="d-flex align-items-start gap-2 py-1 border-bottom">
                    <i class="bi mt-1" :class="s.event_icon"></i>
                    <div class="flex-grow-1">
                      <span class="fw-semibold" x-text="s.event_label"></span>
                      <span class="text-muted-sm" x-show="s.location"> · <span x-text="s.location"></span></span>
                      <span class="text-muted-sm" x-show="s.note"> · <span x-text="s.note"></span></span>
                      <div class="text-muted-sm"><span x-text="fmtDateTime(s.at)"></span> · by <span x-text="s.performed_by || '—'"></span></div>
                    </div>
                  </li>
                </template>
              </ul>
            </div>
          </div>
        </template>
      </div>
      <div class="modal-footer">
        <a :href="vCarton ? `{{ url('carton') }}/${vCarton.qr_code}` : '#'" target="_blank" class="btn btn-outline-secondary btn-sm me-auto"><i class="bi bi-qr-code-scan me-1"></i>Open scan page</a>
        <button class="btn btn-outline-secondary btn-sm" @click="showView=false">Close</button>
      </div>
    </div></div>
  </div>
  <div class="modal-backdrop fade show" x-show="showView" @click="showView=false"></div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
<script>
function cartonPage() {
  return {
    // create
    showCreate:false, cType:'standard', cProductId:'', cBatchId:'', cBatches:[], cInfo:null,
    cCapacity:null, cCount:null, cManual:false, cLabel:'', cNotes:'', cLoadingB:false, cSaving:false, cErrors:{},
    // pack
    showPack:false, pkMode:'standard', pkCartons:[], pkCartonId:'', pkContents:[], pkLoading:false, pkBusy:false, pkChanged:false, pErrors:{},
    pmProductId:'', pmBatchId:'', pmBatches:[], pmLoadingB:false,
    segProductId:'', segBatchId:'', segBatches:[], segStart:null, segEnd:null, segMaxSerial:0, segLoadingB:false,
    // view
    showView:false, vCarton:null, vLoading:false, vLocation:'', vMoving:false,
    // summary expand
    expandedBatch:null,
    toggleBatch(id){ this.expandedBatch = this.expandedBatch===id ? null : id; },
    // multi-select (bulk label download)
    selected:[],
    pageIds: @json($cartons->pluck('id')->map(fn($i)=>(string)$i)->values()),
    get allChecked(){ return this.pageIds.length>0 && this.pageIds.every(id=>this.selected.includes(id)); },
    toggleAll(e){ this.selected = e.target.checked ? [...this.pageIds] : []; },
    clearSel(){ this.selected=[]; },
    downloadSelected(action){
      if(!this.selected.length) return;
      const base = action==='pdf' ? '{{ route('master-cartons.labels-pdf') }}' : '{{ route('master-cartons.labels') }}';
      const url = base + '?ids=' + this.selected.join(',');
      if(action==='print') window.open(url,'_blank'); else window.location.href=url;
    },
    // labels
    showLabels:false, lblScope:'all', lblProductId:'', lblBatchId:'', lblBatches:[], lblLoadingB:false,
    get lblReady(){ if(this.lblScope==='product') return !!this.lblProductId; if(this.lblScope==='batch') return !!this.lblBatchId; return true; },
    openLabels(){ this.showLabels=true; this.lblScope='all'; this.lblProductId=''; this.lblBatchId=''; this.lblBatches=[]; },
    async lblOnProduct(){ this.lblBatchId=''; this.lblBatches=[]; if(!this.lblProductId) return; this.lblLoadingB=true; this.lblBatches=await this._batches(this.lblProductId); this.lblLoadingB=false; },
    doLabels(action){
      const base = action==='pdf' ? '{{ route('master-cartons.labels-pdf') }}' : '{{ route('master-cartons.labels') }}';
      const p = new URLSearchParams();
      if(this.lblScope==='generic') p.set('type','generic');
      if(this.lblScope==='product') p.set('product_id', this.lblProductId);
      if(this.lblScope==='batch'){ p.set('product_id', this.lblProductId); p.set('batch_id', this.lblBatchId); }
      const url = base + (p.toString() ? ('?'+p.toString()) : '');
      if(action==='print') window.open(url,'_blank'); else window.location.href=url;
      this.showLabels=false;
    },

    fmtDate(d){ if(!d) return '—'; const x=new Date(d); return isNaN(x)?'—':x.toLocaleDateString(undefined,{year:'numeric',month:'short',day:'numeric'}); },
    fmtDateTime(d){ if(!d) return '—'; const x=new Date(d); return isNaN(x)?'—':x.toLocaleString(undefined,{year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}); },
    _batches(pid){ return fetch(`{{ url('partial-batches/products') }}/${pid}/batches`,{headers:{'Accept':'application/json'}}).then(r=>r.json()); },

    // computed — create
    get cAutoCount(){ const t=this.cInfo?this.cInfo.total_quantity:0; const c=this.cCapacity||0; return c>0?Math.ceil(t/c):0; },
    get cEffectiveCount(){ if(this.cType==='generic') return this.cCount||0; return this.cManual ? (this.cCount||0) : this.cAutoCount; },
    get cCanSubmit(){ if(!(this.cCapacity>0) || !(this.cEffectiveCount>0)) return false; return this.cType==='generic' ? true : !!this.cBatchId; },
    // computed — pack
    get pkCarton(){ return this.pkCartons.find(c=>String(c.id)===String(this.pkCartonId)) || null; },
    get pkCartonOptions(){
      if(this.pkMode==='generic') return this.pkCartons.filter(c=>c.carton_type==='generic');
      if(!this.pmBatchId) return [];
      return this.pkCartons.filter(c=>String(c.batch_id)===String(this.pmBatchId));
    },
    get segValid(){ return this.pkCartonId && this.segProductId && this.segBatchId && this.segStart>0 && this.segEnd>=this.segStart
        && (!this.segMaxSerial || this.segEnd<=this.segMaxSerial)
        && (!this.pkCarton || (this.segEnd-this.segStart+1) <= this.pkCarton.remaining); },
    // computed — view movement
    get vPacked(){ return (this.vCarton?.packed_quantity||0) > 0; },
    get vDispatched(){ return this.vCarton?.status==='dispatched' || this.vCarton?.status==='received' || !!this.vCarton?.dispatched_at; },
    get vReceived(){ return this.vCarton?.status==='received'; },
    get canDispatch(){ return this.vPacked && !this.vDispatched && !this.vReceived; },
    get canReceive(){ return this.vPacked && this.vDispatched && !this.vReceived; },

    // ── CREATE ──
    openCreate(){ this.showCreate=true; this.cType='standard'; this.cProductId=''; this.cBatchId=''; this.cBatches=[]; this.cInfo=null; this.cCapacity=null; this.cCount=null; this.cManual=false; this.cLabel=''; this.cNotes=''; this.cErrors={}; },
    async cOnProduct(){ this.cBatchId=''; this.cInfo=null; this.cBatches=[]; if(!this.cProductId) return; this.cLoadingB=true; this.cBatches=await this._batches(this.cProductId); this.cLoadingB=false; },
    async cOnBatch(){ this.cInfo=null; if(!this.cBatchId) return; const r=await fetch(`{{ url('partial-batches/batches') }}/${this.cBatchId}/info`,{headers:{'Accept':'application/json'}}); this.cInfo=await r.json(); },
    async submitCreate(){
      this.cSaving=true; this.cErrors={};
      const fd=new FormData(); fd.append('_token','{{ csrf_token() }}'); fd.append('carton_type',this.cType);
      fd.append('capacity',this.cCapacity??''); fd.append('carton_count',this.cEffectiveCount??''); fd.append('label',this.cLabel??''); fd.append('notes',this.cNotes??'');
      if(this.cType==='standard'){ fd.append('product_id',this.cProductId); fd.append('batch_id',this.cBatchId); }
      try { const res=await fetch('{{ route('master-cartons.store') }}',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json(); if(d.success){ window.location.href=d.redirect; } else { this.cErrors=d.errors??{}; this.cSaving=false; }
      } catch(e){ alert('Server error.'); this.cSaving=false; }
    },

    // ── PACK ──
    async openPack(cartonId=null){
      this.showPack=true; this.pkChanged=false; this.pErrors={}; this.pkContents=[]; this.pkCartonId='';
      this.pkMode='standard'; this.pmProductId=''; this.pmBatchId=''; this.pmBatches=[];
      this.segProductId=''; this.segBatchId=''; this.segBatches=[]; this.segStart=null; this.segEnd=null; this.segMaxSerial=0;
      this.pkLoading=true;
      const r=await fetch('{{ route('master-cartons.packing-cartons') }}',{headers:{'Accept':'application/json'}}); this.pkCartons=await r.json(); this.pkLoading=false;
      if(cartonId){
        const c=this.pkCartons.find(x=>String(x.id)===String(cartonId));
        if(c && c.carton_type==='generic'){ this.pkMode='generic'; }
        else if(c && c.batch_id){ this.pkMode='standard'; this.pmProductId=String(c.product_id); await this.pmOnProduct(); this.pmBatchId=String(c.batch_id); await this.pmOnBatch(); }
        this.pkCartonId=String(cartonId); await this.pkOnCarton();
      }
    },
    setPkMode(m){ this.pkMode=m; this.pkCartonId=''; this.pkContents=[]; this.pmProductId=''; this.pmBatchId=''; this.pmBatches=[];
      this.segProductId=''; this.segBatchId=''; this.segBatches=[]; this.segStart=null; this.segEnd=null; this.segMaxSerial=0; this.pErrors={}; },
    cartonPlaceholder(){
      if(this.pkMode==='standard' && !this.pmBatchId) return 'Select product & batch first…';
      return this.pkCartonOptions.length ? 'Select carton…' : 'No cartons with free capacity';
    },
    async pmOnProduct(){ this.pmBatchId=''; this.pmBatches=[]; this.pkCartonId=''; if(!this.pmProductId) return; this.pmLoadingB=true; this.pmBatches=await this._batches(this.pmProductId); this.pmLoadingB=false; },
    async pmOnBatch(){ this.pkCartonId=''; this.pkContents=[]; if(!this.pmBatchId) return;
      // In product/batch mode the segment product & batch are fixed to the selection.
      this.segProductId=String(this.pmProductId); this.segBatches=this.pmBatches; this.segBatchId=String(this.pmBatchId);
      await this.segOnBatch(); },
    async pkOnCarton(){ this.pkContents=[]; this.pErrors={}; if(!this.pkCartonId) return;
      const r=await fetch(`{{ url('master-cartons') }}/${this.pkCartonId}/contents`,{headers:{'Accept':'application/json'}}); const d=await r.json();
      this._applyPack(d); this.prefillEnd(); },
    _applyPack(d){
      this.pkContents=d.contents||[];
      if(d.carton){ const i=this.pkCartons.findIndex(c=>String(c.id)===String(d.carton.id));
        if(i>=0){ this.pkCartons[i].packed_quantity=d.carton.packed_quantity; this.pkCartons[i].remaining=d.carton.remaining; } }
    },
    async segOnProduct(){ this.segBatchId=''; this.segBatches=[]; this.segStart=null; this.segEnd=null; this.segMaxSerial=0; if(!this.segProductId) return; this.segLoadingB=true; this.segBatches=await this._batches(this.segProductId); this.segLoadingB=false; },
    async segOnBatch(){ this.segStart=null; this.segEnd=null; this.segMaxSerial=0; if(!this.segBatchId) return;
      const r=await fetch(`{{ url('master-cartons/batches') }}/${this.segBatchId}/pack-info`,{headers:{'Accept':'application/json'}}); const d=await r.json();
      this.segStart=d.next_serial; this.segMaxSerial=d.max_serial; this.prefillEnd(); },
    prefillEnd(){
      if(!this.segStart || !this.pkCarton) return;
      let end=this.segStart + this.pkCarton.remaining - 1;
      if(this.segMaxSerial && end>this.segMaxSerial) end=this.segMaxSerial;
      this.segEnd=end;
    },
    async addSeg(){
      this.pkBusy=true; this.pErrors={};
      const fd=new FormData(); fd.append('_token','{{ csrf_token() }}');
      fd.append('carton_id',this.pkCartonId); fd.append('product_id',this.segProductId); fd.append('batch_id',this.segBatchId);
      fd.append('serial_start',this.segStart??''); fd.append('serial_end',this.segEnd??'');
      try { const res=await fetch('{{ route('master-cartons.contents.add') }}',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json(); if(d.success){ this._applyPack(d); this.pkChanged=true; await this.segOnBatch(); } else { this.pErrors=d.errors??{}; }
      } catch(e){ alert('Server error.'); }
      this.pkBusy=false;
    },
    async removeSeg(id){
      this.pkBusy=true; this.pErrors={};
      try { const res=await fetch(`{{ url('master-cartons/contents') }}/${id}`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-HTTP-Method-Override':'DELETE'},body:new URLSearchParams({_token:'{{ csrf_token() }}',_method:'DELETE'})});
        const d=await res.json(); if(d.success){ this._applyPack(d); this.pkChanged=true; } else { this.pErrors=d.errors??{}; }
      } catch(e){ alert('Server error.'); }
      this.pkBusy=false;
    },
    closePack(){ this.showPack=false; if(this.pkChanged){ window.location.reload(); } },

    // ── VIEW ──
    openView(id){
      this.showView=true; this.vCarton=null; this.vLoading=true; this.vLocation='';
      fetch(`{{ url('master-cartons') }}/${id}`,{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{
        this.vCarton=d; this.vLoading=false;
        this.$nextTick(()=>{ const el=document.getElementById('viewCartonQr'); if(el){ el.innerHTML=''; new QRCode(el,{text:d.scan_url,width:150,height:150,correctLevel:QRCode.CorrectLevel.M}); } });
      }).catch(()=>{ alert('Could not load carton.'); this.showView=false; this.vLoading=false; });
    },
    async move(event){
      if(!this.vCarton) return; this.vMoving=true;
      const fd=new FormData(); fd.append('_token','{{ csrf_token() }}'); fd.append('event',event); fd.append('location',this.vLocation??'');
      try { const res=await fetch(`{{ url('master-cartons') }}/${this.vCarton.id}/move`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json(); if(d.success){ window.location.href=d.redirect; } else { this.vMoving=false; alert((d.errors?.event?.[0])||'Could not update.'); }
      } catch(e){ this.vMoving=false; alert('Server error.'); }
    },

    confirmDelete(ev, num){ if(confirm(`Remove carton ${num}? Any packed units will be released.`)) ev.target.submit(); },
  };
}
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.carton-qr').forEach(function(el){ new QRCode(el, { text: el.dataset.qr, width: 92, height: 92, correctLevel: QRCode.CorrectLevel.M }); });
});
</script>
@endpush
