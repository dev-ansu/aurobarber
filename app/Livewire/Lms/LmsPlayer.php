<?php

declare(strict_types=1);

namespace App\Livewire\Lms;

use App\Models\Contract;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsStudentAssignment;
use App\Models\LmsStudentLessonProgress;
use App\Models\LmsStudentStepAttempt;
use App\Models\User;
use App\Services\LmsProgressionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class LmsPlayer extends Component
{
    use WithFileUploads;

    public LmsLesson $lesson;

    public ?int $contractId = null;

    public bool $isPreview = false;
    public bool $isEducatorStudentTest = false;

    public int $currentStepIndex = 0;

    /**
     * @var Collection<int, LmsLessonStep>
     */
    public Collection $steps;

    // Estado do Quiz (com suporte a Banco de Questões e Sorteio Anti-Cola)
    public ?string $selectedOptionId = null;

    public ?bool $lastAnswerCorrect = null;

    public string $feedbackMessage = '';

    public int $attemptNumber = 1;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $activeQuizQuestions = [];

    public int $activeQuizQuestionIndex = 0;

    /**
     * @var array<int, array{selected: string, is_correct: bool}>
     */
    public array $quizPoolAnswers = [];

    // Estado da Simulação e Simulador Guiado Multi-Passos
    public bool $simulationHotspotClicked = false;

    public int $currentSubStepIndex = 0;

    // Estado do Desafio
    public bool $challengeAccepted = false;

    // Estado de Envio de Trabalho Prático (Assignment)
    public $assignmentFile = null;

    public string $assignmentNotes = '';

    public ?int $submittedAssignmentId = null;

    // Telemetria de Foco da Janela/Aba
    public bool $isInFocus = true;

    // Comemoração de Conquistas (Nota 10 / Conclusão de Módulo)
    public bool $showCelebrationModal = false;

    public string $celebrationType = '';

    public string $celebrationTitle = '';

    public string $celebrationMessage = '';

    // Chat Rápido / Mensagem Direta do Professor na Tela do Aluno
    public ?string $activeEducatorMessage = null;

    public ?string $activeEducatorSender = null;

    // Avaliação Rápida da Aula pelo Aluno (1 a 5 estrelas)
    public int $lessonRating = 0;

    public string $lessonRatingComment = '';

    public bool $lessonRatingSubmitted = false;

    // Timer & Progresso
    public int $timeSpentSeconds = 0;

    public bool $isLessonCompleted = false;

    public ?float $finalScore = null;

    public bool $passed = false;

    // Trava Diária e Pré-requisitos
    public bool $isLocked = false;

    public string $lockReason = '';

    public string $lockMessage = '';

    public ?int $progressId = null;

    // Modal de Zoom de Imagem
    public bool $showZoomModal = false;

    public string $zoomImageUrl = '';

    // Coordenadas de clique e feedback do Simulador Hotspot (Experientia)
    public ?float $lastClickX = null;

    public ?float $lastClickY = null;

    public ?float $lastDragStartX = null;

    public ?float $lastDragStartY = null;

    public ?float $lastDragEndX = null;

    public ?float $lastDragEndY = null;

    public ?bool $hotspotHit = null;

    public int $hotspotMistakeCount = 0;

    public string $typedInput = '';

    // Modo de Revisão de Aula Concluída
    public bool $isReviewMode = false;

    // Tempo de Permanência Mínima (Pacing)
    public int $stepLoadedAt = 0;

    // Modal de Autorização do Educador
    public bool $showEducatorAuthModal = false;

    public string $educatorEmail = '';

    public string $educatorPassword = '';

    public string $educatorAuthError = '';

    // Chamado de Ajuda do Aluno (Integração com Monitor de Sala)
    public bool $helpRequested = false;

    // Conclusão de Vídeo sem Adiantar
    public bool $videoCompleted = false;

    // Regra pedagógica: Treino obrigatório nos primeiros 15 minutos de aula
    public bool $needsTypingWarmup = false;

    public function mount(LmsLesson $lesson, LmsProgressionService $progressionService): void
    {
        $this->lesson = $lesson->load(['module.course', 'steps']);
        $this->steps = $this->lesson->steps()->orderBy('sort_order')->get();
        $this->stepLoadedAt = now()->timestamp;

        $user = Auth::user();
        if (! $user) {
            abort(403, 'Acesso não autorizado.');
        }

        // Colaboradores sem papel definido no sistema não podem acessar o player
        if ($user->isCollaborator() && ! $user->hasDefinedRole()) {
            Auth::logout();
            session()->invalidate();
            abort(403, 'Acesso bloqueado: Este colaborador não possui um papel definido no sistema.');
        }

        $canManage = $user->can('View:LmsLesson') || $user->can('View:LmsStudio') || $user->can('page_LmsStudio');
        $wantsPreview = request()->boolean('preview');
        $requestedContractId = request()->query('contract_id');
        $isEducatorTesting = $canManage && ! $wantsPreview && (session('portal_mode_' . $user->id) === 'student' || request()->query('test_as_student') == '1');

        // Se for professor/gestor/educador solicitando preview explícito
        if ($wantsPreview || ($canManage && ! $requestedContractId && ! $isEducatorTesting)) {
            $this->isPreview = true;
            $this->currentStepIndex = 0;
            $this->initializeCurrentStepState();

            return;
        }

        // Modo Aluno: localiza contrato do aluno
        $contract = null;
        if ($requestedContractId) {
            $contract = Contract::find((int) $requestedContractId);
        } elseif ($user->person_id) {
            $contract = Contract::query()
                ->where('student_id', $user->person_id)
                ->where('status', 'active')
                ->first();
        }

        // Educador testando como aluno (sem necessidade de contrato comercial no BD)
        if (! $contract && $isEducatorTesting) {
            $this->isEducatorStudentTest = true;
            $this->isPreview = false;
            $this->isLocked = false;
            $this->currentStepIndex = 0;
            $this->initializeCurrentStepState();

            return;
        }

        // Se não encontrou contrato e tem permissão de gestor, ativa modo preview
        if (! $contract && $canManage) {
            $this->isPreview = true;
            $this->currentStepIndex = 0;
            $this->initializeCurrentStepState();

            return;
        }

        if (! $contract) {
            $this->isLocked = true;
            $this->lockReason = 'no_contract';
            $this->lockMessage = 'Nenhuma matrícula ativa foi encontrada para acessar este conteúdo.';

            return;
        }

        $this->contractId = $contract->id;

        // Verifica travas pedagógicas (Teto diário de 1 aula e pré-requisitos lineares)
        $accessCheck = $progressionService->canAccessLesson($contract, $this->lesson);
        if (! $accessCheck['can_access']) {
            $this->isLocked = true;
            $this->lockReason = $accessCheck['reason'];
            $this->lockMessage = $accessCheck['message'] ?? 'Acesso à aula bloqueado pelo método de progressão.';

            return;
        }

        // Inicia ou retoma o progresso
        $progress = $progressionService->startLesson($contract, $this->lesson);
        $this->progressId = $progress->id;
        $this->timeSpentSeconds = $progress->time_spent_seconds ?? 0;
        $this->helpRequested = (bool) ($progress->help_requested ?? false);
        $this->checkEducatorMessage();

        if ($progress->status === 'completed') {
            $this->finalScore = (float) $progress->score;
            $this->passed = true;

            if (request()->boolean('review')) {
                $this->isReviewMode = true;
                $this->isLessonCompleted = false;
                $this->currentStepIndex = 0;
            } else {
                $this->isLessonCompleted = true;
                $this->currentStepIndex = max(0, $this->steps->count() - 1);
            }

            return;
        }

        // Posiciona no passo salvo
        if ($progress->current_step_id) {
            $index = $this->steps->search(fn ($s) => $s->id === $progress->current_step_id);
            $this->currentStepIndex = $index !== false ? (int) $index : 0;
        }

        // Regra pedagógica: Aluno deve cumprir 15 minutos obrigatórios de digitação pré-aula
        $checkWarmup = ! app()->runningUnitTests() || request()->boolean('check_typing_warmup');
        if ($checkWarmup && ! $canManage && ! $this->isPreview && $progress->status !== 'completed' && ! request()->boolean('review')) {
            if (! $progressionService->hasCompletedTypingWarmup($contract, $this->lesson)) {
                $this->needsTypingWarmup = true;
            }
        }

        $this->initializeCurrentStepState();
    }

    public function initializeCurrentStepState(bool $forceNewQuizDraw = false): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return;
        }

        $this->currentSubStepIndex = 0;

        if ($step->step_type === 'quiz') {
            $pool = $step->content['question_pool'] ?? [];
            if (empty($pool) || ! is_array($pool)) {
                $pool = [[
                    'id' => 'q1',
                    'question' => $step->content['question'] ?? '',
                    'options' => $step->content['options'] ?? [],
                    'explanation_on_error' => $step->content['explanation_on_error'] ?? null,
                ]];
            }

            $randomize = (bool) ($step->content['randomize'] ?? $step->content['randomize_pool'] ?? false);
            $shuffleOptions = (bool) ($step->content['shuffle_options'] ?? false);
            $questionsToDraw = (int) ($step->content['questions_to_draw'] ?? count($pool));
            $questionsToDraw = max(1, min(count($pool), $questionsToDraw));

            if ($randomize) {
                shuffle($pool);
            }
            if ($questionsToDraw < count($pool)) {
                $pool = array_slice($pool, 0, $questionsToDraw);
            }

            if ($shuffleOptions) {
                foreach ($pool as &$questionItem) {
                    if (! empty($questionItem['options']) && is_array($questionItem['options'])) {
                        $opts = $questionItem['options'];
                        shuffle($opts);
                        $questionItem['options'] = array_values($opts);
                    }
                }
                unset($questionItem);
            }

            $this->activeQuizQuestions = array_values($pool);
            $this->activeQuizQuestionIndex = 0;
            $this->quizPoolAnswers = [];
        }

        if ($step->step_type === 'assignment' && $this->progressId) {
            $existing = LmsStudentAssignment::query()
                ->where('progress_id', $this->progressId)
                ->where('step_id', $step->id)
                ->latest('id')
                ->first();

            if ($existing) {
                $this->submittedAssignmentId = $existing->id;
                $this->assignmentNotes = (string) ($existing->student_notes ?? '');
                if ($existing->status === 'approved' || ! ($step->content['require_educator_approval'] ?? false)) {
                    $this->lastAnswerCorrect = true;
                }
            }
        }
    }

    public function getCurrentStepProperty(): ?LmsLessonStep
    {
        return $this->steps->get($this->currentStepIndex);
    }

    /**
     * Retorna a questão ativa do Quiz (seja de questão única ou do banco sorteado).
     *
     * @return array<string, mixed>|null
     */
    public function getCurrentQuizQuestionProperty(): ?array
    {
        $step = $this->getCurrentStepProperty();
        if (! $step || $step->step_type !== 'quiz') {
            return null;
        }

        if (empty($this->activeQuizQuestions)) {
            $this->initializeCurrentStepState();
        }

        return $this->activeQuizQuestions[$this->activeQuizQuestionIndex] ?? [
            'id' => 'q1',
            'question' => $step->content['question'] ?? '',
            'options' => $step->content['options'] ?? [],
            'explanation_on_error' => $step->content['explanation_on_error'] ?? null,
        ];
    }

    /**
     * Retorna os dados normalizados do passo ou sub-passo atual do Simulador Guiado.
     *
     * @return array<string, mixed>
     */
    public function getActiveHotspotStepDataProperty(): array
    {
        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return [
                'is_multi_step' => false,
                'current_index' => 0,
                'total_sub_steps' => 1,
                'instruction' => '',
                'screen_image' => '',
                'explanation_on_error' => null,
                'hotspot' => null,
            ];
        }

        $subSteps = $step->content['sub_steps'] ?? [];
        if (! empty($subSteps) && is_array($subSteps)) {
            $subSteps = array_values($subSteps);
            $idx = max(0, min($this->currentSubStepIndex, count($subSteps) - 1));
            $currentSub = $subSteps[$idx];

            return [
                'is_multi_step' => true,
                'current_index' => $idx,
                'total_sub_steps' => count($subSteps),
                'instruction' => $currentSub['instruction'] ?? ($step->content['instruction'] ?? ''),
                'screen_image' => $currentSub['screen_image'] ?? ($step->content['screen_image'] ?? ''),
                'explanation_on_error' => $currentSub['explanation_on_error'] ?? ($step->content['explanation_on_error'] ?? null),
                'hotspot' => $currentSub['hotspot'] ?? ($step->content['hotspot'] ?? null),
            ];
        }

        return [
            'is_multi_step' => false,
            'current_index' => 0,
            'total_sub_steps' => 1,
            'instruction' => $step->content['instruction'] ?? '',
            'screen_image' => $step->content['screen_image'] ?? '',
            'explanation_on_error' => $step->content['explanation_on_error'] ?? null,
            'hotspot' => $step->content['hotspot'] ?? null,
        ];
    }

    public function selectOption(string $optionId): void
    {
        $this->selectedOptionId = $optionId;
    }

    public function submitQuizAnswer(LmsProgressionService $progressionService): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step || $this->selectedOptionId === null) {
            return;
        }

        if (empty($this->activeQuizQuestions)) {
            $this->initializeCurrentStepState();
        }

        $currentQuestion = $this->getCurrentQuizQuestionProperty();
        $options = $currentQuestion['options'] ?? ($step->content['options'] ?? []);
        $isCorrect = false;

        foreach ($options as $opt) {
            if (($opt['id'] ?? '') === $this->selectedOptionId && ! empty($opt['is_correct'])) {
                $isCorrect = true;
                break;
            }
        }

        $totalQuestions = count($this->activeQuizQuestions);

        // Fluxo de Banco de Questões Multi-Perguntas (Anti-Cola)
        if ($totalQuestions > 1) {
            $this->quizPoolAnswers[$this->activeQuizQuestionIndex] = [
                'question_id' => $currentQuestion['id'] ?? ('q_' . ($this->activeQuizQuestionIndex + 1)),
                'selected' => $this->selectedOptionId,
                'is_correct' => $isCorrect,
            ];

            // Se ainda há próximas perguntas sorteadas no quiz atual, avança para a próxima pergunta
            if ($this->activeQuizQuestionIndex < $totalQuestions - 1) {
                $this->activeQuizQuestionIndex++;
                $this->selectedOptionId = null;
                $this->feedbackMessage = '';
                $this->lastAnswerCorrect = null;

                return;
            }

            // Última pergunta respondida: consolida nota do lote sorteado
            $correctCount = collect($this->quizPoolAnswers)->where('is_correct', true)->count();
            $poolScore = round(($correctCount / $totalQuestions) * 100, 2);
            $minPassing = (float) ($this->lesson->min_passing_score ?? 70.0);
            $poolPassed = $poolScore >= $minPassing;

            if ($this->isPreview || ($this->isEducatorStudentTest && ! $this->progressId)) {
                $this->lastAnswerCorrect = $poolPassed;
                $this->feedbackMessage = $poolPassed
                    ? "Excelente! Você acertou {$correctCount} de {$totalQuestions} questões ({$poolScore}%)."
                    : "Você acertou {$correctCount} de {$totalQuestions} questões ({$poolScore}%). Nota mínima exigida: {$minPassing}%.";
                if (! $poolPassed) {
                    $this->attemptNumber++;
                } elseif ($this->isEducatorStudentTest && $this->currentStepIndex === $this->steps->count() - 1) {
                    $this->isLessonCompleted = true;
                    $this->finalScore = (float) $poolScore;
                    $this->passed = true;
                    $this->recordCompletionIfEducatorTest();
                    $this->checkAndTriggerCelebration();
                }

                return;
            }

            if (! $this->progressId) {
                return;
            }

            $progress = LmsStudentLessonProgress::find($this->progressId);
            if (! $progress) {
                return;
            }

            $attemptData = [
                'custom_score' => $poolScore,
                'drawn_questions' => array_column($this->activeQuizQuestions, 'id'),
                'answers' => $this->quizPoolAnswers,
            ];

            $result = $progressionService->submitStepAttempt(
                $progress,
                $step,
                $poolPassed,
                json_encode($this->quizPoolAnswers),
                $attemptData
            );

            if ($poolPassed) {
                $this->lastAnswerCorrect = true;
                $this->feedbackMessage = "Excelente! Você acertou {$correctCount} de {$totalQuestions} questões ({$poolScore}%).";

                if (! empty($result['lesson_completed'])) {
                    $this->isLessonCompleted = true;
                    $this->finalScore = (float) $result['score'];
                    $this->passed = (bool) $result['passed'];
                    $this->checkAndTriggerCelebration();
                }
            } else {
                $this->lastAnswerCorrect = false;
                $this->feedbackMessage = "Você acertou {$correctCount} de {$totalQuestions} questões ({$poolScore}%). A nota mínima é {$minPassing}%. Clique em tentar novamente para realizar uma nova bateria sorteada!";
                $this->attemptNumber++;
            }

            return;
        }

        // Fluxo Clássico (1 pergunta no passo)
        if ($this->isPreview || ($this->isEducatorStudentTest && ! $this->progressId)) {
            if ($isCorrect) {
                $this->lastAnswerCorrect = true;
                $this->feedbackMessage = 'Excelente! Resposta correta demonstrando domínio do conceito.';

                if ($this->isEducatorStudentTest && $this->currentStepIndex === $this->steps->count() - 1) {
                    $this->isLessonCompleted = true;
                    $this->finalScore = 100.0;
                    $this->passed = true;
                    $this->recordCompletionIfEducatorTest();
                    $this->checkAndTriggerCelebration();
                }
            } else {
                $this->lastAnswerCorrect = false;
                $this->feedbackMessage = $currentQuestion['explanation_on_error']
                    ?? $step->content['explanation_on_error']
                    ?? 'Atenção aos detalhes do conceito apresentado. Tente novamente para fixar!';
                $this->attemptNumber++;
            }

            return;
        }

        // Modo Aluno com auditoria pedagógica inaciana
        if (! $this->progressId) {
            return;
        }

        $progress = LmsStudentLessonProgress::find($this->progressId);
        if (! $progress) {
            return;
        }

        $result = $progressionService->submitStepAttempt($progress, $step, $isCorrect, $this->selectedOptionId);

        if ($isCorrect) {
            $this->lastAnswerCorrect = true;
            $this->feedbackMessage = $result['message'] ?? 'Excelente! Passo concluído com sucesso.';

            if (! empty($result['lesson_completed'])) {
                $this->isLessonCompleted = true;
                $this->finalScore = (float) $result['score'];
                $this->passed = (bool) $result['passed'];
                $this->checkAndTriggerCelebration();
            }
        } else {
            $this->lastAnswerCorrect = false;
            $this->feedbackMessage = $currentQuestion['explanation_on_error']
                ?? $result['feedback']
                ?? 'Resposta incorreta. Revise o conceito e tente novamente.';
            $this->attemptNumber++;
        }
    }

    protected function handleHotspotSubStepSuccess(
        LmsLessonStep $step,
        string $answerLog,
        string $successMessage,
        LmsProgressionService $progressionService
    ): void {
        $activeData = $this->getActiveHotspotStepDataProperty();

        if ($activeData['is_multi_step'] && $this->currentSubStepIndex < $activeData['total_sub_steps'] - 1) {
            $this->currentSubStepIndex++;
            $this->hotspotHit = true;
            $this->hotspotMistakeCount = 0;
            $this->typedInput = '';
            $this->simulationHotspotClicked = false;
            $this->lastAnswerCorrect = null;
            $nextNum = $this->currentSubStepIndex + 1;
            $this->feedbackMessage = "{$successMessage} Avançando para a etapa {$nextNum} de {$activeData['total_sub_steps']}!";

            return;
        }

        $this->hotspotHit = true;
        $this->simulationHotspotClicked = true;
        $this->lastAnswerCorrect = true;
        $this->feedbackMessage = $successMessage;

        if (! $this->isPreview && $this->progressId) {
            $progress = LmsStudentLessonProgress::find($this->progressId);
            if ($progress) {
                $result = $progressionService->submitStepAttempt($progress, $step, true, $answerLog);
                if (! empty($result['lesson_completed'])) {
                    $this->isLessonCompleted = true;
                    $this->finalScore = (float) $result['score'];
                    $this->passed = (bool) $result['passed'];
                    $this->checkAndTriggerCelebration();
                }
            }
        } elseif ($this->isEducatorStudentTest && ! $this->progressId) {
            if ($this->currentStepIndex === $this->steps->count() - 1) {
                $this->isLessonCompleted = true;
                $this->finalScore = 100.0;
                $this->passed = true;
                $this->recordCompletionIfEducatorTest();
                $this->checkAndTriggerCelebration();
            }
        }

        // Auto-avanço inteligente após acertar no simulador (sem necessidade de clique manual)
        if (! $this->isLessonCompleted) {
            $this->dispatch('hotspot-auto-advance');
        }
    }

    public function triggerSimulationHotspot(bool $isCorrect, LmsProgressionService $progressionService): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return;
        }

        $activeData = $this->getActiveHotspotStepDataProperty();

        if ($isCorrect) {
            $this->handleHotspotSubStepSuccess(
                $step,
                'hotspot_success',
                'Comando executado com precisão no simulador! Você dominou o procedimento prático.',
                $progressionService
            );
        } else {
            $this->lastAnswerCorrect = false;
            $this->feedbackMessage = $activeData['explanation_on_error']
                ?? 'Atenção aos detalhes da interface. Clique exatamente na ferramenta indicada pelo roteiro.';

            if (! $this->isPreview && $this->progressId) {
                $progress = LmsStudentLessonProgress::find($this->progressId);
                if ($progress) {
                    $progressionService->submitStepAttempt($progress, $step, false, 'hotspot_miss');
                }
            }
        }
    }

    public function clickHotspotCoordinates(float $clickX, float $clickY, LmsProgressionService $progressionService): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return;
        }

        $this->lastClickX = round($clickX, 2);
        $this->lastClickY = round($clickY, 2);

        $activeData = $this->getActiveHotspotStepDataProperty();
        $hotspot = $activeData['hotspot'] ?? null;
        if (! $hotspot || ! isset($hotspot['x'], $hotspot['y'])) {
            $this->triggerSimulationHotspot(true, $progressionService);

            return;
        }

        $targetX = (float) $hotspot['x'];
        $targetY = (float) $hotspot['y'];
        $radius = (float) ($hotspot['radius'] ?? 6.0);

        // Distância Euclidiana em %
        $distance = sqrt(pow($clickX - $targetX, 2) + pow($clickY - $targetY, 2));

        if ($distance <= $radius) {
            $label = ! empty($hotspot['label']) ? '"' . $hotspot['label'] . '"' : 'o alvo correto';
            $this->handleHotspotSubStepSuccess(
                $step,
                "click_{$this->lastClickX}_{$this->lastClickY}",
                "Excelente! Você localizou e acionou com precisão {$label}.",
                $progressionService
            );
        } else {
            $this->hotspotHit = false;
            $this->lastAnswerCorrect = false;
            $this->hotspotMistakeCount++;
            $this->feedbackMessage = $activeData['explanation_on_error']
                ?? 'Atenção aos detalhes da interface. Clique exatamente na ferramenta indicada pelo roteiro.';

            if (! $this->isPreview && $this->progressId) {
                $progress = LmsStudentLessonProgress::find($this->progressId);
                if ($progress) {
                    $progressionService->submitStepAttempt($progress, $step, false, "click_{$this->lastClickX}_{$this->lastClickY}");
                }
            }
        }
    }

    public function dragHotspotCoordinates(float $startX, float $startY, float $endX, float $endY, LmsProgressionService $progressionService): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return;
        }

        $this->lastDragStartX = round($startX, 2);
        $this->lastDragStartY = round($startY, 2);
        $this->lastDragEndX = round($endX, 2);
        $this->lastDragEndY = round($endY, 2);
        $this->lastClickX = $this->lastDragStartX;
        $this->lastClickY = $this->lastDragStartY;

        $activeData = $this->getActiveHotspotStepDataProperty();
        $hotspot = $activeData['hotspot'] ?? null;
        if (! $hotspot || ! isset($hotspot['x'], $hotspot['y'])) {
            $this->triggerSimulationHotspot(true, $progressionService);

            return;
        }

        $targetStartX = (float) $hotspot['x'];
        $targetStartY = (float) $hotspot['y'];
        $targetEndX = isset($hotspot['end_x']) ? (float) $hotspot['end_x'] : $targetStartX;
        $targetEndY = isset($hotspot['end_y']) ? (float) $hotspot['end_y'] : $targetStartY;
        $radius = (float) ($hotspot['radius'] ?? 7.0);
        $direction = $hotspot['direction'] ?? 'left_to_right';

        // 1. Proximidade do ponto inicial
        $startDist = sqrt(pow($startX - $targetStartX, 2) + pow($startY - $targetStartY, 2));

        // 2. Proximidade do ponto final
        $endDist = sqrt(pow($endX - $targetEndX, 2) + pow($endY - $targetEndY, 2));

        // 3. Verificação de sentido/direção
        $directionCorrect = true;
        if ($direction === 'left_to_right' && $endX < $startX) {
            $directionCorrect = false;
        } elseif ($direction === 'right_to_left' && $endX > $startX) {
            $directionCorrect = false;
        }

        if ($startDist <= $radius && $endDist <= $radius && $directionCorrect) {
            $label = ! empty($hotspot['label']) ? '"' . $hotspot['label'] . '"' : 'o texto solicitado';
            $this->handleHotspotSubStepSuccess(
                $step,
                "drag_{$startX}_{$startY}_to_{$endX}_{$endY}",
                "Excelente! Você selecionou {$label} com precisão.",
                $progressionService
            );
        } else {
            $this->hotspotHit = false;
            $this->lastAnswerCorrect = false;
            $this->hotspotMistakeCount++;

            if (! $directionCorrect) {
                $this->feedbackMessage = 'Atenção ao sentido: selecione o texto da esquerda para a direita.';
            } else {
                $this->feedbackMessage = $activeData['explanation_on_error']
                    ?? 'Atenção aos limites do texto. Inicie no início da palavra e arraste até o fim com o mouse pressionado.';
            }

            if (! $this->isPreview && $this->progressId) {
                $progress = LmsStudentLessonProgress::find($this->progressId);
                if ($progress) {
                    $progressionService->submitStepAttempt($progress, $step, false, "drag_{$startX}_{$startY}_to_{$endX}_{$endY}");
                }
            }
        }
    }

    public function doubleClickHotspotCoordinates(float $clickX, float $clickY, LmsProgressionService $progressionService): void
    {
        $activeData = $this->getActiveHotspotStepDataProperty();
        $expectedType = $activeData['hotspot']['interaction_type'] ?? 'click';

        if ($expectedType !== 'double_click' && $expectedType !== 'click') {
            $this->clickHotspotCoordinates($clickX, $clickY, $progressionService);

            return;
        }

        $this->clickHotspotCoordinates($clickX, $clickY, $progressionService);
    }

    public function rightClickHotspotCoordinates(float $clickX, float $clickY, LmsProgressionService $progressionService): void
    {
        $activeData = $this->getActiveHotspotStepDataProperty();
        $expectedType = $activeData['hotspot']['interaction_type'] ?? 'click';

        if ($expectedType !== 'right_click') {
            $this->hotspotHit = false;
            $this->lastAnswerCorrect = false;
            $this->feedbackMessage = 'Esta ação não requer o botão direito do mouse. Utilize o botão esquerdo.';

            return;
        }

        $this->clickHotspotCoordinates($clickX, $clickY, $progressionService);
    }

    public function submitTypedText(string $input, LmsProgressionService $progressionService): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return;
        }

        $this->typedInput = $input;
        $activeData = $this->getActiveHotspotStepDataProperty();
        $hotspot = $activeData['hotspot'] ?? null;
        $expected = (string) ($hotspot['expected_text'] ?? '');
        $caseSensitive = (bool) ($hotspot['case_sensitive'] ?? false);
        $trimWhitespace = (bool) ($hotspot['trim_whitespace'] ?? true);

        $normalizedInput = $trimWhitespace ? trim($input) : $input;
        $normalizedExpected = $trimWhitespace ? trim($expected) : $expected;

        if (! $caseSensitive) {
            $isMatch = (mb_strtolower($normalizedInput, 'UTF-8') === mb_strtolower($normalizedExpected, 'UTF-8'));
        } else {
            $isMatch = ($normalizedInput === $normalizedExpected);
        }

        if ($isMatch) {
            $label = ! empty($hotspot['label']) ? '"' . $hotspot['label'] . '"' : 'o texto solicitado';
            $this->handleHotspotSubStepSuccess(
                $step,
                "typed: {$input}",
                "Excelente! Você digitou {$label} com perfeição.",
                $progressionService
            );
        } else {
            $this->hotspotHit = false;
            $this->lastAnswerCorrect = false;
            $this->hotspotMistakeCount++;

            $this->feedbackMessage = $activeData['explanation_on_error']
                ?? "O texto digitado não confere. Verifique a ortografia ou pontuação e tente novamente.";

            if (! $this->isPreview && $this->progressId) {
                $progress = LmsStudentLessonProgress::find($this->progressId);
                if ($progress) {
                    $progressionService->submitStepAttempt($progress, $step, false, "typed_fail: {$input}");
                }
            }
        }
    }

    public function submitAssignment(LmsProgressionService $progressionService): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step || $step->step_type !== 'assignment') {
            return;
        }

        $this->validate([
            'assignmentFile' => 'required|file|max:20480',
        ], [
            'assignmentFile.required' => 'Selecione o arquivo do trabalho prático para enviar.',
            'assignmentFile.max' => 'O arquivo não pode ultrapassar 20MB.',
        ]);

        $originalName = $this->assignmentFile->getClientOriginalName();
        $folder = 'lms/assignments/' . ($this->contractId ?? 'preview') . '/' . $this->lesson->id;
        $storedPath = $this->assignmentFile->store($folder, 'public');

        if ($this->isPreview) {
            $this->lastAnswerCorrect = true;
            $this->feedbackMessage = "Modo Preview: Arquivo \"{$originalName}\" recebido com sucesso!";

            return;
        }

        if (! $this->progressId || ! $this->contractId) {
            return;
        }

        $progress = LmsStudentLessonProgress::find($this->progressId);
        $contract = Contract::find($this->contractId);
        if (! $progress || ! $contract) {
            return;
        }

        $assignment = LmsStudentAssignment::create([
            'progress_id' => $progress->id,
            'step_id' => $step->id,
            'contract_id' => $contract->id,
            'student_id' => $contract->student_id,
            'file_path' => (string) $storedPath,
            'file_name' => $originalName,
            'student_notes' => $this->assignmentNotes ?: null,
            'status' => 'pending',
        ]);

        $this->submittedAssignmentId = $assignment->id;
        $this->lastAnswerCorrect = true;
        $this->feedbackMessage = "Trabalho prático \"{$originalName}\" enviado com sucesso para avaliação do educador!";

        $result = $progressionService->submitStepAttempt(
            $progress,
            $step,
            true,
            "assignment:{$assignment->id}:{$originalName}",
            ['assignment_id' => $assignment->id, 'file_name' => $originalName]
        );

        if (! empty($result['lesson_completed'])) {
            $this->isLessonCompleted = true;
            $this->finalScore = (float) $result['score'];
            $this->passed = (bool) $result['passed'];
            $this->checkAndTriggerCelebration();
        }
    }

    public function getSubmittedAssignmentProperty(): ?LmsStudentAssignment
    {
        if (! $this->submittedAssignmentId) {
            return null;
        }

        return LmsStudentAssignment::find($this->submittedAssignmentId);
    }

    public function reportFocusLoss(): void
    {
        $this->isInFocus = false;

        if (! $this->isPreview && $this->progressId) {
            LmsStudentLessonProgress::query()
                ->where('id', $this->progressId)
                ->update([
                    'is_in_focus' => false,
                    'focus_lost_at' => now(),
                    'last_heartbeat_at' => now(),
                ]);
        }
    }

    public function reportFocusGain(int $awaySeconds = 0): void
    {
        $this->isInFocus = true;

        if (! $this->isPreview && $this->progressId) {
            $increment = max(0, min(3600, $awaySeconds));
            LmsStudentLessonProgress::query()
                ->where('id', $this->progressId)
                ->update([
                    'is_in_focus' => true,
                    'focus_lost_at' => null,
                    'total_focus_lost_seconds' => \Illuminate\Support\Facades\DB::raw("total_focus_lost_seconds + {$increment}"),
                    'last_heartbeat_at' => now(),
                ]);
        }
    }

    public function recordCompletionIfEducatorTest(): void
    {
        if ($this->isEducatorStudentTest) {
            $user = Auth::user();
            if ($user) {
                $completed = session('educator_test_completed_lessons_' . $user->id, []);
                if (! in_array($this->lesson->id, $completed, true)) {
                    $completed[] = $this->lesson->id;
                    session(['educator_test_completed_lessons_' . $user->id => $completed]);
                }
            }
        }
    }

    public function checkAndTriggerCelebration(): void
    {
        if (! $this->passed || $this->isPreview) {
            return;
        }

        $this->recordCompletionIfEducatorTest();

        // Verifica se concluiu todas as aulas do módulo atual
        $moduleCompleted = false;
        if ($this->contractId && $this->lesson->module) {
            $moduleLessonIds = $this->lesson->module->lessons()
                ->where('is_active', true)
                ->pluck('id');

            if ($moduleLessonIds->isNotEmpty()) {
                $completedCount = LmsStudentLessonProgress::query()
                    ->where('contract_id', $this->contractId)
                    ->whereIn('lesson_id', $moduleLessonIds)
                    ->where('status', 'completed')
                    ->count();

                $moduleCompleted = ($completedCount >= $moduleLessonIds->count());
            }
        } elseif ($this->isEducatorStudentTest && $this->lesson->module) {
            $moduleLessonIds = $this->lesson->module->lessons()
                ->where('is_active', true)
                ->pluck('id');

            if ($moduleLessonIds->isNotEmpty()) {
                $user = Auth::user();
                $completed = session('educator_test_completed_lessons_' . ($user?->id ?? 0), []);
                $completedCount = count(array_intersect($moduleLessonIds->all(), $completed));
                $moduleCompleted = ($completedCount >= $moduleLessonIds->count());
            }
        }

        if ($moduleCompleted) {
            $this->showCelebrationModal = true;
            $this->celebrationType = 'module_completed';
            $this->celebrationTitle = '🏆 Módulo Concluído com Sucesso!';
            $this->celebrationMessage = "Parabéns! Você finalizou todas as aulas do módulo \"{$this->lesson->module->title}\" com nota {$this->finalScore}%!";

            return;
        }

        if (($this->finalScore ?? 0) >= 100.0) {
            $this->showCelebrationModal = true;
            $this->celebrationType = 'perfect_score';
            $this->celebrationTitle = '🌟 Nota 100%! Desempenho Perfeito!';
            $this->celebrationMessage = "Incrível! Você concluiu a aula \"{$this->lesson->title}\" gabaritando todas as atividades com 100% de aproveitamento!";
        }
    }

    public function dismissCelebration(): void
    {
        $this->showCelebrationModal = false;
    }

    public function getStepRequiredSecondsProperty(): int
    {
        if (app()->runningUnitTests() || $this->isReviewMode || $this->isPreview) {
            return 0;
        }

        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return 0;
        }

        // 1. Tempo customizado explicitamente configurado no passo (ex: duração de vídeo, áudio ou leitura)
        $customMinStay = (int) ($step->content['min_stay_seconds'] ?? 0);
        if ($customMinStay > 0) {
            return $customMinStay;
        }

        // 2. Se houver mídia de vídeo ou áudio sem tempo definido, exige permanência mínima de 30s
        $mediaType = $step->content['media_type'] ?? 'none';
        if (in_array($mediaType, ['video', 'audio'], true)) {
            return 30;
        }

        if ($step->step_type === 'theory') {
            $words = str_word_count(strip_tags($step->content['body'] ?? ''));

            return max(15, min(45, (int) round($words / 3)));
        }

        if ($step->step_type === 'challenge') {
            return 15;
        }

        return 0;
    }

    public function getStepDisplayNumberProperty(): string
    {
        if (! isset($this->steps) || $this->steps->isEmpty()) {
            return (string) ($this->currentStepIndex + 1);
        }

        $mainIndex = 0;
        $subIndex = 0;
        $lastMainTitle = null;
        $targetNumber = (string) ($this->currentStepIndex + 1);

        foreach ($this->steps as $idx => $step) {
            $isSub = ! empty($step->content['is_substep']);
            $currentTitle = trim((string) $step->title);
            $isSameTitle = ($lastMainTitle !== null && strcasecmp($currentTitle, trim((string) $lastMainTitle)) === 0 && $mainIndex > 0);

            if ($isSub || $isSameTitle) {
                $subIndex++;
                $num = "{$mainIndex}.{$subIndex}";
            } else {
                $mainIndex++;
                $subIndex = 0;
                $lastMainTitle = $currentTitle;
                $num = (string) $mainIndex;
            }

            if ($idx === $this->currentStepIndex) {
                $targetNumber = $num;
                break;
            }
        }

        return $targetNumber;
    }

    public function completeTheoryStep(LmsProgressionService $progressionService): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return;
        }

        // Validação de tempo de permanência mínimo contra avanços precipitados
        $elapsed = now()->timestamp - $this->stepLoadedAt;
        if (! $this->isPreview && ! $this->isReviewMode && $elapsed < $this->getStepRequiredSecondsProperty()) {
            return;
        }

        if (! $this->isPreview && ! $this->isReviewMode && $this->progressId) {
            $progress = LmsStudentLessonProgress::find($this->progressId);
            if ($progress) {
                $progressionService->submitStepAttempt($progress, $step, true, 'theory_read');
            }
        }

        $this->nextStep();
    }

    public function completeChallenge(LmsProgressionService $progressionService): void
    {
        $step = $this->getCurrentStepProperty();
        if (! $step) {
            return;
        }

        $elapsed = now()->timestamp - $this->stepLoadedAt;
        if (! $this->isPreview && ! $this->isReviewMode && $elapsed < $this->getStepRequiredSecondsProperty()) {
            return;
        }

        $this->challengeAccepted = true;
        $this->lastAnswerCorrect = true;
        $this->feedbackMessage = 'Desafio concluído com êxito! Você demonstrou autonomia e destreza prática.';

        if (! $this->isPreview && ! $this->isReviewMode && $this->progressId) {
            $progress = LmsStudentLessonProgress::find($this->progressId);
            if ($progress) {
                $result = $progressionService->submitStepAttempt($progress, $step, true, 'challenge_completed');
                if (! empty($result['lesson_completed'])) {
                    $this->isLessonCompleted = true;
                    $this->finalScore = (float) $result['score'];
                    $this->passed = (bool) $result['passed'];
                    $this->checkAndTriggerCelebration();
                }
            }
        }
    }

    public function nextStep(): void
    {
        // Se houve erro pendente em quiz ou simulação, não avança sem acertar (Repetitio inaciana)
        if ($this->lastAnswerCorrect === false) {
            return;
        }

        $this->resetStepState();
        $this->stepLoadedAt = now()->timestamp;

        if ($this->currentStepIndex < $this->steps->count() - 1) {
            $this->currentStepIndex++;
            $this->initializeCurrentStepState();
        } else {
            if ($this->isReviewMode) {
                $this->isLessonCompleted = true;
                $this->isReviewMode = false;
            } else {
                $this->isLessonCompleted = true;
                if ($this->finalScore === null) {
                    $this->finalScore = 100.00;
                    $this->passed = true;
                }
                $this->recordCompletionIfEducatorTest();
                $this->checkAndTriggerCelebration();
            }
        }
    }

    public function prevStep(): void
    {
        if (($this->isPreview || $this->isReviewMode) && $this->currentStepIndex > 0) {
            $this->resetStepState();
            $this->currentStepIndex--;
            $this->stepLoadedAt = now()->timestamp;
            $this->initializeCurrentStepState();
        }
    }

    public function goToStep(int $index): void
    {
        if (($this->isPreview || $this->isReviewMode) && isset($this->steps[$index])) {
            $this->resetStepState();
            $this->currentStepIndex = $index;
            $this->stepLoadedAt = now()->timestamp;
            $this->initializeCurrentStepState();
        }
    }

    public function enterReviewMode(): void
    {
        $this->isReviewMode = true;
        $this->isLessonCompleted = false;
        $this->currentStepIndex = 0;
        $this->stepLoadedAt = now()->timestamp;
        $this->resetStepState();
        $this->initializeCurrentStepState();
    }

    public function exitReviewMode(): void
    {
        $this->isReviewMode = false;
        $this->isLessonCompleted = true;
    }

    public function educatorUnlockLesson(LmsProgressionService $progressionService): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('View:LmsLesson') && ! $user->can('View:LmsStudio') && ! $user->can('page_LmsStudio'))) {
            $this->showEducatorAuthModal = true;

            return;
        }

        $contract = Contract::find($this->contractId);
        if ($contract) {
            $progressionService->unlockLessonForContract($contract, $this->lesson, $user, 'Liberação direta pelo educador autenticado.');
            $progressionService->unlockDailyLimitForContract($contract, $user, 'Liberação do teto diário pelo educador.');

            $this->isLocked = false;
            $this->lockReason = '';
            $this->lockMessage = '';
            $this->needsTypingWarmup = false;

            $progress = $progressionService->startLesson($contract, $this->lesson);
            $progress->update(['typing_warmup_completed_at' => now()]);
            $this->progressId = $progress->id;
            $this->currentStepIndex = 0;
            $this->stepLoadedAt = now()->timestamp;
            $this->initializeCurrentStepState();
        }
    }

    public function verifyAndUnlockWithCredentials(LmsProgressionService $progressionService): void
    {
        $this->educatorAuthError = '';

        $cleanIdentifier = trim($this->educatorEmail);
        $cleanDigits = preg_replace('/\D/', '', $cleanIdentifier);

        // Busca o educador por email, username da Ouro Moderno ou CPF
        $educatorUser = User::query()
            ->where('email', $cleanIdentifier)
            ->orWhereHas('person', function ($q) use ($cleanIdentifier, $cleanDigits) {
                if (! empty($cleanDigits)) {
                    $q->whereCpf($cleanDigits);
                }
                $q->orWhere('ouro_moderno_usuario', $cleanIdentifier);
            })
            ->first();

        if (! $educatorUser || ! Hash::check($this->educatorPassword, $educatorUser->password)) {
            $this->educatorAuthError = 'E-mail, usuário ou senha de educador incorretos.';

            return;
        }

        if (! $educatorUser->can('View:LmsLesson') && ! $educatorUser->can('View:LmsStudio') && ! $educatorUser->can('page_LmsStudio')) {
            $this->educatorAuthError = 'Este usuário não possui privilégios de educador pedagógico.';

            return;
        }

        $contract = Contract::find($this->contractId);
        if ($contract) {
            $progressionService->unlockLessonForContract($contract, $this->lesson, $educatorUser, 'Liberação presencial por credenciais do educador.');
            $progressionService->unlockDailyLimitForContract($contract, $educatorUser, 'Liberação presencial de teto diário.');

            $this->showEducatorAuthModal = false;
            $this->educatorEmail = '';
            $this->educatorPassword = '';
            $this->isLocked = false;
            $this->lockReason = '';
            $this->lockMessage = '';
            $this->needsTypingWarmup = false;

            $progress = $progressionService->startLesson($contract, $this->lesson);
            $progress->update(['typing_warmup_completed_at' => now()]);
            $this->progressId = $progress->id;
            $this->currentStepIndex = 0;
            $this->stepLoadedAt = now()->timestamp;
            $this->initializeCurrentStepState();
        }
    }

    public function resetAttemptForRepetitio(): void
    {
        $this->selectedOptionId = null;
        $this->lastAnswerCorrect = null;
        $this->feedbackMessage = '';
        $this->simulationHotspotClicked = false;
        $this->lastClickX = null;
        $this->lastClickY = null;
        $this->lastDragStartX = null;
        $this->lastDragStartY = null;
        $this->lastDragEndX = null;
        $this->lastDragEndY = null;
        $this->hotspotHit = null;
        $this->typedInput = '';

        $step = $this->getCurrentStepProperty();
        if ($step && $step->step_type === 'quiz' && count($this->activeQuizQuestions) > 1) {
            $this->initializeCurrentStepState(true);
        }
    }

    protected function resetStepState(): void
    {
        $this->selectedOptionId = null;
        $this->lastAnswerCorrect = null;
        $this->feedbackMessage = '';
        $this->attemptNumber = 1;
        $this->simulationHotspotClicked = false;
        $this->currentSubStepIndex = 0;
        $this->challengeAccepted = false;
        $this->assignmentFile = null;
        $this->assignmentNotes = '';
        $this->submittedAssignmentId = null;
        $this->lastClickX = null;
        $this->lastClickY = null;
        $this->lastDragStartX = null;
        $this->lastDragStartY = null;
        $this->lastDragEndX = null;
        $this->lastDragEndY = null;
        $this->hotspotHit = null;
        $this->hotspotMistakeCount = 0;
        $this->typedInput = '';
    }

    public function incrementTimeSpent(int $seconds = 10): void
    {
        $this->timeSpentSeconds += $seconds;

        if (! $this->isPreview && $this->progressId) {
            LmsStudentLessonProgress::query()
                ->where('id', $this->progressId)
                ->update([
                    'time_spent_seconds' => \Illuminate\Support\Facades\DB::raw("time_spent_seconds + {$seconds}"),
                    'last_heartbeat_at' => now(),
                    'exited_at' => null,
                ]);

            $this->checkEducatorMessage();
        }
    }

    public function checkEducatorMessage(): void
    {
        if (! $this->progressId) {
            return;
        }

        $progress = LmsStudentLessonProgress::find($this->progressId);
        if (! $progress) {
            return;
        }

        if ($progress->student_rating !== null && ! $this->lessonRatingSubmitted) {
            $this->lessonRating = (int) $progress->student_rating;
            $this->lessonRatingComment = (string) ($progress->student_rating_comment ?? '');
            $this->lessonRatingSubmitted = true;
        }

        if (! empty($progress->educator_message) && $progress->educator_message_read_at === null) {
            $this->activeEducatorMessage = (string) $progress->educator_message;
            $this->activeEducatorSender = (string) ($progress->educator_message_sender ?? 'Professor(a)');
        }
    }

    public function acknowledgeEducatorMessage(): void
    {
        if ($this->progressId) {
            LmsStudentLessonProgress::query()
                ->where('id', $this->progressId)
                ->update([
                    'educator_message_read_at' => now(),
                ]);
        }

        $this->activeEducatorMessage = null;
        $this->activeEducatorSender = null;
    }

    public function setLessonRating(int $stars): void
    {
        $this->lessonRating = max(1, min(5, $stars));
    }

    public function submitLessonRating(?int $stars = null): void
    {
        if ($stars !== null) {
            $this->lessonRating = max(1, min(5, $stars));
        }

        if ($this->lessonRating < 1) {
            $this->lessonRating = 5;
        }

        if ($this->progressId) {
            LmsStudentLessonProgress::query()
                ->where('id', $this->progressId)
                ->update([
                    'student_rating' => $this->lessonRating,
                    'student_rating_comment' => trim($this->lessonRatingComment) ?: null,
                    'student_rated_at' => now(),
                ]);
        }

        $this->lessonRatingSubmitted = true;
    }

    public function exitLesson(): void
    {
        if ($this->isPreview) {
            $this->redirectRoute('filament.studio.pages.lms-studio', [
                'course_id' => $this->lesson->module?->course_id,
            ]);

            return;
        }

        if (! $this->isPreview && $this->progressId) {
            $progress = LmsStudentLessonProgress::find($this->progressId);
            if ($progress) {
                $progress->update([
                    'exited_at' => now(),
                ]);

                \App\Models\StudentAccessLog::log(
                    'lesson_exit',
                    "Aluno saiu da aula '{$this->lesson->title}'",
                    $progress->contract,
                    $this->lesson->id,
                    $progress->contract?->student
                );
            }
        }

        $this->redirectRoute('student.portal');
    }

    public function openZoom(string $url): void
    {
        $this->zoomImageUrl = $url;
        $this->showZoomModal = true;
    }

    public function closeZoom(): void
    {
        $this->showZoomModal = false;
        $this->zoomImageUrl = '';
    }

    public function requestHelp(): void
    {
        if ($this->progressId) {
            LmsStudentLessonProgress::query()
                ->where('id', $this->progressId)
                ->update([
                    'help_requested' => true,
                    'help_requested_at' => now(),
                ]);
        }
        $this->helpRequested = true;
    }

    public function cancelHelpRequest(): void
    {
        if ($this->progressId) {
            LmsStudentLessonProgress::query()
                ->where('id', $this->progressId)
                ->update([
                    'help_requested' => false,
                    'help_requested_at' => null,
                ]);
        }
        $this->helpRequested = false;
    }

    public function markVideoCompleted(): void
    {
        $this->videoCompleted = true;
    }

    public function getEmbedVideoUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0&modestbranding=1';
        }

        if (preg_match('/vimeo\.com\/([0-9]+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        // Se for URL local ou caminho relativo (/storage/...)
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return asset($url);
        }

        return $url;
    }

    public function render(): View
    {
        return view('livewire.lms.lms-player');
    }
}
