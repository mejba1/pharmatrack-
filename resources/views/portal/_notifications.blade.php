@php
  $me = auth('customer')->user();
  $notifs = $me->portalNotifications()->limit(15)->get();
  $unread = $me->portalNotifications()->whereNull('read_at')->count();
@endphp
<div class="dropdown">
  <button class="btn btn-outline-secondary btn-sm rounded-3 position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
    <i class="bi bi-bell"></i>
    @if($unread)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
  </button>
  <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="width:340px;max-height:420px;overflow:auto;border-radius:14px">
    <div class="d-flex align-items-center px-3 py-2 border-bottom">
      <span class="fw-semibold small">Notifications @if($unread)<span class="badge bg-danger ms-1">{{ $unread }}</span>@endif</span>
      @if($unread)
        <form method="POST" action="{{ route('portal.notifications.read') }}" class="ms-auto">@csrf<button class="btn btn-link btn-sm p-0 text-decoration-none" style="color:var(--brand1)">Mark all read</button></form>
      @endif
    </div>
    @forelse($notifs as $n)
      <div class="px-3 py-2 border-bottom d-flex gap-2 {{ $n->read_at ? '' : 'bg-light' }}">
        <i class="bi {{ $n->icon ?: 'bi-bell' }} mt-1" style="color:var(--brand1)"></i>
        <div class="min-w-0">
          <div class="small fw-semibold">{{ $n->title }}</div>
          <div class="small text-muted text-truncate">{{ $n->message }}</div>
          <div class="text-muted" style="font-size:11px">{{ $n->created_at->diffForHumans() }}</div>
        </div>
        @unless($n->read_at)<span class="ms-auto mt-1 rounded-circle" style="width:8px;height:8px;background:var(--brand1);flex:0 0 auto"></span>@endunless
      </div>
    @empty
      <div class="px-3 py-5 text-center text-muted small"><i class="bi bi-bell-slash d-block mb-2 fs-4"></i>No notifications yet.</div>
    @endforelse
  </div>
</div>
