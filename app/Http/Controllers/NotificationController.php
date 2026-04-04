<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function userNotifications(Request $request, $userId)
    {
        $actorIdentifier = $request->attributes->get('actor_identifier');
        $actorRole = $request->attributes->get('actor_role');

        if ($actorRole !== 'admin' && is_string($actorIdentifier) && $actorIdentifier !== $userId) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return Notification::where('recipient_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $actorIdentifier = $request->attributes->get('actor_identifier');
        $actorRole = $request->attributes->get('actor_role');

        if ($actorRole !== 'admin' && is_string($actorIdentifier) && $notification->recipient_id !== $actorIdentifier) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $notification->is_read = true;
        $notification->save();

        return response()->json(['message' => 'Notification marked as read']);
    }
}
