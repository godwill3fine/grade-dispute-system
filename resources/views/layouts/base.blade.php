<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    use Illuminate\Support\Str;

    $currentPageId = trim($__env->yieldContent('page_id'));
    $isAuthPage = Str::startsWith($currentPageId, 'auth-');
    $portalUserMeta = is_array($portalUserMeta ?? null) ? $portalUserMeta : [];
    $roleStylesheet = match (auth()->user()->role ?? null) {
        'student' => 'student-pages.css',
        'lecturer', 'staff' => 'lecturer-pages.css',
        'admin' => 'admin-pages.css',
        default => null,
    };
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Grade Dispute Portal')</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E">
    <link rel="shortcut icon" href="data:,">

    @auth
        <meta name="app-user-role" content="{{ auth()->user()->role }}">
        <meta name="app-user-identifier" content="{{ auth()->user()->identifier }}">
        <meta name="app-user-name" content="{{ auth()->user()->name }}">
    @endauth

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('assets/css/shared.css') }}" rel="stylesheet">
    @if ($isAuthPage)
        <link href="{{ asset('assets/css/login-page.css') }}" rel="stylesheet">
    @endif
    @if ($roleStylesheet)
        <link href="{{ asset('assets/css/'.$roleStylesheet) }}" rel="stylesheet">
    @endif
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    @stack('head')
</head>
<body
    class="@yield('body_class')"
    data-page="{{ $currentPageId }}"
    data-dispute-id="@yield('dispute_id')"
    data-user-role="{{ auth()->user()->role ?? '' }}"
    data-user-identifier="{{ auth()->user()->identifier ?? '' }}"
>
@yield('body')

@php
    $gdsUser = auth()->check()
        ? array_merge([
            'role' => auth()->user()->role,
            'identifier' => auth()->user()->identifier,
            'name' => auth()->user()->name,
        ], $portalUserMeta)
        : null;
@endphp
<script>
    window.GDS_CONFIG = {
        csrfToken: @json(csrf_token()),
        user: @json($gdsUser),
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@if ($isAuthPage)
    <script src="{{ asset('assets/js/login-page.js') }}"></script>
@else
    <script src="{{ asset('assets/js/portal-layout.js') }}"></script>
@endif
@stack('scripts')
</body>
</html>
