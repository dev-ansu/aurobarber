<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Projeção do caixa</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Agendamentos previstos versus contas pendentes com vencimento no período.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ([
                        'next_15_days' => '15 dias',
                        'next_30_days' => '30 dias',
                        'next_60_days' => '60 dias',
                        'next_90_days' => '90 dias',
                        'this_month' => 'Este mês',
                        'next_month' => 'Próximo mês',
                    ] as $period => $label)
                        <x-filament::button size="sm" color="gray" wire:click="setQuickPeriod('{{ $period }}')">
                            {{ $label }}
                        </x-filament::button>
                    @endforeach
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-end gap-4">
                <label class="text-sm">
                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">De</span>
                    <input
                        type="date"
                        wire:model="periodStart"
                        class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />
                </label>
                <label class="text-sm">
                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Até</span>
                    <input
                        type="date"
                        wire:model="periodEnd"
                        class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />
                </label>
                <x-filament::button wire:click="loadReport">Atualizar projeção</x-filament::button>
                @if ($generatedAt)
                    <span class="text-xs text-gray-500 dark:text-gray-400">Atualizado em {{ $generatedAt }}</span>
                @endif
            </div>
        </x-filament::section>

        @if ($report)
            @php
                $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
            @endphp

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo acumulado estimado</p>
                    <p class="mt-2 text-xl font-bold text-gray-950 dark:text-white">{{ $money($report['current_liquid_balance']) }}</p>
                </x-filament::section>
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Entradas previstas</p>
                    <p class="mt-2 text-xl font-bold text-success-600 dark:text-success-400">{{ $money($report['total_projected_inflow']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $report['total_appointments_count'] }} agendamento(s)</p>
                </x-filament::section>
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Contas a pagar previstas</p>
                    <p class="mt-2 text-xl font-bold text-danger-600 dark:text-danger-400">{{ $money($report['total_projected_outflow']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $report['total_payables_count'] }} conta(s)</p>
                </x-filament::section>
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Resultado projetado</p>
                    <p class="mt-2 text-xl font-bold {{ $report['projected_net_result'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        {{ $money($report['projected_net_result']) }}
                    </p>
                </x-filament::section>
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo final estimado</p>
                    <p class="mt-2 text-xl font-bold {{ $report['projected_final_balance'] >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-danger-600 dark:text-danger-400' }}">
                        {{ $money($report['projected_final_balance']) }}
                    </p>
                </x-filament::section>
            </section>

            <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 p-5 dark:border-white/10">
                    <h3 class="font-semibold text-gray-950 dark:text-white">Movimentações previstas por dia</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Entradas estimadas por agendamentos ainda não pagos e saídas por contas pendentes.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Data</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Entradas</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Saídas</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Resultado do dia</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Saldo acumulado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse ($report['timeline'] as $day)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $day['formatted_date'] }}</span>
                                        <span class="ml-1 text-gray-500 dark:text-gray-400">{{ $day['day_of_week'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-success-600 dark:text-success-400">
                                        {{ $day['inflow'] > 0 ? $money($day['inflow']) : '—' }}
                                        @if ($day['appointments_count'])
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $day['appointments_count'] }} agendamento(s)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-danger-600 dark:text-danger-400">
                                        {{ $day['outflow'] > 0 ? $money($day['outflow']) : '—' }}
                                        @if ($day['payables_count'])
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $day['payables_count'] }} conta(s)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-950 dark:text-white">{{ $money($day['day_net']) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">{{ $money($day['running_balance']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Nenhum agendamento ou vencimento previsto neste período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Estimativa gerencial: o saldo acumulado considera atendimentos concluídos e pagos, contas registradas como pagas e movimentações de caixa. A projeção não substitui conciliação bancária e considera apenas receitas e despesas lançadas no sistema.
            </p>
        @endif
    </div>
</x-filament-panels::page>
