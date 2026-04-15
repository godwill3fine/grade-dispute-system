@extends('layouts.app')

@section('title', (($adminManageTicket['page_title'] ?? 'Manage Ticket').' | Grade Dispute'))
@section('page_id', 'admin-manage-ticket')
@section('page_title', 'Manage Ticket')
@section('dispute_id', (string) ($adminManageTicket['id'] ?? ''))

@section('content')
@php($ticket = $adminManageTicket ?? [])

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
    <div class="header-actions">
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

        <section class="detail-card">
            <div class="detail-card-header">Administrative Actions</div>
            <div class="detail-card-body">
                <div class="form-group">
                    <label for="adminTicketLecturerSelect">Reassign lecturer</label>
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
                        <button class="btn btn-outline" data-admin-ticket-action="Lecturer reassignment" type="button">Update</button>
                    </div>
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
                        <button class="btn btn-outline" data-admin-ticket-action="Status override" type="button">Apply</button>
                    </div>
                </div>

                <div class="form-group form-group-compact">
                    <label for="adminTicketRoutingSelect">Escalation routing</label>
                    <div class="input-action-group">
                        <select id="adminTicketRoutingSelect" class="form-control">
                            @foreach (($ticket['route_options'] ?? []) as $option)
                                <option value="{{ $option['value'] }}" @selected(($ticket['selected_routing'] ?? '') === ($option['value'] ?? ''))>
                                    {{ $option['label'] }}@if (($ticket['selected_routing'] ?? '') === ($option['value'] ?? '')) (Current) @endif
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline" data-admin-ticket-action="Routing update" type="button">Route</button>
                    </div>
                </div>

                <div class="form-actions ticket-inline-actions">
                    <button class="btn btn-outline" data-admin-ticket-action="Clarification request" type="button">Request Clarification</button>
                    <button class="btn btn-primary" data-admin-ticket-action="Admin change set" type="button">Apply Changes</button>
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

                <div class="form-actions ticket-inline-actions">
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
