<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Dispute;
use App\Models\DisputeLog;
use App\Models\Lecturer;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Unit;
use App\Models\UnitLecturer;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DisputeController extends Controller
{
    public function searchUnits(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $query = (string) $request->query('q', '');

        $units = Unit::query()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where('unit_code', 'like', "%{$query}%")
                    ->orWhere('unit_name', 'like', "%{$query}%");
            })
            ->limit(30)
            ->get();

        return response()->json($units);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string|exists:students,reg_number',
            'type' => ['required', Rule::in(['unit', 'general'])],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $actorIdentifier = $this->actorIdentifier($request);
        $actorRole = $this->actorRole($request);

        if ($actorRole === 'student' && $actorIdentifier !== null && $actorIdentifier !== $request->student_id) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $student = Student::find($request->student_id);
        if (! $student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        if ($request->type === 'unit') {
            $request->validate([
                'unit_code' => 'required|string|exists:units,unit_code',
            ]);

            $mappings = UnitLecturer::where('unit_code', $request->unit_code)
                ->orderBy('id')
                ->get();

            if ($mappings->isEmpty()) {
                return response()->json(['error' => 'No lecturer assigned to this unit'], 404);
            }

            $lecturerIds = $mappings
                ->pluck('lecturer_id')
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->map(fn ($value) => trim((string) $value))
                ->unique()
                ->values();

            if ($lecturerIds->isEmpty()) {
                return response()->json(['error' => 'No lecturer assigned to this unit'], 404);
            }

            if ($lecturerIds->count() > 1) {
                return response()->json([
                    'error' => 'Multiple lecturers are assigned to this unit. Please ask admin to keep one active lecturer per unit until year-based routing is added.',
                ], 409);
            }

            $assignedTo = $lecturerIds->first();
            $assignedRole = 'lecturer';
        } else {
            $request->validate([
                'assigned_role' => ['required', Rule::in(['exam_officer', 'hod', 'admin'])],
            ]);

            $staff = Staff::where('role', $request->assigned_role)->first();

            if (! $staff) {
                return response()->json(['error' => 'Staff not found'], 404);
            }

            $assignedTo = $staff->staff_id;
            $assignedRole = $staff->role;
        }

        $dispute = Dispute::create([
            'student_id' => $request->student_id,
            'type' => $request->type,
            'unit_code' => $request->unit_code,
            'priority' => $request->priority ?? 'medium',
            'assigned_to' => $assignedTo,
            'assigned_role' => $assignedRole,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');

            Attachment::create([
                'dispute_id' => $dispute->id,
                'file_path' => $path,
                'uploaded_by' => $request->student_id,
            ]);
        }

        NotificationService::notify(
            $assignedTo,
            'New Dispute Assigned',
            'A new dispute has been assigned to you. Ticket: '.$dispute->ticket_number,
            '/tickets/'.$dispute->id,
            $dispute->id
        );

        ActivityLogService::log(
            $actorIdentifier ?? $request->student_id,
            'Dispute Created',
            'dispute',
            $dispute->id,
            'Student created dispute '.$dispute->ticket_number
        );

        return response()->json([
            'message' => 'Dispute submitted successfully',
            'data' => $dispute,
        ]);
    }

    public function lecturerDisputes(Request $request, $lecturer_id)
    {
        $actorIdentifier = $this->actorIdentifier($request);
        $actorRole = $this->actorRole($request);

        if (in_array($actorRole, ['lecturer', 'staff'], true) && $actorIdentifier !== null && $actorIdentifier !== $lecturer_id) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $query = Dispute::where('assigned_to', $lecturer_id);

        if ($actorRole === 'lecturer') {
            $query->where('assigned_role', 'lecturer');
        }

        $disputes = $query
            ->orderByDesc('created_at')
            ->get();

        return response()->json($this->decorateDisputes($disputes)->all());
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'dispute_id' => 'required|integer|exists:disputes,id',
            'status' => ['required', Rule::in(['pending', 'under_review', 'resolved', 'escalated'])],
            'performed_by' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $dispute = Dispute::find($request->dispute_id);
        if (! $dispute) {
            return response()->json(['error' => 'Dispute not found'], 404);
        }

        $performedBy = $this->actorIdentifier($request) ?? $request->performed_by;
        if (! is_string($performedBy) || trim($performedBy) === '') {
            return response()->json(['error' => 'performed_by is required'], 422);
        }

        $actorRole = $this->actorRole($request);
        if (in_array($actorRole, ['lecturer', 'staff'], true) && $dispute->assigned_to !== $performedBy) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $oldStatus = $dispute->status;
        $dispute->status = $request->status;
        $dispute->resolved_at = $request->status === 'resolved' ? now() : null;
        $dispute->save();

        DisputeLog::create([
            'dispute_id' => $dispute->id,
            'action' => 'Status Updated',
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'comment' => $request->comment,
            'performed_by' => $performedBy,
        ]);

        ActivityLogService::log(
            $performedBy,
            'Status Updated',
            'dispute',
            $dispute->id,
            'Status changed from '.$oldStatus.' to '.$request->status
        );

        NotificationService::notify(
            $dispute->student_id,
            'Dispute Status Updated',
            'Your dispute '.$dispute->ticket_number.' status changed to '.$request->status,
            '/student/tickets/'.$dispute->id,
            $dispute->id
        );

        return response()->json([
            'message' => 'Status updated successfully',
        ]);
    }

    public function respond(Request $request)
    {
        $request->validate([
            'dispute_id' => 'required|integer|exists:disputes,id',
            'performed_by' => 'nullable|string',
            'comment' => 'required|string',
        ]);

        $dispute = Dispute::find($request->dispute_id);
        if (! $dispute) {
            return response()->json(['error' => 'Dispute not found'], 404);
        }

        $performedBy = $this->actorIdentifier($request) ?? $request->performed_by;
        if (! is_string($performedBy) || trim($performedBy) === '') {
            return response()->json(['error' => 'performed_by is required'], 422);
        }

        $actorRole = $this->actorRole($request);
        if (in_array($actorRole, ['lecturer', 'staff'], true) && $dispute->assigned_to !== $performedBy) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        DisputeLog::create([
            'dispute_id' => $dispute->id,
            'action' => 'Response Added',
            'old_status' => $dispute->status,
            'new_status' => $dispute->status,
            'comment' => $request->comment,
            'performed_by' => $performedBy,
        ]);

        NotificationService::notify(
            $dispute->student_id,
            'New Response on Your Dispute',
            'There is a new response on dispute '.$dispute->ticket_number,
            '/student/tickets/'.$dispute->id,
            $dispute->id
        );

        ActivityLogService::log(
            $performedBy,
            'Response Added',
            'dispute',
            $dispute->id,
            'Response added to dispute '.$dispute->ticket_number
        );

        return response()->json([
            'message' => 'Response added successfully',
        ]);
    }

    public function studentDisputes(Request $request, $student_id)
    {
        $actorIdentifier = $this->actorIdentifier($request);
        $actorRole = $this->actorRole($request);

        if ($actorRole === 'student' && $actorIdentifier !== null && $actorIdentifier !== $student_id) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $disputes = Dispute::where('student_id', $student_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($this->decorateDisputes($disputes)->all());
    }

    public function disputeLogs($dispute_id)
    {
        $logs = DisputeLog::where('dispute_id', $dispute_id)
            ->orderBy('created_at')
            ->get();

        return response()->json($logs);
    }

    public function adminDashboard()
    {
        $averageResolution = Dispute::where('status', 'resolved')
            ->selectRaw('AVG(DATEDIFF(COALESCE(resolved_at, updated_at), created_at)) as avg_days')
            ->value('avg_days');

        return response()->json([
            'total_disputes' => Dispute::count(),
            'average_resolution_time' => number_format((float) ($averageResolution ?? 0), 1).' days',
            'pending' => Dispute::where('status', 'pending')->count(),
            'under_review' => Dispute::where('status', 'under_review')->count(),
            'resolved' => Dispute::where('status', 'resolved')->count(),
            'escalated' => Dispute::where('status', 'escalated')->count(),
            'active_users' => User::where('status', 'active')->count(),
        ]);
    }

    public function allDisputes()
    {
        return response()->json(
            $this->decorateDisputes(
                Dispute::orderBy('created_at', 'desc')->get()
            )->all()
        );
    }

    public function adminUpdateTicket(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'under_review', 'resolved', 'escalated'])],
            'routing_stage' => ['nullable', Rule::in(['Lecturer Review', 'Department Board', 'Dean Review', 'Academic Appeals Panel'])],
            'lecturer_id' => ['nullable', 'string', 'exists:lecturers,lecturer_id'],
        ]);

        $dispute = Dispute::find($id);
        if (! $dispute) {
            return response()->json(['error' => 'Dispute not found'], 404);
        }

        $performedBy = $this->actorIdentifier($request);
        if (! is_string($performedBy) || trim($performedBy) === '') {
            return response()->json(['error' => 'performed_by is required'], 422);
        }

        $statusInput = isset($validated['status'])
            ? trim((string) $validated['status'])
            : null;
        $lecturerId = isset($validated['lecturer_id'])
            ? trim((string) $validated['lecturer_id'])
            : null;
        $routingStage = isset($validated['routing_stage'])
            ? trim((string) $validated['routing_stage'])
            : ($lecturerId !== null && $lecturerId !== '' ? 'Lecturer Review' : null);

        if (($statusInput === null || $statusInput === '') && ($routingStage === null || $routingStage === '')) {
            return response()->json(['error' => 'No ticket updates were provided.'], 422);
        }

        $oldStatus = trim((string) ($dispute->status ?? 'pending'));
        $oldAssignedTo = trim((string) ($dispute->assigned_to ?? ''));
        $oldAssignedRole = trim((string) ($dispute->assigned_role ?? ''));
        $oldStage = $this->defaultAdminRoutingStage($dispute);

        $newStatus = $oldStatus;
        $newAssignedTo = $oldAssignedTo;
        $newAssignedRole = $oldAssignedRole;
        $newStage = trim((string) ($dispute->assignment_name ?? ''));
        $routingReason = null;

        if (is_string($routingStage) && $routingStage !== '') {
            $resolvedRouting = $this->resolveAdminRoutingSelection($dispute, $routingStage, $lecturerId, $performedBy);
            $newAssignedTo = $resolvedRouting['assigned_to'];
            $newAssignedRole = $resolvedRouting['assigned_role'];
            $newStage = $resolvedRouting['stage'];
            $routingReason = $resolvedRouting['reason'];

            if ($statusInput === null || $statusInput === '') {
                $newStatus = $resolvedRouting['status'];
            }
        }

        if (is_string($statusInput) && $statusInput !== '') {
            $newStatus = $statusInput;
        }

        $statusChanged = $newStatus !== $oldStatus;
        $assigneeChanged = $newAssignedTo !== $oldAssignedTo || $newAssignedRole !== $oldAssignedRole;
        $stageChanged = is_string($routingStage) && $routingStage !== '' && $newStage !== $oldStage;

        if (! $statusChanged && ! $assigneeChanged && ! $stageChanged) {
            return response()->json([
                'changed' => false,
                'message' => 'No ticket changes were applied.',
            ]);
        }

        DB::transaction(function () use (
            $dispute,
            $performedBy,
            $oldStatus,
            $oldAssignedTo,
            $oldAssignedRole,
            $oldStage,
            $newStatus,
            $newAssignedTo,
            $newAssignedRole,
            $newStage,
            $routingReason,
            $statusChanged,
            $assigneeChanged,
            $stageChanged
        ) {
            $dispute->status = $newStatus;
            $dispute->assigned_to = $newAssignedTo;
            $dispute->assigned_role = $newAssignedRole;

            if ($newStage !== '') {
                $dispute->assignment_name = $newStage;
            }

            if ($statusChanged) {
                $dispute->resolved_at = $newStatus === 'resolved' ? now() : null;
            }

            if ($newStatus === 'escalated' && $oldStatus !== 'escalated') {
                $dispute->escalated_at = now();
                $dispute->escalation_level = max(1, (int) ($dispute->escalation_level ?? 0) + 1);
            }

            if ($routingReason !== null && trim($routingReason) !== '') {
                $dispute->escalation_reason = $routingReason;
            }

            $dispute->save();

            if ($stageChanged || $assigneeChanged) {
                $routingComment = collect([
                    $stageChanged ? 'Routing moved from '.$oldStage.' to '.$newStage.'.' : null,
                    $assigneeChanged
                        ? 'Assignment updated from '
                            .$this->formatAssignmentSummary($oldAssignedTo, $oldAssignedRole)
                            .' to '
                            .$this->formatAssignmentSummary($newAssignedTo, $newAssignedRole)
                            .'.'
                        : null,
                ])
                    ->filter()
                    ->implode(' ');

                $routingAction = $newAssignedRole === 'lecturer' && $assigneeChanged
                    ? 'Lecturer Reassigned'
                    : 'Routing Updated';

                DisputeLog::create([
                    'dispute_id' => $dispute->id,
                    'action' => $routingAction,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'comment' => $routingComment,
                    'performed_by' => $performedBy,
                ]);
            }

            if ($statusChanged) {
                DisputeLog::create([
                    'dispute_id' => $dispute->id,
                    'action' => 'Status Updated',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'comment' => 'Status changed from '.$oldStatus.' to '.$newStatus.'.',
                    'performed_by' => $performedBy,
                ]);
            }

            $activitySummary = collect([
                $stageChanged ? 'Stage '.$oldStage.' -> '.$newStage : null,
                $assigneeChanged
                    ? 'Assignee '.$this->formatAssignmentSummary($oldAssignedTo, $oldAssignedRole)
                        .' -> '
                        .$this->formatAssignmentSummary($newAssignedTo, $newAssignedRole)
                    : null,
                $statusChanged ? 'Status '.$oldStatus.' -> '.$newStatus : null,
            ])
                ->filter()
                ->implode('; ');

            ActivityLogService::log(
                $performedBy,
                'Admin Updated Ticket',
                'dispute',
                $dispute->id,
                $activitySummary !== '' ? $activitySummary : 'Admin updated dispute '.$dispute->ticket_number
            );

            NotificationService::notify(
                $dispute->student_id,
                'Dispute Updated',
                'Your dispute '.$dispute->ticket_number.' was updated by admin.',
                '/portal/student/disputes/'.$dispute->id,
                $dispute->id
            );

            if (($stageChanged || $assigneeChanged) && $newAssignedTo !== '') {
                NotificationService::notify(
                    $newAssignedTo,
                    'Dispute Assignment Updated',
                    'Ticket '.$dispute->ticket_number.' was routed to you for '.$newStage.'.',
                    '/portal/admin/disputes/'.$dispute->id,
                    $dispute->id
                );
            }
        });

        return response()->json([
            'changed' => true,
            'message' => 'Ticket updated successfully.',
            'data' => [
                'id' => $dispute->id,
                'status' => $dispute->status,
                'assigned_to' => $dispute->assigned_to,
                'assigned_role' => $dispute->assigned_role,
                'routing_stage' => $dispute->assignment_name,
            ],
        ]);
    }

    public function escalatedDisputes()
    {
        $disputes = Dispute::where('status', 'escalated')
            ->orWhere('status', 'escalated_admin')
            ->get();

        $identifiers = $disputes
            ->pluck('assigned_to')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->unique()
            ->values();
        $unitCodes = $disputes
            ->pluck('unit_code')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->unique()
            ->values();

        $userNames = User::query()
            ->whereIn('identifier', $identifiers)
            ->pluck('name', 'identifier');

        $lecturerNames = Lecturer::query()
            ->whereIn('lecturer_id', $identifiers)
            ->get()
            ->mapWithKeys(function (Lecturer $lecturer) {
                return [$lecturer->lecturer_id => $lecturer->formal_name];
            });

        $unitLecturerNames = UnitLecturer::query()
            ->with('lecturer')
            ->whereIn('unit_code', $unitCodes)
            ->orderBy('unit_code')
            ->get()
            ->groupBy('unit_code')
            ->map(function ($mappings) {
                $first = $mappings->first();
                $lecturer = $first?->lecturer;

                return $lecturer?->formal_name ?: null;
            });

        $staffNames = Staff::query()
            ->whereIn('staff_id', $identifiers)
            ->get()
            ->mapWithKeys(function (Staff $staff) {
                return [$staff->staff_id => $staff->formal_name];
            });

        $payload = $disputes->map(function (Dispute $dispute) use ($lecturerNames, $unitLecturerNames, $userNames, $staffNames) {
            $assignedTo = trim((string) ($dispute->assigned_to ?? ''));
            $assignedName = $lecturerNames[$assignedTo] ?? $userNames[$assignedTo] ?? $staffNames[$assignedTo] ?? null;
            $unitCode = trim((string) ($dispute->unit_code ?? ''));
            $assignedLecturerName = $unitLecturerNames[$unitCode] ?? $assignedName;

            return array_merge($dispute->toArray(), [
                'assigned_name' => $assignedName ?: ($assignedTo !== '' ? $assignedTo : 'Unassigned'),
                'assigned_lecturer_name' => $assignedLecturerName ?: ($assignedTo !== '' ? $assignedTo : 'Unassigned'),
            ]);
        })->values()->all();

        ActivityLogService::log(
            null,
            'Viewed Escalated Disputes',
            'dispute',
            null,
            'Admin viewed escalated disputes list'
        );

        return response()->json($payload);
    }

    public function disputesPerUnit()
    {
        $data = DB::table('disputes')
            ->leftJoin('units', 'disputes.unit_code', '=', 'units.unit_code')
            ->select(
                'disputes.unit_code',
                'units.unit_name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN disputes.status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN disputes.status = 'under_review' THEN 1 ELSE 0 END) as under_review"),
                DB::raw("SUM(CASE WHEN disputes.status = 'resolved' THEN 1 ELSE 0 END) as resolved"),
                DB::raw("SUM(CASE WHEN disputes.status IN ('escalated', 'escalated_admin') THEN 1 ELSE 0 END) as escalated")
            )
            ->whereNotNull('disputes.unit_code')
            ->groupBy('disputes.unit_code', 'units.unit_name')
            ->orderByDesc('total')
            ->get();

        return response()->json($data);
    }

    public function disputesPerLecturer()
    {
        $data = DB::table('disputes')
            ->leftJoin('lecturers', 'disputes.assigned_to', '=', 'lecturers.lecturer_id')
            ->select(
                'disputes.assigned_to',
                'lecturers.salutation',
                'lecturers.last_name',
                'lecturers.first_name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN disputes.status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN disputes.status = 'under_review' THEN 1 ELSE 0 END) as under_review"),
                DB::raw("SUM(CASE WHEN disputes.status = 'resolved' THEN 1 ELSE 0 END) as resolved")
            )
            ->where('assigned_role', 'lecturer')
            ->groupBy('disputes.assigned_to', 'lecturers.salutation', 'lecturers.last_name', 'lecturers.first_name')
            ->orderByDesc('total')
            ->get();

        return response()->json($data);
    }

    public function monthlyDisputes()
    {
        $data = DB::table('disputes')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    public function resolutionTime()
    {
        $data = DB::table('disputes')
            ->where('status', 'resolved')
            ->select(DB::raw('AVG(DATEDIFF(updated_at, created_at)) as avg_days'))
            ->first();

        return response()->json($data);
    }

    public function timeline($id)
    {
        $logs = DisputeLog::where('dispute_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($logs);
    }

    public function disputeAttachments($dispute_id)
    {
        $attachments = Attachment::where('dispute_id', $dispute_id)->get();

        return response()->json($attachments);
    }

    public function downloadAttachment($id)
    {
        $attachment = Attachment::find($id);
        if (! $attachment) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        $fullPath = storage_path('app/public/'.$attachment->file_path);
        if (! file_exists($fullPath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download($fullPath);
    }

    public function filterDisputes(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'under_review', 'resolved', 'escalated'])],
            'assigned_role' => 'nullable|string|max:50',
            'unit_code' => 'nullable|string|max:50',
            'student_id' => 'nullable|string|max:50',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
        ]);

        $query = Dispute::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->assigned_role) {
            $query->where('assigned_role', $request->assigned_role);
        }

        if ($request->unit_code) {
            $query->where('unit_code', $request->unit_code);
        }

        if ($request->student_id) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        $disputes = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($disputes);
    }

    public function alerts()
    {
        $pendingCutoff = now()->subDays(2);
        $escalationCutoff = now()->subDays(2);
        $longUnresolvedCutoff = now()->subDays(7);

        $unassigned = Dispute::whereNull('assigned_to')->count();

        $pendingOverdue = Dispute::where('status', 'pending')
            ->where('created_at', '<', $pendingCutoff)
            ->count();

        $escalationSoon = Dispute::whereIn('status', ['pending', 'under_review'])
            ->where('updated_at', '<', $escalationCutoff)
            ->count();

        $escalated = Dispute::where('status', 'escalated')->count();

        $longUnresolved = Dispute::whereNotIn('status', ['resolved', 'closed'])
            ->where('created_at', '<', $longUnresolvedCutoff)
            ->count();

        return response()->json([
            'unassigned' => $unassigned,
            'pending_overdue' => $pendingOverdue,
            'escalation_soon' => $escalationSoon,
            'escalated' => $escalated,
            'long_unresolved' => $longUnresolved,
        ]);
    }

    public function lecturerAlerts(Request $request, $lecturer_id)
    {
        $actorIdentifier = $this->actorIdentifier($request);
        $actorRole = $this->actorRole($request);

        if (in_array($actorRole, ['lecturer', 'staff'], true) && $actorIdentifier !== null && $actorIdentifier !== $lecturer_id) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $overdueCutoff = now()->subDays(2);

        $pending = Dispute::where('assigned_to', $lecturer_id)
            ->where('status', 'pending')
            ->count();

        $overdue = Dispute::where('assigned_to', $lecturer_id)
            ->whereIn('status', ['pending', 'under_review'])
            ->where('updated_at', '<', $overdueCutoff)
            ->count();

        return response()->json([
            'pending_disputes' => $pending,
            'overdue_disputes' => $overdue,
        ]);
    }

    // Dashboard chart endpoints
    public function disputesPerMonth()
    {
        $data = Dispute::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    public function disputesByStatus()
    {
        $data = Dispute::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        return response()->json($data);
    }

    public function disputesByUnit()
    {
        $data = Dispute::selectRaw('unit_code, COUNT(*) as total')
            ->groupBy('unit_code')
            ->get();

        return response()->json($data);
    }

    public function disputesByLecturer()
    {
        $data = Dispute::selectRaw('assigned_to, COUNT(*) as total')
            ->where('assigned_role', 'lecturer')
            ->groupBy('assigned_to')
            ->get();

        return response()->json($data);
    }

    public function averageResolutionTime()
    {
        $data = Dispute::where('status', 'resolved')
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->first();

        return response()->json($data);
    }

    private function actorIdentifier(Request $request): ?string
    {
        $identifier = $request->attributes->get('actor_identifier');

        if (is_string($identifier) && trim($identifier) !== '') {
            return trim($identifier);
        }

        $fallback = $request->input('performed_by')
            ?? $request->input('identifier')
            ?? $request->header('X-User-Identifier');

        if (! is_string($fallback) || trim($fallback) === '') {
            return null;
        }

        return trim($fallback);
    }

    private function actorRole(Request $request): ?string
    {
        $role = $request->attributes->get('actor_role');

        if (! is_string($role) || trim($role) === '') {
            return null;
        }

        return trim($role);
    }

    private function resolveAdminRoutingSelection(
        Dispute $dispute,
        string $routingStage,
        ?string $lecturerId,
        string $adminIdentifier
    ): array {
        return match ($routingStage) {
            'Lecturer Review' => $this->resolveLecturerRoutingSelection($dispute, $lecturerId),
            'Department Board' => $this->resolveDepartmentBoardRoutingSelection($dispute),
            'Dean Review', 'Academic Appeals Panel' => [
                'assigned_to' => $adminIdentifier,
                'assigned_role' => 'admin',
                'stage' => $routingStage,
                'status' => 'escalated',
                'reason' => 'Manually routed to '.$routingStage.' by admin.',
            ],
            default => throw ValidationException::withMessages([
                'routing_stage' => ['The selected routing stage is not supported.'],
            ]),
        };
    }

    private function resolveLecturerRoutingSelection(Dispute $dispute, ?string $lecturerId): array
    {
        $selectedLecturerId = is_string($lecturerId) ? trim($lecturerId) : '';

        if ($selectedLecturerId === '') {
            $currentAssignedRole = Str::lower(trim((string) ($dispute->assigned_role ?? '')));
            $currentAssignedTo = trim((string) ($dispute->assigned_to ?? ''));

            if ($currentAssignedRole === 'lecturer' && $currentAssignedTo !== '') {
                $selectedLecturerId = $currentAssignedTo;
            } else {
                $selectedLecturerId = trim((string) (
                    UnitLecturer::query()
                        ->where('unit_code', $dispute->unit_code)
                        ->value('lecturer_id')
                ));
            }
        }

        if ($selectedLecturerId === '') {
            throw ValidationException::withMessages([
                'lecturer_id' => ['Select a lecturer before routing this ticket to lecturer review.'],
            ]);
        }

        return [
            'assigned_to' => $selectedLecturerId,
            'assigned_role' => 'lecturer',
            'stage' => 'Lecturer Review',
            'status' => 'under_review',
            'reason' => 'Manually routed to Lecturer Review by admin.',
        ];
    }

    private function resolveDepartmentBoardRoutingSelection(Dispute $dispute): array
    {
        $currentAssignedRole = Str::lower(trim((string) ($dispute->assigned_role ?? '')));
        $currentAssignedTo = trim((string) ($dispute->assigned_to ?? ''));

        if (in_array($currentAssignedRole, ['hod', 'exam_officer'], true) && $currentAssignedTo !== '') {
            return [
                'assigned_to' => $currentAssignedTo,
                'assigned_role' => $currentAssignedRole,
                'stage' => 'Department Board',
                'status' => 'escalated',
                'reason' => 'Manually routed to Department Board by admin.',
            ];
        }

        $staffMember = Staff::query()
            ->whereIn('role', ['hod', 'exam_officer'])
            ->orderByRaw("CASE role WHEN 'hod' THEN 0 ELSE 1 END")
            ->first();

        if (! $staffMember) {
            throw ValidationException::withMessages([
                'routing_stage' => ['No HOD or exam officer is available for department-board routing.'],
            ]);
        }

        return [
            'assigned_to' => $staffMember->staff_id,
            'assigned_role' => trim((string) $staffMember->role),
            'stage' => 'Department Board',
            'status' => 'escalated',
            'reason' => 'Manually routed to Department Board by admin.',
        ];
    }

    private function defaultAdminRoutingStage(Dispute $dispute): string
    {
        $statusKey = Str::lower(trim((string) ($dispute->status ?? 'pending')));
        $storedStage = trim((string) ($dispute->assignment_name ?? ''));
        $assignedRole = Str::lower(trim((string) ($dispute->assigned_role ?? '')));

        return match (true) {
            $statusKey === 'resolved' => 'Resolved',
            $storedStage !== '' => $storedStage,
            $statusKey === 'escalated' => 'Dean Review',
            $assignedRole === 'lecturer' => 'Lecturer Review',
            in_array($assignedRole, ['hod', 'exam_officer'], true) => 'Department Board',
            $assignedRole === 'admin' => 'Dean Review',
            default => 'Department Board',
        };
    }

    private function formatAssignmentSummary(string $identifier, string $role): string
    {
        $normalizedRole = trim(str_replace('_', ' ', $role));
        $roleLabel = $normalizedRole !== '' ? Str::title($normalizedRole) : 'Unassigned';
        $idLabel = trim($identifier) !== '' ? trim($identifier) : 'none';

        return $roleLabel.' ('.$idLabel.')';
    }

    private function decorateDisputes($disputes)
    {
        $studentIds = $disputes
            ->pluck('student_id')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        $unitCodes = $disputes
            ->pluck('unit_code')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        $students = Student::query()
            ->whereIn('reg_number', $studentIds)
            ->get()
            ->keyBy('reg_number');

        $units = Unit::query()
            ->whereIn('unit_code', $unitCodes)
            ->get()
            ->keyBy('unit_code');

        return $disputes->map(function (Dispute $dispute) use ($students, $units) {
            $student = $students->get(trim((string) $dispute->student_id));
            $unit = $units->get(trim((string) ($dispute->unit_code ?? '')));

            return array_merge($dispute->toArray(), [
                'student_name' => trim((string) ($student?->display_name ?? '')),
                'student_course' => trim((string) ($student?->course ?? '')),
                'unit_name' => trim((string) ($unit?->unit_name ?? '')),
            ]);
        });
    }
}
