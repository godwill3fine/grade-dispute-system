@extends('layouts.app')

@section('title', 'Admin Reports | Grade Dispute')
@section('page_id', 'admin-reports')
@section('page_title', 'Reports & Analytics')

@section('content')
<div class="stats-container stats-grid-3">
    <div class="stat-card escalation-stat">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">Average Resolution</span>
            <span class="stat-value stat-value-compact"><span id="resolutionDays">0</span> Days</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">calendar_today</span>
        </div>
    </div>
    <div class="stat-card escalation-stat">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">Unit Reports</span>
            <span class="stat-value stat-value-compact">Live</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">dataset</span>
        </div>
    </div>
    <div class="stat-card escalation-stat">
        <div class="stat-left">
            <span class="eyebrow stat-eyebrow">Lecturer Reports</span>
            <span class="stat-value stat-value-compact">Live</span>
        </div>
        <div class="stat-icon-box">
            <span class="material-symbols-outlined">bar_chart</span>
        </div>
    </div>
</div>

<div class="admin-grid admin-reports-grid">
    <div class="detail-card detail-card-flat admin-report-trend-card">
        <div class="detail-card-header detail-card-header-inline admin-report-header">
            <span>Monthly Trend</span>
            <span class="trend trend-up">Live</span>
        </div>
        <div class="detail-card-body detail-card-body-stretch">
            <div class="chart-panel admin-report-chart-panel">
                <canvas id="adminMonthlyReportChart" height="110"></canvas>
            </div>
        </div>
    </div>

    <div class="detail-card detail-card-flat admin-report-notes-card">
        <div class="detail-card-header detail-card-header-inline detail-card-header-tight">
            <span class="material-symbols-outlined icon-sm">insights</span>
            <span>Reporting Notes</span>
        </div>
        <div class="detail-card-body activity-feed admin-report-notes-feed">
            <div class="activity-item">
                <div class="activity-title">Monthly dispute volume updates automatically.</div>
                <div class="activity-desc">Use the tables below for unit and lecturer-level snapshots.</div>
            </div>
            <div class="activity-item">
                <div class="activity-title">Resolution time is calculated from resolved disputes only.</div>
                <div class="activity-desc">If no resolved records exist yet, the metric will remain at zero.</div>
            </div>
            <div class="activity-item">
                <div class="activity-title">The tables below surface live workload and closure patterns.</div>
                <div class="activity-desc">Use them to spot departments or lecturers that need follow-up sooner.</div>
            </div>
        </div>
    </div>
</div>

<div class="table-container table-container-spaced">
    <div class="table-controls">
        <h3 class="section-title">Unit Performance Snapshot</h3>
    </div>
    <div class="table-responsive">
        <table class="tickets-table admin-report-table">
            <thead>
                <tr>
                    <th>UNIT CODE</th>
                    <th>UNIT NAME</th>
                    <th>TOTAL DISPUTES</th>
                    <th>PENDING</th>
                    <th>UNDER REVIEW</th>
                    <th>RESOLVED</th>
                    <th>ESCALATED</th>
                </tr>
            </thead>
            <tbody id="unitReportTable"><tr><td colspan="7" class="text-muted text-center py-3">Loading...</td></tr></tbody>
        </table>
    </div>
</div>

<div class="table-container table-container-spaced">
    <div class="table-controls">
        <h3 class="section-title">Lecturer Workload Snapshot</h3>
    </div>
    <div class="table-responsive">
        <table class="tickets-table admin-report-table">
            <thead>
                <tr>
                    <th>LECTURER</th>
                    <th>TOTAL ASSIGNED</th>
                    <th>PENDING</th>
                    <th>UNDER REVIEW</th>
                    <th>RESOLVED</th>
                </tr>
            </thead>
            <tbody id="lecturerReportTable"><tr><td colspan="5" class="text-muted text-center py-3">Loading...</td></tr></tbody>
        </table>
    </div>
</div>
@endsection
