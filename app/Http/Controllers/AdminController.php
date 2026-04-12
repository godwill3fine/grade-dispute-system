<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Lecturer;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function recentActivity()
    {
        return ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    public function userDirectory(): JsonResponse
    {
        $users = User::query()
            ->with('department:id,name')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'identifier',
                'role',
                'department_id',
                'status',
                'last_login',
                'created_at',
            ]);

        $students = Student::query()
            ->whereIn('reg_number', $users->where('role', 'student')->pluck('identifier')->filter())
            ->get()
            ->keyBy('reg_number');

        $lecturers = Lecturer::query()
            ->whereIn('lecturer_id', $users->where('role', 'lecturer')->pluck('identifier')->filter())
            ->get()
            ->keyBy('lecturer_id');

        $staff = Staff::query()
            ->whereIn('staff_id', $users->whereIn('role', ['staff', 'admin'])->pluck('identifier')->filter())
            ->get()
            ->keyBy('staff_id');

        $payload = $users
            ->map(function (User $user) use ($students, $lecturers, $staff) {
                $profile = match ($user->role) {
                    'student' => $students->get($user->identifier),
                    'lecturer' => $lecturers->get($user->identifier),
                    'staff', 'admin' => $staff->get($user->identifier),
                    default => null,
                };

                $department = trim((string) (
                    $user->department?->name
                    ?? $profile?->department
                    ?? $profile?->course
                    ?? ($user->role === 'admin' ? 'Administration' : '')
                ));

                $role = Str::lower((string) $user->role);

                return [
                    'id' => $user->id,
                    'name' => $this->displayNameFor($user, $profile),
                    'email' => $user->email,
                    'identifier' => $user->identifier ?: 'ID unavailable',
                    'role' => $role,
                    'role_label' => Str::upper($role === '' ? 'user' : $role),
                    'department' => $department !== '' ? $department : '-',
                    'status' => $this->normalizeStatus($user->status),
                    'last_login' => $user->last_login?->toIso8601String(),
                    'icon' => $role === 'admin' ? 'shield' : 'person',
                    'created_at' => $user->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return response()->json($payload);
    }

    private function displayNameFor(User $user, mixed $profile): string
    {
        if ($profile) {
            $salutation = trim((string) data_get($profile, 'salutation', ''));
            $displayName = trim((string) data_get($profile, 'display_name', data_get($profile, 'full_name', '')));

            if ($salutation !== '' && $displayName !== '') {
                $prefixed = Str::lower($salutation.' ');
                if (! Str::startsWith(Str::lower($displayName), $prefixed)) {
                    return trim($salutation.' '.$displayName);
                }
            }

            if ($displayName !== '') {
                return $displayName;
            }
        }

        $profileName = trim((string) data_get($profile, 'display_name', data_get($profile, 'full_name', '')));
        if ($profileName !== '') {
            return $profileName;
        }

        return trim((string) $user->name) !== '' ? trim((string) $user->name) : 'Unknown User';
    }

    private function normalizeStatus(?string $status): string
    {
        $normalized = Str::lower(trim((string) $status));

        return in_array($normalized, ['inactive', 'disabled', 'suspended'], true) ? 'inactive' : 'active';
    }
}
