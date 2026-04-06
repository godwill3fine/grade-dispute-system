@extends('layouts.guest')

@section('title', 'Sign Up | Grade Dispute Portal')
@section('page_id', 'auth-signup-id')

@section('content')
<div class="gd-auth-center">
    <section class="card border-0 shadow-sm gd-auth-card">
        <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">Create Your Account</h1>
                <p class="text-muted mb-0">Enter your identifier to begin sign up.</p>
            </div>

            <form id="signupIdentifyForm" class="d-grid gap-3">
                <div>
                    <label for="signupIdentifier" class="form-label">Identifier</label>
                    <input id="signupIdentifier" name="identifier" class="form-control" placeholder="S001 / L001 / ADMIN001" required>
                </div>
                <button class="btn btn-primary" type="submit">Continue</button>
                <p class="small mb-0" id="signupIdentifyFeedback"></p>
            </form>

            <hr class="my-4">
            <p class="text-center mb-0 text-muted">
                Already have an account?
                <a href="{{ route('portal.auth') }}" class="fw-semibold">Back to login</a>
            </p>
        </div>
    </section>
</div>
@endsection
