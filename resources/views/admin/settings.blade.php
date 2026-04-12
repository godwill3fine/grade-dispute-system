@extends('layouts.app')

@section('title', 'Admin Settings | Grade Dispute')
@section('page_id', 'admin-settings')
@section('page_title', 'System Settings')

@section('content')
<div class="page-header flex-header page-header-plain page-header-gap-lg">
    <div class="header-text"></div>
    <div class="header-actions">
        <button class="btn btn-dark" form="adminSettingsForm" type="submit">
            <span class="material-symbols-outlined icon-xs">save</span> Save Changes
        </button>
    </div>
</div>

<div class="settings-layout">
    <aside class="settings-mini-sidebar">
        <ul class="settings-menu">
            <li class="active">
                <span class="material-symbols-outlined">schedule</span>
                <span class="text">General Rules</span>
            </li>
            <li>
                <span class="material-symbols-outlined">mail</span>
                <span class="text">Notifications</span>
            </li>
            <li>
                <span class="material-symbols-outlined">shield_person</span>
                <span class="text">Access & Roles</span>
            </li>
            <li>
                <span class="material-symbols-outlined">integration_instructions</span>
                <span class="text">Integrations</span>
            </li>
            <li>
                <span class="material-symbols-outlined">key</span>
                <span class="text">Security</span>
            </li>
        </ul>
    </aside>

    <div class="settings-panels">
        <form id="adminSettingsForm">
            <div class="settings-card">
                <h3>Dispute Submission Deadlines</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="standardSubmissionWindow">Standard Submission Window (Days)</label>
                        <input
                            id="standardSubmissionWindow"
                            type="number"
                            min="1"
                            class="form-control"
                            data-setting-key="standard_submission_window_days"
                        >
                        <span class="form-text">Number of days after grade release students can submit a dispute.</span>
                    </div>
                    <div class="form-group">
                        <label for="finalExamSubmissionWindow">Final Exam Dispute Window (Days)</label>
                        <input
                            id="finalExamSubmissionWindow"
                            type="number"
                            min="1"
                            class="form-control"
                            data-setting-key="final_exam_dispute_window_days"
                        >
                        <span class="form-text">Shorter window specific to final examination grades.</span>
                    </div>
                </div>

                <label class="settings-checkbox-wrapper" for="autoCloseOverdueTickets">
                    <input
                        id="autoCloseOverdueTickets"
                        type="checkbox"
                        data-setting-key="auto_close_overdue_pending_tickets"
                    >
                    <span class="checkbox-text">Automatically close overdue pending tickets</span>
                </label>
            </div>

            <div class="settings-card">
                <h3>Lecturer Review Configuration</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="expectedReviewTime">Expected Review Time (Days)</label>
                        <input
                            id="expectedReviewTime"
                            type="number"
                            min="1"
                            class="form-control"
                            data-setting-key="escalation_days_hod"
                        >
                        <span class="form-text">Days before a ticket is flagged as delayed and escalated for follow-up.</span>
                    </div>
                    <div aria-hidden="true"></div>
                </div>

                <label class="settings-checkbox-wrapper" for="requireAdjustmentJustification">
                    <input
                        id="requireAdjustmentJustification"
                        type="checkbox"
                        data-setting-key="require_grade_adjustment_justification"
                    >
                    <span class="checkbox-text">Require justification for all grade adjustments</span>
                </label>

                <input type="hidden" data-setting-key="escalation_days_admin">
            </div>
        </form>

        <div class="settings-card danger-zone">
            <div class="danger-corner"></div>

            <div class="danger-header">
                <div class="danger-icon">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <div class="danger-text">
                    <h4>Danger Zone</h4>
                    <p>These actions are irreversible and will affect system functionality.</p>
                </div>
            </div>

            <div class="danger-actions">
                <button class="btn btn-outline" id="archiveOldDataBtn" type="button">Archive Old Data</button>
                <button class="btn btn-danger-outline" id="purgeSystemCacheBtn" type="button">Purge System Cache</button>
            </div>
        </div>
    </div>
</div>
@endsection
