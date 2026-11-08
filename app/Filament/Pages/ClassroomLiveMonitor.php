<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Models\Contract;
use App\Models\LmsLessonUnlock;
use App\Models\LmsStudentAssignment;
use App\Models\LmsStudentLessonProgress;
use App\Models\LmsStudentStepAttempt;
use App\Models\StudentAccessLog;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Services\LmsProgressionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ClassroomLiveMonitor extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $navigationLabel = 'Monitor de Laboratório (Ao Vivo)';

    protected static ?string $title = 'Painel da Sala de Aula (Monitor em Tempo Real)';

    protected static ?string $slug = 'classroom-live-monitor';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.classroom-live-monitor';

    // Seletor de Modalidade: 'presencial' ou 'ead'
    public string $activeMode = 'presencial';

    // Filtros e busca
    public ?int $selectedClassroomId = null;

    public string $statusFilter = 'all'; // all, studying, needs_help, out_of_focus, completed_today

    public string $search = '';

    public bool $autoRefresh = true;

    // Modal de busca avulsa de aluno
    public bool $showLookupModal = false;

    public string $lookupQuery = '';

    public ?int $inspectedContractId = null;

    // Modal de Visto de Trabalhos Práticos (Assignments)
    public bool $showAssignmentsModal = false;

    public ?int $gradingAssignmentId = null;

    public float $gradingScore = 100.0;

    public string $gradingFeedback = '';

    // Modal de Chat Rápido / Mensagem Direta para a Tela do Aluno
    public bool $showDirectMessageModal = false;

    public ?int $directMessageProgressId = null;

    public string $directMessageStudentName = '';

    public string $directMessageText = '';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('super_admin')
            || $user->can('View:ClassroomLiveMonitor')
            || $user->can('page_ClassroomLiveMonitor')
            || $user->can('View:LmsStudentProgress')
        );
    }

    /**
     * Carrega a lista de salas disponíveis para filtro
     *
     * @return Collection<int, Classroom>
     */
    public function getClassroomsProperty(): Collection
    {
        return Classroom::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Retorna a lista de alunos ativos no laboratório hoje com seu progresso pedagógico
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMonitoredStudentsProperty(): array
    {
        $today = Carbon::today();

        // 1. Coleta IDs de contratos presentes hoje nas chamadas
        $presentAttendances = StudentAttendance::query()
            ->where('attendance_date', $today->toDateString())
            ->where('status', 'present')
            ->with([
                'studentSchedule.contractItem.contract.student',
                'studentSchedule.classroom',
                'session.classroom',
            ])
            ->get();

        $contractMap = [];
        foreach ($presentAttendances as $attendance) {
            $contract = $attendance->studentSchedule?->contractItem?->contract;
            if ($contract && $contract->student) {
                $classroom = $attendance->session?->classroom ?? $attendance->studentSchedule?->classroom;
                $contractMap[$contract->id] = [
                    'contract' => $contract,
                    'classroom_name' => $classroom?->name ?? 'Sala Geral',
                    'schedule_time' => $attendance->studentSchedule
                        ? sprintf('%02d:00 - %02d:00', $attendance->studentSchedule->start_hour, $attendance->studentSchedule->end_hour)
                        : null,
                ];
            }
        }

        // 2. Coleta contratos que tiveram progresso LMS hoje (mesmo sem chamada marcada)
        $todayProgresses = LmsStudentLessonProgress::query()
            ->where(function ($query) use ($today) {
                $query->where('session_date', $today->toDateString())
                    ->orWhere('updated_at', '>=', $today->startOfDay());
            })
            ->with(['contract.student', 'lesson.module.course', 'currentStep'])
            ->get();

        foreach ($todayProgresses as $prog) {
            if ($prog->contract && $prog->contract->student && ! isset($contractMap[$prog->contract->id])) {
                $contractMap[$prog->contract->id] = [
                    'contract' => $prog->contract,
                    'classroom_name' => 'Laboratório LMS',
                    'schedule_time' => 'Livre / Online',
                ];
            }
        }

        // 3. Monta os dados pedagógicos detalhados para cada aluno
        $results = [];

        foreach ($contractMap as $contractId => $meta) {
            /** @var Contract $contract */
            $contract = $meta['contract'];
            $student = $contract->student;

            // Filtro por texto (nome do aluno ou código do contrato)
            if ($this->search !== '') {
                $term = mb_strtolower(trim($this->search));
                $studentName = mb_strtolower($student->name ?? '');
                $contractCode = mb_strtolower($contract->contract_number ?? '');

                if (! str_contains($studentName, $term) && ! str_contains($contractCode, $term)) {
                    continue;
                }
            }

            // Localiza progresso de hoje ou o status mais recente do aluno
            $latestProgress = LmsStudentLessonProgress::query()
                ->where('contract_id', $contract->id)
                ->with(['lesson.module.course', 'currentStep'])
                ->latest('updated_at')
                ->first();

            $isOuroModerno = false;
            $ouroCourseName = null;
            $ouroLessonTitle = null;

            if (! $latestProgress) {
                $contractCourses = $contract->items->flatMap->contractCourses;
                $activeCc = $contractCourses->first(fn ($cc) => $cc->status === 'in_progress' || ! empty($cc->ouro_moderno_lesson_status));
                if (! $activeCc) {
                    $activeCc = $contractCourses->first();
                }

                if ($activeCc) {
                    $isOuroModerno = true;
                    $ouroCourseName = $activeCc->course?->name ?? 'Curso Prático';
                    $ouroSync = app(\App\Services\OuroModernoProgressSyncService::class);
                    $fb = $ouroSync->feedback($activeCc);

                    $currentLessonNum = $fb['current_lesson'] ?? (($fb['completed_lessons'] ?? 0) + 1);
                    $ouroLessonTitle = "Aula {$currentLessonNum} (" . ($fb['completed_lessons'] ?? 0) . " de " . ($fb['total_lessons'] ?? 0) . " concluídas)";
                    $stepPct = ! empty($fb['total_lessons']) ? (int) min(100, round(($fb['completed_lessons'] / $fb['total_lessons']) * 100)) : 50;
                    $score = $fb['average'] !== null ? (float) $fb['average'] : null;
                    $status = $fb['local_status'] ?? 'in_progress';
                    $isActivelyStudying = true;
                }
            }

            $lesson = $latestProgress?->lesson;
            $currentStep = $latestProgress?->currentStep;
            $status = $latestProgress?->status ?? 'pending';
            $score = $latestProgress?->score !== null ? (float) $latestProgress->score : null;
            $updatedAt = $latestProgress?->updated_at;

            // Contagem de tentativas incorretas no passo atual
            $mistakesOnCurrentStep = 0;
            if ($latestProgress && $currentStep) {
                $mistakesOnCurrentStep = LmsStudentStepAttempt::query()
                    ->where('progress_id', $latestProgress->id)
                    ->where('step_id', $currentStep->id)
                    ->where('is_correct', false)
                    ->count();
            }

            // Cálculo dos passos do progresso
            $totalSteps = 1;
            $currentStepOrder = 1;
            if ($lesson) {
                $allSteps = $lesson->steps()->orderBy('sort_order')->get();
                $totalSteps = max(1, $allSteps->count());
                if ($currentStep) {
                    $foundIndex = $allSteps->search(fn ($s) => $s->id === $currentStep->id);
                    $currentStepOrder = $foundIndex !== false ? $foundIndex + 1 : 1;
                } elseif ($status === 'completed') {
                    $currentStepOrder = $totalSteps;
                }
            }

            $stepPct = min(100, round(($currentStepOrder / $totalSteps) * 100));

            // Classificação de Status com detecção de Saída (exited_at) e Heartbeat
            $hasExited = false;
            if ($latestProgress?->exited_at) {
                if (! $latestProgress->last_heartbeat_at || $latestProgress->exited_at->greaterThanOrEqualTo($latestProgress->last_heartbeat_at)) {
                    $hasExited = true;
                }
            }

            $lastActivity = $latestProgress?->last_heartbeat_at ?? $updatedAt;
            $isHeartbeatAlive = $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(3));
            $isActivelyStudying = $status === 'in_progress' && ! $hasExited && $isHeartbeatAlive;

            $isOutOfFocus = $latestProgress !== null && $latestProgress->is_in_focus === false && ! $hasExited && $status === 'in_progress';
            $focusLossCount = (int) ($latestProgress?->focus_loss_count ?? 0);

            $isHelpRequested = (bool) ($latestProgress?->help_requested ?? false);
            $isNeedsHelp = $isHelpRequested || $mistakesOnCurrentStep >= 2;
            $isCompletedToday = $status === 'completed' && $latestProgress?->completed_at && $latestProgress->completed_at->isToday();

            // Trabalho Prático pendente de visto
            $pendingAssignment = LmsStudentAssignment::query()
                ->where('contract_id', $contract->id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            // Verificação de Aniversário ou Conquista Nota 100
            $isBirthdayToday = false;
            if (! empty($student->birth_date)) {
                try {
                    $isBirthdayToday = Carbon::parse($student->birth_date)->isBirthday();
                } catch (\Throwable) {
                    $isBirthdayToday = false;
                }
            }

            $cleanPhone = preg_replace('/\D+/', '', (string) ($student->phone ?? $student->whatsapp ?? ''));
            if ($cleanPhone !== '' && strlen($cleanPhone) <= 11 && ! str_starts_with($cleanPhone, '55')) {
                $cleanPhone = '55'.$cleanPhone;
            }
            $congratsText = $isBirthdayToday
                ? "🎉 Parabéns pelo seu aniversário, {$student->name}! Toda a equipe da escola deseja muito sucesso na sua jornada!"
                : ($score !== null && $score >= 100
                    ? "🏆 Parabéns, {$student->name}! Você tirou Nota 100% de Maestria na aula '{$lesson?->title}'!"
                    : "👏 Parabéns pela conclusão da aula '{$lesson?->title}', {$student->name}! Continue firme nos estudos!");
            $whatsappCongratsUrl = ($cleanPhone !== '' ? "https://wa.me/{$cleanPhone}?text=" : 'https://wa.me/?text=').rawurlencode($congratsText);

            // Verificação de Liberação de Educador hoje
            $isLessonUnlockedToday = LmsLessonUnlock::query()
                ->where('contract_id', $contract->id)
                ->whereDate('unlocked_date', $today)
                ->exists();

            // Determina a etiqueta de status
            if ($isHelpRequested) {
                $statusType = 'needs_help';
                $statusLabel = '🚨 Chamando Educador!';
                $statusColor = 'danger';
            } elseif ($isNeedsHelp) {
                $statusType = 'needs_help';
                $statusLabel = 'Atenção: Dúvida / 2+ Erros';
                $statusColor = 'danger';
            } elseif ($isOutOfFocus) {
                $statusType = 'out_of_focus';
                $statusLabel = '👀 Fora da Aula';
                $statusColor = 'warning';
            } elseif ($isCompletedToday) {
                $statusType = 'completed_today';
                $statusLabel = 'Aula Concluída Hoje';
                $statusColor = 'success';
            } elseif ($isActivelyStudying) {
                $statusType = 'studying';
                $statusLabel = 'Estudando Agora';
                $statusColor = 'primary';
            } elseif ($hasExited) {
                $statusType = 'idle';
                $statusLabel = '🚪 Saiu da Aula';
                $statusColor = 'gray';
            } else {
                $statusType = 'idle';
                $statusLabel = $status === 'in_progress' ? 'Offline / Ausente' : 'Aguardando Início';
                $statusColor = 'gray';
            }

            // Aplicar filtro de status
            if ($this->statusFilter === 'studying' && ! $isActivelyStudying) {
                continue;
            }
            if ($this->statusFilter === 'needs_help' && ! $isNeedsHelp) {
                continue;
            }
            if ($this->statusFilter === 'out_of_focus' && ! $isOutOfFocus) {
                continue;
            }
            if ($this->statusFilter === 'completed_today' && ! $isCompletedToday) {
                continue;
            }

            $results[] = [
                'contract_id' => $contract->id,
                'contract_code' => $contract->contract_number ?? "#{$contract->id}",
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_initials' => mb_strtoupper(mb_substr($student->name, 0, 2)),
                'classroom_name' => $meta['classroom_name'],
                'schedule_time' => $meta['schedule_time'],
                'course_name' => $isOuroModerno ? $ouroCourseName : ($lesson?->module?->course?->name ?? 'Aurosoft LMS'),
                'module_title' => $isOuroModerno ? 'Curso Interativo' : ($lesson?->module?->title ?? 'Módulo Geral'),
                'lesson_id' => $lesson?->id,
                'lesson_title' => $isOuroModerno ? $ouroLessonTitle : ($lesson?->title ?? 'Nenhuma aula iniciada'),
                'current_step_title' => $isOuroModerno ? 'Laboratório Interativo' : ($currentStep?->title ?? ($status === 'completed' ? 'Aula Concluída' : 'Introdução')),
                'is_ouro_moderno' => $isOuroModerno,
                'current_step_phase' => $currentStep?->phase ?? 'praelectio',
                'current_step_order' => $currentStepOrder,
                'total_steps' => $totalSteps,
                'progress_pct' => $stepPct,
                'status_type' => $statusType,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'is_actively_studying' => $isActivelyStudying,
                'is_out_of_focus' => $isOutOfFocus,
                'focus_loss_count' => $focusLossCount,
                'is_needs_help' => $isNeedsHelp,
                'is_help_requested' => $isHelpRequested,
                'mistakes_count' => $mistakesOnCurrentStep,
                'score' => $score,
                'time_spent_seconds' => $latestProgress?->time_spent_seconds ?? 0,
                'time_spent_formatted' => round(($latestProgress?->time_spent_seconds ?? 0) / 60).' min',
                'last_activity_text' => $updatedAt ? $updatedAt->diffForHumans() : 'Hoje',
                'is_unlocked_today' => $isLessonUnlockedToday,
                'progress_id' => $latestProgress?->id,
                'pending_assignment_id' => $pendingAssignment?->id,
                'pending_assignment_name' => $pendingAssignment?->original_name,
                'is_birthday_today' => $isBirthdayToday,
                'whatsapp_congrats_url' => $whatsappCongratsUrl,
                'student_rating' => $latestProgress?->student_rating,
                'student_rating_comment' => $latestProgress?->student_rating_comment,
                'has_unread_educator_message' => $latestProgress !== null && ! empty($latestProgress->educator_message) && $latestProgress->educator_message_read_at === null,
            ];
        }

        // Ordena por prioridade: alunos que chamaram o professor primeiro, depois dúvidas/erros, depois fora de foco, depois estudando
        usort($results, function ($a, $b) {
            if ($a['is_help_requested'] && ! $b['is_help_requested']) {
                return -1;
            }
            if (! $a['is_help_requested'] && $b['is_help_requested']) {
                return 1;
            }

            $order = ['needs_help' => 1, 'out_of_focus' => 2, 'studying' => 3, 'completed_today' => 4, 'idle' => 5];
            $rankA = $order[$a['status_type']] ?? 6;
            $rankB = $order[$b['status_type']] ?? 6;

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return strcasecmp($a['student_name'], $b['student_name']);
        });

        return $results;
    }

    public function openDirectMessageModal(int $progressId, string $studentName = ''): void
    {
        $this->directMessageProgressId = $progressId;
        $this->directMessageStudentName = $studentName;
        $this->directMessageText = '';
        $this->showDirectMessageModal = true;
    }

    public function sendDirectMessageToStudent(?string $presetMessage = null): void
    {
        if (! $this->directMessageProgressId) {
            return;
        }

        $text = trim($presetMessage !== null ? $presetMessage : $this->directMessageText);
        if ($text === '') {
            Notification::make()->title('Digite ou selecione uma mensagem para enviar.')->warning()->send();

            return;
        }

        $progress = LmsStudentLessonProgress::query()->find($this->directMessageProgressId);
        if (! $progress) {
            Notification::make()->title('Sessão de aula do aluno não encontrada.')->danger()->send();

            return;
        }

        $user = Auth::user();
        $senderName = $user?->person?->name ?? $user?->name ?? 'Professor(a)';

        $progress->update([
            'educator_message' => $text,
            'educator_message_sender' => $senderName,
            'educator_message_at' => now(),
            'educator_message_read_at' => null,
        ]);

        $this->showDirectMessageModal = false;
        $this->directMessageText = '';

        Notification::make()
            ->title('💬 Mensagem enviada para a tela do aluno!')
            ->body("O alerta aparecerá imediatamente no player de {$progress->student?->name}.")
            ->success()
            ->send();
    }

    /**
     * Métricas resumidas de hoje
     *
     * @return array{total_monitored: int, actively_studying: int, needs_help: int, out_of_focus: int, pending_assignments: int, completed_today: int}
     */
    public function getMetricsProperty(): array
    {
        $all = $this->monitoredStudents;

        return [
            'total_monitored' => count($all),
            'actively_studying' => count(array_filter($all, fn ($s) => $s['is_actively_studying'])),
            'needs_help' => count(array_filter($all, fn ($s) => $s['status_type'] === 'needs_help')),
            'out_of_focus' => count(array_filter($all, fn ($s) => ! empty($s['is_out_of_focus']))),
            'pending_assignments' => LmsStudentAssignment::query()->whereHas('contract')->where('status', 'pending')->count(),
            'completed_today' => count(array_filter($all, fn ($s) => $s['status_type'] === 'completed_today')),
        ];
    }

    public function openAssignmentsModal(?int $assignmentId = null): void
    {
        $this->gradingAssignmentId = $assignmentId;
        $this->gradingScore = 100.0;
        $this->gradingFeedback = '';
        $this->showAssignmentsModal = true;
    }

    /**
     * @return Collection<int, LmsStudentAssignment>
     */
    public function getPendingAssignmentsProperty(): Collection
    {
        return LmsStudentAssignment::query()
            ->whereHas('contract')
            ->with(['student', 'contract', 'progress.lesson', 'step.lesson'])
            ->latest('id')
            ->limit(30)
            ->get();
    }

    public function gradeAssignment(int $assignmentId, string $status = 'approved', ?float $score = null, ?string $feedback = null): void
    {
        $assignment = LmsStudentAssignment::query()->find($assignmentId);
        if (! $assignment) {
            Notification::make()->title('Trabalho prático não encontrado')->danger()->send();

            return;
        }

        $finalScore = $score !== null ? $score : $this->gradingScore;
        $finalFeedback = $feedback !== null ? $feedback : $this->gradingFeedback;
        $user = Auth::user();

        $normalizedStatus = in_array($status, ['approved', 'revision_requested', 'needs_revision'], true)
            ? $status
            : 'approved';

        $assignment->update([
            'status' => $normalizedStatus,
            'grade' => $finalScore,
            'feedback' => $finalFeedback,
            'graded_by_user_id' => $user instanceof User ? $user->id : null,
            'graded_at' => now(),
        ]);

        if ($normalizedStatus === 'approved' && $assignment->progress_id && $assignment->step_id) {
            LmsStudentStepAttempt::query()->create([
                'progress_id' => $assignment->progress_id,
                'step_id' => $assignment->step_id,
                'attempt_number' => 1,
                'is_correct' => true,
                'student_answer' => "assignment_approved:{$assignment->id}",
                'attempt_data' => [
                    'assignment_id' => $assignment->id,
                    'educator_approved' => true,
                    'score' => $finalScore,
                ],
            ]);
        }

        $this->gradingAssignmentId = null;
        $this->gradingFeedback = '';

        Notification::make()
            ->title($normalizedStatus === 'approved' ? '✅ Visto Registrado com Sucesso!' : '🔄 Revisão Solicitada ao Aluno')
            ->body("O trabalho prático '{$assignment->file_name}' de {$assignment->student?->name} foi avaliado.")
            ->success()
            ->send();
    }

    /**
     * Ação: Liberar Próxima Aula para o Aluno (e suspender o teto diário)
     */
    public function unlockNextLesson(int $contractId): void
    {
        $contract = Contract::query()->find($contractId);
        if (! $contract) {
            Notification::make()->title('Contrato não encontrado')->danger()->send();

            return;
        }

        /** @var LmsProgressionService $service */
        $service = app(LmsProgressionService::class);
        $user = Auth::user();

        $lesson = $service->unlockNextLessonForContract(
            $contract,
            $user instanceof User ? $user : null,
            'Liberado pelo educador via Monitor de Sala de Aula'
        );

        if ($lesson) {
            Notification::make()
                ->title('🔓 Próxima Aula Liberada!')
                ->body("A aula '{$lesson->title}' e o limite diário de hoje foram desbloqueados para {$contract->student?->name}.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Atenção')
                ->body('Nenhuma aula pendente foi encontrada para liberação ou o aluno já completou toda a grade.')
                ->warning()
                ->send();
        }
    }

    /**
     * Ação: Liberar apenas o teto diário para o aluno poder avançar no mesmo dia
     */
    public function unlockDailyLimit(int $contractId): void
    {
        $contract = Contract::query()->find($contractId);
        if (! $contract) {
            Notification::make()->title('Contrato não encontrado')->danger()->send();

            return;
        }

        /** @var LmsProgressionService $service */
        $service = app(LmsProgressionService::class);
        $user = Auth::user();

        $service->unlockDailyLimitForContract(
            $contract,
            $user instanceof User ? $user : null,
            'Teto diário liberado via Monitor de Sala de Aula'
        );

        Notification::make()
            ->title('⚡ Limite Diário Liberado!')
            ->body("O aluno {$contract->student?->name} agora pode realizar uma aula adicional na data de hoje.")
            ->success()
            ->send();
    }

    /**
     * Ação: Reiniciar a aula atual em caso de travamento do aluno
     */
    public function resetLessonProgress(int $progressId): void
    {
        $progress = LmsStudentLessonProgress::query()->find($progressId);
        if (! $progress) {
            Notification::make()->title('Registro de progresso não encontrado')->danger()->send();

            return;
        }

        // Apaga tentativas de passos para reiniciar limpo
        LmsStudentStepAttempt::query()->where('progress_id', $progress->id)->delete();

        // Encontra o primeiro passo da aula
        $firstStep = $progress->lesson?->steps()->orderBy('sort_order')->first();

        $progress->update([
            'current_step_id' => $firstStep?->id,
            'status' => 'in_progress',
            'score' => null,
            'completed_at' => null,
        ]);

        Notification::make()
            ->title('🔄 Aula Reiniciada')
            ->body("A aula '{$progress->lesson?->title}' foi reiniciada do primeiro passo para {$progress->student?->name}.")
            ->info()
            ->send();
    }

    /**
     * Ação: Marcar pedido de ajuda como atendido pelo professor
     */
    public function resolveHelp(int $progressId): void
    {
        $progress = LmsStudentLessonProgress::query()->find($progressId);
        if ($progress) {
            $progress->update([
                'help_requested' => false,
                'help_requested_at' => null,
            ]);

            Notification::make()
                ->title('Atendimento Registrado')
                ->body('O chamado de ajuda do aluno foi marcado como atendido com sucesso.')
                ->success()
                ->send();
        }
    }

    /**
     * Busca avulsa de alunos cadastrados na escola
     *
     * @return Collection<int, Contract>
     */
    public function getLookupContractsProperty(): Collection
    {
        if (mb_strlen(trim($this->lookupQuery)) < 2) {
            return new Collection;
        }

        $term = trim($this->lookupQuery);

        return Contract::query()
            ->where(function ($sub) use ($term): void {
                $sub->whereHas('student', function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhereCpf($term);
                })
                    ->orWhere('contract_number', 'like', "%{$term}%");
            })
            ->with(['student', 'items.formation'])
            ->limit(10)
            ->get();
    }

    public bool $showLogsModal = false;

    public function openLookupModal(): void
    {
        $this->lookupQuery = '';
        $this->inspectedContractId = null;
        $this->showLookupModal = true;
    }

    /**
     * @return Collection<int, StudentAccessLog>
     */
    public function getRecentAccessLogsProperty(): Collection
    {
        return StudentAccessLog::query()
            ->whereHas('contract')
            ->with(['student', 'contract', 'lesson'])
            ->latest('id')
            ->limit(40)
            ->get();
    }

    /**
     * Retorna os alunos EAD que estão online agora ou tiveram atividades recentes no portal EAD da Ouro Moderno.
     *
     * @return list<array<string, mixed>>
     */
    public function getEadStudentsProperty(): array
    {
        $school = \Filament\Facades\Filament::getTenant();
        if (! $school instanceof \App\Models\School) {
            $schoolId = session('filament_tenant_id') ?? auth()->user()?->school_id;
            $school = $schoolId ? \App\Models\School::find($schoolId) : \App\Models\School::first();
        }

        if (! $school || ! ($school->ouro_moderno_ead_enabled ?? false)) {
            return [];
        }

        $service = app(\App\Services\OuroModernoEadService::class);
        $activities = $service->getLiveStudentActivities($school);

        if ($this->search !== '') {
            $term = mb_strtolower(trim($this->search));
            $activities = array_filter($activities, function ($st) use ($term) {
                return str_contains(mb_strtolower($st['name'] ?? ''), $term)
                    || str_contains((string) ($st['login'] ?? ''), $term)
                    || str_contains(mb_strtolower($st['action'] ?? ''), $term);
            });
        }

        return array_values($activities);
    }

    /**
     * Dispara WhatsApp pedagógico de apoio para um aluno que está estudando no EAD agora.
     */
    public function sendWhatsAppEadStudent(string $studentName, ?string $phone, string $lesson): void
    {
        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : '';
        if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
            $cleanPhone = '55'.$cleanPhone;
        }

        if (empty($cleanPhone)) {
            Notification::make()->title('Telefone não informado no cadastro do aluno')->warning()->send();

            return;
        }

        $school = \Filament\Facades\Filament::getTenant()
            ?? \App\Models\School::getActiveSchool()
            ?? \App\Models\School::first();
        $schoolName = $school?->trade_name ?: ($school?->legal_name ?: 'Nossa Escola');
        $firstName = explode(' ', trim($studentName))[0];

        $aulaRef = $lesson ? " na {$lesson}" : '';
        $msg = "Olá, {$firstName}! 👋 Aqui é da equipe pedagógica da {$schoolName}.\n\n";
        $msg .= "Vimos que você está estudando no portal EAD{$aulaRef}! Passando para desejar excelentes estudos. Se tiver qualquer dúvida com o conteúdo ou exercícios, nossos educadores estão disponíveis para te apoiar! Conte conosco!";

        $this->js("window.open('https://wa.me/{$cleanPhone}?text=".rawurlencode($msg)."', '_blank');");
    }
}
