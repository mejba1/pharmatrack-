@extends('layouts.app')
@section('title', 'Batch-wise Carton Summary')

@section('content')
<div x-data="batchSummaryPage()">

  <div class="page-header">
    <div>
      <h1>Batch-wise Carton Summary</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / <a href="{{ route('master-cartons') }}">Master Carton Mgmt</a> / Batch-wise Summary</div>
    </div>
    <a href="{{ route('master-cartons') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-grid me-1"></i>All Cartons</a>
  </div>

  <div class="card">
    <div class="card-header bg-transparent fw-semibold"><i class="bi bi-clipboard-data me-1"></i>Batch-wise Carton Summary <span class="text-muted-sm fw-normal">(recent batches)</span></div>
    <div class="card-body p-0" @click="onSummaryClick($event)">
      @include('partials.carton-batch-summary', ['summary' => $summary])
    </div>
  </div>

  {{-- Batch cartons modal --}}
  <div class="modal fade" :class="{show:bcShow}" :style="bcShow?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-clipboard-data me-2 text-info"></i>Cartons in <span class="font-monospace" x-text="bcBrn"></span></h5>
        <button class="btn-close" @click="bcShow=false"></button>
      </div>
      <div class="modal-body">
        <div x-show="bcLoading" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
        <div x-show="!bcLoading && !bcCartons.length" class="text-center py-4 text-muted">No cartons hold this batch.</div>
        <div class="table-responsive" x-show="!bcLoading && bcCartons.length">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Carton</th><th class="text-end">Qty (this batch)</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
              <template x-for="c in bcCartons" :key="c.id">
                <tr>
                  <td class="font-monospace" style="font-size:12px">
                    <span x-text="c.carton_number"></span>
                    <span class="badge text-bg-warning ms-1" style="font-size:9px" x-show="c.mixed">mixed</span>
                  </td>
                  <td class="text-end" x-text="c.qty"></td>
                  <td><span class="badge-status" :class="c.status_badge" x-text="c.status"></span></td>
                  <td class="text-end"><a :href="`{{ route('master-cartons') }}?search=${c.carton_number}`" class="btn btn-outline-primary btn-sm btn-icon" title="Open in carton list"><i class="bi bi-box-arrow-up-right"></i></a></td>
                </tr>
              </template>
            </tbody>
          </table>
          <div class="text-muted-sm mt-2" x-show="bcCartons.length>=100">Showing the first 100 cartons.</div>
        </div>
      </div>
      <div class="modal-footer">
        <a :href="bcBatchId ? `{{ route('master-cartons') }}?batch_id=${bcBatchId}` : '#'" class="btn btn-outline-primary btn-sm me-auto"><i class="bi bi-box-seam me-1"></i>Open full list</a>
        <button class="btn btn-outline-secondary btn-sm" @click="bcShow=false">Close</button>
      </div>
    </div></div>
  </div>
  <div class="modal-backdrop fade show" x-show="bcShow" @click="bcShow=false"></div>

</div>
@endsection

@push('scripts')
<script>
function batchSummaryPage(){
  return {
    bcShow:false, bcBrn:'', bcBatchId:'', bcCartons:[], bcLoading:false,
    onSummaryClick(e){
      const btn = e.target.closest('[data-batch-cartons]');
      if(btn){ e.preventDefault(); this.openBatchCartons(btn.dataset.batchCartons, btn.dataset.brn); }
    },
    async openBatchCartons(batchId, brn){
      this.bcShow=true; this.bcBrn=brn||''; this.bcBatchId=batchId; this.bcCartons=[]; this.bcLoading=true;
      try{
        const r=await fetch(`{{ url('master-cartons/batch') }}/${batchId}/cartons`,{headers:{'Accept':'application/json'}});
        const d=await r.json(); this.bcCartons=d.cartons||[];
      }catch(e){ this.bcCartons=[]; }
      this.bcLoading=false;
    },
  };
}
</script>
@endpush
