{{-- Shared Add/Edit batch fields. $mode = 'add' | 'edit'. Needs $products, $countries. --}}
@php $edit = $mode === 'edit'; @endphp
<div class="row g-3">

  {{-- Core --}}
  <div class="col-12"><div class="section-label">Batch Identity</div></div>

  <div class="col-md-6">
    <label class="form-label">Product <span class="text-danger">*</span></label>
    <select name="product_id" class="form-select" required
            @if($edit) x-effect="$el.value = editBatch?.product_id ?? ''" @endif>
      <option value="">Select product…</option>
      @foreach($products as $p)
        <option value="{{ $p->id }}" @if(!$edit){{ old('product_id')==$p->id ? 'selected':'' }}@endif>{{ $p->name }} ({{ $p->prn }})</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Batch Number <span class="text-danger">*</span></label>
    <input type="text" name="batch_number" class="form-control" required
           @if($edit) :value="editBatch?.batch_number" @else value="{{ old('batch_number') }}" @endif placeholder="e.g. B-2601-003">
  </div>
  <div class="col-md-3">
    <label class="form-label">Lot Number</label>
    <input type="text" name="lot_number" class="form-control"
           @if($edit) :value="editBatch?.lot_number" @else value="{{ old('lot_number') }}" @endif placeholder="e.g. LOT-2601-003">
  </div>

  {{-- Dates & Quantities --}}
  <div class="col-12"><div class="section-label">Dates &amp; Quantities</div></div>
  <div class="col-md-4">
    <label class="form-label">Manufacture Date <span class="text-danger">*</span></label>
    <input type="date" name="manufacture_date" class="form-control" required
           @if($edit) x-effect="$el.value = (editBatch?.manufacture_date ?? '').substring(0,10)" @else value="{{ old('manufacture_date') }}" @endif>
  </div>
  <div class="col-md-4">
    <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
    <input type="date" name="expiry_date" class="form-control" required
           @if($edit) x-effect="$el.value = (editBatch?.expiry_date ?? '').substring(0,10)" @else value="{{ old('expiry_date') }}" @endif>
  </div>
  <div class="col-md-2">
    <label class="form-label">Qty Produced <span class="text-danger">*</span></label>
    <input type="number" name="quantity_produced" class="form-control" min="0" required
           @if($edit) :value="editBatch?.quantity_produced" @else value="{{ old('quantity_produced') }}" @endif>
  </div>
  <div class="col-md-2">
    <label class="form-label">Qty Available</label>
    <input type="number" name="quantity_available" class="form-control" min="0"
           @if($edit) :value="editBatch?.quantity_available" @else value="{{ old('quantity_available') }}" @endif placeholder="= produced">
  </div>

  {{-- Manufacturing & QC --}}
  <div class="col-12"><div class="section-label">Manufacturing &amp; QC</div></div>
  <div class="col-md-4">
    <label class="form-label">Manufacturing Site</label>
    <input type="text" name="manufacturing_site" class="form-control"
           @if($edit) :value="editBatch?.manufacturing_site" @else value="{{ old('manufacturing_site') }}" @endif>
  </div>
  <div class="col-md-2">
    <label class="form-label">Mfg Country</label>
    <select name="manufacturing_country" class="form-select"
            @if($edit) x-effect="$el.value = editBatch?.manufacturing_country ?? ''" @endif>
      <option value="">—</option>
      @foreach($countries as $c)
        <option value="{{ $c->code }}" @if(!$edit){{ old('manufacturing_country')===$c->code ? 'selected':'' }}@endif>{{ $c->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">QC Status</label>
    <select name="qc_status" class="form-select"
            @if($edit) x-effect="$el.value = editBatch?.qc_status ?? 'pending'" @endif>
      @foreach(['pending'=>'Pending','released'=>'Released','quarantine'=>'Quarantine','rejected'=>'Rejected','recalled'=>'Recalled'] as $v=>$l)
        <option value="{{ $v }}" @if(!$edit){{ old('qc_status')===$v ? 'selected':'' }}@endif>{{ $l }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select"
            @if($edit) x-effect="$el.value = editBatch?.status ?? 'active'" @endif>
      @foreach(['active'=>'Active','expired'=>'Expired','recalled'=>'Recalled','quarantine'=>'Quarantine','depleted'=>'Depleted'] as $v=>$l)
        <option value="{{ $v }}" @if(!$edit){{ old('status')===$v ? 'selected':'' }}@endif>{{ $l }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">QC Approved By</label>
    <input type="text" name="qc_approved_by" class="form-control"
           @if($edit) :value="editBatch?.qc_approved_by" @else value="{{ old('qc_approved_by') }}" @endif>
  </div>
  <div class="col-md-4">
    <label class="form-label">QC Approval Date</label>
    <input type="date" name="qc_approval_date" class="form-control"
           @if($edit) x-effect="$el.value = (editBatch?.qc_approval_date ?? '').substring(0,10)" @else value="{{ old('qc_approval_date') }}" @endif>
  </div>

  {{-- Storage --}}
  <div class="col-12"><div class="section-label">Storage</div></div>
  <div class="col-md-6">
    <label class="form-label">Storage Conditions</label>
    <input type="text" name="storage_conditions" class="form-control"
           @if($edit) :value="editBatch?.storage_conditions" @else value="{{ old('storage_conditions') }}" @endif placeholder="e.g. Store below 25°C">
  </div>
  <div class="col-md-3">
    <label class="form-label">Storage Temp Min (°C)</label>
    <input type="number" step="0.01" name="storage_temp_min" class="form-control"
           @if($edit) :value="editBatch?.storage_temp_min" @else value="{{ old('storage_temp_min') }}" @endif>
  </div>
  <div class="col-md-3">
    <label class="form-label">Storage Temp Max (°C)</label>
    <input type="number" step="0.01" name="storage_temp_max" class="form-control"
           @if($edit) :value="editBatch?.storage_temp_max" @else value="{{ old('storage_temp_max') }}" @endif>
  </div>

  {{-- COA & Notes --}}
  <div class="col-12"><div class="section-label">Certificate of Analysis &amp; Notes</div></div>
  <div class="col-md-6">
    <label class="form-label">COA Document (PDF)</label>
    @if($edit)
    <div x-show="editBatch?.coa_url" class="d-flex align-items-center gap-2 mb-2">
      <a :href="editBatch?.coa_url" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm text-truncate" style="max-width:60%">
        <i class="bi bi-file-earmark-pdf me-1"></i><span x-text="editBatch?.coa_name"></span></a>
      <label class="form-check-label text-danger d-flex align-items-center" style="font-size:12px">
        <input type="checkbox" name="remove_coa" value="1" class="form-check-input me-1">Remove</label>
    </div>
    @endif
    <input type="file" name="coa" class="form-control" accept="application/pdf">
    <div class="form-text" style="font-size:10px">PDF only · max 10 MB</div>
  </div>
  <div class="col-md-6">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="2"
              @if($edit) x-effect="$el.value = editBatch?.notes ?? ''" @endif>@if(!$edit){{ old('notes') }}@endif</textarea>
  </div>
</div>
