@extends('layouts.portal-app')
@php $editing = isset($order) && $order; @endphp
@section('title', $editing ? 'Edit Order' : 'Place an Order')
@section('heading', $editing ? 'Edit Order' : 'Place an Order')

@section('content')
@php
  $lineData = $editing
    ? $order->lines->map(fn ($l) => ['product_id' => (string) $l->product_id, 'quantity' => (int) $l->quantity])->values()
    : collect([['product_id' => '', 'quantity' => 1]]);
@endphp

<div class="container-xl py-4" style="max-width:900px" x-data="orderForm({{ Illuminate\Support\Js::from($lineData) }})">
  <a href="{{ $editing ? route('portal.order.show', $order) : route('portal.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-3 mb-3"><i class="bi bi-arrow-left me-1"></i>{{ $editing ? 'Back to order' : 'Back to dashboard' }}</a>
  <h4 class="fw-bold mb-1">{{ $editing ? 'Edit Order '.$order->po_number : 'Place an Order' }}</h4>
  <div class="text-muted small mb-4">Select products and quantities. We'll confirm pricing and send you a quote.</div>

  @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ $editing ? route('portal.order.update', $order) : route('portal.order.store') }}">
    @csrf
    @if($editing)@method('PUT')@endif
    <div class="card-soft p-3 p-md-4 mb-3">
      <div class="row g-3 mb-3">
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Required by</label>
          <input type="date" name="required_by_date" class="form-control" min="{{ now()->toDateString() }}"
                 value="{{ old('required_by_date', $editing ? $order->required_by_date?->toDateString() : now()->addDays(30)->toDateString()) }}">
        </div>
        @unless($editing)
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Promo code <span class="text-muted fw-normal">(optional)</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
            <input type="text" name="promo_code" class="form-control text-uppercase" value="{{ old('promo_code') }}" placeholder="Have a code? Enter it here">
          </div>
          <div class="text-muted" style="font-size:12px">We'll apply the discount when we quote your order.</div>
        </div>
        @endunless
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
      <textarea name="remarks" rows="2" class="form-control" placeholder="Delivery notes, special requirements…">{{ old('remarks', $editing ? $order->remarks : '') }}</textarea>
    </div>

    <div class="d-flex justify-content-end gap-2">
      <a href="{{ $editing ? route('portal.order.show', $order) : route('portal.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
      <button class="btn btn-grad px-4"><i class="bi bi-cart-check me-1"></i>{{ $editing ? 'Save changes' : 'Submit order' }}</button>
    </div>
  </form>
</div>

<script>
function orderForm(initialLines){
  return {
    lines: (initialLines && initialLines.length) ? initialLines : [{ product_id:'', quantity:1 }],
    addLine(){ this.lines.push({ product_id:'', quantity:1 }); },
    removeLine(i){ this.lines.splice(i,1); if(!this.lines.length) this.addLine(); },
  };
}
</script>
@endsection
