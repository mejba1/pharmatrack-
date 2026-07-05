@extends('layouts.app')
@section('title', 'Shipment Management')

@push('styles')
<style>
  [x-cloak]{display:none!important}
  .section-label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6c757d;padding-bottom:4px;border-bottom:1px solid #e9ecef;margin-bottom:8px}
  .info-tile{display:flex;align-items:center;gap:10px;padding:9px 11px;border:1px solid #e9ecef;border-radius:11px;height:100%}
  .info-ic{width:34px;height:34px;border-radius:9px;flex-shrink:0;font-size:15px;display:flex;align-items:center;justify-content:center;background:rgba(13,110,253,.08);color:#0d6efd}
  .pick{border:1px solid #e9ecef;border-radius:9px;padding:7px 10px;cursor:pointer;transition:.12s}
  .pick:hover{border-color:#9ec5fe;background:#f8fbff}
  .pick.on{border-color:#0d6efd;background:rgba(13,110,253,.06)}
  .ship-qr canvas,.ship-qr img{border-radius:6px}
  .recon{display:flex;gap:10px;flex-wrap:wrap}
  .recon .box{flex:1;min-width:90px;border:1px solid #e9ecef;border-radius:10px;padding:10px;text-align:center}
  .recon .n{font-size:22px;font-weight:700;line-height:1}
</style>
@endpush

@section('content')
<div x-data="shipmentPage()">

  @if(session('success'))
  <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill text-success"></i><span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  @endif

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1>Shipment Management</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Shipment / Consignment</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('shipments.labels', request()->only('status','destination','search','date_from','date_to')) }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Print QR labels for the current filter (date-wise)"><i class="bi bi-qr-code me-1"></i>QR Labels</a>
      @can('logistics.create')<button class="btn btn-primary btn-sm" @click="openCreate()"><i class="bi bi-plus-lg me-1"></i>Create Shipment</button>@endcan
    </div>
  </div>

  @if(($mode ?? null) === 'receiving')
  <div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="font-size:13px">
    <i class="bi bi-box-arrow-in-down"></i>
    <span><strong>Receiving / Verification</strong> — showing only shipments in transit. Open one and mark each carton <strong>Received</strong>, <strong>Damaged</strong>, or <strong>Missing</strong> as it arrives.</span>
  </div>
  @endif

  {{-- Stats --}}
  <div class="row g-2 mb-3">
    <div class="col-6 col-md"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-truck"></i></div><div><div class="stat-value">{{ number_format($stats['total']) }}</div><div class="stat-label">Total Shipments</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-secondary"><div class="stat-icon"><i class="bi bi-pencil-square"></i></div><div><div class="stat-value">{{ number_format($stats['created']) }}</div><div class="stat-label">Draft / Created</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div><div><div class="stat-value">{{ number_format($stats['in_transit']) }}</div><div class="stat-label">In Transit</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></div><div><div class="stat-value">{{ number_format($stats['received']) }}</div><div class="stat-label">Received</div></div></div></div>
    <div class="col-6 col-md"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-value">{{ number_format($stats['cartons']) }}</div><div class="stat-label">Cartons Shipped</div></div></div></div>
  </div>

  {{-- Filters --}}
  <div class="card mb-3"><div class="card-body py-2">
    <form method="GET" action="{{ route('shipments') }}" class="row g-2 align-items-center">
      <div class="col-md-3"><div class="search-wrapper"><i class="bi bi-search search-icon"></i>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Shipment no / QR / carton / destination…" value="{{ $filters['search'] ?? '' }}"></div></div>
      <div class="col-6 col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          @foreach(['created'=>'Created','dispatched'=>'Dispatched','in_transit'=>'In Transit','received'=>'Received','closed'=>'Closed'] as $v=>$l)
            <option value="{{ $v }}" {{ ($filters['status'] ?? '')===$v?'selected':'' }}>{{ $l }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-2"><input type="text" name="destination" class="form-control form-control-sm" placeholder="Destination" value="{{ $filters['destination'] ?? '' }}"></div>
      <div class="col-6 col-md-2"><div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-calendar3"></i></span><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}"></div></div>
      <div class="col-6 col-md-2"><div class="input-group input-group-sm"><span class="input-group-text">→</span><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}"></div></div>
      <div class="col-6 col-md-1 d-flex gap-1">
        <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel"></i></button>
        <a href="{{ route('shipments') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div></div>

  {{-- Shipments table --}}
  <div class="card table-card"><div class="card-body p-0">

    {{-- Bulk QR download toolbar --}}
    <div class="px-3 py-2 d-flex flex-wrap align-items-center gap-2 border-bottom" style="background:linear-gradient(180deg,rgba(13,110,253,.07),rgba(13,110,253,.02))">
      <span class="fw-semibold" :class="selected.length ? '' : 'text-muted'"><i class="bi bi-check2-square me-1 text-primary"></i><span x-text="selected.length"></span> selected</span>
      <button class="btn btn-outline-primary btn-sm" @click="downloadSelected('print')"><i class="bi bi-printer me-1"></i>Print QR labels</button>
      <button class="btn btn-danger btn-sm" @click="downloadSelected('pdf')"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</button>
      <button class="btn btn-link btn-sm text-muted ms-auto text-decoration-none" @click="clearSel()" x-show="selected.length" x-cloak>Clear selection</button>
    </div>

    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr>
        <th style="width:38px" class="text-center"><input type="checkbox" class="form-check-input" title="Select all on this page" :checked="allChecked" @change="toggleAll($event)"></th>
        <th>Shipment No</th><th>Destination</th><th class="text-end">Cartons</th><th class="text-end">Units</th>
        <th>Status</th><th>Created</th><th class="text-end" style="width:130px">Actions</th>
      </tr></thead>
      <tbody>
        @forelse($consignments as $s)
        <tr :class="selected.includes('{{ $s->id }}') ? 'table-active' : ''">
          <td class="text-center"><input type="checkbox" class="form-check-input" value="{{ $s->id }}" x-model="selected"></td>
          <td>
            <span class="fw-semibold font-monospace" style="font-size:12px">{{ $s->consignment_number }}</span>
            <div class="text-muted-sm font-monospace">{{ $s->qr_code }}</div>
          </td>
          <td style="font-size:13px">
            <i class="bi bi-geo-alt text-muted me-1"></i>{{ $s->destination ?? '—' }}
            <div class="text-muted-sm">from {{ $s->origin }}</div>
          </td>
          <td class="text-end fw-semibold">{{ number_format($s->carton_count) }}</td>
          <td class="text-end">{{ number_format($s->total_units) }}</td>
          <td><span class="badge-status {{ $s->status_badge_class }}">{{ $s->status_label }}</span></td>
          <td style="font-size:12px" class="text-muted">{{ $s->created_at?->format('M d, Y') }}</td>
          <td class="text-end"><div class="d-flex gap-1 justify-content-end">
            <button class="btn btn-outline-primary btn-sm btn-icon" title="View / track" @click="openView({{ $s->id }})"><i class="bi bi-eye"></i></button>
            <a href="{{ route('shipment.scan', $s->qr_code) }}" target="_blank" class="btn btn-outline-secondary btn-sm btn-icon" title="Open scan page"><i class="bi bi-qr-code-scan"></i></a>
            @can('logistics.delete')
            <form method="POST" action="{{ route('shipments.destroy', $s) }}" @submit.prevent="confirmDelete($event, '{{ $s->consignment_number }}')">
              @csrf @method('DELETE')
              <button class="btn btn-outline-danger btn-sm btn-icon" title="Remove"><i class="bi bi-trash"></i></button>
            </form>
            @endcan
          </div></td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center py-5 text-muted">
          <i class="bi bi-truck" style="font-size:32px;opacity:.2"></i>
          <div class="mt-2">@if($mode==='receiving')No shipments are currently in transit.@else No shipments yet. Click <strong>Create Shipment</strong> to group packed cartons into a consignment.@endif</div>
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($consignments->hasPages())
  <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top flex-wrap gap-2">
    <div class="text-muted-sm">Showing <strong>{{ $consignments->count() }}</strong> on this page</div>
    <div class="d-flex gap-1">
      @if($consignments->onFirstPage())
        <span class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-chevron-left"></i></span>
      @else
        <a href="{{ $consignments->previousPageUrl() }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i> Prev</a>
      @endif
      @if($consignments->hasMorePages())
        <a href="{{ $consignments->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">Load more <i class="bi bi-chevron-right"></i></a>
      @else
        <span class="btn btn-outline-secondary btn-sm disabled">End</span>
      @endif
    </div>
  </div>
  @endif
  </div></div>

  {{-- ═══════════ CREATE MODAL ═══════════ --}}
  <div class="modal fade" :class="{show:showCreate}" :style="showCreate?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-truck me-2 text-primary"></i>Create Shipment</h5>
        <button class="btn-close" @click="showCreate=false"></button>
      </div>
      <form @submit.prevent="submitCreate()">
        <div class="modal-body">
          <template x-if="Object.keys(cErrors).length">
            <div class="alert alert-danger mb-3"><ul class="mb-0 ps-3" style="font-size:13px"><template x-for="(m,f) in cErrors" :key="f"><template x-for="x in m" :key="x"><li x-text="x"></li></template></template></ul></div>
          </template>

          <div class="section-label">Destination &amp; Transport</div>
          <div class="row g-3 mb-3">
            <div class="col-md-4"><label class="form-label">Origin</label><input class="form-control" x-model="cOrigin"></div>
            <div class="col-md-4"><label class="form-label">Destination <span class="text-danger">*</span></label><input class="form-control" x-model="cDestination" placeholder="e.g. Head Office / Dhaka Depot"></div>
            <div class="col-md-4"><label class="form-label">Carrier</label><input class="form-control" x-model="cCarrier" placeholder="Transport / courier"></div>
            <div class="col-md-4"><label class="form-label">Vehicle No</label><input class="form-control" x-model="cVehicle"></div>
            <div class="col-md-8"><label class="form-label">Notes</label><input class="form-control" x-model="cNotes" placeholder="Optional"></div>
          </div>

          <div class="section-label d-flex justify-content-between align-items-center">
            <span>Add Packed Cartons <span class="text-muted-sm" x-show="cChosen.length" x-text="'· '+cChosen.length+' selected'"></span></span>
            <a href="#" class="text-muted-sm" x-show="cChosen.length" @click.prevent="cChosen=[]">Clear</a>
          </div>

          {{-- Type-ahead search (never loads the whole table) --}}
          <div class="search-wrapper mb-2"><i class="bi bi-search search-icon"></i>
            <input type="text" class="form-control form-control-sm" x-model="cSearch" @input.debounce.300ms="searchCartons()"
                   placeholder="Search carton number (e.g. MC-0001)…">
          </div>
          <div x-show="cSearching" class="text-muted-sm py-1"><span class="spinner-border spinner-border-sm me-1"></span>Searching…</div>
          <div class="row g-2" x-show="cResults.length" style="max-height:200px;overflow:auto">
            <template x-for="c in cResults" :key="c.id">
              <div class="col-md-6">
                <div class="pick d-flex align-items-center gap-2" :class="{on: cChosen.some(x=>x.id===c.id)}" @click="cPick(c)">
                  <i class="bi" :class="cChosen.some(x=>x.id===c.id) ? 'bi-check-square text-primary' : 'bi-plus-square text-muted'"></i>
                  <div class="flex-grow-1" style="min-width:0">
                    <div class="fw-semibold font-monospace" style="font-size:12px" x-text="c.carton_number"></div>
                    <div class="text-muted-sm text-truncate"><span x-text="c.product"></span> · <span class="font-monospace" x-text="c.batch"></span></div>
                  </div>
                  <span class="badge text-bg-light" x-text="c.packed+' u'"></span>
                </div>
              </div>
            </template>
          </div>
          <div x-show="!cSearching && cSearched && !cResults.length" class="text-muted-sm py-2 text-center">No matching packed, unassigned cartons.</div>

          {{-- Chosen chips --}}
          <div class="d-flex flex-wrap gap-1 mt-2" x-show="cChosen.length">
            <template x-for="c in cChosen" :key="c.id">
              <span class="badge bg-white border text-dark d-inline-flex align-items-center gap-2 py-1 px-2">
                <span class="font-monospace fw-semibold" x-text="c.carton_number"></span>
                <span class="text-muted" x-text="c.packed+'u'"></span>
                <button type="button" class="btn-close" style="font-size:9px" @click="cUnpick(c.id)"></button>
              </span>
            </template>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" @click="showCreate=false">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="cSaving || !cDestination">
            <span x-show="cSaving" class="spinner-border spinner-border-sm me-1"></span>
            <span x-text="cSaving ? 'Creating…' : ('Create Shipment ('+cChosen.length+' carton'+(cChosen.length===1?'':'s')+')')"></span>
          </button>
        </div>
      </form>
    </div></div>
  </div>
  <div class="modal-backdrop fade show" x-show="showCreate" @click="showCreate=false"></div>

  {{-- ═══════════ VIEW / TRACK MODAL ═══════════ --}}
  <div class="modal fade" :class="{show:showView}" :style="showView?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title fw-semibold"><i class="bi bi-truck me-2 text-primary"></i><span x-text="v?.shipment_number ?? 'Loading…'"></span></h5>
          <code class="text-muted-sm" x-text="v ? (v.origin+' → '+(v.destination||'—')) : ''"></code>
        </div>
        <button class="btn-close ms-auto" @click="showView=false"></button>
      </div>
      <div class="modal-body">
        <div x-show="vLoading" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>
        <template x-if="!vLoading && v">
          <div class="row g-3">
            <div class="col-md-4 text-center">
              <div class="d-inline-block p-2 border rounded ship-qr"><div id="shipQr"></div></div>
              <div class="font-monospace text-muted-sm mt-1" x-text="v?.qr_code"></div>
              <span class="badge-status mt-1 d-inline-block" :class="v?.status_badge" x-text="v?.status_label"></span>
            </div>
            <div class="col-md-8"><div class="row g-2">
              <div class="col-6"><div class="info-tile"><div class="info-ic"><i class="bi bi-box-seam"></i></div><div><div class="text-muted-sm">Total Cartons</div><div class="fw-semibold" x-text="v?.carton_count"></div></div></div></div>
              <div class="col-6"><div class="info-tile"><div class="info-ic"><i class="bi bi-123"></i></div><div><div class="text-muted-sm">Total Units</div><div class="fw-semibold" x-text="(v?.total_units||0).toLocaleString()"></div></div></div></div>
              <div class="col-6"><div class="info-tile"><div class="info-ic"><i class="bi bi-capsule"></i></div><div><div class="text-muted-sm">Products</div><div class="fw-semibold" x-text="v?.product_list?.length||0"></div></div></div></div>
              <div class="col-6"><div class="info-tile"><div class="info-ic"><i class="bi bi-upc"></i></div><div><div class="text-muted-sm">Batches</div><div class="fw-semibold" x-text="v?.batch_list?.length||0"></div></div></div></div>
            </div></div>

            {{-- Receiving reconciliation --}}
            <div class="col-12" x-show="v?.dispatched_at">
              <div class="section-label">Receiving Reconciliation</div>
              <div class="recon">
                <div class="box"><div class="n text-primary" x-text="v?.carton_count"></div><div class="text-muted-sm">Expected</div></div>
                <div class="box"><div class="n text-success" x-text="v?.received_ok||0"></div><div class="text-muted-sm">Received OK</div></div>
                <div class="box"><div class="n" :class="(v?.damaged_cartons?.length? 'text-danger':'text-muted')" x-text="v?.damaged_cartons?.length||0"></div><div class="text-muted-sm">Damaged</div></div>
                <div class="box"><div class="n" :class="(v?.missing_cartons?.length? 'text-danger':'text-muted')" x-text="v?.missing_cartons?.length||0"></div><div class="text-muted-sm">Missing</div></div>
                <div class="box"><div class="n" :class="(v?.pending_cartons?.length? 'text-warning':'text-muted')" x-text="v?.pending_cartons?.length||0"></div><div class="text-muted-sm">In Transit</div></div>
              </div>
              <div class="alert alert-light border mt-2 mb-0 py-2" style="font-size:12px">
                <i class="bi bi-info-circle me-1 text-primary"></i>
                <strong>Expected</strong> = total cartons sent. As they arrive, mark each
                <strong class="text-success">Received</strong>, <strong class="text-danger">Damaged</strong>, or
                <strong class="text-danger">Missing</strong> (short / never arrived).
                <strong class="text-warning">In Transit</strong> = still on the way, not yet scanned.
              </div>
              <div class="mt-2" x-show="v?.missing_cartons?.length">
                <span class="text-muted-sm me-1">Missing cartons:</span>
                <template x-for="m in v?.missing_cartons" :key="m"><span class="badge text-bg-danger me-1 font-monospace" x-text="m"></span></template>
              </div>
            </div>

            {{-- Cartons + receiving verification --}}
            <div class="col-12">
              <div class="section-label">Cartons (<span x-text="v?.cartons?.length||0"></span>)
                <span class="text-muted-sm fw-normal" x-show="v?.dispatched_at"> · verify each carton on arrival</span>
              </div>
              <div x-show="!v?.cartons?.length" class="text-muted-sm">No cartons in this shipment.</div>
              <div class="table-responsive" x-show="v?.cartons?.length">
                <table class="table table-sm align-middle mb-0">
                  <thead><tr><th>Carton</th><th>Product</th><th class="text-end">Units</th><th>Status</th><th>Condition</th><th class="text-end">Receive</th></tr></thead>
                  <tbody>
                    <template x-for="c in v?.cartons" :key="c.id">
                      <tr>
                        <td class="font-monospace" style="font-size:12px" x-text="c.carton_number"></td>
                        <td style="font-size:12px"><span x-text="c.product"></span><div class="text-muted-sm font-monospace" x-text="c.batch"></div></td>
                        <td class="text-end" x-text="c.packed"></td>
                        <td><span class="badge-status" :class="c.status_badge" x-text="c.status_label"></span></td>
                        <td>
                          <span class="badge-status" :class="c.condition_badge" x-text="c.condition_label"></span>
                          <a x-show="c.evidence_url" :href="c.evidence_url" target="_blank" class="ms-1" title="View evidence"><i class="bi bi-image"></i></a>
                        </td>
                        <td class="text-end">
                          <div class="btn-group btn-group-sm" role="group" x-show="v?.dispatched_at">
                            <button class="btn btn-outline-success" title="Received (good)" @click="receive(c,'received')" :disabled="rcBusy"><i class="bi bi-check-lg"></i></button>
                            <button class="btn btn-outline-warning" title="Damaged" @click="openDamage(c)" :disabled="rcBusy"><i class="bi bi-exclamation-triangle"></i></button>
                            <button class="btn btn-outline-danger" title="Missing" @click="receive(c,'missing')" :disabled="rcBusy"><i class="bi bi-question-lg"></i></button>
                          </div>
                          <span class="text-muted-sm" x-show="!v?.dispatched_at">—</span>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>

              {{-- Damage capture (remark + evidence photo) --}}
              <div class="border rounded p-2 mt-2" x-show="dmgCarton" x-cloak>
                <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Report damage — <span class="font-monospace" x-text="dmgCarton?.carton_number"></span></div>
                <div class="row g-2 align-items-end">
                  <div class="col-md-6"><label class="form-label">Remarks</label><input type="text" class="form-control form-control-sm" x-model="dmgNote" placeholder="Describe the damage / shortage"></div>
                  <div class="col-md-4"><label class="form-label">Evidence photo</label><input type="file" accept="image/*" class="form-control form-control-sm" @change="dmgFile=$event.target.files[0]"></div>
                  <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-warning btn-sm flex-fill" @click="receive(dmgCarton,'damaged')" :disabled="rcBusy">Save</button>
                    <button class="btn btn-outline-secondary btn-sm" @click="dmgCarton=null">✕</button>
                  </div>
                </div>
              </div>
            </div>

            {{-- Movement --}}
            <div class="col-12">
              <div class="section-label">Movement</div>
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" class="form-control form-control-sm" style="max-width:200px" placeholder="Location (optional)" x-model="vLocation">
                <button class="btn btn-warning btn-sm" @click="move('dispatched')" :disabled="vMoving || !canDispatch"><i class="bi bi-box-arrow-up me-1"></i>Dispatch from Factory</button>
                <button class="btn btn-secondary btn-sm" @click="move('in_transit')" :disabled="vMoving || !canTransit"><i class="bi bi-truck me-1"></i>In Transit</button>
                <button class="btn btn-success text-white btn-sm" @click="move('received')" :disabled="vMoving || !canReceive"><i class="bi bi-box-arrow-in-down me-1"></i>Receive</button>
                <span class="text-success small" x-show="v?.status==='received'"><i class="bi bi-check-circle me-1"></i>Delivered</span>
              </div>
            </div>

            {{-- Scan history --}}
            <div class="col-12">
              <div class="section-label">Scan &amp; Movement History</div>
              <div x-show="!v?.scans?.length" class="text-muted-sm">No events yet.</div>
              <ul class="list-unstyled mb-0">
                <template x-for="(s,i) in v?.scans" :key="i">
                  <li class="d-flex align-items-start gap-2 py-1 border-bottom">
                    <i class="bi mt-1" :class="s.event_icon"></i>
                    <div class="flex-grow-1">
                      <span class="fw-semibold" x-text="s.event_label"></span>
                      <span class="text-muted-sm" x-show="s.location"> · <span x-text="s.location"></span></span>
                      <div class="text-muted-sm"><span x-text="fmtDateTime(s.at)"></span> · by <span x-text="s.performed_by||'—'"></span></div>
                    </div>
                  </li>
                </template>
              </ul>
            </div>
          </div>
        </template>
      </div>
      <div class="modal-footer">
        <a :href="v ? `{{ url('shipment') }}/${v.qr_code}` : '#'" target="_blank" class="btn btn-outline-secondary btn-sm me-auto"><i class="bi bi-qr-code-scan me-1"></i>Open scan page</a>
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
function shipmentPage(){
  return {
    // create
    showCreate:false, cOrigin:'Factory', cDestination:'', cCarrier:'', cVehicle:'', cNotes:'',
    cSearch:'', cResults:[], cChosen:[], cSearching:false, cSearched:false, cSaving:false, cErrors:{},
    // view
    showView:false, v:null, vLoading:false, vLocation:'', vMoving:false,
    // receiving verification
    rcBusy:false, dmgCarton:null, dmgNote:'', dmgFile:null,
    // multi-select (bulk QR labels)
    selected:[],
    pageIds: @json($consignments->pluck('id')->map(fn($i)=>(string)$i)->values()),
    get allChecked(){ return this.pageIds.length>0 && this.pageIds.every(id=>this.selected.includes(id)); },
    toggleAll(e){ this.selected = e.target.checked ? [...this.pageIds] : []; },
    clearSel(){ this.selected=[]; },
    downloadSelected(action){
      if(!this.selected.length){ this.$store.toast.show('Select at least one shipment first.','warning'); return; }
      const base = action==='pdf' ? '{{ route('shipments.labels-pdf') }}' : '{{ route('shipments.labels') }}';
      const url = base + '?ids=' + this.selected.join(',');
      if(action==='print') window.open(url,'_blank'); else window.location.href=url;
    },

    get canDispatch(){ return this.v && this.v.status==='created' && this.v.carton_count>0; },
    get canTransit(){ return this.v && this.v.status==='dispatched'; },
    get canReceive(){ return this.v && ['dispatched','in_transit'].includes(this.v.status); },

    fmtDateTime(d){ if(!d) return '—'; const x=new Date(d); return isNaN(x)?'—':x.toLocaleString(undefined,{year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}); },

    // ── CREATE ──
    openCreate(){
      this.showCreate=true; this.cOrigin='Factory'; this.cDestination=''; this.cCarrier=''; this.cVehicle=''; this.cNotes='';
      this.cSearch=''; this.cResults=[]; this.cChosen=[]; this.cSearched=false; this.cErrors={};
    },
    async searchCartons(){
      this.cSearching=true;
      try{
        const r=await fetch('{{ route('shipments.available-cartons') }}?q='+encodeURIComponent(this.cSearch.trim()),{headers:{'Accept':'application/json'}});
        this.cResults=await r.json(); this.cSearched=true;
      }catch(e){ this.cResults=[]; }
      this.cSearching=false;
    },
    cPick(c){ if(!this.cChosen.some(x=>x.id===c.id)) this.cChosen.push(c); },
    cUnpick(id){ this.cChosen=this.cChosen.filter(x=>x.id!==id); },
    async submitCreate(){
      this.cSaving=true; this.cErrors={};
      const fd=new FormData(); fd.append('_token','{{ csrf_token() }}');
      fd.append('origin',this.cOrigin); fd.append('destination',this.cDestination); fd.append('carrier',this.cCarrier);
      fd.append('vehicle_no',this.cVehicle); fd.append('notes',this.cNotes);
      this.cChosen.forEach(c=>fd.append('carton_ids[]',c.id));
      try{ const res=await fetch('{{ route('shipments.store') }}',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json(); if(d.success){ window.location.href=d.redirect; } else { this.cErrors=d.errors??{}; this.cSaving=false; }
      }catch(e){ alert('Server error.'); this.cSaving=false; }
    },

    // ── VIEW ──
    openView(id){
      this.showView=true; this.v=null; this.vLoading=true; this.vLocation='';
      fetch(`{{ url('shipments') }}/${id}`,{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{
        this.v=d; this.vLoading=false;
        this.$nextTick(()=>{ const el=document.getElementById('shipQr'); if(el){ el.innerHTML=''; new QRCode(el,{text:d.scan_url,width:150,height:150,correctLevel:QRCode.CorrectLevel.M}); } });
      }).catch(()=>{ alert('Could not load shipment.'); this.showView=false; this.vLoading=false; });
    },
    async move(event){
      if(!this.v) return; this.vMoving=true;
      const fd=new FormData(); fd.append('_token','{{ csrf_token() }}'); fd.append('event',event); fd.append('location',this.vLocation??'');
      try{ const res=await fetch(`{{ url('shipments') }}/${this.v.id}/move`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json(); if(d.success){ window.location.href=d.redirect; } else { this.vMoving=false; alert((d.errors?.event?.[0])||'Could not update.'); }
      }catch(e){ this.vMoving=false; alert('Server error.'); }
    },

    // ── RECEIVING VERIFICATION ──
    openDamage(c){ this.dmgCarton=c; this.dmgNote=c.condition_note||''; this.dmgFile=null; },
    async receive(carton, outcome){
      if(!carton || !this.v) return; this.rcBusy=true;
      const fd=new FormData(); fd.append('_token','{{ csrf_token() }}'); fd.append('outcome',outcome);
      fd.append('location', this.vLocation || (this.v.destination||''));
      if(outcome==='damaged'){ fd.append('note', this.dmgNote||''); if(this.dmgFile) fd.append('evidence', this.dmgFile); }
      try{
        const res=await fetch(`{{ url('shipments') }}/${this.v.id}/cartons/${carton.id}/receive`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
        const d=await res.json();
        if(d.success){ this.v=d.shipment; this.dmgCarton=null; this.dmgNote=''; this.dmgFile=null; }
        else { alert(Object.values(d.errors||{}).flat().join('\n')||'Could not update.'); }
      }catch(e){ alert('Server error.'); }
      this.rcBusy=false;
    },

    confirmDelete(ev,num){ if(confirm(`Remove shipment ${num}? Its cartons will be released (not deleted).`)) ev.target.submit(); },
  };
}
</script>
@endpush
