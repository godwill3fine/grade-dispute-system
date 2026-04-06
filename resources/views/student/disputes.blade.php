@extends('layouts.app')

@section('title', 'Tickets | Grade Dispute')
@section('page_id', 'student-tickets')
@section('page_title', 'Tickets')

@section('content')
<section class="gd-student-page gd-student-tickets">
    <div class="gd-student-header-card mb-4">
        <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
            <div>
                <p class="gd-kicker mb-1">My Cases</p>
                <h3 class="h4 mb-2">Ticket History</h3>
                <p class="text-muted mb-0">Track status updates and open any dispute for full activity details.</p>
            </div>
            <a href="{{ route('portal.student.submit') }}" class="btn btn-dark">
                <i class="bi bi-plus-lg me-2"></i>New Dispute
            </a>
        </div>
    </div>

    <section class="gd-wire-block">
        <div class="gd-wire-toolbar">
            <div class="gd-search-box">
                <i class="bi bi-search"></i>
                <input type="search" class="form-control border-0" id="studentTicketsSearch" placeholder="Search tickets">
            </div>
            <select class="form-select" id="studentStatusFilter">
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
                        <th>Assigned To</th>
                        <th>Updated</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="studentDisputesTableBody">
                    <tr><td colspan="6" class="text-muted text-center py-4">Loading disputes...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
