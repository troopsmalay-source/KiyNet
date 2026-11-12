<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'KiNet eBilling') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="{{ asset('assets/css/kinet.css') }}?v=theme-1" rel="stylesheet">
        <link href="{{ asset('assets/css/guest.css') }}?v=1" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="kinet-auth font-sans text-gray-900 antialiased">
        <main class="auth-page">
            <a class="auth-brand" href="{{ route('login') }}" aria-label="KiNet eBilling">
                <img src="{{ asset('assets/images/kinet-logo.svg') }}" alt="kiynet — PT Kreasi Indokarya Network">
            </a>
            <section class="auth-card">
                {{ $slot }}
            </section>
            <p class="auth-footer">KiNet eBilling <span>·</span> Manajemen layanan internet</p>
        </main>
    </body>
</html>
