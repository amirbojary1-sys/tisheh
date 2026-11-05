<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notifications', ['notifications' => auth()->user()->notifications()->latest()->paginate(20)]);
    }

    public function read(string $id)
    {
        $n = auth()->user()->notifications()->findOrFail($id);
        $n->markAsRead();

        return back()->with('success', 'اعلان خوانده‌شده علامت‌گذاری شد.');
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'همه اعلان‌ها خوانده شدند.');
    }
}
