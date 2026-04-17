@extends('layouts.app')

@php
    $roleBadgeClass = in_array(($adminUserEdit['role'] ?? ''), ['staff', 'admin'], true) ? 'role-dark' : 'role-outline';
    $statusDotClass = match ($adminUserEdit['status'] ?? '') {
        'pending invite' => 'pending',
        'active' => 'active',
        default => 'inactive',
    };
@endphp

@section('title', 'Edit User Profile | Grade Dispute')
@section('page_id', 'admin-user-edit')
@section('page_title', 'Edit User Profile')

@section('content')
<div class="page-header flex-header edit-user-page-header">
    <div class="header-text">
        <span class="eyebrow">Admin / Manage Users / Edit User / {{ $adminUserEdit['identifier'] }}</span>
        <h1>Edit User Profile</h1>
        <p>Review identity details, adjust role assignments, and manage account access for {{ $adminUserEdit['name'] }}.</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('portal.admin.users') }}" class="btn btn-outline btn-surface">
            <span class="material-symbols-outlined">arrow_back</span> Back to Users
        </a>
        <button class="btn btn-outline btn-surface" id="adminUserEditSendPasswordResetBtn" type="button">
            <span class="material-symbols-outlined">key</span> Send Password Reset
        </button>
    </div>
</div>

<div class="details-grid edit-user-layout">
    <div class="details-left">
        <form
            action="{{ route('admin.users.update', ['identifier' => $adminUserEdit['identifier']]) }}"
            class="edit-user-form"
            id="adminUserEditForm"
            data-action-url="{{ route('admin.users.actions', ['identifier' => $adminUserEdit['identifier']]) }}"
            data-reload-url="{{ route('portal.admin.users.edit', ['identifier' => $adminUserEdit['identifier']]) }}"
        >
            <section class="detail-card">
                <div class="detail-card-header">Profile Information</div>
                <div class="detail-card-body">
                    <div class="alert alert-danger d-none" id="adminUserEditFeedback" role="alert"></div>
                    <p class="edit-user-section-copy">Keep names and contact information aligned with official university records to avoid duplicate or mismatched accounts.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminUserEditFirstName">First Name *</label>
                            <input id="adminUserEditFirstName" name="first_name" type="text" class="form-control" value="{{ $adminUserEdit['first_name'] }}" required>
                        </div>
                        <div class="form-group">
                            <label for="adminUserEditLastName">Last Name *</label>
                            <input id="adminUserEditLastName" name="last_name" type="text" class="form-control" value="{{ $adminUserEdit['last_name'] }}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminUserEditEmail">Email Address *</label>
                            <input id="adminUserEditEmail" name="email" type="email" class="form-control" value="{{ $adminUserEdit['email'] }}" required>
                        </div>
                        <div class="form-group">
                            <label for="adminUserEditIdentifier">University ID *</label>
                            <input id="adminUserEditIdentifier" name="identifier" type="text" class="form-control" value="{{ $adminUserEdit['identifier'] }}" readonly>
                            <span class="form-text">IDs are locked after creation to preserve audit integrity.</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-header">Role &amp; Assignment</div>
                <div class="detail-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminUserEditRole">System Role *</label>
                            <select id="adminUserEditRole" name="role" class="form-control" required>
                                @foreach (($adminUserFormMeta['roles'] ?? []) as $role)
                                    <option value="{{ $role }}" @selected($role === ($adminUserEdit['role'] ?? ''))>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adminUserEditDepartment">Department</label>
                            <select id="adminUserEditDepartment" name="department" class="form-control" data-required-roles="lecturer,staff">
                                <option value="">None / Not Applicable</option>
                                @foreach (($adminUserFormMeta['departments'] ?? []) as $department)
                                    <option value="{{ $department }}" @selected($department === ($adminUserEdit['department'] ?? ''))>{{ $department }}</option>
                                @endforeach
                            </select>
                            <span class="form-text" id="adminUserEditDepartmentHelp">Use a department for lecturers and staff reviewers who need scoped access.</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adminUserEditOfficeLocation">Office / Campus</label>
                            <input id="adminUserEditOfficeLocation" name="office_location" type="text" class="form-control" value="{{ $adminUserEdit['office_location'] }}">
                        </div>
                        <div class="form-group">
                            <label for="adminUserEditResponsibility">Access Focus</label>
                            <input id="adminUserEditResponsibility" type="text" class="form-control" value="{{ $adminUserEdit['responsibility'] }}" readonly>
                            <span class="form-text">This summary is derived from the selected role and profile data.</span>
                        </div>
                    </div>

                    <div class="edit-user-role-fields" data-role-section="student" @if(($adminUserEdit['role'] ?? '') !== 'student') hidden @endif>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adminUserEditCourse">Course *</label>
                                <input id="adminUserEditCourse" name="course" type="text" class="form-control" value="{{ $adminUserEdit['course'] }}" data-required-roles="student">
                            </div>
                            <div class="form-group">
                                <label for="adminUserEditYear">Year *</label>
                                <input id="adminUserEditYear" name="year" type="number" min="1" max="12" step="1" class="form-control" value="{{ $adminUserEdit['year'] }}" data-required-roles="student">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="adminUserEditFaculty">Faculty</label>
                                <input id="adminUserEditFaculty" name="faculty" type="text" class="form-control" value="{{ $adminUserEdit['faculty'] === 'Not assigned' ? '' : $adminUserEdit['faculty'] }}">
                            </div>
                        </div>
                    </div>

                    <div class="edit-user-role-fields" data-role-section="staff" @if(($adminUserEdit['role'] ?? '') !== 'staff') hidden @endif>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adminUserEditStaffRole">Staff Responsibility *</label>
                                <select id="adminUserEditStaffRole" name="staff_role" class="form-control" data-required-roles="staff">
                                    <option value="">Select a staff responsibility...</option>
                                    @foreach (($adminUserFormMeta['staff_roles'] ?? []) as $staffRole)
                                        <option value="{{ $staffRole }}" @selected($staffRole === ($adminUserEdit['staff_role'] ?? ''))>{{ ucwords(str_replace('_', ' ', $staffRole)) }}</option>
                                    @endforeach
                                </select>
                                <span class="form-text">Choose which elevated lecturer queue this staff account should receive.</span>
                            </div>
                        </div>
                    </div>

                    <div class="edit-user-role-fields" data-role-section="lecturer,staff,admin" @if(!in_array(($adminUserEdit['role'] ?? ''), ['lecturer', 'staff', 'admin'], true)) hidden @endif>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adminUserEditSalutation">Salutation</label>
                                <input id="adminUserEditSalutation" name="salutation" type="text" class="form-control" value="{{ $adminUserEdit['salutation'] }}" placeholder="e.g. Dr., Prof., Mr., Ms.">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-header">Status &amp; Access</div>
                <div class="detail-card-body">
                    <p class="edit-user-section-copy">Adjust access carefully. Changes here take effect immediately for the user's next session.</p>

                    <div class="form-group">
                        <label>Account Status</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="status" value="active" @checked(($adminUserEdit['status'] ?? '') === 'active')>
                                Active
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="pending invite" @checked(($adminUserEdit['status'] ?? '') === 'pending invite')>
                                Pending Invite
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="inactive" @checked(($adminUserEdit['status'] ?? '') === 'inactive')>
                                Inactive
                            </label>
                        </div>
                    </div>

                    <label class="settings-checkbox-wrapper edit-user-toggle">
                        <input id="adminUserEditRequirePasswordReset" name="require_password_reset" type="checkbox" @checked($adminUserEdit['require_password_reset'] ?? false)>
                        <span class="checkbox-text">Require password reset on next sign-in</span>
                    </label>

                    <label class="settings-checkbox-wrapper edit-user-toggle">
                        <input id="adminUserEditManualApproval" name="manual_approval_required" type="checkbox" @checked($adminUserEdit['manual_approval_required'] ?? false)>
                        <span class="checkbox-text">Require manual approval before activation</span>
                    </label>

                    <label class="settings-checkbox-wrapper edit-user-toggle">
                        <input id="adminUserEditSendAccountEmail" name="send_account_email" type="checkbox">
                        <span class="checkbox-text">Send updated account email after saving</span>
                    </label>

                    <p class="edit-user-action-note" id="adminUserEditSecurityNote">
                        Password reset emails route the user into the existing self-service reset flow.
                    </p>

                    <div class="form-actions edit-user-actions">
                        <a href="{{ route('portal.admin.users') }}" class="btn btn-outline">
                            <span class="material-symbols-outlined">close</span> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="adminUserEditSaveBtn">
                            <span class="material-symbols-outlined">save</span> Save Changes
                        </button>
                    </div>
                </div>
            </section>
        </form>
    </div>

    <div class="details-right">
        <section class="detail-card">
            <div class="detail-card-header">User Snapshot</div>
            <div class="detail-card-body">
                <div class="user-profile-cell">
                    <div class="user-avatar"><span class="material-symbols-outlined">{{ $adminUserEdit['icon'] }}</span></div>
                    <div class="user-info">
                        <span class="user-name">{{ $adminUserEdit['name'] }}</span>
                        <span class="user-meta">{{ $adminUserEdit['email'] }}</span>
                        <span class="user-meta user-meta-muted">{{ $adminUserEdit['identifier'] }}</span>
                    </div>
                </div>

                <div class="edit-user-status-row">
                    <span class="role-badge {{ $roleBadgeClass }}">{{ $adminUserEdit['role_label'] }}</span>
                    <div class="status-indicator"><span class="status-dot {{ $statusDotClass }}"></span> {{ $adminUserEdit['status_label'] }}</div>
                </div>

                <div class="overview-grid edit-user-overview-grid">
                    <span class="overview-label">Department</span>
                    <span class="overview-value">{{ $adminUserEdit['department'] }}</span>

                    <span class="overview-label">Faculty</span>
                    <span class="overview-value">{{ $adminUserEdit['faculty'] }}</span>

                    <span class="overview-label">Campus</span>
                    <span class="overview-value">{{ $adminUserEdit['campus'] }}</span>

                    <span class="overview-label">Focus</span>
                    <span class="overview-value">{{ $adminUserEdit['responsibility'] }}</span>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Audit Information</div>
            <div class="detail-card-body">
                <div class="overview-grid edit-user-overview-grid">
                    <span class="overview-label">Created</span>
                    <span class="overview-value">{{ $adminUserEdit['created_at'] }}</span>

                    <span class="overview-label">Last Login</span>
                    <span class="overview-value">{{ $adminUserEdit['last_login'] }}</span>

                    <span class="overview-label">Status</span>
                    <span class="overview-value">{{ $adminUserEdit['status_label'] }}</span>

                    <span class="overview-label">Updated</span>
                    <span class="overview-value">{{ $adminUserEdit['last_updated'] }}</span>

                    <span class="overview-label">Password Reset</span>
                    <span class="overview-value">{{ ($adminUserEdit['require_password_reset'] ?? false) ? 'Required on next sign-in' : 'Not currently required' }}</span>

                    <span class="overview-label">Account Email</span>
                    <span class="overview-value">{{ $adminUserEdit['welcome_email_sent_at'] }}</span>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Account Actions</div>
            <div class="detail-card-body">
                <div class="edit-user-action-list">
                    <button class="btn btn-outline btn-surface" id="adminUserEditResendAccountEmailBtn" type="button">
                        <span class="material-symbols-outlined">mail</span> Resend Account Email
                    </button>
                    <button class="btn btn-outline btn-surface" id="adminUserEditResetLoginFactorsBtn" type="button">
                        <span class="material-symbols-outlined">lock_reset</span> Reset MFA / Login Factors
                    </button>
                    <button class="btn btn-danger-outline" id="adminUserEditDeactivateBtn" type="button">
                        <span class="material-symbols-outlined">block</span> Deactivate User
                    </button>
                </div>
                <p class="edit-user-action-note">Deactivation removes active access immediately, but preserves historical dispute records and audit entries.</p>
            </div>
        </section>
    </div>
</div>
@endsection
