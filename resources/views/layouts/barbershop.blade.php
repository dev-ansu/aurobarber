@props([
    'barbershop',
    'title' => null,
    'description' => null,
])

@php
    $resolvedLogoUrl = $barbershop->logo_url
        ? (str_starts_with($barbershop->logo_url, 'http') ? $barbershop->logo_url : asset('storage/' . $barbershop->logo_url))
        : null;
    $resolvedBannerUrl = $barbershop->banner_url
        ? (str_starts_with($barbershop->banner_url, 'http') ? $barbershop->banner_url : asset('storage/' . $barbershop->banner_url))
        : null;
    $pageTitle = $title ?? ($barbershop->name . ' • Agendamento Online');
    $brandColor = $barbershop->brand_color ?: '#020617';
@endphp

<!DOCTYPE html>
<html lang="pt-BR" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    <link rel="manifest" href="{{ url($barbershop->slug . '/manifest.json') }}">
    <meta name="theme-color" content="{{ $brandColor }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $barbershop->name }}">
    <link rel="apple-touch-icon" href="{{ $resolvedLogoUrl ?: asset('pwa-icon.svg') }}">

    @if ($resolvedLogoUrl)
        <link rel="icon" type="image/png" href="{{ $resolvedLogoUrl }}">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700,900&display=swap" rel="stylesheet" />

    <!-- Tailwind & Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @if(config('services.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    <script>
        window.deferredPwaPrompt = null;
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            window.deferredPwaPrompt = e;
            window.dispatchEvent(new CustomEvent('pwa-ready'));
        });
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function() {});
            });
        }
    </script>

    <!-- Estilos de Marca Dinâmicos -->
    <x-barbershop-brand-styles :barbershop="$barbershop" />

    {{ $head ?? '' }}
    @yield('head')
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950 min-h-screen flex flex-col justify-between pb-24">

    @if(isset($topBar))
        {{ $topBar }}
    @endif

    <main class="flex-1">
        @if(isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    {{ $scripts ?? '' }}
    @yield('scripts')
</body>
</html>
