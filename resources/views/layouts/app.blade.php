<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $htmlClass ?? 'scroll-smooth' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name', 'AuroBarber') }}</title>

        @include('components.pwa-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles

        {{ $head ?? '' }}
        @yield('head')

        @if(filled(config('services.turnstile.site_key')))
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
    </head>
    <body class="{{ $bodyClass ?? 'antialiased selection:bg-amber-500/30 selection:text-amber-200 min-h-screen' }}">
        @if(isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif

        @include('components.pwa-install-button')
        @include('components.global-error-notifier')
        @livewireScripts

        {{ $scripts ?? '' }}
        @yield('scripts')
    </body>
</html>
