@extends('layouts.app')

@section('title', 'Admin Activity | Grade Dispute')
@section('page_id', 'admin-activity')
@section('page_title', 'Recent Activity')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Latest System Actions</h3>
        <button class="btn btn-sm btn-outline-primary" id="refreshActivityBtn" type="button">Refresh</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody id="adminActivityTableBody">
                <tr><td colspan="5" class="text-muted text-center py-4">Loading activity...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
