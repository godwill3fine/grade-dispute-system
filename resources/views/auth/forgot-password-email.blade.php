@extends('layouts.guest')

@section('title', 'Verify Email | Grade Dispute Portal')
@section('page_id', 'auth-forgot-email')

@section('content')
<section class="login-card">
    <div class="login-header">
        <span class="material-symbols-outlined brand-icon">alternate_email</span>
        <h2>Verify Your Email</h2>
        <p>Enter the email address linked to University ID <strong>{{ $identifier }}</strong>.</p>
    </div>

    @if (session('auth_error'))
        <div class="auth-inline-message warning">{{ session('auth_error') }}</div>
    @endif

    <form id="forgotPasswordEmailForm">
        <div class="form-group form-group-compact">
            <label for="forgotResetEmail">Email Address</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">mail</span>
                <input id="forgotResetEmail" name="email" class="form-control" type="email" placeholder="name@university.edu" required>
            </div>
        </div>

        <button class="btn btn-primary btn-block btn-login" type="submit">Send Verification Code</button>
        <p class="small mb-0 auth-feedback" id="forgotPasswordEmailFeedback"></p>
    </form>

    <div class="login-footer">
        <p>Need a different University ID? <a href="{{ route('portal.password.forgot') }}" class="signup-link">Start over</a></p>
    </div>
</section>
@endsection
