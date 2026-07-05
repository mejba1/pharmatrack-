@extends('layouts.app')
@section('title', 'UUC Management')

@section('content')
<div class="page-header">
  <div>
    <h1>UUC Management</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / UUC Management</div>
  </div>
</div>

{{-- Flash → global toast (no inline banner) --}}
@if(session('success'))
  <div x-data x-init="$nextTick(() => $store.toast.show(@js(session('success')), 'success'))"></div>
@endif

<div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>Each Unique Unit Code (UUC) is a unit's secret verification code. Search to look up a unit's batch, product, status and verification URL. Use <strong>Lock</strong> to instantly block a code from being verified.</div>

<div class="card mb-3"><div class="card-body">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label">Search UUC / Label</label>
      <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}" placeholder="HAK33NQJCP">
    </div>
    <div class="col-md-3">
      <label class="form-label">Product</label>
      <select name="product_id" class="form-select form-select-sm">
        <option value="">All products</option>
        @foreach($products as $p)
          <option value="{{ $p->id }}" @selected((string)$filters['product_id'] === (string)$p->id)>{{ $p->name }} ({{ $p->prn }})</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Batch</label>
      <select name="batch_id" class="form-select form-select-sm">
        <option value="">All batches</option>
        @foreach($batches as $b)
          <option value="{{ $b->id }}" @selected((string)$filters['batch_id'] === (string)$b->id)>{{ $b->brn }} — {{ $b->product?->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">State</label>
      <select name="state" class="form-select form-select-sm">
        <option value="">All states</option>
        <option value="locked"   @selected($filters['state']==='locked')>Locked</option>
        <option value="unlocked" @selected($filters['state']==='unlocked')>Unlocked</option>
        <option value="blocked"  @selected($filters['state']==='blocked')>Blocked / inactive</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Generated from</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Generated to</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
    </div>
    <div class="col-md-2">
      <label class="form-label">Sort by</label>
      <select name="sort" class="form-select form-select-sm">
        <option value="newest" @selected($filters['sort']==='newest')>Newest</option>
        <option value="oldest" @selected($filters['sort']==='oldest')>Oldest</option>
        <option value="scans"  @selected($filters['sort']==='scans')>Most scans</option>
        <option value="hits"   @selected($filters['sort']==='hits')>Most hits</option>
        <option value="recent" @selected($filters['sort']==='recent')>Recently scanned</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Per page</label>
      <select name="per_page" class="form-select form-select-sm">
        @foreach([15,30,50,100] as $pp)<option value="{{ $pp }}" @selected($filters['per_page']===$pp)>{{ $pp }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2 d-flex gap-1">
      <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
      <a href="{{ route('anticounterfeit.uuc') }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div></div>

<div x-data="uucBulk(@js($units->pluck('id')->map(fn($i)=>(string)$i)->all()))">

  {{-- Hidden bulk form (ids + action filled by Alpine) --}}
  <form method="POST" action="{{ route('anticounterfeit.uuc.bulk') }}" x-ref="bulkForm" class="d-none">
    @csrf
    <input type="hidden" name="action" x-model="action">
    <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
  </form>

  {{-- Bulk action bar --}}
  <div x-show="sel.length" x-cloak class="alert alert-secondary d-flex align-items-center py-2 mb-2">
    <span class="me-3"><strong x-text="sel.length"></strong> selected</span>
    <button type="button" @click="submit('lock')" class="btn btn-danger btn-sm me-1"><i class="bi bi-lock-fill me-1"></i>Lock selected</button>
    <button type="button" @click="submit('unlock')" class="btn btn-success btn-sm me-1"><i class="bi bi-unlock-fill me-1"></i>Unlock selected</button>
    <button type="button" @click="clear()" class="btn btn-link btn-sm text-muted ms-auto">Clear</button>
  </div>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead><tr><th style="width:34px"><input type="checkbox" class="form-check-input" @change="toggleAll($event)" :checked="allChecked()"></th><th>UUC (secret)</th><th>Label No.</th><th>Serial</th><th>Product</th><th>Batch</th><th>Status</th><th class="text-center">Scans / Hits</th><th class="text-center">Access</th><th class="text-center">Verify</th><th class="text-end">Action</th></tr></thead>
    <tbody>
      @forelse($units as $u)
        @php $isLocked = $lockedCodes->has($u->secret_code); @endphp
        <tr>
          <td><input type="checkbox" class="form-check-input" value="{{ $u->id }}" x-model="sel"></td>
          <td class="font-monospace small">{{ $u->secret_code }}</td>
          <td class="font-monospace small">{{ $u->unique_number ?? '—' }}</td>
          <td class="small">#{{ $u->serial_number }}</td>
          <td class="small">{{ $u->batch?->product?->name ?? '—' }}</td>
          <td class="font-monospace small">{{ $u->batch?->brn ?? '—' }}</td>
          <td><span class="badge-status {{ $u->status_badge_class }}">{{ ucfirst($u->status) }}</span></td>
          <td class="text-center">
            @php
              $st        = $scanStats[$u->secret_code] ?? null;
              $total     = (int) ($st->total ?? 0);
              $flagged   = (int) ($st->flagged ?? 0);
              $countries = (int) ($st->countries ?? 0);
              $ips       = (int) ($st->ips ?? 0);
              $lastAgo   = ($st && $st->last_scan) ? \Illuminate\Support\Carbon::parse($st->last_scan)->diffForHumans(null, true) . ' ago' : null;
            @endphp
            @if($total > 0)
              <div class="d-inline-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-primary" title="Total verification scans" style="font-size:11px">{{ $total }} {{ \Illuminate\Support\Str::plural('scan', $total) }}</span>
                @if($flagged > 0)
                  <span class="badge rounded-pill bg-danger" title="Blocked / suspicious scans" style="font-size:11px">{{ $flagged }} {{ \Illuminate\Support\Str::plural('hit', $flagged) }}</span>
                @endif
              </div>
              <div class="text-muted mt-1" style="font-size:10.5px" title="Distinct countries / IPs · last scan">
                <i class="bi bi-globe2 me-1"></i>{{ $countries }} ·
                <i class="bi bi-hdd-network me-1"></i>{{ $ips }} {{ \Illuminate\Support\Str::plural('IP', $ips) }}
                {{ $lastAgo ? '· ' . $lastAgo : '' }}
              </div>
            @else
              <span class="text-muted small">—</span>
            @endif
          </td>
          <td class="text-center">
            @if($isLocked)
              <span class="badge-status badge-cancelled" @if($u->lock_reason) title="Reason: {{ $u->lock_reason }}" @endif><i class="bi bi-lock-fill me-1"></i>Locked</span>
            @else
              <span class="badge-status badge-active"><i class="bi bi-unlock me-1"></i>Open</span>
            @endif
            @if(($u->blocked_scan_count ?? 0) > 0)
              <div class="text-danger small mt-1" title="Scans blocked after lock / limit{{ $u->last_blocked_scan_at ? ' · last '.$u->last_blocked_scan_at->diffForHumans() : '' }}">
                <i class="bi bi-activity me-1"></i>{{ $u->blocked_scan_count }} blocked
              </div>
            @endif
          </td>
          <td class="text-center"><a href="{{ route('verify', $u->secret_code) }}" target="_blank" class="btn btn-outline-secondary btn-sm btn-icon" title="Open verification page"><i class="bi bi-box-arrow-up-right"></i></a></td>
          <td class="text-end">
            <div class="d-inline-flex align-items-center gap-1">
              <a href="{{ route('anticounterfeit.uuc.report', $u) }}" target="_blank" class="btn btn-sm btn-outline-primary btn-icon" title="Download PDF report (product details + scan history)">
                <i class="bi bi-file-earmark-pdf"></i>
              </a>
              <form method="POST" action="{{ route('anticounterfeit.quicklock.unit', $u) }}">
                @csrf
                {{-- Colour reflects current state: red = locked, green = unlocked --}}
                <button class="btn btn-sm {{ $isLocked ? 'btn-danger' : 'btn-success' }}">
                  <i class="bi {{ $isLocked ? 'bi-unlock-fill' : 'bi-lock-fill' }} me-1"></i>{{ $isLocked ? 'Unlock' : 'Lock' }}
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="11" class="text-center text-muted py-4">{{ ($filters['search'] || $filters['product_id'] || $filters['batch_id'] || $filters['state'] || $filters['date_from'] || $filters['date_to']) ? 'No units match these filters.' : 'No units found.' }}</td></tr>
      @endforelse
    </tbody>
  </table>
</div></div>
@if($units->hasPages())<div class="card-footer bg-transparent">{{ $units->links() }}</div>@endif
</div>{{-- /card --}}
</div>{{-- /x-data uucBulk --}}
@endsection

@push('scripts')
<script>
  function uucBulk(pageIds){
    return {
      sel: [],
      action: 'lock',
      pageIds: pageIds || [],
      toggleAll(e){ this.sel = e.target.checked ? [...this.pageIds] : []; },
      allChecked(){ return this.pageIds.length > 0 && this.pageIds.every(id => this.sel.includes(id)); },
      submit(action){
        if(!this.sel.length) return;
        if(action === 'lock' && !confirm('Lock '+this.sel.length+' selected UUC code(s)?')) return;
        if(action === 'unlock' && !confirm('Unlock '+this.sel.length+' selected UUC code(s)?')) return;
        this.action = action;
        this.$nextTick(() => this.$refs.bulkForm.submit());
      },
      clear(){ this.sel = []; },
    };
  }
</script>
@endpush
