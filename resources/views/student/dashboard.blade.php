@extends('layouts.app')

@section('title', 'Student Dashboard | Grade Dispute')
@section('page_id', 'student-dashboard')
@section('page_title', 'Student Dashboard')

@section('content')
<section class="gd-student-page gd-student-dashboard">
    <div class="gd-student-header-card mb-4">
        <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
            <div>
                <p class="gd-kicker mb-1">Student Workspace</p>
                <h3 class="h4 mb-2">Track Your Disputes In One Place</h3>
                <p class="text-muted mb-0">See progress, check feedback, and quickly open any ticket.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('portal.student.submit') }}" class="btn btn-dark">
                    <i class="bi bi-file-earmark-plus me-2"></i>Submit Dispute
                </a>
                <a href="{{ route('portal.student.tickets') }}" class="btn btn-outline-dark">View Tickets</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4" id="studentMetricCards">
        <div class="col-sm-6 col-xl-4">
            <article class="gd-wire-card">
                <p class="text-muted mb-1">Open Tickets</p>
                <h3 class="mb-0" data-metric="pending">0</h3>
            </article>
        </div>
        <div class="col-sm-6 col-xl-4">
            <article class="gd-wire-card">
                <p class="text-muted mb-1">Under Review</p>
                <h3 class="mb-0" data-metric="under_review">0</h3>
            </article>
        </div>
        <div class="col-sm-6 col-xl-4">
            <article class="gd-wire-card">
                <p class="text-muted mb-1">Resolved</p>
                <h3 class="mb-0" data-metric="resolved">0</h3>
            </article>
        </div>
        <div class="col-12">
            <article class="gd-inline-total">
                <span>Total disputes submitted:</span>
                <strong data-metric="total">0</strong>
            </article>
        </div>
    </div>

    <section class="gd-wire-block">
        <div class="gd-wire-toolbar">
            <div class="gd-search-box">
                <i class="bi bi-search"></i>
                <input type="search" class="form-control border-0" id="studentDashboardSearch" placeholder="Search by ticket, title, or unit">
            </div>
            <select class="form-select" id="studentDashboardStatusFilter">
                <option value="all">All statuses</option>
                <option value="pending">Pending</option>
                <option value="under_review">Under Review</option>
                <option value="resolved">Resolved</option>
                <option value="escalated">Escalated</option>
            </select>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0 gd-wire-table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Updated</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="studentRecentDisputes">
                    <tr><td colspan="6" class="text-muted text-center py-4">Loading disputes...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
