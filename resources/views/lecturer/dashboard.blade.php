@extends('layouts.app')

@section('title', 'Lecturer Dashboard | Grade Dispute')
@section('page_id', 'lecturer-dashboard')
@section('page_title', 'Lecturer Dashboard')

@section('content')
@include('lecturer.partials.staff-review-banner')

{{-- JavaScript fills these metric cards from the reviewer dispute queue API. --}}
<div class="stats-container stats-grid-4" id="lecturerMetricCards">
    <div class="stat-card">
        <span class="material-symbols-outlined stat-icon">description</span>
        <h4>Assigned</h4>
        <span class="stat-value" data-metric="assigned">0</span>
    </div>
    <div class="stat-card">
        <span class="material-symbols-outlined stat-icon">schedule</span>
        <h4>{{ ($portalUserMeta['is_staff_reviewer'] ?? false) ? 'Escalated Review' : 'Pending Review' }}</h4>
        <span class="stat-value" data-metric="pending">0</span>
    </div>
    <div class="stat-card">
        <span class="material-symbols-outlined stat-icon">check_circle</span>
        <h4>Under Review</h4>
        <span class="stat-value" data-metric="under_review">0</span>
    </div>
    <div class="stat-card">
        <span class="material-symbols-outlined stat-icon">warning</span>
        <h4>Overdue</h4>
        <span class="stat-value" data-metric="overdue">0</span>
    </div>
</div>

{{-- Filters and actions control the reviewer dashboard table below. --}}
<div class="table-section-header">
    <h2>{{ ($portalUserMeta['is_staff_reviewer'] ?? false) ? 'Disputes requiring higher-level review' : 'Disputes requiring action' }}</h2>
    <div class="header-actions">
        <a href="{{ route('portal.lecturer.disputes') }}" class="btn btn-outline">
            <span class="material-symbols-outlined">open_in_new</span> Open Queue
        </a>
        <div class="dropdown">
            <button class="btn btn-outline btn-surface" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="material-symbols-outlined">filter_alt</span> Filter
            </button>
            <div class="dropdown-menu dropdown-menu-end lecturer-filter-menu shadow-sm">
                <label for="lecturerDashboardStatusFilter">Filter Status</label>
                <select class="status-filter w-100" id="lecturerDashboardStatusFilter">
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    @if ($portalUserMeta['is_staff_reviewer'] ?? false)
                        <option value="escalated">Escalated</option>
                    @endif
                </select>
            </div>
        </div>
        <button class="btn btn-outline btn-surface" id="lecturerDashboardExport" type="button">
            <span class="material-symbols-outlined">upload</span> Export
        </button>
    </div>
</div>

<div class="course-filters" id="lecturerDashboardCourseFilters"></div>

{{-- The initial row is replaced once the dashboard module loads reviewer disputes. --}}
<div class="table-container">
    <div class="table-responsive">
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>TICKET</th>
                    <th>STUDENT</th>
                    <th>TITLE</th>
                    <th>STATUS</th>
                    <th>UPDATED</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="lecturerRecentDisputes">
                <tr><td colspan="6" class="text-muted text-center py-4">Loading disputes...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="guidelines-card">
    <h4>Review Guidelines</h4>
    <ul>
        <li>Review deadline is 5 business days from submission.</li>
        <li>Provide concise feedback for each decision you make.</li>
        <li>Escalate complex or overdue cases when resolution needs higher review.</li>
    </ul>
</div>
@endsection
