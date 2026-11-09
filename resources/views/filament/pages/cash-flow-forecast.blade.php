<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-gray-950 dark:text-white">Projeção do caixa</h2>
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
                    <input type="date" wire:model="periodStart" class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950">
                </label>
                <label class="text-sm">
                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Até</span>
                    <input type="date" wire:model="periodEnd" class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950">
                </label>
                <x-filament::button wire:click="loadReport">Atualizar projeção</x-filament::button>
                @if ($generatedAt)
                    <span class="text-xs text-gray-500">Atualizado em {{ $generatedAt }}</span>
                @endif
            </div>
        </section>

        @if ($report)
            @php
                $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
            @endphp

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <x-filament::section>
                    <p class="text-sm text-gray-500">Saldo acumulado estimado</p>
                    <p class="mt-2 text-xl font-bold">{{ $money($report['current_liquid_balance']) }}</p>
                </x-filament::section>
                <x-filament::section>
                    <p class="text-sm text-gray-500">Entradas previstas</p>
                    <p class="mt-2 text-xl font-bold text-success-600">{{ $money($report['total_projected_inflow']) }}</p>
                    <p class="text-xs text-gray-500">{{ $report['total_appointments_count'] }} agendamento(s)</p>
                </x-filament::section>
                <x-filament::section>
                    <p class="text-sm text-gray-500">Contas a pagar previstas</p>
                    <p class="mt-2 text-xl font-bold text-danger-600">{{ $money($report['total_projected_outflow']) }}</p>
                    <p class="text-xs text-gray-500">{{ $report['total_payables_count'] }} conta(s)</p>
                </x-filament::section>
                <x-filament::section>
                    <p class="text-sm text-gray-500">Resultado projetado</p>
                    <p class="mt-2 text-xl font-bold {{ $report['projected_net_result'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $money($report['projected_net_result']) }}
                    </p>
                </x-filament::section>
                <x-filament::section>
                    <p class="text-sm text-gray-500">Saldo final estimado</p>
                    <p class="mt-2 text-xl font-bold {{ $report['projected_final_balance'] >= 0 ? 'text-primary-600' : 'text-danger-600' }}">
                        {{ $money($report['projected_final_balance']) }}
                    </p>
                </x-filament::section>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-5 dark:border-gray-800">
                    <h3 class="font-semibold">Movimentações previstas por dia</h3>
                    <p class="mt-1 text-sm text-gray-500">Entradas estimadas por agendamentos ainda não pagos e saídas por contas pendentes.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3 text-right">Entradas</th>
                                <th class="px-4 py-3 text-right">Saídas</th>
                                <th class="px-4 py-3 text-right">Resultado do dia</th>
                                <th class="px-4 py-3 text-right">Saldo acumulado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($report['timeline'] as $day)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="font-medium">{{ $day['formatted_date'] }}</span>
                                        <span class="ml-1 text-gray-500">{{ $day['day_of_week'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-success-600">
                                        {{ $day['inflow'] > 0 ? $money($day['inflow']) : '—' }}
                                        @if ($day['appointments_count'])
                                            <span class="block text-xs text-gray-500">{{ $day['appointments_count'] }} agendamento(s)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-danger-600">
                                        {{ $day['outflow'] > 0 ? $money($day['outflow']) : '—' }}
                                        @if ($day['payables_count'])
                                            <span class="block text-xs text-gray-500">{{ $day['payables_count'] }} conta(s)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">{{ $money($day['day_net']) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ $money($day['running_balance']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Nenhum agendamento ou vencimento previsto neste período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="text-xs text-gray-500">
                Estimativa gerencial: o saldo acumulado considera atendimentos concluídos e pagos, contas registradas como pagas e movimentações de caixa. A projeção não substitui conciliação bancária e considera apenas receitas e despesas lançadas no sistema.
            </p>
        @endif
    </div>
</x-filament-panels::page>
