<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galeria de Telas de Erro — Aurosoft LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 p-6 flex flex-col items-center justify-center">
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-4xl">
        <div class="text-center mb-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20 mb-3">
                Ambiente de Demonstração
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                Galeria de Telas de Erro do Aurosoft
            </h1>
            <p class="mt-2 text-sm text-slate-400">
                Visualize e teste a experiência do usuário e os botões de retorno de cada código de erro:
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Card 404 -->
            <a href="{{ url('/preview-errors/404') }}"
               class="group block p-6 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-cyan-500/50 hover:bg-slate-900 transition shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center border border-cyan-500/20 group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold text-white group-hover:text-cyan-400 transition">Erro 404</span>
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-400">Não Encontrado</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Exibida quando um link ou registro foi removido ou não existe.</p>
                    </div>
                </div>
            </a>

            <!-- Card 403 -->
            <a href="{{ url('/preview-errors/403') }}"
               class="group block p-6 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-rose-500/50 hover:bg-slate-900 transition shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center border border-rose-500/20 group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold text-white group-hover:text-rose-400 transition">Erro 403</span>
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-400">Acesso Negado</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Exibida quando o usuário não possui permissão no Shield para a tela.</p>
                    </div>
                </div>
            </a>

            <!-- Card 500 -->
            <a href="{{ url('/preview-errors/500') }}"
               class="group block p-6 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-amber-500/50 hover:bg-slate-900 transition shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20 group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold text-white group-hover:text-amber-400 transition">Erro 500</span>
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-400">Falha no Servidor</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Exibida em falhas de banco ou exceções não tratadas no servidor.</p>
                    </div>
                </div>
            </a>

            <!-- Card 419 -->
            <a href="{{ url('/preview-errors/419') }}"
               class="group block p-6 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-900 transition shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20 group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold text-white group-hover:text-indigo-400 transition">Erro 419</span>
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-400">Sessão Expirada</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Exibida quando a sessão ou token CSRF do usuário expira.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
                &larr; Voltar para o Painel Administrativo
            </a>
        </div>
    </div>
</body>
</html>
