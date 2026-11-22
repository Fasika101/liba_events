<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d1b2a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>@yield('title', config('app.name')) — {{ __('public.discover') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/marketplace.css') }}?v=2">
    <script src="https://telegram.org/js/telegram-web-app.js" defer></script>
</head>
<body class="mp-body">
    <header class="mp-top">
        <a href="{{ route('public.discover') }}" class="mp-brand">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="mp-logo">
            @else
                <span class="mp-brand-mark" aria-hidden="true"><i class="fas fa-ticket-alt"></i></span>
            @endif
            <span class="mp-brand-text">
                <strong>{{ config('app.name', 'Liba Events') }}</strong>
                <small>{{ __('public.app_tagline') }}</small>
            </span>
        </a>
        <div class="mp-top-actions">
            <form method="POST" action="{{ route('public.locale') }}" class="mp-lang">
                @csrf
                <input type="hidden" name="locale" value="{{ app()->getLocale() === 'am' ? 'en' : 'am' }}">
                <button type="submit">{{ app()->getLocale() === 'am' ? 'EN' : 'አማ' }}</button>
            </form>
        </div>
    </header>

    <main class="mp-main">
        @if (session('status'))
            <div class="mp-flash">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <nav class="mp-tabbar" aria-label="Primary">
        <a href="{{ route('public.discover') }}" class="{{ request()->routeIs('public.discover') ? 'is-active' : '' }}">
            <i class="fas fa-compass" aria-hidden="true"></i>
            <span>{{ __('public.discover') }}</span>
        </a>
        <a href="{{ route('public.organizers') }}" class="{{ request()->routeIs('public.organizers*') ? 'is-active' : '' }}">
            <i class="fas fa-building" aria-hidden="true"></i>
            <span>{{ __('public.organizers') }}</span>
        </a>
        <a href="{{ route('public.profile') }}" class="{{ request()->routeIs('public.profile') || request()->routeIs('public.orders*') ? 'is-active' : '' }}">
            <i class="fas fa-ticket-alt" aria-hidden="true"></i>
            <span>{{ __('public.my_tickets') }}</span>
        </a>
    </nav>

    <script src="{{ asset('js/marketplace.js') }}?v=2" defer></script>
    @stack('scripts')
</body>
</html>
