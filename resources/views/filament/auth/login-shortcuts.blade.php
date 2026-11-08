<div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800 space-y-4">
    @if (session('active_tenant_schema') === 'demo' || str_starts_with(request()->getHost(), 'demo.'))
        <div class="p-3.5 rounded-xl bg-gradient-to-r from-amber-500/15 via-amber-500/10 to-transparent border border-amber-500/40 text-amber-500 flex items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🧪</span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-xs text-amber-400 uppercase tracking-wider">Ambiente de Demonstração</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/20 text-amber-300">Acesso Livre</span>
                    </div>
                    <p class="text-[12px] text-gray-300 mt-1">
                        Login: <strong class="text-white font-mono bg-gray-800/80 px-1.5 py-0.5 rounded">demo@aurosoft.com.br</strong> • Senha: <strong class="text-white font-mono bg-gray-800/80 px-1.5 py-0.5 rounded">demo123</strong>
                    </p>
                </div>
            </div>
            <a href="{{ route('home') }}" class="shrink-0 px-2.5 py-1 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white font-semibold text-xs border border-gray-700 transition">
                Início
            </a>
        </div>
    @endif

    <div class="relative flex items-center justify-center">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase">
            <span class="bg-white dark:bg-gray-900 px-3 text-[11px] font-bold tracking-wider text-gray-500 dark:text-gray-400">
                Acesso a outros módulos
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
        <a href="{{ route('student.login') }}"
           class="group flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 hover:bg-amber-500/10 dark:bg-gray-800/40 dark:hover:bg-amber-500/10 p-3 transition-all duration-200 hover:border-amber-500/40">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-500 dark:bg-indigo-500/20 dark:text-indigo-300 font-bold text-base transition group-hover:scale-105">
                🎓
            </div>
            <div class="flex flex-col text-left overflow-hidden">
                <span class="text-xs font-bold text-gray-900 dark:text-gray-100 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition truncate">
                    Portal do Aluno
                </span>
                <span class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                    Aulas, simulados e cursos
                </span>
            </div>
        </a>

        <a href="{{ url('/presenca') }}"
           class="group flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 hover:bg-amber-500/10 dark:bg-gray-800/40 dark:hover:bg-amber-500/10 p-3 transition-all duration-200 hover:border-amber-500/40">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-500 dark:bg-cyan-500/20 dark:text-cyan-300 font-bold text-base transition group-hover:scale-105">
                🪪
            </div>
            <div class="flex flex-col text-left overflow-hidden">
                <span class="text-xs font-bold text-gray-900 dark:text-gray-100 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition truncate">
                    Totem de Presença
                </span>
                <span class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                    Registro de ponto em sala
                </span>
            </div>
        </a>
    </div>

    <div class="pt-4 border-t border-gray-200/60 dark:border-gray-800/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-gray-400 dark:text-gray-500 select-none">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 hover:text-amber-500 dark:hover:text-amber-400 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Página Inicial</span>
        </a>

        {{-- Logo Aurosoft na parte inferior, abaixo de "Acesso a outros módulos" --}}
        <div class="flex items-center gap-2 select-none opacity-85 hover:opacity-100 transition-opacity">
            <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-gradient-to-tr from-amber-500 via-amber-400 to-amber-300 text-slate-950 font-black shadow-sm text-[10px] select-none">
                A
            </div>
            <div class="flex items-center gap-1.5 leading-none">
                <span class="text-xs font-black tracking-wider uppercase text-slate-700 dark:text-slate-300">Aurosoft</span>
                <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">• Gestão Escolar & LMS</span>
            </div>
        </div>
    </div>
</div>

