@extends('layouts.app')

@section('title', 'Review History | Grade Dispute')
@section('page_id', 'lecturer-history')
@section('page_title', 'Review History')

@section('content')
<div class="page-header flex-header page-header-plain page-header-gap-lg">
    <div class="header-text"></div>
    <button class="btn btn-outline btn-surface" id="lecturerHistoryExport" type="button">
        <span class="material-symbols-outlined">upload</span> Export Report
    </button>
</div>

<div class="stats-container stats-grid-4">
    <div class="stat-card">
        <span class="eyebrow stat-eyebrow stat-eyebrow-lg">Total Reviewed</span>
        <span class="stat-value" data-history-metric="total">0</span>
    </div>
    <div class="stat-card">
        <span class="eyebrow stat-eyebrow stat-eyebrow-lg">Resolved</span>
        <span class="stat-value" data-history-metric="resolved">0</span>
    </div>
    <div class="stat-card">
        <span class="eyebrow stat-eyebrow stat-eyebrow-lg">Rejected</span>
        <span class="stat-value" data-history-metric="rejected">0</span>
    </div>
    <div class="stat-card">
        <span class="eyebrow stat-eyebrow stat-eyebrow-lg">Escalated</span>
        <span class="stat-value" data-history-metric="escalated">0</span>
    </div>
</div>

<div class="table-container">
    <div class="table-controls lecturer-controls">
        <div class="search-box expanded-search">
            <span class="material-symbols-outlined">search</span>
            <input type="search" id="lecturerHistorySearch" placeholder="Search tickets by ID, student name, or course...">
        </div>
        <div class="control-actions">
            <select class="status-filter" id="lecturerHistoryOutcomeFilter">
                <option value="all">All outcomes</option>
                <option value="resolved">Resolved</option>
                <option value="rejected">Rejected</option>
                <option value="escalated">Escalated</option>
                <option value="closed">Closed</option>
            </select>
            <select class="status-filter" id="lecturerHistoryCourseFilter">
                <option value="all">All courses</option>
            </select>
        </div>
    </div>

                    <div class="table-responsive">
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>TICKET</th>
                    <th>STUDENT</th>
                    <th>COURSE / ISSUE</th>
                    <th>OUTCOME</th>
                    <th>DATE CLOSED</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="lecturerHistoryTableBody">
                <tr><td colspan="6" class="text-muted text-center py-4">Loading review history...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer-stats" id="lecturerHistoryFooter">Showing 0 of 0 records</div>
</div>
@endsection
