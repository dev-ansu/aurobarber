<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsLessonUnlock;
use App\Models\LmsModule;
use App\Models\LmsStudentLessonProgress;
use App\Models\LmsStudentStepAttempt;
use App\Models\Person;
use App\Models\StudentAccessLog;
use App\Models\StudentMakeupClass;
use App\Models\StudentSchedule;
use App\Models\StudentTypingRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LmsProgressionService
{
    /**
     * Limite padrão de lições concluídas por dia de aula para evitar o avanço desenfreado
     */
    public const DEFAULT_MAX_LESSONS_PER_SESSION = 1;

    /**
     * Retorna a lista ordenada dos cursos da formação do contrato
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Course>
     */
    /**
     * Retorna a lista ordenada dos cursos da formação do contrato
     *
    /**
     * Retorna a lista ordenada dos cursos vinculados a uma formação específica do contrato
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Course>
     */
    public function getOrderedCoursesForContractItem(Contract $contract, ContractItem $item): \Illuminate\Database\Eloquent\Collection
    {
        $courses = new \Illuminate\Database\Eloquent\Collection;

        $item->loadMissing([
            'formation',
            'contractCourses' => fn ($q) => $q->orderByRaw('COALESCE("order", 999) ASC')->orderBy('id', 'asc'),
            'contractCourses.course.lmsModules.lessons.steps',
        ]);

        if ($item->contractCourses->isNotEmpty()) {
            $orderedContractCourses = $item->contractCourses
                ->sortBy(fn (ContractCourse $cc): int => (int) ($cc->order ?? 999))
                ->values();

            foreach ($orderedContractCourses as $cc) {
                $c = $cc->course;
                if (! $c) {
                    continue;
                }

                $c->contract_item_id = $item->id;
                $c->contract_formation_id = $item->formation_id;
                $c->contract_formation_name = $item->formation?->name;
                $c->is_contract_unlocked = (bool) ($cc->is_unlocked ?? false);
                $c->contract_course_order = $cc->order;
                $c->contract_course_status = $cc->status;
                $courses->push($c);
            }
        } elseif ($item->formation) {
            $formationCourses = $item->formation->courses()
                ->with(['lmsModules.lessons.steps'])
                ->get();

            foreach ($formationCourses as $fCourse) {
                $fCourse->contract_item_id = $item->id;
                $fCourse->contract_formation_id = $item->formation_id;
                $fCourse->contract_formation_name = $item->formation->name;
                $fCourse->is_contract_unlocked = false;
                $fCourse->contract_course_order = $fCourse->pivot?->order ?? 999;
                $fCourse->contract_course_status = 'not_started';
                $courses->push($fCourse);
            }
        }

        return new \Illuminate\Database\Eloquent\Collection(
            $courses->unique('id')->values()->all()
        );
    }

    /**
     * Retorna a lista ordenada de todos os cursos das formações do contrato
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Course>
     */
    public function getOrderedCoursesForContract(Contract $contract): \Illuminate\Database\Eloquent\Collection
    {
        $courses = new \Illuminate\Database\Eloquent\Collection;

        $items = $contract->items()
            ->orderByRaw('COALESCE("order", 999) ASC')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($items as $item) {
            $itemCourses = $this->getOrderedCoursesForContractItem($contract, $item);
            foreach ($itemCourses as $course) {
                $courses->push($course);
            }
        }

        return new \Illuminate\Database\Eloquent\Collection(
            $courses->unique('id')->values()->all()
        );
    }

    /**
     * Verifica se dois itens de contrato compartilham o mesmo horário de aula
     */
    public function contractItemsShareSchedule(ContractItem $itemA, ContractItem $itemB): bool
    {
        $schedsA = $itemA->schedules ?? $itemA->schedules()->get();
        $schedsB = $itemB->schedules ?? $itemB->schedules()->get();

        if ($schedsA->isEmpty() || $schedsB->isEmpty()) {
            return false;
        }

        foreach ($schedsA as $sA) {
            foreach ($schedsB as $sB) {
                if ((int) $sA->weekday === (int) $sB->weekday) {
                    $startA = Carbon::parse($sA->start_hour)->format('H:i:s');
                    $endA = Carbon::parse($sA->end_hour)->format('H:i:s');
                    $startB = Carbon::parse($sB->start_hour)->format('H:i:s');
                    $endB = Carbon::parse($sB->end_hour)->format('H:i:s');

                    if ($startA < $endB && $endA > $startB) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Verifica se todos os cursos de um item de contrato (formação) foram concluídos
     */
    public function isContractItemCompleted(Contract $contract, ContractItem $item): bool
    {
        if ($item->formation_completed_at !== null) {
            return true;
        }

        $courses = $this->getOrderedCoursesForContractItem($contract, $item);
        if ($courses->isEmpty()) {
            return false;
        }

        foreach ($courses as $course) {
            if (! $this->isCourseCompleted($contract, $course)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sincroniza a progressão sequencial dos cursos do contrato por formação:
     * - O bloqueio sequencial e liberação de cursos ocorre DENTRO de cada formação.
     * - Formações com horários distintos progridem de forma independente em seus horários.
     * - Se duas formações compartilham o MESMO horário, a segunda só inicia após a conclusão da anterior.
     */
    public function syncContractProgression(Contract $contract, ?ContractCourse $triggerCourse = null): void
    {
        $items = $contract->items()
            ->orderByRaw('COALESCE("order", 999) ASC')
            ->orderBy('id', 'asc')
            ->with([
                'formation',
                'schedules',
                'contractCourses' => fn ($q) => $q->orderByRaw('COALESCE("order", 999) ASC')->orderBy('id', 'asc'),
                'contractCourses.course',
            ])
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        foreach ($items as $itemIndex => $item) {
            $contractCourses = $item->contractCourses;
            if ($contractCourses->isEmpty()) {
                continue;
            }

            // Verifica se este item compartilha o mesmo horário de alguma formação anterior que não foi concluída
            $blockedByEarlierFormation = false;
            for ($pi = 0; $pi < $itemIndex; $pi++) {
                $prevItem = $items->get($pi);
                if ($prevItem && $this->contractItemsShareSchedule($prevItem, $item)) {
                    if (! $this->isContractItemCompleted($contract, $prevItem)) {
                        $blockedByEarlierFormation = true;
                        break;
                    }
                }
            }

            if ($blockedByEarlierFormation) {
                // Formação anterior no mesmo horário ainda não foi concluída:
                // os cursos desta formação devem ficar 'not_started' (a menos que manualmente liberados)
                foreach ($contractCourses as $cc) {
                    if (! $cc->is_unlocked && $cc->status === 'in_progress') {
                        $cc->status = 'not_started';
                        $cc->saveQuietly();
                    }
                }

                continue;
            }

            // A formação progride normalmente dentro de sua própria sequência de cursos
            $canBeInProgress = true;
            foreach ($contractCourses as $cc) {
                if ($cc->status === 'completed' || $cc->is_unlocked) {
                    // Curso concluído ou manualmente liberado: permite que o próximo seja cursado
                    continue;
                }

                if ($canBeInProgress) {
                    if ($cc->status === 'not_started') {
                        $cc->status = 'in_progress';
                        $cc->saveQuietly();

                        // Se for curso da Ouro Moderno e o aluno possuir cod_int, atualiza situação no LMS remoto
                        $codInt = $contract->student?->ouro_moderno_cod_int;
                        $omCourseId = $cc->course?->ouro_moderno_id;
                        if ($codInt && $omCourseId) {
                            try {
                                app(OuroModernoService::class)->forSchool($contract->school_id)->updateStudentCourse(
                                    $codInt,
                                    (int) $omCourseId,
                                    ['situacao' => 'cursando', 'disponivel' => 1]
                                );
                            } catch (\Throwable $e) {
                                Log::warning("Falha ao definir curso {$omCourseId} como cursando na Ouro Moderno: ".$e->getMessage());
                            }
                        }
                    }
                    $canBeInProgress = false;
                } else {
                    if ($cc->status === 'in_progress') {
                        $cc->status = 'not_started';
                        $cc->saveQuietly();
                    }
                }
            }
        }
    }

    /**
     * Verifica se o aluno pode acessar uma formação em função dos dias da semana agendados
     */
    public function checkFormationScheduleForToday(Contract $contract, Course $course): array
    {
        // Se o contrato for FLEX, o aluno possui acesso livre a qualquer formação nos horários
        if ($contract->isFlex()) {
            return ['is_allowed' => true];
        }

        $item = $contract->items()
            ->with(['formation', 'schedules' => fn ($q) => $q->withoutGlobalScopes()])
            ->where(function ($query) use ($course) {
                $query->whereHas('contractCourses', fn ($q) => $q->where('course_id', $course->id))
                    ->orWhereHas('formation.courses', fn ($q) => $q->where('courses.id', $course->id));
            })
            ->first();

        if (! $item) {
            return ['is_allowed' => true];
        }

        $allContractSchedules = StudentSchedule::withoutGlobalScopes()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->get();

        if ($allContractSchedules->isEmpty()) {
            return ['is_allowed' => true];
        }

        $itemSchedules = $item->schedules ?? collect();
        if ($itemSchedules->isEmpty()) {
            return ['is_allowed' => true];
        }

        $currentWeekday = now()->dayOfWeek;

        // Se hoje é dia da semana agendado para esta formação específica
        $isScheduledForToday = $itemSchedules->contains(fn ($s) => (int) $s->weekday === $currentWeekday);
        if ($isScheduledForToday) {
            return ['is_allowed' => true];
        }

        // Se o aluno possui outra formação agendada especificamente para hoje
        $hasOtherFormationToday = $allContractSchedules->contains(fn ($s) => (int) $s->weekday === $currentWeekday && (int) $s->contract_item_id !== (int) $item->id);

        if ($hasOtherFormationToday) {
            $scheduledDays = $itemSchedules
                ->map(fn ($s) => $s->weekdayLabel())
                ->unique()
                ->join(' e ');

            $formationName = $item->formation?->name ?? 'desta formação';

            return [
                'is_allowed' => false,
                'reason' => 'formation_not_scheduled_today',
                'scheduled_days' => $scheduledDays,
                'message' => "Os cursos da '{$formationName}' estão programados para {$scheduledDays}. Hoje seu horário letivo é reservado para outra formação.",
            ];
        }

        return ['is_allowed' => true];
    }

    /**
     * Valida se o aluno está dentro do seu horário letivo agendado no dia e horário atuais:
     * - Se não tem horário agendado
     * - Se hoje não é dia de aula
     * - Se chegou muito cedo (antes do horário de início)
     * - Se entrou no portal depois do horário agendado (após o término da aula)
     *
     * @return array{is_allowed: bool, reason: string, message: string, schedules_text: string, start_hour?: string, end_hour?: string}
     */
    public function checkStudentScheduleWindow(Contract $contract, ?Course $course = null): array
    {
        $allSchedules = StudentSchedule::withoutGlobalScopes()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->orderBy('weekday')
            ->orderBy('start_hour')
            ->get();

        if ($allSchedules->isEmpty()) {
            return [
                'is_allowed' => true,
                'reason' => 'in_session',
                'message' => 'Horário letivo livre ou sem agendamentos cadastrados.',
                'schedules_text' => 'Horário livre',
            ];
        }

        $formattedSchedules = $allSchedules
            ->map(fn (StudentSchedule $s) => $s->weekdayLabel().' das '.$s->formattedStartHour().' às '.$s->formattedEndHour())
            ->unique()
            ->join('; ');

        $user = auth()->user();
        $isStaffOrEducator = $user && ($user->isStaff() || $user->isEducator() || $user->hasRole('super_admin'));

        if (! $isStaffOrEducator && $contract->school && $contract->school->enforce_school_ip_for_lms && ! $contract->school->isClientIpAllowed()) {
            return [
                'is_allowed' => false,
                'reason' => 'outside_school_network',
                'message' => 'O acesso ao Laboratório Interativo está disponível exclusivamente nos computadores conectados à rede de internet da escola.',
                'schedules_text' => $formattedSchedules,
            ];
        }

        // Se o curso pertence a uma formação específica e o aluno não é Flex, filtra pelos horários daquela formação
        $relevantSchedules = $allSchedules;
        if ($course && ! $contract->isFlex()) {
            $item = $contract->items()
                ->where(function ($query) use ($course) {
                    $query->whereHas('contractCourses', fn ($q) => $q->where('course_id', $course->id))
                        ->orWhereHas('formation.courses', fn ($q) => $q->where('courses.id', $course->id));
                })
                ->first();

            if ($item) {
                $itemSchedules = $item->schedules()->withoutGlobalScopes()->get();
                if ($itemSchedules->isEmpty()) {
                    $formationName = $item->formation?->name ?? 'desta formação';

                    return [
                        'is_allowed' => false,
                        'reason' => 'no_schedule_registered_for_formation',
                        'message' => "A formação '{$formationName}' ainda não possui horários agendados pela coordenação da escola.",
                        'schedules_text' => 'Sem horários cadastrados para esta formação',
                    ];
                }

                $relevantSchedules = $itemSchedules;
            }
        }

        $now = now();
        $currentWeekday = $now->dayOfWeek;
        $currentTime = $now->format('H:i:s');

        // Verifica se há aula de reposição agendada para hoje para este contrato
        $todayMakeups = StudentMakeupClass::query()
            ->where('contract_id', $contract->id)
            ->whereDate('makeup_date', $now->toDateString())
            ->whereIn('status', ['scheduled', 'completed'])
            ->orderBy('start_hour')
            ->get();

        $activeMakeup = $todayMakeups->first(function (StudentMakeupClass $m) use ($currentTime): bool {
            $start = Carbon::parse($m->start_hour)->format('H:i:s');
            $end = Carbon::parse($m->end_hour)->format('H:i:s');

            return $currentTime >= $start && $currentTime <= $end;
        });

        if ($activeMakeup) {
            $makeupRange = $activeMakeup->formattedStartHour().' às '.$activeMakeup->formattedEndHour();

            return [
                'is_allowed' => true,
                'reason' => 'in_makeup_session',
                'message' => "Aula de Reposição agendada em andamento ({$makeupRange}).",
                'schedules_text' => $formattedSchedules." • Reposição hoje: {$makeupRange}",
            ];
        }

        $todaySchedules = $relevantSchedules->filter(fn (StudentSchedule $s) => (int) $s->weekday === $currentWeekday);

        if ($todaySchedules->isEmpty()) {
            // Se não tem aula fixa hoje, mas tem uma reposição agendada para mais tarde hoje
            $upcomingMakeup = $todayMakeups->first(fn (StudentMakeupClass $m) => Carbon::parse($m->start_hour)->format('H:i:s') > $currentTime);
            if ($upcomingMakeup) {
                $mStart = $upcomingMakeup->formattedStartHour();
                $mEnd = $upcomingMakeup->formattedEndHour();

                return [
                    'is_allowed' => false,
                    'reason' => 'early_arrival',
                    'message' => "Sua Aula de Reposição de hoje está agendada das {$mStart} às {$mEnd}. Aguarde o horário de início para liberar o conteúdo.",
                    'start_hour' => $mStart,
                    'schedules_text' => $formattedSchedules." • Reposição hoje: {$mStart} às {$mEnd}",
                ];
            }

            return [
                'is_allowed' => false,
                'reason' => 'no_schedule_today',
                'message' => "Você não possui aula agendada para hoje. Seus horários fixos de aula são: {$formattedSchedules}.",
                'schedules_text' => $formattedSchedules,
            ];
        }

        // Verifica se o momento atual coincide com algum slot de aula de hoje
        $isInSlot = $todaySchedules->contains(function (StudentSchedule $s) use ($currentTime): bool {
            $start = Carbon::parse($s->start_hour)->format('H:i:s');
            $end = Carbon::parse($s->end_hour)->format('H:i:s');

            return $currentTime >= $start && $currentTime <= $end;
        });

        if ($isInSlot) {
            return [
                'is_allowed' => true,
                'reason' => 'in_session',
                'message' => 'Horário letivo em andamento.',
                'schedules_text' => $formattedSchedules,
            ];
        }

        // Se não está no horário: verifica se chegou muito cedo ou se é depois do término
        $earliestSlot = $todaySchedules->sortBy('start_hour')->first();
        $earliestStart = Carbon::parse($earliestSlot->start_hour)->format('H:i:s');
        $earliestStartFormatted = $earliestSlot->formattedStartHour();

        $latestSlot = $todaySchedules->sortByDesc('end_hour')->first();
        $latestEnd = Carbon::parse($latestSlot->end_hour)->format('H:i:s');
        $latestEndFormatted = $latestSlot->formattedEndHour();

        if ($currentTime < $earliestStart) {
            return [
                'is_allowed' => false,
                'reason' => 'early_arrival',
                'message' => "Você chegou antes do seu horário de aula! Sua aula de hoje está agendada para iniciar às {$earliestStartFormatted}. Por favor, aguarde o início para acessar as aulas.",
                'start_hour' => $earliestStartFormatted,
                'schedules_text' => $formattedSchedules,
            ];
        }

        if ($currentTime > $latestEnd) {
            return [
                'is_allowed' => false,
                'reason' => 'after_hours',
                'message' => "Seu horário de aula de hoje encerrou às {$latestEndFormatted}. O acesso aos conteúdos foi finalizado para este dia.",
                'end_hour' => $latestEndFormatted,
                'end_hour_raw' => $latestEnd,
                'schedules_text' => $formattedSchedules,
            ];
        }

        // Se está entre múltiplos slots no mesmo dia (ex: 08:00-10:00 e 14:00-16:00, e agora são 12:00)
        $nextSlot = $todaySchedules
            ->filter(fn ($s) => Carbon::parse($s->start_hour)->format('H:i:s') > $currentTime)
            ->sortBy('start_hour')
            ->first();

        if ($nextSlot) {
            $nextStart = $nextSlot->formattedStartHour();

            return [
                'is_allowed' => false,
                'reason' => 'early_arrival',
                'message' => "Intervalo entre seus horários de aula. Sua próxima aula de hoje inicia às {$nextStart}.",
                'start_hour' => $nextStart,
                'schedules_text' => $formattedSchedules,
            ];
        }

        return [
            'is_allowed' => false,
            'reason' => 'after_hours',
            'message' => "Seu horário de aula de hoje encerrou às {$latestEndFormatted}.",
            'end_hour' => $latestEndFormatted,
            'end_hour_raw' => $latestEnd,
            'schedules_text' => $formattedSchedules,
        ];
    }

    /**
     * Verifica se um curso específico da formação foi 100% concluído pelo aluno
     */
    public function isCourseCompleted(Contract $contract, Course $course): bool
    {
        $contractCourse = ContractCourse::query()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->where('course_id', $course->id)
            ->first();

        // 1. Se no registro de ContractCourse já estiver marcado como completed
        if ($contractCourse && $contractCourse->status === 'completed') {
            return true;
        }

        // 2. Se for curso Aurosoft LMS com módulos e aulas cadastradas:
        $lessonIds = LmsLesson::query()
            ->whereIn('module_id', $course->lmsModules()->select('id'))
            ->where('is_active', true)
            ->pluck('id');

        if ($lessonIds->isNotEmpty()) {
            $completedLessons = LmsStudentLessonProgress::query()
                ->where('contract_id', $contract->id)
                ->whereIn('lesson_id', $lessonIds)
                ->where('status', 'completed')
                ->count();

            if ($completedLessons >= $lessonIds->count()) {
                if ($contractCourse && $contractCourse->status !== 'completed') {
                    $contractCourse->status = 'completed';
                    $contractCourse->saveQuietly();
                }

                return true;
            }

            return false;
        }

        // 3. Se for curso da Ouro Moderno sem aulas locais cadastradas:
        if ($contractCourse && ! empty($contractCourse->ouro_moderno_lesson_status)) {
            $feedback = app(OuroModernoProgressSyncService::class)->feedback($contractCourse);
            if (! empty($feedback['total_lessons']) && $feedback['total_lessons'] > 0 && $feedback['completed_lessons'] >= $feedback['total_lessons']) {
                if ($contractCourse->status !== 'completed') {
                    $contractCourse->status = 'completed';
                    $contractCourse->saveQuietly();
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o aluno pode acessar um curso da formação (bloqueio sequencial de cursos)
     */
    public function canAccessCourse(Contract $contract, Course $course, bool $ignoreScheduleWindow = false): array
    {
        // 0. Verificação Financeira: Parcela em atraso (bloqueio amigável da secretaria)
        if ($contract->hasOverdueInstallments()) {
            return [
                'can_access' => false,
                'reason' => 'administrative_check',
                'message' => 'Identificamos uma atualização cadastral necessária na sua matrícula. Por gentileza, procure a secretaria ou recepção da sua escola para continuar seus estudos!',
            ];
        }

        // 0.5. Verificação de Horário Agendado (se não possui horário hoje, chegou cedo ou após o término)
        if (! $ignoreScheduleWindow) {
            $scheduleWindowCheck = $this->checkStudentScheduleWindow($contract, $course);
            if (! $scheduleWindowCheck['is_allowed']) {
                return [
                    'can_access' => false,
                    'reason' => $scheduleWindowCheck['reason'],
                    'message' => $scheduleWindowCheck['message'],
                ];
            }
        }

        // 1. Se o curso estiver explicitamente desbloqueado individualmente no contrato
        $contractCourse = ContractCourse::query()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->where('course_id', $course->id)
            ->first();

        if ($contractCourse && $contractCourse->is_unlocked) {
            return [
                'can_access' => true,
                'reason' => 'manually_unlocked',
                'message' => 'Acesso ao curso liberado pelo contrato.',
            ];
        }

        // Se o próprio curso já estiver como 'completed', o aluno tem acesso liberado para revisão
        if ($contractCourse && $contractCourse->status === 'completed') {
            return [
                'can_access' => true,
                'reason' => 'course_completed',
                'message' => 'Curso concluído com sucesso.',
            ];
        }

        // 2. Verificação de Restrição de Formação por Dia da Semana (se não for Aluno Flex)
        $scheduleCheck = $this->checkFormationScheduleForToday($contract, $course);
        if (! $scheduleCheck['is_allowed']) {
            return [
                'can_access' => false,
                'reason' => $scheduleCheck['reason'] ?? 'formation_not_scheduled_today',
                'message' => $scheduleCheck['message'] ?? 'Curso indisponível no dia de hoje.',
            ];
        }

        // 3. Se o contrato for FLEX, todos os cursos matriculados podem ser acessados livremente
        if ($contract->isFlex()) {
            return [
                'can_access' => true,
                'reason' => 'flex_student_unlocked',
                'message' => 'Aluno com Perfil Flex: acesso livre nos horários agendados.',
            ];
        }

        // Identifica o item de contrato (formação) a que o curso pertence
        $item = $contract->items()
            ->with(['formation', 'schedules'])
            ->where(function ($query) use ($course) {
                $query->whereHas('contractCourses', fn ($q) => $q->where('course_id', $course->id))
                    ->orWhereHas('formation.courses', fn ($q) => $q->where('courses.id', $course->id));
            })
            ->first();

        // 4. Se esta formação compartilha o mesmo horário de uma formação anterior incompleta no contrato
        if ($item) {
            $allItems = $contract->items()
                ->orderByRaw('COALESCE("order", 999) ASC')
                ->orderBy('id', 'asc')
                ->with(['formation', 'schedules'])
                ->get();

            $itemIndex = $allItems->search(fn ($i) => $i->id === $item->id);
            if ($itemIndex !== false && $itemIndex > 0) {
                for ($pi = 0; $pi < $itemIndex; $pi++) {
                    $prevItem = $allItems->get($pi);
                    if ($prevItem && $this->contractItemsShareSchedule($prevItem, $item)) {
                        if (! $this->isContractItemCompleted($contract, $prevItem)) {
                            return [
                                'can_access' => false,
                                'reason' => 'previous_formation_incomplete',
                                'message' => "Esta formação está agendada para o mesmo horário de '{$prevItem->formation?->name}'. Conclua a formação anterior para liberar o acesso a estes cursos.",
                                'previous_formation' => $prevItem->formation,
                            ];
                        }
                    }
                }
            }
        }

        // 5. Bloqueio sequencial de cursos estritamente DENTRO DA MESMA FORMAÇÃO
        $formationCourses = $item
            ? $this->getOrderedCoursesForContractItem($contract, $item)
            : $this->getOrderedCoursesForContract($contract);

        $courseIndex = $formationCourses->search(fn (Course $c) => $c->id === $course->id);

        // Se for o primeiro curso desta formação ou não estiver na lista de ordenação, está liberado
        if ($courseIndex === false || $courseIndex === 0) {
            return [
                'can_access' => true,
                'reason' => 'first_course',
            ];
        }

        // Todos os cursos anteriores DA MESMA FORMAÇÃO devem estar concluídos ou desbloqueados
        for ($i = 0; $i < $courseIndex; $i++) {
            $prevCourse = $formationCourses->get($i);
            if (! $prevCourse) {
                continue;
            }

            $prevContractCourse = ContractCourse::query()
                ->when($item, fn ($q) => $q->where('contract_item_id', $item->id))
                ->when(! $item, fn ($q) => $q->whereIn('contract_item_id', $contract->items()->select('id')))
                ->where('course_id', $prevCourse->id)
                ->first();

            // Se o curso anterior foi manualmente liberado no contrato, não trava os posteriores
            if ($prevContractCourse && $prevContractCourse->is_unlocked) {
                continue;
            }

            if (! $this->isCourseCompleted($contract, $prevCourse)) {
                // Se o curso anterior for Ouro Moderno e o atual for Aurosoft LMS:
                if ($prevCourse->isFromOuroModerno() && $course->isAurosoftLms()) {
                    return [
                        'can_access' => false,
                        'reason' => 'previous_om_course_incomplete',
                        'message' => "O curso anterior ('{$prevCourse->name}') desta formação é da Ouro Moderno e ainda não consta como concluído. Para liberar este curso do LMS Aurosoft, solicite à coordenação a liberação pelo contrato ou conclua o curso anterior.",
                        'previous_course' => $prevCourse,
                    ];
                }

                return [
                    'can_access' => false,
                    'reason' => 'previous_course_incomplete',
                    'message' => "Você precisa concluir todas as aulas do curso anterior ('{$prevCourse->name}') para liberar este curso.",
                    'previous_course' => $prevCourse,
                ];
            }
        }

        return [
            'can_access' => true,
            'reason' => 'previous_courses_completed',
        ];
    }

    /**
     * Verifica se uma seção (módulo) específica do curso foi 100% concluída pelo aluno
     */
    public function isSectionCompleted(Contract $contract, LmsModule $module): bool
    {
        $lessonIds = LmsLesson::query()
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return true;
        }

        $completedLessons = LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', 'completed')
            ->count();

        return $completedLessons >= $lessonIds->count();
    }

    /**
     * Verifica se o aluno pode acessar uma seção do curso (bloqueio sequencial de seções)
     */
    public function canAccessSection(Contract $contract, LmsModule $module): array
    {
        $course = $module->course;
        if ($course) {
            $courseAccess = $this->canAccessCourse($contract, $course);
            if (! $courseAccess['can_access']) {
                return $courseAccess;
            }

            // Verifica se seções anteriores ativas do mesmo curso estão concluídas
            $previousModules = LmsModule::query()
                ->where('course_id', $course->id)
                ->where('is_active', true)
                ->where('sort_order', '<', $module->sort_order)
                ->orderBy('sort_order')
                ->get();

            foreach ($previousModules as $prevModule) {
                if (! $this->isSectionCompleted($contract, $prevModule)) {
                    return [
                        'can_access' => false,
                        'reason' => 'previous_section_incomplete',
                        'message' => "Você precisa concluir todas as aulas da seção anterior ('{$prevModule->title}') para liberar esta etapa.",
                        'previous_section' => $prevModule,
                    ];
                }
            }
        }

        return [
            'can_access' => true,
            'reason' => 'previous_sections_completed',
        ];
    }

    /**
     * Libera todas as aulas de uma seção específica para o aluno
     */
    public function unlockSectionForContract(
        Contract $contract,
        LmsModule $module,
        ?User $byUser = null,
        ?string $reason = null
    ): void {
        $lessons = LmsLesson::query()
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->get();

        foreach ($lessons as $lesson) {
            $this->unlockLessonForContract($contract, $lesson, $byUser, $reason ?? "Seção '{$module->title}' liberada pelo educador.");
        }
    }

    /**
     * Verifica se todos os cursos da formação do contrato foram integralmente concluídos
     */
    public function isFormationCompleted(Contract $contract): bool
    {
        $courses = $this->getOrderedCoursesForContract($contract);
        if ($courses->isEmpty()) {
            return false;
        }

        foreach ($courses as $course) {
            if (! $this->isCourseCompleted($contract, $course)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retorna a lista linear e ordenada de todas as aulas ativas de um curso através dos módulos
     *
     * @return Collection<int, LmsLesson>
     */
    public function getOrderedLessonsForCourse(Course $course): Collection
    {
        return LmsLesson::query()
            ->join('lms_modules', 'lms_lessons.module_id', '=', 'lms_modules.id')
            ->where('lms_modules.course_id', $course->id)
            ->where('lms_lessons.is_active', true)
            ->where('lms_modules.is_active', true)
            ->orderBy('lms_modules.sort_order')
            ->orderBy('lms_modules.id')
            ->orderBy('lms_lessons.sort_order')
            ->orderBy('lms_lessons.id')
            ->select('lms_lessons.*')
            ->get();
    }

    /**
     * Verifica se o aluno tem permissão para acessar/iniciar uma lição
     */
    public function canAccessLesson(Contract $contract, LmsLesson $lesson, ?Carbon $date = null): array
    {
        $date = $date ?? today();

        // 0. Verificação Financeira: Parcela em atraso (bloqueio amigável da secretaria)
        if ($contract->hasOverdueInstallments()) {
            return [
                'can_access' => false,
                'reason' => 'administrative_check',
                'message' => 'Identificamos uma atualização cadastral necessária na sua matrícula. Por gentileza, procure a secretaria ou recepção da sua escola para continuar suas aulas!',
                'progress' => null,
            ];
        }

        // Verifica se há progresso nesta lição específica
        $progress = LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        // 0.5. Verificação de Horário Agendado (se não possui horário hoje, chegou cedo ou após o término)
        $scheduleWindowCheck = $this->checkStudentScheduleWindow($contract, $lesson->module?->course);

        // Se o horário agendado de hoje encerrou, mas o aluno JÁ estava assistindo a esta aula específica hoje:
        // Não expulsa o aluno abruptamente enquanto faz os exercícios finais. Concede até 60 minutos de tolerância
        // para finalizar a lição atual. Não permite avançar para outras aulas nem revisar aulas já concluídas fora do horário.
        $isFinishingCurrentLesson = false;
        if (! $scheduleWindowCheck['is_allowed'] && $scheduleWindowCheck['reason'] === 'after_hours') {
            if ($progress && $progress->status === 'in_progress' && $progress->updated_at?->isToday()) {
                $latestEndHour = $scheduleWindowCheck['end_hour_raw'] ?? null;
                if ($latestEndHour) {
                    $endTimeToday = Carbon::today()->setTimeFromTimeString($latestEndHour);
                    if (now()->diffInMinutes($endTimeToday, false) >= -60) {
                        $isFinishingCurrentLesson = true;
                    }
                }
            }
        }

        if (! $scheduleWindowCheck['is_allowed'] && ! $isFinishingCurrentLesson) {
            return [
                'can_access' => false,
                'reason' => $scheduleWindowCheck['reason'],
                'message' => $scheduleWindowCheck['message'],
                'progress' => null,
            ];
        }

        // 1. Verifica se já está concluída ou em andamento pelo aluno (sempre acessível para continuar ou revisar durante o horário letivo ou tolerância)
        if ($progress && in_array($progress->status, ['completed', 'in_progress'], true)) {
            // Se o horário já encerrou e a aula já está concluída, não permite reabrir/revisar fora do turno
            if (! $scheduleWindowCheck['is_allowed'] && $progress->status === 'completed') {
                return [
                    'can_access' => false,
                    'reason' => $scheduleWindowCheck['reason'],
                    'message' => $scheduleWindowCheck['message'],
                    'progress' => null,
                ];
            }

            return [
                'can_access' => true,
                'reason' => 'in_progress_or_completed',
                'progress' => $progress,
            ];
        }

        // 1.5. Liberação Manual desta aula específica por educador autorizado
        if ($this->isLessonUnlockedByEducator($contract, $lesson, $date)) {
            return [
                'can_access' => true,
                'reason' => 'unlocked_by_educator',
                'message' => 'Aula liberada manualmente pelo educador.',
                'progress' => $progress,
            ];
        }

        // 2. Trava de Seção e Curso da Formação: A seção e o curso devem estar liberados
        $module = $lesson->module;
        $course = $module?->course;
        if ($module) {
            $sectionAccess = $this->canAccessSection($contract, $module);
            if (! $sectionAccess['can_access']) {
                return [
                    'can_access' => false,
                    'reason' => $sectionAccess['reason'],
                    'message' => $sectionAccess['message'],
                    'progress' => null,
                ];
            }
        }

        // 3. Trava de Teto Diário (Pacing): Não permitir mais de 1 lição nova por dia de aula (salvo se liberado pelo educador)
        $completedToday = $this->getStudentDailyCompletedLessonsCount($contract, $date);
        if ($completedToday >= self::DEFAULT_MAX_LESSONS_PER_SESSION && ! $this->isDailyLimitUnlockedByEducator($contract, $date)) {
            return [
                'can_access' => false,
                'reason' => 'daily_limit_reached',
                'message' => 'Você atingiu o limite de 1 aula concluída por dia. Excelente dedicação! Pratique os exercícios complementares ou solicite a liberação da próxima aula ao seu educador.',
                'progress' => null,
            ];
        }

        // 4. Trava de Pré-Requisito Linear: A aula anterior na sequência do curso deve estar concluída com maestria
        if ($course) {
            $orderedLessons = $this->getOrderedLessonsForCourse($course);
            $currentIndex = $orderedLessons->search(fn (LmsLesson $l) => $l->id === $lesson->id);

            if ($currentIndex !== false && $currentIndex > 0) {
                $previousLesson = $orderedLessons->get($currentIndex - 1);

                if ($previousLesson) {
                    $prevProgress = LmsStudentLessonProgress::query()
                        ->where('contract_id', $contract->id)
                        ->where('lesson_id', $previousLesson->id)
                        ->first();

                    if (! $prevProgress || $prevProgress->status !== 'completed' || ($prevProgress->score !== null && $prevProgress->score < $previousLesson->passing_score)) {
                        return [
                            'can_access' => false,
                            'reason' => 'previous_lesson_incomplete',
                            'message' => "Você precisa concluir a aula '{$previousLesson->title}' com nota mínima de {$previousLesson->passing_score}% para liberar esta etapa.",
                            'progress' => null,
                        ];
                    }
                }
            }
        } else {
            // Fallback se a aula não tiver curso carregado
            $previousLesson = LmsLesson::query()
                ->where('module_id', $lesson->module_id)
                ->where('sort_order', '<', $lesson->sort_order)
                ->orderByDesc('sort_order')
                ->first();

            if ($previousLesson) {
                $prevProgress = LmsStudentLessonProgress::query()
                    ->where('contract_id', $contract->id)
                    ->where('lesson_id', $previousLesson->id)
                    ->first();

                if (! $prevProgress || $prevProgress->status !== 'completed' || ($prevProgress->score !== null && $prevProgress->score < $previousLesson->passing_score)) {
                    return [
                        'can_access' => false,
                        'reason' => 'previous_lesson_incomplete',
                        'message' => "Você precisa concluir a aula '{$previousLesson->title}' com nota mínima de {$previousLesson->passing_score}% para liberar esta etapa.",
                        'progress' => null,
                    ];
                }
            }
        }

        return [
            'can_access' => true,
            'reason' => 'prerequisites_met',
            'progress' => $progress,
        ];
    }

    /**
     * Verifica se o aluno cumpriu a etapa obrigatória de 15 minutos (900s) de digitação pré-aula
     */
    public function hasCompletedTypingWarmup(Contract $contract, ?LmsLesson $lesson = null): bool
    {
        // 1. Se houver progresso desta aula e o aquecimento já foi registrado ou a aula já foi concluída
        if ($lesson) {
            $progress = LmsStudentLessonProgress::query()
                ->where('contract_id', $contract->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            if ($progress && $progress->typing_warmup_completed_at !== null) {
                return true;
            }

            if ($progress && $progress->status === 'completed') {
                return true;
            }
        }

        // 2. Se o aluno acumulou pelo menos 15 minutos (900 segundos) de digitação no dia de hoje
        $todayTypingSeconds = (int) StudentTypingRecord::query()
            ->where('contract_id', $contract->id)
            ->whereDate('completed_at', today())
            ->sum('time_spent_seconds');

        if ($todayTypingSeconds >= 900) {
            if ($lesson && isset($progress) && $progress) {
                $progress->update([
                    'typing_warmup_completed_at' => now(),
                    'typing_time_seconds' => $todayTypingSeconds,
                ]);
            }

            return true;
        }

        return false;
    }

    /**
     * Retorna a quantidade de lições que o aluno concluiu na data informada
     */
    public function getStudentDailyCompletedLessonsCount(?Contract $contract, ?Carbon $date = null): int
    {
        if (! $contract) {
            return 0;
        }

        $date = $date ?? today();

        return LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', $date)
            ->count();
    }

    /**
     * Inicia ou recupera o progresso de uma aula para o aluno
     */
    public function startLesson(Contract $contract, LmsLesson $lesson): LmsStudentLessonProgress
    {
        return DB::transaction(function () use ($contract, $lesson) {
            $firstStep = $lesson->steps()->orderBy('sort_order')->first();

            $progress = LmsStudentLessonProgress::firstOrCreate(
                [
                    'contract_id' => $contract->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'student_id' => $contract->student_id,
                    'current_step_id' => $firstStep?->id,
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'session_date' => today(),
                    'time_spent_seconds' => 0,
                    'last_heartbeat_at' => now(),
                    'exited_at' => null,
                ]
            );

            if ($progress->status !== 'completed') {
                $progress->update([
                    'status' => 'in_progress',
                    'last_heartbeat_at' => now(),
                    'exited_at' => null,
                ]);
            }

            StudentAccessLog::log('lesson_start', "Aluno acessou a aula '{$lesson->title}'", $contract, $lesson->id);

            return $progress;
        });
    }

    /**
     * Registra a resposta em uma etapa e calcula o avanço ou repetição jesuítica
     */
    public function submitStepAttempt(
        LmsStudentLessonProgress $progress,
        LmsLessonStep $step,
        bool $isCorrect,
        ?string $answer = null,
        ?array $attemptData = null
    ): array {
        return DB::transaction(function () use ($progress, $step, $isCorrect, $answer, $attemptData) {
            $attemptCount = LmsStudentStepAttempt::query()
                ->where('progress_id', $progress->id)
                ->where('step_id', $step->id)
                ->count();

            LmsStudentStepAttempt::create([
                'progress_id' => $progress->id,
                'step_id' => $step->id,
                'attempt_number' => $attemptCount + 1,
                'is_correct' => $isCorrect,
                'student_answer' => $answer,
                'attempt_data' => $attemptData,
            ]);

            // Se for passo avaliativo/quiz ou prático e o aluno errou, aplica a Repetitio
            if (! $isCorrect) {
                return [
                    'advances' => false,
                    'requires_repetition' => true,
                    'attempt_number' => $attemptCount + 1,
                    'feedback' => $step->content['explanation_on_error'] ?? 'Resposta incorreta. Revise o conceito e tente novamente para fixar o aprendizado.',
                ];
            }

            // Se acertou, localiza o próximo passo linear da aula
            $nextStep = LmsLessonStep::query()
                ->where('lesson_id', $step->lesson_id)
                ->where('sort_order', '>', $step->sort_order)
                ->orderBy('sort_order')
                ->first();

            if ($nextStep) {
                $progress->update([
                    'current_step_id' => $nextStep->id,
                ]);

                return [
                    'advances' => true,
                    'lesson_completed' => false,
                    'next_step_id' => $nextStep->id,
                    'message' => 'Excelente! Passo concluído com sucesso.',
                ];
            }

            // Se não há próximo passo, a aula foi finalizada!
            // Calcula a pontuação com base nas tentativas (1ª tentativa: 100%, 2ª tentativa com reforço: 80%, 3ª+: 60%)
            $totalScorableSteps = LmsLessonStep::query()
                ->where('lesson_id', $step->lesson_id)
                ->whereIn('step_type', ['quiz', 'hotspot_simulation', 'guided_simulation', 'challenge', 'assignment'])
                ->get();

            $score = 100.00;
            if ($totalScorableSteps->isNotEmpty()) {
                $totalPoints = 0.0;
                foreach ($totalScorableSteps as $scorableStep) {
                    $successfulAttempt = LmsStudentStepAttempt::query()
                        ->where('progress_id', $progress->id)
                        ->where('step_id', $scorableStep->id)
                        ->where('is_correct', true)
                        ->orderBy('attempt_number')
                        ->first();

                    if ($successfulAttempt) {
                        if (isset($successfulAttempt->attempt_data['custom_score'])) {
                            $points = (float) $successfulAttempt->attempt_data['custom_score'];
                        } else {
                            $points = match ($successfulAttempt->attempt_number) {
                                1 => 100.0,
                                2 => 80.0,
                                default => 60.0,
                            };
                        }
                        $totalPoints += $points;
                    }
                }

                $score = round($totalPoints / $totalScorableSteps->count(), 2);
            }

            $lesson = $progress->lesson;
            $passed = $score >= (float) $lesson->passing_score;

            $progress->update([
                'status' => $passed ? 'completed' : 'failed',
                'score' => $score,
                'completed_at' => now(),
            ]);

            $course = $lesson->module?->course;
            if ($course && $course->isAurosoftLms()) {
                try {
                    app(AurosoftLmsProgressSyncService::class)
                        ->syncForContractAndCourse($progress->contract, $course);
                } catch (\Throwable $e) {
                    Log::warning("Erro ao sincronizar progresso Aurosoft LMS: {$e->getMessage()}");
                }
            }

            StudentAccessLog::log(
                'lesson_completed',
                $passed
                    ? "Aluno concluiu com maestria a aula '{$lesson->title}' com nota {$score}%"
                    : "Aluno finalizou a aula '{$lesson->title}' com nota {$score}% (abaixo da média exigida)",
                $progress->contract,
                $lesson->id
            );

            return [
                'advances' => true,
                'lesson_completed' => true,
                'passed' => $passed,
                'score' => $score,
                'passing_score' => (float) $lesson->passing_score,
                'message' => $passed
                    ? 'Parabéns! Você dominou todos os conceitos e concluiu esta aula com maestria!'
                    : "Você concluiu os passos, mas obteve nota {$score}% (o mínimo exigido é {$lesson->passing_score}%). Recomendamos refazer os exercícios para fixação.",
            ];
        });
    }

    /**
     * Retorna a nota média geral do aluno em todas as aulas concluídas (LMS Aurosoft + Ouro Moderno)
     */
    public function getStudentOverallGrade(Contract $contract): ?float
    {
        $avg = LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->avg('score');

        $scores = [];
        if ($avg !== null && is_numeric($avg)) {
            $scores[] = (float) $avg;
        }

        $contractCourses = ContractCourse::query()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->get();

        $ouroSync = app(OuroModernoProgressSyncService::class);
        foreach ($contractCourses as $cc) {
            if ($cc->course && $cc->course->isAurosoftLms()) {
                continue;
            }

            if ($cc->grade !== null && is_numeric($cc->grade)) {
                $scores[] = (float) $cc->grade;
            } elseif (! empty($cc->ouro_moderno_lesson_status) || ! empty($cc->ouro_moderno_test_status)) {
                $fb = $ouroSync->feedback($cc);
                if (isset($fb['average']) && is_numeric($fb['average'])) {
                    $scores[] = (float) $fb['average'];
                }
            }
        }

        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 1);
    }

    /**
     * Retorna o tempo total de estudo do aluno em segundos (LMS Aurosoft + Ouro Moderno)
     */
    public function getStudentTotalStudyTimeSeconds(Contract $contract): int
    {
        $localTime = (int) LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->sum('time_spent_seconds');

        $contractCourses = ContractCourse::query()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->get();

        $ouroSync = app(OuroModernoProgressSyncService::class);
        $ouroLessons = 0;
        foreach ($contractCourses as $cc) {
            if ($cc->course && $cc->course->isAurosoftLms()) {
                continue;
            }

            if (! empty($cc->ouro_moderno_lesson_status)) {
                $fb = $ouroSync->feedback($cc);
                $ouroLessons += (int) ($fb['completed_lessons'] ?? 0);
            } elseif ($cc->status === 'completed' || $cc->ouro_moderno_status === 'concluido') {
                $ouroLessons += (int) ($cc->course?->workload ?? 20);
            }
        }

        // Cada aula concluída da Ouro Moderno equivale a aprox. 45 minutos (2700s) de estudo
        return $localTime + ($ouroLessons * 2700);
    }

    /**
     * Retorna o total de aulas concluídas com sucesso pelo aluno (LMS Aurosoft + Ouro Moderno)
     */
    public function getStudentCompletedLessonsCount(Contract $contract): int
    {
        $localCompleted = LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->where('status', 'completed')
            ->count();

        $contractCourses = ContractCourse::query()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->get();

        $ouroCompleted = 0;
        $ouroSync = app(OuroModernoProgressSyncService::class);
        foreach ($contractCourses as $cc) {
            if ($cc->course && $cc->course->isAurosoftLms()) {
                continue;
            }

            if (! empty($cc->ouro_moderno_lesson_status)) {
                $fb = $ouroSync->feedback($cc);
                $ouroCompleted += (int) ($fb['completed_lessons'] ?? 0);
            } elseif ($cc->status === 'completed' || $cc->ouro_moderno_status === 'concluido') {
                $ouroCompleted += (int) ($cc->course?->workload ?? 20);
            }
        }

        return $localCompleted + $ouroCompleted;
    }

    /**
     * Retorna a porcentagem de progresso do aluno no curso (0% a 100%)
     */
    public function getCourseProgressPercentage(Contract $contract, Course $course): int
    {
        $lessonIds = LmsLesson::query()
            ->whereIn('module_id', $course->lmsModules()->select('id'))
            ->where('is_active', true)
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            $contractCourse = ContractCourse::query()
                ->whereIn('contract_item_id', $contract->items()->select('id'))
                ->where('course_id', $course->id)
                ->first();

            if ($contractCourse) {
                if ($contractCourse->status === 'completed') {
                    return 100;
                }

                // Verifica se possui progresso de lições sincronizadas da Ouro Moderno
                if (! empty($contractCourse->ouro_moderno_lesson_status)) {
                    $feedback = app(OuroModernoProgressSyncService::class)->feedback($contractCourse);
                    if (! empty($feedback['total_lessons']) && $feedback['total_lessons'] > 0) {
                        return (int) min(100, round(($feedback['completed_lessons'] / $feedback['total_lessons']) * 100));
                    }
                }

                if ($contractCourse->status === 'in_progress') {
                    return 50;
                }
            }

            return $this->isCourseCompleted($contract, $course) ? 100 : 0;
        }

        $completedCount = LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', 'completed')
            ->count();

        return (int) min(100, round(($completedCount / $lessonIds->count()) * 100));
    }

    /**
     * Retorna o histórico de todas as atividades/lições feitas pelo aluno
     *
     * @return Collection<int, LmsStudentLessonProgress>
     */
    public function getStudentCompletedActivities(Contract $contract): Collection
    {
        return LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->with(['lesson.module.course'])
            ->orderByDesc('completed_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Retorna o ranking gamificado de alunos da escola (Leaderboard) e a posição do aluno atual
     */
    public function getSchoolLeaderboard(Contract $currentContract, int $limit = 10): array
    {
        $schoolId = $currentContract->school_id;

        // Busca todos os contratos ativos/assinados da unidade
        $contracts = Contract::withoutGlobalScopes()
            ->where(function ($q) use ($schoolId) {
                if ($schoolId) {
                    $q->where('school_id', $schoolId);
                }
            })
            ->whereIn('status', ['active', 'in_progress', 'signed'])
            ->with(['student', 'items.contractCourses'])
            ->get();

        $studentScores = [];

        foreach ($contracts as $contract) {
            $studentId = (int) $contract->student_id;
            if (! $studentId) {
                continue;
            }

            $completedCount = $this->getStudentCompletedLessonsCount($contract);
            $grade = $this->getStudentOverallGrade($contract) ?? 0.0;
            $points = ($completedCount * 100) + ($completedCount > 0 ? (int) ($grade * 10) : 0);

            // Mantém a melhor pontuação caso o aluno tenha mais de um contrato
            if (! isset($studentScores[$studentId]) || $studentScores[$studentId]['total_points'] < $points) {
                $studentScores[$studentId] = [
                    'student_id' => $studentId,
                    'contract_id' => $contract->id,
                    'student_name' => $contract->student?->name ?? 'Aluno ' . $studentId,
                    'completed_count' => $completedCount,
                    'avg_score' => $grade,
                    'total_points' => $points,
                    'is_current' => (int) $contract->id === (int) $currentContract->id || $studentId === (int) $currentContract->student_id,
                ];
            }
        }

        $currentStudentId = (int) $currentContract->student_id;
        if ($currentStudentId && ! isset($studentScores[$currentStudentId])) {
            $currCompleted = $this->getStudentCompletedLessonsCount($currentContract);
            $currGrade = $this->getStudentOverallGrade($currentContract) ?? 0.0;
            $currPoints = ($currCompleted * 100) + ($currCompleted > 0 ? (int) ($currGrade * 10) : 0);

            $studentScores[$currentStudentId] = [
                'student_id' => $currentStudentId,
                'contract_id' => $currentContract->id,
                'student_name' => $currentContract->student?->name ?? 'Você',
                'completed_count' => $currCompleted,
                'avg_score' => $currGrade,
                'total_points' => $currPoints,
                'is_current' => true,
            ];
        }

        // Ordena decrescente por pontos e depois por aulas concluídas
        usort($studentScores, function ($a, $b) {
            if ($b['total_points'] === $a['total_points']) {
                return $b['completed_count'] <=> $a['completed_count'];
            }

            return $b['total_points'] <=> $a['total_points'];
        });

        $ranked = [];
        $currentRank = null;
        $rankNumber = 1;

        foreach ($studentScores as $entry) {
            $entry['rank'] = $rankNumber;

            if ($entry['is_current'] && $currentRank === null) {
                $currentRank = $entry;
            }

            if (count($ranked) < $limit) {
                $ranked[] = $entry;
            }

            $rankNumber++;
        }

        if ($currentRank === null) {
            $currentRank = [
                'rank' => count($studentScores) + 1,
                'student_id' => $currentContract->student_id,
                'contract_id' => $currentContract->id,
                'student_name' => $currentContract->student?->name ?? 'Você',
                'completed_count' => $this->getStudentCompletedLessonsCount($currentContract),
                'avg_score' => $this->getStudentOverallGrade($currentContract) ?? 0.0,
                'total_points' => 0,
                'is_current' => true,
            ];
        }

        return [
            'top' => $ranked,
            'current_student_rank' => $currentRank,
        ];
    }

    /**
     * Libera uma aula específica para um contrato por um educador
     */
    public function unlockLessonForContract(
        Contract $contract,
        LmsLesson $lesson,
        ?User $byUser = null,
        ?string $reason = null,
        ?Carbon $date = null
    ): LmsLessonUnlock {
        $unlockDate = ($date ?? Carbon::today())->toDateString();

        return LmsLessonUnlock::updateOrCreate(
            [
                'contract_id' => $contract->id,
                'lesson_id' => $lesson->id,
                'unlocked_date' => $unlockDate,
            ],
            [
                'unlocked_by_user_id' => $byUser?->id ?? Auth::id(),
                'reason' => $reason ?? 'Liberação pedagógica autorizada pelo educador.',
            ]
        );
    }

    /**
     * Libera o teto diário de aulas (pacing) para um contrato hoje
     */
    public function unlockDailyLimitForContract(
        Contract $contract,
        ?User $byUser = null,
        ?string $reason = null,
        ?Carbon $date = null
    ): LmsLessonUnlock {
        $unlockDate = ($date ?? Carbon::today())->toDateString();

        return LmsLessonUnlock::updateOrCreate(
            [
                'contract_id' => $contract->id,
                'lesson_id' => null,
                'unlocked_date' => $unlockDate,
            ],
            [
                'unlocked_by_user_id' => $byUser?->id ?? Auth::id(),
                'reason' => $reason ?? 'Teto diário liberado pelo educador para continuidade de estudos.',
            ]
        );
    }

    /**
     * Libera a próxima aula bloqueada para o aluno (liberando a aula e o teto diário)
     */
    public function unlockNextLessonForContract(
        Contract $contract,
        ?User $byUser = null,
        ?string $reason = null
    ): ?LmsLesson {
        $target = $this->getNextOrCurrentLessonForStudent($contract, ignoreScheduleWindow: true);
        $lesson = $target['lesson'] ?? null;

        if ($lesson) {
            $this->unlockLessonForContract($contract, $lesson, $byUser, $reason);
            $this->unlockDailyLimitForContract($contract, $byUser, $reason);
        }

        return $lesson;
    }

    /**
     * Verifica se uma aula foi explicitamente liberada por educador
     */
    public function isLessonUnlockedByEducator(Contract $contract, LmsLesson $lesson, ?Carbon $date = null): bool
    {
        $unlockDate = ($date ?? Carbon::today())->toDateString();

        return LmsLessonUnlock::query()
            ->where('contract_id', $contract->id)
            ->where('lesson_id', $lesson->id)
            ->whereDate('unlocked_date', $unlockDate)
            ->exists();
    }

    /**
     * Verifica se o teto diário foi liberado por educador para a data indicada
     */
    public function isDailyLimitUnlockedByEducator(Contract $contract, ?Carbon $date = null): bool
    {
        $unlockDate = ($date ?? Carbon::today())->toDateString();

        return LmsLessonUnlock::query()
            ->where('contract_id', $contract->id)
            ->whereNull('lesson_id')
            ->whereDate('unlocked_date', $unlockDate)
            ->exists();
    }

    /**
     * Localiza a próxima lição ou a lição em andamento onde o aluno parou
     *
     * @return array{course: ?Course, lesson: ?LmsLesson, progress: ?LmsStudentLessonProgress, type: string, is_completed: bool}
     */
    public function getNextOrCurrentLessonForStudent(Contract $contract, bool $ignoreScheduleWindow = false): array
    {
        // 1. Se há alguma aula em andamento (in_progress), retoma ela somente se o curso for acessível
        $inProgress = LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->where('status', 'in_progress')
            ->with(['lesson.module.course'])
            ->latest('updated_at')
            ->first();

        if ($inProgress && $inProgress->lesson) {
            $inProgressCourse = $inProgress->lesson->module?->course;
            if ($inProgressCourse && $this->canAccessCourse($contract, $inProgressCourse, $ignoreScheduleWindow)['can_access']) {
                return [
                    'course' => $inProgressCourse,
                    'lesson' => $inProgress->lesson,
                    'progress' => $inProgress,
                    'type' => 'resuming',
                    'is_completed' => false,
                ];
            }
        }

        // 2. Procura a primeira aula não concluída nos cursos ordenados que estejam acessíveis
        // Prioriza as formações agendadas para hoje
        $items = $contract->items()
            ->orderByRaw('COALESCE("order", 999) ASC')
            ->orderBy('id', 'asc')
            ->with(['schedules', 'formation'])
            ->get();

        $currentWeekday = now()->dayOfWeek;
        $sortedItems = $items->sortByDesc(function ($item) use ($currentWeekday): int {
            return $item->schedules->contains(fn ($s) => (int) $s->weekday === $currentWeekday) ? 1 : 0;
        })->values();

        foreach ($sortedItems as $item) {
            $courses = $this->getOrderedCoursesForContractItem($contract, $item);
            foreach ($courses as $course) {
                $courseAccess = $this->canAccessCourse($contract, $course, $ignoreScheduleWindow);
                if (! $courseAccess['can_access']) {
                    // Não avança para cursos bloqueados dentro desta formação
                    break;
                }

                if ($course->isFromOuroModerno() && ! $this->isCourseCompleted($contract, $course)) {
                    // Se o curso da Ouro Moderno não foi concluído, interrompe o fluxo nesta formação
                    break;
                }

                $orderedLessons = $this->getOrderedLessonsForCourse($course);
                foreach ($orderedLessons as $lesson) {
                    $isCompleted = LmsStudentLessonProgress::query()
                        ->where('contract_id', $contract->id)
                        ->where('lesson_id', $lesson->id)
                        ->where('status', 'completed')
                        ->exists();

                    if (! $isCompleted) {
                        $progress = LmsStudentLessonProgress::query()
                            ->where('contract_id', $contract->id)
                            ->where('lesson_id', $lesson->id)
                            ->first();

                        return [
                            'course' => $course,
                            'lesson' => $lesson,
                            'progress' => $progress,
                            'type' => 'next',
                            'is_completed' => false,
                        ];
                    }
                }
            }
        }

        // 3. Todas as aulas de todos os cursos foram concluídas!
        return [
            'course' => null,
            'lesson' => null,
            'progress' => null,
            'type' => 'completed_all',
            'is_completed' => true,
        ];
    }
}
