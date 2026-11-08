<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @include('components.pwa-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="antialiased selection:bg-amber-500/30 selection:text-amber-200">
        {{ $slot }}

        @include('components.pwa-install-button')
        @include('components.global-error-notifier')
        @livewireScripts
    </body>
</html>
