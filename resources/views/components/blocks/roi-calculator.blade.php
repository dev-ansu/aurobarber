@props([
    'data' => [],
])

@php
    $badge = $data['section_badge'] ?? 'Simulador de Impacto Financeiro';
    $title = $data['title'] ?? 'Quanto sua barbearia pode faturar a mais por ano?';
    $subtitle = $data['subtitle'] ?? 'Ajuste abaixo a média de atendimentos diários da sua barbearia e veja o ganho estimado com redução de faltas, retorno de clientes e comanda de produtos:';
    $defaultCuts = (int) ($data['default_cuts_day'] ?? 25);
    $defaultTicket = (int) ($data['default_ticket'] ?? 50);
@endphp

<section id="calculadora" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80" x-data="{ calcCutsPerDay: {{ $defaultCuts }}, calcAvgTicket: {{ $defaultTicket }} }">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900/90 to-amber-950/20 p-8 sm:p-12 shadow-2xl space-y-10">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">{{ $badge }}</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white">{{ $title }}</h2>
                <p class="text-xs sm:text-sm text-slate-400">
                    {{ $subtitle }}
                </p>
            </div>

            {{-- Sliders Interativos --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-3xl mx-auto">
                <div class="space-y-3 bg-slate-950/60 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-400">Cortes realizados por dia:</span>
                        <span class="text-xl font-black text-amber-400 font-mono" x-text="calcCutsPerDay + ' cortes/dia'"></span>
                    </div>
                    <input
                        type="range"
                        min="5"
                        max="80"
                        step="1"
                        x-model="calcCutsPerDay"
                        class="w-full h-3 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-500"
                    />
                    <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                        <span>5/dia</span>
                        <span>25/dia</span>
                        <span>50/dia</span>
                        <span>80/dia</span>
                    </div>
                </div>

                <div class="space-y-3 bg-slate-950/60 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-400">Ticket Médio atual:</span>
                        <span class="text-xl font-black text-emerald-400 font-mono" x-text="'R$ ' + calcAvgTicket"></span>
                    </div>
                    <input
                        type="range"
                        min="25"
                        max="120"
                        step="5"
                        x-model="calcAvgTicket"
                        class="w-full h-3 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500"
                    />
                    <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                        <span>R$ 25</span>
                        <span>R$ 50</span>
                        <span>R$ 85</span>
                        <span>R$ 120</span>
                    </div>
                </div>
            </div>

            {{-- Resultados da Calculadora --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                    <span class="text-xs text-slate-400 font-medium block">Tempo Poupado no WhatsApp</span>
                    <div class="text-2xl sm:text-3xl font-black text-amber-400 font-mono mt-1" x-text="Math.round(calcCutsPerDay * 2.2) + 'h / mês'"></div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Com agendamento online automático</span>
                </div>

                <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                    <span class="text-xs text-slate-400 font-medium block">Receita Extra (Retorno + Bar)</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono mt-1" x-text="'+ R$ ' + (Math.round(calcCutsPerDay * 26 * calcAvgTicket * 0.16)).toLocaleString('pt-BR') + ' / mês'"></div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Clientes recuperados + produtos na comanda</span>
                </div>

                <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                    <span class="text-xs text-slate-400 font-medium block">Crescimento Anual Estimado</span>
                    <div class="text-2xl sm:text-3xl font-black text-sky-400 font-mono mt-1" x-text="'+ R$ ' + (Math.round(calcCutsPerDay * 26 * calcAvgTicket * 0.16 * 12)).toLocaleString('pt-BR') + ' / ano'"></div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Lucro retido sem taxas por agendamento</span>
                </div>
            </div>

            <div class="text-center pt-2">
                <button
                    type="button"
                    @click="leadModalOpen = true"
                    class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-emerald-500 bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 hover:to-emerald-300 text-slate-950 font-black text-xs transition shadow-lg shadow-emerald-500/25 active:scale-95 cursor-pointer"
                >
                    <span>🚀</span>
                    <span>Quero Aumentar Meu Faturamento</span>
                </button>
            </div>
        </div>
    </div>
</section>
