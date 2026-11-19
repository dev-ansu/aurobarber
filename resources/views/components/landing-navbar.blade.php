@props([
    'activeBarbershops' => collect(),
])

<header class="sticky top-0 z-40 bg-slate-950/90 backdrop-blur-md border-b border-slate-800/80 transition">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-3 xl:gap-6">
        {{-- Logo --}}
        <a href="/" class="flex items-center gap-3 group shrink-0">
            <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-gradient-to-tr from-amber-600 via-amber-500 to-amber-300 flex items-center justify-center text-slate-950 font-black text-xl shadow-lg shadow-amber-500/25 group-hover:scale-105 transition-transform shrink-0">
                💈
            </div>
            <div>
                <span class="text-sm sm:text-base font-black tracking-tight text-white block group-hover:text-amber-400 transition whitespace-nowrap">
                    Auro<span class="text-amber-400">Barber</span>
                    <span class="text-amber-400 text-[10px] sm:text-xs font-mono font-bold uppercase ml-1 px-1.5 py-0.5 rounded bg-amber-500/15 border border-amber-500/30">PRO</span>
                </span>
                <span class="text-[10px] sm:text-[11px] text-slate-400 font-medium block leading-tight whitespace-nowrap">
                    by Aurosoft Tecnologia
                </span>
            </div>
        </a>

        {{-- Links de Navegação Desktop --}}
        <nav class="hidden xl:flex items-center gap-5 xl:gap-6 text-xs font-bold text-slate-300 whitespace-nowrap">
            <a href="/#recursos" class="hover:text-amber-400 transition">Tour pelo Sistema</a>
            <a href="/#modulos" class="hover:text-amber-400 transition">Módulos</a>
            <a href="/#calculadora" class="hover:text-amber-400 transition flex items-center gap-1">
                <span>Simulador de Lucro</span>
                <span class="text-[9px] bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded-full font-black">NOVO</span>
            </a>
            <a href="/#comparativo" class="hover:text-amber-400 transition">Por que o AuroBarber?</a>
            <a href="/#planos" class="hover:text-amber-400 transition">Planos</a>
            <a href="/#faq" class="hover:text-amber-400 transition">Dúvidas</a>
        </nav>

        {{-- Ações Direitas --}}
        <div class="hidden sm:flex items-center gap-2 xl:gap-3 shrink-0">
            <button
                type="button"
                @click="tenantModalOpen = true"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-700 bg-slate-900/80 hover:bg-slate-800 text-slate-200 text-xs font-bold transition shadow-sm whitespace-nowrap shrink-0 cursor-pointer"
            >
                <span>💈</span>
                <span>Acessar Barbearia</span>
            </button>

            <a
                href="{{ url('/admin') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-amber-500/40 bg-amber-950/30 hover:bg-amber-900/50 text-amber-300 text-xs font-bold transition shadow-sm whitespace-nowrap shrink-0"
            >
                <span>🔑</span>
                <span>Login do Painel</span>
            </a>

            <button
                type="button"
                @click="selectedPlanName = 'Demonstração Comercial'; selectedPlanId = null; leadModalOpen = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:brightness-110 text-slate-950 text-xs font-black transition shadow-lg shadow-amber-500/25 active:scale-95 whitespace-nowrap shrink-0 cursor-pointer"
            >
                <span>🚀</span>
                <span>Criar Minha Barbearia</span>
            </button>
        </div>

        {{-- Botões Mobile --}}
        <div class="flex sm:hidden items-center gap-2">
            <a
                href="{{ url('/admin') }}"
                class="px-3 py-2 rounded-xl border border-slate-700 bg-slate-900 text-slate-200 font-bold text-xs"
            >
                Entrar
            </a>
            <button
                type="button"
                @click="selectedPlanName = 'Demonstração Comercial'; selectedPlanId = null; leadModalOpen = true"
                class="px-3 py-2 rounded-xl bg-amber-500 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20"
            >
                Contratar
            </button>
        </div>
    </div>
</header>
