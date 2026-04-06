@extends('layouts.base')

@section('body_class', 'gd-app-body')

@section('body')
    <div class="gd-app-shell">
        @include('partials.sidebar')

        <div class="gd-app-main">
            @include('partials.topbar')

            <main class="gd-content container-fluid py-4">
                <div id="globalAlert" class="alert d-none" role="alert"></div>
                @yield('content')
            </main>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastRegion"></div>
@endsection
