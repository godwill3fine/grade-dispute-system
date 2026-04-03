<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function userNotifications($userId)
    {
        return Notification::where('recipient_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function markAsRead($id)
    {
        $notification = Notification::find($id);
        $notification->is_read = true;
        $notification->save();

        return response()->json(['message' => 'Notification marked as read']);
    }
}
