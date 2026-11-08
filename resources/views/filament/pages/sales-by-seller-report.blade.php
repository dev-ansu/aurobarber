<x-filament-panels::page>
    <div class="space-y-6">

        @php
            $metrics = $this->summaryMetrics();
            $sellers = $this->sellersReport();
            $canExport = (auth()->user()?->can('Export:SalesBySellerReport') ?? false)
                || (auth()->user()?->hasAnyRole(['super_admin', 'secretaria']) ?? false);
        @endphp

        <!-- BARRA SUPERIOR DE AÇÕES E EXPORTAÇÃO -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Acompanhamento de matrículas, faturamento contratado, ticket médio e taxa de conversão por consultor comercial.
                </p>
            </div>

            @if($canExport)
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="exportCsv"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Exportar CSV</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- FILTROS -->
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Data Inicial</label>
                    <input
                        type="date"
                        wire:model.live="fromDate"
                        class="block w-full rounded-lg border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Data Final</label>
                    <input
                        type="date"
                        wire:model.live="untilDate"
                        class="block w-full rounded-lg border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Vendedor / Comercial</label>
                    <select
                        wire:model.live="collaboratorId"
                        class="block w-full rounded-lg border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todos os vendedores</option>
                        @foreach($this->collaborators() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Situação do Contrato</label>
                    <select
                        wire:model.live="contractStatus"
                        class="block w-full rounded-lg border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todas as situações</option>
                        @foreach($this->contractStatuses() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- CARDS DE METRICAS CONSOLIDADAS -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Matrículas Fechadas</p>
                <p class="mt-1 text-2xl font-black text-primary-600 dark:text-primary-400">{{ $metrics['total_contracts'] }}</p>
                <p class="mt-1 text-[11px] text-gray-400">{{ $metrics['total_leads'] }} leads atendidos</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Faturamento Gerado</p>
                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($metrics['total_sales'], 2, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-gray-400">Descontos: R$ {{ number_format($metrics['total_discounts'], 2, ',', '.') }}</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Ticket Médio</p>
                <p class="mt-1 text-xl font-bold text-sky-600 dark:text-sky-400">R$ {{ number_format($metrics['average_ticket'], 2, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-gray-400">Por aluno matriculado</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Taxa de Conversão</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $metrics['overall_conversion'] }}%</p>
                <p class="mt-1 text-[11px] text-gray-400">Leads &rarr; Matrículas</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Recebido</p>
                <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">R$ {{ number_format($metrics['total_paid'], 2, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-gray-400">Parcelas já quitadas</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Saldo a Receber</p>
                <p class="mt-1 text-xl font-bold text-amber-600 dark:text-amber-400">R$ {{ number_format($metrics['total_pending'], 2, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-gray-400">Parcelas futuras/abertas</p>
            </div>
        </div>

        <!-- TABELA DE DESEMPENHO POR VENDEDOR -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-100 p-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Produtividade Individual por Consultor / Vendedor</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                        <tr>
                            <th class="py-3 px-4">Vendedor / Comercial</th>
                            <th class="py-3 px-4 text-center">Leads Atendidos</th>
                            <th class="py-3 px-4 text-center">Matrículas Fechadas</th>
                            <th class="py-3 px-4 text-center">Conversão</th>
                            <th class="py-3 px-4 text-right">Faturamento Total</th>
                            <th class="py-3 px-4 text-right">Descontos</th>
                            <th class="py-3 px-4 text-right">Ticket Médio</th>
                            <th class="py-3 px-4 text-right">Total Recebido</th>
                            <th class="py-3 px-4 text-right">A Receber</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($sellers as $seller)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $seller['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $seller['role'] }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-300 font-medium">
                                    {{ $seller['leads_count'] }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-bold text-primary-700 dark:bg-primary-950/60 dark:text-primary-300">
                                        {{ $seller['contracts_count'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold {{ $seller['conversion_rate'] >= 30 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                        {{ $seller['conversion_rate'] }}%
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white">
                                    R$ {{ number_format($seller['total_sales'], 2, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right text-gray-500 text-xs">
                                    R$ {{ number_format($seller['total_discounts'], 2, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-sky-600 dark:text-sky-400">
                                    R$ {{ number_format($seller['average_ticket'], 2, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right font-semibold text-emerald-600 dark:text-emerald-400">
                                    R$ {{ number_format($seller['total_paid'], 2, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right text-amber-600 dark:text-amber-400">
                                    R$ {{ number_format($seller['total_pending'], 2, ',', '.') }}
                                </td>
                            </tr>

                            {{-- LISTA DE CONTRATOS DO VENDEDOR (SE HOUVER) --}}
                            @if(count($seller['contracts']) > 0)
                                <tr class="bg-gray-50/60 dark:bg-gray-800/20">
                                    <td colspan="9" class="p-3 pl-8">
                                        <details class="group">
                                            <summary class="cursor-pointer text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                                Ver {{ count($seller['contracts']) }} contrato(s) fechado(s) por {{ $seller['name'] }} &darr;
                                            </summary>
                                            <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                                                @foreach($seller['contracts'] as $ct)
                                                    <div class="rounded-lg border border-gray-200 bg-white p-2.5 text-xs shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                                        <div class="flex items-center justify-between">
                                                            <strong class="text-gray-900 dark:text-white">{{ $ct['number'] }}</strong>
                                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                                {{ $ct['status'] }}
                                                            </span>
                                                        </div>
                                                        <div class="mt-1 font-medium text-gray-800 dark:text-gray-200 truncate">{{ $ct['student_name'] }}</div>
                                                        <div class="text-gray-500 text-[11px] truncate">{{ $ct['course'] }}</div>
                                                        <div class="mt-1.5 flex items-center justify-between border-t border-gray-100 pt-1 text-[11px] dark:border-gray-800">
                                                            <span class="text-gray-400">{{ $ct['date'] }}</span>
                                                            <span class="font-bold text-gray-900 dark:text-white">R$ {{ number_format($ct['total_value'], 2, ',', '.') }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endif

                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma venda ou atendimento registrado para os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>

