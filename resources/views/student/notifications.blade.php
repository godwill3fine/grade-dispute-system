@extends('layouts.app')

@section('title', 'Notifications | Grade Dispute')
@section('page_id', 'student-notifications')
@section('page_title', 'Notifications')

@section('content')
<div class="page-header flex-header">
    <div class="header-text">
        <span class="eyebrow">UPDATES</span>
        <h1>Notification Center</h1>
        <p>Keep up with dispute comments, status changes, and review outcomes.</p>
    </div>
    <button class="btn btn-outline" type="button" id="studentMarkAllRead">Mark All As Read</button>
</div>

<section class="notifications-container" id="studentNotificationFeed">
    <div class="gd-notification-empty">Loading notifications...</div>
</section>
@endsection
