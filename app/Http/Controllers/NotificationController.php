<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->userNotifications()->latest()->paginate(20);
        auth()->user()->userNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead()
    {
        auth()->user()->userNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => auth()->user()->userNotifications()->whereNull('read_at')->count(),
        ]);
    }
}
