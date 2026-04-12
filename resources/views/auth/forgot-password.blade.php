@extends('layouts.guest')

@section('title', 'Forgot Password | Grade Dispute Portal')
@section('page_id', 'auth-forgot-password')

@section('content')
<section class="login-card">
    <div class="login-header">
        <span class="material-symbols-outlined brand-icon">lock_reset</span>
        <h2>Forgot Password</h2>
        <p>Enter your University ID to begin password recovery.</p>
    </div>

    @if (session('auth_error'))
        <div class="auth-inline-message warning">{{ session('auth_error') }}</div>
    @endif

    <form id="forgotPasswordIdentifyForm">
        <div class="form-group form-group-compact">
            <label for="forgotIdentifier">University ID</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">badge</span>
                <input id="forgotIdentifier" name="identifier" class="form-control" placeholder="e.g. SCT000-0000/1999" required>
            </div>
        </div>

        <button class="btn btn-primary btn-block btn-login" type="submit">Continue</button>
        <p class="small mb-0 auth-feedback" id="forgotPasswordIdentifyFeedback"></p>
    </form>

    <div class="login-footer">
        <p>Remembered your password? <a href="{{ route('portal.auth') }}" class="signup-link">Back to login</a></p>
    </div>
</section>
@endsection
