@extends('layouts.app')

@section('title', 'Admin Disputes | Grade Dispute')
@section('page_id', 'admin-disputes')
@section('page_title', 'Dispute Management')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent"><h3 class="h6 mb-0">Filter Disputes</h3></div>
    <div class="card-body">
        <form id="adminDisputeFilterForm" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="resolved">Resolved</option>
                    <option value="escalated">Escalated</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Priority</label>
                <select class="form-select" name="priority">
                    <option value="">All</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input class="form-control" type="date" name="from_date">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input class="form-control" type="date" name="to_date">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Filtered Results</h3>
        <button class="btn btn-sm btn-outline-primary" id="loadEscalatedBtn" type="button">Load Escalated Only</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Student</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Assigned Role</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody id="adminDisputesTableBody">
                <tr><td colspan="7" class="text-muted text-center py-4">Loading disputes...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
        <small class="text-muted" id="adminDisputePaginationInfo">Page 1</small>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-secondary" id="adminPrevPage" type="button">Previous</button>
            <button class="btn btn-sm btn-outline-secondary" id="adminNextPage" type="button">Next</button>
        </div>
    </div>
</div>
@endsection
