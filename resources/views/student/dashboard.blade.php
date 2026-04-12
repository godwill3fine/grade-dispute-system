@extends('layouts.app')

@section('title', 'Student Dashboard | Grade Dispute')
@section('page_id', 'student-dashboard')
@section('page_title', 'Student Dashboard')

@section('content')
<div class="stats-container">
    <div class="stat-card">
        <h4>Open Tickets</h4>
        <span class="stat-value" data-metric="pending">0</span>
    </div>
    <div class="stat-card">
        <h4>Under Review</h4>
        <span class="stat-value" data-metric="under_review">0</span>
    </div>
    <div class="stat-card">
        <h4>Resolved</h4>
        <span class="stat-value" data-metric="resolved">0</span>
    </div>
</div>

<div class="dashboard-toolbar">
    <div class="total-disputes">
        Total disputes submitted: <strong data-metric="total">0</strong>
    </div>
    <div class="header-actions">
        <a href="{{ route('portal.student.submit') }}" class="btn btn-primary">
            <span class="material-symbols-outlined">post_add</span> Submit Dispute
        </a>
        <a href="{{ route('portal.student.tickets') }}" class="btn btn-outline">View Tickets</a>
    </div>
</div>

<section class="table-container">
    <div class="table-controls">
        <div class="search-box">
            <span class="material-symbols-outlined">search</span>
            <input type="search" id="studentDashboardSearch" placeholder="Search by ticket, title, or unit">
        </div>
        <select class="status-filter" id="studentDashboardStatusFilter">
            <option value="all">All statuses</option>
            <option value="pending">Pending</option>
            <option value="under_review">Under Review</option>
            <option value="resolved">Resolved</option>
            <option value="escalated">Escalated</option>
        </select>
    </div>

    <div class="table-responsive">
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>TICKET</th>
                    <th>TITLE</th>
                    <th>STATUS</th>
                    <th>PRIORITY</th>
                    <th>UPDATED</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="studentRecentDisputes">
                <tr><td colspan="6" class="text-muted text-center py-4">Loading disputes...</td></tr>
            </tbody>
        </table>
    </div>
</section>
@endsection
