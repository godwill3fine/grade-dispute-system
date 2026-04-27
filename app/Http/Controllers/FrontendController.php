<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Department;
use App\Models\Dispute;
use App\Models\DisputeLog;
use App\Models\Lecturer;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Unit;
use App\Models\UnitLecturer;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function landing(): RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return redirect()->route('portal.auth');
    }

    public function authPage(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.entry');
    }

    public function signupPage(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.signup');
    }

    public function signupPasswordPage(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $identifier = trim((string) $request->query('identifier', ''));
        $role = trim((string) $request->query('role', ''));

        if ($identifier === '' || $role === '') {
            return redirect()
                ->route('portal.signup')
                ->with('auth_error', 'Start sign up by entering your University ID first.');
        }

        return view('auth.signup-password', [
            'identifier' => $identifier,
            'role' => $role,
        ]);
    }

    public function forgotPasswordPage(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        request()->session()->forget('password_reset_flow');

        return view('auth.forgot-password');
    }

    public function forgotPasswordEmailPage(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $flow = $request->session()->get('password_reset_flow', []);
        $identifier = trim((string) ($flow['identifier'] ?? ''));

        if ($identifier === '') {
            return redirect()
                ->route('portal.password.forgot')
                ->with('auth_error', 'Start by entering your University ID first.');
        }

        return view('auth.forgot-password-email', [
            'identifier' => $identifier,
        ]);
    }

    public function forgotPasswordVerifyPage(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $flow = $request->session()->get('password_reset_flow', []);
        $identifier = trim((string) ($flow['identifier'] ?? ''));
        $email = trim((string) ($flow['email'] ?? ''));

        if ($identifier === '' || $email === '') {
            return redirect()
                ->route('portal.password.forgot')
                ->with('auth_error', 'Start the reset process again with your University ID.');
        }

        return view('auth.forgot-password-verify', [
            'identifier' => $identifier,
            'maskedEmail' => $this->maskEmail($email),
        ]);
    }

    public function forgotPasswordResetPage(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $flow = $request->session()->get('password_reset_flow', []);
        $identifier = trim((string) ($flow['identifier'] ?? ''));
        $verifiedAt = trim((string) ($flow['verified_at'] ?? ''));

        if ($identifier === '' || $verifiedAt === '') {
            return redirect()
                ->route('portal.password.forgot')
                ->with('auth_error', 'Verify your reset code before setting a new password.');
        }

        return view('auth.forgot-password-reset', [
            'identifier' => $identifier,
        ]);
    }

    public function appHome(): RedirectResponse
    {
        return $this->redirectByRole(Auth::user()->role);
    }

    public function studentDashboard(): View
    {
        $this->ensureRole('student');

        return view('student.dashboard');
    }

    public function studentSubmitDispute(): View
    {
        $this->ensureRole('student');

        $student = Student::find(Auth::user()?->identifier);

        return view('student.submit-dispute', [
            'studentCourse' => trim((string) ($student?->course ?? '')),
        ]);
    }

    public function studentTickets(): View
    {
        $this->ensureRole('student');

        return view('student.tickets');
    }

    public function studentNotifications(): View
    {
        $this->ensureRole('student');

        return view('student.notifications');
    }

    public function studentProfile(): View
    {
        $this->ensureRole('student');

        return view('student.profile', $this->buildStudentPortalContext());
    }

    public function studentSettings(): View
    {
        $this->ensureRole('student');

        return view('student.account-settings', $this->buildStudentPortalContext());
    }

    public function studentDisputes(): View
    {
        return $this->studentTickets();
    }

    public function studentDisputeDetail(int $id): View
    {
        $this->ensureRole('student');

        return view('student.dispute-detail', [
            'disputeId' => $id,
        ]);
    }

    public function lecturerDashboard(): View
    {
        $this->ensureRole('lecturer', 'staff');

        return view('lecturer.dashboard', $this->buildLecturerPortalContext());
    }

    public function lecturerDisputes(): View
    {
        $this->ensureRole('lecturer', 'staff');

        return view('lecturer.disputes', $this->buildLecturerPortalContext());
    }

    public function lecturerHistory(): View
    {
        $this->ensureRole('lecturer', 'staff');

        return view('lecturer.history', $this->buildLecturerPortalContext());
    }

    public function lecturerDisputeDetail(int $id): View
    {
        $this->ensureRole('lecturer', 'staff');

        return view('lecturer.dispute-detail', [
            'disputeId' => $id,
        ] + $this->buildLecturerPortalContext());
    }

    public function adminDashboard(): View
    {
        $this->ensureRole('admin');

        return view('admin.dashboard');
    }

    public function adminDisputes(): View
    {
        $this->ensureRole('admin');

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $units = Unit::query()
            ->with('department:id,name')
            ->orderBy('unit_code')
            ->get(['unit_code', 'unit_name', 'department_id']);

        return view('admin.disputes', [
            'adminDisputeMeta' => [
                'departments' => $departments
                    ->map(fn (Department $department) => [
                        'id' => $department->id,
                        'name' => $department->name,
                    ])
                    ->values(),
                'units' => $units
                    ->map(fn (Unit $unit) => [
                        'unit_code' => $unit->unit_code,
                        'unit_name' => $unit->unit_name,
                        'department_name' => $unit->department?->name,
                    ])
                    ->values(),
            ],
        ]);
    }

    public function adminCreateTicket(): View
    {
        $this->ensureRole('admin');

        return view('admin.create-ticket', [
            'adminCreateTicketMeta' => $this->buildAdminCreateTicketMeta(),
        ]);
    }

    public function adminManageTicket(int $id): View
    {
        $this->ensureRole('admin');

        $dispute = Dispute::query()
            ->with('unit.department')
            ->findOrFail($id);

        return view('admin.manage-ticket', [
            'adminManageTicket' => $this->buildAdminManageTicketContext($dispute),
        ]);
    }

    public function adminUsers(): View
    {
        $this->ensureRole('admin');

        return view('admin.users');
    }

    public function adminUserCreate(): View
    {
        $this->ensureRole('admin');

        return view('admin.user-create', [
            'adminUserFormMeta' => $this->adminUserFormMeta(),
        ]);
    }

    public function adminUserEdit(string $identifier): View
    {
        $this->ensureRole('admin');

        $user = User::query()
            ->with('department:id,name,faculty')
            ->where('identifier', $identifier)
            ->firstOrFail();

        $context = $this->buildAdminUserEditContext($user);

        return view('admin.user-edit', [
            'adminUserFormMeta' => $this->adminUserFormMeta($context['department'] ?? null),
            'adminUserEdit' => $context,
        ]);
    }

    public function adminReports(): View
    {
        $this->ensureRole('admin');

        return view('admin.reports');
    }

    public function adminEscalations(): View
    {
        $this->ensureRole('admin');

        return view('admin.escalations');
    }

    public function adminSettings(): View
    {
        $this->ensureRole('admin');

        return view('admin.settings');
    }

    public function adminActivity(): View
    {
        $this->ensureRole('admin');

        return view('admin.activity');
    }

    private function ensureRole(string ...$roles): void
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403);
        }
    }

    private function buildStudentPortalContext(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $user?->loadMissing('department');

        $student = Student::query()->find($user?->identifier);
        $course = trim((string) ($student?->course ?? ''));
        $departmentName = trim((string) (data_get($student, 'department') ?? $user?->department?->name ?? ''));
        $facultyName = trim((string) (data_get($student, 'faculty') ?? $user?->department?->faculty ?? ''));
        $yearLabel = filled($student?->year) ? 'Year '.$student->year : 'Not available';
        $profileMeta = array_filter([
            $course !== '' ? $course : null,
            filled($student?->year) ? $yearLabel : null,
        ]);

        return [
            'studentProfile' => [
                'name' => $user?->name ?? $student?->full_name ?? 'Student',
                'identifier' => $user?->identifier ?? $student?->reg_number ?? 'Not available',
                'email' => trim((string) ($user?->email ?? '')) !== '' ? $user->email : 'No email on file',
                'course' => $course !== '' ? $course : 'Not assigned',
                'year_label' => $yearLabel,
                'profile_meta' => !empty($profileMeta) ? implode(', ', $profileMeta) : 'Student portal account',
                'department_name' => $departmentName !== '' ? $departmentName : 'Not assigned',
                'faculty_name' => $facultyName !== '' ? $facultyName : 'Not assigned',
                'status_label' => ucfirst((string) ($user?->status ?? 'active')),
                'email_verification_label' => $user?->email_verified_at ? 'Email Verified' : 'Email Pending',
                'time_zone_label' => 'East Africa Time (Nairobi)',
                'last_sign_in' => $this->formatPortalTimestamp($user?->last_login, 'No sign-in history yet'),
                'last_sync' => $this->formatPortalTimestamp($student?->updated_at ?? $user?->updated_at, 'No sync history yet'),
            ],
        ];
    }

    private function buildLecturerPortalContext(): array
    {
        $user = Auth::user();
        $staffRole = '';

        if ($user?->role === 'staff') {
            $staffRole = trim((string) Staff::query()
                ->where('staff_id', $user->identifier)
                ->value('role'));
        }

        return [
            'portalUserMeta' => [
                'uses_lecturer_workspace' => in_array($user?->role, ['lecturer', 'staff'], true),
                'is_staff_reviewer' => $user?->role === 'staff',
                'staff_role' => $staffRole,
                'staff_role_label' => $staffRole !== ''
                    ? Str::title(str_replace('_', ' ', $staffRole))
                    : 'Staff Reviewer',
            ],
        ];
    }

    private function buildAdminManageTicketContext(Dispute $dispute): array
    {
        /** @var User|null $currentAdmin */
        $currentAdmin = Auth::user();
        $currentAdmin?->loadMissing('department');

        $logs = DisputeLog::query()
            ->where('dispute_id', $dispute->id)
            ->orderBy('created_at')
            ->get();

        $attachments = Attachment::query()
            ->where('dispute_id', $dispute->id)
            ->orderBy('created_at')
            ->get();

        $studentId = trim((string) ($dispute->student_id ?? ''));
        $assignedTo = trim((string) ($dispute->assigned_to ?? ''));
        $unitCode = trim((string) ($dispute->unit_code ?? ''));
        $mappedUnitLecturers = UnitLecturer::query()
            ->where('unit_code', $unitCode)
            ->orderBy('course')
            ->orderBy('id')
            ->get(['lecturer_id', 'course']);

        $mappedLecturerIds = $mappedUnitLecturers
            ->pluck('lecturer_id')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        $actorIdentifiers = collect([$studentId, $assignedTo, $currentAdmin?->identifier])
            ->merge($logs->pluck('performed_by'))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        $users = User::query()
            ->with('department:id,name,faculty')
            ->whereIn('identifier', $actorIdentifiers)
            ->get()
            ->keyBy('identifier');

        $students = Student::query()
            ->whereIn('reg_number', $actorIdentifiers)
            ->get()
            ->keyBy('reg_number');

        $staffReviewers = Staff::query()
            ->whereIn('role', ['hod', 'exam_officer'])
            ->orderByRaw("CASE role WHEN 'hod' THEN 0 ELSE 1 END")
            ->orderBy('department')
            ->orderBy('staff_id')
            ->get();

        $staff = Staff::query()
            ->whereIn('staff_id', $actorIdentifiers->merge($staffReviewers->pluck('staff_id'))->unique()->values())
            ->get()
            ->keyBy('staff_id');

        $lecturers = Lecturer::query()
            ->orderBy('department')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('lecturer_id')
            ->get()
            ->keyBy('lecturer_id');
        $studentCourseCode = trim((string) data_get($students->get($studentId), 'course', ''));
        $courseMatchedLecturerIds = $mappedUnitLecturers
            ->filter(fn (UnitLecturer $mapping) => $this->normalizeCourseCode($mapping->course) === $this->normalizeCourseCode($studentCourseCode))
            ->pluck('lecturer_id')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        $studentInfo = $this->resolveDisputeActorInfo($studentId, $users, $students, $lecturers, $staff);
        $assignedInfo = $this->resolveDisputeActorInfo($assignedTo, $users, $students, $lecturers, $staff);
        $courseLecturerInfo = $this->resolveDisputeActorInfo(
            trim((string) ($courseMatchedLecturerIds->first() ?? $mappedLecturerIds->first() ?? '')),
            $users,
            $students,
            $lecturers,
            $staff,
        );
        $currentAdminInfo = $this->resolveDisputeActorInfo(
            trim((string) ($currentAdmin?->identifier ?? '')),
            $users,
            $students,
            $lecturers,
            $staff,
        );

        $reviewerInfo = ($assignedInfo['role'] ?? '') === 'lecturer' ? $assignedInfo : $courseLecturerInfo;
        if (($reviewerInfo['identifier'] ?? '') === '') {
            $reviewerInfo = $assignedInfo;
        }

        $caseOwnerInfo = in_array($assignedInfo['role'] ?? '', ['admin', 'hod', 'exam_officer'], true)
            ? $assignedInfo
            : $currentAdminInfo;

        $statusKey = Str::lower(trim((string) ($dispute->status ?? 'pending')));
        $statusLabel = $this->formatDisputeStatusLabel($statusKey);
        $priorityLabel = $this->formatDisputePriorityLabel($dispute->priority);
        $issueType = $this->disputeIssueTypeLabel($dispute);

        $unitName = trim((string) ($dispute->unit?->unit_name ?? ''));
        $courseLabel = $unitCode !== '' && $unitName !== ''
            ? $unitCode.' - '.$unitName
            : ($unitCode !== ''
                ? $unitCode
                : (trim((string) data_get($students->get($studentId), 'course', '')) !== ''
                    ? trim((string) data_get($students->get($studentId), 'course', ''))
                    : 'General Appeal'));

        $departmentName = trim((string) (
            data_get($students->get($studentId), 'department')
            ?: $dispute->unit?->department?->name
            ?: data_get($users->get($studentId), 'department.name')
            ?: ($reviewerInfo['department'] ?? '')
        ));
        $facultyName = trim((string) (
            data_get($students->get($studentId), 'faculty')
            ?: $dispute->unit?->department?->faculty
            ?: data_get($users->get($studentId), 'department.faculty')
            ?: ($studentInfo['faculty'] ?? '')
        ));

        $lastActivityAt = $logs->last()?->created_at ?? $dispute->updated_at ?? $dispute->created_at;
        $staleDays = $lastActivityAt
            ? round(
                Carbon::instance($lastActivityAt)
                    ->setTimezone('Africa/Nairobi')
                    ->diffInSeconds(Carbon::now('Africa/Nairobi')) / 86400,
                1,
            )
            : 0.0;
        $overdueDays = max(round($staleDays - 2, 1), 0.0);
        $isResolved = in_array($statusKey, ['resolved', 'closed'], true);
        $isOverdue = ! $isResolved && $overdueDays > 0;
        $overdueUnit = abs($overdueDays - 1.0) < 0.05 ? 'Day' : 'Days';
        $overdueLabel = number_format($overdueDays, 1).' '.$overdueUnit;

        $slaLabel = $isResolved
            ? 'Resolved'
            : ($isOverdue ? 'Overdue by '.$overdueLabel : 'Within review window');

        $timeOpenDays = $dispute->created_at
            ? round(
                Carbon::instance($dispute->created_at)
                    ->setTimezone('Africa/Nairobi')
                    ->diffInSeconds(Carbon::now('Africa/Nairobi')) / 86400,
                1,
            )
            : 0.0;
        $timeOpenUnit = abs($timeOpenDays - 1.0) < 0.05 ? 'Day' : 'Days';
        $timeOpenLabel = $dispute->created_at ? number_format($timeOpenDays, 1).' '.$timeOpenUnit : 'Not available';
        $timeOpenMeta = $isOverdue
            ? 'SLA exceeded by '.$overdueLabel
            : 'Submitted '.$this->formatPortalDate($dispute->created_at);

        $storedStage = trim((string) ($dispute->assignment_name ?? ''));
        $currentStage = match (true) {
            $isResolved => 'Resolved',
            $storedStage === 'Admin Intake' => 'Admin Intake',
            $storedStage === 'Academic Appeals Panel' => 'Academic Appeals Panel',
            ($assignedInfo['role'] ?? '') === 'lecturer' => 'Lecturer Review',
            ($assignedInfo['role'] ?? '') === 'exam_officer' => 'Exam Officer Review',
            ($assignedInfo['role'] ?? '') === 'hod' => 'Head of Department Review',
            ($assignedInfo['role'] ?? '') === 'admin' => 'Admin Review',
            $storedStage === 'Dean Review' => 'Admin Review',
            $storedStage === 'Department Board' => 'Department Review',
            $storedStage !== '' => $storedStage,
            in_array($statusKey, ['escalated', 'escalated_admin'], true) => 'Admin Review',
            default => 'Admin Review',
        };

        $reviewStageMeta = match (true) {
            $isResolved => 'Closed on '.$this->formatPortalDate($dispute->resolved_at ?? $lastActivityAt),
            $currentStage === 'Admin Intake' => 'Waiting in the admin intake queue',
            $currentStage === 'Academic Appeals Panel' => 'Prepared for appeals-panel handling',
            ($assignedInfo['role'] ?? '') === 'lecturer' => 'Awaiting lecturer follow-up',
            ($assignedInfo['role'] ?? '') === 'exam_officer' => 'Awaiting exam-office follow-up',
            ($assignedInfo['role'] ?? '') === 'hod' => 'Awaiting head-of-department follow-up',
            in_array($statusKey, ['escalated', 'escalated_admin'], true) => 'Routed for higher-level decision on '.$this->formatPortalDate($lastActivityAt),
            default => 'Updated on '.$this->formatPortalDate($lastActivityAt),
        };

        $statusOptions = collect(['pending', 'under_review', 'escalated', 'resolved'])
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => $this->formatDisputeStatusLabel($value),
            ])
            ->values()
            ->all();

        $selectedLecturerId = ($assignedInfo['role'] ?? '') === 'lecturer'
            ? ($assignedInfo['identifier'] ?? '')
            : ($reviewerInfo['identifier'] ?? '');

        $preferredExamOfficer = $this->preferredStaffRouteReviewer($staffReviewers, 'exam_officer', $departmentName);
        $preferredHod = $this->preferredStaffRouteReviewer($staffReviewers, 'hod', $departmentName);
        $selectedRouting = $this->adminRouteDestinationValue($dispute);

        $routeOptions = collect([
            [
                'value' => 'lecturer',
                'label' => 'Lecturer Review',
            ],
            [
                'value' => 'exam_officer',
                'label' => $preferredExamOfficer
                    ? 'Exam Officer - '.$preferredExamOfficer->formal_name
                    : 'Exam Officer',
            ],
            [
                'value' => 'hod',
                'label' => $preferredHod
                    ? 'Head of Department - '.$preferredHod->formal_name
                    : 'Head of Department',
            ],
            [
                'value' => 'admin',
                'label' => 'Admin Review',
            ],
            [
                'value' => 'appeals_panel',
                'label' => 'Academic Appeals Panel',
            ],
        ])->values()->all();

        $lecturerOptions = $lecturers
            ->map(function (Lecturer $lecturer) use (
                $departmentName,
                $courseMatchedLecturerIds,
                $lecturers,
                $mappedLecturerIds,
                $selectedLecturerId,
                $staff,
                $students,
                $users
            ) {
                $info = $this->resolveDisputeActorInfo(
                    trim((string) $lecturer->lecturer_id),
                    $users,
                    $students,
                    $lecturers,
                    $staff,
                );

                $flags = [];
                if (($info['identifier'] ?? '') === $selectedLecturerId) {
                    $flags[] = 'Current';
                }

                if ($courseMatchedLecturerIds->contains((string) $lecturer->lecturer_id)) {
                    $flags[] = 'Course Match';
                }

                if ($mappedLecturerIds->contains((string) $lecturer->lecturer_id)) {
                    $flags[] = 'Mapped';
                }

                $lecturerDepartment = trim((string) ($lecturer->department ?? ''));
                if (
                    $departmentName !== ''
                    && $lecturerDepartment !== ''
                    && Str::lower($lecturerDepartment) === Str::lower($departmentName)
                ) {
                    $flags[] = 'Same Department';
                }

                $baseLabel = trim((string) ($info['name'] ?? '')) !== ''
                    ? trim((string) $info['name'])
                    : trim((string) $lecturer->lecturer_id);

                if ($lecturerDepartment !== '') {
                    $baseLabel .= ' - '.$lecturerDepartment;
                }

                if (! empty($flags)) {
                    $baseLabel .= ' ('.implode(', ', $flags).')';
                }

                return [
                    'identifier' => trim((string) $lecturer->lecturer_id),
                    'label' => $baseLabel,
                    'sort_bucket' => match (true) {
                        trim((string) $lecturer->lecturer_id) === $selectedLecturerId => 0,
                        $courseMatchedLecturerIds->contains((string) $lecturer->lecturer_id) => 1,
                        $mappedLecturerIds->contains((string) $lecturer->lecturer_id) => 2,
                        $departmentName !== ''
                            && $lecturerDepartment !== ''
                            && Str::lower($lecturerDepartment) === Str::lower($departmentName) => 3,
                        default => 4,
                    },
                ];
            })
            ->filter(fn (array $option) => $option['identifier'] !== '')
            ->sortBy([
                ['sort_bucket', 'asc'],
                ['label', 'asc'],
            ])
            ->values();

        $timeline = $logs
            ->map(function (DisputeLog $log) use ($dispute, $users, $students, $lecturers, $staff) {
                $action = trim((string) ($log->action ?? 'Activity'));
                if (Str::lower($action) === 'dispute submitted') {
                    $action = 'Ticket Submitted';
                }

                $authorInfo = $this->resolveDisputeActorInfo(
                    trim((string) ($log->performed_by ?? '')),
                    $users,
                    $students,
                    $lecturers,
                    $staff,
                );

                $authorRole = trim((string) ($authorInfo['role_label'] ?? ''));
                $authorName = trim((string) ($authorInfo['name'] ?? ''));
                $author = $authorName !== ''
                    ? (($authorRole !== '' ? Str::title(Str::lower($authorRole)) : 'User').': '.$authorName)
                    : trim((string) ($log->performed_by ?? 'System'));

                $comment = trim((string) ($log->comment ?? ''));
                if ($comment === '' || Str::lower($comment) === 'dispute created by student') {
                    $comment = trim((string) ($dispute->description ?: $dispute->title ?: 'No additional details were provided.'));
                }

                return [
                    'action' => $action,
                    'created_at' => $this->formatPortalTimestamp($log->created_at),
                    'author' => $author !== '' ? $author : 'System',
                    'comment' => $comment !== '' ? $comment : 'No additional details were recorded.',
                ];
            })
            ->values();

        if ($timeline->isEmpty()) {
            $timeline = collect([[
                'action' => 'Ticket Submitted',
                'created_at' => $this->formatPortalTimestamp($dispute->created_at),
                'author' => 'Student: '.(($studentInfo['name'] ?? '') !== '' ? $studentInfo['name'] : ($studentId !== '' ? $studentId : 'Student')),
                'comment' => trim((string) ($dispute->description ?: $dispute->title ?: 'No additional details were recorded.')),
            ]]);
        }

        $lastTimelineEntry = $timeline->last();
        $lastUpdateLabel = $lastTimelineEntry
            ? $lastTimelineEntry['action'].' on '.$lastTimelineEntry['created_at']
            : 'No recorded updates yet';

        $people = [
            $this->buildTicketPersonCard(
                $studentInfo,
                'Student ID: '.($studentId !== '' ? $studentId : 'Not available'),
                'Submitted on '.$this->formatPortalDate($dispute->created_at),
                'student',
            ),
        ];

        if (
            ($reviewerInfo['identifier'] ?? '') !== ''
            && ($reviewerInfo['identifier'] ?? '') !== ($studentInfo['identifier'] ?? '')
        ) {
            $people[] = $this->buildTicketPersonCard(
                $reviewerInfo,
                ($reviewerInfo['department'] ?? '') !== '' ? $reviewerInfo['department'] : 'Course reviewer',
                ($assignedInfo['role'] ?? '') === 'lecturer' ? 'Current reviewer' : 'Linked to this course',
                $reviewerInfo['role'] ?? 'lecturer',
            );
        }

        if (
            ($caseOwnerInfo['identifier'] ?? '') !== ''
            && ! collect($people)->contains(fn (array $person): bool => $person['identifier'] === $caseOwnerInfo['identifier'])
        ) {
            $ownerSecondary = (
                in_array($assignedInfo['role'] ?? '', ['admin', 'hod', 'exam_officer'], true)
                && ($assignedInfo['identifier'] ?? '') === ($caseOwnerInfo['identifier'] ?? '')
            )
                ? 'Assigned on '.$this->formatPortalDate($dispute->updated_at ?? $dispute->created_at)
                : 'Viewing in admin portal';

            $people[] = $this->buildTicketPersonCard(
                $caseOwnerInfo,
                'Admin case owner',
                $ownerSecondary,
                'admin',
            );
        }

        $descriptionText = trim((string) ($dispute->description ?? ''));
        $summaryLead = $issueType;
        if ($courseLabel !== '') {
            $summaryLead .= ' for '.$courseLabel;
        }

        $summaryCopy = $summaryLead !== '' ? $summaryLead : 'Ticket review';
        if ($descriptionText !== '') {
            $summaryCopy .= '. '.Str::limit($descriptionText, 180);
        }

        $escalationTrigger = trim((string) ($dispute->escalation_reason ?? ''));
        if ($escalationTrigger === '') {
            $escalationTrigger = match (true) {
                in_array($statusKey, ['escalated', 'escalated_admin'], true) => 'Routed for administrative review after the initial response window.',
                ($assignedInfo['role'] ?? '') === 'lecturer' => 'Submitted directly to the assigned lecturer for review.',
                default => 'Awaiting administrative review in the current workflow stage.',
            };
        }

        $internalNotes = trim((string) ($dispute->escalation_reason ?? ''));
        if ($internalNotes === '') {
            $internalNotes = $isOverdue
                ? 'This ticket has exceeded the normal review window. Capture routing decisions, policy references, and follow-up reminders here.'
                : 'No private admin notes have been saved yet. Capture routing decisions, policy references, and follow-up reminders here.';
        }

        if ($attachments->isNotEmpty()) {
            $internalNotes .= PHP_EOL.PHP_EOL.'Attachments on file: '.$attachments->count().'.';
        }

        $studentCourseLabel = $studentCourseCode !== '' ? $studentCourseCode : 'Not assigned';
        $studentYearValue = data_get($students->get($studentId), 'year');
        $studentYearLabel = filled($studentYearValue) ? 'Year '.$studentYearValue : 'Not available';
        $unitLabel = $unitCode !== '' && $unitName !== ''
            ? $unitCode.' - '.$unitName
            : ($unitCode !== '' ? $unitCode : 'General Appeal');
        $assignedReviewerLabel = ($reviewerInfo['name'] ?? '') !== ''
            ? $reviewerInfo['name'].(($reviewerInfo['identifier'] ?? '') !== '' ? ' ('.$reviewerInfo['identifier'].')' : '')
            : 'Not assigned';
        $currentOwnerLabel = ($assignedInfo['name'] ?? '') !== ''
            ? $assignedInfo['name'].(($assignedInfo['identifier'] ?? '') !== '' ? ' ('.$assignedInfo['identifier'].')' : '')
            : (($caseOwnerInfo['name'] ?? '') !== ''
                ? $caseOwnerInfo['name'].(($caseOwnerInfo['identifier'] ?? '') !== '' ? ' ('.$caseOwnerInfo['identifier'].')' : '')
                : 'Not assigned');
        $routingBasisLabel = match (true) {
            Str::lower(trim((string) ($dispute->type ?? ''))) !== 'unit' => 'Manual routing for a general academic dispute.',
            $unitCode === '' => 'Unit dispute without a unit code on file.',
            $studentCourseCode === '' => 'Unit dispute without a student course on file.',
            $courseMatchedLecturerIds->isNotEmpty() => 'Matched by unit '.$unitCode.' and student course '.$studentCourseCode.'.',
            $mappedLecturerIds->isNotEmpty() => 'Unit '.$unitCode.' has lecturer mappings, but none match student course '.$studentCourseCode.'.',
            default => 'No lecturer mapping is currently configured for unit '.$unitCode.'.',
        };
        $routingNarrative = match (true) {
            Str::lower(trim((string) ($dispute->type ?? ''))) !== 'unit' => 'This case is being handled as a general academic dispute and can be routed manually by the administration team.',
            $unitCode === '' => 'This case was submitted as a unit dispute, but the disputed unit code is missing from the ticket record.',
            $studentCourseCode === '' => 'This case cannot show course-aware lecturer matching because the student course is not recorded on the ticket profile.',
            $courseMatchedLecturerIds->isNotEmpty() => 'The system matched '.$unitCode.' for course '.$studentCourseCode.' and identified '.$assignedReviewerLabel.' as the lecturer linked to that teaching assignment.',
            $mappedLecturerIds->isNotEmpty() => 'The disputed unit has lecturer mappings on file, but none of them match the student course '.$studentCourseCode.', so the case requires manual review.',
            default => 'No lecturer mapping exists for this unit, so the case currently depends on manual routing by the administration team.',
        };
        $caseOverviewRows = [
            ['label' => 'Ticket Number', 'value' => trim((string) ($dispute->ticket_number ?? 'DSP-'.$dispute->id))],
            ['label' => 'Case Type', 'value' => $issueType],
            ['label' => 'Current Status', 'value' => $statusLabel],
            ['label' => 'Priority', 'value' => $priorityLabel],
            ['label' => 'Review Stage', 'value' => $currentStage],
            ['label' => 'Submitted On', 'value' => $this->formatPortalTimestamp($dispute->created_at)],
            ['label' => 'Time Open', 'value' => $timeOpenLabel],
            ['label' => 'Resolution SLA', 'value' => $slaLabel],
        ];
        $studentProfileRows = [
            ['label' => 'Student Name', 'value' => ($studentInfo['name'] ?? '') !== '' ? $studentInfo['name'] : 'Student'],
            ['label' => 'Registration Number', 'value' => $studentId !== '' ? $studentId : 'Not available'],
            ['label' => 'Course', 'value' => $studentCourseLabel],
            ['label' => 'Year of Study', 'value' => $studentYearLabel],
            ['label' => 'Department', 'value' => $departmentName !== '' ? $departmentName : 'Not assigned'],
            ['label' => 'Faculty', 'value' => $facultyName !== '' ? $facultyName : 'Not assigned'],
        ];
        $academicContextRows = [
            ['label' => 'Disputed Unit', 'value' => $unitLabel],
            ['label' => 'Assessment Workflow', 'value' => match (Str::lower(trim((string) ($dispute->type ?? '')))) {
                'unit' => 'Unit Assessment Review',
                'general' => 'General Academic Appeal',
                default => 'Grade Review Workflow',
            }],
            ['label' => 'Assigned Lecturer', 'value' => $assignedReviewerLabel],
            ['label' => 'Current Case Owner', 'value' => $currentOwnerLabel],
            ['label' => 'Escalation Trigger', 'value' => $escalationTrigger],
            ['label' => 'Last Activity', 'value' => $lastUpdateLabel],
        ];
        $routingAuditRows = [
            ['label' => 'Routing Basis', 'value' => $routingBasisLabel],
            ['label' => 'Current Assignment Stage', 'value' => $currentStage],
            ['label' => 'Assigned Role', 'value' => ($assignedInfo['role_label'] ?? '') !== '' ? $assignedInfo['role_label'] : 'Not assigned'],
            ['label' => 'Escalation Level', 'value' => (int) ($dispute->escalation_level ?? 0) > 0 ? 'Level '.(int) $dispute->escalation_level : 'Base workflow'],
            ['label' => 'Last Updated', 'value' => $this->formatPortalTimestamp($dispute->updated_at ?? $dispute->created_at)],
            ['label' => 'Prepared By', 'value' => ($currentAdminInfo['name'] ?? '') !== '' ? $currentAdminInfo['name'] : (($currentAdmin?->name ?? '') !== '' ? $currentAdmin->name : 'Administration')],
        ];
        $printPeople = collect($people)
            ->map(fn (array $person): array => [
                'name' => trim((string) ($person['name'] ?? 'Unknown User')),
                'role_label' => trim((string) ($person['role_label'] ?? 'USER')),
                'meta' => trim((string) ($person['meta'] ?? '')),
                'secondary_meta' => trim((string) ($person['secondary_meta'] ?? '')),
            ])
            ->values()
            ->all();
        $printAssignmentSnapshot = $mappedUnitLecturers
            ->map(function (UnitLecturer $mapping) use ($courseMatchedLecturerIds, $lecturers, $staff, $studentCourseCode, $students, $users): array {
                $lecturerInfo = $this->resolveDisputeActorInfo(
                    trim((string) ($mapping->lecturer_id ?? '')),
                    $users,
                    $students,
                    $lecturers,
                    $staff,
                );

                $courseCode = $this->normalizeCourseCode($mapping->course);

                return [
                    'course' => $courseCode !== '' ? $courseCode : 'Not assigned',
                    'lecturer_name' => ($lecturerInfo['name'] ?? '') !== '' ? $lecturerInfo['name'] : trim((string) ($mapping->lecturer_id ?? 'Unknown lecturer')),
                    'lecturer_id' => trim((string) ($mapping->lecturer_id ?? '')),
                    'is_student_course' => $courseCode !== '' && $courseCode === $this->normalizeCourseCode($studentCourseCode),
                    'is_course_match' => $courseMatchedLecturerIds->contains(trim((string) ($mapping->lecturer_id ?? ''))),
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $dispute->id,
            'ticket_number' => trim((string) ($dispute->ticket_number ?? 'DSP-'.$dispute->id)),
            'student_id' => $studentId !== '' ? $studentId : 'Not available',
            'page_title' => trim((string) ($dispute->ticket_number ?? 'Ticket')).' Case Review',
            'page_summary' => $summaryCopy,
            'status_label' => $statusLabel,
            'status_meta' => match (true) {
                $isResolved => 'Closed and preserved for audit history',
                $currentStage === 'Academic Appeals Panel' => 'Awaiting appeals-panel handling',
                $currentStage === 'Admin Intake' => 'Waiting in the intake queue',
                $currentStage === 'Exam Officer Review' => 'Awaiting exam-office review',
                $currentStage === 'Head of Department Review' => 'Awaiting HOD review',
                in_array($statusKey, ['escalated', 'escalated_admin'], true) => 'Awaiting admin follow-up',
                $statusKey === 'under_review' => 'Currently under active review',
                $statusKey === 'pending' => 'Awaiting first response',
                default => 'Needs administrative attention',
            },
            'priority_label' => $priorityLabel,
            'sla_label' => $slaLabel,
            'is_overdue' => $isOverdue,
            'time_open_label' => $timeOpenLabel,
            'time_open_meta' => $timeOpenMeta,
            'review_stage' => $currentStage,
            'review_stage_meta' => $reviewStageMeta,
            'issue_type' => $issueType,
            'submitted_at' => $this->formatPortalTimestamp($dispute->created_at),
            'escalation_trigger' => $escalationTrigger,
            'course_label' => $courseLabel,
            'student_course_label' => $studentCourseLabel,
            'unit_label' => $unitLabel,
            'department_name' => $departmentName !== '' ? $departmentName : 'Not assigned',
            'faculty_name' => $facultyName !== '' ? $facultyName : 'Not assigned',
            'assessment_label' => match (Str::lower(trim((string) ($dispute->type ?? '')))) {
                'unit' => 'Unit Assessment Review',
                'general' => 'General Academic Appeal',
                default => 'Grade Review Workflow',
            },
            'last_update_label' => $lastUpdateLabel,
            'selected_status' => $statusKey !== '' ? $statusKey : 'pending',
            'selected_routing' => $selectedRouting,
            'selected_lecturer' => $selectedLecturerId,
            'reviewer_name' => ($reviewerInfo['name'] ?? '') !== '' ? $reviewerInfo['name'] : 'Not assigned',
            'current_owner_name' => $currentOwnerLabel,
            'student_name' => ($studentInfo['name'] ?? '') !== '' ? $studentInfo['name'] : 'Student',
            'attachment_count' => $attachments->count(),
            'activity_count' => $timeline->count(),
            'header_badges' => [
                ['label' => $statusLabel, 'class' => 'badge-dark-solid'],
                ['label' => $priorityLabel.' Priority', 'class' => 'badge-outline-gray'],
                ['label' => $slaLabel, 'class' => $isOverdue ? 'badge-outline-gray badge-outline-alert' : 'badge-outline-gray'],
            ],
            'status_options' => $statusOptions,
            'route_options' => $routeOptions,
            'lecturer_options' => $lecturerOptions
                ->map(fn (array $option) => [
                    'identifier' => $option['identifier'],
                    'label' => $option['label'],
                ])
                ->values()
                ->all(),
            'lecturer_help_text' => 'Any lecturer can be selected here. Reviewers mapped to this student course are listed first for convenience.',
            'routing_help_text' => 'This chooses the next owner of the ticket. Lecturer routes use the selector above, while HOD and Exam Officer routes send the ticket directly to those staff queues.',
            'internal_notes' => $internalNotes,
            'people' => array_slice($people, 0, 3),
            'timeline' => $timeline->all(),
            'print_case_summary' => $summaryCopy,
            'print_case_description' => $descriptionText !== '' ? $descriptionText : 'No detailed dispute statement was provided on the ticket.',
            'print_case_overview_rows' => $caseOverviewRows,
            'print_student_profile_rows' => $studentProfileRows,
            'print_academic_context_rows' => $academicContextRows,
            'print_routing_audit_rows' => $routingAuditRows,
            'print_routing_narrative' => $routingNarrative,
            'print_people' => $printPeople,
            'print_assignment_snapshot' => $printAssignmentSnapshot,
            'print_generated_by_name' => ($currentAdminInfo['name'] ?? '') !== '' ? $currentAdminInfo['name'] : (($currentAdmin?->name ?? '') !== '' ? $currentAdmin->name : 'Administration'),
            'print_generated_by_role' => ($currentAdminInfo['role_label'] ?? '') !== '' ? $currentAdminInfo['role_label'] : 'ADMIN',
            'print_generated_by_department' => ($currentAdminInfo['department'] ?? '') !== '' ? $currentAdminInfo['department'] : 'Administration',
            'print_certification_statement' => 'I certify that this case file reflects the dispute record, routing state, and audit notes available in the Grade Dispute System at the time of printing.',
            'attachments' => $attachments
                ->map(function (Attachment $attachment) use ($users, $students, $lecturers, $staff) {
                    $uploader = $this->resolveDisputeActorInfo(
                        trim((string) ($attachment->uploaded_by ?? '')),
                        $users,
                        $students,
                        $lecturers,
                        $staff,
                    );

                    return [
                        'id' => $attachment->id,
                        'label' => 'Attachment #'.$attachment->id,
                        'uploaded_by' => ($uploader['name'] ?? '') !== '' ? $uploader['name'] : trim((string) ($attachment->uploaded_by ?? 'Unknown uploader')),
                        'uploaded_at' => $this->formatPortalTimestamp($attachment->created_at),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function preferredStaffRouteReviewer($staffReviewers, string $role, string $departmentName): ?Staff
    {
        $matches = $staffReviewers
            ->filter(fn (Staff $staffMember) => trim((string) ($staffMember->role ?? '')) === $role)
            ->values();

        if ($matches->isEmpty()) {
            return null;
        }

        if ($departmentName !== '') {
            $departmentMatch = $matches->first(function (Staff $staffMember) use ($departmentName): bool {
                return Str::lower(trim((string) ($staffMember->department ?? ''))) === Str::lower($departmentName);
            });

            if ($departmentMatch instanceof Staff) {
                return $departmentMatch;
            }
        }

        $firstMatch = $matches->first();

        return $firstMatch instanceof Staff ? $firstMatch : null;
    }

    private function adminRouteDestinationValue(Dispute $dispute): string
    {
        $assignedRole = Str::lower(trim((string) ($dispute->assigned_role ?? '')));
        $storedStage = trim((string) ($dispute->assignment_name ?? ''));

        return match (true) {
            $storedStage === 'Academic Appeals Panel' => 'appeals_panel',
            $assignedRole === 'lecturer' => 'lecturer',
            $assignedRole === 'exam_officer' => 'exam_officer',
            $assignedRole === 'hod' => 'hod',
            $assignedRole === 'admin' => 'admin',
            $storedStage === 'Lecturer Review' => 'lecturer',
            $storedStage === 'Head of Department Review' => 'hod',
            $storedStage === 'Exam Officer Review' => 'exam_officer',
            $storedStage === 'Admin Review' || $storedStage === 'Dean Review' || $storedStage === 'Admin Intake' => 'admin',
            default => 'admin',
        };
    }

    private function buildAdminCreateTicketMeta(): array
    {
        $departments = Department::query()
            ->orderBy('name')
            ->get(['name', 'faculty']);

        return [
            'departments' => $departments
                ->map(fn (Department $department) => [
                    'name' => $department->name,
                    'faculty' => trim((string) ($department->faculty ?? '')),
                ])
                ->values()
                ->all(),
            'ticket_types' => [
                ['value' => 'unit', 'label' => 'Unit Dispute'],
                ['value' => 'general', 'label' => 'General Dispute'],
            ],
            'issue_types' => [
                'Calculation Error',
                'Missing Grade',
                'Late Penalty',
                'Rubric Question',
                'General Review Request',
            ],
            'priority_options' => ['low', 'medium', 'high'],
            'general_route_options' => [
                ['value' => 'exam_officer', 'label' => 'Exam Officer'],
                ['value' => 'hod', 'label' => 'Head of Department'],
                ['value' => 'admin', 'label' => 'Admin Review'],
            ],
            'academic_terms' => $this->buildAcademicTerms(),
        ];
    }

    private function buildAcademicTerms(): array
    {
        $year = (int) now('Africa/Nairobi')->format('Y');

        return [
            "{$year} Semester 1",
            "{$year} Semester 2",
            ($year - 1).' Semester 2',
            ($year - 1).' Semester 1',
        ];
    }

    private function adminUserFormMeta(?string $selectedDepartment = null): array
    {
        $departments = Department::query()
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        if (
            $selectedDepartment !== null
            && $selectedDepartment !== ''
            && $selectedDepartment !== 'None / Not Applicable'
            && ! in_array($selectedDepartment, $departments, true)
        ) {
            array_unshift($departments, $selectedDepartment);
        }

        return [
            'departments' => $departments,
            'roles' => ['student', 'lecturer', 'staff', 'admin'],
            'staff_roles' => ['hod', 'exam_officer'],
            'statuses' => ['pending invite', 'active', 'inactive'],
        ];
    }

    private function buildAdminUserEditContext(User $user): array
    {
        $profile = match ($user->role) {
            'student' => Student::query()->find($user->identifier),
            'lecturer' => Lecturer::query()->find($user->identifier),
            'staff', 'admin' => Staff::query()->where('staff_id', $user->identifier)->first(),
            default => null,
        };

        $firstName = trim((string) data_get($profile, 'first_name', ''));
        $lastName = trim((string) data_get($profile, 'last_name', ''));

        if ($firstName === '' && $lastName === '') {
            [$firstName, $lastName] = $this->splitDisplayName(trim((string) $user->name));
        }

        $department = trim((string) (
            data_get($profile, 'department')
            ?: $user->department?->name
            ?: data_get($profile, 'course')
            ?: ($user->role === 'admin' ? 'Administration' : '')
        ));

        $faculty = trim((string) (
            data_get($profile, 'faculty')
            ?: $user->department?->faculty
            ?: ''
        ));

        $role = Str::lower(trim((string) $user->role));
        $status = $this->normalizeAdminUserStatus($user->status);
        $course = trim((string) data_get($profile, 'course', ''));
        $salutation = trim((string) data_get($profile, 'salutation', ''));
        $staffRole = $role === 'staff' ? trim((string) data_get($profile, 'role', '')) : '';
        $year = filled(data_get($profile, 'year')) ? (string) data_get($profile, 'year') : '';
        $officeLocation = trim((string) ($user->office_location ?? ''));
        $teachingAssignments = $role === 'lecturer'
            ? UnitLecturer::query()
                ->with('unit:unit_code,unit_name')
                ->where('lecturer_id', $user->identifier)
                ->orderBy('unit_code')
                ->orderBy('course')
                ->get()
            : collect();
        $teachingAssignmentLines = $teachingAssignments
            ->map(fn (UnitLecturer $assignment) => $this->formatTeachingAssignmentLine($assignment->unit_code, $assignment->course))
            ->values()
            ->all();
        $teachingAssignmentLabels = $teachingAssignments
            ->map(function (UnitLecturer $assignment) {
                $unitCode = trim((string) $assignment->unit_code);
                $courseCode = trim((string) $assignment->course);
                $unitName = trim((string) ($assignment->unit?->unit_name ?? ''));

                return $unitName !== ''
                    ? $unitCode.' | '.$courseCode.' - '.$unitName
                    : $unitCode.' | '.$courseCode;
            })
            ->values()
            ->all();

        return [
            'name' => $this->adminUserDisplayName($user, $profile),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => trim((string) $user->email),
            'identifier' => trim((string) $user->identifier),
            'role' => $role,
            'role_label' => Str::upper($role === '' ? 'user' : $role),
            'staff_role' => $staffRole,
            'staff_role_label' => $staffRole !== '' ? Str::title(str_replace('_', ' ', $staffRole)) : '',
            'status' => $status,
            'status_label' => Str::title(str_replace('_', ' ', $status)),
            'department' => $department !== '' ? $department : 'None / Not Applicable',
            'faculty' => $faculty !== '' ? $faculty : 'Not assigned',
            'salutation' => $salutation,
            'course' => $course,
            'year' => $year,
            'campus' => $officeLocation !== '' ? $officeLocation : 'Main Campus',
            'office_location' => $officeLocation,
            'responsibility' => match ($role) {
                'student' => $course !== '' ? 'Course '.$course.' dispute submissions' : 'Student dispute submissions',
                'lecturer' => 'Assessment review and ticket responses for mapped teaching assignments',
                'staff' => match ($staffRole) {
                    'hod' => 'Department-level escalations and lecturer-side review controls',
                    'exam_officer' => 'Examination review escalations and lecturer-side review controls',
                    default => 'Elevated lecturer-side review controls',
                },
                'admin' => 'User, settings, and escalation management',
                default => 'Portal access management',
            },
            'teaching_assignments' => implode(PHP_EOL, $teachingAssignmentLines),
            'teaching_assignment_count' => count($teachingAssignmentLines),
            'teaching_assignment_labels' => $teachingAssignmentLabels,
            'teaching_assignments_summary' => $teachingAssignmentLabels !== []
                ? implode(', ', $teachingAssignmentLabels)
                : 'Not assigned',
            'require_password_reset' => (bool) $user->require_password_reset,
            'manual_approval_required' => (bool) $user->manual_approval_required,
            'welcome_email_sent_at' => $this->formatPortalTimestamp($user->welcome_email_sent_at, 'Not sent yet'),
            'icon' => match ($role) {
                'student' => 'school',
                'lecturer' => 'co_present',
                'staff', 'admin' => 'shield',
                default => 'person',
            },
            'created_at' => $user->created_at?->setTimezone('Africa/Nairobi')->format('Y-m-d') ?? 'Not available',
            'last_login' => $user->last_login?->setTimezone('Africa/Nairobi')->format('Y-m-d') ?? 'No recent login',
            'last_updated' => $user->updated_at?->setTimezone('Africa/Nairobi')->format('Y-m-d') ?? 'Not available',
        ];
    }

    private function redirectByRole(string $role): RedirectResponse
    {
        if ($role === 'student') {
            return redirect()->route('portal.student.dashboard');
        }

        if (in_array($role, ['lecturer', 'staff'], true)) {
            return redirect()->route('portal.lecturer.dashboard');
        }

        if ($role === 'admin') {
            return redirect()->route('portal.admin.dashboard');
        }

        Auth::logout();

        return redirect()
            ->route('portal.auth')
            ->with('auth_error', 'Your role is not yet supported in this web interface.');
    }

    private function maskEmail(string $email): string
    {
        $normalized = trim($email);
        if ($normalized === '' || ! str_contains($normalized, '@')) {
            return $normalized;
        }

        [$local, $domain] = explode('@', $normalized, 2);
        $localLength = strlen($local);

        if ($localLength <= 2) {
            $maskedLocal = substr($local, 0, 1).str_repeat('*', max($localLength - 1, 1));
        } else {
            $maskedLocal = substr($local, 0, 1).str_repeat('*', max($localLength - 2, 1)).substr($local, -1);
        }

        return $maskedLocal.'@'.$domain;
    }

    private function formatPortalTimestamp(?DateTimeInterface $timestamp, string $fallback = 'Not available'): string
    {
        if (!$timestamp) {
            return $fallback;
        }

        return Carbon::instance($timestamp)
            ->setTimezone('Africa/Nairobi')
            ->format('Y-m-d h:i A');
    }

    private function adminUserDisplayName(User $user, mixed $profile): string
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

        return trim((string) $user->name) !== '' ? trim((string) $user->name) : 'Unknown User';
    }

    private function splitDisplayName(string $name): array
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $name));
        if ($normalized === '') {
            return ['', ''];
        }

        $parts = explode(' ', $normalized, 2);

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    private function normalizeAdminUserStatus(?string $status): string
    {
        $normalized = Str::lower(trim((string) $status));

        return match ($normalized) {
            'inactive', 'disabled', 'suspended' => 'inactive',
            'pending', 'pending invite' => 'pending invite',
            default => 'active',
        };
    }

    private function formatTeachingAssignmentLine(?string $unitCode, ?string $course): string
    {
        return Str::upper(trim((string) $unitCode)).' | '.$this->normalizeCourseCode($course);
    }

    private function normalizeCourseCode(?string $course): string
    {
        return Str::upper(trim((string) $course));
    }

    private function resolveDisputeActorInfo(
        string $identifier,
        mixed $users,
        mixed $students,
        mixed $lecturers,
        mixed $staff,
    ): array {
        $normalized = trim($identifier);

        if ($normalized === '') {
            return [
                'identifier' => '',
                'name' => '',
                'role' => 'user',
                'role_label' => $this->ticketRoleLabel('user'),
                'icon' => $this->ticketRoleIcon('user'),
                'badge_class' => $this->ticketRoleBadgeClass('user'),
                'department' => '',
                'faculty' => '',
            ];
        }

        $user = $users->get($normalized);
        $student = $students->get($normalized);
        $lecturer = $lecturers->get($normalized);
        $staffMember = $staff->get($normalized);

        $role = 'user';
        $name = '';
        $department = '';
        $faculty = '';
        $userRole = trim((string) ($user?->role ?? ''));

        if ($userRole === 'student' && $student) {
            $role = 'student';
            $name = trim((string) data_get($student, 'display_name', data_get($student, 'full_name', '')));
            $department = trim((string) data_get($student, 'department', ''));
            $faculty = trim((string) data_get($student, 'faculty', ''));
        } elseif ($userRole === 'lecturer' && $lecturer) {
            $role = 'lecturer';
            $name = trim((string) data_get($lecturer, 'formal_name', data_get($lecturer, 'display_name', '')));
            $department = trim((string) data_get($lecturer, 'department', ''));
            $faculty = trim((string) data_get($user, 'department.faculty', ''));
        } elseif (in_array($userRole, ['staff', 'admin'], true) && $staffMember) {
            $role = $userRole === 'staff'
                ? trim((string) ($staffMember->role ?: 'staff'))
                : 'admin';
            $name = trim((string) data_get($staffMember, 'formal_name', data_get($staffMember, 'display_name', '')));
            $department = trim((string) data_get($staffMember, 'department', ''));
            $faculty = trim((string) data_get($user, 'department.faculty', ''));
        } elseif ($staffMember) {
            $role = trim((string) ($staffMember->role ?: 'staff'));
            $name = trim((string) data_get($staffMember, 'formal_name', data_get($staffMember, 'display_name', '')));
            $department = trim((string) data_get($staffMember, 'department', ''));
            $faculty = trim((string) data_get($user, 'department.faculty', ''));
        } elseif ($lecturer) {
            $role = 'lecturer';
            $name = trim((string) data_get($lecturer, 'formal_name', data_get($lecturer, 'display_name', '')));
            $department = trim((string) data_get($lecturer, 'department', ''));
            $faculty = trim((string) data_get($user, 'department.faculty', ''));
        } elseif ($student) {
            $role = 'student';
            $name = trim((string) data_get($student, 'display_name', data_get($student, 'full_name', '')));
            $department = trim((string) data_get($student, 'department', ''));
            $faculty = trim((string) data_get($student, 'faculty', ''));
        } elseif ($user) {
            $role = $userRole !== '' ? $userRole : 'user';
            $name = trim((string) ($user->name ?? ''));
            $department = trim((string) data_get($user, 'department.name', ''));
            $faculty = trim((string) data_get($user, 'department.faculty', ''));
        }

        if ($name === '' && $user) {
            $name = trim((string) ($user->name ?? ''));
        }

        if ($department === '' && $user) {
            $department = trim((string) data_get($user, 'department.name', ''));
        }

        if ($faculty === '' && $user) {
            $faculty = trim((string) data_get($user, 'department.faculty', ''));
        }

        if ($department === '' && in_array($role, ['admin', 'exam_officer', 'hod'], true)) {
            $department = 'Administration';
        }

        if ($name === '') {
            $name = $normalized;
        }

        return [
            'identifier' => $normalized,
            'name' => $name,
            'role' => $role,
            'role_label' => $this->ticketRoleLabel($role),
            'icon' => $this->ticketRoleIcon($role),
            'badge_class' => $this->ticketRoleBadgeClass($role),
            'department' => $department,
            'faculty' => $faculty,
        ];
    }

    private function buildTicketPersonCard(
        array $actor,
        string $meta,
        string $secondaryMeta,
        ?string $roleOverride = null,
    ): array {
        $role = trim((string) ($roleOverride ?? ($actor['role'] ?? 'user')));

        return [
            'identifier' => $actor['identifier'] ?? '',
            'name' => trim((string) ($actor['name'] ?? 'Unassigned')),
            'icon' => $this->ticketRoleIcon($role !== '' ? $role : 'user'),
            'meta' => trim($meta) !== '' ? trim($meta) : 'No additional details',
            'secondary_meta' => trim($secondaryMeta) !== '' ? trim($secondaryMeta) : 'No recent activity',
            'role_label' => $this->ticketRoleLabel($role !== '' ? $role : 'user'),
            'badge_class' => $this->ticketRoleBadgeClass($role !== '' ? $role : 'user'),
        ];
    }

    private function formatDisputeStatusLabel(?string $status): string
    {
        $normalized = Str::lower(trim((string) $status));

        return match ($normalized) {
            'under_review' => 'Under Review',
            'escalated', 'escalated_admin' => 'Escalated',
            'resolved' => 'Resolved',
            'rejected' => 'Rejected',
            'closed' => 'Closed',
            'pending' => 'Pending',
            default => $normalized !== '' ? Str::title(str_replace('_', ' ', $normalized)) : 'Pending',
        };
    }

    private function formatDisputePriorityLabel(?string $priority): string
    {
        $normalized = Str::lower(trim((string) $priority));

        return $normalized !== '' ? Str::title($normalized) : 'Medium';
    }

    private function disputeIssueTypeLabel(Dispute $dispute): string
    {
        $title = trim((string) ($dispute->title ?? ''));
        if ($title !== '') {
            return $title;
        }

        return match (Str::lower(trim((string) ($dispute->type ?? '')))) {
            'unit' => 'Unit Grade Dispute',
            'general' => 'General Academic Appeal',
            default => 'General Dispute',
        };
    }

    private function ticketRoleLabel(string $role): string
    {
        $normalized = trim($role);

        return Str::upper($normalized !== '' ? str_replace('_', ' ', $normalized) : 'user');
    }

    private function ticketRoleIcon(string $role): string
    {
        return match (Str::lower(trim($role))) {
            'student' => 'school',
            'lecturer' => 'co_present',
            'staff', 'admin', 'exam_officer', 'hod' => 'shield',
            default => 'person',
        };
    }

    private function ticketRoleBadgeClass(string $role): string
    {
        return in_array(Str::lower(trim($role)), ['staff', 'admin', 'exam_officer', 'hod'], true)
            ? 'role-dark'
            : 'role-outline';
    }

    private function formatPortalDate(?DateTimeInterface $timestamp, string $fallback = 'Not available'): string
    {
        if (! $timestamp) {
            return $fallback;
        }

        return Carbon::instance($timestamp)
            ->setTimezone('Africa/Nairobi')
            ->format('Y-m-d');
    }
}
