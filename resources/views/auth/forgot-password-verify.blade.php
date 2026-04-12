@extends('layouts.guest')

@section('title', 'Verify Code | Grade Dispute Portal')
@section('page_id', 'auth-forgot-verify')

@section('content')
<section class="login-card">
    <div class="login-header">
        <span class="material-symbols-outlined brand-icon">verified_user</span>
        <h2>Enter Verification Code</h2>
        <p>Enter the 6-digit code sent to <strong>{{ $maskedEmail }}</strong>.</p>
    </div>

    @if (session('auth_error'))
        <div class="auth-inline-message warning">{{ session('auth_error') }}</div>
    @endif

    <form id="forgotPasswordVerifyForm">
        <div class="form-group form-group-compact">
            <label for="forgotVerificationCode">Verification Code</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">pin</span>
                <input
                    id="forgotVerificationCode"
                    name="code"
                    class="form-control"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    pattern="\d{6}"
                    placeholder="Enter 6-digit code"
                    required
                >
            </div>
        </div>

        <button class="btn btn-primary btn-block btn-login" type="submit">Verify Code</button>
        <p class="small mb-0 auth-feedback" id="forgotPasswordVerifyFeedback"></p>
    </form>

    <div class="login-footer">
        <p>Use a different email? <a href="{{ route('portal.password.forgot.email') }}" class="signup-link">Go back</a></p>
    </div>
</section>
@endsection
