{{-- Targeting (eligibility) multi-selects. $m = Alpine model prefix ('form' | 'bulk'). --}}
<div class="col-12"><hr class="my-1"><div class="text-muted text-uppercase fw-bold" style="font-size:11px;letter-spacing:.05em">Targeting (leave empty = everyone)</div></div>

<div class="col-md-4">
  <label class="form-label">Specific customers</label>
  <select name="customer_ids[]" multiple size="5" class="form-select form-select-sm" x-model="{{ $m }}.customer_ids">
    @foreach($customers as $cu)<option value="{{ $cu->id }}">{{ $cu->name }} ({{ $cu->customer_code }})</option>@endforeach
  </select>
  <div class="text-muted" style="font-size:11px">Ctrl/⌘-click to pick several.</div>
</div>

<div class="col-md-4">
  <label class="form-label">Specific countries</label>
  <select name="country_ids[]" multiple size="5" class="form-select form-select-sm" x-model="{{ $m }}.country_ids">
    @foreach($countries as $co)<option value="{{ $co->id }}">{{ $co->flag }} {{ $co->name }}</option>@endforeach
  </select>
  <div class="text-muted" style="font-size:11px">Based on the customer's country.</div>
</div>

<div class="col-md-4">
  <label class="form-label">Specific products</label>
  <select name="product_ids[]" multiple size="5" class="form-select form-select-sm" x-model="{{ $m }}.product_ids">
    @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
  </select>
  <div class="text-muted" style="font-size:11px">Order must include one of these.</div>
</div>
