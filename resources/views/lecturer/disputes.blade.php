@extends('layouts.app')

@section('title', 'Assigned Disputes | Grade Dispute')
@section('page_id', 'lecturer-disputes')
@section('page_title', 'Assigned Disputes')

@section('content')
<div class="table-container">
    <div class="table-controls lecturer-controls">
        <h3 class="section-title">Lecturer Work Queue</h3>
        <select class="status-filter" id="lecturerStatusFilter">
            <option value="all">All Statuses</option>
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
                    <th>STUDENT</th>
                    <th>TITLE</th>
                    <th>PRIORITY</th>
                    <th>STATUS</th>
                    <th>UPDATED</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="lecturerDisputesTableBody">
                <tr><td colspan="7" class="text-muted text-center py-4">Loading disputes...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
