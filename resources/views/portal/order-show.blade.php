@extends('layouts.portal')
@section('title', 'Order '.$order->po_number)

@section('body')
<nav class="navbar bg-white border-bottom px-3 px-md-4 py-2 sticky-top">
  <a href="{{ route('portal.dashboard') }}" class="text-decoration-none d-inline-flex align-items-center">@include('portal._brand') <span class="text-muted fs-6 fw-normal ms-2">Portal</span></a>
  <div class="ms-auto d-flex align-items-center gap-2">
    @include('portal._notifications')
    <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-3"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
  </div>
</nav>

<div class="container-xl py-4" style="max-width:900px">

  <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-3 mb-3"><i class="bi bi-arrow-left me-1"></i>Back to orders</a>

  @if(session('status'))<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}</div>@endif

  <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
    <h4 class="fw-bold mb-0 font-monospace">{{ $order->po_number }}</h4>
    <span class="chip" style="background:#eef2ff;color:#4f46e5">{{ $order->status_label ?? ucfirst($order->status) }}</span>
    @if($editable)
      <span class="chip" style="background:#ecfdf5;color:#047857"><i class="bi bi-unlock me-1"></i>Editable</span>
    @else
      <span class="chip" style="background:#f1f5f9;color:#64748b"><i class="bi bi-lock me-1"></i>In progress — view only</span>
    @endif
  </div>
  <div class="text-muted small mb-4">Placed {{ $order->po_date?->format('d M Y') }} · Required by {{ $order->required_by_date?->format('d M Y') ?? '—' }}</div>

  {{-- Progress chain --}}
  <div class="card-soft p-3 p-md-4 mb-3">
    <div class="fw-semibold mb-3"><i class="bi bi-diagram-3 me-1" style="color:var(--brand1)"></i>Progress</div>
    <div class="d-flex align-items-center" style="max-width:520px">
      @foreach(['PO','SO','PI','CI'] as $i => $label)
        @php $n = $i + 1; $done = $chain['step'] >= $n; @endphp
        <div class="text-center" style="width:52px">
          <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center"
               style="width:30px;height:30px;font-size:11px;font-weight:700;{{ $done ? 'background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff' : 'background:#fff;border:1px solid #e2e8f0;color:#94a3b8' }}">
            @if($done)<i class="bi bi-check-lg"></i>@else{{ $label }}@endif
          </div>
          <div style="font-size:10px;{{ $done ? 'color:#16a34a;font-weight:600' : 'color:#94a3b8' }}">{{ $label }}@if($label==='CI' && $chain['ci_count'] > 1) ×{{ $chain['ci_count'] }}@endif</div>
        </div>
        @if($i < 3)<div class="flex-fill" style="height:2px;{{ $chain['step'] > $n ? 'background:#16a34a' : 'background:#e2e8f0' }}"></div>@endif
      @endforeach
    </div>
  </div>

  {{-- Line items --}}
  <div class="card-soft p-3 p-md-4 mb-3">
    <div class="fw-semibold mb-3"><i class="bi bi-box-seam me-1" style="color:var(--brand1)"></i>Items</div>
    <div class="table-responsive">
      <table class="table table-clean align-middle mb-0">
        <thead><tr><th>#</th><th>Product</th><th class="text-end">Quantity</th><th class="text-end">Unit price</th><th class="text-end">Line total</th></tr></thead>
        <tbody>
          @foreach($order->lines as $line)
            <tr>
              <td class="text-muted">{{ $line->line_number }}</td>
              <td>{{ $line->product?->name ?? '—' }}<div class="text-muted small font-monospace">{{ $line->product?->prn }}</div></td>
              <td class="text-end">{{ number_format((int) $line->quantity) }}</td>
              <td class="text-end">{{ (float) $line->unit_price > 0 ? $order->currency.' '.number_format((float) $line->unit_price, 2) : '—' }}</td>
              <td class="text-end">{{ (float) $line->line_total > 0 ? $order->currency.' '.number_format((float) $line->line_total, 2) : '—' }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr><th colspan="4" class="text-end">Total</th><th class="text-end">{{ $order->currency }} {{ number_format((float) $order->total_value, 2) }}</th></tr>
        </tfoot>
      </table>
    </div>
    @if((float) $order->total_value <= 0)
      <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Pricing will be confirmed by our team in your quote.</div>
    @endif
  </div>

  @if($order->remarks)
    <div class="card-soft p-3 p-md-4 mb-3">
      <div class="fw-semibold mb-2"><i class="bi bi-chat-left-text me-1" style="color:var(--brand1)"></i>Notes</div>
      <div class="small">{{ $order->remarks }}</div>
    </div>
  @endif

  {{-- Documents shared on this order --}}
  @if($order->documents->isNotEmpty())
    <div class="card-soft p-3 p-md-4 mb-3">
      <div class="fw-semibold mb-3"><i class="bi bi-paperclip me-1" style="color:var(--brand1)"></i>Documents</div>
      <div class="row g-2">
        @foreach($order->documents as $doc)
          <div class="col-md-6">
            <a href="{{ $doc->url }}" target="_blank" class="d-flex align-items-center gap-3 p-3 border rounded-3 text-body" style="border-color:var(--line)!important">
              <i class="bi bi-file-earmark-text fs-4"></i>
              <div class="min-w-0 flex-fill"><div class="fw-semibold text-truncate">{{ $doc->name }}</div></div>
              <i class="bi bi-download text-muted"></i>
            </a>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Conditional actions --}}
  <div class="d-flex justify-content-end gap-2">
    @if($editable)
      <form method="POST" action="{{ route('portal.order.cancel', $order) }}" onsubmit="return confirm('Cancel order {{ $order->po_number }}? This cannot be undone.')">
        @csrf
        <button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Cancel order</button>
      </form>
      <a href="{{ route('portal.order.edit', $order) }}" class="btn btn-grad px-4"><i class="bi bi-pencil me-1"></i>Edit order</a>
    @else
      <div class="text-muted small align-self-center"><i class="bi bi-lock me-1"></i>This order is being processed and can no longer be changed. Contact us for any updates.</div>
    @endif
  </div>

</div>
@endsection
