@extends('layouts.app')

@section('title', 'Admin Reports | Grade Dispute')
@section('page_id', 'admin-reports')
@section('page_title', 'Reports & Analytics')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Average Resolution Time</h3></div>
            <div class="card-body d-flex align-items-center">
                <h2 class="mb-0"><span id="resolutionDays">0</span> days</h2>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Monthly Trend</h3></div>
            <div class="card-body"><canvas id="adminMonthlyReportChart" height="120"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Disputes per Unit</h3></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Unit</th><th>Name</th><th>Total</th></tr></thead>
                    <tbody id="unitReportTable"><tr><td colspan="3" class="text-muted text-center py-3">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Disputes per Lecturer</h3></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Lecturer</th><th>Total</th></tr></thead>
                    <tbody id="lecturerReportTable"><tr><td colspan="2" class="text-muted text-center py-3">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
