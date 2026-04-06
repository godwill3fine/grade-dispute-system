@extends('layouts.app')

@section('title', 'Notifications | Grade Dispute')
@section('page_id', 'student-notifications')
@section('page_title', 'Notifications')

@section('content')
<section class="gd-student-page gd-student-notifications">
    <div class="gd-student-header-card mb-4">
        <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
            <div>
                <p class="gd-kicker mb-1">Updates</p>
                <h3 class="h4 mb-2">Notification Center</h3>
                <p class="text-muted mb-0">Keep up with dispute comments, status changes, and review outcomes.</p>
            </div>
            <button class="btn btn-outline-dark" type="button" id="studentMarkAllRead">Mark All As Read</button>
        </div>
    </div>

    <section class="gd-wire-block p-0 overflow-hidden">
        <div id="studentNotificationFeed" class="gd-student-notification-feed">
            <div class="gd-notification-row text-muted">Loading notifications...</div>
        </div>
    </section>
</section>
@endsection
