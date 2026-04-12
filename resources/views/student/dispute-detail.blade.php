@extends('layouts.app')

@section('title', 'Dispute Detail | Grade Dispute')
@section('page_id', 'student-dispute-detail')
@section('page_title', 'Dispute Detail')
@section('dispute_id', (string) $disputeId)

@section('content')
<div class="details-grid">
    <div class="details-left">
        <section class="detail-card">
            <div class="detail-card-header">Dispute Overview</div>
            <div class="detail-card-body" id="studentDisputeOverview">
                <p class="text-muted mb-0">Loading dispute details...</p>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Attachments</div>
            <div class="detail-card-body" id="studentAttachmentList">
                <p class="empty-state">No attachments found.</p>
            </div>
        </section>
    </div>

    <div class="details-right">
        <section class="detail-card">
            <div class="detail-card-header">Timeline &amp; Activity</div>
            <div class="detail-card-body">
                <div id="studentTimeline">
                    <p class="text-muted mb-0">Loading timeline...</p>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
