@props([
    'lessons' => null,
    'tests' => null,
    'lessonCheckedAt' => null,
    'testCheckedAt' => null,
])

@php
    $toRows = static function ($data, array $containers): array {
        if (! is_array($data) || $data === []) {
            return [];
        }

        if (array_is_list($data)) {
            return $data;
        }

        foreach ($containers as $container) {
            if (isset($data[$container]) && is_array($data[$container])) {
                return array_is_list($data[$container]) ? $data[$container] : [$data[$container]];
            }
        }

        return [$data];
    };

    $lessonRows = $toRows($lessons, ['aulas', 'lessons', 'dados', 'data', 'resultado', 'resposta']);
    $testRows = $toRows($tests, ['testes', 'tests', 'dados', 'data', 'resultado', 'resposta']);

    $valueFrom = static function (?array $record, array $keys): mixed {
        if (! $record) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($record[$key]) && ! is_array($record[$key])) {
                return $record[$key];
            }
        }

        return null;
    };

    $lessonIdKeys = ['idaula', 'id_aula', 'aula_id', 'lesson_id', 'aula', 'id'];
    $lessonNameKeys = ['nome_aula', 'titulo_aula', 'nome', 'titulo', 'name'];
    $testLessonKeys = ['idaula', 'id_aula', 'aula_id', 'lesson_id', 'aula'];
    $rows = [];
    $attachedTests = [];

    foreach ($lessonRows as $lessonIndex => $lesson) {
        if (! is_array($lesson)) {
            $lesson = ['aula' => $lesson];
        }

        $lessonId = $valueFrom($lesson, $lessonIdKeys);
        $lessonName = $valueFrom($lesson, $lessonNameKeys);
        $matchingTests = [];

        foreach ($testRows as $testIndex => $test) {
            if (! is_array($test)) {
                $test = ['teste' => $test];
            }

            $testLessonId = $valueFrom($test, $testLessonKeys);
            $testLessonName = $valueFrom($test, ['nome_aula', 'lesson']);
            $hasExplicitLink = filled($testLessonId) || filled($testLessonName);
            $matchesById = filled($lessonId) && filled($testLessonId) && (string) $lessonId === (string) $testLessonId;
            $matchesByName = filled($lessonName) && filled($testLessonName) && mb_strtolower((string) $lessonName) === mb_strtolower((string) $testLessonName);
            $onlyLessonAvailable = count($lessonRows) === 1 && ! $hasExplicitLink;

            if ($matchesById || $matchesByName || $onlyLessonAvailable) {
                $matchingTests[] = [$testIndex, $test];
                $attachedTests[$testIndex] = true;
            }
        }

        if ($matchingTests === []) {
            $rows[] = [$lesson, null];

            continue;
        }

        foreach ($matchingTests as [, $test]) {
            $rows[] = [$lesson, $test];
        }
    }

    foreach ($testRows as $testIndex => $test) {
        if (! isset($attachedTests[$testIndex])) {
            $rows[] = [null, is_array($test) ? $test : ['teste' => $test]];
        }
    }

    $activityDate = static function (mixed $value): string {
        if (! filled($value)) {
            return '—';
        }

        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return $timestamp ? date('d/m/Y H:i', $timestamp) : (string) $value;
    };

    $activitiesCheckedAt = collect([$lessonCheckedAt, $testCheckedAt])
        ->filter()
        ->sortByDesc(fn ($date) => $date->timestamp)
        ->first();
    $hasTestEndDate = collect($testRows)->contains(fn ($test): bool => is_array($test) && filled($valueFrom($test, ['data_fim', 'fim', 'datahora_fim', 'ended_at'])));
@endphp

<section class="om-activities rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
    <header class="om-activities__header mb-3 flex flex-wrap items-start justify-between gap-2">
        <div>
            <h4 class="font-semibold text-gray-900 dark:text-white">Aulas e testes</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">Atividades do aluno neste curso</p>
        </div>
        @if ($activitiesCheckedAt)
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Consulta em {{ $activitiesCheckedAt->format('d/m/Y H:i') }}</span>
        @endif
    </header>

    @if ($rows !== [])
        <div class="om-activities__table-wrap overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="om-activities__table w-full min-w-[760px] border-collapse text-left text-xs">
                <thead>
                    <tr class="bg-gray-100/80 dark:bg-gray-800/90 text-gray-700 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">
                        <th class="om-activities__number px-3 py-2.5 border border-gray-200 dark:border-gray-800 text-center font-bold">#</th>
                        <th class="px-3 py-2.5 border border-gray-200 dark:border-gray-800 font-bold">Aula</th>
                        <th class="px-3 py-2.5 border border-gray-200 dark:border-gray-800 font-bold">Início</th>
                        <th class="px-3 py-2.5 border border-gray-200 dark:border-gray-800 font-bold">Fim</th>
                        <th class="px-3 py-2.5 border border-gray-200 dark:border-gray-800 font-bold">Teste / situação</th>
                        <th class="px-3 py-2.5 border border-gray-200 dark:border-gray-800 text-center font-bold">Média</th>
                        <th class="px-3 py-2.5 border border-gray-200 dark:border-gray-800 text-center font-bold">Execuções</th>
                        <th class="px-3 py-2.5 border border-gray-200 dark:border-gray-800 font-bold">Data do teste</th>
                        @if ($hasTestEndDate)
                            <th class="px-3 py-2.5 border border-gray-200 dark:border-gray-800 font-bold">Término do teste</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rows as $index => [$lesson, $test])
                        @php
                            $lessonNumber = $valueFrom($lesson, ['aula', 'numero_aula', 'lesson_number']);
                            $lessonName = $valueFrom($lesson, $lessonNameKeys)
                                ?? $valueFrom($lesson, ['descricao', 'description'])
                                ?? ($lesson ? 'Aula '.($lessonNumber ?? ($index + 1)) : null);
                            $testStatus = mb_strtolower(trim((string) $valueFrom($test, ['situacao', 'status'])));
                            $testStatusLabel = [
                                'concluida' => 'Concluída',
                                'concluído' => 'Concluído',
                                'concluido' => 'Concluído',
                                'cursando' => 'Em andamento',
                                'em andamento' => 'Em andamento',
                                'pendente' => 'Pendente',
                                'reprovado' => 'Reprovado',
                                'aprovado' => 'Aprovado',
                            ][$testStatus] ?? ($testStatus !== '' ? ucfirst($testStatus) : 'Sem realização');
                            $testName = $valueFrom($test, ['teste', 'nome_teste', 'titulo_teste', 'nome', 'titulo', 'name'])
                                ?? ($test ? 'Teste'.($lessonNumber ? ' da aula '.$lessonNumber : '') : null);
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="om-activities__number px-3 py-2 border border-gray-200/80 dark:border-gray-800 text-center font-bold text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 border border-gray-200/80 dark:border-gray-800 font-semibold text-gray-900 dark:text-white">{{ $lessonName ?? '—' }}</td>
                            <td class="px-3 py-2 border border-gray-200/80 dark:border-gray-800 text-gray-600 dark:text-gray-300 font-mono text-[11px]">{{ $activityDate($valueFrom($lesson, ['data_inico', 'data_inicio', 'inicio', 'datahora_inicio', 'data_hora_inicio', 'dt_inicio', 'started_at', 'start'])) }}</td>
                            <td class="px-3 py-2 border border-gray-200/80 dark:border-gray-800 text-gray-600 dark:text-gray-300 font-mono text-[11px]">{{ $activityDate($valueFrom($lesson, ['fim', 'data_fim', 'datahora_fim', 'data_hora_fim', 'dt_fim', 'ended_at', 'end'])) }}</td>
                            <td class="px-3 py-2 border border-gray-200/80 dark:border-gray-800">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $testName ?? '—' }}</span>
                                @if ($test)
                                    <small class="block text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $testStatusLabel }}</small>
                                @endif
                            </td>
                            <td class="px-3 py-2 border border-gray-200/80 dark:border-gray-800 text-center font-bold text-emerald-600 dark:text-emerald-400">{{ $valueFrom($test, ['media', 'nota', 'grade', 'score', 'pontuacao', 'pontos']) ?? '—' }}</td>
                            <td class="px-3 py-2 border border-gray-200/80 dark:border-gray-800 text-center font-medium text-gray-700 dark:text-gray-300">{{ $valueFrom($test, ['execucoes', 'tentativas', 'attempts']) ?? '—' }}</td>
                            <td class="px-3 py-2 border border-gray-200/80 dark:border-gray-800 text-gray-600 dark:text-gray-300 font-mono text-[11px]">{{ $activityDate($valueFrom($test, ['data', 'inicio', 'data_inicio', 'datahora_inicio', 'data_hora_inicio', 'started_at', 'start'])) }}</td>
                            @if ($hasTestEndDate)
                                <td class="px-3 py-2 border border-gray-200/80 dark:border-gray-800 text-gray-600 dark:text-gray-300 font-mono text-[11px]">{{ $activityDate($valueFrom($test, ['data_fim', 'fim', 'datahora_fim', 'ended_at'])) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="om-activities__empty text-sm text-gray-500 dark:text-gray-400">Ainda não há aulas ou testes registrados para este curso.</p>
    @endif
</section>
