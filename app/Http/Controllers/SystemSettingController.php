<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Services\ActivityLogService;

class SystemSettingController extends Controller
{
    // Get all settings
    public function index()
    {
        return SystemSetting::orderBy('setting_key')->get();
    }

    // Update settings
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array|min:1',
            'updated_by' => 'nullable|string|max:255',
        ]);

        $actorIdentifier = $request->attributes->get('actor_identifier');
        $updatedBy = is_string($actorIdentifier) && $actorIdentifier !== ''
            ? $actorIdentifier
            : $request->updated_by;

        foreach ($request->settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => (string) $value,
                    'updated_by' => $updatedBy
                ]
            );
        }

        // Activity log
        ActivityLogService::log(
            $updatedBy,
            'Updated System Settings',
            'system_settings',
            null,
            'System settings were updated'
        );

        return response()->json([
            'message' => 'Settings updated successfully'
        ]);
    }
}
