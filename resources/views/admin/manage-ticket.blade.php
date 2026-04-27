@extends('layouts.app')

@section('title', (($adminManageTicket['page_title'] ?? 'Manage Ticket').' | Grade Dispute'))
@section('page_id', 'admin-manage-ticket')
@section('page_title', 'Manage Ticket')
@section('dispute_id', (string) ($adminManageTicket['id'] ?? ''))

@section('content')
@php($ticket = $adminManageTicket ?? [])
@php($printNotesRaw = trim((string) ($ticket['internal_notes'] ?? '')))
@php($printNotes = $printNotesRaw === '' || str_starts_with($printNotesRaw, 'No private admin notes have been saved yet.') ? 'No admin notes recorded.' : $printNotesRaw)
@php($attachmentCount = (int) ($ticket['attachment_count'] ?? count($ticket['attachments'] ?? [])))
@php($activityCount = (int) ($ticket['activity_count'] ?? count($ticket['timeline'] ?? [])))

<div class="page-header flex-header ticket-page-header" data-ticket-number="{{ $ticket['ticket_number'] ?? 'Ticket' }}">
    <div class="header-text">
        <span class="eyebrow">Admin / Manage Tickets / {{ $ticket['ticket_number'] ?? 'Ticket' }}</span>
        <h1>{{ $ticket['page_title'] ?? 'Ticket Review' }}</h1>
        <p>{{ $ticket['page_summary'] ?? 'Review the case details, status, and routing context for this dispute.' }}</p>
        <div class="header-badges">
            @foreach (($ticket['header_badges'] ?? []) as $badge)
                <span class="{{ $badge['class'] ?? 'badge-outline-gray' }}">{{ $badge['label'] ?? '' }}</span>
            @endforeach
        </div>
    </div>
    <div class="header-actions no-print">
        <a href="{{ route('portal.admin.disputes') }}" class="btn btn-outline btn-surface">
            <span class="material-symbols-outlined">arrow_back</span> Back to Tickets
        </a>
        <button class="btn btn-outline btn-surface" id="adminTicketPrintBtn" type="button">
            <span class="material-symbols-outlined">print</span> Print Case File
        </button>
        <button class="btn btn-primary" id="adminTicketResolveBtn" type="button">
            <span class="material-symbols-outlined">task_alt</span> Mark Resolved
        </button>
    </div>
</div>

<article class="casefile-print-sheet print-only">
    <header class="casefile-print-hero">
        <div class="casefile-print-brand-row">
            <div>
                <span class="ticket-print-brand">Grade Dispute System</span>
                <h1>Case File Report</h1>
            </div>
            <div class="casefile-print-badges">
                <span class="casefile-print-badge">{{ $ticket['ticket_number'] ?? 'Not available' }}</span>
                <span class="casefile-print-badge">{{ $ticket['status_label'] ?? 'Pending' }}</span>
            </div>
        </div>
        <p class="casefile-print-inline-meta">
            Prepared by {{ $ticket['print_generated_by_name'] ?? 'Administration' }} |
            {{ $ticket['print_generated_by_department'] ?? 'Administration' }} |
            Printed <span data-print-timestamp>{{ now('Africa/Nairobi')->format('M j, Y g:i A') }}</span>
        </p>
    </header>

    <section class="casefile-print-section casefile-print-section-compact">
        <div class="casefile-print-fact-grid casefile-print-fact-grid-compact">
            <div class="casefile-print-fact-card">
                <span class="casefile-print-fact-label">Review Stage</span>
                <strong>{{ $ticket['review_stage'] ?? 'Pending Review' }}</strong>
            </div>
            <div class="casefile-print-fact-card">
                <span class="casefile-print-fact-label">Priority</span>
                <strong>{{ $ticket['priority_label'] ?? 'Medium' }}</strong>
            </div>
            <div class="casefile-print-fact-card">
                <span class="casefile-print-fact-label">Submitted</span>
                <strong>{{ $ticket['submitted_at'] ?? 'Not available' }}</strong>
            </div>
            <div class="casefile-print-fact-card">
                <span class="casefile-print-fact-label">Current Reviewer</span>
                <strong>{{ $ticket['reviewer_name'] ?? 'Not assigned' }}</strong>
            </div>
        </div>
    </section>

    <div class="casefile-print-columns">
        <section class="casefile-print-section">
            <div class="casefile-print-section-header">
                <h2>Student and Case</h2>
                <span>Core identity and dispute details.</span>
            </div>
            <dl class="casefile-print-definition-list">
                <div>
                    <dt>Student</dt>
                    <dd>{{ ($ticket['student_name'] ?? 'Student').' ('.($ticket['student_id'] ?? 'Not available').')' }}</dd>
                </div>
                <div>
                    <dt>Course</dt>
                    <dd>{{ $ticket['student_course_label'] ?? 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt>Unit</dt>
                    <dd>{{ $ticket['unit_label'] ?? 'General Appeal' }}</dd>
                </div>
                <div>
                    <dt>Department</dt>
                    <dd>{{ $ticket['department_name'] ?? 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt>Faculty</dt>
                    <dd>{{ $ticket['faculty_name'] ?? 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt>Issue Type</dt>
                    <dd>{{ $ticket['issue_type'] ?? 'General Dispute' }}</dd>
                </div>
                <div>
                    <dt>Time Open</dt>
                    <dd>{{ $ticket['time_open_label'] ?? 'Not available' }}</dd>
                </div>
            </dl>
        </section>

        <section class="casefile-print-section">
            <div class="casefile-print-section-header">
                <h2>Routing Snapshot</h2>
                <span>Current ownership and audit counts.</span>
            </div>
            <dl class="casefile-print-definition-list">
                <div>
                    <dt>Assigned Lecturer</dt>
                    <dd>{{ $ticket['reviewer_name'] ?? 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt>Current Owner</dt>
                    <dd>{{ $ticket['current_owner_name'] ?? 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt>Escalation Trigger</dt>
                    <dd>{{ $ticket['escalation_trigger'] ?? 'Awaiting review context' }}</dd>
                </div>
                <div>
                    <dt>Last Update</dt>
                    <dd>{{ $ticket['last_update_label'] ?? 'No recorded updates yet' }}</dd>
                </div>
                <div>
                    <dt>Attachments</dt>
                    <dd>{{ $attachmentCount }} on file</dd>
                </div>
                <div>
                    <dt>Activity Entries</dt>
                    <dd>{{ $activityCount }} recorded</dd>
                </div>
            </dl>
        </section>
    </div>

    <div class="casefile-print-columns casefile-print-columns-wide">
        <section class="casefile-print-section">
            <div class="casefile-print-section-header">
                <h2>Dispute Statement</h2>
                <span>Student submission summary.</span>
            </div>
            <div class="casefile-print-prose">
                <h3>{{ $ticket['issue_type'] ?? 'General Dispute' }}</h3>
                <p>{{ $ticket['print_case_description'] ?? 'No detailed dispute statement was provided on the ticket.' }}</p>
            </div>
        </section>

        <section class="casefile-print-section">
            <div class="casefile-print-section-header">
                <h2>Admin Notes</h2>
                <span>Internal notes saved on the case.</span>
            </div>
            <div class="ticket-print-notes">{{ $printNotes }}</div>
        </section>
    </div>

    <footer class="casefile-signature-panel">
        <p>{{ $ticket['print_certification_statement'] ?? 'I certify that this case file reflects the dispute record available in the Grade Dispute System at the time of printing.' }}</p>
        <div class="casefile-signature-grid">
            <div class="casefile-signature-field">
                <span class="casefile-signature-label">Prepared by</span>
                <strong>{{ $ticket['print_generated_by_name'] ?? 'Administration' }}</strong>
                <span>{{ $ticket['print_generated_by_role'] ?? 'ADMIN' }}</span>
            </div>
            <div class="casefile-signature-field casefile-signature-line">
                <span class="casefile-signature-label">Signature</span>
            </div>
            <div class="casefile-signature-field">
                <span class="casefile-signature-label">Date</span>
                <strong data-print-timestamp>{{ now('Africa/Nairobi')->format('M j, Y g:i A') }}</strong>
                <span>Signed on print review</span>
            </div>
        </div>
        <div class="casefile-stamp-box">Official stamp / seal</div>
    </footer>
</article>

<div class="stats-container stats-grid-3 ticket-stats">
    <div class="stat-card escalation-stat ticket-stat-card">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">Current Status</span>
            <span class="stat-value stat-value-compact">{{ $ticket['status_label'] ?? 'Pending' }}</span>
            <span class="ticket-stat-meta">{{ $ticket['status_meta'] ?? 'Awaiting review' }}</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">warning</span>
        </div>
    </div>
    <div class="stat-card escalation-stat ticket-stat-card">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">Time Open</span>
            <span class="stat-value stat-value-compact">{{ $ticket['time_open_label'] ?? 'Not available' }}</span>
            <span class="ticket-stat-meta">{{ $ticket['time_open_meta'] ?? 'No review window available' }}</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">schedule</span>
        </div>
    </div>
    <div class="stat-card escalation-stat ticket-stat-card">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">Review Stage</span>
            <span class="stat-value stat-value-compact">{{ $ticket['review_stage'] ?? 'Pending Review' }}</span>
            <span class="ticket-stat-meta">{{ $ticket['review_stage_meta'] ?? 'Awaiting routing update' }}</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">gavel</span>
        </div>
    </div>
</div>

<div class="details-grid ticket-details-grid">
    <div class="details-left">
        <section class="detail-card">
            <div class="detail-card-header">Case Summary</div>
            <div class="detail-card-body">
                <div class="overview-grid ticket-overview-grid">
                    <span class="overview-label">Ticket ID</span>
                    <span class="overview-value">{{ $ticket['ticket_number'] ?? 'Not available' }}</span>

                    <span class="overview-label">Student</span>
                    <span class="overview-value">{{ ($ticket['student_name'] ?? 'Student').' ('.($ticket['student_id'] ?? 'Not available').')' }}</span>

                    <span class="overview-label">Lecturer</span>
                    <span class="overview-value">{{ $ticket['reviewer_name'] ?? 'Not assigned' }}</span>

                    <span class="overview-label">Course</span>
                    <span class="overview-value">{{ $ticket['course_label'] ?? 'General Appeal' }}</span>

                    <span class="overview-label">Issue Type</span>
                    <span class="overview-value">{{ $ticket['issue_type'] ?? 'General Dispute' }}</span>

                    <span class="overview-label">Submitted</span>
                    <span class="overview-value">{{ $ticket['submitted_at'] ?? 'Not available' }}</span>

                    <span class="overview-label">Escalation Trigger</span>
                    <span class="overview-value">{{ $ticket['escalation_trigger'] ?? 'Awaiting review context' }}</span>

                    <span class="overview-label">Resolution SLA</span>
                    <span class="overview-value {{ !empty($ticket['is_overdue']) ? 'ticket-alert-text' : '' }}">{{ $ticket['sla_label'] ?? 'Within review window' }}</span>
                </div>
            </div>
        </section>

        <section class="detail-card no-print">
            <div class="detail-card-header">Administrative Actions</div>
            <div class="detail-card-body">
                <div class="form-group">
                    <label for="adminTicketLecturerSelect">Lecturer reviewer</label>
                    <div class="input-action-group">
                        <select id="adminTicketLecturerSelect" class="form-control">
                            @forelse (($ticket['lecturer_options'] ?? []) as $option)
                                <option value="{{ $option['identifier'] }}" @selected(($ticket['selected_lecturer'] ?? '') === ($option['identifier'] ?? ''))>
                                    {{ $option['label'] }}@if (($ticket['selected_lecturer'] ?? '') === ($option['identifier'] ?? '')) (Current) @endif
                                </option>
                            @empty
                                <option value="">No lecturer mappings available</option>
                            @endforelse
                        </select>
                        <button class="btn btn-outline" id="adminTicketLecturerUpdateBtn" type="button">Update</button>
                    </div>
                    <span class="form-text">{{ $ticket['lecturer_help_text'] ?? 'Select the lecturer who should own the next review step.' }}</span>
                </div>

                <div class="form-group">
                    <label for="adminTicketStatusSelect">Override status</label>
                    <div class="input-action-group">
                        <select id="adminTicketStatusSelect" class="form-control">
                            @foreach (($ticket['status_options'] ?? []) as $option)
                                <option value="{{ $option['value'] }}" @selected(($ticket['selected_status'] ?? '') === ($option['value'] ?? ''))>
                                    {{ $option['label'] }}@if (($ticket['selected_status'] ?? '') === ($option['value'] ?? '')) (Current) @endif
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline" id="adminTicketStatusApplyBtn" type="button">Apply</button>
                    </div>
                </div>

                <div class="form-group form-group-compact">
                    <label for="adminTicketRoutingSelect">Route ticket to</label>
                    <div class="input-action-group">
                        <select id="adminTicketRoutingSelect" class="form-control">
                            @foreach (($ticket['route_options'] ?? []) as $option)
                                <option value="{{ $option['value'] }}" @selected(($ticket['selected_routing'] ?? '') === ($option['value'] ?? ''))>
                                    {{ $option['label'] }}@if (($ticket['selected_routing'] ?? '') === ($option['value'] ?? '')) (Current) @endif
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline" id="adminTicketRoutingBtn" type="button">Route</button>
                    </div>
                    <span class="form-text">{{ $ticket['routing_help_text'] ?? 'Choose the next review owner for this ticket.' }}</span>
                </div>

                <div class="form-actions ticket-inline-actions">
                    <button class="btn btn-outline" data-admin-ticket-action="Clarification request" type="button">Request Clarification</button>
                    <button class="btn btn-primary" id="adminTicketApplyChangesBtn" type="button">Apply Changes</button>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Internal Notes</div>
            <div class="detail-card-body">
                <div class="form-group form-group-compact">
                    <label for="adminInternalNotes">Notes visible to admins only</label>
                    <textarea id="adminInternalNotes" class="form-control" rows="6">{{ $ticket['internal_notes'] ?? '' }}</textarea>
                    <span class="form-text">Use this area for routing decisions, policy references, and internal follow-up reminders.</span>
                </div>

                <div class="form-actions ticket-inline-actions no-print">
                    <button class="btn btn-outline" data-admin-ticket-action="Internal follow-up" type="button">Add Follow-up</button>
                    <button class="btn btn-dark" id="adminTicketSaveNotesBtn" type="button">Save Notes</button>
                </div>
            </div>
        </section>
    </div>

    <div class="details-right">
        <section class="detail-card">
            <div class="detail-card-header">People Involved</div>
            <div class="detail-card-body">
                <div class="ticket-person-list">
                    @foreach (($ticket['people'] ?? []) as $person)
                        <div class="ticket-person-card">
                            <div class="user-profile-cell">
                                <div class="user-avatar"><span class="material-symbols-outlined">{{ $person['icon'] ?? 'person' }}</span></div>
                                <div class="user-info">
                                    <span class="user-name">{{ $person['name'] ?? 'Unknown User' }}</span>
                                    <span class="user-meta">{{ $person['meta'] ?? 'No profile details available' }}</span>
                                    <span class="user-meta user-meta-muted">{{ $person['secondary_meta'] ?? 'No recent activity' }}</span>
                                </div>
                            </div>
                            <span class="role-badge {{ $person['badge_class'] ?? 'role-outline' }}">{{ $person['role_label'] ?? 'USER' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Course Snapshot</div>
            <div class="detail-card-body">
                <div class="overview-grid ticket-overview-grid">
                    <span class="overview-label">Department</span>
                    <span class="overview-value">{{ $ticket['department_name'] ?? 'Not assigned' }}</span>

                    <span class="overview-label">Faculty</span>
                    <span class="overview-value">{{ $ticket['faculty_name'] ?? 'Not assigned' }}</span>

                    <span class="overview-label">Assessment</span>
                    <span class="overview-value">{{ $ticket['assessment_label'] ?? 'Grade Review Workflow' }}</span>

                    <span class="overview-label">Last Update</span>
                    <span class="overview-value">{{ $ticket['last_update_label'] ?? 'No recorded updates yet' }}</span>
                </div>

                @if (!empty($ticket['attachments']))
                    <div class="ticket-attachment-list">
                        @foreach (($ticket['attachments'] ?? []) as $attachment)
                            <a class="ticket-attachment-link" href="{{ url('/attachment/download/'.$attachment['id']) }}">
                                <span class="material-symbols-outlined">attach_file</span>
                                <span class="ticket-attachment-copy">
                                    <strong>{{ $attachment['label'] }}</strong>
                                    <span>{{ $attachment['uploaded_by'] }} • {{ $attachment['uploaded_at'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="ticket-card-note">No attachments uploaded for this ticket.</p>
                @endif
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Activity Timeline</div>
            <div class="detail-card-body">
                <div class="timeline-container ticket-timeline">
                    @foreach (($ticket['timeline'] ?? []) as $item)
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-box">
                                <div class="timeline-header">
                                    <h4>{{ $item['action'] ?? 'Activity' }}</h4>
                                    <span class="timeline-date">{{ $item['created_at'] ?? 'Not available' }}</span>
                                </div>
                                <span class="timeline-author">{{ $item['author'] ?? 'System' }}</span>
                                <p>{{ $item['comment'] ?? 'No additional details were recorded.' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
