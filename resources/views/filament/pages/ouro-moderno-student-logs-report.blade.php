<x-filament-panels::page>
    <x-report-document-header title="Relatório de atividades dos alunos no LMS" />
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300 xl:col-span-2"><span>Aluno / contrato</span><select wire:model="personId" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Selecione</option>@foreach ($this->students() as $id => $student)<option value="{{ $id }}">{{ $student }}</option>@endforeach</select></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>De</span><input type="date" wire:model="fromDate" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Até</span><input type="date" wire:model="untilDate" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Filtrar por</span><select wire:model="filter" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Todos</option><option value="tipo">Tipo</option><option value="curso">Curso</option><option value="aula">Aula</option></select></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>ID do filtro</span><input type="number" min="1" wire:model="filterValue" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"></label>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-gray-500 dark:text-gray-400">Os logs usam o ID interno do aluno no LMS, resolvido pela correspondência com cod_int. A consulta retorna até 500 registros.</p><div class="flex gap-3"><x-filament::button wire:click="loadLogs" icon="heroicon-o-magnifying-glass">Consultar logs</x-filament::button>@if (auth()->user()?->can('Export:OuroModernoStudentLogsReport') && $lastUpdated)<x-filament::button wire:click="exportReport" color="gray" icon="heroicon-o-arrow-down-tray">Exportar CSV</x-filament::button>@endif</div></div>
            @error('personId')<p class="mt-2 text-sm text-danger-600">{{ $message }}</p>@enderror
            @error('untilDate')<p class="mt-2 text-sm text-danger-600">{{ $message }}</p>@enderror
        </section>

        @if ($lastUpdated)
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-950 dark:text-white">Atividade registrada no LMS</h2><p class="mt-1 text-sm text-gray-500">{{ $totalLogs }} registro(s) · consultado em {{ $lastUpdated }}</p></div>
                <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300"><tr><th class="px-4 py-3">Data</th><th class="px-4 py-3">Curso / aula</th><th class="px-4 py-3">Atividade</th><th class="px-4 py-3">Autor</th><th class="px-4 py-3">Duração</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($logs as $log)
                        <tr class="text-gray-800 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $log['data'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ ($log['curso'] ?? '0') === '0' ? 'Sistema / Geral' : 'Curso ' . ($log['curso'] ?? '') }}</span>
                                <br>
                                <span class="text-xs text-gray-500">{{ ($log['aula'] ?? '0') === '0' ? 'Acesso geral' : 'Aula ' . ($log['aula'] ?? '') }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $this->cleanText($log['logtxt'] ?? 'Evento sem descrição') }}</td>
                            <td class="px-4 py-3 text-xs">{{ $this->cleanText($log['autor'] ?? '—') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs font-medium">{{ $this->formatDuration(data_get($log, 'atividade.tempo')) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">A API não retornou atividade para esses filtros.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
