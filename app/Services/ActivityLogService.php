<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogService
{
    public static function log($userId, $action, $entityType = null, $entityId = null, $description = null)
    {
        $resolvedUserId = self::resolveUserId($userId);

        ActivityLog::create([
            'user_id' => $resolvedUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description
        ]);
    }

    private static function resolveUserId($userId): ?int
    {
        if ($userId === null) {
            return null;
        }

        if (is_int($userId)) {
            return $userId;
        }

        if (is_string($userId) && trim($userId) !== '') {
            $user = User::where('identifier', trim($userId))->first();
            return $user?->id;
        }

        return null;
    }
}
