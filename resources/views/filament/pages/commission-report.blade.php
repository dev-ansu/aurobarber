<x-filament-panels::page>
    <div class="space-y-6">

        @php
            $rows = $this->reportData();
            $totals = $this->totals();
            $canExport = (auth()->user()?->can('Export:CommissionReport') ?? false)
                || (auth()->user()?->hasRole('super_admin') ?? false);
        @endphp

        <!-- BARRA SUPERIOR DE AÇÕES E EXPORTAÇÃO -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Cálculo automático e fechamento de comissões para consultores comerciais e educadores conforme regras cadastradas.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-3.5 py-2 text-xs font-bold text-gray-700 shadow-sm hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                >
                    <x-heroicon-m-printer class="h-4 w-4" />
                    <span>Imprimir Demonstrativo</span>
                </button>

                @if($canExport)
                    <button
                        type="button"
                        wire:click="exportCsv"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500 transition"
                    >
                        <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                        <span>Exportar CSV</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- FILTROS -->
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Data Inicial</label>
                    <input
                        type="date"
                        wire:model.live="fromDate"
                        class="block w-full rounded-xl border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Data Final</label>
                    <input
                        type="date"
                        wire:model.live="untilDate"
                        class="block w-full rounded-xl border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Colaborador</label>
                    <select
                        wire:model.live="collaboratorId"
                        class="block w-full rounded-xl border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todos os colaboradores</option>
                        @foreach($this->collaborators() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Regra de Comissão</label>
                    <select
                        wire:model.live="commissionType"
                        class="block w-full rounded-xl border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todas as regras</option>
                        @foreach($this->commissionTypes() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- CARDS DE TOTALIZADORES -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Colaboradores com Comissão</span>
                <p class="mt-2 text-2xl font-black text-gray-950 dark:text-white">
                    {{ number_format($totals['collaborators_count'], 0, ',', '.') }}
                </p>
                <span class="text-xs text-gray-400">no período selecionado</span>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total de Matrículas</span>
                <p class="mt-2 text-2xl font-black text-primary-600 dark:text-primary-400">
                    {{ number_format($totals['contracts_count'], 0, ',', '.') }}
                </p>
                <span class="text-xs text-gray-400">contratos assinados</span>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Faturamento em Vendas</span>
                <p class="mt-2 text-2xl font-black text-violet-600 dark:text-violet-400">
                    R$ {{ number_format($totals['total_sales'], 2, ',', '.') }}
                </p>
                <span class="text-xs text-gray-400">Recebido: R$ {{ number_format($totals['total_paid'], 2, ',', '.') }}</span>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/20">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Total de Comissões a Pagar</span>
                <p class="mt-2 text-2xl font-black text-emerald-700 dark:text-emerald-300">
                    R$ {{ number_format($totals['total_commission'], 2, ',', '.') }}
                </p>
                <span class="text-xs text-emerald-600/80 dark:text-emerald-400/80">fechamento do período</span>
            </div>
        </div>

        <!-- TABELA DE COMISSÕES -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Demonstrativo por Colaborador</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Clique em "Ver Contratos" para detalhar os alunos e valores
                </span>
            </div>

            @if($rows->isEmpty())
                <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Nenhum colaborador encontrado com os filtros aplicados.
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-800" x-data="{ expandedId: null }">
                    @foreach($rows as $collab)
                        <div class="p-5 transition hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <!-- Colaborador info -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-base font-bold text-gray-950 dark:text-white truncate">
                                            {{ $collab['name'] }}
                                        </h4>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 font-semibold">
                                            {{ $collab['role'] }}
                                        </span>
                                    </div>

                                    <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="font-semibold text-gray-700 dark:text-gray-300">Regra:</span>
                                            <span class="font-medium text-primary-600 dark:text-primary-400">{{ $collab['rule_label'] }}</span>
                                        </span>

                                        @if($collab['pix'])
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="font-semibold text-gray-700 dark:text-gray-300">PIX:</span>
                                                <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ $collab['pix'] }}</code>
                                                <button
                                                    type="button"
                                                    onclick="navigator.clipboard.writeText('{{ $collab['pix'] }}'); alert('Chave PIX copiada!')"
                                                    class="text-primary-600 hover:text-primary-500 font-semibold text-xs"
                                                    title="Copiar Chave PIX"
                                                >
                                                    Copiar
                                                </button>
                                            </span>
                                        @else
                                            <span class="text-amber-500 italic">PIX não cadastrado</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Métricas do Colaborador -->
                                <div class="flex flex-wrap items-center gap-6 lg:justify-end">
                                    <div class="text-right">
                                        <span class="block text-xs text-gray-400">Matrículas</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $collab['contracts_count'] }}
                                        </span>
                                    </div>

                                    <div class="text-right">
                                        <span class="block text-xs text-gray-400">Total Vendido</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                                            R$ {{ number_format($collab['total_sales'], 2, ',', '.') }}
                                        </span>
                                    </div>

                                    <div class="text-right">
                                        <span class="block text-xs text-gray-400">Total Recebido</span>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                            R$ {{ number_format($collab['total_paid'], 2, ',', '.') }}
                                        </span>
                                    </div>

                                    <div class="text-right min-w-[130px]">
                                        <span class="block text-xs font-semibold text-emerald-600 dark:text-emerald-400">Comissão a Pagar</span>
                                        <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">
                                            R$ {{ number_format($collab['total_commission'], 2, ',', '.') }}
                                        </span>
                                    </div>

                                    @if(count($collab['contracts']) > 0)
                                        <div>
                                            <button
                                                type="button"
                                                x-on:click="expandedId = (expandedId === {{ $collab['collaborator_id'] }} ? null : {{ $collab['collaborator_id'] }})"
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800 transition"
                                            >
                                                <span x-text="expandedId === {{ $collab['collaborator_id'] }} ? 'Ocultar' : 'Ver Contratos ({{ count($collab['contracts']) }})'"></span>
                                                <x-heroicon-m-chevron-down class="h-3.5 w-3.5 transition-transform" x-bind:class="{ 'rotate-180': expandedId === {{ $collab['collaborator_id'] }} }" />
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- ACCORDION DE CONTRATOS DO COLABORADOR -->
                            @if(count($collab['contracts']) > 0)
                                <div
                                    x-show="expandedId === {{ $collab['collaborator_id'] }}"
                                    x-collapse
                                    x-cloak
                                    class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800"
                                >
                                    <div class="overflow-x-auto rounded-xl bg-gray-50/70 p-3 dark:bg-gray-950/40">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                                                    <th class="pb-2 font-semibold">Contrato</th>
                                                    <th class="pb-2 font-semibold">Aluno</th>
                                                    <th class="pb-2 font-semibold">Formação / Curso</th>
                                                    <th class="pb-2 font-semibold">Data</th>
                                                    <th class="pb-2 font-semibold text-right">Valor Venda</th>
                                                    <th class="pb-2 font-semibold text-right">Valor Pago</th>
                                                    <th class="pb-2 font-semibold text-right text-emerald-600 dark:text-emerald-400">Comissão Gerada</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200/60 dark:divide-gray-800/60">
                                                @foreach($collab['contracts'] as $item)
                                                    <tr>
                                                        <td class="py-2 font-mono font-bold text-primary-600 dark:text-primary-400">
                                                            {{ $item['contract_number'] }}
                                                        </td>
                                                        <td class="py-2 font-medium text-gray-900 dark:text-white">
                                                            {{ $item['student_name'] }}
                                                        </td>
                                                        <td class="py-2 text-gray-600 dark:text-gray-300">
                                                            {{ $item['course'] }}
                                                        </td>
                                                        <td class="py-2 text-gray-500">
                                                            {{ $item['date'] }}
                                                        </td>
                                                        <td class="py-2 text-right font-medium text-gray-900 dark:text-white">
                                                            R$ {{ number_format($item['total_value'], 2, ',', '.') }}
                                                        </td>
                                                        <td class="py-2 text-right text-gray-600 dark:text-gray-300">
                                                            R$ {{ number_format($item['paid_value'], 2, ',', '.') }}
                                                        </td>
                                                        <td class="py-2 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                                            R$ {{ number_format($item['commission_amount'], 2, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>

