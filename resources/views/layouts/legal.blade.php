@props([
    'title' => 'AuroBarber',
    'badge' => 'Documento Institucional',
    'backUrl' => '/',
    'backLabel' => '← Voltar ao Início',
])

<!DOCTYPE html>
<html lang="pt-BR" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} • AuroBarber</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>

    {{ $head ?? '' }}
    @yield('head')
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between font-sans antialiased selection:bg-amber-500 selection:text-slate-950">

    {{-- HEADER --}}
    <header class="border-b border-slate-800/80 bg-slate-950/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="{{ $backUrl }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-600 via-amber-500 to-amber-300 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg shadow-amber-500/20 group-hover:scale-105 transition">
                    💈
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white block">AuroBarber</span>
                    <span class="text-[10px] uppercase tracking-widest text-amber-400 font-bold block">{{ $badge }}</span>
                </div>
            </a>
            <a href="{{ $backUrl }}" class="text-sm font-semibold text-slate-400 hover:text-amber-400 transition flex items-center gap-1.5">
                {{ $backLabel }}
            </a>
        </div>
    </header>

    {{-- CONTEÚDO PRINCIPAL --}}
    <main class="flex-1 py-12 px-6">
        @if(isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    {{-- FOOTER --}}
    <footer class="border-t border-slate-900 bg-slate-950 py-8 px-6 text-center text-xs text-slate-500">
        <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <p>&copy; {{ date('Y') }} AuroBarber &bull; Uma solução <strong class="text-slate-400">Aurosoft Tecnologia</strong>. Todos os direitos reservados.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('terms') }}" class="hover:text-amber-400 transition">Termos de Uso</a>
                <span>•</span>
                <a href="{{ route('privacy') }}" class="hover:text-amber-400 transition">Privacidade (LGPD)</a>
                <span>•</span>
                <a href="{{ url('/') }}" class="hover:text-amber-400 transition">Página Inicial</a>
            </div>
        </div>
    </footer>

    {{ $scripts ?? '' }}
    @yield('scripts')
</body>
</html>
