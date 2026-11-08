<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    {{-- Cards Informativos / Resumo Executivo --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg">
                <x-heroicon-o-pause-circle class="w-8 h-8" />
            </div>
            <div>
                <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Total Trancados</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total_suspended'] }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Contratos suspensos ativos</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-lg">
                <x-heroicon-o-bell-alert class="w-8 h-8" />
            </div>
            <div>
                <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Próximos de Voltar</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['upcoming_15'] }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Nos próximos 15 dias</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg">
                <x-heroicon-o-exclamation-triangle class="w-8 h-8" />
            </div>
            <div>
                <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Prazo Vencido</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['overdue'] }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Passou da previsão de volta</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-lg">
                <x-heroicon-o-check-circle class="w-8 h-8" />
            </div>
            <div>
                <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Reativados</div>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['reactivated_30'] }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Últimos 30 dias</div>
            </div>
        </div>
    </div>

    {{-- Tabela Gerencial com Ações e WhatsApp --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        {{ $this->table }}
    </div>
</x-filament-panels::page>

