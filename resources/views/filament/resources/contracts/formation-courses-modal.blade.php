<div class="space-y-3">
    @php
        $orderedContractCourses = $item->contractCourses->sortBy(fn ($cc) => $cc->order ?? 999)->values();
        $linkedCourses = $orderedContractCourses->map(fn ($cc) => $cc->course)->filter()->values();
        $contractCourses = $orderedContractCourses->keyBy('course_id');

        // Cursos da formação original que foram desvinculados do aluno
        $allFormationCourses = $item->formation?->courses ?? collect();
        $unlinkedCourses = $allFormationCourses
            ->reject(fn ($c) => $contractCourses->has($c->id))
            ->values();
    @endphp

    @if ($linkedCourses->isEmpty() && $unlinkedCourses->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">Esta formação não possui cursos cadastrados.</p>
    @else
        <ul class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($linkedCourses as $index => $course)
                @php
                    $studentCourse = $contractCourses->get($course->id);
                @endphp
                <li class="flex items-center justify-between gap-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-primary-500/10 text-primary-600 dark:text-primary-400 text-xs font-bold border border-primary-500/20">
                            {{ $studentCourse->order ?? ($index + 1) }}
                        </span>
                        <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $course->name }}</span>
                    </div>

                    @if ($studentCourse)
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                <p>
                                    <span class="font-semibold {{ $studentCourse->status === 'completed' ? 'text-emerald-500' : ($studentCourse->status === 'in_progress' ? 'text-amber-500' : 'text-gray-400') }}">
                                        {{ match ($studentCourse->status) {
                                            'completed' => 'Concluído no Aurosoft',
                                            'in_progress' => 'Em andamento no Aurosoft',
                                            default => 'Não iniciado no Aurosoft',
                                        } }}
                                    </span>
                                    @if ($studentCourse->is_unlocked)
                                        <span class="inline-flex items-center gap-0.5 text-amber-400 font-bold">· 🔓 Liberado</span>
                                    @endif
                                    @if ($studentCourse->grade !== null)
                                        · Nota: {{ $studentCourse->grade }}
                                    @endif
                                </p>
                                <x-ouro-moderno-progress-summary :contract-course="$studentCourse" />
                                @if ($studentCourse->ouro_moderno_status_checked_at)
                                    <p>Consulta ao LMS: {{ $studentCourse->ouro_moderno_status_checked_at->format('d/m/Y H:i') }}</p>
                                @else
                                    <p>Situação do LMS ainda não consultada</p>
                                @endif
                            </div>
                            @if ($item->contract)
                                <a
                                    href="{{ route('contracts.certificate.course', [$item->contract, $studentCourse]) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-500"
                                >
                                    <span>Certificado</span>
                                    <x-filament::icon icon="heroicon-m-academic-cap" class="h-4 w-4" />
                                </a>
                            @endif
                            @if (auth()->user()?->can('View:OuroModernoTranscript') && $item->contract)
                                <a
                                    href="{{ route('contracts.ouro-moderno-transcript.course', [$item->contract, $studentCourse]) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500"
                                >
                                    <span>Boletim do curso</span>
                                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" />
                                </a>
                            @endif
                        </div>
                    @endif
                </li>
                @foreach ([
                    'Situação do curso' => ['ouro_moderno_status', 'ouro_moderno_status_checked_at'],
                ] as $label => [$statusField, $checkedAtField])
                    @php
                        $response = $studentCourse?->{$statusField};
                    @endphp
                    @if ($response)
                        <li class="pb-3 pl-4">
                            <details class="text-sm">
                                <summary class="cursor-pointer text-primary-600 dark:text-primary-400">{{ $label }} · {{ $studentCourse->{$checkedAtField}?->format('d/m/Y H:i') ?? 'data indisponível' }}</summary>
                                <div class="mt-3"><x-ouro-moderno-status :data="$response" /></div>
                            </details>
                        </li>
                    @endif
                @endforeach
                @php
                    $lessonStatus = $studentCourse?->ouro_moderno_lesson_status;
                    $testStatus = $studentCourse?->ouro_moderno_test_status;
                    if ($studentCourse && empty($lessonStatus) && $course->isAurosoftLms()) {
                        $feedback = app(\App\Services\AurosoftLmsProgressSyncService::class)->feedback($studentCourse);
                        $lessonStatus = $feedback['lesson_status'] ?? [];
                        $testStatus = $feedback['test_status'] ?? [];
                    }
                @endphp
                @if ($studentCourse && (!empty($lessonStatus) || !empty($testStatus)))
                    <li class="pb-3 pl-4">
                        <details class="text-sm">
                            <summary class="cursor-pointer text-primary-600 dark:text-primary-400 font-medium">
                                Atividades do curso ({{ $course->isAurosoftLms() ? 'LMS Aurosoft' : 'LMS Ouro Moderno' }}) · {{ collect([$studentCourse->ouro_moderno_lesson_status_checked_at, $studentCourse->ouro_moderno_test_status_checked_at, $studentCourse->lms_synced_at])->filter()->max()?->format('d/m/Y H:i') ?? 'disponível' }}
                            </summary>
                            <div class="mt-3">
                                <x-ouro-moderno-activities
                                    :lessons="$lessonStatus"
                                    :tests="$testStatus"
                                    :lesson-checked-at="$studentCourse->ouro_moderno_lesson_status_checked_at ?? $studentCourse->lms_synced_at"
                                    :test-checked-at="$studentCourse->ouro_moderno_test_status_checked_at ?? $studentCourse->lms_synced_at"
                                />
                            </div>
                        </details>
                    </li>
                @endif
            @endforeach

            @if($unlinkedCourses->isNotEmpty())
                <li class="pt-4 pb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Cursos desvinculados desta matrícula</span>
                </li>
                @foreach($unlinkedCourses as $unlinkedCourse)
                    <li class="flex items-center justify-between gap-4 py-2 opacity-60">
                        <span class="text-sm text-gray-500 dark:text-gray-400 line-through">{{ $unlinkedCourse->name }}</span>
                        <span class="text-xs text-gray-400 italic">Desvinculado do aluno</span>
                    </li>
                @endforeach
            @endif
        </ul>
    @endif
</div>
