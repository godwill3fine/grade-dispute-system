@extends('layouts.app')

@section('title', 'Tickets | Grade Dispute')
@section('page_id', 'student-disputes')
@section('page_title', 'Tickets')

@section('content')
<div class="page-header flex-header">
    <div class="header-text">
        <span class="eyebrow">MY CASES</span>
        <h1>Ticket History</h1>
        <p>Track status updates and open any dispute for full activity details.</p>
    </div>
    <a href="{{ route('portal.student.submit') }}" class="btn btn-primary">
        <span class="material-symbols-outlined">add</span> New Dispute
    </a>
</div>

<section class="table-container">
    <div class="table-controls">
        <div class="search-box">
            <span class="material-symbols-outlined">search</span>
            <input type="search" id="studentTicketsSearch" placeholder="Search tickets">
        </div>
        <select class="status-filter" id="studentStatusFilter">
            <option value="all">All statuses</option>
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
                    <th>TITLE</th>
                    <th>STATUS</th>
                    <th>ASSIGNED TO</th>
                    <th>UPDATED</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="studentDisputesTableBody">
                <tr><td colspan="6" class="text-muted text-center py-4">Loading disputes...</td></tr>
            </tbody>
        </table>
    </div>
</section>
@endsection
