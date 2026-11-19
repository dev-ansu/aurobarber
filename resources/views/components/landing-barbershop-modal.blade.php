@props([
    'activeBarbershops' => collect(),
])

<div
    x-show="tenantModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        @click.away="tenantModalOpen = false"
        class="relative w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-7 shadow-2xl text-left space-y-5"
    >
        <button
            type="button"
            @click="tenantModalOpen = false"
            class="absolute top-5 right-5 text-slate-400 hover:text-white text-xl p-1 font-bold"
        >
            ✕
        </button>

        <div>
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest block">Portal de Acesso</span>
            <h3 class="text-xl font-black text-white mt-1">Acessar Barbearia</h3>
            <p class="text-xs text-slate-400 mt-1">Selecione a unidade para agendamento online ou entre no painel da sua barbearia.</p>
        </div>

        @php
            $matrizes = $activeBarbershops->whereNull('parent_id');
            if ($matrizes->isEmpty() && $activeBarbershops->isNotEmpty()) {
                $matrizes = $activeBarbershops;
            }
        @endphp

        @if($matrizes->isNotEmpty())
            <div class="space-y-2">
                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Barbearias & Unidades Ativas:</span>
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    @foreach($matrizes as $shop)
                        @php
                            $branches = $shop->branches ?? collect();
                            $hasBranches = $branches->isNotEmpty();
                        @endphp
                        <div class="rounded-2xl bg-slate-950 border border-slate-800 p-3 space-y-2 hover:border-amber-500/40 transition">
                            {{-- Matriz / Unidade Principal --}}
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="text-base shrink-0">💈</span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-xs font-bold text-white truncate">{{ $shop->name }}</span>
                                            @if($hasBranches)
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-300 border border-amber-500/30">
                                                    Rede • {{ 1 + $branches->count() }} unidades
                                                </span>
                                            @elseif($shop->parent_id)
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-300">
                                                    Filial
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-mono truncate block">
                                            {{ $shop->city ?: ($hasBranches ? 'Matriz' : 'Unidade Ativa') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a
                                        href="{{ url('/' . $shop->slug) }}"
                                        class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 text-[10px] font-bold hover:bg-emerald-500 hover:text-slate-950 transition"
                                    >
                                        Agendar
                                    </a>
                                    <a
                                        href="{{ url('/admin/' . $shop->slug) }}"
                                        class="px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-300 text-[10px] font-bold hover:bg-amber-500 hover:text-slate-950 transition"
                                    >
                                        Painel
                                    </a>
                                </div>
                            </div>

                            {{-- Filiais da Barbearia --}}
                            @if($hasBranches)
                                <div class="pt-2 border-t border-slate-900/80 space-y-1.5 pl-3">
                                    @foreach($branches as $branch)
                                        <div class="flex items-center justify-between p-2 rounded-xl bg-slate-900/70 border border-slate-800/80 hover:border-amber-500/30 transition">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="text-xs text-amber-400 shrink-0">↳</span>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-[11px] font-semibold text-slate-200 truncate">{{ $branch->name }}</span>
                                                        <span class="text-[9px] font-bold px-1 py-0.2 rounded bg-amber-500/15 text-amber-300">Filial</span>
                                                    </div>
                                                    <span class="text-[9px] text-slate-400 font-mono truncate block">{{ $branch->city ?: 'Filial' }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <a
                                                    href="{{ url('/' . $branch->slug) }}"
                                                    class="px-2 py-0.5 rounded-lg bg-emerald-500/20 text-emerald-300 text-[9px] font-bold hover:bg-emerald-500 hover:text-slate-950 transition"
                                                >
                                                    Agendar
                                                </a>
                                                <a
                                                    href="{{ url('/admin/' . $branch->slug) }}"
                                                    class="px-2 py-0.5 rounded-lg bg-amber-500/20 text-amber-300 text-[9px] font-bold hover:bg-amber-500 hover:text-slate-950 transition"
                                                >
                                                    Painel
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <form
            onsubmit="event.preventDefault(); const s = document.getElementById('barber-slug-input').value.trim().toLowerCase(); if(s) window.location.href = '/' + s;"
            class="space-y-2.5 pt-3 border-t border-slate-800"
        >
            <label class="text-[11px] font-bold text-slate-300 block">Ou digite o link da barbearia:</label>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs font-mono">/</span>
                    <input
                        id="barber-slug-input"
                        type="text"
                        placeholder="navalha-de-ouro"
                        class="w-full pl-6 pr-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-xs font-mono focus:outline-none focus:border-amber-500"
                    />
                </div>
                <button
                    type="submit"
                    class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black transition cursor-pointer shrink-0"
                >
                    Ir ➔
                </button>
            </div>
        </form>

        <div class="pt-2 text-center">
            <a
                href="{{ url('/admin') }}"
                class="text-xs text-amber-400 hover:underline font-bold"
            >
                Acessar Login Central Administrativo &rarr;
            </a>
        </div>
    </div>
</div>
