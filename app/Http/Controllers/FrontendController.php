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
        $this->ensureRole('lecturer');

        return view('lecturer.dashboard');
    }

    public function lecturerDisputes(): View
    {
        $this->ensureRole('lecturer');

        return view('lecturer.disputes');
    }

    public function lecturerHistory(): View
    {
        $this->ensureRole('lecturer');

        return view('lecturer.history');
    }

    public function lecturerDisputeDetail(int $id): View
    {
        $this->ensureRole('lecturer');

        return view('lecturer.dispute-detail', [
            'disputeId' => $id,
        ]);
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

    private function buildAdminManageTicketContext(Dispute $dispute): array
    {
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

        $mappedLecturerIds = UnitLecturer::query()
            ->where('unit_code', $unitCode)
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

        $staff = Staff::query()
            ->whereIn('staff_id', $actorIdentifiers)
            ->get()
            ->keyBy('staff_id');

        $lecturers = Lecturer::query()
            ->whereIn('lecturer_id', $mappedLecturerIds->merge($actorIdentifiers)->unique()->values())
            ->get()
            ->keyBy('lecturer_id');

        $studentInfo = $this->resolveDisputeActorInfo($studentId, $users, $students, $lecturers, $staff);
        $assignedInfo = $this->resolveDisputeActorInfo($assignedTo, $users, $students, $lecturers, $staff);
        $courseLecturerInfo = $this->resolveDisputeActorInfo(
            trim((string) ($mappedLecturerIds->first() ?? '')),
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
            $storedStage !== '' => $storedStage,
            in_array($statusKey, ['escalated', 'escalated_admin'], true) => 'Dean Review',
            ($assignedInfo['role'] ?? '') === 'lecturer' => 'Lecturer Review',
            in_array($assignedInfo['role'] ?? '', ['hod', 'exam_officer'], true) => 'Department Board',
            ($assignedInfo['role'] ?? '') === 'admin' => 'Dean Review',
            default => 'Department Board',
        };

        $reviewStageMeta = match (true) {
            $isResolved => 'Closed on '.$this->formatPortalDate($dispute->resolved_at ?? $lastActivityAt),
            in_array($statusKey, ['escalated', 'escalated_admin'], true) => 'Routed for higher-level decision on '.$this->formatPortalDate($lastActivityAt),
            ($assignedInfo['role'] ?? '') === 'lecturer' => 'Awaiting lecturer follow-up',
            default => 'Updated on '.$this->formatPortalDate($lastActivityAt),
        };

        $routingOptions = ['Lecturer Review', 'Department Board', 'Dean Review', 'Academic Appeals Panel'];
        if (! in_array($currentStage, $routingOptions, true)) {
            array_unshift($routingOptions, $currentStage);
        }

        $statusOptions = collect(['pending', 'under_review', 'escalated', 'resolved'])
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => $this->formatDisputeStatusLabel($value),
            ])
            ->values()
            ->all();

        $routeOptions = collect($routingOptions)
            ->map(fn (string $label) => [
                'value' => $label,
                'label' => $label,
            ])
            ->values()
            ->all();

        $lecturerOptions = $mappedLecturerIds
            ->map(fn (string $lecturerId) => $this->resolveDisputeActorInfo($lecturerId, $users, $students, $lecturers, $staff))
            ->filter(fn (array $info) => ($info['identifier'] ?? '') !== '')
            ->unique('identifier')
            ->values();

        if (
            ($assignedInfo['role'] ?? '') === 'lecturer'
            && ($assignedInfo['identifier'] ?? '') !== ''
            && ! $lecturerOptions->contains('identifier', $assignedInfo['identifier'])
        ) {
            $lecturerOptions->prepend($assignedInfo);
        }

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

        return [
            'id' => $dispute->id,
            'ticket_number' => trim((string) ($dispute->ticket_number ?? 'DSP-'.$dispute->id)),
            'student_id' => $studentId !== '' ? $studentId : 'Not available',
            'page_title' => trim((string) ($dispute->ticket_number ?? 'Ticket')).' Case Review',
            'page_summary' => $summaryCopy,
            'status_label' => $statusLabel,
            'status_meta' => match (true) {
                $isResolved => 'Closed and preserved for audit history',
                in_array($statusKey, ['escalated', 'escalated_admin'], true) => 'Awaiting final admin review',
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
            'department_name' => $departmentName !== '' ? $departmentName : 'Not assigned',
            'faculty_name' => $facultyName !== '' ? $facultyName : 'Not assigned',
            'assessment_label' => match (Str::lower(trim((string) ($dispute->type ?? '')))) {
                'unit' => 'Unit Assessment Review',
                'general' => 'General Academic Appeal',
                default => 'Grade Review Workflow',
            },
            'last_update_label' => $lastUpdateLabel,
            'selected_status' => $statusKey !== '' ? $statusKey : 'pending',
            'selected_routing' => $currentStage,
            'selected_lecturer' => ($assignedInfo['role'] ?? '') === 'lecturer'
                ? ($assignedInfo['identifier'] ?? '')
                : ($reviewerInfo['identifier'] ?? ''),
            'reviewer_name' => ($reviewerInfo['name'] ?? '') !== '' ? $reviewerInfo['name'] : 'Not assigned',
            'student_name' => ($studentInfo['name'] ?? '') !== '' ? $studentInfo['name'] : 'Student',
            'header_badges' => [
                ['label' => $statusLabel, 'class' => 'badge-dark-solid'],
                ['label' => $priorityLabel.' Priority', 'class' => 'badge-outline-gray'],
                ['label' => $slaLabel, 'class' => $isOverdue ? 'badge-outline-gray badge-outline-alert' : 'badge-outline-gray'],
            ],
            'status_options' => $statusOptions,
            'route_options' => $routeOptions,
            'lecturer_options' => $lecturerOptions->map(fn (array $info) => [
                'identifier' => $info['identifier'],
                'label' => $info['name'],
            ])->values()->all(),
            'internal_notes' => $internalNotes,
            'people' => array_slice($people, 0, 3),
            'timeline' => $timeline->all(),
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
            'roles' => ['student', 'lecturer', 'admin'],
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
        $year = filled(data_get($profile, 'year')) ? (string) data_get($profile, 'year') : '';
        $officeLocation = trim((string) ($user->office_location ?? ''));

        return [
            'name' => $this->adminUserDisplayName($user, $profile),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => trim((string) $user->email),
            'identifier' => trim((string) $user->identifier),
            'role' => $role,
            'role_label' => Str::upper($role === '' ? 'user' : $role),
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
                'lecturer' => 'Assessment review and ticket responses',
                'admin' => 'User, settings, and escalation management',
                default => 'Portal access management',
            },
            'require_password_reset' => (bool) $user->require_password_reset,
            'manual_approval_required' => (bool) $user->manual_approval_required,
            'welcome_email_sent_at' => $this->formatPortalTimestamp($user->welcome_email_sent_at, 'Not sent yet'),
            'icon' => match ($role) {
                'student' => 'school',
                'lecturer' => 'co_present',
                'admin' => 'shield',
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

        if ($role === 'lecturer') {
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

        if ($student) {
            $role = 'student';
            $name = trim((string) data_get($student, 'display_name', data_get($student, 'full_name', '')));
            $department = trim((string) data_get($student, 'department', ''));
            $faculty = trim((string) data_get($student, 'faculty', ''));
        } elseif ($lecturer) {
            $role = 'lecturer';
            $name = trim((string) data_get($lecturer, 'formal_name', data_get($lecturer, 'display_name', '')));
            $department = trim((string) data_get($lecturer, 'department', ''));
            $faculty = trim((string) data_get($user, 'department.faculty', ''));
        } elseif ($staffMember) {
            $role = trim((string) ($user?->role ?: $staffMember->role ?: 'admin'));
            $name = trim((string) data_get($staffMember, 'formal_name', data_get($staffMember, 'display_name', '')));
            $department = trim((string) data_get($staffMember, 'department', ''));
            $faculty = trim((string) data_get($user, 'department.faculty', ''));
        } elseif ($user) {
            $role = trim((string) ($user->role ?? 'user')) !== '' ? trim((string) $user->role) : 'user';
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
            'admin', 'exam_officer', 'hod' => 'shield',
            default => 'person',
        };
    }

    private function ticketRoleBadgeClass(string $role): string
    {
        return in_array(Str::lower(trim($role)), ['admin', 'exam_officer', 'hod'], true)
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
