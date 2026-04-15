@extends('layouts.app')

@php
    $roleBadgeClass = ($adminUserEdit['role'] ?? '') === 'admin' ? 'role-dark' : 'role-outline';
    $statusDotClass = ($adminUserEdit['status'] ?? '') === 'active' ? 'active' : 'inactive';
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
        <button class="btn btn-outline btn-surface" type="button">
            <span class="material-symbols-outlined">key</span> Send Password Reset
        </button>
    </div>
</div>

<div class="details-grid edit-user-layout">
    <div class="details-left">
        <form action="#" class="edit-user-form">
            <section class="detail-card">
                <div class="detail-card-header">Profile Information</div>
                <div class="detail-card-body">
                    <p class="edit-user-section-copy">Keep names and contact information aligned with official university records to avoid duplicate or mismatched accounts.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name *</label>
                            <input id="first-name" type="text" class="form-control" value="{{ $adminUserEdit['first_name'] }}" required>
                        </div>
                        <div class="form-group">
                            <label for="last-name">Last Name *</label>
                            <input id="last-name" type="text" class="form-control" value="{{ $adminUserEdit['last_name'] }}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email-address">Email Address *</label>
                            <input id="email-address" type="email" class="form-control" value="{{ $adminUserEdit['email'] }}" required>
                        </div>
                        <div class="form-group">
                            <label for="university-id">University ID *</label>
                            <input id="university-id" type="text" class="form-control" value="{{ $adminUserEdit['identifier'] }}" readonly>
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
                            <label for="system-role">System Role *</label>
                            <select id="system-role" class="form-control" required>
                                @foreach (($adminUserFormMeta['roles'] ?? []) as $role)
                                    <option value="{{ $role }}" @selected($role === ($adminUserEdit['role'] ?? ''))>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" class="form-control">
                                <option>None / Not Applicable</option>
                                @foreach (($adminUserFormMeta['departments'] ?? []) as $department)
                                    <option value="{{ $department }}" @selected($department === ($adminUserEdit['department'] ?? ''))>{{ $department }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="office-location">Office / Campus</label>
                            <input id="office-location" type="text" class="form-control" value="{{ $adminUserEdit['campus'] }}">
                        </div>
                        <div class="form-group">
                            <label for="responsibility-area">Primary Responsibility</label>
                            <input id="responsibility-area" type="text" class="form-control" value="{{ $adminUserEdit['responsibility'] }}">
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
                                <input type="radio" name="account_status" value="active" @checked(($adminUserEdit['status'] ?? '') === 'active')>
                                Active
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="account_status" value="inactive" @checked(($adminUserEdit['status'] ?? '') === 'inactive')>
                                Inactive
                            </label>
                            <label class="radio-option text-danger">
                                <input type="radio" name="account_status" value="suspended">
                                Suspended
                            </label>
                        </div>
                    </div>

                    <label class="settings-checkbox-wrapper edit-user-toggle">
                        <input type="checkbox" checked>
                        <span class="checkbox-text">Require password reset on next sign-in</span>
                    </label>

                    <label class="settings-checkbox-wrapper edit-user-toggle">
                        <input type="checkbox" checked>
                        <span class="checkbox-text">Send user notifications for newly assigned ticket activity</span>
                    </label>

                    <label class="settings-checkbox-wrapper edit-user-toggle">
                        <input type="checkbox">
                        <span class="checkbox-text">Temporarily block access to new dispute assignments</span>
                    </label>

                    <div class="form-actions edit-user-actions">
                        <a href="{{ route('portal.admin.users') }}" class="btn btn-outline">
                            <span class="material-symbols-outlined">close</span> Cancel
                        </a>
                        <button type="button" class="btn btn-primary">
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
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Account Actions</div>
            <div class="detail-card-body">
                <div class="edit-user-action-list">
                    <button class="btn btn-outline btn-surface" type="button">
                        <span class="material-symbols-outlined">mail</span> Resend Account Email
                    </button>
                    <button class="btn btn-outline btn-surface" type="button">
                        <span class="material-symbols-outlined">lock_reset</span> Reset MFA / Login Factors
                    </button>
                    <button class="btn btn-danger-outline" type="button">
                        <span class="material-symbols-outlined">block</span> Deactivate User
                    </button>
                </div>
                <p class="edit-user-action-note">Deactivation removes active access immediately, but preserves historical dispute records and audit entries.</p>
            </div>
        </section>
    </div>
</div>
@endsection
