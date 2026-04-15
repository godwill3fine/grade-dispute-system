@extends('layouts.app')

@section('title', 'Student Profile | Grade Dispute')
@section('page_id', 'student-profile')
@section('page_title', 'Student Profile')

@section('content')
<div class="page-header flex-header student-profile-page-header">
    <div class="header-text">
        <span class="eyebrow">Student / Profile</span>
        <h1>Student Profile</h1>
        <p>View your registrar details, academic placement, and account information used across the dispute portal.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline btn-surface" type="button">
            <span class="material-symbols-outlined">mail</span> Contact Student Affairs
        </button>
    </div>
</div>

<div class="details-grid student-profile-layout">
    <div class="details-left">
        <section class="detail-card">
            <div class="detail-card-header">Profile Summary</div>
            <div class="detail-card-body">
                <div class="profile-header-section">
                    <div class="profile-avatar-large">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                    <div class="profile-title-block">
                        <h2 class="profile-name">{{ $studentProfile['name'] }}</h2>
                        <div class="profile-badges">
                            <span class="badge-role-dark">Student</span>
                            <span
                                @class([
                                    'badge-status-outline',
                                    'badge-status-outline-active' => \Illuminate\Support\Str::lower($studentProfile['status_label']) === 'active',
                                ])
                            >
                                {{ $studentProfile['status_label'] }}
                            </span>
                        </div>
                        <p class="profile-meta">{{ $studentProfile['profile_meta'] }}</p>
                    </div>
                </div>

                <div class="student-profile-divider"></div>

                <div class="profile-details-grid">
                    <div class="profile-info-box">
                        <div class="info-eyebrow">
                            <span class="material-symbols-outlined">numbers</span> University ID
                        </div>
                        <div class="info-value">{{ $studentProfile['identifier'] }}</div>
                    </div>

                    <div class="profile-info-box">
                        <div class="info-eyebrow">
                            <span class="material-symbols-outlined">mail</span> Email Address
                        </div>
                        <div class="info-value">{{ $studentProfile['email'] }}</div>
                    </div>

                    <div class="profile-info-box">
                        <div class="info-eyebrow">
                            <span class="material-symbols-outlined">menu_book</span> Department
                        </div>
                        <div class="info-value">{{ $studentProfile['department_name'] }}</div>
                    </div>

                    <div class="profile-info-box">
                        <div class="info-eyebrow">
                            <span class="material-symbols-outlined">apartment</span> Faculty
                        </div>
                        <div class="info-value">{{ $studentProfile['faculty_name'] }}</div>
                    </div>
                </div>

                <div class="profile-notice">
                    <p>Your core profile data is synchronized from the university registrar. If your legal name, university ID, or academic program looks incorrect, contact Student Affairs so the master record can be updated.</p>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Registrar Notice</div>
            <div class="detail-card-body">
                <div class="student-profile-note-list">
                    <div class="student-profile-note">
                        <h4>Identity details</h4>
                        <p>Name, ID, and program corrections must be updated through Student Affairs.</p>
                    </div>
                    <div class="student-profile-note">
                        <h4>Contact preferences</h4>
                        <p>Notification settings are managed from your portal account preferences page.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="details-right">
        <section class="detail-card">
            <div class="detail-card-header">Portal Preferences</div>
            <div class="detail-card-body">
                <div class="overview-grid student-profile-overview">
                    <span class="overview-label">Alerts</span>
                    <span class="overview-value">Email and in-app notifications enabled</span>

                    <span class="overview-label">Profile Source</span>
                    <span class="overview-value">Registrar synchronization</span>

                    <span class="overview-label">Last Sync</span>
                    <span class="overview-value">{{ $studentProfile['last_sync'] }}</span>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Need Changes?</div>
            <div class="detail-card-body">
                <div class="student-profile-note-list">
                    <div class="student-profile-note">
                        <h4>Dispute readiness</h4>
                        <p>Make sure your email and department details are correct before submitting a new ticket.</p>
                    </div>
                    <div class="student-profile-note">
                        <h4>Help desk</h4>
                        <p>If portal information looks outdated after a registrar update, contact support for a sync check.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
