@extends('layouts.app')
@section('title', 'Country Authorization')

@section('content')
<div class="page-header">
  <div>
    <h1>Country Authorization</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Country Authorization</div>
  </div>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

<div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>Define each product's authorized markets. Scans from countries outside this list raise an <strong>Unauthorized Market</strong> risk alert. Leave a product with no countries to allow everywhere.</div>

<div class="card mb-3"><div class="card-body">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-6">
      <label class="form-label">Product</label>
      <select name="product_id" class="form-select form-select-sm" onchange="this.form.submit()">
        @foreach($products as $p)<option value="{{ $p->id }}" @selected($productId==$p->id)>{{ $p->name }} ({{ $p->prn }})</option>@endforeach
      </select>
    </div>
  </form>
</div></div>

<div class="row g-3">
  <div class="col-md-5">
    <div class="card"><div class="card-body">
      <h6 class="fw-bold mb-3">Add Authorized Market</h6>
      <form method="POST" action="{{ route('anticounterfeit.countries.store') }}" class="d-flex gap-2">
        @csrf
        <input type="hidden" name="product_id" value="{{ $productId }}">
        <select name="country_code" class="form-select form-select-sm" required>
          <option value="">Select country…</option>
          @foreach($countries as $c)<option value="{{ $c->code }}">{{ $c->flag }} {{ $c->name }}</option>@endforeach
        </select>
        <button class="btn btn-primary btn-sm flex-shrink-0"><i class="bi bi-plus-lg"></i></button>
      </form>
    </div></div>
  </div>
  <div class="col-md-7">
    <div class="card"><div class="card-body p-0"><div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Authorized Country</th><th>Code</th><th class="text-end">Action</th></tr></thead>
        <tbody>
          @forelse($authorized as $a)
            <tr>
              <td>{{ $a->country_name }}</td>
              <td class="font-monospace small">{{ $a->country_code }}</td>
              <td class="text-end">
                <form method="POST" action="{{ route('anticounterfeit.countries.destroy', $a) }}" onsubmit="return confirm('Remove this market?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-center text-muted py-4">No authorized markets — product allowed everywhere.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div></div></div>
  </div>
</div>
@endsection
