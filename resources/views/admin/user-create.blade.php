@extends('layouts.app')

@section('title', 'Create New User | Grade Dispute')
@section('page_id', 'admin-user-create')
@section('page_title', 'Create New User')

@section('content')
<div class="page-header flex-header add-user-page-header">
    <div class="header-text">
        <span class="eyebrow">Admin / Manage Users / Add User</span>
        <h1>Create New User</h1>
        <p>Create student, lecturer, and administrator accounts using the same role-based structure as the rest of the portal.</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('portal.admin.users') }}" class="btn btn-outline btn-surface">
            <span class="material-symbols-outlined">arrow_back</span> Back to Users
        </a>
        <button class="btn btn-primary" type="button">
            <span class="material-symbols-outlined">upload_file</span> Bulk Import
        </button>
    </div>
</div>

<div class="details-grid add-user-layout">
    <div class="details-left">
        <form action="#" class="add-user-form">
            <section class="detail-card">
                <div class="detail-card-header">Profile Information</div>
                <div class="detail-card-body">
                    <p class="add-user-section-copy">Capture the user identity details exactly as they appear in university records.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name *</label>
                            <input id="first-name" type="text" class="form-control" placeholder="e.g. John" required>
                        </div>
                        <div class="form-group">
                            <label for="last-name">Last Name *</label>
                            <input id="last-name" type="text" class="form-control" placeholder="e.g. Doe" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email-address">Email Address *</label>
                            <input id="email-address" type="email" class="form-control" placeholder="e.g. jdoe@university.edu" required>
                        </div>
                        <div class="form-group">
                            <label for="university-id">University ID *</label>
                            <input id="university-id" type="text" class="form-control" placeholder="e.g. U100234" required>
                        </div>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-header">Role &amp; Assignment</div>
                <div class="detail-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="system-role">System Role *</label>
                            <select id="system-role" class="form-control" required>
                                <option value="" disabled selected>Select a role...</option>
                                @foreach (($adminUserFormMeta['roles'] ?? []) as $role)
                                    <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" class="form-control">
                                <option>None / Not Applicable</option>
                                @foreach (($adminUserFormMeta['departments'] ?? []) as $department)
                                    <option value="{{ $department }}">{{ $department }}</option>
                                @endforeach
                            </select>
                            <span class="form-text">Use a department for lecturers and support staff who need scoped access.</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="account-status">Account Status</label>
                            <select id="account-status" class="form-control">
                                @foreach (($adminUserFormMeta['statuses'] ?? []) as $status)
                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="office-location">Office / Campus</label>
                            <input id="office-location" type="text" class="form-control" placeholder="e.g. Main Campus - Block B">
                        </div>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-header">Security &amp; Notifications</div>
                <div class="detail-card-body">
                    <p class="add-user-section-copy">Choose how the account is activated and what the new user receives immediately after creation.</p>

                    <label class="settings-checkbox-wrapper add-user-toggle">
                        <input type="checkbox" checked>
                        <span class="checkbox-text">Send welcome email with password creation link</span>
                    </label>

                    <label class="settings-checkbox-wrapper add-user-toggle">
                        <input type="checkbox" checked>
                        <span class="checkbox-text">Require password reset on first sign-in</span>
                    </label>

                    <label class="settings-checkbox-wrapper add-user-toggle">
                        <input type="checkbox">
                        <span class="checkbox-text">Mark account for manual approval before activation</span>
                    </label>

                    <div class="form-actions add-user-actions">
                        <a href="{{ route('portal.admin.users') }}" class="btn btn-outline">
                            <span class="material-symbols-outlined">close</span> Cancel
                        </a>
                        <button type="button" class="btn btn-primary">
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
                        <p>Lecturer and support accounts should match the correct academic unit before access is granted.</p>
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
                    <span class="overview-value">Self-service creation link sent from the welcome workflow</span>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
