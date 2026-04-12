@extends('layouts.app')

@section('title', 'Admin Escalations | Grade Dispute')
@section('page_id', 'admin-escalations')
@section('page_title', 'Escalated Disputes')

@section('content')
<div class="page-header flex-header page-header-plain page-header-gap-lg">
    <div class="header-text"></div>
    <div class="header-actions">
        <button class="btn btn-outline btn-surface" id="adminEscalationsExportBtn" type="button">
            Export Report
        </button>
    </div>
</div>

<div class="stats-container stats-grid-3">
    <div class="stat-card escalation-stat">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">Total Escalated</span>
            <span class="stat-value stat-value-compact" data-escalation-metric="total">0</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">warning</span>
        </div>
    </div>
    <div class="stat-card escalation-stat">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">High Priority</span>
            <span class="stat-value stat-value-compact" data-escalation-metric="high_priority">0</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">priority_high</span>
        </div>
    </div>
    <div class="stat-card escalation-stat">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">Staff Involved</span>
            <span class="stat-value stat-value-compact" data-escalation-metric="staff_involved">0</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">group</span>
        </div>
    </div>
</div>

<div class="table-container table-container-spaced">
    <div class="table-controls table-controls-spacious admin-escalation-controls">
        <h3 class="section-title">Active Escalations</h3>
        <div class="control-actions admin-escalation-actions">
            <input
                class="form-control expanded-search admin-escalation-search"
                id="adminEscalationSearch"
                type="search"
                placeholder="Search ticket, student, title, or unit"
            >
            <select class="status-filter" id="adminEscalationRoleFilter" aria-label="Filter by assignee">
                <option value="all">All Assignees</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="tickets-table admin-escalations-table">
            <thead>
                <tr>
                    <th>TICKET ID</th>
                    <th>STUDENT &amp; UNIT</th>
                    <th>ASSIGNED LECTURER</th>
                    <th>ESCALATION REASON</th>
                    <th>CURRENT STATUS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="adminEscalationsTableBody">
                <tr>
                    <td colspan="6" class="text-muted text-center py-4">Loading escalations...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="policy-card">
    <h4>Escalation Policy Reminder</h4>
    <p>All escalated tickets should be reviewed promptly and reassigned when a final decision needs higher-level approval.</p>
    <p>Use this page to monitor sensitive disputes, identify overloaded assignees, and follow up on high-priority escalations.</p>
</div>
@endsection
