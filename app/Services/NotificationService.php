<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function notify($userId, $title, $message, $link = null, $disputeId = null)
    {
        // Centralizes notification creation so controllers only describe the message.
        Notification::create([
            'dispute_id' => $disputeId,
            'recipient_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link
        ]);
    }
}
