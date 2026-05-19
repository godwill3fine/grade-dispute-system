@extends('layouts.app')

@section('title', 'Create New User | Grade Dispute')
@section('page_id', 'admin-user-create')
@section('page_title', 'Create New User')

@section('content')
<div class="page-header flex-header add-user-page-header">
    <div class="header-text">
        <span class="eyebrow">Admin / Manage Users / Add User</span>
        <h1>Create New User</h1>
        <p>Create student, lecturer, staff, and administrator accounts using the same role-based structure as the rest of the portal.</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('portal.admin.users') }}" class="btn btn-outline btn-surface">
            <span class="material-symbols-outlined">arrow_back</span> Back to Users
        </a>
        <button class="btn btn-primary" id="adminUserCreateImportBtn" type="button">
            <span class="material-symbols-outlined">upload_file</span> Bulk Import
        </button>
        <input id="adminUserCreateImportInput" type="file" accept=".csv,text/csv" class="d-none" aria-hidden="true">
    </div>
</div>

<div class="details-grid add-user-layout">
    <div class="details-left">
        <form
            action="{{ route('admin.users.store') }}"
            method="post"
            class="add-user-form"
            id="adminUserCreateForm"
            data-redirect-url="{{ route('portal.admin.users') }}"
        >
            <section class="detail-card">
                <div class="detail-card-header">Profile Information</div>
                <div class="detail-card-body">
                    <div class="alert alert-danger d-none" id="adminUserCreateFeedback" role="alert"></div>
                    <p class="add-user-section-copy">Capture the user identity details exactly as they appear in university records.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminUserFirstName">First Name *</label>
                            <input id="adminUserFirstName" name="first_name" type="text" class="form-control" placeholder="e.g. John" required>
                        </div>
                        <div class="form-group">
                            <label for="adminUserLastName">Last Name *</label>
                            <input id="adminUserLastName" name="last_name" type="text" class="form-control" placeholder="e.g. Doe" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminUserEmail">Email Address *</label>
                            <input id="adminUserEmail" name="email" type="email" class="form-control" placeholder="e.g. jdoe@university.edu" required>
                        </div>
                        <div class="form-group">
                            <label for="adminUserIdentifier">University ID *</label>
                            <input id="adminUserIdentifier" name="identifier" type="text" class="form-control" placeholder="e.g. U100234" required>
                        </div>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-header">Role &amp; Assignment</div>
                <div class="detail-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminUserRole">System Role *</label>
                            <select id="adminUserRole" name="role" class="form-control" required>
                                <option value="" disabled selected>Select a role...</option>
                                @foreach (($adminUserFormMeta['roles'] ?? []) as $role)
                                    <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adminUserDepartment">Department</label>
                            <select id="adminUserDepartment" name="department" class="form-control" data-required-roles="lecturer,staff">
                                <option value="">None / Not Applicable</option>
                                @foreach (($adminUserFormMeta['departments'] ?? []) as $department)
                                    <option value="{{ $department }}">{{ $department }}</option>
                                @endforeach
                            </select>
                            <span class="form-text" id="adminUserDepartmentHelp">Use a department for lecturers and staff reviewers who need scoped access.</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminUserStatus">Account Status</label>
                            <select id="adminUserStatus" name="status" class="form-control">
                                @foreach (($adminUserFormMeta['statuses'] ?? []) as $status)
                                    <option value="{{ $status }}" @selected($status === 'active')>{{ ucwords($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adminUserOfficeLocation">Office / Campus</label>
                            <input id="adminUserOfficeLocation" name="office_location" type="text" class="form-control" placeholder="e.g. Main Campus - Block B">
                        </div>
                    </div>

                    <div class="add-user-role-fields" data-role-section="student" hidden>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adminUserCourse">Course *</label>
                                <select id="adminUserCourse" name="course" class="form-control" data-required-roles="student">
                                    <option value="">Select a course...</option>
                                    @foreach (($adminUserFormMeta['courses'] ?? []) as $course)
                                        <option value="{{ $course['code'] }}">{{ $course['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="adminUserYear">Year *</label>
                                <input id="adminUserYear" name="year" type="number" min="1" max="12" step="1" class="form-control" placeholder="e.g. 2" data-required-roles="student">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="adminUserFaculty">Faculty</label>
                                <input id="adminUserFaculty" name="faculty" type="text" class="form-control" placeholder="e.g. COPAS">
                                <span class="form-text">Add faculty when you want student records grouped beyond the department label.</span>
                            </div>
                        </div>
                    </div>

                    <div class="add-user-role-fields" data-role-section="staff" hidden>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adminUserStaffRole">Staff Responsibility *</label>
                                <select id="adminUserStaffRole" name="staff_role" class="form-control" data-required-roles="staff">
                                    <option value="">Select a staff responsibility...</option>
                                    @foreach (($adminUserFormMeta['staff_roles'] ?? []) as $staffRole)
                                        <option value="{{ $staffRole }}">{{ ucwords(str_replace('_', ' ', $staffRole)) }}</option>
                                    @endforeach
                                </select>
                                <span class="form-text">Choose which elevated lecturer queue this staff account should receive.</span>
                            </div>
                        </div>
                    </div>

                    <div class="add-user-role-fields" data-role-section="lecturer,staff,admin" hidden>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adminUserSalutation">Salutation</label>
                                <input id="adminUserSalutation" name="salutation" type="text" class="form-control" placeholder="e.g. Dr., Prof., Mr., Ms.">
                                <span class="form-text">Optional title used in lecturer, staff, and admin profiles.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-header">Security &amp; Notifications</div>
                <div class="detail-card-body">
                    <p class="add-user-section-copy">Choose how the account is activated and what the new user receives immediately after creation.</p>

                    <label class="settings-checkbox-wrapper add-user-toggle">
                        <input id="adminUserSendWelcomeEmail" name="send_welcome_email" type="checkbox" checked>
                        <span class="checkbox-text">Send welcome email with password creation link</span>
                    </label>

                    <label class="settings-checkbox-wrapper add-user-toggle">
                        <input id="adminUserRequirePasswordReset" name="require_password_reset" type="checkbox" checked>
                        <span class="checkbox-text">Require password reset on first sign-in</span>
                    </label>

                    <label class="settings-checkbox-wrapper add-user-toggle">
                        <input id="adminUserManualApproval" name="manual_approval_required" type="checkbox">
                        <span class="checkbox-text">Mark account for manual approval before activation</span>
                    </label>

                    <p class="ticket-card-note" id="adminUserSecurityNote">
                        If the welcome email is skipped, the temporary password defaults to the University ID.
                    </p>

                    <div class="form-actions add-user-actions">
                        <a href="{{ route('portal.admin.users') }}" class="btn btn-outline">
                            <span class="material-symbols-outlined">close</span> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="adminUserCreateSubmitBtn">
                            <span class="material-symbols-outlined">person_add</span> Create User
                        </button>
                    </div>
                </div>
            </section>
        </form>
    </div>

    <div class="details-right">
        <section class="detail-card">
            <div class="detail-card-header">Role Guide</div>
            <div class="detail-card-body">
                <div class="add-user-role-list">
                    <div class="add-user-role-card">
                        <div class="user-avatar"><span class="material-symbols-outlined">school</span></div>
                        <div class="user-info">
                            <span class="user-name">Student</span>
                            <span class="user-meta">Can submit disputes, upload evidence, and track ticket updates.</span>
                        </div>
                    </div>
                    <div class="add-user-role-card">
                        <div class="user-avatar"><span class="material-symbols-outlined">co_present</span></div>
                        <div class="user-info">
                            <span class="user-name">Lecturer</span>
                            <span class="user-meta">Reviews assigned tickets, posts responses, and closes decisions.</span>
                        </div>
                    </div>
                    <div class="add-user-role-card">
                        <div class="user-avatar"><span class="material-symbols-outlined">shield</span></div>
                        <div class="user-info">
                            <span class="user-name">Staff</span>
                            <span class="user-meta">Uses the lecturer workspace with elevated HOD or Exam Officer dispute-review privileges.</span>
                        </div>
                    </div>
                    <div class="add-user-role-card">
                        <div class="user-avatar"><span class="material-symbols-outlined">shield</span></div>
                        <div class="user-info">
                            <span class="user-name">Admin</span>
                            <span class="user-meta">Manages users, system settings, escalations, and case routing.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Provisioning Checklist</div>
            <div class="detail-card-body">
                <div class="add-user-note-list">
                    <div class="add-user-note">
                        <h4>Use official contacts</h4>
                        <p>Prefer institutional email addresses so onboarding notices are not filtered or rejected.</p>
                    </div>
                    <div class="add-user-note">
                        <h4>Confirm department mapping</h4>
                        <p>Lecturer and staff reviewer accounts should match the correct academic unit before access is granted.</p>
                    </div>
                    <div class="add-user-note">
                        <h4>Keep IDs unique</h4>
                        <p>University IDs should match the registrar source to avoid duplicate portal identities.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Access Preview</div>
            <div class="detail-card-body">
                <div class="overview-grid add-user-overview-grid">
                    <span class="overview-label">Landing Page</span>
                    <span class="overview-value">Role-specific dashboard after first sign-in</span>

                    <span class="overview-label">Notifications</span>
                    <span class="overview-value">Email alerts plus in-app updates when enabled</span>

                    <span class="overview-label">Password Setup</span>
                    <span class="overview-value" id="adminUserPasswordSetupPreview">Welcome email can point the user into the password setup flow; otherwise the temporary password defaults to the University ID.</span>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
