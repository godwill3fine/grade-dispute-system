@extends('layouts.guest')

@section('title', 'Sign Up | Grade Dispute Portal')
@section('page_id', 'auth-signup-id')

@section('content')
<section class="login-card">
    <div class="login-header">
        <span class="material-symbols-outlined brand-icon">person_add</span>
        <h2>Create Your Account</h2>
        <p>Enter your identifier to begin sign up.</p>
    </div>

    <form id="signupIdentifyForm">
        <div class="form-group form-group-compact">
            <label for="signupIdentifier">University ID</label>
            <div class="input-icon-wrapper">
                <span class="material-symbols-outlined input-icon">badge</span>
                <input id="signupIdentifier" name="identifier" class="form-control" placeholder="e.g. SCT000-0000/1999" required>
            </div>
        </div>

        <button class="btn btn-primary btn-block btn-login" type="submit">Continue</button>
        <p class="small mb-0 auth-feedback" id="signupIdentifyFeedback"></p>
    </form>

    <div class="login-footer">
        <p>Already have an account? <a href="{{ route('portal.auth') }}" class="signup-link">Back to login</a></p>
    </div>
</section>
@endsection
