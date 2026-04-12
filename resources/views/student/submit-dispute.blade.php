@extends('layouts.app')

@section('title', 'Submit Dispute | Grade Dispute')
@section('page_id', 'student-submit-dispute')
@section('page_title', 'Submit Dispute')

@section('content')
<div class="page-header">
    <span class="eyebrow">NEW TICKET</span>
    <h1>Submit A Grade Dispute</h1>
    <p>Provide clear details and supporting evidence so your case can be reviewed quickly.</p>
</div>

<div class="form-card">
    <form id="studentDisputeForm" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label for="studentDisputeType">Dispute Type</label>
                <select name="type" class="form-control" id="studentDisputeType" required>
                    <option value="unit">Unit Dispute</option>
                    <option value="general">General Dispute</option>
                </select>
            </div>
            <div class="form-group">
                <label for="studentDisputePriority">Priority</label>
                <select class="form-control" name="priority" id="studentDisputePriority" required>
                    <option value="medium" selected>Medium</option>
                    <option value="low">Low</option>
                    <option value="high">High</option>
                </select>
            </div>
        </div>

        <div id="unitFields">
            <div class="form-row">
                <div class="form-group">
                    <label for="unitCodeInput">Unit Code</label>
                    <input class="form-control" name="unit_code" id="unitCodeInput" list="unitCodeList" placeholder="Type to search units">
                    <datalist id="unitCodeList"></datalist>
                </div>
                <div class="form-group">
                    <label for="studentCourseInput">Course</label>
                    <input class="form-control" name="course" id="studentCourseInput" placeholder="BSc CS">
                </div>
            </div>
        </div>

        <div id="generalFields" class="d-none">
            <div class="form-row">
                <div class="form-group">
                    <label for="generalAssignRole">Assign To</label>
                    <select class="form-control" name="assigned_role" id="generalAssignRole">
                        <option value="exam_officer">Exam Officer</option>
                        <option value="hod">Head of Department</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div></div>
            </div>
        </div>

        <div class="form-group">
            <label for="studentDisputeTitle">Dispute Title</label>
            <input class="form-control" id="studentDisputeTitle" name="title" required placeholder="Short summary of the issue">
        </div>

        <div class="form-group">
            <label for="studentDisputeDescription">Description</label>
            <textarea class="form-control" id="studentDisputeDescription" name="description" rows="6" required placeholder="Include context, expected grade, and any relevant timeline."></textarea>
        </div>

        <div class="form-group">
            <label for="studentDisputeAttachment">Attachment (Optional)</label>
            <input class="form-control file-input" id="studentDisputeAttachment" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
            <span class="form-text">Accepted formats: PDF, JPG, JPEG, PNG.</span>
        </div>

        <div class="form-actions">
            <p class="auth-feedback mb-0" id="studentDisputeFormFeedback"></p>
            <div class="header-actions">
                <a href="{{ route('portal.student.tickets') }}" class="btn btn-outline">Cancel</a>
                <button class="btn btn-primary" type="submit">Submit Dispute</button>
            </div>
        </div>
    </form>
</div>
@endsection
