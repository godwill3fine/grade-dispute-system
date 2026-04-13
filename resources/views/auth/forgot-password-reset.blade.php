@extends('layouts.guest')

@section('title', 'Set New Password | Grade Dispute Portal')
@section('page_id', 'auth-forgot-reset')

@section('content')
<section class="login-card">
    <div class="login-header">
        <span class="material-symbols-outlined brand-icon">password</span>
        <h2>Create New Password</h2>
        <p>Set a new password for University ID <strong>{{ $identifier }}</strong>.</p>
    </div>

    @if (session('auth_error'))
        <div class="auth-inline-message warning">{{ session('auth_error') }}</div>
    @endif

    <form id="forgotPasswordResetForm">
        <div class="form-group">
            <label for="forgotPassword">New Password</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">lock</span>
                <input id="forgotPassword" name="password" class="form-control" type="password" minlength="6" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required>
            </div>
        </div>

        <div class="form-group form-group-compact">
            <label for="forgotPasswordConfirmation">Confirm New Password</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">lock_reset</span>
                <input id="forgotPasswordConfirmation" name="password_confirmation" class="form-control" type="password" minlength="6" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required>
            </div>
        </div>

        <button class="btn btn-primary btn-block btn-login" type="submit">Update Password</button>
        <p class="small mb-0 auth-feedback" id="forgotPasswordResetFeedback"></p>
    </form>

    <div class="login-footer">
        <p>Remembered your password? <a href="{{ route('portal.auth') }}" class="signup-link">Back to login</a></p>
    </div>
</section>
@endsection
