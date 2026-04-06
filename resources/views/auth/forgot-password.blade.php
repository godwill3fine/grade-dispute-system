@extends('layouts.guest')

@section('title', 'Forgot Password | Grade Dispute Portal')
@section('page_id', 'auth-forgot-password')

@section('content')
<div class="gd-auth-center">
    <section class="card border-0 shadow-sm gd-auth-card">
        <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">Reset Password</h1>
                <p class="text-muted mb-0">Enter your identifier and set a new password.</p>
            </div>

            <form id="forgotPasswordForm" class="d-grid gap-3">
                <div>
                    <label for="forgotIdentifier" class="form-label">Identifier</label>
                    <input id="forgotIdentifier" name="identifier" class="form-control" placeholder="S001 / L001 / ADMIN001" required>
                </div>
                <div>
                    <label for="forgotPassword" class="form-label">New Password</label>
                    <input id="forgotPassword" name="password" class="form-control" type="password" minlength="6" required>
                </div>
                <div>
                    <label for="forgotPasswordConfirmation" class="form-label">Confirm New Password</label>
                    <input id="forgotPasswordConfirmation" name="password_confirmation" class="form-control" type="password" minlength="6" required>
                </div>

                <button class="btn btn-primary" type="submit">Update Password</button>
                <p class="small mb-0" id="forgotPasswordFeedback"></p>
            </form>

            <hr class="my-4">
            <p class="text-center mb-0 text-muted">
                Remembered your password?
                <a href="{{ route('portal.auth') }}" class="fw-semibold">Back to login</a>
            </p>
        </div>
    </section>
</div>
@endsection
