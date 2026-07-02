@extends('layouts.app')
@section('title', 'Banking Information')

@section('content')
<div x-data="bankApp()">

  @if(session('success'))<div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3"><i class="bi bi-check-circle-fill text-success"></i><span>{{ session('success') }}</span><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>@endif
  @if(session('error'))<div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3"><i class="bi bi-exclamation-triangle-fill text-danger"></i><span>{{ session('error') }}</span><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>@endif
  @if($errors->any())<div class="alert alert-danger mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:13px">{{ $e }}</li>@endforeach</ul></div>@endif

  <div class="page-header">
    <div>
      <h1>Banking Information</h1>
      <div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Master Data / Banking Information</div>
    </div>
    <div class="d-flex gap-2"><button class="btn btn-primary btn-sm" @click="openAdd()"><i class="bi bi-plus-lg me-1"></i>New Bank Account</button></div>
  </div>

  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr><th>Bank</th><th>Account name</th><th>Account no.</th><th>SWIFT</th><th>IBAN</th><th>Currency</th><th>Status</th><th style="width:80px"></th></tr>
          </thead>
          <tbody>
            @forelse($banks as $b)
              @php $payload = \Illuminate\Support\Js::from([
                'id' => $b->id, 'bank_name' => $b->bank_name, 'account_name' => $b->account_name, 'account_number' => $b->account_number,
                'swift_code' => $b->swift_code, 'iban' => $b->iban, 'branch' => $b->branch, 'address' => $b->address,
                'currency' => $b->currency, 'is_default' => $b->is_default, 'is_active' => $b->is_active, 'notes' => $b->notes,
              ]); @endphp
              <tr>
                <td class="fw-semibold" style="font-size:13px">
                  {{ $b->bank_name }}
                  @if($b->is_default)<span class="badge bg-primary-subtle text-primary ms-2">Default</span>@endif
                  @if($b->branch)<div class="text-muted small">{{ $b->branch }}</div>@endif
                </td>
                <td style="font-size:13px">{{ $b->account_name ?? '—' }}</td>
                <td class="font-monospace" style="font-size:13px">{{ $b->account_number ?? '—' }}</td>
                <td class="font-monospace" style="font-size:13px">{{ $b->swift_code ?? '—' }}</td>
                <td class="font-monospace" style="font-size:12px">{{ $b->iban ?? '—' }}</td>
                <td>{{ $b->currency ?? '—' }}</td>
                <td>@if($b->is_active)<span class="badge bg-success-subtle text-success">Active</span>@else<span class="badge bg-secondary-subtle text-secondary-emphasis">Inactive</span>@endif</td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-icon" title="Edit" @click="openEdit({{ $payload }})"><i class="bi bi-pencil"></i></button>
                    <form method="POST" action="{{ route('master.banks.destroy', $b) }}" onsubmit="return confirm('Remove bank account {{ addslashes($b->bank_name) }}?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm btn-icon" title="Remove"><i class="bi bi-trash"></i></button></form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-bank" style="font-size:32px;opacity:.2"></i><div class="mt-2">No bank accounts yet. Add one — it will appear in the Proforma Invoice bank picker.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($banks->hasPages())<div class="px-3 py-2 border-top">{{ $banks->links('pagination::bootstrap-5') }}</div>@endif
    </div>
  </div>

  {{-- Add / Edit modal --}}
  <div class="modal fade" :class="{show:open}" :style="open?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form method="POST" :action="form.id ? updateAction : '{{ route('master.banks.store') }}'">
          @csrf
          <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
          <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-bank me-2 text-primary"></i><span x-text="form.id ? 'Edit Bank Account' : 'New Bank Account'"></span></h5><button type="button" class="btn-close" @click="open=false"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-7"><label class="form-label">Bank name <span class="text-danger">*</span></label><input type="text" name="bank_name" class="form-control form-control-sm" x-model="form.bank_name" placeholder="e.g. HSBC Bank" required></div>
              <div class="col-md-5"><label class="form-label">Branch</label><input type="text" name="branch" class="form-control form-control-sm" x-model="form.branch" placeholder="Branch / office"></div>
              <div class="col-md-6"><label class="form-label">Account name (beneficiary)</label><input type="text" name="account_name" class="form-control form-control-sm" x-model="form.account_name"></div>
              <div class="col-md-4"><label class="form-label">Account number</label><input type="text" name="account_number" class="form-control form-control-sm" x-model="form.account_number"></div>
              <div class="col-md-2"><label class="form-label">Currency</label><input type="text" name="currency" maxlength="3" class="form-control form-control-sm text-uppercase" x-model="form.currency" placeholder="USD"></div>
              <div class="col-md-4"><label class="form-label">SWIFT / BIC</label><input type="text" name="swift_code" class="form-control form-control-sm text-uppercase" x-model="form.swift_code"></div>
              <div class="col-md-8"><label class="form-label">IBAN</label><input type="text" name="iban" class="form-control form-control-sm" x-model="form.iban"></div>
              <div class="col-12"><label class="form-label">Bank address</label><input type="text" name="address" class="form-control form-control-sm" x-model="form.address"></div>
              <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control form-control-sm" x-model="form.notes"></textarea></div>
              <div class="col-md-6">
                <div class="form-check form-switch"><input type="hidden" name="is_default" value="0"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="bk_default" x-model="form.is_default"><label class="form-check-label" for="bk_default">Default account (pre-selected on invoices)</label></div>
              </div>
              <div class="col-md-6">
                <div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="bk_active" x-model="form.is_active"><label class="form-check-label" for="bk_active">Active</label></div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="open=false">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i><span x-text="form.id ? 'Save changes' : 'Add account'"></span></button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="open" @click="open=false" x-cloak></div>
</div>

@push('scripts')
<script>
function bankApp(){
  return {
    open:false,
    form:{},
    updateTpl:'{{ url('master/bank-accounts') }}/__ID__',
    get updateAction(){ return this.updateTpl.replace('__ID__', this.form.id); },
    blank(){ return { id:null, bank_name:'', account_name:'', account_number:'', swift_code:'', iban:'', branch:'', address:'', currency:'USD', notes:'', is_default:false, is_active:true }; },
    openAdd(){ this.form = this.blank(); this.open = true; },
    openEdit(b){ this.form = { ...this.blank(), ...b }; this.open = true; },
  };
}
</script>
@endpush
@endsection
