<x-filament-panels::page>
    <style>
        @media print {
            aside,
            nav,
            header,
            .fi-sidebar,
            .fi-topbar,
            .fi-breadcrumbs,
            .fi-header,
            .fi-page-header,
            .print\:hidden {
                display: none !important;
            }

            body,
            main,
            .fi-main,
            .fi-main-ctn,
            .fi-body,
            .fi-page {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                background: #ffffff !important;
            }
        }
    </style>

    <div class="space-y-6">
        <!-- Controles de Período e Filtros (Oculto na impressão) -->
        <section class="print:hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📊</span>
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">Filtros do DRE Gerencial</h2>
                        <p class="text-xs text-gray-500">Selecione o intervalo de datas e o regime contábil para apuração do resultado.</p>
                    </div>
                </div>

                <!-- Atalhos rápidos -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" wire:click="setQuickPeriod('this_month')" class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        Este Mês
                    </button>
                    <button type="button" wire:click="setQuickPeriod('last_month')" class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        Mês Passado
                    </button>
                    <button type="button" wire:click="setQuickPeriod('last_3_months')" class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        Últimos 3 Meses
                    </button>
                    <button type="button" wire:click="setQuickPeriod('this_year')" class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        Este Ano
                    </button>
                    <a href="{{ route('admin.dre.print', ['start' => $periodStart, 'end' => $periodEnd, 'regime' => $regime, 'school_id' => \Filament\Facades\Filament::getTenant()?->id]) }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300">
                        🖨️ Imprimir A4
                    </a>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Data Inicial</label>
                    <input type="date" wire:model="periodStart" class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Data Final</label>
                    <input type="date" wire:model="periodEnd" class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Regime Contábil</label>
                    <select wire:model="regime" class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                        <option value="caixa">Regime de Caixa (Valores Realmente Pagos/Recebidos)</option>
                        <option value="competencia">Regime de Competência (Vencimentos no Período)</option>
                    </select>
                </div>

                <div>
                    <x-filament::button wire:click="loadReport" icon="heroicon-o-arrow-path" class="w-full">
                        Atualizar DRE
                    </x-filament::button>
                </div>
            </div>
        </section>

        <!-- Cabeçalho de Impressão -->
        <div class="hidden print:block border-b-2 border-gray-900 pb-3 mb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-black text-gray-900">DEMONSTRATIVO DO RESULTADO DO EXERCÍCIO (DRE)</h1>
                    <p class="text-xs text-gray-600">Período: {{ $report['period']['start'] ?? '' }} até {{ $report['period']['end'] ?? '' }} · {{ $report['period']['regime_label'] ?? '' }}</p>
                </div>
                <div class="text-right text-xs text-gray-500">
                    Gerado em: {{ $generatedAt ?? now()->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        @if (!empty($report))
            <!-- Cards de Visão Executiva -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Receita Líquida -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 border-l-4 border-l-emerald-500">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Receita Líquida</span>
                    <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        R$ {{ number_format((float) ($report['revenue']['net'] ?? 0), 2, ',', '.') }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ $report['revenue']['count'] ?? 0 }} títulos recebidos
                    </p>
                </div>

                <!-- Despesas Operacionais -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 border-l-4 border-l-rose-500">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Despesas Operacionais</span>
                    <div class="mt-2 text-2xl font-black text-rose-600 dark:text-rose-400">
                        R$ {{ number_format((float) ($report['expenses']['total'] ?? 0), 2, ',', '.') }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ $report['expenses']['count'] ?? 0 }} contas quitadas
                    </p>
                </div>

                <!-- Resultado Líquido -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 border-l-4 {{ ($report['result']['is_profit'] ?? true) ? 'border-l-blue-500' : 'border-l-red-500' }}">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Resultado Operacional</span>
                    <div class="mt-2 text-2xl font-black {{ ($report['result']['is_profit'] ?? true) ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">
                        R$ {{ number_format((float) ($report['result']['net_amount'] ?? 0), 2, ',', '.') }}
                    </div>
                    <p class="mt-1 text-xs font-semibold {{ ($report['result']['is_profit'] ?? true) ? 'text-blue-500' : 'text-red-500' }}">
                        {{ ($report['result']['is_profit'] ?? true) ? '▲ Lucro Operacional' : '▼ Prejuízo Operacional' }}
                    </p>
                </div>

                <!-- Margem Operacional -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 border-l-4 border-l-amber-500">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Margem Operacional</span>
                    <div class="mt-2 text-2xl font-black text-amber-600 dark:text-amber-400">
                        {{ number_format((float) ($report['result']['operating_margin'] ?? 0), 1, ',', '.') }}%
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Sobre a Receita Líquida
                    </p>
                </div>
            </div>

            <!-- Tabela Estruturada do DRE -->
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-950/50">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>📋</span> Estrutura Contábil do DRE ({{ $report['period']['regime_label'] ?? '' }})
                    </h3>
                    <span class="text-xs text-gray-500">Valores em Reais (BRL)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100/70 text-xs font-bold text-gray-600 dark:bg-gray-950 dark:text-gray-400 uppercase">
                            <tr>
                                <th class="px-6 py-3 text-left">Classificação Contábil</th>
                                <th class="px-6 py-3 text-right">Valor Nominal</th>
                                <th class="px-6 py-3 text-right">% Receita</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <!-- 1. RECEITA BRUTA -->
                            <tr class="bg-emerald-50/30 dark:bg-emerald-950/10 font-bold text-emerald-900 dark:text-emerald-300">
                                <td class="px-6 py-3">1. (+) RECEITA OPERACIONAL BRUTA</td>
                                <td class="px-6 py-3 text-right">R$ {{ number_format((float) $report['revenue']['gross'], 2, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right">—</td>
                            </tr>
                            <tr class="text-xs text-gray-600 dark:text-gray-400 pl-4">
                                <td class="px-10 py-2">Mensalidades, Cursos e Matrículas</td>
                                <td class="px-6 py-2 text-right">R$ {{ number_format((float) $report['revenue']['installments_amount'], 2, ',', '.') }}</td>
                                <td class="px-6 py-2 text-right">—</td>
                            </tr>
                            @if ((float) $report['revenue']['fines'] > 0)
                                <tr class="text-xs text-gray-600 dark:text-gray-400">
                                    <td class="px-10 py-2">Multas e Juros Recebidos</td>
                                    <td class="px-6 py-2 text-right text-emerald-600">+ R$ {{ number_format((float) $report['revenue']['fines'], 2, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right">—</td>
                                </tr>
                            @endif

                            <!-- 2. DEDUÇÕES -->
                            <tr class="bg-rose-50/30 dark:bg-rose-950/10 font-semibold text-rose-800 dark:text-rose-300">
                                <td class="px-6 py-3">2. (-) DEDUÇÕES DA RECEITA BRUTA</td>
                                <td class="px-6 py-3 text-right text-rose-600">- R$ {{ number_format((float) $report['revenue']['deductions'], 2, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right">—</td>
                            </tr>
                            <tr class="text-xs text-gray-600 dark:text-gray-400">
                                <td class="px-10 py-2">Descontos Comerciais e Pontualidade Concedidos</td>
                                <td class="px-6 py-2 text-right text-rose-600">- R$ {{ number_format((float) $report['revenue']['deductions'], 2, ',', '.') }}</td>
                                <td class="px-6 py-2 text-right">—</td>
                            </tr>

                            <!-- 3. RECEITA LÍQUIDA -->
                            <tr class="bg-emerald-100/50 dark:bg-emerald-950/30 font-black text-emerald-950 dark:text-emerald-100 text-base">
                                <td class="px-6 py-3.5">3. (=) RECEITA OPERACIONAL LÍQUIDA</td>
                                <td class="px-6 py-3.5 text-right">R$ {{ number_format((float) $report['revenue']['net'], 2, ',', '.') }}</td>
                                <td class="px-6 py-3.5 text-right">100,0%</td>
                            </tr>

                            <!-- Detalhamento de Receitas por Tipo -->
                            @if (!empty($report['revenue']['by_type']))
                                @foreach ($report['revenue']['by_type'] as $revType)
                                    <tr class="text-xs text-gray-500 dark:text-gray-400 italic">
                                        <td class="px-10 py-1.5">• {{ $revType['name'] }} ({{ $revType['count'] }} recebimentos)</td>
                                        <td class="px-6 py-1.5 text-right">R$ {{ number_format((float) $revType['total'], 2, ',', '.') }}</td>
                                        <td class="px-6 py-1.5 text-right">{{ $revType['percentage'] }}%</td>
                                    </tr>
                                @endforeach
                            @endif

                            <!-- 4. DESPESAS OPERACIONAIS -->
                            <tr class="bg-rose-50/40 dark:bg-rose-950/20 font-bold text-rose-950 dark:text-rose-200">
                                <td class="px-6 py-3">4. (-) DESPESAS E CUSTOS OPERACIONAIS (CONTAS A PAGAR)</td>
                                <td class="px-6 py-3 text-right text-rose-600">- R$ {{ number_format((float) $report['expenses']['total'], 2, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-rose-600 font-bold">
                                    {{ $report['revenue']['net'] > 0 ? number_format(((float) $report['expenses']['total'] / (float) $report['revenue']['net']) * 100, 1, ',', '.') : '0' }}%
                                </td>
                            </tr>

                            <!-- Detalhamento de Despesas por Categoria -->
                            @forelse ($report['expenses']['by_type'] as $expType)
                                <tr class="text-xs text-gray-700 dark:text-gray-300">
                                    <td class="px-10 py-2">
                                        <span class="font-medium">• {{ $expType['name'] }}</span>
                                        <span class="text-[11px] text-gray-400">({{ $expType['count'] }} lançamentos)</span>
                                    </td>
                                    <td class="px-6 py-2 text-right text-rose-600">
                                        - R$ {{ number_format((float) $expType['total'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-2 text-right text-gray-500">
                                        {{ $expType['pct_revenue'] }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-10 py-2 text-xs text-gray-400 italic">Nenhuma despesa registrada no período.</td>
                                </tr>
                            @endforelse

                            <!-- 5. RESULTADO FINAL -->
                            <tr class="text-lg font-black {{ ($report['result']['is_profit'] ?? true) ? 'bg-blue-100/60 dark:bg-blue-950/40 text-blue-950 dark:text-blue-100' : 'bg-red-100/60 dark:bg-red-950/40 text-red-950 dark:text-red-100' }}">
                                <td class="px-6 py-4">
                                    5. (=) RESULTADO OPERACIONAL LÍQUIDO
                                    <span class="text-xs font-normal ml-2">({{ ($report['result']['is_profit'] ?? true) ? 'LUCRO DO PERÍODO' : 'PREJUÍZO DO PERÍODO' }})</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    R$ {{ number_format((float) $report['result']['net_amount'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ number_format((float) $report['result']['operating_margin'], 1, ',', '.') }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>

