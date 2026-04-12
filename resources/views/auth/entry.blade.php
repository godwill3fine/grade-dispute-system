@extends('layouts.guest')

@section('title', 'Login | Grade Dispute Portal')
@section('page_id', 'auth-login')

@section('content')
<section class="login-card">
    <div class="login-header">
        <span class="material-symbols-outlined brand-icon">automation</span>
        <h2>Grade Dispute System</h2>
        <p>Enter your credentials to access your account.</p>
    </div>

    @if (session('auth_error'))
        <div class="auth-inline-message warning">{{ session('auth_error') }}</div>
    @endif

    @if (request()->query('signup') === 'success')
        <div class="auth-inline-message success">Account created successfully. You can now log in.</div>
    @endif

    @if (request()->query('reset') === 'success')
        <div class="auth-inline-message success">Password updated successfully. Please log in with your new password.</div>
    @endif

    <form id="loginForm">
        <div class="form-group">
            <label for="loginIdentifier">University ID</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">badge</span>
                <input id="loginIdentifier" name="identifier" class="form-control" placeholder="e.g. SCT000-0000/1999" required>
            </div>
        </div>

        <div class="form-group form-group-compact">
            <label for="loginPassword">Password</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">lock</span>
                <input id="loginPassword" name="password" class="form-control" type="password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required>
            </div>
        </div>

        <div class="form-options-row">
            <label class="remember-me">
                <input type="checkbox">
                <span>Remember me</span>
            </label>
            <a href="{{ route('portal.password.forgot') }}" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-login">Sign In</button>
        <p class="small mb-0 auth-feedback" id="loginFeedback"></p>
    </form>

    <div class="login-footer">
        <p>Don't have an account? <a href="{{ route('portal.signup') }}" class="signup-link">Sign up</a></p>
    </div>
</section>
@endsection
