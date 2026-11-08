@php
    $allowed = $allowedTabs ?? [
        'geral' => true,
        'captacao' => true,
        'financeiro' => true,
        'pedagogico' => true,
        'todos' => true,
    ];
@endphp

<div
    x-data="{
        currentTab: (function() {
            const urlTab = new URLSearchParams(window.location.search).get('tab');
            const allowed = @js($allowed);
            if (urlTab && allowed[urlTab]) return urlTab;
            if (allowed['geral']) return 'geral';
            for (const [key, val] of Object.entries(allowed)) {
                if (val) return key;
            }
            return 'geral';
        })(),
        switchTab(tab) {
            this.currentTab = tab;
            try {
                const url = new URL(window.location);
                if (tab === 'geral') {
                    url.searchParams.delete('tab');
                } else {
                    url.searchParams.set('tab', tab);
                }
                window.history.replaceState({}, '', url);
            } catch (e) {}

            this.applyFilter(tab);
        },
        applyFilter(tab) {
            const elements = document.querySelectorAll('[data-dashboard-category]');
            elements.forEach(el => {
                const attr = el.getAttribute('data-dashboard-category') || '';
                const categories = attr.split(' ').map(c => c.trim()).filter(Boolean);
                const isMatch = (tab === 'todos' || categories.includes(tab));

                // Localiza o nó wrapper pai que ocupa espaço na grade do Filament
                let cell = el;
                while (cell.parentElement && !cell.parentElement.classList.contains('fi-sc') && cell.parentElement.tagName !== 'MAIN') {
                    if (cell.parentElement.classList.contains('grid') || cell.parentElement.classList.contains('fi-grid-ctn')) {
                        break;
                    }
                    cell = cell.parentElement;
                }

                if (isMatch) {
                    el.style.display = '';
                    if (cell !== el) cell.style.display = '';
                } else {
                    el.style.display = 'none';
                    if (cell !== el) cell.style.display = 'none';
                }
            });
        }
    }"
    x-init="
        $nextTick(() => {
            applyFilter(currentTab);
        });
        setTimeout(() => applyFilter(currentTab), 150);
    "
    class="space-y-4 mb-2"
>
    {{-- Banner de Boas-vindas e Contexto da Unidade --}}
    <div class="flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 shadow-sm">
        <div class="flex items-center gap-3.5">
            <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-300 flex items-center justify-center text-slate-950 text-xl font-black shadow-lg shadow-amber-500/20 shrink-0">
                🏫
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-black text-white tracking-tight">
                        {{ $school ? ($school->trade_name ?: $school->legal_name) : 'Aurosoft Academy' }}
                    </h2>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Unidade Operacional
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">
                    Olá, <strong>{{ $user ? explode(' ', $user->name)[0] : 'Gestor' }}</strong>! Veja o resumo executivo antes de consultar os relatórios completos.
                </p>
            </div>
        </div>

        {{-- Atalho Rápido para a Vitrine Web da Escola --}}
        @if($school && ($school->showcase_enabled ?? true))
            <a
                href="{{ $school->slug ? 'https://' . $school->slug . '.' . config('app.saas_domain', env('SAAS_DOMAIN', 'aurosoft.com.br')) . '/vitrine' : url('/vitrine/' . $school->id) }}"
                target="_blank"
                class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700/80 transition active:scale-95 group shadow-sm"
            >
                <span>🌐 Vitrine Pública</span>
                @if($todayVisitors > 0)
                    <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 text-[10px] font-mono font-bold">
                        {{ $todayVisitors }} hoje
                    </span>
                @endif
                <span class="group-hover:translate-x-0.5 transition-transform text-slate-400">➔</span>
            </a>
        @endif
    </div>

    {{-- Abas de Navegação Setoriais do Dashboard (Filtragem Instantânea em 0ms com Respeito Rigoroso a Permissões) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar text-xs">
        {{-- Aba 1: Geral & Operação --}}
        @if($allowed['geral'] ?? true)
            <button
                type="button"
                @click="switchTab('geral')"
                :class="currentTab === 'geral' ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-md shadow-amber-500/20 font-black' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800'"
                class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shrink-0 cursor-pointer border"
            >
                <span>⚡</span>
                <span>Visão Geral</span>
            </button>
        @endif

        {{-- Aba 2: Captação & Vitrine Web --}}
        @if($allowed['captacao'] ?? true)
            <button
                type="button"
                @click="switchTab('captacao')"
                :class="currentTab === 'captacao' ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-md shadow-amber-500/20 font-black' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800'"
                class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shrink-0 cursor-pointer border"
            >
                <span>🌐</span>
                <span>Captação & Vitrine Web</span>
                <span
                    :class="currentTab === 'captacao' ? 'bg-slate-950 text-amber-300' : 'bg-emerald-500/20 text-emerald-400'"
                    class="px-1.5 py-0.5 rounded text-[10px] uppercase font-mono font-bold"
                >
                    Novo
                </span>
            </button>
        @endif

        {{-- Aba 3: Financeiro & Cobrança --}}
        @if($allowed['financeiro'] ?? true)
            <button
                type="button"
                @click="switchTab('financeiro')"
                :class="currentTab === 'financeiro' ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-md shadow-amber-500/20 font-black' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800'"
                class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shrink-0 cursor-pointer border"
            >
                <span>💰</span>
                <span>Financeiro & Cobrança</span>
            </button>
        @endif

        {{-- Aba 4: Pedagógico & Alunos --}}
        @if($allowed['pedagogico'] ?? true)
            <button
                type="button"
                @click="switchTab('pedagogico')"
                :class="currentTab === 'pedagogico' ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-md shadow-amber-500/20 font-black' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800'"
                class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shrink-0 cursor-pointer border"
            >
                <span>🎓</span>
                <span>Pedagógico & Retenção</span>
            </button>
        @endif

        {{-- Aba 5: Todos os Indicadores --}}
        @if($allowed['todos'] ?? true)
            <button
                type="button"
                @click="switchTab('todos')"
                :class="currentTab === 'todos' ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-md shadow-amber-500/20 font-black' : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-800'"
                class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shrink-0 cursor-pointer border"
            >
                <span>📊</span>
                <span>Visão Completa</span>
            </button>
        @endif
    </div>
</div>
