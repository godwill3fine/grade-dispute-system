@extends('layouts.app')

@section('title', 'Admin Create Ticket | Grade Dispute')
@section('page_id', 'admin-create-ticket')
@section('page_title', 'Create Ticket')

@section('content')
@php($ticketMeta = $adminCreateTicketMeta ?? [])

<div class="page-header flex-header create-ticket-page-header">
    <div class="header-text">
        <span class="eyebrow">Admin / Manage Tickets / Create Ticket</span>
        <h1>Create New Ticket</h1>
        <p>Open and route a dispute ticket on behalf of a student while capturing enough context for immediate review.</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('portal.admin.disputes') }}" class="btn btn-outline btn-surface">
            <span class="material-symbols-outlined">arrow_back</span> Back to Tickets
        </a>
        <button class="btn btn-primary" id="adminCreateTicketDraftBtn" form="adminCreateTicketForm" type="button">
            <span class="material-symbols-outlined">draft</span> Save Draft
        </button>
    </div>
</div>

<div class="details-grid create-ticket-layout">
    <div class="details-left">
        <form action="{{ url('/admin/disputes') }}" class="create-ticket-form" enctype="multipart/form-data" id="adminCreateTicketForm">
            <input id="adminCreateTicketSubmissionMode" name="submission_mode" type="hidden" value="submit">
            <input id="adminVerifiedStudentId" name="student_id" type="hidden" value="">

            <div class="detail-card">
                <div class="detail-card-header">Student & Course Details</div>
                <div class="detail-card-body">
                    <p class="create-ticket-section-copy">Verify the student record first so the ticket is created against the correct profile and academic route.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminStudentLookup">Student ID or Email *</label>
                            <div class="input-action-group">
                                <input id="adminStudentLookup" type="text" class="form-control" placeholder="e.g. SCT222-0163/2022 or student@campus.edu" required>
                                <button type="button" class="btn btn-outline btn-surface" id="adminStudentVerifyBtn">Verify</button>
                            </div>
                            <span class="form-text">Verification confirms the student profile before ticket creation.</span>
                        </div>
                        <div class="form-group">
                            <label for="adminStudentName">Student Name</label>
                            <input id="adminStudentName" type="text" class="form-control" placeholder="Auto-filled after verification" readonly>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminStudentEmail">Student Email</label>
                            <input id="adminStudentEmail" type="text" class="form-control" placeholder="Auto-filled after verification" readonly>
                        </div>
                        <div class="form-group">
                            <label for="adminStudentCourse">Student Course</label>
                            <input id="adminStudentCourse" type="text" class="form-control" placeholder="Auto-filled after verification" readonly>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminCreateTicketType">Ticket Type *</label>
                            <select id="adminCreateTicketType" class="form-control" name="type" required>
                                @foreach (($ticketMeta['ticket_types'] ?? []) as $option)
                                    <option value="{{ $option['value'] }}" @selected(($option['value'] ?? '') === 'unit')>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adminAcademicTerm">Academic Term</label>
                            <select id="adminAcademicTerm" class="form-control" name="academic_term">
                                @foreach (($ticketMeta['academic_terms'] ?? []) as $term)
                                    <option value="{{ $term }}">{{ $term }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row" data-admin-type-section="unit">
                        <div class="form-group">
                            <label for="adminUnitCodeInput">Course Code *</label>
                            <input id="adminUnitCodeInput" name="unit_code" type="text" class="form-control" list="adminUnitCodeList" placeholder="e.g. CS101" autocomplete="off">
                            <datalist id="adminUnitCodeList"></datalist>
                            <span class="form-text">Type a unit code or name to load matching routing details.</span>
                        </div>
                        <div class="form-group">
                            <label for="adminAssignedLecturer">Assign Lecturer</label>
                            <select id="adminAssignedLecturer" class="form-control" name="lecturer_id">
                                <option value="">Unassigned / Auto-route</option>
                            </select>
                            <span class="form-text">Leave this unassigned if the portal should auto-route using the unit mapping.</span>
                        </div>
                    </div>

                    <div class="form-row d-none" data-admin-type-section="general">
                        <div class="form-group">
                            <label for="adminGeneralRoute">Initial Review Route *</label>
                            <select id="adminGeneralRoute" class="form-control" name="assigned_role">
                                @foreach (($ticketMeta['general_route_options'] ?? []) as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adminStudentYear">Student Year</label>
                            <input id="adminStudentYear" type="text" class="form-control" placeholder="Auto-filled after verification" readonly>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminDepartmentRoute">Faculty / Department</label>
                            <select id="adminDepartmentRoute" class="form-control" name="department_name">
                                <option value="">Select department...</option>
                                @foreach (($ticketMeta['departments'] ?? []) as $department)
                                    <option value="{{ $department['name'] }}">{{ $department['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adminStudentFaculty">Faculty</label>
                            <input id="adminStudentFaculty" type="text" class="form-control" placeholder="Auto-filled after verification" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header">Dispute Information</div>
                <div class="detail-card-body">
                    <p class="create-ticket-section-copy">Capture the issue clearly enough for the first reviewer to understand the request without additional intake questions.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminIssueType">Issue Type</label>
                            <select id="adminIssueType" class="form-control" name="issue_type">
                                <option value="">Select an issue type...</option>
                                @foreach (($ticketMeta['issue_types'] ?? []) as $issueType)
                                    <option value="{{ $issueType }}">{{ $issueType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adminInitialPriority">Initial Priority *</label>
                            <select id="adminInitialPriority" class="form-control" name="priority" required>
                                @foreach (($ticketMeta['priority_options'] ?? []) as $priority)
                                    <option value="{{ $priority }}" @selected($priority === 'medium')>{{ ucfirst($priority) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="adminTicketTitle">Ticket Title *</label>
                        <input id="adminTicketTitle" name="title" type="text" class="form-control" placeholder="e.g. CS101 midterm grade calculation mismatch" required>
                    </div>

                    <div class="form-group">
                        <label for="adminTicketDescription">Dispute Description *</label>
                        <textarea id="adminTicketDescription" name="description" class="form-control create-ticket-textarea" placeholder="Provide a clear description of the student's dispute, relevant grading concern, and any urgency notes..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="adminTicketAttachments">Attachments</label>
                        <input id="adminTicketAttachments" class="d-none" type="file" name="attachments[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
                        <div class="file-upload-zone" id="adminTicketAttachmentDropzone" tabindex="0">
                            <span class="material-symbols-outlined">description</span>
                            <p class="file-upload-title">Drag and drop files here, or click to browse</p>
                            <p class="file-upload-caption">Supports PDF, DOC, DOCX, JPG, and PNG up to 5MB each.</p>
                            <button type="button" class="btn btn-outline btn-surface" id="adminTicketAttachmentBrowseBtn">Browse Files</button>
                        </div>
                        <div class="ticket-attachment-list create-ticket-upload-list d-none" id="adminTicketAttachmentList"></div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header">Notifications & Submission</div>
                <div class="detail-card-body">
                    <p class="create-ticket-section-copy">Choose who gets notified immediately and whether the case should move straight into the live review queue.</p>

                    <label class="settings-checkbox-wrapper create-ticket-toggle">
                        <input id="adminNotifyStudentToggle" name="notify_student" type="checkbox" checked>
                        <span class="checkbox-text">Send a notification to the student after the ticket is created</span>
                    </label>

                    <label class="settings-checkbox-wrapper create-ticket-toggle">
                        <input id="adminNotifyAssigneeToggle" name="notify_assignee" type="checkbox" checked>
                        <span class="checkbox-text">Send a notification to the assigned reviewer when the ticket is routed</span>
                    </label>

                    <label class="settings-checkbox-wrapper create-ticket-toggle">
                        <input id="adminRouteImmediatelyToggle" name="route_immediately" type="checkbox" checked>
                        <span class="checkbox-text">Route the ticket into the active review queue immediately</span>
                    </label>

                    <label class="settings-checkbox-wrapper create-ticket-toggle">
                        <input id="adminFollowUpToggle" name="admin_follow_up" type="checkbox">
                        <span class="checkbox-text">Flag this case for admin follow-up after the first reviewer responds</span>
                    </label>

                    <div class="form-actions create-ticket-actions">
                        <p class="auth-feedback mb-0" id="adminCreateTicketFeedback"></p>
                        <div class="header-actions">
                            <a href="{{ route('portal.admin.disputes') }}" class="btn btn-outline">
                                <span class="material-symbols-outlined">close</span> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="adminCreateTicketSubmitBtn">
                                <span class="material-symbols-outlined">save</span> Create Ticket
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="details-right">
        <div class="detail-card">
            <div class="detail-card-header">Routing Snapshot</div>
            <div class="detail-card-body">
                <div class="overview-grid create-ticket-overview-grid">
                    <span class="overview-label">Ticket Status</span>
                    <span class="overview-value" id="adminCreateTicketSnapshotStatus">Pending intake</span>

                    <span class="overview-label">Default Queue</span>
                    <span class="overview-value" id="adminCreateTicketSnapshotQueue">Admin intake</span>

                    <span class="overview-label">Priority</span>
                    <span class="overview-value" id="adminCreateTicketSnapshotPriority">Medium</span>

                    <span class="overview-label">Notifications</span>
                    <span class="overview-value" id="adminCreateTicketSnapshotNotifications">Student and reviewer alerts enabled</span>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-card-header">What Happens Next</div>
            <div class="detail-card-body">
                <div class="create-ticket-note-list">
                    <div class="create-ticket-note">
                        <h4>Student record check</h4>
                        <p>Verify the identity field before submission so the case history attaches to the correct student account.</p>
                    </div>
                    <div class="create-ticket-note">
                        <h4>Routing rules</h4>
                        <p>If no lecturer is selected, the portal will try the unit mapping first and only fall back to manual selection when multiple mappings remain.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-card-header">Creation Checklist</div>
            <div class="detail-card-body">
                <div class="create-ticket-note-list">
                    <div class="create-ticket-note">
                        <h4>Required before submit</h4>
                        <p>Verified student, ticket type, priority, title, and dispute description are all mandatory.</p>
                    </div>
                    <div class="create-ticket-note">
                        <h4>Recommended evidence</h4>
                        <p>Attach mark sheets, email threads, or screenshots if the case is likely to need fast escalation.</p>
                    </div>
                    <div class="create-ticket-note">
                        <h4>Admin ownership</h4>
                        <p>Use Save Draft or disable immediate routing when the ticket should stay in the admin intake queue first.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script id="adminCreateTicketMeta" type="application/json">@json($ticketMeta)</script>
@endsection
