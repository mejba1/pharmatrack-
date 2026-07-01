@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div>
  <div class="page-header">
    <div><h1>Notifications &amp; Alerts</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Notifications</div></div>
    @if($unread)
    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-outline-primary btn-sm"><i class="bi bi-check2-all me-1"></i>Mark all read ({{ $unread }})</button></form>
    @endif
  </div>

  @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $tone)
    @if(session($key))<div x-data x-init="$nextTick(() => $store.toast.show(@js(session($key)), '{{ $tone }}'))"></div>@endif
  @endforeach

  <div class="card"><div class="card-body p-0">
    <div class="list-group list-group-flush">
      @forelse($notifications as $n)
        <div class="list-group-item d-flex gap-3 align-items-start {{ $n->is_read ? '' : 'bg-light-primary' }}">
          <i class="bi {{ $n->icon }} fs-5 mt-1"></i>
          <div class="flex-fill min-w-0">
            <div class="fw-semibold">{{ $n->title }}</div>
            <div class="text-muted small">{{ $n->message }}</div>
            <div class="text-muted" style="font-size:11px">{{ $n->created_at->diffForHumans() }}</div>
          </div>
          <div class="d-flex gap-1 align-items-center">
            @if($n->action_url)
              <form method="POST" action="{{ route('notifications.read', $n) }}">@csrf<button class="btn btn-outline-primary btn-sm">{{ $n->action_label ?: 'Open' }}</button></form>
            @elseunless($n->is_read)
              <form method="POST" action="{{ route('notifications.read', $n) }}">@csrf<button class="btn btn-outline-secondary btn-sm btn-icon" title="Mark read"><i class="bi bi-check2"></i></button></form>
            @endif
            <form method="POST" action="{{ route('notifications.dismiss', $n) }}">@csrf<button class="btn btn-outline-danger btn-sm btn-icon" title="Dismiss"><i class="bi bi-x-lg"></i></button></form>
          </div>
        </div>
      @empty
        <div class="text-center text-muted py-5"><i class="bi bi-bell-slash d-block mb-2 fs-3"></i>No notifications.</div>
      @endforelse
    </div>
  </div></div>

  @if($notifications->hasPages())<div class="mt-3">{{ $notifications->links() }}</div>@endif
</div>
@endsection
