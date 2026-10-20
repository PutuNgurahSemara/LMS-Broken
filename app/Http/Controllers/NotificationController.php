<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, string $id)
    {
        // Enforce ownership: only mark notification belonging to authenticated user
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return redirect()->back()->with('success', 'Notifikasi ditandai telah dibaca.');
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Semua notifikasi ditandai telah dibaca.');
    }
}
