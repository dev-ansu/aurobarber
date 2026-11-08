<div class="space-y-6">
    @php
        $student = $contract->student;
        $codInt = $student?->ouro_moderno_cod_int;
    @endphp

    @if (! $codInt)
        <div class="rounded-2xl bg-amber-50 p-6 ring-1 ring-amber-500/20 dark:bg-amber-500/10 dark:ring-amber-500/30">
            <div class="flex items-start gap-4">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6 shrink-0 text-amber-600 dark:text-amber-400" />
                <div>
                    <h3 class="text-base font-semibold text-amber-900 dark:text-amber-200">
                        Aluno não integrado com o LMS
                    </h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        O aluno <strong>{{ $student?->name ?? 'deste contrato' }}</strong> ainda não possui o código de integração (<code>cod_int</code>) cadastrado. Utilize a ação <strong>"Enviar contrato ao LMS"</strong> no topo da página ou informe o código no cadastro do aluno para consultar os logs de atividade.
                    </p>
                </div>
            </div>
        </div>
    @else
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Atividades de {{ $student->name }} no LMS
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Código no LMS: <span class="font-mono font-medium text-gray-700 dark:text-gray-300">{{ $codInt }}</span>
                    </p>
                </div>
                @if ($lastUpdated)
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-clock class="h-4 w-4" />
                        Última consulta: {{ $lastUpdated }}
                    </span>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Data inicial</span>
                    <input type="date" wire:model="fromDate" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                </label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Data final</span>
                    <input type="date" wire:model="untilDate" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                </label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Filtrar por</span>
                    <select wire:model="filter" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Sem filtro específico</option>
                        <option value="tipo">Tipo (0=Acesso, 1=Avaliação/Conclusão, 6=Método)</option>
                        <option value="curso">ID do Curso</option>
                        <option value="aula">Número da Aula</option>
                    </select>
                </label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Valor do filtro</span>
                    <input type="number" min="0" placeholder="Ex: 126" wire:model="filterValue" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-white/5">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Acompanhamento direto dos acessos, execuções de aulas, exercícios passo a passo e testes no LMS.
                </p>
                <div class="flex items-center gap-3">
                    <x-filament::button wire:click="loadLogs" icon="heroicon-o-magnifying-glass" size="sm">
                        <span wire:loading.remove wire:target="loadLogs">Consultar logs</span>
                        <span wire:loading wire:target="loadLogs">Consultando...</span>
                    </x-filament::button>
                    @if (filled($fromDate) || filled($untilDate) || filled($filter) || filled($filterValue))
                        <x-filament::button wire:click="clearFilters" color="gray" size="sm">
                            Limpar filtros
                        </x-filament::button>
                    @endif
                    @if (((auth()->user()?->can('Export:OuroModernoStudentLogsReport') ?? false) || (auth()->user()?->hasAnyRole(['super_admin', 'secretaria']) ?? false)) && ! empty($logs))
                        <x-filament::button wire:click="exportReport" color="gray" icon="heroicon-o-arrow-down-tray" size="sm">
                            Exportar CSV
                        </x-filament::button>
                    @endif
                </div>
            </div>

            @error('fromDate') <p class="mt-2 text-xs text-danger-600">{{ $message }}</p> @enderror
            @error('untilDate') <p class="mt-2 text-xs text-danger-600">{{ $message }}</p> @enderror
            @error('filter') <p class="mt-2 text-xs text-danger-600">{{ $message }}</p> @enderror
            @error('filterValue') <p class="mt-2 text-xs text-danger-600">{{ $message }}</p> @enderror
        </section>

        @if ($hasQueried)
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-950 dark:text-white">
                        Registros de atividade
                    </h3>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">
                        {{ $totalLogs }} registro(s)
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Data e hora</th>
                                <th class="px-4 py-3">Curso / Aula</th>
                                <th class="px-4 py-3">Evento / Atividade</th>
                                <th class="px-4 py-3">Autor</th>
                                <th class="px-4 py-3">Duração</th>
                                <th class="px-4 py-3 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($logs as $index => $log)
                                @php
                                    $courseId = $log['curso'] ?? '0';
                                    $courseName = $this->resolveCourseName($courseId);
                                    $lessonNumber = (string) ($log['aula'] ?? '0');
                                    $tipo = (string) ($log['tipo'] ?? '0');
                                    $logText = $this->cleanText($log['logtxt'] ?? '');
                                    $author = $this->cleanText($log['autor'] ?? '');
                                    $hasMetadata = filled($log['metadata'] ?? null);

                                    $typeBadge = match ($tipo) {
                                        '1' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
                                        '6' => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-500/10 dark:text-purple-400',
                                        default => 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-white/5 dark:text-gray-400',
                                    };

                                    $typeLabel = match ($tipo) {
                                        '1' => 'Avaliação / Conclusão',
                                        '6' => 'Método',
                                        default => 'Acesso',
                                    };
                                @endphp
                                <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/5">
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                        {{ $log['data'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $courseName }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            @if ($courseId !== '0')
                                                ID: {{ $courseId }} ·
                                            @endif
                                            @if ($lessonNumber !== '0')
                                                Aula {{ $lessonNumber }}
                                            @else
                                                Geral
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset {{ $typeBadge }}">
                                                {{ $typeLabel }}
                                            </span>
                                            <span class="text-sm text-gray-800 dark:text-gray-200">
                                                {{ $logText }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                        {{ $author ?: '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ $this->formatDuration(data_get($log, 'atividade.tempo')) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @if ($hasMetadata)
                                            <button
                                                type="button"
                                                wire:click="showTestDetails({{ $index }})"
                                                class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/20"
                                            >
                                                <x-heroicon-m-eye class="h-3.5 w-3.5" />
                                                <span>Ver teste</span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Nenhuma atividade registrada no LMS para o período e filtros selecionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <!-- Modal de Detalhes da Avaliação (Metadata) -->
        @if ($selectedTest)
            <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-950/50 p-4 backdrop-blur-sm">
                <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-start justify-between border-b border-gray-100 pb-4 dark:border-white/10">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                                    Respostas do teste
                                </h3>
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    {{ $selectedTest['correct'] }} / {{ $selectedTest['total'] }} acertos
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $selectedTest['course'] }} · Aula {{ $selectedTest['lesson'] }} · {{ $selectedTest['date'] }}
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeTestDetails"
                            class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        >
                            <x-heroicon-m-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                        @forelse ($selectedTest['questions'] as $idx => $q)
                            @php
                                $acertou = (int) ($q['acertou'] ?? 0) === 1;
                            @endphp
                            <div @class([
                                'rounded-xl p-4 text-sm ring-1 ring-inset',
                                'bg-emerald-50/50 ring-emerald-600/20 dark:bg-emerald-500/5 dark:ring-emerald-500/20' => $acertou,
                                'bg-danger-50/50 ring-danger-600/20 dark:bg-danger-500/5 dark:ring-danger-500/20' => ! $acertou,
                            ])>
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                        Questão {{ $idx + 1 }}: {{ $this->cleanText($q['pergunta'] ?? '') }}
                                    </h4>
                                    <span @class([
                                        'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' => $acertou,
                                        'bg-danger-100 text-danger-800 dark:bg-danger-500/20 dark:text-danger-300' => ! $acertou,
                                    ])>
                                        {{ $acertou ? 'Correta' : 'Incorreta' }}
                                    </span>
                                </div>

                                <div class="mt-2.5 space-y-1 text-xs">
                                    <p class="text-gray-700 dark:text-gray-300">
                                        <span class="font-semibold text-gray-500">Resposta do aluno:</span>
                                        {{ $this->cleanText($q['resposta'] ?? '') }}
                                    </p>
                                    @if (! $acertou && filled($q['certa'] ?? null))
                                        <p class="text-emerald-700 dark:text-emerald-400">
                                            <span class="font-semibold">Resposta correta:</span>
                                            {{ $this->cleanText($q['certa'] ?? '') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-sm text-gray-500">
                                Detalhes das questões não disponíveis.
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-filament::button wire:click="closeTestDetails" color="gray" size="sm">
                            Fechar
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
