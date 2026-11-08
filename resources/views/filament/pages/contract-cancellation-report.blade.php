<x-filament-panels::page>
    <x-report-document-header title="Relatório de cancelamentos de contratos" />
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <span>De</span><input type="date" wire:model.live="fromDate" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                        @error('from') <span class="block text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <span>Até</span><input type="date" wire:model.live="untilDate" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                        @error('until') <span class="block text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <span>Formação</span>
                        <select wire:model.live="formationId" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                            <option value="">Todas as formações</option>
                            @foreach ($this->formations() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                    </label>
                </div>
                @can('Export:ContractCancellationReport')
                    <x-filament::button wire:click="exportReport" icon="heroicon-o-arrow-down-tray">Exportar CSV</x-filament::button>
                @endcan
            </div>
        </section>

        @php($report = $this->reportData())

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['Eventos de cancelamento', $report['cancellation_events'], 'danger'],
                ['Contratos afetados', $report['contracts'], 'warning'],
                ['Alunos afetados', $report['students'], 'primary'],
                ['Sem motivo informado', $report['without_reason'], 'gray'],
                ['Vínculo médio', $report['average_duration_days'] === null ? '—' : number_format($report['average_duration_days'], 1, ',', '').' dias', 'info'],
            ] as [$label, $value, $color])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p @class(['mt-2 text-2xl font-bold', 'text-danger-600 dark:text-danger-400' => $color === 'danger', 'text-warning-600 dark:text-warning-400' => $color === 'warning', 'text-primary-600 dark:text-primary-400' => $color === 'primary', 'text-info-600 dark:text-info-400' => $color === 'info', 'text-gray-700 dark:text-gray-300' => $color === 'gray'])>{{ $value }}</p>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            @foreach ([['Cancelamentos por motivo', $report['by_reason']], ['Evolução mensal', $report['by_month']]] as [$title, $rows])
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-950 dark:border-gray-800 dark:text-white">{{ $title }}</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300"><tr><th class="px-5 py-3">Grupo</th><th class="px-5 py-3">Cancelamentos</th>@if ($title === 'Cancelamentos por motivo')<th class="px-5 py-3">Percentual</th>@endif</tr></thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($rows as $row)
                                    <tr class="text-gray-800 dark:text-gray-200"><td class="px-5 py-3 font-medium">{{ $row['label'] }}</td><td class="px-5 py-3">{{ $row['count'] }}</td>@if ($title === 'Cancelamentos por motivo')<td class="px-5 py-3">{{ number_format($row['percentage'], 1, ',', '') }}%</td>@endif</tr>
                                @empty
                                    <tr><td colspan="3" class="px-5 py-8 text-center text-gray-500">Sem cancelamentos no período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="text-base font-semibold text-gray-950 dark:text-white">Contratos cancelados no período</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cada linha representa uma mudança de situação para cancelado.</p></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300"><tr><th class="px-5 py-3">Data</th><th class="px-5 py-3">Aluno</th><th class="px-5 py-3">Contrato</th><th class="px-5 py-3">Formação</th><th class="px-5 py-3">Motivo</th><th class="px-5 py-3">Vínculo</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($report['rows'] as $row)
                            <tr class="text-gray-800 dark:text-gray-200"><td class="whitespace-nowrap px-5 py-3">{{ $row['date'] }}</td><td class="px-5 py-3 font-medium">{{ $row['student'] }}</td><td class="px-5 py-3">{{ $row['contract_number'] }}</td><td class="px-5 py-3">{{ $row['formations'] }}</td><td class="px-5 py-3">{{ $row['reason'] }}</td><td class="px-5 py-3">{{ $row['duration_days'] === null ? '—' : $row['duration_days'].' dias' }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">Nenhum cancelamento encontrado com esses filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
