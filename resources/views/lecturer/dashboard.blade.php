@extends('layouts.app')

@section('title', 'Lecturer Dashboard | Grade Dispute')
@section('page_id', 'lecturer-dashboard')
@section('page_title', 'Lecturer Dashboard')

@section('content')
<div class="row g-3 mb-4" id="lecturerMetricCards">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Assigned</p><h3 data-metric="assigned" class="mb-0">0</h3></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Pending</p><h3 data-metric="pending" class="mb-0">0</h3></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Under Review</p><h3 data-metric="under_review" class="mb-0">0</h3></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm gd-stat-card"><div class="card-body"><p class="text-muted mb-2">Overdue</p><h3 data-metric="overdue" class="mb-0">0</h3></div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h4 class="h6 mb-0">Recent Assigned Disputes</h4>
        <a href="{{ route('portal.lecturer.disputes') }}" class="btn btn-sm btn-primary">Open Queue</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Student</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody id="lecturerRecentDisputes">
                <tr><td colspan="5" class="text-muted text-center py-4">Loading disputes...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
