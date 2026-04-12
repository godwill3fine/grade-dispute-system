@extends('layouts.app')

@section('title', 'Admin Manage Tickets | Grade Dispute')
@section('page_id', 'admin-disputes')
@section('page_title', 'Manage Tickets')

@section('content')
<div class="page-header flex-header page-header-plain">
    <div class="header-text"></div>
    <div class="header-actions">
        <button class="btn btn-outline btn-surface" id="adminDisputesExportBtn" type="button">
            <span class="material-symbols-outlined">download</span> Export CSV
        </button>
        <button class="btn btn-primary" id="adminCreateTicketBtn" type="button">
            <span class="material-symbols-outlined">add</span> Create Ticket
        </button>
    </div>
</div>

<div class="table-container">
    <div class="admin-table-controls admin-disputes-controls">
        <div class="search-box expanded-search">
            <span class="material-symbols-outlined">search</span>
            <input
                id="adminDisputeSearch"
                type="text"
                placeholder="Search tickets by ID, student, course, or type..."
                aria-label="Search tickets"
            >
        </div>
        <div class="control-actions admin-disputes-actions">
            <div class="dropdown-wrapper">
                <span class="material-symbols-outlined filter-icon">filter_alt</span>
                <select class="status-filter with-icon" id="adminDisputeStatusFilter" aria-label="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="resolved">Resolved</option>
                    <option value="escalated">Escalated</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <select class="status-filter" id="adminDisputeDepartmentFilter" aria-label="Filter by department">
                <option value="">Department</option>
                @foreach (($adminDisputeMeta['departments'] ?? []) as $department)
                    <option value="{{ $department['name'] }}">{{ $department['name'] }}</option>
                @endforeach
            </select>
            <div class="dropdown-wrapper">
                <span class="material-symbols-outlined filter-icon">sort</span>
                <select class="status-filter with-icon" id="adminDisputeSortFilter" aria-label="Sort tickets">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="priority">Priority</option>
                    <option value="status">Status</option>
                </select>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="tickets-table admin-disputes-table">
            <thead>
                <tr>
                    <th>TICKET ID</th>
                    <th>STUDENT / COURSE</th>
                    <th>ISSUE TYPE</th>
                    <th>PRIORITY</th>
                    <th>STATUS</th>
                    <th>DATE SUBMITTED</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="adminDisputesTableBody">
                <tr><td colspan="7" class="text-muted text-center py-4">Loading tickets...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer-pagination">
        <div class="pagination-info" id="adminDisputePaginationInfo">Showing 0 to 0 of 0 total tickets</div>
        <div class="pagination-controls" id="adminDisputePaginationControls"></div>
    </div>
</div>

<script id="adminDisputeMeta" type="application/json">@json($adminDisputeMeta ?? ['departments' => [], 'units' => []])</script>
@endsection
