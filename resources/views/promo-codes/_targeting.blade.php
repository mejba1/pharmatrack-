{{-- Targeting (eligibility). $m = prefix ('form' | 'bulk'). Selects are empty —
     options are loaded on demand by Tom Select via the search endpoint, so this
     scales to very large customer / product / country lists. --}}
<div class="col-12"><hr class="my-1"><div class="text-muted text-uppercase fw-bold" style="font-size:11px;letter-spacing:.05em">Targeting (leave empty = everyone)</div></div>

<div class="col-md-4">
  <label class="form-label">Specific customers</label>
  <select id="{{ $m }}_customer_ids" name="customer_ids[]" multiple class="form-select form-select-sm" placeholder="Search customers…" autocomplete="off"></select>
</div>

<div class="col-md-4">
  <label class="form-label">Specific countries</label>
  <select id="{{ $m }}_country_ids" name="country_ids[]" multiple class="form-select form-select-sm" placeholder="Search countries…" autocomplete="off"></select>
</div>

<div class="col-md-4">
  <label class="form-label">Specific products</label>
  <select id="{{ $m }}_product_ids" name="product_ids[]" multiple class="form-select form-select-sm" placeholder="Order must include one…" autocomplete="off"></select>
</div>
