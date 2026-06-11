@extends('layouts.app')
@section('title', 'Batch Units — ' . $batch->brn)

@section('content')
@php
  $product  = \Illuminate\Support\Str::slug($batch->product?->name ?? 'product', '_');
  $batchTag = \Illuminate\Support\Str::slug($batch->batch_number, '_');

  $curSort = request('sort', 'serial_number');
  $curDir  = request('dir', 'asc') === 'desc' ? 'desc' : 'asc';
  $sortUrl = function ($col) use ($batch, $curSort, $curDir) {
      $dir = ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
      return route('batches.units', array_merge(
          ['batch' => $batch, 'sort' => $col, 'dir' => $dir],
          request()->only('q', 'status', 'per_page')
      ));
  };
  $caret = function ($col) use ($curSort, $curDir) {
      return $curSort === $col
          ? '<i class="bi bi-caret-'.($curDir === 'asc' ? 'up' : 'down').'-fill ms-1"></i>'
          : '<i class="bi bi-arrow-down-up ms-1 text-muted" style="opacity:.4"></i>';
  };
@endphp
<div>

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1>Batch Units</h1>
      <div class="page-breadcrumb">
        <a href="{{ route('dashboard') }}">Home</a> /
        <a href="{{ route('batches') }}">Batch &amp; Lot Mgmt</a> /
        <span class="font-monospace">{{ $batch->brn }}</span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('batches.labels', $batch) }}" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-qr-code me-1"></i>Print QR Labels</a>
      <a href="{{ route('batches.logs', $batch) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-clock-history me-1"></i>Trace Logs</a>
      <a href="{{ route('batches') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Batches</a>
    </div>
  </div>

  {{-- Summary --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <span class="badge text-bg-primary font-monospace" style="font-size:13px">{{ $batch->brn }}</span>
        <span class="badge-status {{ $batch->qc_badge_class }}">QC · {{ $batch->qc_status_label }}</span>
        <span class="badge-status {{ $batch->status_badge_class }}">{{ $batch->status_label }}</span>
      </div>
      <h4 class="fw-bold mb-0">{{ $batch->product?->name ?? '—' }}</h4>
      <div class="text-muted font-monospace mb-3" style="font-size:12px">{{ $batch->product?->prn }}</div>

      @php $d = $batch->days_to_expiry; @endphp
      <div class="row g-2">
        <div class="col-6 col-md"><div class="metric-tile"><div class="metric-ic"><i class="bi bi-upc"></i></div>
          <div class="metric-txt"><div class="text-muted-sm">Batch / Lot</div><div class="fw-semibold text-truncate">{{ $batch->batch_number }} / {{ $batch->lot_number ?? '—' }}</div></div></div></div>
        <div class="col-6 col-md"><div class="metric-tile"><div class="metric-ic"><i class="bi bi-box-seam"></i></div>
          <div class="metric-txt"><div class="text-muted-sm">Produced</div><div class="fw-semibold">{{ number_format($batch->quantity_produced) }}</div></div></div></div>
        <div class="col-6 col-md"><div class="metric-tile"><div class="metric-ic"><i class="bi bi-upc-scan"></i></div>
          <div class="metric-txt"><div class="text-muted-sm">Total Units</div><div class="fw-semibold">{{ number_format($statusCounts->sum()) }}</div></div></div></div>
        <div class="col-6 col-md"><div class="metric-tile"><div class="metric-ic"><i class="bi bi-calendar-check"></i></div>
          <div class="metric-txt"><div class="text-muted-sm">Manufactured</div><div class="fw-semibold">{{ optional($batch->manufacture_date)->format('M d, Y') ?? '—' }}</div></div></div></div>
        <div class="col-6 col-md"><div class="metric-tile"><div class="metric-ic"><i class="bi bi-calendar-x"></i></div>
          <div class="metric-txt"><div class="text-muted-sm">Expiry</div><div class="fw-semibold">{{ optional($batch->expiry_date)->format('M d, Y') ?? '—' }}</div></div></div></div>
        <div class="col-6 col-md"><div class="metric-tile"><div class="metric-ic"><i class="bi bi-hourglass-split"></i></div>
          <div class="metric-txt"><div class="text-muted-sm">Expires In</div><div class="fw-semibold {{ $d!==null && $d<30 ? 'text-danger' : ($d!==null && $d<90 ? 'text-warning' : '') }}">{{ $d!==null ? ($d<0 ? 'Expired' : $d.' days') : '—' }}</div></div></div></div>
      </div>
    </div>
  </div>

  {{-- Status breakdown --}}
  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <a href="{{ route('batches.units', array_merge(['batch'=>$batch], request()->only('per_page','q','sort','dir'))) }}" class="status-chip {{ !request('status') ? 'active' : '' }}">All <span class="count">{{ number_format($statusCounts->sum()) }}</span></a>
    @foreach(['generated','printing','packed','scanned','blocked','active','inactive','verified','expired'] as $st)
      @if(($statusCounts[$st] ?? 0) > 0)
      <a href="{{ route('batches.units', array_merge(['batch'=>$batch, 'status'=>$st], request()->only('per_page','q','sort','dir'))) }}"
         class="status-chip {{ request('status')===$st ? 'active' : '' }}">{{ ucfirst($st) }} <span class="count">{{ number_format($statusCounts[$st]) }}</span></a>
      @endif
    @endforeach
  </div>

  {{-- Toolbar: search/filter (left) + export (right) — sticky on scroll --}}
  <div class="card mb-3 sticky-toolbar">
    <div class="card-body py-3">
      <div class="d-flex flex-wrap gap-4 justify-content-between align-items-end">

        {{-- LEFT: Search · Status · Per page --}}
        <form method="GET" action="{{ route('batches.units', $batch) }}" class="d-flex flex-wrap gap-2 align-items-end flex-grow-1" style="min-width:300px">
          <input type="hidden" name="sort" value="{{ $curSort }}">
          <input type="hidden" name="dir" value="{{ $curDir }}">
          <div style="width:300px">
            <label class="form-label mb-1 text-muted-sm">Search</label>
            <div class="search-wrapper"><i class="bi bi-search search-icon"></i>
              <input type="text" name="q" class="form-control form-control-sm w-100" placeholder="Search units…" value="{{ request('q') }}">
            </div>
          </div>
          <div>
            <label class="form-label mb-1 text-muted-sm">Status</label>
            <select name="status" class="form-select form-select-sm" style="min-width:140px">
              <option value="">All Statuses</option>
              @foreach(['generated','printing','packed','scanned','blocked','active','inactive','verified','expired'] as $st)
                <option value="{{ $st }}" {{ request('status')===$st ? 'selected':'' }}>{{ ucfirst($st) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="form-label mb-1 text-muted-sm">Per page</label>
            <select name="per_page" class="form-select form-select-sm" style="min-width:90px" onchange="this.form.submit()">
              @foreach([10,50,100,500,2000] as $pp)
                <option value="{{ $pp }}" {{ (int)request('per_page',10)===$pp ? 'selected':'' }}>{{ number_format($pp) }}</option>
              @endforeach
            </select>
          </div>
          <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ route('batches.units', $batch) }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-x-lg"></i></a>
          </div>
        </form>

        {{-- RIGHT: Export panel --}}
        <div class="export-panel">
          <div class="export-title"><i class="bi bi-box-arrow-down me-1"></i>Export codes</div>
          <form method="GET" action="{{ route('batches.export', $batch) }}" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
              <label class="form-label mb-1 text-muted-sm">Field</label>
              <select name="field" class="form-select form-select-sm" style="min-width:170px">
                <option value="secret_code">Secret Code</option>
                <option value="unique_number">Unique Number</option>
              </select>
            </div>
            <div>
              <label class="form-label mb-1 text-muted-sm">Format</label>
              <select name="format" class="form-select form-select-sm" style="min-width:150px">
                <option value="txt">Text (.txt)</option>
                <option value="excel">Excel (.csv)</option>
                <option value="pdf">PDF (.pdf)</option>
              </select>
            </div>
            <button class="btn btn-success btn-sm px-3" title="Download as product_batch_field.ext"><i class="bi bi-download me-1"></i>Export</button>
          </form>
        </div>

      </div>
    </div>
  </div>

  {{-- Units table --}}
  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th style="width:80px"><a href="{{ $sortUrl('serial_number') }}" class="th-sort {{ $curSort==='serial_number'?'on':'' }}">Serial {!! $caret('serial_number') !!}</a></th>
              <th style="width:78px">QR</th>
              <th><a href="{{ $sortUrl('unique_number') }}" class="th-sort {{ $curSort==='unique_number'?'on':'' }}">Unique Number (label) {!! $caret('unique_number') !!}</a></th>
              <th><a href="{{ $sortUrl('secret_code') }}" class="th-sort {{ $curSort==='secret_code'?'on':'' }}">Secret Code (QR / verify) {!! $caret('secret_code') !!}</a></th>
              <th><a href="{{ $sortUrl('status') }}" class="th-sort {{ $curSort==='status'?'on':'' }}">Status {!! $caret('status') !!}</a></th>
              <th><a href="{{ $sortUrl('created_at') }}" class="th-sort {{ $curSort==='created_at'?'on':'' }}">Created {!! $caret('created_at') !!}</a></th>
              <th class="text-end" style="width:100px">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($units as $unit)
            <tr>
              <td class="fw-semibold">{{ $unit->serial_number }}</td>
              <td class="text-center">
                <div class="qr-sm mx-auto" data-qr="{{ url('verify/'.$unit->secret_code) }}"></div>
                <button type="button" class="btn btn-link btn-sm p-0 qr-dl d-block mx-auto mt-1" title="Download this QR code"
                        data-name="{{ $product }}_{{ $batchTag }}_{{ $unit->unique_number }}"
                        style="font-size:11px;text-decoration:none">
                  <i class="bi bi-download"></i> PNG
                </button>
              </td>
              <td class="font-monospace" style="font-size:13px">{{ $unit->unique_number }}</td>
              <td class="font-monospace" style="font-size:13px">{{ $unit->secret_code }}</td>
              <td><span class="badge-status {{ $unit->status_badge_class }}">{{ ucfirst($unit->status) }}</span></td>
              <td style="font-size:12px" class="text-muted">{{ $unit->created_at?->format('M d, Y H:i') }}</td>
              <td class="text-end">
                <div class="d-flex gap-1 justify-content-end">
                  <a href="{{ route('verify', $unit->secret_code) }}" target="_blank" rel="noopener noreferrer"
                     class="btn btn-outline-primary btn-sm btn-icon" title="Verify / view result"><i class="bi bi-patch-check"></i></a>
                  <button type="button" class="btn btn-outline-secondary btn-sm btn-icon copy-code"
                          data-code="{{ $unit->secret_code }}" title="Copy secret code"><i class="bi bi-clipboard"></i></button>
                </div>
              </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-5 text-muted">
              <i class="bi bi-upc-scan" style="font-size:32px;opacity:.2"></i>
              <div class="mt-2">No units found.</div>
            </td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($units->hasPages())
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top flex-wrap gap-2">
        <div class="text-muted-sm">Showing <strong>{{ $units->firstItem() }}–{{ $units->lastItem() }}</strong> of <strong>{{ number_format($units->total()) }}</strong> units</div>
        {{ $units->links('pagination::bootstrap-5') }}
      </div>
      @endif
    </div>
  </div>

</div>
@endsection

@push('styles')
<style>
  .qr-sm { width:56px; height:56px; }
  .qr-sm canvas, .qr-sm img { width:56px !important; height:56px !important; border-radius:4px; }

  .metric-tile { display:flex; align-items:center; gap:9px; padding:9px 11px;
    border:1px solid var(--border-color,#e9ecef); border-radius:11px; background:transparent; height:100%; min-width:0; }
  .metric-ic { width:34px; height:34px; border-radius:9px; flex-shrink:0; font-size:16px;
    display:flex; align-items:center; justify-content:center; background:rgba(13,110,253,.08); color:#0d6efd; }
  .metric-txt { min-width:0; }

  .status-chip { font-size:12px; padding:4px 11px; border-radius:20px; text-decoration:none;
    border:1px solid var(--border-color,#dee2e6); color:#495057; background:transparent;
    display:inline-flex; align-items:center; gap:6px; transition:all .12s; }
  .status-chip:hover { border-color:#0d6efd; color:#0d6efd; }
  .status-chip.active { background:#0d6efd; border-color:#0d6efd; color:#fff; }
  .status-chip .count { background:rgba(0,0,0,.06); border-radius:10px; padding:0 7px; font-weight:600; font-size:11px; }
  .status-chip.active .count { background:rgba(255,255,255,.22); }

  .export-panel { border:1px solid var(--border-color,#e9ecef); border-radius:12px;
    padding:10px 14px 12px; background:linear-gradient(180deg, rgba(25,135,84,.05), rgba(25,135,84,.02)); }
  .export-title { font-size:12px; font-weight:700; letter-spacing:.02em; color:#198754; margin-bottom:8px; }
  @media (max-width: 991px){ .export-panel { width:100%; } }

  /* Sticky filter/export toolbar (sits just under the fixed topbar) */
  .sticky-toolbar { position:sticky; top:calc(var(--topbar-height, 60px) + 8px); z-index:15;
    box-shadow:0 4px 14px rgba(0,0,0,.06); }

  /* Sortable column headers */
  th .th-sort { color:inherit; text-decoration:none; white-space:nowrap; display:inline-flex; align-items:center; }
  th .th-sort:hover { color:#0d6efd; }
  th .th-sort.on { color:#0d6efd; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
<script>
  // Render at 128px (CSS scales display to 56px) so the downloaded PNG is crisp
  document.querySelectorAll('.qr-sm').forEach(function (el) {
    new QRCode(el, { text: el.dataset.qr, width: 128, height: 128, correctLevel: QRCode.CorrectLevel.M });
  });

  // Download a single unit's QR code as PNG
  document.querySelectorAll('.qr-dl').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var cell   = btn.closest('td');
      var canvas = cell.querySelector('.qr-sm canvas');
      var img    = cell.querySelector('.qr-sm img');
      var url    = canvas ? canvas.toDataURL('image/png') : (img ? img.src : null);
      if (!url) return;
      var a = document.createElement('a');
      a.href = url;
      a.download = (btn.dataset.name || 'qr') + '.png';
      document.body.appendChild(a); a.click(); a.remove();
    });
  });

  // Copy a unit's secret code to the clipboard
  document.querySelectorAll('.copy-code').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!navigator.clipboard) return;
      navigator.clipboard.writeText(btn.dataset.code).then(function () {
        var i = btn.querySelector('i'), prev = i.className;
        i.className = 'bi bi-check2';
        btn.classList.add('btn-success'); btn.classList.remove('btn-outline-secondary');
        setTimeout(function () {
          i.className = prev;
          btn.classList.remove('btn-success'); btn.classList.add('btn-outline-secondary');
        }, 1200);
      });
    });
  });
</script>
@endpush
