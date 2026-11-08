<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Barra Superior de Seleção de Período e Atalhos Rápidos --}}
        <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-document-currency-dollar class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Período de Recebimentos & Emissão Fiscal</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Filtre os pagamentos confirmados para conferência e declaração no ISS Fortaleza (NFS-e).</p>
                    </div>
                </div>

                {{-- Atalhos Rápidos de Fechamento de Mês --}}
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        wire:click="setPeriod('current_month')"
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $activePreset === 'current_month' ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500' : 'border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        Mês Atual
                    </button>
                    <button
                        wire:click="setPeriod('previous_month')"
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $activePreset === 'previous_month' ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500' : 'border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        Mês Anterior (Fechamento)
                    </button>
                    <button
                        wire:click="setPeriod('last_30_days')"
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $activePreset === 'last_30_days' ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500' : 'border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        Últimos 30 Dias
                    </button>
                    <button
                        wire:click="setPeriod('current_year')"
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $activePreset === 'current_year' ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500' : 'border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        Ano {{ date('Y') }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Data Inicial:</label>
                    <input
                        type="date"
                        wire:model.live.debounce.500ms="periodStart"
                        class="block w-44 rounded-lg border-0 py-2 px-3 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:focus:ring-indigo-500 shadow-sm transition"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Data Final:</label>
                    <input
                        type="date"
                        wire:model.live.debounce.500ms="periodEnd"
                        class="block w-44 rounded-lg border-0 py-2 px-3 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:focus:ring-indigo-500 shadow-sm transition"
                    />
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Exibindo recebimentos entre <span class="font-bold text-gray-900 dark:text-white font-mono">{{ \Carbon\Carbon::parse($periodStart)->format('d/m/Y') }}</span> e <span class="font-bold text-gray-900 dark:text-white font-mono">{{ \Carbon\Carbon::parse($periodEnd)->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Cards de Resumo e Indicadores (KPIs) --}}
        @php $s = $this->stats; @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Total Geral Recebido --}}
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-2">
                <div class="flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span>Total Recebido</span>
                    <span class="p-1.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600">
                        <x-heroicon-o-currency-dollar class="w-4 h-4" />
                    </span>
                </div>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                    R$ {{ number_format($s['total_recebido'] ?? 0, 2, ',', '.') }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $s['total_registros'] ?? 0 }} pagamento(s) liquidado(s)
                </p>
            </div>

            {{-- Card 2: Matrículas e Entradas --}}
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-2">
                <div class="flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span>Matrículas & Entradas</span>
                    <span class="p-1.5 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600">
                        <x-heroicon-o-user-plus class="w-4 h-4" />
                    </span>
                </div>
                <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 font-mono">
                    R$ {{ number_format($s['total_matriculas'] ?? 0, 2, ',', '.') }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Valores de adesão / primeira parcela
                </p>
            </div>

            {{-- Card 3: Mensalidades Regulares --}}
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-2">
                <div class="flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span>Mensalidades</span>
                    <span class="p-1.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600">
                        <x-heroicon-o-calendar-days class="w-4 h-4" />
                    </span>
                </div>
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 font-mono">
                    R$ {{ number_format($s['total_mensalidades'] ?? 0, 2, ',', '.') }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Parcelas recorrentes de cursos
                </p>
            </div>

            {{-- Card 4: Alunos Únicos Atendidos --}}
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-2">
                <div class="flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span>Alunos Faturados</span>
                    <span class="p-1.5 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600">
                        <x-heroicon-o-academic-cap class="w-4 h-4" />
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                    {{ $s['alunos_distintos'] ?? 0 }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Alunos com baixa confirmada no período
                </p>
            </div>
        </div>

        {{-- Tabela Principal do Filament --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>

