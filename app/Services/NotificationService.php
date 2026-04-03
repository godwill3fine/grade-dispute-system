<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function notify($userId, $title, $message, $link = null)
    {
        Notification::create([
            'recipient_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link
        ]);
    }
}