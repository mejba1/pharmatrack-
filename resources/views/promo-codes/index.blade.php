@extends('layouts.app')
@section('title', 'Promo Codes')

@section('content')
<div x-data="promoApp()">

  @foreach(['success' => 'success', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="page-header">
    <div>
      <h1>Promo Codes</h1>
      <div class="page-breadcrumb">Sales / Promo Codes &amp; Discounts</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary btn-sm" @click="openAdd()"><i class="bi bi-plus-lg me-1"></i>New Promo Code</button>
    </div>
  </div>

  <div class="row g-3 mb-3">
    @php $cards = [
      ['Total Codes', $stats['total'], 'bi-ticket-perforated', '#4f46e5'],
      ['Active', $stats['active'], 'bi-check-circle', '#10b981'],
      ['Times Redeemed', $stats['redeemed'], 'bi-bag-check', '#f59e0b'],
    ]; @endphp
    @foreach($cards as [$label,$value,$icon,$color])
      <div class="col-6 col-lg-4"><div class="card-soft p-3 d-flex align-items-center gap-3">
        <div class="d-inline-flex align-items-center justify-content-center rounded text-white" style="width:42px;height:42px;background:{{ $color }}"><i class="bi {{ $icon }}"></i></div>
        <div><div class="fw-bold fs-5">{{ $value }}</div><div class="text-muted small">{{ $label }}</div></div>
      </div></div>
    @endforeach
  </div>

  <div class="card-soft p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Code</th><th>Scope</th><th>Discount</th><th>Validity</th><th>Usage</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          @forelse($codes as $c)
            @php $payload = \Illuminate\Support\Js::from([
              'id' => $c->id, 'code' => $c->code, 'description' => $c->description,
              'scope' => $c->scope, 'discount_type' => $c->discount_type, 'discount_value' => (float) $c->discount_value,
              'product_id' => $c->product_id, 'min_order_value' => $c->min_order_value ? (float) $c->min_order_value : null,
              'max_discount' => $c->max_discount ? (float) $c->max_discount : null, 'usage_limit' => $c->usage_limit,
              'starts_at' => $c->starts_at?->format('Y-m-d'), 'ends_at' => $c->ends_at?->format('Y-m-d'), 'is_active' => $c->is_active,
            ]); @endphp
            <tr>
              <td><span class="fw-bold font-monospace">{{ $c->code }}</span>@if($c->description)<div class="text-muted small">{{ $c->description }}</div>@endif</td>
              <td><span class="badge bg-primary-subtle text-primary">{{ $c->scope_label }}</span>@if($c->scope==='product' && $c->product)<div class="text-muted small">{{ $c->product->name }}</div>@endif</td>
              <td>{{ $c->label }}@if($c->max_discount)<div class="text-muted small">max {{ number_format((float)$c->max_discount,2) }}</div>@endif</td>
              <td class="small">
                {{ $c->starts_at?->format('d M Y') ?? '—' }} → {{ $c->ends_at?->format('d M Y') ?? '—' }}
                @if($c->min_order_value)<div class="text-muted">min order {{ number_format((float)$c->min_order_value,2) }}</div>@endif
              </td>
              <td class="small">{{ $c->used_count }}@if($c->usage_limit) / {{ $c->usage_limit }}@endif</td>
              <td>
                <form method="POST" action="{{ route('promo-codes.toggle', $c) }}">@csrf
                  <button class="badge border-0 {{ $c->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary-emphasis' }}" title="Toggle">{{ $c->is_active ? 'Active' : 'Inactive' }}</button>
                </form>
              </td>
              <td class="text-end text-nowrap">
                <button class="btn btn-outline-secondary btn-sm btn-icon" title="Edit" @click="openEdit({{ $payload }})"><i class="bi bi-pencil"></i></button>
                <form method="POST" action="{{ route('promo-codes.destroy', $c) }}" class="d-inline" @submit="return confirm('Delete promo code {{ $c->code }}?')">@csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm btn-icon" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No promo codes yet. Create one to offer customers a discount at checkout.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Add / Edit modal --}}
  <div class="modal fade" :class="{show:showModal}" :style="showModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form method="POST" :action="form.id ? updateAction : '{{ route('promo-codes.store') }}'">
          @csrf
          <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
          <div class="modal-header"><h5 class="modal-title"><i class="bi bi-ticket-perforated me-2 text-primary"></i><span x-text="form.id ? 'Edit Promo Code' : 'New Promo Code'"></span></h5><button type="button" class="btn-close" @click="showModal=false"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Code <span class="text-danger">*</span></label><input type="text" name="code" class="form-control form-control-sm text-uppercase" x-model="form.code" placeholder="SAVE10" required></div>
              <div class="col-md-6"><label class="form-label">Description</label><input type="text" name="description" class="form-control form-control-sm" x-model="form.description" placeholder="Spring promotion"></div>

              <div class="col-md-4">
                <label class="form-label">Applies to <span class="text-danger">*</span></label>
                <select name="scope" class="form-select form-select-sm" x-model="form.scope" required>
                  @foreach(\App\Models\PromoCode::SCOPES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </select>
              </div>
              <div class="col-md-4" x-show="form.scope==='product'">
                <label class="form-label">Product <span class="text-danger">*</span></label>
                <select name="product_id" class="form-select form-select-sm" x-model="form.product_id" :required="form.scope==='product'">
                  <option value="">Select product…</option>
                  @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
                </select>
              </div>
              <template x-if="form.scope!=='none'">
                <div class="col-md-4">
                  <label class="form-label">Discount type</label>
                  <select name="discount_type" class="form-select form-select-sm" x-model="form.discount_type">
                    @foreach(\App\Models\PromoCode::TYPES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                  </select>
                </div>
              </template>
              <template x-if="form.scope!=='none'">
                <div class="col-md-4">
                  <label class="form-label">Value <span class="text-danger">*</span></label>
                  <div class="input-group input-group-sm">
                    <input type="number" min="0" step="0.01" name="discount_value" class="form-control" x-model="form.discount_value" required>
                    <span class="input-group-text" x-text="form.discount_type==='percent' ? '%' : 'amt'"></span>
                  </div>
                </div>
              </template>
              <template x-if="form.scope!=='none'">
                <div class="col-md-4"><label class="form-label">Max discount (cap)</label><input type="number" min="0" step="0.01" name="max_discount" class="form-control form-control-sm" x-model="form.max_discount" placeholder="optional"></div>
              </template>

              <div class="col-md-4"><label class="form-label">Min order value</label><input type="number" min="0" step="0.01" name="min_order_value" class="form-control form-control-sm" x-model="form.min_order_value" placeholder="optional"></div>
              <div class="col-md-4"><label class="form-label">Usage limit</label><input type="number" min="1" name="usage_limit" class="form-control form-control-sm" x-model="form.usage_limit" placeholder="unlimited"></div>
              <div class="col-md-2"><label class="form-label">Starts</label><input type="date" name="starts_at" class="form-control form-control-sm" x-model="form.starts_at"></div>
              <div class="col-md-2"><label class="form-label">Ends</label><input type="date" name="ends_at" class="form-control form-control-sm" x-model="form.ends_at"></div>

              <div class="col-12">
                <div class="form-check form-switch">
                  <input type="hidden" name="is_active" value="0">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="pc_active" x-model="form.is_active">
                  <label class="form-check-label" for="pc_active">Active</label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="showModal=false">Cancel</button>
            <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i><span x-text="form.id ? 'Update' : 'Create'"></span></button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showModal" @click="showModal=false" x-cloak></div>
</div>

@push('scripts')
<script>
function promoApp(){
  return {
    showModal:false,
    form:{},
    updateTpl:'{{ url('promo-codes') }}/__ID__',
    get updateAction(){ return this.updateTpl.replace('__ID__', this.form.id); },
    blank(){ return { id:null, code:'', description:'', scope:'total', discount_type:'percent', discount_value:'', product_id:'', min_order_value:'', max_discount:'', usage_limit:'', starts_at:'', ends_at:'', is_active:true }; },
    openAdd(){ this.form = this.blank(); this.showModal = true; },
    openEdit(c){ this.form = { ...this.blank(), ...c, product_id: c.product_id ?? '', min_order_value: c.min_order_value ?? '', max_discount: c.max_discount ?? '', usage_limit: c.usage_limit ?? '', starts_at: c.starts_at ?? '', ends_at: c.ends_at ?? '' }; this.showModal = true; },
  };
}
</script>
@endpush
@endsection
