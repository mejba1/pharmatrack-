{{-- Dual-list country picker. Props: $countries (collection), $selected (array of codes).
     Submits chosen codes as allowed_countries[]. Click an item to move it left↔right. --}}
@php
  $cpAll = $countries->map(fn ($c) => ['code' => strtoupper($c->code), 'name' => $c->name, 'flag' => $c->flag])->values();
  $cpSel = array_values(array_map('strtoupper', (array) ($selected ?? [])));
@endphp
<div x-data="countryPicker(@js($cpAll), @js($cpSel))">
  <template x-for="code in chosen" :key="'h-'+code"><input type="hidden" name="allowed_countries[]" :value="code"></template>
  <div class="row g-2">
    <div class="col-6">
      <div class="cp-label">Available <span class="text-muted" x-text="'('+available.length+')'"></span></div>
      <input type="text" x-model="q" class="form-control form-control-sm mb-1" placeholder="Search country…">
      <div class="cp-list">
        <template x-for="c in available" :key="c.code">
          <div class="cp-item" @click="add(c.code)"><span x-text="c.flag+' '+c.name+' ('+c.code+')'"></span><i class="bi bi-arrow-right cp-arrow"></i></div>
        </template>
        <div x-show="!available.length" class="cp-empty">No countries</div>
      </div>
    </div>
    <div class="col-6">
      <div class="cp-label d-flex align-items-center">Allowed <span class="text-muted ms-1" x-text="'('+chosen.length+')'"></span>
        <span class="ms-auto">
          <button type="button" class="btn btn-link btn-sm p-0 me-2" x-show="available.length" @click="addAll()">Add all</button>
          <button type="button" class="btn btn-link btn-sm p-0 text-danger" x-show="chosen.length" @click="clearAll()">Clear</button>
        </span>
      </div>
      <div class="cp-list">
        <template x-for="code in chosen" :key="'c-'+code">
          <div class="cp-item" @click="remove(code)"><i class="bi bi-arrow-left cp-arrow"></i><span x-text="label(code)"></span></div>
        </template>
        <div x-show="!chosen.length" class="cp-empty">Any country (none = no restriction)</div>
      </div>
    </div>
  </div>
</div>
