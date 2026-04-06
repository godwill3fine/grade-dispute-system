@extends('layouts.app')

@section('title', 'Admin Dashboard | Grade Dispute')
@section('page_id', 'admin-dashboard')
@section('page_title', 'Admin Dashboard')

@section('content')
<div class="row g-3 mb-4" id="adminMetricCards">
    <div class="col-6 col-xl-2"><div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Total</p><h3 data-metric="total_disputes" class="mb-0">0</h3></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Pending</p><h3 data-metric="pending" class="mb-0">0</h3></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Review</p><h3 data-metric="under_review" class="mb-0">0</h3></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Resolved</p><h3 data-metric="resolved" class="mb-0">0</h3></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Escalated</p><h3 data-metric="escalated" class="mb-0">0</h3></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Long Unresolved</p><h3 data-alert="long_unresolved" class="mb-0">0</h3></div></div></div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Disputes Per Month</h3></div>
            <div class="card-body"><canvas id="adminMonthChart" height="120"></canvas></div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Disputes by Status</h3></div>
            <div class="card-body"><canvas id="adminStatusChart" height="120"></canvas></div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Operational Alerts</h3></div>
            <div class="list-group list-group-flush" id="adminAlertList">
                <div class="list-group-item d-flex justify-content-between"><span>Unassigned</span><strong data-alert="unassigned">0</strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Pending Overdue</span><strong data-alert="pending_overdue">0</strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Escalation Soon</span><strong data-alert="escalation_soon">0</strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Escalated</span><strong data-alert="escalated">0</strong></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h3 class="h6 mb-0">Quick Access</h3>
            </div>
            <div class="card-body d-grid gap-2">
                <a class="btn btn-outline-primary" href="{{ route('portal.admin.disputes') }}">Open Dispute Queue</a>
                <a class="btn btn-outline-primary" href="{{ route('portal.admin.reports') }}">View Reports</a>
                <a class="btn btn-outline-primary" href="{{ route('portal.admin.settings') }}">Manage Settings</a>
            </div>
        </div>
    </div>
</div>
@endsection
