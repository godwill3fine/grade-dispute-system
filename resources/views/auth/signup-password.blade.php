@extends('layouts.guest')

@section('title', 'Set Password | Grade Dispute Portal')
@section('page_id', 'auth-signup-password')

@section('content')
<section class="login-card">
    <div class="login-header">
        <span class="material-symbols-outlined brand-icon">password</span>
        <h2>Set Your Password</h2>
        <p>Create a password for identifier <strong>{{ $identifier }}</strong>.</p>
    </div>

    <form id="signupPasswordForm">
        <input type="hidden" name="identifier" id="signupPasswordIdentifier" value="{{ $identifier }}">
        <input type="hidden" name="role" id="signupPasswordRole" value="{{ $role }}">

        <div class="form-group">
            <label>Identifier</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">badge</span>
                <input class="form-control" value="{{ $identifier }}" readonly>
            </div>
        </div>

        <div class="form-group">
            <label for="signupPassword">Password</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">lock</span>
                <input id="signupPassword" name="password" class="form-control" type="password" minlength="6" required>
            </div>
        </div>

        <div class="form-group form-group-compact">
            <label for="signupPasswordConfirmation">Confirm Password</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">lock_reset</span>
                <input id="signupPasswordConfirmation" name="password_confirmation" class="form-control" type="password" minlength="6" required>
            </div>
        </div>

        <button class="btn btn-primary btn-block btn-login" type="submit">Create Account</button>
        <p class="small mb-0 auth-feedback" id="signupPasswordFeedback"></p>
    </form>

    <div class="login-footer">
        <p>Need to change identifier? <a href="{{ route('portal.signup') }}" class="signup-link">Go back</a></p>
    </div>
</section>
@endsection
