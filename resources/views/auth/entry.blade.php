@extends('layouts.guest')

@section('title', 'Login | Grade Dispute Portal')
@section('page_id', 'auth-login')

@section('content')
<div class="gd-auth-center">
    <section class="card border-0 shadow-sm gd-auth-card">
        <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">Welcome Back</h1>
                <p class="text-muted mb-0">Log in to continue.</p>
            </div>

            @if (request()->query('signup') === 'success')
                <div class="alert alert-success">Account created successfully. You can now log in.</div>
            @endif

            @if (request()->query('reset') === 'success')
                <div class="alert alert-success">Password updated successfully. Please log in with your new password.</div>
            @endif

            <form id="loginForm" class="d-grid gap-3">
                <div>
                    <label for="loginIdentifier" class="form-label">Identifier</label>
                    <input id="loginIdentifier" name="identifier" class="form-control" placeholder="S001 / L001 / ADMIN001" required>
                </div>
                <div>
                    <label for="loginPassword" class="form-label">Password</label>
                    <input id="loginPassword" name="password" class="form-control" type="password" required>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('portal.password.forgot') }}" class="small">Forgot password?</a>
                </div>

                <button class="btn btn-primary" type="submit">Login</button>
                <p class="small mb-0" id="loginFeedback"></p>
            </form>

            <hr class="my-4">
            <p class="text-center mb-0 text-muted">
                Don't have an account?
                <a href="{{ route('portal.signup') }}" class="fw-semibold">Sign up</a>
            </p>
        </div>
    </section>
</div>
@endsection
