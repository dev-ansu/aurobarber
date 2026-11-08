@php
    $saasPath = trim((string) env('SAAS_PANEL_PATH', 'saas'), '/');
    $host = strtolower(request()->getHost());
    $isSaas = (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'saas') 
        || str_starts_with($host, 'master.')
        || request()->is("{$saasPath}*") 
        || request()->is('saas*');
    $isStudent = (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'student')
        || request()->is('portal-aluno*');

    $currentPanel = $isSaas ? 'saas' : ($isStudent ? 'student' : 'admin');
    $manifestUrl = url('/pwa/manifest.webmanifest') . '?panel=' . $currentPanel;
@endphp
<!-- Aurosoft Favicon, PWA Meta Tags & Service Worker -->
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ $manifestUrl }}">
<meta name="theme-color" content="#f59e0b">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ $isSaas ? 'SaaS Master' : ($isStudent ? 'Portal Aluno' : 'Aurosoft') }}">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(reg) {
                reg.update();
            }).catch(function(err) {
                console.warn('Aurosoft PWA SW registration failed: ', err);
            });
        });
    }

    // Capture install prompt for PWA installation button
    window.deferredPWAInstallPrompt = null;
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        window.deferredPWAInstallPrompt = e;
        window.dispatchEvent(new CustomEvent('pwa-installable', { detail: { prompt: e } }));
    });
</script>
