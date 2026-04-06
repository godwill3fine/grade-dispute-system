@extends('layouts.guest')

@section('title', 'Set Password | Grade Dispute Portal')
@section('page_id', 'auth-signup-password')

@section('content')
<div class="gd-auth-center">
    <section class="card border-0 shadow-sm gd-auth-card">
        <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">Set Your Password</h1>
                <p class="text-muted mb-0">Create a password for identifier <strong>{{ $identifier }}</strong>.</p>
            </div>

            <form id="signupPasswordForm" class="d-grid gap-3">
                <input type="hidden" name="identifier" id="signupPasswordIdentifier" value="{{ $identifier }}">
                <input type="hidden" name="role" id="signupPasswordRole" value="{{ $role }}">

                <div>
                    <label class="form-label">Identifier</label>
                    <input class="form-control" value="{{ $identifier }}" readonly>
                </div>
                <div>
                    <label for="signupPassword" class="form-label">Password</label>
                    <input id="signupPassword" name="password" class="form-control" type="password" minlength="6" required>
                </div>
                <div>
                    <label for="signupPasswordConfirmation" class="form-label">Confirm Password</label>
                    <input id="signupPasswordConfirmation" name="password_confirmation" class="form-control" type="password" minlength="6" required>
                </div>

                <button class="btn btn-primary" type="submit">Create Account</button>
                <p class="small mb-0" id="signupPasswordFeedback"></p>
            </form>

            <hr class="my-4">
            <p class="text-center mb-0 text-muted">
                Need to change identifier?
                <a href="{{ route('portal.signup') }}" class="fw-semibold">Go back</a>
            </p>
        </div>
    </section>
</div>
@endsection
