<x-filament-panels::page>
    <style>
        @media print {
            aside, nav, header, .fi-sidebar, .fi-topbar, .fi-breadcrumbs, .fi-header, .fi-page-header, .print\:hidden {
                display: none !important;
            }
            body, main, .fi-main, .fi-main-ctn, .fi-body, .fi-page {
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
        <!-- Controles de Período e Filtros -->
        <section class="print:hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📈</span>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Projeção de Fluxo de Caixa</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Confronto diário de mensalidades a receber versus despesas agendadas a pagar.</p>
                    </div>
                </div>

                <!-- Atalhos rápidos de período -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" wire:click="setQuickPeriod('next_15_days')" class="rounded-lg border border-gray-300 bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Próximos 15 dias
                    </button>
                    <button type="button" wire:click="setQuickPeriod('next_30_days')" class="rounded-lg border border-primary-300 bg-primary-50 px-2.5 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100 dark:border-primary-800 dark:bg-primary-950/60 dark:text-primary-300 dark:hover:bg-primary-900/60">
                        Próximos 30 dias
                    </button>
                    <button type="button" wire:click="setQuickPeriod('next_60_days')" class="rounded-lg border border-gray-300 bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Próximos 60 dias
                    </button>
                    <button type="button" wire:click="setQuickPeriod('this_month')" class="rounded-lg border border-gray-300 bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Este Mês
                    </button>
                    <button type="button" wire:click="setQuickPeriod('next_month')" class="rounded-lg border border-gray-300 bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Próximo Mês
                    </button>
                </div>
            </div>

            <!-- Seleção Manual de Datas -->
            <div class="mt-4 flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">A partir de</label>
                    <input
                        type="date"
                        wire:model.live="periodStart"
                        class="mt-1 block rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Até a data</label>
                    <input
                        type="date"
                        wire:model.live="periodEnd"
                        class="mt-1 block rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    />
                </div>
                <button
                    type="button"
                    wire:click="loadReport"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-500 focus:outline-none"
                >
                    Atualizar Projeção
                </button>
                @if($generatedAt)
                    <span class="text-xs text-gray-500 dark:text-gray-400">Atualizado às {{ $generatedAt }}</span>
                @endif
            </div>
        </section>

        <!-- Indicadores / Cards de Resumo -->
        @if(!empty($report))
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <!-- Saldo Líquido Atual Disponível -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Saldo Atual (Caixas + Contas)</div>
                    <div class="mt-2 text-xl font-extrabold text-blue-600 dark:text-blue-400">
                        R$ {{ number_format($report['current_liquid_balance'] ?? 0, 2, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Caixas: R$ {{ number_format($report['cash_register_balance'] ?? 0, 2, ',', '.') }} | Contas: R$ {{ number_format($report['financial_accounts_balance'] ?? 0, 2, ',', '.') }}
                    </div>
                </div>

                <!-- Total de Entradas Previstas -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Receitas Previstas (Parcelas)</div>
                    <div class="mt-2 text-xl font-extrabold text-emerald-600 dark:text-emerald-400">
                        + R$ {{ number_format($report['total_projected_inflow'] ?? 0, 2, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                        {{ $report['total_installments_count'] ?? 0 }} parcela(s) no período
                    </div>
                </div>

                <!-- Total de Saídas Previstas -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Despesas Agendadas (Contas)</div>
                    <div class="mt-2 text-xl font-extrabold text-rose-600 dark:text-rose-400">
                        - R$ {{ number_format($report['total_projected_outflow'] ?? 0, 2, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                        {{ $report['total_payables_count'] ?? 0 }} conta(s) a pagar
                    </div>
                </div>

                <!-- Resultado Operacional Projetado -->
                @php
                    $net = (float) ($report['projected_net_result'] ?? 0);
                @endphp
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Resultado do Período</div>
                    <div class="mt-2 text-xl font-extrabold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $net >= 0 ? '+' : '' }} R$ {{ number_format($net, 2, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $net >= 0 ? 'Superávit operacional previsto' : 'Déficit operacional previsto' }}
                    </div>
                </div>

                <!-- Saldo Final Acumulado Estimado -->
                @php
                    $final = (float) ($report['projected_final_balance'] ?? 0);
                @endphp
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Saldo Final Estimado</div>
                    <div class="mt-2 text-xl font-extrabold {{ $final >= 0 ? 'text-purple-600 dark:text-purple-400' : 'text-rose-600 dark:text-rose-400' }}">
                        R$ {{ number_format($final, 2, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Saldo projetado em {{ \Illuminate\Support\Carbon::parse($report['end_date'] ?? now())->format('d/m/Y') }}
                    </div>
                </div>
            </section>

            <!-- Cronograma Diário do Fluxo de Caixa -->
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-5 dark:border-gray-800">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Linha do Tempo Diária (Cronograma Projetado)</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Detalhamento das entradas e saídas programadas dia a dia com evolução do saldo acumulado.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Data / Vencimento</th>
                                <th class="px-4 py-3 font-semibold">Dia da Semana</th>
                                <th class="px-4 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-400">Entradas Previstas (+)</th>
                                <th class="px-4 py-3 text-right font-semibold text-rose-700 dark:text-rose-400">Saídas Previstas (-)</th>
                                <th class="px-4 py-3 text-right font-semibold">Resultado do Dia</th>
                                <th class="px-4 py-3 text-right font-bold">Saldo Acumulado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($report['timeline'] ?? [] as $day)
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $day['formatted_date'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ $day['day_of_week'] }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                        @if($day['inflow'] > 0)
                                            + R$ {{ number_format($day['inflow'], 2, ',', '.') }}
                                            <span class="ml-1 text-[10px] text-gray-400 dark:text-gray-500">({{ $day['installments_count'] }})</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-rose-600 dark:text-rose-400">
                                        @if($day['outflow'] > 0)
                                            - R$ {{ number_format($day['outflow'], 2, ',', '.') }}
                                            <span class="ml-1 text-[10px] text-gray-400 dark:text-gray-500">({{ $day['payables_count'] }})</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold {{ $day['day_net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $day['day_net'] >= 0 ? '+' : '' }} R$ {{ number_format($day['day_net'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-extrabold {{ $day['running_balance'] >= 0 ? 'text-gray-900 dark:text-white' : 'text-rose-600 dark:text-rose-400' }}">
                                        R$ {{ number_format($day['running_balance'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                        Nenhuma movimentação prevista encontrada para o período selecionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
