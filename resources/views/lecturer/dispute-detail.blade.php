@extends('layouts.app')

@section('title', 'Review Dispute | Grade Dispute')
@section('page_id', 'lecturer-dispute-detail')
@section('page_title', 'Review Dispute')
@section('dispute_id', (string) $disputeId)

@section('content')
@include('lecturer.partials.staff-review-banner')

<div class="details-grid">
    <div class="details-left">
        <section class="detail-card">
            <div class="detail-card-header">Dispute Summary</div>
            <div class="detail-card-body" id="lecturerDisputeOverview">
                <p class="text-muted mb-0">Loading dispute details...</p>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Attachments</div>
            <div class="detail-card-body" id="lecturerAttachmentList">
                <p class="empty-state">No attachments available.</p>
            </div>
        </section>
    </div>

    <div class="details-right">
        <section class="detail-card">
            <div class="detail-card-header">Actions</div>
            <div class="detail-card-body">
                <form id="lecturerResponseForm" class="form-group">
                    <label for="lecturerResponseComment">Add Response</label>
                    <textarea class="form-control" id="lecturerResponseComment" name="comment" rows="3" required></textarea>
                    <div class="form-actions form-actions-compact">
                        <p class="auth-feedback mb-0" id="lecturerResponseFeedback"></p>
                        <button class="btn btn-primary" type="submit">Submit Response</button>
                    </div>
                </form>

                <form id="lecturerStatusForm" class="form-group">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lecturerStatusValue">Update Status</label>
                            <select class="form-control" id="lecturerStatusValue" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="under_review">Under Review</option>
                                <option value="resolved">Resolved</option>
                                <option value="escalated">Escalated</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lecturerStatusComment">Comment (Optional)</label>
                            <input class="form-control" id="lecturerStatusComment" name="comment" placeholder="Status note">
                        </div>
                    </div>
                    <div class="form-actions form-actions-compact">
                        <p class="auth-feedback mb-0" id="lecturerStatusFeedback"></p>
                        <button class="btn btn-outline" type="submit">Apply Status</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-header">Timeline &amp; Logs</div>
            <div class="detail-card-body">
                <div id="lecturerTimeline">
                    <p class="text-muted mb-0">Loading timeline...</p>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
