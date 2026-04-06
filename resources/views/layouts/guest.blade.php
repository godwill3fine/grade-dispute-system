@extends('layouts.base')

@section('body_class', 'gd-guest-body')

@section('body')
    <div class="gd-guest-bg"></div>
    <main class="container py-5">
        @if (session('auth_error'))
            <div class="alert alert-warning border-0 shadow-sm mb-4">{{ session('auth_error') }}</div>
        @endif
        @yield('content')
    </main>
@endsection
