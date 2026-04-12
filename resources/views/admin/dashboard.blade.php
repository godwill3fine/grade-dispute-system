@extends('layouts.app')

@section('title', 'Admin Dashboard | Grade Dispute')
@section('page_id', 'admin-dashboard')
@section('page_title', 'Admin Dashboard')

@section('content')
<div class="stats-container stats-grid-4 admin-dashboard-stats" id="adminMetricCards">
    <div class="stat-card admin-stat">
        <div class="stat-top">
            <span class="material-symbols-outlined">trending_up</span>
            <span class="trend trend-up">Live</span>
        </div>
        <span class="stat-value" data-metric="total_disputes">0</span>
        <h4>Total Disputes</h4>
    </div>
    <div class="stat-card admin-stat">
        <div class="stat-top">
            <span class="material-symbols-outlined">trending_down</span>
            <span class="trend trend-down">Rolling</span>
        </div>
        <span class="stat-value" data-metric="average_resolution_time">0.0 days</span>
        <h4>Average Resolution Time</h4>
    </div>
    <div class="stat-card admin-stat">
        <div class="stat-top">
            <span class="material-symbols-outlined">verified</span>
            <span class="trend trend-up">Stable</span>
        </div>
        <span class="stat-value" data-metric="resolved">0</span>
        <h4>Resolved Cases</h4>
    </div>
    <div class="stat-card admin-stat">
        <div class="stat-top">
            <span class="material-symbols-outlined">groups</span>
            <span class="trend trend-up">Current</span>
        </div>
        <span class="stat-value" data-metric="active_users">0</span>
        <h4>Active Users</h4>
    </div>
</div>

<div class="admin-grid admin-dashboard-grid">
    <div class="detail-card detail-card-flat admin-trend-card">
        <div class="detail-card-header detail-card-header-inline admin-trend-header">
            <span>Dispute Trends</span>
            <div class="toggle-group" id="adminTrendRange">
                <button class="active" data-trend-range="week" type="button">Week</button>
                <button data-trend-range="month" type="button">Month</button>
                <button data-trend-range="year" type="button">Year</button>
            </div>
        </div>
        <div class="detail-card-body detail-card-body-stretch">
            <div class="chart-panel admin-trend-panel">
                <canvas id="adminTrendChart" height="110"></canvas>
            </div>
        </div>
    </div>

    <div class="detail-card detail-card-flat admin-activity-card">
        <div class="detail-card-header detail-card-header-inline detail-card-header-tight">
            <span class="material-symbols-outlined icon-sm">calendar_today</span>
            <span>Recent Activity</span>
        </div>
        <div class="detail-card-body activity-feed admin-activity-feed" id="adminRecentActivity">
            <div class="activity-item">
                <div class="activity-title">Loading recent activity...</div>
            </div>
        </div>
    </div>
</div>

<div class="table-container table-container-spaced">
    <div class="table-controls">
        <h3 class="section-title">Unit Summary</h3>
    </div>
    <div class="table-responsive">
        <table class="tickets-table admin-summary-table">
            <thead>
                <tr>
                    <th>UNIT</th>
                    <th>TOTAL DISPUTES</th>
                    <th>PENDING</th>
                    <th>RESOLVED</th>
                    <th>AVG. RESOLUTION TIME</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="adminUnitSummaryTableBody">
                <tr><td colspan="6" class="text-muted text-center py-4">Loading unit summary...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="alerts-section">
    <h3><span class="material-symbols-outlined">error</span> System Alerts</h3>
    <div class="alert-card alert-danger">
        <span class="alert-title">Unassigned disputes</span>
        <p class="alert-desc"><strong data-alert="unassigned">0</strong> disputes need routing.</p>
    </div>
    <div class="alert-card alert-info">
        <span class="alert-title">Pending overdue</span>
        <p class="alert-desc"><strong data-alert="pending_overdue">0</strong> disputes are past the expected review window.</p>
    </div>
    <div class="alert-card alert-info">
        <span class="alert-title">Escalation queue</span>
        <p class="alert-desc">
            <strong data-alert="escalation_soon">0</strong> nearing escalation,
            <strong data-alert="escalated">0</strong> already escalated,
            <strong data-alert="long_unresolved">0</strong> long unresolved.
        </p>
    </div>
</div>
@endsection
