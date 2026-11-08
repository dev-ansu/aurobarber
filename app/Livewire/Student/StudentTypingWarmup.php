<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Contract;
use App\Models\LmsLesson;
use App\Models\LmsStudentLessonProgress;
use App\Models\StudentAccessLog;
use App\Models\StudentTypingRecord;
use App\Models\TypingExercise;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StudentTypingWarmup extends Component
{
    public ?int $contractId = null;

    public ?int $lessonId = null;

    public ?int $selectedExerciseId = null;

    public string $activeTab = 'practice'; // practice, school_ranking, exercise_ranking, my_history

    public string $categoryFilter = 'all';

    public string $currentExerciseText = '';

    // Regra dos 15 minutos obrigatórios pré-aula
    public bool $isMandatory = false;

    public int $requiredSeconds = 900; // 15 minutos = 900 segundos

    public int $typingTimeSpentToday = 0;

    public int $remainingSeconds = 900;

    public bool $isWarmupCompleted = false;

    // Autorização do Educador para liberar aula antecipadamente
    public bool $showEducatorAuthModal = false;

    public string $educatorEmail = '';

    public string $educatorPassword = '';

    public string $educatorAuthError = '';

    public bool $isEducatorAuthorized = false;

    // Estado da Digitação Atual
    public bool $isFinished = false;

    public float $finalWpm = 0.0;

    public float $finalCpm = 0.0;

    public float $finalAccuracy = 0.0;

    public int $finalTimeSpent = 0;

    public int $stars = 1;

    public bool $passed = true;

    public function mount(
        $contractId = null,
        $lessonId = null,
        $exerciseId = null,
        $contract_id = null,
        $lesson_id = null,
        $exercise_id = null,
        $mandatory = null
    ): void {
        $user = Auth::user();
        if (! $user) {
            redirect()->route('student.login');

            return;
        }

        $this->lessonId = $lessonId ?? $lesson_id ?? (request()->query('lesson_id') ? (int) request()->query('lesson_id') : null);
        $reqContractId = $contractId ?? $contract_id ?? (request()->query('contract_id') ? (int) request()->query('contract_id') : null);
        $isStaff = $user->isStaff() || $user->isEducator();
        $portalMode = session('portal_mode_' . $user->id, session('portal_auth_type') === 'staff' ? 'educator' : 'student');

        // Localiza contrato ativo do aluno
        $contract = null;
        if ($isStaff && $portalMode === 'educator' && ! $reqContractId) {
            $contract = null;
        } elseif ($reqContractId) {
            $contract = Contract::find((int) $reqContractId);
        } elseif ($user->person_id) {
            $contract = Contract::query()
                ->where('student_id', $user->person_id)
                ->where('status', 'active')
                ->first();
        }

        if (! $contract && ! $isStaff) {
            session()->flash('errorMessage', 'Acesso não disponível. Sua matrícula não está com status ativo. Procure a secretaria da sua escola.');
            redirect()->route('student.login');

            return;
        }

        if ($contract) {
            $this->contractId = $contract->id;

            // Se for acesso de aluno (ou educador atuando como aluno em seu contrato), valida pendência financeira e horário
            $isStudentAccess = ! $isStaff || ($portalMode === 'student' && ! $user->hasRole('super_admin'));
            if ($isStudentAccess) {
                if ($contract->hasOverdueInstallments()) {
                    redirect()->route('student.portal');

                    return;
                }

                $scheduleCheck = app(\App\Services\LmsProgressionService::class)->checkStudentScheduleWindow($contract);
                if (! $scheduleCheck['is_allowed']) {
                    redirect()->route('student.portal');

                    return;
                }
            }
        }

        // Define obrigatoriedade dos 15 minutos
        $this->isMandatory = (bool) ($mandatory ?? request()->boolean('mandatory') ?? ($this->lessonId !== null));

        // Calcula tempo total de digitação acumulado no dia
        if ($this->contractId) {
            $this->typingTimeSpentToday = (int) StudentTypingRecord::query()
                ->where('contract_id', $this->contractId)
                ->whereDate('completed_at', today())
                ->sum('time_spent_seconds');

            // Verifica se a lição em questão já tem a flag de aquecimento concluído
            if ($this->lessonId) {
                $progress = LmsStudentLessonProgress::query()
                    ->where('contract_id', $this->contractId)
                    ->where('lesson_id', $this->lessonId)
                    ->first();

                if ($progress && $progress->typing_warmup_completed_at !== null) {
                    $this->isWarmupCompleted = true;
                }
            }

            if ($this->typingTimeSpentToday >= $this->requiredSeconds) {
                $this->isWarmupCompleted = true;
            }

            $this->remainingSeconds = max(0, $this->requiredSeconds - $this->typingTimeSpentToday);
        }

        // Determina o exercício inicial (próximo não concluído ou o selecionado se liberado)
        $exerciseParam = $exerciseId ?? $exercise_id ?? request()->query('exercise_id');
        if ($exerciseParam && in_array((int) $exerciseParam, $this->unlockedExerciseIds, true)) {
            $this->selectedExerciseId = (int) $exerciseParam;
        } else {
            $this->selectNextPendingExercise();
        }

        $this->currentExerciseText = $this->exercise?->target_text ?? '';
    }

    public function selectExercise(int $exerciseId): void
    {
        if (! in_array($exerciseId, $this->unlockedExerciseIds, true)) {
            $this->dispatch('typing-locked-alert', message: 'Exercício bloqueado! Conclua o exercício anterior com sucesso para desbloquear este nível.');

            return;
        }

        $this->selectedExerciseId = $exerciseId;
        $this->currentExerciseText = $this->exercise?->target_text ?? '';
        $this->isFinished = false;
        $this->activeTab = 'practice';
        $this->dispatch('reset-typing');
    }

    public function setCategoryFilter(string $category): void
    {
        $this->categoryFilter = $category;
    }

    protected function selectNextPendingExercise(): void
    {
        $allExercises = TypingExercise::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($allExercises->isEmpty()) {
            $this->selectedExerciseId = null;
            $this->currentExerciseText = '';

            return;
        }

        $completedIds = $this->completedExerciseIds;

        // Na trilha sequencial, busca o primeiro ainda não concluído
        $next = $allExercises->first(fn (TypingExercise $ex) => ! in_array($ex->id, $completedIds, true));
        $exercise = $next ?? $allExercises->first();

        $this->selectedExerciseId = $exercise?->id;
        $this->currentExerciseText = $exercise?->target_text ?? '';
    }

    public function getExerciseProperty(): ?TypingExercise
    {
        if (! $this->selectedExerciseId) {
            return null;
        }

        return TypingExercise::find($this->selectedExerciseId);
    }

    public function getContractProperty(): ?Contract
    {
        if (! $this->contractId) {
            return null;
        }

        return Contract::with('student')->find($this->contractId);
    }

    public function getLessonProperty(): ?LmsLesson
    {
        if (! $this->lessonId) {
            return null;
        }

        return LmsLesson::with('module.course')->find($this->lessonId);
    }

    /**
     * @return Collection<int, TypingExercise>
     */
    public function getExercisesListProperty(): Collection
    {
        $query = TypingExercise::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order');

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        return $query->get();
    }

    /**
     * IDs dos exercícios que o aluno já concluiu com aprovação
     *
     * @return array<int, int>
     */
    public function getCompletedExerciseIdsProperty(): array
    {
        if (! $this->contractId) {
            return [];
        }

        return StudentTypingRecord::query()
            ->where('contract_id', $this->contractId)
            ->where('passed', true)
            ->pluck('typing_exercise_id')
            ->unique()
            ->toArray();
    }

    public function getHasCompletedAllExercisesProperty(): bool
    {
        $allActiveCount = TypingExercise::where('is_active', true)->count();
        if ($allActiveCount === 0) {
            return true;
        }

        return count($this->completedExerciseIds) >= $allActiveCount;
    }

    /**
     * Trilha Sequencial:
     * - O aluno começa pelo primeiro exercício.
     * - Libera os próximos a partir das metas atingidas (precisão mínima).
     * - Quando terminar todos, tem acesso 100% livre a toda a biblioteca.
     * - Educadores possuem acesso livre a todos os exercícios.
     *
     * @return array<int, int>
     */
    public function getUnlockedExerciseIdsProperty(): array
    {
        $user = Auth::user();
        // Apenas educadores em modo estrito de visualização/preview (sem contrato de aluno ativo vinculado) possuem acesso irrestrito
        if (! $this->contractId && $user && ($user->isEducator() || $user->hasRole('super_admin') || $user->isStaff())) {
            return TypingExercise::where('is_active', true)->pluck('id')->toArray();
        }

        if ($this->hasCompletedAllExercises) {
            return TypingExercise::where('is_active', true)->pluck('id')->toArray();
        }

        $allExercises = TypingExercise::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $completedIds = $this->completedExerciseIds;
        $unlockedIds = [];
        $foundNextUncompleted = false;

        foreach ($allExercises as $ex) {
            if (in_array($ex->id, $completedIds, true)) {
                $unlockedIds[] = $ex->id;
            } elseif (! $foundNextUncompleted) {
                // O primeiro ainda não concluído da sequência fica disponível
                $unlockedIds[] = $ex->id;
                $foundNextUncompleted = true;
            }
        }

        if (empty($unlockedIds) && $allExercises->isNotEmpty()) {
            $unlockedIds[] = $allExercises->first()->id;
        }

        return $unlockedIds;
    }

    /**
     * Registra o resultado final da sessão de digitação
     */
    public function submitTypingResult(
        float $wpm,
        float $cpm,
        float $accuracy,
        int $totalKeystrokes,
        int $correctKeystrokes,
        int $errorKeystrokes,
        int $timeSpentSeconds
    ): void {
        $exercise = $this->exercise;
        if (! $exercise) {
            return;
        }

        $contract = $this->contract;

        $minAccuracy = (float) $exercise->min_accuracy;
        $minWpm = (int) $exercise->min_wpm;

        $passed = ($accuracy >= $minAccuracy);
        $stars = StudentTypingRecord::calculateStars($wpm, $accuracy, $minWpm);

        $this->finalWpm = $wpm;
        $this->finalCpm = $cpm;
        $this->finalAccuracy = $accuracy;
        $this->finalTimeSpent = $timeSpentSeconds;
        $this->stars = $stars;
        $this->passed = $passed;
        $this->isFinished = true;

        // Atualiza tempo acumulado no dia
        $this->typingTimeSpentToday += $timeSpentSeconds;
        $this->remainingSeconds = max(0, $this->requiredSeconds - $this->typingTimeSpentToday);

        if ($this->remainingSeconds <= 0) {
            $this->isWarmupCompleted = true;
        }

        if (! $contract) {
            return;
        }

        StudentTypingRecord::create([
            'contract_id' => $contract->id,
            'student_id' => $contract->student_id,
            'typing_exercise_id' => $exercise->id,
            'lesson_id' => $this->lessonId,
            'wpm' => $wpm,
            'cpm' => $cpm,
            'accuracy_percentage' => $accuracy,
            'total_keystrokes' => $totalKeystrokes,
            'correct_keystrokes' => $correctKeystrokes,
            'error_keystrokes' => $errorKeystrokes,
            'time_spent_seconds' => $timeSpentSeconds,
            'stars' => $stars,
            'passed' => $passed,
            'completed_at' => now(),
        ]);

        StudentAccessLog::log(
            'typing_warmup_completed',
            "Aluno completou o treino de digitação '{$exercise->title}' com {$wpm} PPM e {$accuracy}% de precisão",
            $contract,
            $this->lessonId,
            $contract->student
        );

        if ($this->isWarmupCompleted && $this->lessonId) {
            LmsStudentLessonProgress::updateOrCreate(
                [
                    'contract_id' => $contract->id,
                    'lesson_id' => $this->lessonId,
                ],
                [
                    'student_id' => $contract->student_id,
                    'typing_warmup_completed_at' => now(),
                    'typing_time_seconds' => $this->typingTimeSpentToday,
                ]
            );
        }
    }

    /**
     * Autorização presencial do Educador para liberar a aula sem aguardar os 15 min
     */
    public function authorizeByEducator(): void
    {
        $this->educatorAuthError = '';

        $cleanIdentifier = trim($this->educatorEmail);
        $cleanDigits = preg_replace('/\D/', '', $cleanIdentifier);

        $educator = User::query()
            ->where(function (Builder $query) use ($cleanIdentifier, $cleanDigits): void {
                $query->whereRaw('LOWER(email) = ?', [mb_strtolower($cleanIdentifier)])
                    ->orWhereHas('person', function (Builder $pq) use ($cleanIdentifier, $cleanDigits): void {
                        $pq->where(function (Builder $inner) use ($cleanIdentifier, $cleanDigits): void {
                            $inner->whereRaw('LOWER(email) = ?', [mb_strtolower($cleanIdentifier)])
                                ->orWhere('ouro_moderno_usuario', $cleanIdentifier);

                            if (! empty($cleanDigits)) {
                                $inner->orWhereCpf($cleanDigits);
                            }
                        });
                    });
            })
            ->first();

        if (! $educator || ! Hash::check($this->educatorPassword, $educator->password)) {
            $this->educatorAuthError = 'Credenciais de educador inválidas (e-mail/usuário ou senha incorretos).';

            return;
        }

        if (! $educator->isEducator() && ! $educator->hasRole(['super_admin', 'professor', 'secretaria']) && ! $educator->can('Manage:Pedagogical')) {
            $this->educatorAuthError = 'Este usuário não possui permissão para autorizar a liberação.';

            return;
        }

        $this->isEducatorAuthorized = true;
        $this->isWarmupCompleted = true;
        $this->remainingSeconds = 0;
        $this->showEducatorAuthModal = false;

        if ($this->contractId && $this->lessonId) {
            LmsStudentLessonProgress::updateOrCreate(
                [
                    'contract_id' => $this->contractId,
                    'lesson_id' => $this->lessonId,
                ],
                [
                    'student_id' => $this->contract?->student_id,
                    'typing_warmup_completed_at' => now(),
                    'typing_time_seconds' => $this->typingTimeSpentToday,
                ]
            );

            StudentAccessLog::log(
                'typing_warmup_educator_override',
                "Educador {$educator->name} autorizou a dispensa dos 15 min de digitação para a aula",
                $this->contract,
                $this->lessonId,
                $this->contract?->student
            );
        }
    }

    public function startLesson(): void
    {
        if ($this->lessonId) {
            if ($this->contractId) {
                LmsStudentLessonProgress::updateOrCreate(
                    [
                        'contract_id' => $this->contractId,
                        'lesson_id' => $this->lessonId,
                    ],
                    [
                        'student_id' => $this->contract?->student_id,
                        'typing_warmup_completed_at' => now(),
                        'typing_time_seconds' => $this->typingTimeSpentToday,
                    ]
                );
            }

            redirect()->route('lms.player', [
                'lesson' => $this->lessonId,
                'contract_id' => $this->contractId,
            ]);

            return;
        }

        redirect()->route('student.portal', ['tab' => 'courses']);
    }

    public function restartExercise(): void
    {
        $this->currentExerciseText = $this->exercise?->target_text ?? '';
        $this->isFinished = false;
        $this->dispatch('reset-typing');
    }

    public function nextExercise(): void
    {
        $allExercises = TypingExercise::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($allExercises->isNotEmpty() && $this->selectedExerciseId) {
            $unlockedIds = $this->unlockedExerciseIds;
            $currentIndex = $allExercises->search(fn (TypingExercise $ex) => $ex->id === $this->selectedExerciseId);

            if ($currentIndex !== false && isset($allExercises[$currentIndex + 1])) {
                $candidate = $allExercises[$currentIndex + 1];
                if (in_array($candidate->id, $unlockedIds, true) || ! $this->contractId) {
                    $this->selectedExerciseId = $candidate->id;
                    $this->currentExerciseText = $candidate->target_text ?? '';
                    $this->isFinished = false;
                    $this->dispatch('reset-typing');

                    return;
                }
            }
        }

        $this->selectNextPendingExercise();
        $this->currentExerciseText = $this->exercise?->target_text ?? '';
        $this->isFinished = false;
        $this->dispatch('reset-typing');
    }

    public function render(): View
    {
        $exerciseRankings = $this->exercise ? $this->exercise->topRankings(10) : new Collection();
        $schoolRankings = StudentTypingRecord::globalSchoolRankings(15);
        $myHistory = $this->contractId
            ? StudentTypingRecord::query()
                ->where('contract_id', $this->contractId)
                ->with('exercise')
                ->latest('id')
                ->limit(20)
                ->get()
            : new Collection();

        return view('livewire.student.student-typing-warmup', [
            'exercise' => $this->exercise,
            'contract' => $this->contract,
            'lesson' => $this->lesson,
            'exercisesList' => $this->exercisesList,
            'completedExerciseIds' => $this->completedExerciseIds,
            'unlockedExerciseIds' => $this->unlockedExerciseIds,
            'hasCompletedAllExercises' => $this->hasCompletedAllExercises,
            'exerciseRankings' => $exerciseRankings,
            'schoolRankings' => $schoolRankings,
            'myHistory' => $myHistory,
        ]);
    }
}
