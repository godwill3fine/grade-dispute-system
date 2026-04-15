@extends('layouts.app')

@section('title', 'Account Settings | Grade Dispute')
@section('page_id', 'student-account-settings')
@section('page_title', 'Account Settings')

@section('content')
<div class="page-header flex-header student-settings-page-header">
    <div class="header-text">
        <span class="eyebrow">Student / Account Settings</span>
        <h1>Account Settings</h1>
        <p>Manage contact details, password security, and notification preferences for your dispute portal account.</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('portal.student.profile') }}" class="btn btn-outline btn-surface">
            <span class="material-symbols-outlined">person</span> View Profile
        </a>
    </div>
</div>

<div class="details-grid student-settings-layout">
    <div class="details-left">
        <section class="detail-card">
            <div class="detail-card-header">Contact Information</div>
            <div class="detail-card-body">
                <p class="student-settings-section-copy">Use an email address you actively monitor so you do not miss ticket status changes, lecturer responses, or final resolution notices.</p>
                <form action="#">
                    <div class="form-group">
                        <label for="primary-email">Primary Email Address</label>
                        <input id="primary-email" type="email" class="form-control" value="{{ $studentProfile['email'] }}">
                        <span class="form-text">This address receives dispute updates, escalations, and resolution notices.</span>
                    </div>

                    <div class="form-actions student-settings-actions">
                        <button type="button" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span> Update Contact
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Security</div>
            <div class="detail-card-body">
                <p class="student-settings-section-copy">Choose a strong password and review session activity whenever you make changes to your account.</p>
                <div class="student-settings-status-row">
                    <span class="badge-role-dark">Password Active</span>
                    <span class="badge-status-outline">Last sign-in {{ $studentProfile['last_sign_in'] }}</span>
                </div>
                <form action="#">
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <input id="current-password" type="password" class="form-control" placeholder="Enter current password">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-password">New Password</label>
                            <input id="new-password" type="password" class="form-control" placeholder="Create a new password">
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Confirm New Password</label>
                            <input id="confirm-password" type="password" class="form-control" placeholder="Repeat the new password">
                        </div>
                    </div>
                    <label class="student-settings-toggle" for="sign-out-sessions">
                        <input id="sign-out-sessions" type="checkbox" checked>
                        <span>Sign out of other active sessions after changing my password.</span>
                    </label>
                    <div class="form-actions student-settings-actions">
                        <button type="button" class="btn btn-primary">
                            <span class="material-symbols-outlined">lock_reset</span> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Preferences</div>
            <div class="detail-card-body">
                <p class="student-settings-section-copy">Set how the portal communicates with you and which reminders you want surfaced while a ticket is in progress.</p>
                <form action="#">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="language">Language</label>
                            <select id="language" class="form-control">
                                <option selected>English</option>
                                <option>Spanish</option>
                                <option>French</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="timezone">Timezone</label>
                            <select id="timezone" class="form-control">
                                <option>UTC</option>
                                <option selected>{{ $studentProfile['time_zone_label'] }}</option>
                                <option>Eastern Time (New York)</option>
                            </select>
                        </div>
                    </div>

                    <label class="student-settings-toggle" for="notify-status">
                        <input id="notify-status" type="checkbox" checked>
                        <span>Email me when my ticket status changes.</span>
                    </label>
                    <label class="student-settings-toggle" for="notify-comments">
                        <input id="notify-comments" type="checkbox" checked>
                        <span>Email me when a lecturer or admin adds a comment.</span>
                    </label>
                    <label class="student-settings-toggle" for="notify-reminders">
                        <input id="notify-reminders" type="checkbox">
                        <span>Send in-app reminders when I have an action pending.</span>
                    </label>

                    <div class="form-actions student-settings-actions">
                        <button type="button" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span> Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div class="details-right">
        <section class="detail-card">
            <div class="detail-card-header">Account Snapshot</div>
            <div class="detail-card-body student-settings-summary">
                <div class="profile-header-section">
                    <div class="profile-avatar-large student-settings-avatar">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                    <div class="profile-title-block">
                        <h2 class="profile-name">{{ $studentProfile['name'] }}</h2>
                        <div class="profile-badges">
                            <span class="badge-role-dark">Student</span>
                            <span class="badge-status-outline">{{ $studentProfile['email_verification_label'] }}</span>
                        </div>
                        <p class="profile-meta">Portal communications and security preferences</p>
                    </div>
                </div>

                <div class="overview-grid student-settings-overview">
                    <span class="overview-label">Student ID</span>
                    <span class="overview-value">{{ $studentProfile['identifier'] }}</span>

                    <span class="overview-label">Primary Email</span>
                    <span class="overview-value">{{ $studentProfile['email'] }}</span>

                    <span class="overview-label">Timezone</span>
                    <span class="overview-value">{{ $studentProfile['time_zone_label'] }}</span>

                    <span class="overview-label">Last Sign-In</span>
                    <span class="overview-value">{{ $studentProfile['last_sign_in'] }}</span>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Security Tips</div>
            <div class="detail-card-body">
                <div class="student-settings-note-list">
                    <div class="student-settings-note">
                        <h4>Use a unique password</h4>
                        <p>Keep your portal password separate from social, email, or shared campus passwords.</p>
                    </div>
                    <div class="student-settings-note">
                        <h4>Review session activity</h4>
                        <p>If you update your password on a public or lab device, sign out of every other session.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Support</div>
            <div class="detail-card-body">
                <div class="student-settings-note-list">
                    <div class="student-settings-note">
                        <h4>Email delivery issues</h4>
                        <p>If ticket alerts are not arriving, confirm your primary email first, then contact the support desk for a notification check.</p>
                    </div>
                    <div class="student-settings-note">
                        <h4>Registrar-managed data</h4>
                        <p>Name, program, and university ID corrections must still be handled through Student Affairs.</p>
                    </div>
                </div>
                <div class="form-actions student-settings-actions">
                    <button type="button" class="btn btn-outline btn-surface">
                        <span class="material-symbols-outlined">support_agent</span> Contact Support
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
