<x-filament-panels::page>
    <x-report-document-header title="Relatório de Desempenho Comercial & Funil de Matrículas" />
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <span>De</span>
                        <input type="date" wire:model.live="fromDate" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                        @error('from') <span class="block text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <span>Até</span>
                        <input type="date" wire:model.live="untilDate" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                        @error('until') <span class="block text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <span>Investimento comercial/marketing (R$)</span>
                        <input type="text" wire:model.live.debounce.400ms="marketingInvestment" placeholder="Ex: 1500,00" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                        <span class="text-xs text-gray-500">Usado no cálculo automático de CAC</span>
                    </label>
                </div>
                @can('Export:CommercialReport')
                    <x-filament::button wire:click="exportReport" icon="heroicon-o-arrow-down-tray">
                        Exportar CSV
                    </x-filament::button>
                @endcan
            </div>
        </section>

        @php($report = $this->reportData())

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Interessados</p>
                <p class="mt-2 text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($report['lead_count'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Matrículas</p>
                <p class="mt-2 text-2xl font-bold text-success-600 dark:text-success-400">{{ number_format($report['enrolled_count'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Taxa de Conversão</p>
                <p class="mt-2 text-2xl font-bold text-info-600 dark:text-info-400">{{ number_format($report['conversion_rate'], 1, ',', '') }}%</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Investimento</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">R$ {{ number_format($report['investment'], 2, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CAC por Aluno</p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                    @if ($report['enrolled_count'] > 0 && $report['investment'] > 0)
                        R$ {{ number_format($report['cac'], 2, ',', '.') }}
                    @elseif ($report['investment'] > 0)
                        Sem matrículas
                    @else
                        R$ 0,00
                    @endif
                </p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Retornos em Atraso</p>
                <p class="mt-2 text-2xl font-bold text-danger-600 dark:text-danger-400">{{ number_format($report['overdue_follow_ups'], 0, ',', '.') }}</p>
            </div>
        </section>

        {{-- Funil de Matrículas --}}
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Funil de Conversão de Matrículas</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Acompanhamento das etapas comerciais do primeiro contato à assinatura do contrato.</p>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($report['funnel'] as $index => $stage)
                    <div class="relative flex flex-col justify-between rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Etapa {{ $index + 1 }}</span>
                                <span class="text-xs font-bold text-primary-600 dark:text-primary-400">{{ number_format($stage['rate'], 1, ',', '') }}%</span>
                            </div>
                            <h3 class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $stage['stage'] }}</h3>
                        </div>
                        <div class="mt-4">
                            <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $stage['count'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($stage['conversion_from_total'], 1, ',', '') }}% do total de leads</p>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-primary-600" style="width: {{ min(100, max(5, $stage['conversion_from_total'])) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Motivos de Objeção e Perda & Por Responsável --}}
        <section class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-950 dark:border-gray-800 dark:text-white">Taxa de Conversão por Responsável / Captador</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                            <tr><th class="px-5 py-3">Responsável</th><th class="px-5 py-3">Interessados</th><th class="px-5 py-3">Matrículas</th><th class="px-5 py-3">Conversão</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($report['by_collaborator'] as $row)
                                <tr class="text-gray-800 dark:text-gray-200">
                                    <td class="px-5 py-3 font-medium">{{ $row['label'] }}</td>
                                    <td class="px-5 py-3">{{ $row['leads'] }}</td>
                                    <td class="px-5 py-3">{{ $row['enrolled'] }}</td>
                                    <td class="px-5 py-3 font-semibold {{ $row['conversion_rate'] >= 25 ? 'text-emerald-600 dark:text-emerald-400' : ($row['conversion_rate'] >= 10 ? 'text-sky-600 dark:text-sky-400' : 'text-gray-600 dark:text-gray-400') }}">
                                        {{ number_format($row['conversion_rate'], 1, ',', '') }}%
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">Sem responsáveis associados no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-950 dark:border-gray-800 dark:text-white">Motivos Frequentes de Objeção / Perda</h2>
                <div class="max-h-96 overflow-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="sticky top-0 bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                            <tr><th class="px-5 py-3">Motivo / Objeção</th><th class="px-5 py-3 text-right">Ocorrências</th><th class="px-5 py-3 text-right">Participação</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($report['loss_reasons'] as $loss)
                                <tr class="text-gray-800 dark:text-gray-200">
                                    <td class="px-5 py-3 font-medium">{{ $loss['reason'] }}</td>
                                    <td class="px-5 py-3 text-right font-semibold">{{ $loss['count'] }}</td>
                                    <td class="px-5 py-3 text-right text-rose-600 dark:text-rose-400">{{ number_format($loss['percentage'], 1, ',', '') }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-8 text-center text-gray-500">Nenhuma objeção ou perda registrada no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-950 dark:border-gray-800 dark:text-white">Interessados por origem</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                            <tr><th class="px-5 py-3">Origem</th><th class="px-5 py-3">Interessados</th><th class="px-5 py-3">Matrículas</th><th class="px-5 py-3">Conversão</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($report['by_source'] as $row)
                                <tr class="text-gray-800 dark:text-gray-200">
                                    <td class="px-5 py-3 font-medium">{{ $row['label'] }}</td><td class="px-5 py-3">{{ $row['leads'] }}</td><td class="px-5 py-3">{{ $row['enrolled'] }}</td><td class="px-5 py-3">{{ number_format($row['conversion_rate'], 1, ',', '') }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">Sem interessados no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @php($maxStateLeads = max([1, ...array_column($report['by_state'], 'leads')]))
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Distribuição regional por estado</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Barras proporcionais ao número de interessados; inclui a conversão em matrículas.</p>
            </div>
            <div class="space-y-4">
                @forelse ($report['by_state'] as $row)
                    <div>
                        <div class="mb-1 flex items-center justify-between gap-4 text-sm">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $row['label'] }}</span>
                            <span class="shrink-0 text-gray-600 dark:text-gray-400">{{ $row['leads'] }} interessados · {{ $row['enrolled'] }} matrículas · {{ number_format($row['conversion_rate'], 1, ',', '') }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ max(3, ($row['leads'] / $maxStateLeads) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-gray-500">Sem interessados no período.</p>
                @endforelse
            </div>
            <p class="mt-5 text-xs text-gray-500 dark:text-gray-400">O banco atual não guarda coordenadas nem polígonos geográficos. Este resumo por UF funciona como mapa de intensidade; um mapa geográfico real pode ser adicionado com dados cartográficos locais.</p>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            @foreach ([['Interessados por cidade', $report['by_city']], ['Interessados por bairro', $report['by_neighborhood']]] as [$title, $rows])
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-950 dark:border-gray-800 dark:text-white">{{ $title }}</h2>
                    <div class="max-h-96 overflow-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="sticky top-0 bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                                <tr><th class="px-5 py-3">Região</th><th class="px-5 py-3">Interessados</th><th class="px-5 py-3">Matrículas</th><th class="px-5 py-3">Conversão</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($rows as $row)
                                    <tr class="text-gray-800 dark:text-gray-200">
                                        <td class="px-5 py-3 font-medium">{{ $row['label'] }}</td><td class="px-5 py-3">{{ $row['leads'] }}</td><td class="px-5 py-3">{{ $row['enrolled'] }}</td><td class="px-5 py-3">{{ number_format($row['conversion_rate'], 1, ',', '') }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">Sem regiões cadastradas no período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-950 dark:border-gray-800 dark:text-white">Tempo dos interessados em aberto</h2>
            <div class="grid gap-px bg-gray-100 sm:grid-cols-3 dark:bg-gray-800">
                @foreach ($report['aging'] as $bucket)
                    <div class="bg-white px-5 py-5 dark:bg-gray-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $bucket['label'] }}</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ number_format($bucket['count'], 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
