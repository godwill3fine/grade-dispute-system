@extends('layouts.app')

@section('title', 'Review Dispute | Grade Dispute')
@section('page_id', 'lecturer-dispute-detail')
@section('page_title', 'Review Dispute')
@section('dispute_id', (string) $disputeId)

@section('content')
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Dispute Summary</h3></div>
            <div class="card-body" id="lecturerDisputeOverview">
                <p class="text-muted mb-0">Loading dispute details...</p>
            </div>
        </section>

        <section class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Attachments</h3></div>
            <div class="list-group list-group-flush" id="lecturerAttachmentList">
                <div class="list-group-item text-muted">No attachments available.</div>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-7">
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Actions</h3></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <form id="lecturerResponseForm" class="d-grid gap-2">
                            <label class="form-label mb-0">Add Response</label>
                            <textarea class="form-control" name="comment" rows="3" required></textarea>
                            <button class="btn btn-primary" type="submit">Submit Response</button>
                            <p class="small text-muted mb-0" id="lecturerResponseFeedback"></p>
                        </form>
                    </div>
                    <div class="col-12">
                        <form id="lecturerStatusForm" class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label mb-0">Update Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="escalated">Escalated</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label mb-0">Comment (Optional)</label>
                                <input class="form-control" name="comment" placeholder="Status note">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-outline-primary" type="submit">Apply Status</button>
                            </div>
                            <p class="small text-muted mb-0" id="lecturerStatusFeedback"></p>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h3 class="h6 mb-0">Timeline & Logs</h3></div>
            <div class="card-body">
                <div class="gd-timeline" id="lecturerTimeline">
                    <p class="text-muted mb-0">Loading timeline...</p>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
