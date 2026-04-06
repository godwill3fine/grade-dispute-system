@extends('layouts.app')

@section('title', 'Dispute Detail | Grade Dispute')
@section('page_id', 'student-dispute-detail')
@section('page_title', 'Dispute Detail')
@section('dispute_id', (string) $disputeId)

@section('content')
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h3 class="h6 mb-0">Dispute Overview</h3>
            </div>
            <div class="card-body" id="studentDisputeOverview">
                <p class="text-muted mb-0">Loading dispute details...</p>
            </div>
        </section>

        <section class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h3 class="h6 mb-0">Attachments</h3>
            </div>
            <div class="list-group list-group-flush" id="studentAttachmentList">
                <div class="list-group-item text-muted">No attachments found.</div>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-7">
        <section class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h3 class="h6 mb-0">Timeline & Activity</h3>
            </div>
            <div class="card-body">
                <div class="gd-timeline" id="studentTimeline">
                    <p class="text-muted mb-0">Loading timeline...</p>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
