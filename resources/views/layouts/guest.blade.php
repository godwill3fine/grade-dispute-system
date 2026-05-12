@extends('layouts.base')

@section('body_class', 'gd-guest-body')

@section('body')
    {{-- Guest pages share the theme toggle and animated canvas background. --}}
    <div class="theme-toggle-container">
        <span class="material-symbols-outlined" id="theme-toggle">dark_mode</span>
    </div>

    <canvas id="particle-canvas"></canvas>

    <main class="login-wrapper">
        @yield('content')
    </main>
@endsection
