@extends('layouts.base')

@section('body_class', 'gd-app-body')

@section('body')
    {{-- Signed-in pages share the responsive sidebar, topbar, content slot, and toast region. --}}
    <div class="wrapper">
        <div class="overlay"></div>
        @include('partials.sidebar')

        <div class="main">
            @include('partials.topbar')

            <main class="content">
                <div id="globalAlert" class="alert d-none app-inline-alert" role="alert"></div>
                @yield('content')
            </main>

            <footer class="footer">
                <p>&copy; 2026 Grade Dispute System. All rights reserved.</p>
            </footer>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastRegion"></div>
@endsection
