<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Aviso do Sistema' }} — AuroBarber</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900/90 p-8 shadow-2xl backdrop-blur-xl text-center">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/20 text-3xl text-amber-400">
            ⚠️
        </div>

        <h1 class="text-xl font-extrabold text-white tracking-tight sm:text-2xl">
            {{ $title ?? 'Aviso do Sistema' }}
        </h1>

        <p class="mt-3 text-sm text-slate-400 leading-relaxed">
            {{ $message ?? 'Ocorreu uma situação inesperada. Por favor tente novamente.' }}
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            @if(isset($actionUrl))
                <a href="{{ $actionUrl }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-500 px-6 py-3 text-sm font-bold text-slate-950 hover:bg-cyan-400 transition shadow-lg shadow-cyan-500/20">
                    {{ $actionLabel ?? 'Voltar' }}
                </a>
            @endif

            <a href="{{ url('/') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800/80 px-6 py-3 text-sm font-bold text-slate-200 hover:bg-slate-700 transition">
                Painel Administrativo
            </a>
        </div>
    </div>
</body>
</html>
