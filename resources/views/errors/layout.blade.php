<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Aurosoft LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 flex items-center justify-center p-4 selection:bg-amber-500/20 selection:text-amber-300">
    <!-- Efeito de iluminação de fundo -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-lg rounded-3xl border border-slate-800/80 bg-slate-900/90 p-8 sm:p-10 shadow-2xl backdrop-blur-2xl text-center">
        <!-- Ícone / Badge de Status -->
        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-3xl @yield('badge_class', 'bg-slate-800 text-slate-300 border border-slate-700/60') shadow-inner">
            @yield('icon')
        </div>

        <!-- Código do Erro -->
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase mb-3 @yield('tag_class', 'bg-slate-800/80 text-slate-300 border border-slate-700')">
            @yield('code')
        </div>

        <!-- Título -->
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            @yield('heading')
        </h1>

        <!-- Descrição -->
        <p class="mt-3 text-sm sm:text-base text-slate-400 leading-relaxed max-w-md mx-auto">
            @yield('message')
        </p>

        @hasSection('detail')
            <div class="mt-4 p-3 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 text-left font-mono break-all max-h-24 overflow-y-auto">
                @yield('detail')
            </div>
        @endif

        <!-- Botões de Ação -->
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            @yield('actions')
        </div>

        <!-- Rodapé discreto -->
        <div class="mt-8 pt-6 border-t border-slate-800/60 text-xs text-slate-500 flex items-center justify-center gap-1.5">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Aurosoft LMS &bull; Gestão Escolar Inteligente
        </div>
    </div>
</body>
</html>

