@props(['data'])

@php
    $labels = [
        'idcurso' => 'Código do curso no LMS',
        'curso' => 'Curso',
        'situacao' => 'Situação',
        'status' => 'Situação',
        'disponivel' => 'Disponível',
        'data_conclusao' => 'Data de conclusão',
        'id' => 'Identificador',
        'idaula' => 'Código da aula',
        'aula' => 'Aula',
        'idteste' => 'Código do teste',
        'teste' => 'Teste',
        'nota' => 'Nota',
        'tentativas' => 'Tentativas',
        'resultado' => 'Resultado',
        'data' => 'Data',
        'inicio' => 'Início',
        'fim' => 'Término',
        'duracao' => 'Duração',
        'progresso' => 'Progresso',
        'percentual' => 'Percentual',
    ];

    $isCourse = is_array($data) && (array_key_exists('idcurso', $data) || (array_key_exists('curso', $data) && array_key_exists('situacao', $data)));
@endphp

@if (is_array($data))
    @if (array_is_list($data))
        @if (count($data))
            <div class="om-status om-status__list space-y-3">
                @foreach ($data as $item)
                    <x-ouro-moderno-status :data="$item" />
                @endforeach
            </div>
        @else
            <p class="om-status__empty text-sm text-gray-500 dark:text-gray-400">Nenhum registro retornado.</p>
        @endif
    @elseif ($isCourse)
        <article class="om-status om-status__card rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <div class="om-status__header mb-3 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h4 class="font-semibold text-gray-950 dark:text-white">{{ $data['curso'] ?? 'Curso' }}</h4>
                    @if (filled($data['idcurso'] ?? null))
                        <p class="om-status__id mt-0.5 text-xs text-gray-500 dark:text-gray-400">Código LMS: {{ $data['idcurso'] }}</p>
                    @endif
                </div>
                @if (filled($data['situacao'] ?? null))
                    @php
                        $courseStatus = mb_strtolower(trim((string) $data['situacao']));
                        $translatedStatus = [
                            'cursando' => 'Em andamento',
                            'em andamento' => 'Em andamento',
                            'concluido' => 'Concluído',
                            'concluído' => 'Concluído',
                            'concluída' => 'Concluído',
                            'aprovado' => 'Aprovado',
                            'reprovado' => 'Reprovado',
                            'nao iniciado' => 'Não iniciado',
                            'não iniciado' => 'Não iniciado',
                            'pendente' => 'Pendente',
                        ][$courseStatus] ?? ucfirst($courseStatus);
                        $statusColor = in_array($courseStatus, ['concluido', 'concluída', 'aprovado'], true)
                            ? 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300'
                            : (in_array($courseStatus, ['reprovado'], true)
                                ? 'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-300'
                                : 'bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-300');
                    @endphp
                    <span @class(['om-status__badge inline-flex rounded-full px-3 py-1 text-xs font-semibold', $statusColor])>{{ $translatedStatus }}</span>
                @endif
            </div>

            <dl class="om-status__grid grid gap-3 sm:grid-cols-2">
                @foreach ($data as $key => $value)
                    @continue(in_array((string) $key, ['idcurso', 'curso', 'situacao', 'code'], true) || $value === null)
                    <div class="om-status__cell rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                        <dt class="om-status__label text-xs text-gray-500 dark:text-gray-400">{{ $labels[$key] ?? str($key)->replace('_', ' ')->headline() }}</dt>
                        <dd class="om-status__value mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                            @if ($key === 'disponivel')
                                {{ in_array((string) $value, ['1', 'true', 'sim'], true) ? 'Sim' : 'Não' }}
                            @elseif (is_array($value))
                                <x-ouro-moderno-status :data="$value" />
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </article>
    @else
        <dl class="om-status om-status__grid grid gap-3 sm:grid-cols-2">
            @foreach ($data as $key => $value)
                @continue((string) $key === 'code')
                <div class="om-status__cell rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                    <dt class="om-status__label text-xs text-gray-500 dark:text-gray-400">{{ $labels[$key] ?? str($key)->replace('_', ' ')->headline() }}</dt>
                    <dd class="om-status__value mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                        @if (is_array($value))
                            <x-ouro-moderno-status :data="$value" />
                        @elseif ($value === null || $value === '')
                            —
                        @elseif (in_array((string) $key, ['disponivel', 'ativo'], true))
                            {{ in_array((string) $value, ['1', 'true', 'sim', 'S'], true) ? 'Sim' : 'Não' }}
                        @elseif (in_array(mb_strtolower((string) $key), ['situacao', 'status', 'resultado'], true))
                            @php
                                $rawStatus = mb_strtolower(trim((string) $value));
                                $friendlyStatus = [
                                    'cursando' => 'Em andamento',
                                    'concluido' => 'Concluído',
                                    'concluído' => 'Concluído',
                                    'concluída' => 'Concluído',
                                    'aprovado' => 'Aprovado',
                                    'reprovado' => 'Reprovado',
                                    'nao iniciado' => 'Não iniciado',
                                    'não iniciado' => 'Não iniciado',
                                ][$rawStatus] ?? ucfirst($rawStatus);
                            @endphp
                            {{ $friendlyStatus }}
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    @endif
@elseif ($data !== null && $data !== '')
    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $data }}</p>
@else
    <p class="om-status__empty text-sm text-gray-500 dark:text-gray-400">Nenhuma informação retornada.</p>
@endif
