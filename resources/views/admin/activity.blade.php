@extends('layouts.app')

@section('title', 'Admin Activity | Grade Dispute')
@section('page_id', 'admin-activity')
@section('page_title', 'Recent Activity')

@section('content')
<div class="page-header flex-header page-header-plain">
    <div class="header-text">
        <span class="eyebrow">AUDIT TRAIL</span>
        <h1>Latest System Actions</h1>
        <p>Review the most recent administrative and workflow events recorded by the system.</p>
    </div>
    <button class="btn btn-outline btn-surface" id="refreshActivityBtn" type="button">
        <span class="material-symbols-outlined">refresh</span> Refresh
    </button>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>TIME</th>
                    <th>ACTOR</th>
                    <th>ACTION</th>
                    <th>TARGET</th>
                    <th>DESCRIPTION</th>
                </tr>
            </thead>
            <tbody id="adminActivityTableBody">
                <tr><td colspan="5" class="text-muted text-center py-4">Loading activity...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer-pagination">
        <div class="pagination-info" id="adminActivityPaginationInfo">Showing 0 to 0 of 0 total records</div>
        <div class="pagination-controls" id="adminActivityPaginationControls"></div>
    </div>
</div>
@endsection
