<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->where('is_dismissed', false)
            ->latest()
            ->paginate(30);

        $unread = Notification::where('user_id', $request->user()->id)
            ->where('is_dismissed', false)->where('is_read', false)->count();

        return view('notifications', compact('notifications', 'unread'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::where('user_id', $request->user()->id)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return back();
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['is_read' => true, 'read_at' => now()]);

        return $notification->action_url ? redirect($notification->action_url) : back();
    }

    public function dismiss(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['is_dismissed' => true, 'dismissed_at' => now()]);

        return back();
    }
}
