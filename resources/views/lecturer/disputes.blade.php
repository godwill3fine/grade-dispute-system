@extends('layouts.app')

@section('title', 'Assigned Disputes | Grade Dispute')
@section('page_id', 'lecturer-disputes')
@section('page_title', 'Assigned Disputes')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="h6 mb-0">Lecturer Work Queue</h3>
        <select class="form-select form-select-sm w-auto" id="lecturerStatusFilter">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="under_review">Under Review</option>
            <option value="resolved">Resolved</option>
            <option value="escalated">Escalated</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Student</th>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="lecturerDisputesTableBody">
                <tr><td colspan="7" class="text-muted text-center py-4">Loading disputes...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
