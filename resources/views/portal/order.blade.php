@extends('layouts.portal')
@section('title', 'Place an Order')

@section('body')
<nav class="navbar bg-white border-bottom px-3 px-md-4 py-2 sticky-top">
  <a href="{{ route('portal.dashboard') }}" class="text-decoration-none d-inline-flex align-items-center">@include('portal._brand') <span class="text-muted fs-6 fw-normal ms-2">Portal</span></a>
  <div class="ms-auto d-flex align-items-center gap-2">
    @include('portal._notifications')
    <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-3"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
  </div>
</nav>

<div class="container-xl py-4" style="max-width:900px" x-data="orderForm()">
  <h4 class="fw-bold mb-1">Place an Order</h4>
  <div class="text-muted small mb-4">Select products and quantities. We'll confirm pricing and send you a quote.</div>

  @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('portal.order.store') }}">
    @csrf
    <div class="card-soft p-3 p-md-4 mb-3">
      <div class="row g-3 mb-3">
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Required by</label>
          <input type="date" name="required_by_date" class="form-control" min="{{ now()->toDateString() }}" value="{{ now()->addDays(30)->toDateString() }}">
        </div>
      </div>

      <div class="d-flex align-items-center mb-2">
        <div class="fw-semibold"><i class="bi bi-box-seam me-1" style="color:var(--brand1)"></i>Items</div>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" @click="addLine()"><i class="bi bi-plus-lg me-1"></i>Add item</button>
      </div>

      <div class="table-responsive">
        <table class="table table-clean align-middle mb-0">
          <thead><tr><th style="min-width:220px">Product</th><th style="width:140px">Quantity</th><th style="width:44px"></th></tr></thead>
          <tbody>
            <template x-for="(line, i) in lines" :key="i">
              <tr>
                <td>
                  <select class="form-select form-select-sm" :name="`items[${i}][product_id]`" x-model="line.product_id" required>
                    <option value="">Select product…</option>
                    @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
                  </select>
                </td>
                <td><input type="number" min="1" class="form-control form-control-sm" :name="`items[${i}][quantity]`" x-model="line.quantity" required></td>
                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm btn-icon" @click="removeLine(i)" x-show="lines.length>1"><i class="bi bi-trash"></i></button></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-soft p-3 p-md-4 mb-3">
      <label class="form-label small fw-semibold">Notes (optional)</label>
      <textarea name="remarks" rows="2" class="form-control" placeholder="Delivery notes, special requirements…">{{ old('remarks') }}</textarea>
    </div>

    <div class="d-flex justify-content-end gap-2">
      <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
      <button class="btn btn-grad px-4"><i class="bi bi-cart-check me-1"></i>Submit order</button>
    </div>
  </form>
</div>

<script>
function orderForm(){
  return {
    lines: [{ product_id:'', quantity:1 }],
    addLine(){ this.lines.push({ product_id:'', quantity:1 }); },
    removeLine(i){ this.lines.splice(i,1); if(!this.lines.length) this.addLine(); },
  };
}
</script>
@endsection
