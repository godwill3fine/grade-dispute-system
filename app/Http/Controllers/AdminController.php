<?php

namespace App\Http\Controllers;

use App\Mail\AdminUserWelcomeMail;
use App\Models\Attachment;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Department;
use App\Models\Dispute;
use App\Models\DisputeLog;
use App\Models\Lecturer;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Unit;
use App\Models\UnitLecturer;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    // Returns the most recent admin-visible activity log entries.
    public function recentActivity()
    {
        return ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    // Builds the user directory by combining account rows with role-specific profiles.
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
                $staffSubrole = $role === 'staff'
                    ? trim((string) data_get($profile, 'role', ''))
                    : '';

                return [
                    'id' => $user->id,
                    'name' => $this->displayNameFor($user, $profile),
                    'email' => $user->email,
                    'identifier' => $user->identifier ?: 'ID unavailable',
                    'role' => $role,
                    'role_label' => $role === 'staff' && $staffSubrole !== ''
                        ? 'STAFF / '.Str::upper(str_replace('_', ' ', $staffSubrole))
                        : Str::upper($role === '' ? 'user' : $role),
                    'department' => $department !== '' ? $department : '-',
                    'status' => $this->normalizeStatus($user->status),
                    'last_login' => $user->last_login?->toIso8601String(),
                    'icon' => in_array($role, ['staff', 'admin'], true) ? 'shield' : 'person',
                    'created_at' => $user->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return response()->json($payload);
    }

    // Creates a managed user account and the matching student, lecturer, or staff profile.
    public function storeUser(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'identifier' => ['required', 'string', 'max:255', 'unique:users,identifier'],
            'role' => ['required', 'string'],
            'staff_role' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:255'],
            'faculty' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1', 'max:12'],
            'salutation' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string'],
            'office_location' => ['nullable', 'string', 'max:255'],
            'teaching_assignments' => ['nullable', 'string'],
            'send_welcome_email' => ['nullable', 'boolean'],
            'require_password_reset' => ['nullable', 'boolean'],
            'manual_approval_required' => ['nullable', 'boolean'],
        ]);

        $role = $this->normalizeImportRole((string) $request->input('role'));
        $firstName = trim((string) $request->input('first_name'));
        $lastName = trim((string) $request->input('last_name'));
        $email = Str::lower(trim((string) $request->input('email')));
        $identifier = trim((string) $request->input('identifier'));
        $departmentName = $this->normalizeDepartmentSelection((string) $request->input('department', ''));
        $facultyName = trim((string) $request->input('faculty', ''));
        $course = $this->normalizeCourseSelection((string) $request->input('course', ''));
        $salutation = trim((string) $request->input('salutation', ''));
        $staffRole = $this->normalizeStaffRole((string) $request->input('staff_role', ''), $role);
        $officeLocation = trim((string) $request->input('office_location', ''));
        $teachingAssignments = (string) $request->input('teaching_assignments', '');
        $selectedStatus = $this->normalizeAccountStatus((string) $request->input('status', 'active'));
        $sendWelcomeEmail = $request->boolean('send_welcome_email');
        $requirePasswordReset = $request->boolean('require_password_reset');
        $manualApprovalRequired = $request->boolean('manual_approval_required');
        $effectiveStatus = $this->effectiveAccountStatus($selectedStatus, $manualApprovalRequired);
        $roleIds = Role::query()->pluck('id', 'name')->all();
        $temporaryPassword = $identifier;

        if ($role === 'student') {
            if ($course === '') {
                throw ValidationException::withMessages([
                    'course' => ['Course is required for student accounts.'],
                ]);
            }

            if (! $request->filled('year')) {
                throw ValidationException::withMessages([
                    'year' => ['Year is required for student accounts.'],
                ]);
            }

            [$departmentName, $facultyName] = $this->courseProfileDefaults($course, $departmentName, $facultyName);
        }

        if (in_array($role, ['lecturer', 'staff'], true) && $departmentName === '') {
            throw ValidationException::withMessages([
                'department' => ['Department is required for lecturer and staff accounts.'],
            ]);
        }

        $department = $this->resolveImportDepartment($departmentName, $facultyName);

        $user = DB::transaction(function () use (
            $role,
            $firstName,
            $lastName,
            $email,
            $identifier,
            $department,
            $departmentName,
            $facultyName,
            $course,
            $salutation,
            $staffRole,
            $officeLocation,
            $effectiveStatus,
            $requirePasswordReset,
            $manualApprovalRequired,
            $roleIds,
            $temporaryPassword,
            $teachingAssignments,
            $request
        ): User {
            if ($role === 'student') {
                Student::updateOrCreate(
                    ['reg_number' => $identifier],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'course' => $course,
                        'year' => (int) $request->input('year'),
                        'department' => $departmentName !== '' ? $departmentName : null,
                        'faculty' => $facultyName !== '' ? $facultyName : null,
                    ]
                );
            } elseif ($role === 'lecturer') {
                Lecturer::updateOrCreate(
                    ['lecturer_id' => $identifier],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'salutation' => $salutation !== '' ? $salutation : null,
                        'department' => $departmentName,
                    ]
                );

                $this->syncLecturerTeachingAssignments($identifier, $teachingAssignments);
            } else {
                Staff::updateOrCreate(
                    ['staff_id' => $identifier],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'salutation' => $salutation !== '' ? $salutation : null,
                        'role' => $this->staffProfileRoleFor($role, $staffRole),
                        'department' => $departmentName !== '' ? $departmentName : null,
                    ]
                );
            }

            return User::create([
                'name' => trim($firstName.' '.$lastName),
                'email' => $email,
                'identifier' => $identifier,
                'role' => $role,
                'role_id' => $this->resolveUserRoleId($roleIds, $role, $staffRole),
                'department_id' => $department?->id,
                'status' => $effectiveStatus,
                'office_location' => $officeLocation !== '' ? $officeLocation : null,
                'require_password_reset' => $requirePasswordReset,
                'manual_approval_required' => $manualApprovalRequired,
                'password' => Hash::make($temporaryPassword),
            ]);
        });

        $welcomeEmailSent = false;

        if ($sendWelcomeEmail) {
            try {
                Mail::to($user->email)->send(
                    new AdminUserWelcomeMail(
                        recipientName: trim($user->name),
                        identifier: $user->identifier,
                        roleLabel: $this->userRoleLabel($role, $staffRole),
                        portalUrl: route('portal.auth'),
                        passwordSetupUrl: route('portal.password.forgot', ['identifier' => $user->identifier]),
                        statusLabel: Str::title(str_replace('_', ' ', $effectiveStatus)),
                        requirePasswordReset: $requirePasswordReset,
                        manualApprovalRequired: $manualApprovalRequired,
                    )
                );

                $user->forceFill([
                    'welcome_email_sent_at' => now(),
                ])->save();

                $welcomeEmailSent = true;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        ActivityLogService::log(
            trim((string) ($request->attributes->get('actor_identifier') ?? $request->user()?->identifier ?? '')),
            'Create User',
            'user',
            $user->id,
            "Created {$role} account {$identifier} with status {$effectiveStatus}."
        );

        [$adminNotice, $adminNoticeTone] = $this->buildUserCreateAdminNotice(
            sendWelcomeEmail: $sendWelcomeEmail,
            welcomeEmailSent: $welcomeEmailSent,
            requirePasswordReset: $requirePasswordReset,
            effectiveStatus: $effectiveStatus,
            identifier: $identifier,
        );

        return response()->json([
            'message' => "{$user->name} was created successfully.",
            'next' => route('portal.admin.users'),
            'admin_notice' => $adminNotice,
            'admin_notice_tone' => $adminNoticeTone,
            'user' => [
                'id' => $user->id,
                'identifier' => $user->identifier,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'welcome_email_sent' => $welcomeEmailSent,
            'welcome_email_requested' => $sendWelcomeEmail,
            'require_password_reset' => $requirePasswordReset,
            'manual_approval_required' => $manualApprovalRequired,
            'temporary_password_hint' => 'Temporary password defaults to the University ID when you need to share access manually.',
        ], 201);
    }

    // Updates an account and keeps the role-specific profile tables in sync.
    public function updateUser(Request $request, string $identifier): JsonResponse
    {
        $user = User::query()->where('identifier', $identifier)->firstOrFail();

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string'],
            'staff_role' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:255'],
            'faculty' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1', 'max:12'],
            'salutation' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string'],
            'office_location' => ['nullable', 'string', 'max:255'],
            'teaching_assignments' => ['nullable', 'string'],
            'send_account_email' => ['nullable', 'boolean'],
            'require_password_reset' => ['nullable', 'boolean'],
            'manual_approval_required' => ['nullable', 'boolean'],
        ]);

        $role = $this->normalizeImportRole((string) $request->input('role'));
        $firstName = trim((string) $request->input('first_name'));
        $lastName = trim((string) $request->input('last_name'));
        $email = Str::lower(trim((string) $request->input('email')));
        $departmentName = $this->normalizeDepartmentSelection((string) $request->input('department', ''));
        $facultyName = trim((string) $request->input('faculty', ''));
        $course = $this->normalizeCourseSelection((string) $request->input('course', ''));
        $year = $request->filled('year') ? (int) $request->input('year') : null;
        $salutation = trim((string) $request->input('salutation', ''));
        $staffRole = $this->normalizeStaffRole((string) $request->input('staff_role', ''), $role);
        $officeLocation = trim((string) $request->input('office_location', ''));
        $teachingAssignments = (string) $request->input('teaching_assignments', '');
        $selectedStatus = $this->normalizeAccountStatus((string) $request->input('status', 'active'));
        $sendAccountEmail = $request->boolean('send_account_email');
        $requirePasswordReset = $request->boolean('require_password_reset');
        $manualApprovalRequired = $request->boolean('manual_approval_required');
        $effectiveStatus = $this->effectiveAccountStatus($selectedStatus, $manualApprovalRequired);
        $roleIds = Role::query()->pluck('id', 'name')->all();

        if ($role === 'student') {
            if ($course === '') {
                throw ValidationException::withMessages([
                    'course' => ['Course is required for student accounts.'],
                ]);
            }

            if ($year === null) {
                throw ValidationException::withMessages([
                    'year' => ['Year is required for student accounts.'],
                ]);
            }

            [$departmentName, $facultyName] = $this->courseProfileDefaults($course, $departmentName, $facultyName);
        }

        if (in_array($role, ['lecturer', 'staff'], true) && $departmentName === '') {
            throw ValidationException::withMessages([
                'department' => ['Department is required for lecturer and staff accounts.'],
            ]);
        }

        $department = $this->resolveImportDepartment($departmentName, $facultyName);

        $this->guardSelfAdministration(
            actor: $request->user(),
            target: $user,
            nextRole: $role,
            nextStatus: $effectiveStatus,
        );

        DB::transaction(function () use (
            $user,
            $role,
            $firstName,
            $lastName,
            $departmentName,
            $facultyName,
            $course,
            $year,
            $salutation,
            $staffRole,
            $email,
            $department,
            $effectiveStatus,
            $officeLocation,
            $requirePasswordReset,
            $manualApprovalRequired,
            $roleIds,
            $teachingAssignments
        ): void {
            $this->syncUserProfileForRole(
                identifier: $user->identifier,
                role: $role,
                firstName: $firstName,
                lastName: $lastName,
                departmentName: $departmentName,
                facultyName: $facultyName,
                course: $course,
                year: $year,
                salutation: $salutation,
                staffRole: $staffRole,
            );

            if ($role === 'lecturer') {
                $this->syncLecturerTeachingAssignments($user->identifier, $teachingAssignments);
            }

            $this->removeStaleProfiles($user->identifier, $role);

            $user->forceFill([
                'name' => trim($firstName.' '.$lastName),
                'email' => $email,
                'role' => $role,
                'role_id' => $this->resolveUserRoleId($roleIds, $role, $staffRole),
                'department_id' => $department?->id,
                'status' => $effectiveStatus,
                'office_location' => $officeLocation !== '' ? $officeLocation : null,
                'require_password_reset' => $requirePasswordReset,
                'manual_approval_required' => $manualApprovalRequired,
            ])->save();
        });

        $accountEmailSent = false;

        if ($sendAccountEmail) {
            $accountEmailSent = $this->sendUserAccountEmail($user);
        }

        ActivityLogService::log(
            trim((string) ($request->attributes->get('actor_identifier') ?? $request->user()?->identifier ?? '')),
            'Update User',
            'user',
            $user->id,
            "Updated {$user->role} account {$user->identifier} with status {$user->status}."
        );

        [$adminNotice, $adminNoticeTone] = $this->buildUserEditAdminNotice(
            sendAccountEmail: $sendAccountEmail,
            accountEmailSent: $accountEmailSent,
            requirePasswordReset: $requirePasswordReset,
            effectiveStatus: $user->status,
            identifier: $user->identifier,
        );

        return response()->json([
            'message' => "{$user->name} was updated successfully.",
            'admin_notice' => $adminNotice,
            'admin_notice_tone' => $adminNoticeTone,
            'next' => route('portal.admin.users.edit', ['identifier' => $user->identifier]),
            'user' => [
                'identifier' => $user->identifier,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    // Handles focused actions such as activation, deactivation, resend, and delete.
    public function userAction(Request $request, string $identifier): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string'],
        ]);

        $user = User::query()->where('identifier', $identifier)->firstOrFail();
        $action = trim((string) $request->input('action'));
        $actorIdentifier = trim((string) ($request->attributes->get('actor_identifier') ?? $request->user()?->identifier ?? ''));

        if ($action === 'send_password_reset') {
            $user->forceFill([
                'require_password_reset' => true,
            ])->save();

            $emailSent = $this->sendUserAccountEmail($user, true);

            ActivityLogService::log(
                $actorIdentifier,
                'Send Password Reset',
                'user',
                $user->id,
                "Triggered password reset instructions for {$user->identifier}."
            );

            return response()->json([
                'message' => $emailSent
                    ? "Password reset instructions sent to {$user->email}."
                    : "Password reset is now required for {$user->identifier}, but the email could not be sent.",
                'tone' => $emailSent ? 'success' : 'warning',
                'reload' => true,
            ]);
        }

        if ($action === 'resend_account_email') {
            $emailSent = $this->sendUserAccountEmail($user);

            ActivityLogService::log(
                $actorIdentifier,
                'Resend Account Email',
                'user',
                $user->id,
                "Resent onboarding email for {$user->identifier}."
            );

            return response()->json([
                'message' => $emailSent
                    ? "Account email sent to {$user->email}."
                    : "Unable to send the account email for {$user->identifier} right now.",
                'tone' => $emailSent ? 'success' : 'warning',
                'reload' => $emailSent,
            ]);
        }

        if ($action === 'deactivate') {
            if ($request->user()?->id === $user->id) {
                throw ValidationException::withMessages([
                    'action' => ['You cannot deactivate the admin account you are currently using.'],
                ]);
            }

            $user->forceFill([
                'status' => 'inactive',
            ])->save();

            ActivityLogService::log(
                $actorIdentifier,
                'Deactivate User',
                'user',
                $user->id,
                "Deactivated {$user->identifier}."
            );

            return response()->json([
                'message' => "{$user->identifier} is now inactive.",
                'tone' => 'success',
                'reload' => true,
            ]);
        }

        if ($action === 'delete') {
            if ($request->user()?->id === $user->id) {
                throw ValidationException::withMessages([
                    'action' => ['You cannot delete the admin account you are currently using.'],
                ]);
            }

            $blockers = $this->userDeletionBlockers($user);

            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'action' => ['Delete is only available for unused accounts. '.implode(' ', $blockers).' Deactivate this user instead if you need to preserve system history.'],
                ]);
            }

            $deletedUserId = $user->id;
            $deletedIdentifier = trim((string) $user->identifier);

            DB::transaction(function () use ($user, $deletedIdentifier): void {
                Notification::query()->where('recipient_id', $deletedIdentifier)->delete();
                $this->removeStaleProfiles($deletedIdentifier, 'none');
                $user->delete();
            });

            ActivityLogService::log(
                $actorIdentifier,
                'Delete User',
                'user',
                $deletedUserId,
                "Deleted {$deletedIdentifier}."
            );

            return response()->json([
                'message' => "{$deletedIdentifier} was deleted successfully.",
                'tone' => 'success',
                'reload' => false,
                'removed' => true,
            ]);
        }

        if ($action === 'reset_login_factors') {
            return response()->json([
                'message' => 'No additional login factors are configured for this account yet.',
                'tone' => 'primary',
                'reload' => false,
            ]);
        }

        throw ValidationException::withMessages([
            'action' => ['Unsupported user action.'],
        ]);
    }

    // Imports users from CSV data and reports created, skipped, and errored rows.
    public function importUsers(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $rows = $this->parseImportRows($request->file('import_file')->getRealPath());
        if ($rows === []) {
            throw ValidationException::withMessages([
                'import_file' => ['The uploaded CSV does not contain any user rows.'],
            ]);
        }

        $roleIds = Role::query()->pluck('id', 'name')->all();
        $createdCount = 0;
        $skippedCount = 0;
        $defaultPasswordCount = 0;
        $warnings = [];
        $errors = [];
        $seenIdentifiers = [];
        $seenEmails = [];

        foreach ($rows as $lineNumber => $row) {
            try {
                $identifier = $this->requiredImportValue($row, 'identifier', 'identifier');
                $email = Str::lower($this->requiredImportValue($row, 'email', 'email'));

                if (in_array($identifier, $seenIdentifiers, true)) {
                    $skippedCount++;
                    $warnings[] = "Row {$lineNumber}: Duplicate identifier {$identifier} was skipped.";

                    continue;
                }

                if (in_array($email, $seenEmails, true)) {
                    $skippedCount++;
                    $warnings[] = "Row {$lineNumber}: Duplicate email {$email} was skipped.";

                    continue;
                }

                $seenIdentifiers[] = $identifier;
                $seenEmails[] = $email;

                if (User::query()->where('identifier', $identifier)->exists()) {
                    $skippedCount++;
                    $warnings[] = "Row {$lineNumber}: User {$identifier} already exists and was skipped.";

                    continue;
                }

                if (User::query()->where('email', $email)->exists()) {
                    $skippedCount++;
                    $warnings[] = "Row {$lineNumber}: Email {$email} is already in use and was skipped.";

                    continue;
                }

                $importResult = $this->importUserRow($row, $roleIds);
                $createdCount++;

                if ($importResult['used_default_password'] ?? false) {
                    $defaultPasswordCount++;
                }
            } catch (ValidationException $exception) {
                $errors[] = "Row {$lineNumber}: ".collect($exception->errors())->flatten()->implode(' ');
            } catch (\Throwable $exception) {
                report($exception);
                $errors[] = "Row {$lineNumber}: Unexpected import error.";
            }
        }

        if ($createdCount > 0) {
            ActivityLogService::log(
                $request->attributes->get('actor_identifier'),
                'Bulk User Import',
                'user',
                null,
                "Imported {$createdCount} user(s); skipped {$skippedCount}; validation errors ".count($errors).'.'
            );
        }

        return response()->json([
            'message' => $this->importSummaryMessage($createdCount, $skippedCount, count($errors)),
            'summary' => [
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'error_count' => count($errors),
                'default_password_count' => $defaultPasswordCount,
            ],
            'warnings' => $warnings,
            'errors' => $errors,
        ]);
    }

    // Creates or skips one normalized import row.
    private function importUserRow(array $row, array $roleIds): array
    {
        $role = $this->normalizeImportRole($this->requiredImportValue($row, 'role', 'role'));
        [$firstName, $lastName] = $this->resolveImportNameParts($row);
        $identifier = $this->requiredImportValue($row, 'identifier', 'identifier');
        $email = Str::lower($this->requiredImportValue($row, 'email', 'email'));
        $departmentName = $this->optionalImportValue($row, 'department');
        $facultyName = $this->optionalImportValue($row, 'faculty');
        $status = $this->normalizeAccountStatus($this->optionalImportValue($row, 'status'));
        $salutation = $this->optionalImportValue($row, 'salutation');
        $staffRole = $this->normalizeStaffRole($this->optionalImportValue($row, 'staff_role'), $role);
        $password = $this->optionalImportValue($row, 'password');
        $usedDefaultPassword = $password === '';
        $department = null;

        if ($role === 'student') {
            $course = $this->normalizeCourseSelection($this->requiredImportValue($row, 'course', 'course'));
            $year = $this->normalizeImportYear($this->requiredImportValue($row, 'year', 'year'));
            [$departmentName, $facultyName] = $this->courseProfileDefaults($course, $departmentName, $facultyName);
            $department = $this->resolveImportDepartment($departmentName, $facultyName);

            DB::transaction(function () use (
                $identifier,
                $firstName,
                $lastName,
                $email,
                $password,
                $usedDefaultPassword,
                $status,
                $department,
                $departmentName,
                $facultyName,
                $role,
                $roleIds,
                $course,
                $year
            ): void {
                Student::updateOrCreate(
                    ['reg_number' => $identifier],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'course' => $course,
                        'year' => $year,
                        'department' => $departmentName !== '' ? $departmentName : null,
                        'faculty' => $facultyName !== '' ? $facultyName : null,
                    ]
                );

                User::create([
                    'name' => trim($firstName.' '.$lastName),
                    'email' => $email,
                    'identifier' => $identifier,
                    'role' => $role,
                    'role_id' => $roleIds[$role] ?? null,
                    'department_id' => $department?->id,
                    'status' => $status,
                    'password' => Hash::make($usedDefaultPassword ? $identifier : $password),
                ]);
            });

            return ['used_default_password' => $usedDefaultPassword];
        }

        if ($role === 'lecturer') {
            if ($departmentName === '') {
                throw ValidationException::withMessages([
                    'department' => ['Department is required for lecturer imports.'],
                ]);
            }

            $department = $this->resolveImportDepartment($departmentName, $facultyName);

            DB::transaction(function () use (
                $identifier,
                $firstName,
                $lastName,
                $salutation,
                $departmentName,
                $email,
                $password,
                $usedDefaultPassword,
                $status,
                $department,
                $role,
                $roleIds
            ): void {
                Lecturer::updateOrCreate(
                    ['lecturer_id' => $identifier],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'salutation' => $salutation !== '' ? $salutation : null,
                        'department' => $departmentName,
                    ]
                );

                User::create([
                    'name' => trim($firstName.' '.$lastName),
                    'email' => $email,
                    'identifier' => $identifier,
                    'role' => $role,
                    'role_id' => $roleIds[$role] ?? null,
                    'department_id' => $department?->id,
                    'status' => $status,
                    'password' => Hash::make($usedDefaultPassword ? $identifier : $password),
                ]);
            });

            return ['used_default_password' => $usedDefaultPassword];
        }

        if ($role === 'staff' && $departmentName === '') {
            throw ValidationException::withMessages([
                'department' => ['Department is required for staff imports.'],
            ]);
        }

        $department = $this->resolveImportDepartment($departmentName, $facultyName);

        DB::transaction(function () use (
            $identifier,
            $firstName,
            $lastName,
            $salutation,
            $staffRole,
            $departmentName,
            $email,
            $password,
            $usedDefaultPassword,
            $status,
            $department,
            $role,
            $roleIds
        ): void {
            Staff::updateOrCreate(
                ['staff_id' => $identifier],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'salutation' => $salutation !== '' ? $salutation : null,
                    'role' => $this->staffProfileRoleFor($role, $staffRole),
                    'department' => $departmentName !== '' ? $departmentName : null,
                ]
            );

            User::create([
                'name' => trim($firstName.' '.$lastName),
                'email' => $email,
                'identifier' => $identifier,
                'role' => $role,
                'role_id' => $this->resolveUserRoleId($roleIds, $role, $staffRole),
                'department_id' => $department?->id,
                'status' => $status,
                'password' => Hash::make($usedDefaultPassword ? $identifier : $password),
            ]);
        });

        return ['used_default_password' => $usedDefaultPassword];
    }

    // Parses the uploaded CSV file into keyed rows using canonical column names.
    private function parseImportRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'import_file' => ['Unable to read the uploaded CSV file.'],
            ]);
        }

        try {
            $headerRow = fgetcsv($handle);
            if (! is_array($headerRow)) {
                return [];
            }

            $headers = array_map(fn ($value) => $this->canonicalImportColumn($value), $headerRow);
            $requiredHeaders = ['identifier', 'role', 'email'];
            $missingHeaders = array_values(array_filter($requiredHeaders, fn ($header) => ! in_array($header, $headers, true)));

            if (! in_array('first_name', $headers, true) && ! in_array('full_name', $headers, true)) {
                $missingHeaders[] = 'first_name';
            }

            if ($missingHeaders !== []) {
                throw ValidationException::withMessages([
                    'import_file' => ['Missing required CSV headers: '.implode(', ', array_unique($missingHeaders)).'.'],
                ]);
            }

            $rows = [];
            $lineNumber = 2;

            while (($columns = fgetcsv($handle)) !== false) {
                $columns = array_pad($columns, count($headers), null);
                $row = [];

                foreach ($headers as $index => $header) {
                    if ($header === '') {
                        continue;
                    }

                    $row[$header] = trim((string) ($columns[$index] ?? ''));
                }

                if (collect($row)->every(fn ($value) => trim((string) $value) === '')) {
                    $lineNumber++;
                    continue;
                }

                $rows[$lineNumber] = $row;
                $lineNumber++;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    // Normalizes alternate CSV headings into the names the importer expects.
    private function canonicalImportColumn(mixed $value): string
    {
        $normalized = trim((string) preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $value)));
        $normalized = Str::of($normalized)
            ->lower()
            ->replace(['-', ' '], '_')
            ->value();

        return [
            'university_id' => 'identifier',
            'user_id' => 'identifier',
            'reg_number' => 'identifier',
            'lecturer_id' => 'identifier',
            'staff_id' => 'identifier',
            'system_role' => 'role',
            'staff_responsibility' => 'staff_role',
            'staff_subrole' => 'staff_role',
            'sub_role' => 'staff_role',
            'name' => 'full_name',
        ][$normalized] ?? $normalized;
    }

    // Supports both split name columns and a legacy full-name import column.
    private function resolveImportNameParts(array $row): array
    {
        $firstName = $this->optionalImportValue($row, 'first_name');
        $lastName = $this->optionalImportValue($row, 'last_name');
        $fullName = $this->optionalImportValue($row, 'full_name');

        if (($firstName === '' || $lastName === '') && $fullName !== '') {
            $parts = preg_split('/\s+/', $fullName, 2);
            $firstName = $firstName !== '' ? $firstName : trim((string) ($parts[0] ?? ''));
            $lastName = $lastName !== '' ? $lastName : trim((string) ($parts[1] ?? ''));
        }

        if ($firstName === '' || $lastName === '') {
            throw ValidationException::withMessages([
                'name' => ['Both first_name and last_name are required for imported users.'],
            ]);
        }

        return [$firstName, $lastName];
    }

    // Reads a required import value and raises a clear row-level error when missing.
    private function requiredImportValue(array $row, string $key, string $label): string
    {
        $value = $this->optionalImportValue($row, $key);
        if ($value === '') {
            throw ValidationException::withMessages([
                $key => ["{$label} is required."],
            ]);
        }

        return $value;
    }

    // Reads an optional import value as a trimmed string.
    private function optionalImportValue(array $row, string $key): string
    {
        return trim((string) ($row[$key] ?? ''));
    }

    // Collapses CSV role labels into application role keys.
    private function normalizeImportRole(string $role): string
    {
        $normalized = Str::lower(trim($role));

        if (! in_array($normalized, ['student', 'lecturer', 'staff', 'admin'], true)) {
            throw ValidationException::withMessages([
                'role' => ['role must be one of: student, lecturer, staff, admin.'],
            ]);
        }

        return $normalized;
    }

    // Derives the staff subtype used by staff profiles and permissions.
    private function normalizeStaffRole(string $staffRole, string $role): string
    {
        if ($role === 'admin') {
            return 'admin';
        }

        if ($role !== 'staff') {
            return '';
        }

        $normalized = Str::of($staffRole)
            ->trim()
            ->lower()
            ->replace(['-', ' '], '_')
            ->value();

        $normalized = match ($normalized) {
            'head_of_department' => 'hod',
            default => $normalized,
        };

        if (! in_array($normalized, ['hod', 'exam_officer'], true)) {
            throw ValidationException::withMessages([
                'staff_role' => ['Staff responsibility must be either HOD or Exam Officer.'],
            ]);
        }

        return $normalized;
    }

    // Converts imported study year values into the stored integer range.
    private function normalizeImportYear(string $year): int
    {
        if (! ctype_digit($year)) {
            throw ValidationException::withMessages([
                'year' => ['year must be a whole number.'],
            ]);
        }

        return (int) $year;
    }

    // Looks up or creates the department referenced by an import row.
    private function resolveImportDepartment(string $departmentName, string $facultyName): ?Department
    {
        if ($departmentName === '') {
            return null;
        }

        $department = Department::query()->firstOrCreate(
            ['name' => $departmentName],
            ['faculty' => $facultyName !== '' ? $facultyName : null]
        );

        if (($department->faculty === null || trim((string) $department->faculty) === '') && $facultyName !== '') {
            $department->faculty = $facultyName;
            $department->save();
        }

        return $department;
    }

    // Builds the short status message shown after CSV import.
    private function importSummaryMessage(int $createdCount, int $skippedCount, int $errorCount): string
    {
        $parts = [];

        if ($createdCount > 0) {
            $parts[] = "{$createdCount} user(s) imported";
        }

        if ($skippedCount > 0) {
            $parts[] = "{$skippedCount} skipped";
        }

        if ($errorCount > 0) {
            $parts[] = "{$errorCount} row(s) failed validation";
        }

        if ($parts === []) {
            return 'No users were imported.';
        }

        return ucfirst(implode(', ', $parts)).'.';
    }

    // Standardizes user-entered department names before matching or creation.
    private function normalizeDepartmentSelection(string $departmentName): string
    {
        $normalized = trim($departmentName);

        return $normalized === 'None / Not Applicable' ? '' : $normalized;
    }

    // Stores the abbreviation even when a full course label is submitted.
    private function normalizeCourseSelection(string $course): string
    {
        $value = trim($course);
        if ($value === '') {
            return '';
        }

        if (preg_match('/\(([A-Za-z0-9_-]+)\)\s*$/', $value, $matches) === 1) {
            $codeFromLabel = Course::normalizeCode($matches[1] ?? '');
            if ($codeFromLabel !== '' && Course::query()->whereKey($codeFromLabel)->exists()) {
                return $codeFromLabel;
            }
        }

        $code = Course::normalizeCode($value);
        if ($code !== '' && Course::query()->whereKey($code)->exists()) {
            return $code;
        }

        $courseByName = Course::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($value)])
            ->first();

        return $courseByName ? Course::normalizeCode($courseByName->code) : $code;
    }

    // Fills student placement fields from the catalog when the form leaves them blank.
    private function courseProfileDefaults(string $course, string $departmentName, string $facultyName): array
    {
        $courseRecord = Course::query()->find(Course::normalizeCode($course));
        if (! $courseRecord) {
            return [$departmentName, $facultyName];
        }

        return [
            $departmentName !== '' ? $departmentName : trim((string) $courseRecord->name),
            $facultyName !== '' ? $facultyName : trim((string) ($courseRecord->faculty ?: $courseRecord->faculty_code)),
        ];
    }

    // Converts account status labels into the stored status values.
    private function normalizeAccountStatus(string $status): string
    {
        $normalized = Str::lower(trim(str_replace('_', ' ', $status)));

        return match ($normalized) {
            'inactive', 'disabled', 'suspended' => 'inactive',
            'pending', 'pending invite' => 'pending invite',
            default => 'active',
        };
    }

    // Keeps manually approved accounts pending until an admin activates them.
    private function effectiveAccountStatus(string $status, bool $manualApprovalRequired): string
    {
        if ($manualApprovalRequired && $status === 'active') {
            return 'pending invite';
        }

        return $status;
    }

    // Explains to the admin whether the new account is ready or waiting.
    private function buildUserCreateAdminNotice(
        bool $sendWelcomeEmail,
        bool $welcomeEmailSent,
        bool $requirePasswordReset,
        string $effectiveStatus,
        string $identifier,
    ): array {
        $statusNote = match ($effectiveStatus) {
            'pending invite' => "{$identifier} is waiting for manual approval before sign-in is enabled.",
            'inactive' => "{$identifier} is inactive and cannot sign in until the account is reactivated.",
            default => '',
        };

        if ($sendWelcomeEmail && $welcomeEmailSent) {
            $deliveryNote = $requirePasswordReset
                ? 'A welcome email was sent with the password setup link.'
                : 'A welcome email was sent with the account onboarding details.';

            return [trim($deliveryNote.' '.$statusNote), 'success'];
        }

        if ($sendWelcomeEmail && ! $welcomeEmailSent) {
            $recoveryNote = $requirePasswordReset
                ? "The account was created, but the welcome email could not be sent. Ask the user to open Forgot Password with University ID {$identifier} to set a password."
                : "The account was created, but the welcome email could not be sent. Temporary password defaults to University ID {$identifier}.";

            return [trim($recoveryNote.' '.$statusNote), 'warning'];
        }

        $manualNote = $requirePasswordReset
            ? "No welcome email was sent. Ask the user to use Forgot Password with University ID {$identifier} before first sign-in."
            : "No welcome email was sent. Temporary password defaults to University ID {$identifier}.";

        return [trim($manualNote.' '.$statusNote), 'warning'];
    }

    // Explains the result of an admin account edit.
    private function buildUserEditAdminNotice(
        bool $sendAccountEmail,
        bool $accountEmailSent,
        bool $requirePasswordReset,
        string $effectiveStatus,
        string $identifier,
    ): array {
        $statusNote = match ($effectiveStatus) {
            'pending invite' => "{$identifier} remains in pending invite until approval is completed.",
            'inactive' => "{$identifier} is inactive and cannot sign in until reactivated.",
            default => '',
        };

        if ($sendAccountEmail && $accountEmailSent) {
            return [trim('Updated account email sent successfully. '.$statusNote), 'success'];
        }

        if ($sendAccountEmail && ! $accountEmailSent) {
            $fallback = $requirePasswordReset
                ? "The user was updated, but the account email could not be sent. Ask them to use Forgot Password with University ID {$identifier}."
                : "The user was updated, but the account email could not be sent.";

            return [trim($fallback.' '.$statusNote), 'warning'];
        }

        if ($requirePasswordReset) {
            return [trim("Password reset will be required for {$identifier} on the next sign-in. ".$statusNote), 'warning'];
        }

        return [trim($statusNote), 'warning'];
    }

    // Prevents admins from demoting or disabling their own active account.
    private function guardSelfAdministration(?User $actor, User $target, string $nextRole, string $nextStatus): void
    {
        if (! $actor || $actor->id !== $target->id) {
            return;
        }

        if ($nextRole !== 'admin') {
            throw ValidationException::withMessages([
                'role' => ['You cannot remove your own admin access from the account you are currently using.'],
            ]);
        }

        if ($nextStatus !== 'active') {
            throw ValidationException::withMessages([
                'status' => ['You cannot change your own account status away from active in the current session.'],
            ]);
        }
    }

    // Writes the matching profile table after account create or update.
    private function syncUserProfileForRole(
        string $identifier,
        string $role,
        string $firstName,
        string $lastName,
        string $departmentName,
        string $facultyName,
        string $course,
        ?int $year,
        string $salutation,
        string $staffRole,
    ): void {
        if ($role === 'student') {
            Student::updateOrCreate(
                ['reg_number' => $identifier],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'course' => $course,
                    'year' => $year,
                    'department' => $departmentName !== '' ? $departmentName : null,
                    'faculty' => $facultyName !== '' ? $facultyName : null,
                ]
            );

            return;
        }

        if ($role === 'lecturer') {
            Lecturer::updateOrCreate(
                ['lecturer_id' => $identifier],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'salutation' => $salutation !== '' ? $salutation : null,
                    'department' => $departmentName,
                ]
            );

            return;
        }

        Staff::updateOrCreate(
            ['staff_id' => $identifier],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'salutation' => $salutation !== '' ? $salutation : null,
                'role' => $this->staffProfileRoleFor($role, $staffRole),
                'department' => $departmentName !== '' ? $departmentName : null,
            ]
        );
    }

    // Removes old profile rows when a user changes role.
    private function removeStaleProfiles(string $identifier, string $keepRole): void
    {
        if ($keepRole !== 'student') {
            Student::query()->where('reg_number', $identifier)->delete();
        }

        if ($keepRole !== 'lecturer') {
            Lecturer::query()->where('lecturer_id', $identifier)->delete();
        }

        if (! in_array($keepRole, ['staff', 'admin'], true)) {
            Staff::query()->where('staff_id', $identifier)->delete();
        }
    }

    // Replaces lecturer-unit mappings from the admin assignment field.
    private function syncLecturerTeachingAssignments(string $lecturerId, string $rawAssignments): void
    {
        $assignments = $this->parseTeachingAssignments($rawAssignments);
        $unitCodes = $assignments
            ->pluck('unit_code')
            ->unique()
            ->values();

        if ($unitCodes->isNotEmpty()) {
            $existingUnitCodes = Unit::query()
                ->whereIn('unit_code', $unitCodes)
                ->pluck('unit_code')
                ->map(fn ($value) => Str::upper(trim((string) $value)))
                ->unique()
                ->values();
            $missingUnitCodes = $unitCodes
                ->diff($existingUnitCodes)
                ->values();

            if ($missingUnitCodes->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'teaching_assignments' => ['Unknown unit code(s): '.$missingUnitCodes->implode(', ').'.'],
                ]);
            }
        }

        $existingMappings = UnitLecturer::query()
            ->where('lecturer_id', '!=', $lecturerId)
            ->whereIn('unit_code', $unitCodes)
            ->get(['unit_code', 'course', 'lecturer_id']);

        $conflictingAssignments = $assignments
            ->map(function (array $assignment) use ($existingMappings) {
                $conflict = $existingMappings->first(function (UnitLecturer $mapping) use ($assignment) {
                    return Str::upper(trim((string) $mapping->unit_code)) === $assignment['unit_code']
                        && Str::upper(trim((string) $mapping->course)) === $assignment['course'];
                });

                if (! $conflict) {
                    return null;
                }

                return $assignment['unit_code'].' | '.$assignment['course'].' is already assigned to '.$conflict->lecturer_id.'.';
            })
            ->filter()
            ->values();

        if ($conflictingAssignments->isNotEmpty()) {
            throw ValidationException::withMessages([
                'teaching_assignments' => $conflictingAssignments->all(),
            ]);
        }

        UnitLecturer::query()
            ->where('lecturer_id', $lecturerId)
            ->delete();

        if ($assignments->isEmpty()) {
            return;
        }

        $timestamp = now();

        UnitLecturer::query()->insert(
            $assignments
                ->map(fn (array $assignment) => [
                    'unit_code' => $assignment['unit_code'],
                    'lecturer_id' => $lecturerId,
                    'course' => $assignment['course'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all()
        );
    }

    // Parses one assignment per line into unit and optional course filters.
    private function parseTeachingAssignments(string $rawAssignments)
    {
        $lines = preg_split('/\R/', $rawAssignments) ?: [];
        $assignments = collect();

        foreach ($lines as $index => $line) {
            $normalizedLine = trim((string) $line);
            if ($normalizedLine === '') {
                continue;
            }

            $parts = preg_split('/\s*(?:\||,)\s*/', $normalizedLine) ?: [];
            if (count($parts) !== 2) {
                throw ValidationException::withMessages([
                    'teaching_assignments' => ['Each teaching assignment must use the format UNIT_CODE | COURSE. Invalid line '.($index + 1).'.'],
                ]);
            }

            $unitCode = Str::upper(trim((string) ($parts[0] ?? '')));
            $courseCode = $this->normalizeCourseSelection((string) ($parts[1] ?? ''));

            if ($unitCode === '' || $courseCode === '') {
                throw ValidationException::withMessages([
                    'teaching_assignments' => ['Each teaching assignment must include both a unit code and a course. Invalid line '.($index + 1).'.'],
                ]);
            }

            $assignments->push([
                'unit_code' => $unitCode,
                'course' => $courseCode,
            ]);
        }

        return $assignments
            ->unique(fn (array $assignment) => $assignment['unit_code'].'|'.$assignment['course'])
            ->values();
    }

    // Resolves the role stored on the staff profile table.
    private function staffProfileRoleFor(string $role, string $staffRole): string
    {
        if ($role === 'staff') {
            return $staffRole;
        }

        return 'admin';
    }

    // Resolves the foreign key in roles for the selected account role.
    private function resolveUserRoleId(array $roleIds, string $role, string $staffRole): ?int
    {
        if ($role === 'staff' && isset($roleIds[$staffRole])) {
            return $roleIds[$staffRole];
        }

        return $roleIds[$role] ?? null;
    }

    // Produces the role label shown in emails and admin messages.
    private function userRoleLabel(string $role, string $staffRole): string
    {
        if ($role === 'staff') {
            return match ($staffRole) {
                'hod' => 'HOD',
                'exam_officer' => 'Exam Officer',
                default => 'Staff',
            };
        }

        return Str::title($role);
    }

    // Lists related records that make deleting an account unsafe.
    private function userDeletionBlockers(User $user): array
    {
        $identifier = trim((string) $user->identifier);
        $blockers = [];

        if (Dispute::query()->where('student_id', $identifier)->exists()) {
            $blockers[] = 'The account has submitted dispute records.';
        }

        if (Dispute::query()->where('assigned_to', $identifier)->exists()) {
            $blockers[] = 'The account is referenced in dispute assignments.';
        }

        if (DisputeLog::query()->where('performed_by', $identifier)->exists()) {
            $blockers[] = 'The account appears in dispute timeline history.';
        }

        if (Attachment::query()->where('uploaded_by', $identifier)->exists()) {
            $blockers[] = 'The account uploaded dispute evidence.';
        }

        if (ActivityLog::query()->where('user_id', $user->id)->exists()) {
            $blockers[] = 'The account is linked to admin audit activity.';
        }

        return $blockers;
    }

    // Sends the welcome or reset-required email for an admin-managed account.
    private function sendUserAccountEmail(User $user, bool $forcePasswordReset = false): bool
    {
        $staffRole = $user->role === 'staff'
            ? trim((string) Staff::query()->where('staff_id', $user->identifier)->value('role'))
            : '';

        try {
            Mail::to($user->email)->send(
                new AdminUserWelcomeMail(
                    recipientName: trim((string) $user->name),
                    identifier: trim((string) $user->identifier),
                    roleLabel: $this->userRoleLabel(trim((string) $user->role), $staffRole),
                    portalUrl: route('portal.auth'),
                    passwordSetupUrl: route('portal.password.forgot', ['identifier' => $user->identifier]),
                    statusLabel: Str::title(str_replace('_', ' ', $this->normalizeAccountStatus((string) $user->status))),
                    requirePasswordReset: $forcePasswordReset ? true : (bool) $user->require_password_reset,
                    manualApprovalRequired: (bool) $user->manual_approval_required,
                )
            );

            $user->forceFill([
                'welcome_email_sent_at' => now(),
                'require_password_reset' => $forcePasswordReset ? true : $user->require_password_reset,
            ])->save();

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    // Chooses the best display name from profile data before falling back to account name.
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

    // Normalizes status text for directory filtering and display.
    private function normalizeStatus(?string $status): string
    {
        return $this->normalizeAccountStatus((string) $status);
    }
}
