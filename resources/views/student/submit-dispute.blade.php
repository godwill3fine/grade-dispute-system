@extends('layouts.app')

@section('title', 'Submit Dispute | Grade Dispute')
@section('page_id', 'student-submit-dispute')
@section('page_title', 'Submit Dispute')

@section('content')
<section class="gd-student-page gd-student-submit">
    <div class="gd-student-header-card mb-4">
        <p class="gd-kicker mb-1">New Ticket</p>
        <h3 class="h4 mb-2">Submit A Grade Dispute</h3>
        <p class="text-muted mb-0">Provide clear details and supporting evidence so your case can be reviewed quickly.</p>
    </div>

    <section class="gd-wire-block">
        <form id="studentDisputeForm" class="gd-student-form" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Dispute Type</label>
                    <select name="type" class="form-select" id="studentDisputeType" required>
                        <option value="unit">Unit Dispute</option>
                        <option value="general">General Dispute</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority" required>
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>

            <div id="unitFields" class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Unit Code</label>
                    <input class="form-control" name="unit_code" id="unitCodeInput" list="unitCodeList" placeholder="Type to search units">
                    <datalist id="unitCodeList"></datalist>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Course</label>
                    <input class="form-control" name="course" placeholder="BSc CS">
                </div>
            </div>

            <div id="generalFields" class="row g-3 mt-1 d-none">
                <div class="col-md-6">
                    <label class="form-label">Assign To</label>
                    <select class="form-select" name="assigned_role">
                        <option value="exam_officer">Exam Officer</option>
                        <option value="hod">Head of Department</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Dispute Title</label>
                <input class="form-control" name="title" required placeholder="Short summary of the issue">
            </div>

            <div class="mt-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="6" required placeholder="Include context, expected grade, and any relevant timeline."></textarea>
            </div>

            <div class="mt-3">
                <label class="form-label">Attachment (Optional)</label>
                <input class="form-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
                <p class="small text-muted mt-2 mb-0">Accepted formats: PDF, JPG, JPEG, PNG.</p>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-4">
                <p class="small mb-0" id="studentDisputeFormFeedback"></p>
                <div class="d-flex gap-2">
                    <a href="{{ route('portal.student.tickets') }}" class="btn btn-outline-dark">Cancel</a>
                    <button class="btn btn-dark" type="submit">Submit Dispute</button>
                </div>
            </div>
        </form>
    </section>
</section>
@endsection
