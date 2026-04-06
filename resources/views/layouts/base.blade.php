<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Grade Dispute Portal')</title>

    @auth
        <meta name="app-user-role" content="{{ auth()->user()->role }}">
        <meta name="app-user-identifier" content="{{ auth()->user()->identifier }}">
        <meta name="app-user-name" content="{{ auth()->user()->name }}">
    @endauth

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    @stack('head')
</head>
<body
    class="@yield('body_class')"
    data-page="@yield('page_id')"
    data-dispute-id="@yield('dispute_id')"
    data-user-role="{{ auth()->user()->role ?? '' }}"
    data-user-identifier="{{ auth()->user()->identifier ?? '' }}"
>
@yield('body')

@php
    $gdsUser = auth()->check() ? [
        'role' => auth()->user()->role,
        'identifier' => auth()->user()->identifier,
        'name' => auth()->user()->name,
    ] : null;
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
@stack('scripts')
</body>
</html>
