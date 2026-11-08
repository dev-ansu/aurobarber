<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\Course;
use App\Models\Formation;
use App\Models\LmsCourseFinalExamAttempt;
use App\Models\LmsLesson;
use App\Models\LmsStudentLessonProgress;
use App\Models\School;
use App\Models\StudentAnnouncement;
use App\Models\StudentSchedule;
use App\Models\StudentTypingRecord;
use App\Services\LmsProgressionService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StudentPortal extends Component
{
    public ?int $selectedContractId = null;

    public ?int $selectedCourseId = null;

    public ?int $selectedFormationId = null;

    public string $activeTab = 'dashboard'; // 'dashboard' ou 'courses'

    public bool $isManagerPreview = false;

    public bool $isEducatorMode = false;

    public string $portalMode = 'student'; // 'student' ou 'educator'

    public string $courseSearch = '';

    // Exame Final de Certificação do Curso
    public bool $showCourseFinalExamModal = false;

    public ?int $finalExamCourseId = null;

    public string $finalExamCourseName = '';

    public array $finalExamQuestions = [];

    public array $finalExamAnswers = [];

    public bool $finalExamSubmitted = false;

    public float $finalExamScore = 0.0;

    public bool $finalExamPassed = false;

    // Troca de Senha Padrão do Aluno
    public bool $showChangePasswordModal = false;

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    // Propriedades para a Aba de Chamados & Suporte
    public ?int $selectedTicketId = null;
    public string $ticketCategory = 'pedagogico';
    public string $ticketSubject = '';
    public string $ticketDescription = '';
    public string $ticketReplyMessage = '';
    public bool $showNewTicketModal = false;

    public function mount(LmsProgressionService $progressionService): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Acesso não autorizado.');
        }

        // Colaboradores sem papel definido no sistema não podem acessar nenhum sistema
        if ($user->isCollaborator() && ! $user->hasDefinedRole()) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            abort(403, 'Acesso bloqueado: Este colaborador não possui um papel definido no sistema.');
        }

        $isStaff = $user->isStaff() || $user->isEducator();
        $isContractAuth = session('portal_auth_type') === 'student_contract';

        $contractIdFromSession = session('portal_contract_id');
        $studentContract = null;

        if ($contractIdFromSession) {
            $studentContract = Contract::withoutGlobalScopes()->where('id', $contractIdFromSession)->where('status', 'active')->first();
        }

        if (! $studentContract && $user->person_id) {
            $studentContract = Contract::withoutGlobalScopes()->where('student_id', $user->person_id)->where('status', 'active')->first();
        }

        $hasStudentContract = $studentContract !== null;

        // Se o login foi por contrato de aluno, OBRIGATORIAMENTE fica em modo aluno
        if ($isContractAuth) {
            $this->portalMode = 'student';
            $this->isEducatorMode = false;
            $this->selectedContractId = $studentContract?->id;
        } elseif ($isStaff) {
            $requestedMode = session('portal_mode_'.$user->id, request()->query('mode', $hasStudentContract ? 'student' : 'educator'));
            $this->portalMode = in_array($requestedMode, ['student', 'educator'], true) ? $requestedMode : ($hasStudentContract ? 'student' : 'educator');
            $this->isEducatorMode = ($this->portalMode === 'educator');

            if ($this->portalMode === 'student') {
                $this->selectedContractId = $studentContract?->id;
            } else {
                $this->selectedContractId = null;
            }
        } else {
            // Apenas aluno
            $this->portalMode = 'student';
            $this->isEducatorMode = false;
            $this->selectedContractId = $studentContract?->id;
        }

        // Se estiver no modo educador e quiser pré-visualizar outro aluno especificamente
        if ($this->portalMode === 'educator') {
            $previewId = request()->query('preview_contract_id');
            if ($previewId && $isStaff) {
                $previewContract = Contract::query()->where('id', $previewId)->where('status', 'active')->first();
                if ($previewContract) {
                    $this->selectedContractId = $previewContract->id;
                    $this->isManagerPreview = true;
                }
            }
        }

        $this->syncSelectedCourse();

        $requestedCourseId = request()->query('course_id');
        if ($requestedCourseId) {
            $this->selectedCourseId = (int) $requestedCourseId;
            $this->activeTab = 'courses';
        }

        $requestedTab = request()->query('tab');
        if (in_array($requestedTab, ['dashboard', 'courses', 'achievements', 'ranking', 'catalog', 'documents', 'tickets'], true)) {
            $this->activeTab = $requestedTab;
        }

        if ($user && $user->must_change_password) {
            $this->showChangePasswordModal = true;
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['dashboard', 'courses', 'achievements', 'ranking', 'catalog', 'documents', 'tickets'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function openChangePasswordModal(): void
    {
        $this->reset(['newPassword', 'newPasswordConfirmation']);
        $this->showChangePasswordModal = true;
    }

    public function closeChangePasswordModal(): void
    {
        if (Auth::user()?->must_change_password) {
            return;
        }
        $this->showChangePasswordModal = false;
        $this->reset(['newPassword', 'newPasswordConfirmation']);
    }

    public function updatePassword(): void
    {
        $this->validate([
            'newPassword' => ['required', 'string', 'min:6'],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ], [
            'newPassword.required' => 'Informe a sua nova senha.',
            'newPassword.min' => 'A nova senha deve conter no mínimo 6 caracteres.',
            'newPasswordConfirmation.required' => 'Confirme a nova senha.',
            'newPasswordConfirmation.same' => 'A confirmação de senha não confere.',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->password = Hash::make($this->newPassword);
            $user->must_change_password = false;
            $user->save();
        }

        $this->reset(['newPassword', 'newPasswordConfirmation', 'showChangePasswordModal']);

        Notification::make()
            ->title('Senha Alterada com Sucesso!')
            ->success()
            ->body('Sua nova senha individual foi gravada. Use-a nos seus próximos acessos ao portal.')
            ->send();
    }

    public function switchPortalMode(string $mode): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $isStaff = $user->isStaff() || $user->isEducator();
        $studentContract = $user->person_id
            ? Contract::query()->where('student_id', $user->person_id)->where('status', 'active')->first()
            : null;

        if ($mode === 'educator' && $isStaff) {
            $this->portalMode = 'educator';
            $this->isEducatorMode = true;
            $this->selectedContractId = null;
            $this->isManagerPreview = false;
            session(['portal_mode_'.$user->id => 'educator']);
            $this->syncSelectedCourse();
        } elseif ($mode === 'student' && $isStaff) {
            $this->portalMode = 'student';
            $this->isEducatorMode = false;
            $this->isManagerPreview = false;
            $this->selectedContractId = $studentContract?->id;

            session(['portal_mode_'.$user->id => 'student']);
            $this->syncSelectedCourse();
        }
    }

    public function previewStudent(?int $contractId): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isStaff() && ! $user->isEducator())) {
            return;
        }

        if (! $contractId) {
            $this->clearPreviewStudent();

            return;
        }

        $contract = Contract::query()->where('id', $contractId)->where('status', 'active')->first();
        if ($contract) {
            $this->selectedContractId = $contract->id;
            $this->isManagerPreview = true;
            $this->syncSelectedCourse();
        }
    }

    public function clearPreviewStudent(): void
    {
        $this->selectedContractId = null;
        $this->isManagerPreview = false;
        $this->syncSelectedCourse();
    }

    public function getCanReturnToAdminProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Se a sessão foi iniciada por contrato de aluno, JAMAIS exibe opção de voltar ao admin
        if (session('portal_auth_type') === 'student_contract') {
            return false;
        }

        // Se o usuário não possui permissão de acesso ao painel administrativo, oculta o botão
        return $user->canAccessPanel(Filament::getCurrentPanel() ?? Filament::getDefaultPanel());
    }

    public function getCanSwitchPortalModeProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Se o login foi estritamente por contrato de aluno, não exibe alternador de modo educador
        if (session('portal_auth_type') === 'student_contract') {
            return false;
        }

        return $user->isStaff() || $user->isEducator();
    }

    public function getMyStudentContractProperty(): ?Contract
    {
        $user = Auth::user();
        if (! $user || ! $user->person_id) {
            return null;
        }

        return Contract::query()
            ->where('student_id', $user->person_id)
            ->where('status', 'active')
            ->first();
    }

    public function getActiveContractsForPreviewProperty()
    {
        $user = Auth::user();
        if (! $user || (! $user->isStaff() && ! $user->isEducator())) {
            return collect();
        }

        return Contract::query()
            ->with('student')
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->limit(35)
            ->get();
    }

    public function getContractInstallmentsProperty()
    {
        if (! $this->contract) {
            return collect();
        }

        return $this->contract->installments()
            ->with(['financialType', 'paymentMethod'])
            ->orderBy('installment_number')
            ->orderBy('due_date')
            ->get();
    }

    public function selectCourse(int $courseId): void
    {
        $this->selectedCourseId = (int) $courseId;
        $this->activeTab = 'courses';
        if (Auth::id()) {
            session(['last_accessed_course_id_'.Auth::id() => (int) $courseId]);
        }
    }

    public function selectCourseAndOpen(int $courseId): void
    {
        $this->selectCourse($courseId);
    }

    public function backToCoursesList(): void
    {
        $this->activeTab = 'dashboard';
    }

    public function selectFormation(?int $formationId): void
    {
        $this->selectedFormationId = $formationId;
        $this->syncSelectedCourse();
    }

    protected function syncSelectedCourse(): void
    {
        $courses = $this->getCoursesProperty();
        if ($courses->isEmpty()) {
            $this->selectedCourseId = null;

            return;
        }

        $progression = app(LmsProgressionService::class);

        // Se o curso atual já selecionado for válido e acessível, mantém
        if ($this->selectedCourseId && $courses->contains('id', $this->selectedCourseId)) {
            $currentCourse = $courses->firstWhere('id', $this->selectedCourseId);
            if ($this->isEducatorMode || ! $this->contract || $progression->canAccessCourse($this->contract, $currentCourse)['can_access']) {
                return;
            }
        }

        // Prioriza a formação agendada para hoje (se houver e não houver filtro manual selecionado)
        $currentWeekday = now()->dayOfWeek;
        $sortedCourses = $courses;
        if ($this->contract && ! $this->isEducatorMode && $this->selectedFormationId === null) {
            $todayItemIds = StudentSchedule::query()
                ->whereIn('contract_item_id', $this->contract->items()->select('id'))
                ->where('weekday', $currentWeekday)
                ->pluck('contract_item_id')
                ->all();

            if (! empty($todayItemIds)) {
                $sortedCourses = $courses->sortByDesc(fn ($c) => in_array($c->contract_item_id, $todayItemIds, true) ? 1 : 0)->values();
            }
        }

        // 1. Prioriza curso em andamento (in_progress) e acessível
        if ($this->contract && ! $this->isEducatorMode) {
            foreach ($sortedCourses as $c) {
                $access = $progression->canAccessCourse($this->contract, $c);
                if ($access['can_access'] && ! $progression->isCourseCompleted($this->contract, $c)) {
                    $this->selectedCourseId = $c->id;

                    return;
                }
            }
        }

        // 2. Prioriza o último curso acessado se existir na lista e for acessível
        $lastCourse = $this->getLastAccessedCourseProperty();
        if ($lastCourse && $courses->contains('id', $lastCourse->id)) {
            if ($this->isEducatorMode || ! $this->contract || $progression->canAccessCourse($this->contract, $lastCourse)['can_access']) {
                $this->selectedCourseId = $lastCourse->id;

                return;
            }
        }

        // 3. Senão, seleciona o primeiro curso acessível da lista
        if ($this->contract && ! $this->isEducatorMode) {
            foreach ($sortedCourses as $c) {
                if ($progression->canAccessCourse($this->contract, $c)['can_access']) {
                    $this->selectedCourseId = $c->id;

                    return;
                }
            }
        }

        // Fallback final
        $this->selectedCourseId = $courses->first()?->id;
    }

    public function getContractProperty(): ?Contract
    {
        return $this->selectedContractId ? Contract::withoutGlobalScopes()->with('school')->find($this->selectedContractId) : null;
    }

    public function getStudentNameProperty(): string
    {
        return $this->contract?->student?->name ?? Auth::user()?->name ?? 'Aluno';
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCoursesProperty(): Collection
    {
        $user = Auth::user();
        $isStaffOrEducator = $user && ($user->isStaff() || $user->isEducator() || $user->hasRole(['super_admin', 'professor']));

        if ($this->isEducatorMode || $isStaffOrEducator) {
            $collaborator = $user?->person_id ? Collaborator::where('person_id', $user->person_id)->first() : null;

            if ($this->isEducatorMode && $collaborator && $collaborator->courses()->exists()) {
                $courses = $collaborator->courses()
                    ->where('active', true)
                    ->with(['lmsModules.lessons.steps'])
                    ->orderBy('name')
                    ->get();
            } else {
                // Educador no modo aluno ou educador com acesso amplo: TODOS os cursos liberados sem necessidade de horário
                $courses = Course::query()
                    ->where('active', true)
                    ->with(['lmsModules.lessons.steps'])
                    ->orderBy('name')
                    ->get();
            }
        } elseif ($this->contract) {
            $courses = app(LmsProgressionService::class)
                ->getOrderedCoursesForContract($this->contract);
        } else {
            $courses = new Collection;
        }

        if ($this->selectedFormationId !== null) {
            $fid = $this->selectedFormationId;
            $courses = $courses->filter(function (Course $c) use ($fid): bool {
                return (int) ($c->contract_formation_id ?? 0) === $fid;
            })->values();
        }

        if (filled($this->courseSearch)) {
            $term = mb_strtolower(trim($this->courseSearch));
            $courses = $courses->filter(fn (Course $c): bool => str_contains(mb_strtolower($c->name), $term))->values();
        }

        return $courses;
    }

    public function getLastAccessedCourseProperty(): ?Course
    {
        $userId = Auth::id();
        $cachedId = $userId ? session('last_accessed_course_id_'.$userId) : null;
        if ($cachedId) {
            $course = Course::find($cachedId);
            if ($course) {
                return $course;
            }
        }

        if ($this->contract) {
            $progress = LmsStudentLessonProgress::query()
                ->where('contract_id', $this->contract->id)
                ->whereNotNull('lesson_id')
                ->latest('updated_at')
                ->first();

            if ($progress && $progress->lesson?->module?->course) {
                return $progress->lesson->module->course;
            }
        }

        return $this->courses->first();
    }

    public function getSelectedCourseProperty(): ?Course
    {
        return $this->selectedCourseId ? Course::find($this->selectedCourseId) : null;
    }

    public function getCourseAccess(Course $course, LmsProgressionService $progressionService): array
    {
        if ($this->isEducatorMode) {
            $user = Auth::user();
            $collaborator = $user?->person_id ? Collaborator::where('person_id', $user->person_id)->first() : null;
            $isEdu = $collaborator?->is_educator || $user?->hasRole(['super_admin', 'professor']);

            return [
                'can_access' => true,
                'status' => 'educator_unlocked',
                'label' => $isEdu ? 'Acesso Educador' : 'Acesso Colaborador',
                'badge' => 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20',
                'message' => $isEdu
                    ? 'Você tem acesso liberado como Educador / Professor. Todos os cursos estão disponíveis para consulta e estudo sem necessidade de horário agendado.'
                    : 'Curso liberado pela coordenação para seu perfil de colaborador.',
                'progress_percentage' => 100,
            ];
        }

        if (! $this->contract) {
            $user = Auth::user();
            $isStaff = $user && ($user->isStaff() || $user->isEducator() || $user->hasRole(['super_admin', 'professor']));

            if ($isStaff) {
                $completedLessonIds = session('educator_test_completed_lessons_'.$user->id, []);
                $courseLessonIds = $course->lmsModules->flatMap->lessons->pluck('id')->all();
                $totalLessons = count($courseLessonIds);
                $doneCount = count(array_intersect($courseLessonIds, $completedLessonIds));
                $pct = $totalLessons > 0 ? (int) round(($doneCount / $totalLessons) * 100) : 0;
                $isCompleted = ($totalLessons > 0 && $doneCount === $totalLessons);

                if ($isCompleted) {
                    return [
                        'can_access' => true,
                        'status' => 'completed',
                        'label' => 'Concluído (Teste)',
                        'badge' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                        'message' => 'Curso concluído com sucesso no teste do educador.',
                        'progress_percentage' => 100,
                    ];
                }

                return [
                    'can_access' => true,
                    'status' => 'in_progress',
                    'label' => $pct > 0 ? "Em Andamento ({$pct}%)" : 'Liberado (Teste)',
                    'badge' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                    'message' => 'Liberado para teste pedagógico como aluno.',
                    'progress_percentage' => $pct,
                ];
            }

            return [
                'can_access' => false,
                'status' => 'locked',
                'label' => 'Bloqueado',
                'badge' => 'bg-slate-800 text-slate-500 border border-slate-700',
                'message' => 'Nenhum contrato ativo.',
                'progress_percentage' => 0,
            ];
        }

        $isCompleted = $progressionService->isCourseCompleted($this->contract, $course);
        $progressPct = $progressionService->getCourseProgressPercentage($this->contract, $course);

        if ($isCompleted) {
            return [
                'can_access' => true,
                'status' => 'completed',
                'label' => 'Concluído',
                'badge' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                'message' => 'Curso concluído com sucesso!',
                'progress_percentage' => 100,
            ];
        }

        $access = $progressionService->canAccessCourse($this->contract, $course);
        if ($access['can_access']) {
            return [
                'can_access' => true,
                'status' => 'in_progress',
                'label' => $progressPct > 0 ? "Em Andamento ({$progressPct}%)" : 'Liberado',
                'badge' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                'message' => 'Acesse as aulas e continue seu aprendizado.',
                'progress_percentage' => $progressPct,
            ];
        }

        return [
            'can_access' => false,
            'status' => 'locked',
            'label' => 'Bloqueado',
            'badge' => 'bg-slate-800 text-slate-400 border border-slate-700',
            'message' => $access['message'] ?? 'Conclua o curso anterior para liberar este conteúdo.',
            'progress_percentage' => 0,
        ];
    }

    public function getLessonStatus(LmsLesson $lesson, LmsProgressionService $progressionService): array
    {
        if ($this->isEducatorMode) {
            return [
                'state' => 'unlocked',
                'label' => 'Liberada (Educador)',
                'badge' => 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20',
                'message' => 'Visualização pedagógica liberada. Acesso irrestrito sem necessidade de horário agendado.',
                'progress' => null,
            ];
        }

        $user = Auth::user();
        $isStaff = $user && ($user->isStaff() || $user->isEducator() || $user->hasRole(['super_admin', 'professor']));

        // Educador testando o portal no modo aluno sem necessidade de contrato comercial
        if (! $this->contract && $isStaff) {
            $completedLessonIds = session('educator_test_completed_lessons_'.$user->id, []);

            if (in_array($lesson->id, $completedLessonIds, true)) {
                return [
                    'state' => 'completed',
                    'label' => 'Concluída',
                    'badge' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                    'message' => 'Aula concluída no teste do educador.',
                    'progress' => null,
                ];
            }

            return [
                'state' => 'available',
                'label' => 'Iniciar Aula',
                'badge' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                'message' => 'Pronta para iniciar o teste da aula.',
                'progress' => null,
            ];
        }

        if (! $this->contract) {
            return [
                'state' => 'locked',
                'label' => 'Bloqueada',
                'badge' => 'bg-slate-800 text-slate-400',
                'message' => 'Nenhum contrato ativo.',
            ];
        }

        $progress = LmsStudentLessonProgress::query()
            ->where('contract_id', $this->contract->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        // Verifica travas administrativas, financeiras, de horário e pedagógicas
        $access = $progressionService->canAccessLesson($this->contract, $lesson);

        if (! $access['can_access']) {
            if ($access['reason'] === 'administrative_check') {
                return [
                    'state' => 'locked_administrative',
                    'label' => 'Atendimento na Secretaria',
                    'badge' => 'bg-amber-500/10 text-amber-300 border border-amber-500/20',
                    'message' => 'Procure a recepção ou secretaria da escola para validação cadastral.',
                    'progress' => null,
                ];
            }

            if ($access['reason'] === 'early_arrival') {
                return [
                    'state' => 'locked_early',
                    'label' => 'Aguardando Horário',
                    'badge' => 'bg-blue-500/10 text-blue-300 border border-blue-500/20',
                    'message' => $access['message'] ?? 'Você chegou antes do seu horário de aula.',
                    'progress' => null,
                ];
            }

            if ($access['reason'] === 'after_hours') {
                return [
                    'state' => 'locked_after_hours',
                    'label' => 'Horário Encerrado',
                    'badge' => 'bg-slate-800 text-slate-400 border border-slate-700',
                    'message' => $access['message'] ?? 'Seu horário de aula de hoje encerrou.',
                    'progress' => null,
                ];
            }

            if (in_array($access['reason'], ['no_schedule_today', 'formation_not_scheduled_today'], true)) {
                return [
                    'state' => 'locked_no_schedule_today',
                    'label' => 'Sem Aula Hoje',
                    'badge' => 'bg-slate-800 text-slate-400 border border-slate-700',
                    'message' => $access['message'] ?? 'Você não possui aula agendada para hoje.',
                    'progress' => null,
                ];
            }

            if ($access['reason'] === 'no_schedule_registered') {
                return [
                    'state' => 'locked_no_schedule',
                    'label' => 'Sem Horário Cadastrado',
                    'badge' => 'bg-slate-800 text-slate-400 border border-slate-700',
                    'message' => $access['message'] ?? 'Horário de aula não cadastrado pela secretaria.',
                    'progress' => null,
                ];
            }

            if ($access['reason'] === 'previous_course_incomplete') {
                return [
                    'state' => 'locked_course',
                    'label' => 'Curso Bloqueado',
                    'badge' => 'bg-slate-800 text-slate-400 border border-slate-700',
                    'message' => $access['message'] ?? 'Conclua o curso anterior para liberar.',
                    'progress' => null,
                ];
            }

            if ($access['reason'] === 'daily_limit_reached') {
                return [
                    'state' => 'locked_daily',
                    'label' => 'Trava Diária (1 aula/dia)',
                    'badge' => 'bg-amber-500/10 text-amber-300 border border-amber-500/20',
                    'message' => 'Você já concluiu sua aula de hoje. Liberada na próxima aula.',
                    'progress' => null,
                ];
            }

            return [
                'state' => 'locked_prerequisite',
                'label' => 'Pré-requisito Pendente',
                'badge' => 'bg-slate-800 text-slate-400 border border-slate-700',
                'message' => $access['message'] ?? 'Conclua a aula anterior com nota mínima de 80%.',
                'progress' => null,
            ];
        }

        if ($progress && $progress->status === 'completed') {
            return [
                'state' => 'completed',
                'label' => 'Concluída ('.number_format((float) $progress->score, 0).'%)',
                'badge' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                'message' => 'Aula concluída com maestria.',
                'progress' => $progress,
            ];
        }

        if ($progress && $progress->status === 'in_progress') {
            return [
                'state' => 'in_progress',
                'label' => 'Em Andamento',
                'badge' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                'message' => 'Você já iniciou esta aula. Clique para continuar.',
                'progress' => $progress,
            ];
        }

        return [
            'state' => 'available',
            'label' => 'Disponível Hoje',
            'badge' => 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20',
            'message' => 'Aula de 90 minutos pronta para início.',
            'progress' => null,
        ];
    }

    public function getCurrentStudyTargetProperty(): ?array
    {
        if (! $this->contract) {
            if ($this->isEducatorTestingAsStudent) {
                $user = Auth::user();
                $completedLessonIds = session('educator_test_completed_lessons_'.($user?->id ?? 0), []);
                $course = $this->selectedCourse ?? $this->lastAccessedCourse ?? $this->courses->first();
                if ($course) {
                    $orderedLessons = app(LmsProgressionService::class)->getOrderedLessonsForCourse($course);
                    $nextLesson = $orderedLessons->first(fn ($l) => ! in_array($l->id, $completedLessonIds, true))
                        ?? $orderedLessons->last();

                    if ($nextLesson) {
                        $isCompleted = in_array($nextLesson->id, $completedLessonIds, true);

                        return [
                            'course' => $course,
                            'lesson' => $nextLesson,
                            'type' => $isCompleted ? 'reviewing' : 'resuming',
                            'label' => $isCompleted ? 'Revisar Aula' : 'Continuar Aula',
                            'message' => "Você está testando '{$course->name}' no ritmo de aluno.",
                        ];
                    }
                }
            }

            return null;
        }

        return app(LmsProgressionService::class)->getNextOrCurrentLessonForStudent($this->contract);
    }

    public function getHasCompletedTypingWarmupTodayProperty(): bool
    {
        if ($this->isEducatorTestingAsStudent) {
            return true;
        }

        if (! $this->contract) {
            return false;
        }

        $todaySeconds = (int) StudentTypingRecord::query()
            ->where('contract_id', $this->contract->id)
            ->whereDate('completed_at', today())
            ->sum('time_spent_seconds');

        return $todaySeconds >= 900;
    }

    public function resumeStudy(): mixed
    {
        $target = $this->getCurrentStudyTargetProperty();
        $lesson = $target['lesson'] ?? null;
        if (! $lesson) {
            return null;
        }

        if ($this->contract) {
            $access = app(LmsProgressionService::class)->canAccessLesson($this->contract, $lesson);
            if (! $access['can_access']) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => $access['message'] ?? 'Acesso à aula indisponível no momento.',
                ]);

                return null;
            }

            return redirect()->route('lms.player', [
                'lesson' => $lesson->id,
                'contract_id' => $this->contract->id,
            ]);
        }

        if ($this->isEducatorTestingAsStudent) {
            return redirect()->route('lms.player', [
                'lesson' => $lesson->id,
            ]);
        }

        return null;
    }

    public function getIsBirthdayTodayProperty(): bool
    {
        $student = $this->contract?->student ?? Auth::user()->person;
        if (! $student || ! $student->birth_date) {
            return false;
        }

        return $student->birth_date->format('m-d') === now()->format('m-d');
    }

    public function getAnnouncementsProperty()
    {
        return StudentAnnouncement::query()
            ->visibleToStudents()
            ->get();
    }

    public function getCourseFormationName(Course $course): ?string
    {
        if (filled($course->contract_formation_name ?? null)) {
            return $course->contract_formation_name;
        }

        if ($this->contract) {
            $item = $this->contract->items()
                ->where(function ($q) use ($course) {
                    $q->whereHas('contractCourses', fn ($cQ) => $cQ->where('course_id', $course->id))
                        ->orWhereHas('formation.courses', fn ($fQ) => $fQ->where('courses.id', $course->id));
                })
                ->first();

            return $item?->formation?->name;
        }

        return $course->formations->first()?->name;
    }

    public function getEnrolledFormationsProperty(): BaseCollection
    {
        if (! $this->contract) {
            return collect();
        }

        $currentWeekday = now()->dayOfWeek;

        return $this->contract->items()
            ->with(['formation.courses', 'contractCourses', 'schedules'])
            ->get()
            ->filter(fn ($item) => $item->formation !== null)
            ->map(function ($item) use ($currentWeekday) {
                $formation = $item->formation;
                $coursesCount = $item->contractCourses->count() ?: $formation->courses->count();
                $completedCoursesCount = $item->contractCourses->where('status', 'completed')->count();

                $isScheduledToday = $item->schedules->contains(fn ($s) => (int) $s->weekday === $currentWeekday);
                $schedulesText = $item->schedules
                    ->map(fn ($s) => $s->weekdayLabel().' das '.$s->formattedStartHour().' às '.$s->formattedEndHour())
                    ->unique()
                    ->join('; ');

                return (object) [
                    'id' => $formation->id,
                    'contract_item_id' => $item->id,
                    'name' => $formation->name,
                    'description' => $formation->description,
                    'courses_count' => $coursesCount,
                    'completed_courses_count' => $completedCoursesCount,
                    'is_completed' => $item->formation_completed_at !== null,
                    'is_scheduled_today' => $isScheduledToday,
                    'schedules_text' => $schedulesText,
                ];
            })
            ->unique('id')
            ->values();
    }

    public function getShowcaseFormationsProperty()
    {
        $enrolledFormationIds = [];
        if ($this->contract) {
            $enrolledFormationIds = $this->contract->items()
                ->pluck('formation_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        $query = Formation::query()
            ->where('active', true)
            ->with(['courses.lmsModules.lessons'])
            ->orderBy('name');

        $otherFormations = (clone $query)->whereNotIn('id', $enrolledFormationIds)->get();
        $formations = $otherFormations->isNotEmpty() ? $otherFormations : $query->get();

        return $formations->map(function (Formation $f) {
            $courses = $f->courses;
            $f->total_workload = (int) $courses->sum('workload');
            $f->total_courses_count = $courses->count();
            $f->total_lessons_count = $courses->flatMap->lmsModules->flatMap->lessons->where('is_active', true)->count();

            return $f;
        });
    }

    public function getIsEducatorTestingAsStudentProperty(): bool
    {
        $user = Auth::user();
        $isStaff = $user && ($user->isStaff() || $user->isEducator() || $user->hasRole(['super_admin', 'professor']));

        return $isStaff && $this->portalMode === 'student';
    }

    public function getScheduleStatusProperty(): array
    {
        $user = Auth::user();
        $isStaff = $user && ($user->isStaff() || $user->isEducator() || $user->hasRole(['super_admin', 'professor']));

        if ($this->isEducatorMode || $isStaff) {
            $collaborator = $user?->person_id ? Collaborator::where('person_id', $user->person_id)->first() : null;
            $isEdu = $collaborator?->is_educator || $user?->hasRole(['super_admin', 'professor']);

            return [
                'is_allowed' => true,
                'is_educator' => $isEdu,
                'message' => $this->isEducatorMode
                    ? ($isEdu ? 'Modo Educador — Acesso pedagógico irrestrito.' : 'Modo Colaborador — Cursos autorizados.')
                    : 'Modo Teste do Educador — Cursos liberados com experiência e travas de aluno.',
                'schedules_text' => 'Acesso livre de testes (24/7)',
            ];
        }

        if (! $this->contract) {
            return [
                'is_allowed' => false,
                'is_educator' => false,
                'reason' => 'no_contract',
                'message' => 'Nenhuma matrícula ativa localizada para este usuário.',
                'schedules_text' => 'Sem contrato ativo',
            ];
        }

        $status = app(LmsProgressionService::class)->checkStudentScheduleWindow($this->contract);
        $status['is_educator'] = false;

        return $status;
    }

    public function resetCourseTestProgress(int $courseId): void
    {
        $userId = Auth::id() ?? 0;
        $course = Course::find($courseId);
        if ($course) {
            $lessonIds = $course->lmsModules->flatMap->lessons->pluck('id')->all();
            $completed = session('educator_test_completed_lessons_'.$userId, []);
            $completed = array_values(array_diff($completed, $lessonIds));
            session(['educator_test_completed_lessons_'.$userId => $completed]);

            Notification::make()
                ->title('Progresso de teste reiniciado!')
                ->body("As aulas de '{$course->name}' foram reiniciadas para novo teste.")
                ->success()
                ->send();
        }
    }

    /**
     * Galeria de Conquistas (Insígnias / Badges Desbloqueáveis)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAchievementsBadgesProperty(): array
    {
        $completedLessons = 0;
        $hasPerfectScore = false;
        $perfectCount = 0;
        $ratedCount = 0;
        $maxTypingWpm = 0;
        $completedCoursesCount = 0;
        $passedFinalExamsCount = 0;
        $studyHours = 0.0;

        if ($this->contract) {
            $progression = app(LmsProgressionService::class);

            $progresses = LmsStudentLessonProgress::query()
                ->where('contract_id', $this->contract->id)
                ->where('status', 'completed')
                ->get();

            // Total unificado de aulas e tempo (LMS Aurosoft + Ouro Moderno)
            $completedLessons = $progression->getStudentCompletedLessonsCount($this->contract);
            $perfectCount = $progresses->filter(fn ($p) => (float) $p->score >= 100)->count();
            $ratedCount = $progresses->whereNotNull('student_rating')->count();
            $studyHours = round($progression->getStudentTotalStudyTimeSeconds($this->contract) / 3600, 1);

            // Verifica também notas máximas nas avaliações sincronizadas da Ouro Moderno
            $contractCourses = ContractCourse::query()
                ->whereIn('contract_item_id', $this->contract->items()->select('id'))
                ->get();

            foreach ($contractCourses as $cc) {
                if (! empty($cc->ouro_moderno_test_status)) {
                    foreach ((array) $cc->ouro_moderno_test_status as $test) {
                        $n = (float) ($test['nota'] ?? $test['grade'] ?? 0);
                        if ($n >= 100 || ($n >= 9.9 && $n <= 10.0)) {
                            $perfectCount++;
                        }
                    }
                }
                if ($cc->grade !== null && (float) $cc->grade >= 100) {
                    $perfectCount++;
                }
            }

            $hasPerfectScore = $perfectCount > 0;

            $maxTypingWpm = (int) (StudentTypingRecord::query()
                ->where('contract_id', $this->contract->id)
                ->max('wpm') ?? 0);

            foreach ($this->courses as $course) {
                if ($progression->isCourseCompleted($this->contract, $course)) {
                    $completedCoursesCount++;
                }
            }

            $passedFinalExamsCount = LmsCourseFinalExamAttempt::query()
                ->where('contract_id', $this->contract->id)
                ->where('passed', true)
                ->distinct('course_id')
                ->count('course_id');
        } elseif ($this->isEducatorMode) {
            $completedLessons = 10;
            $hasPerfectScore = true;
            $perfectCount = 5;
            $ratedCount = 3;
            $maxTypingWpm = 45;
            $completedCoursesCount = 1;
            $passedFinalExamsCount = 1;
            $studyHours = 5.0;
        }

        return [
            [
                'id' => 'first_step',
                'icon' => '🚀',
                'title' => 'Primeiro Passo',
                'description' => 'Concluiu a primeira aula prática no LMS.',
                'unlocked' => $completedLessons >= 1,
                'progress_text' => min(1, $completedLessons).'/1 aula',
            ],
            [
                'id' => 'perfect_100',
                'icon' => '🎯',
                'title' => 'Nota 100 Impecável',
                'description' => 'Alcançou 100% de aproveitamento em uma aula.',
                'unlocked' => $hasPerfectScore,
                'progress_text' => $hasPerfectScore ? 'Conquistado!' : '0/1 nota 100%',
            ],
            [
                'id' => 'unshakable_mind',
                'icon' => '🧠',
                'title' => 'Mente Inabalável',
                'description' => 'Conquistou nota 100% em 5 aulas diferentes.',
                'unlocked' => $perfectCount >= 5,
                'progress_text' => min(5, $perfectCount).'/5 notas 100%',
            ],
            [
                'id' => 'typing_master',
                'icon' => '⌨️',
                'title' => 'Mestre da Digitação',
                'description' => 'Atingiu velocidade de 35 PPM ou mais na digitação.',
                'unlocked' => $maxTypingWpm >= 35,
                'progress_text' => $maxTypingWpm.'/35 PPM',
            ],
            [
                'id' => 'dedicated_scholar',
                'icon' => '🔥',
                'title' => 'Ritmo Inaciano',
                'description' => 'Concluiu 5 aulas completas com constância.',
                'unlocked' => $completedLessons >= 5,
                'progress_text' => min(5, $completedLessons).'/5 aulas',
            ],
            [
                'id' => 'active_voice',
                'icon' => '⭐',
                'title' => 'Voz Ativa',
                'description' => 'Avaliou 3 aulas concluídas com estrelas.',
                'unlocked' => $ratedCount >= 3,
                'progress_text' => min(3, $ratedCount).'/3 avaliações',
            ],
            [
                'id' => 'course_dominator',
                'icon' => '🏅',
                'title' => 'Dominador de Curso',
                'description' => 'Concluiu 100% das aulas de um curso da formação.',
                'unlocked' => $completedCoursesCount >= 1,
                'progress_text' => min(1, $completedCoursesCount).'/1 curso',
            ],
            [
                'id' => 'certified_specialist',
                'icon' => '🎓',
                'title' => 'Especialista Certificado',
                'description' => 'Aprovado no Exame Final de Certificação do Curso.',
                'unlocked' => $passedFinalExamsCount >= 1,
                'progress_text' => min(1, $passedFinalExamsCount).'/1 exame final',
            ],
        ];
    }

    public function getCourseFinalExamStatus(Course $course): ?LmsCourseFinalExamAttempt
    {
        if (! $this->contract) {
            return null;
        }

        return LmsCourseFinalExamAttempt::query()
            ->where('contract_id', $this->contract->id)
            ->where('course_id', $course->id)
            ->orderByDesc('passed')
            ->orderByDesc('score')
            ->first();
    }

    public function openCourseFinalExam(int $courseId): void
    {
        $course = Course::with(['lmsModules.lessons.steps'])->find($courseId);
        if (! $course) {
            return;
        }

        $pool = [];
        foreach ($course->lmsModules as $module) {
            foreach ($module->lessons->where('is_active', true) as $lesson) {
                foreach ($lesson->steps->where('step_type', 'quiz') as $step) {
                    $content = is_array($step->content) ? $step->content : [];
                    if (! empty($content['question_pool']) && is_array($content['question_pool'])) {
                        foreach ($content['question_pool'] as $q) {
                            if (! empty($q['question']) && ! empty($q['options'])) {
                                $pool[] = [
                                    'lesson_title' => $lesson->title,
                                    'question' => (string) $q['question'],
                                    'options' => array_values($q['options']),
                                ];
                            }
                        }
                    } elseif (! empty($content['question']) && ! empty($content['options'])) {
                        $pool[] = [
                            'lesson_title' => $lesson->title,
                            'question' => (string) $content['question'],
                            'options' => array_values($content['options']),
                        ];
                    }
                }
            }
        }

        if (empty($pool)) {
            $pool[] = [
                'lesson_title' => $course->name,
                'question' => 'Qual é o princípio fundamental da metodologia prática aplicada ao longo deste curso?',
                'options' => [
                    ['id' => 'A', 'text' => 'Repetição deliberada, prática autônoma e atenção aos detalhes.', 'is_correct' => true],
                    ['id' => 'B', 'text' => 'Avançar rapidamente sem praticar os exercícios propostos.', 'is_correct' => false],
                    ['id' => 'C', 'text' => 'Ignorar as instruções técnicas do laboratório.', 'is_correct' => false],
                    ['id' => 'D', 'text' => 'Memorizar textos sem executar no computador.', 'is_correct' => false],
                ],
            ];
        }

        shuffle($pool);
        $drawn = array_slice($pool, 0, 20);

        $this->finalExamCourseId = $course->id;
        $this->finalExamCourseName = $course->name;
        $this->finalExamQuestions = $drawn;
        $this->finalExamAnswers = [];
        $this->finalExamSubmitted = false;
        $this->finalExamScore = 0.0;
        $this->finalExamPassed = false;
        $this->showCourseFinalExamModal = true;
    }

    public function selectFinalExamOption(int $qIndex, string $optionId): void
    {
        if ($this->finalExamSubmitted) {
            return;
        }

        $this->finalExamAnswers[$qIndex] = $optionId;
    }

    public function submitCourseFinalExam(): void
    {
        if (empty($this->finalExamQuestions) || ! $this->finalExamCourseId) {
            return;
        }

        $total = count($this->finalExamQuestions);
        $correct = 0;

        foreach ($this->finalExamQuestions as $idx => $q) {
            $selectedId = $this->finalExamAnswers[$idx] ?? null;
            if (! $selectedId) {
                continue;
            }
            foreach ($q['options'] as $opt) {
                if (($opt['id'] ?? '') === $selectedId && ! empty($opt['is_correct'])) {
                    $correct++;
                    break;
                }
            }
        }

        $score = $total > 0 ? round(($correct / $total) * 100, 2) : 100.0;
        $passed = $score >= 70.0;

        $this->finalExamScore = $score;
        $this->finalExamPassed = $passed;
        $this->finalExamSubmitted = true;

        if ($this->contract) {
            LmsCourseFinalExamAttempt::create([
                'contract_id' => $this->contract->id,
                'course_id' => $this->finalExamCourseId,
                'total_questions' => $total,
                'correct_answers' => $correct,
                'score' => $score,
                'passed' => $passed,
                'questions_snapshot' => $this->finalExamQuestions,
                'answers_snapshot' => $this->finalExamAnswers,
                'completed_at' => now(),
            ]);
        }
    }

    public function closeCourseFinalExam(): void
    {
        $this->showCourseFinalExamModal = false;
    }

    public function render(LmsProgressionService $progressionService): View
    {
        $portalSchoolId = $this->contract?->school_id ?? session('portal_school_id');
        $school = ($portalSchoolId ? School::query()->find($portalSchoolId) : null) ?? School::query()->first();
        $schoolName = $school?->getFilamentName() ?? School::getSchoolName();
        $schoolPhone = $school?->phone ?? '';
        $overallGrade = $this->contract ? $progressionService->getStudentOverallGrade($this->contract) : null;
        $totalStudyTimeSeconds = $this->contract ? $progressionService->getStudentTotalStudyTimeSeconds($this->contract) : 0;
        $completedLessonsCount = $this->contract ? $progressionService->getStudentCompletedLessonsCount($this->contract) : 0;
        $leaderboard = $this->contract ? $progressionService->getSchoolLeaderboard($this->contract) : ['top' => [], 'current_student_rank' => null];
        $activities = $this->contract ? $progressionService->getStudentCompletedActivities($this->contract) : collect();
        $isFormationCompleted = $this->contract ? $progressionService->isFormationCompleted($this->contract) : false;
        $currentStudyTarget = $this->getCurrentStudyTargetProperty();

        // Total de aulas na formação inteira
        $totalLessonsInFormation = 0;
        if ($this->contract) {
            $ouroSync = app(\App\Services\OuroModernoProgressSyncService::class);
            $contractCourses = \App\Models\ContractCourse::query()
                ->whereIn('contract_item_id', $this->contract->items()->select('id'))
                ->with('course.lmsModules.lessons')
                ->get();

            foreach ($contractCourses as $cc) {
                if ($cc->course && $cc->course->isAurosoftLms()) {
                    $totalLessonsInFormation += $cc->course->lmsModules->flatMap->lessons->where('is_active', true)->count();
                } elseif (! empty($cc->ouro_moderno_lesson_status)) {
                    $fb = $ouroSync->feedback($cc);
                    $totalLessonsInFormation += ! empty($fb['total_lessons']) ? (int) $fb['total_lessons'] : (int) ($cc->course?->workload ?? 20);
                } else {
                    $totalLessonsInFormation += (int) ($cc->course?->workload ?? 20);
                }
            }
        }

        $hq = School::getHeadquarters();
        $useHqPix = $school && ! $school->is_headquarters && $school->inherit_pix_settings && $hq;
        $pixSource = $useHqPix ? $hq : $school;

        $pixInfo = [
            'enabled' => (bool) ($pixSource?->pix_enabled ?? false),
            'key' => $pixSource?->pix_key ?? '',
            'key_type' => $pixSource?->pix_key_type ?? '',
            'merchant_name' => $pixSource?->pix_merchant_name ?: ($pixSource?->trade_name ?? ''),
            'merchant_city' => $pixSource?->pix_merchant_city ?: ($pixSource?->city ?? ''),
            'instructions' => $pixSource?->pix_instructions ?? '',
        ];

        return view('livewire.student.student-portal', [
            'studentName' => $this->studentName,
            'schoolName' => $schoolName,
            'schoolLogoUrl' => $school?->logo_url,
            'schoolPhone' => $schoolPhone,
            'overallGrade' => $overallGrade,
            'totalStudyTimeSeconds' => $totalStudyTimeSeconds,
            'completedLessonsCount' => $completedLessonsCount,
            'totalLessonsInFormation' => $totalLessonsInFormation,
            'leaderboard' => $leaderboard,
            'activities' => $activities,
            'isFormationCompleted' => $isFormationCompleted,
            'currentStudyTarget' => $currentStudyTarget,
            'progressionService' => $progressionService,
            'contract' => $this->contract,
            'isBirthdayToday' => $this->isBirthdayToday,
            'announcements' => $this->announcements,
            'showcaseFormations' => $this->showcaseFormations,
            'enrolledFormations' => $this->enrolledFormations,
            'scheduleStatus' => $this->scheduleStatus,
            'canReturnToAdmin' => $this->canReturnToAdmin,
            'pixInfo' => $pixInfo,
            'contractInstallments' => $this->contractInstallments,
            'studentTickets' => $this->studentTickets,
            'currentTicket' => $this->currentTicket,
        ]);
    }

    public function getStudentTicketsProperty()
    {
        if (! $this->contract && ! $this->isEducatorMode) {
            return collect();
        }

        $query = \App\Models\SupportTicket::query()
            ->with(['messages'])
            ->latest('updated_at');

        if ($this->contract) {
            $query->where(function ($q) {
                $q->where('contract_id', $this->contract->id)
                  ->orWhere('student_id', $this->contract->student_id);
            });
        }

        return $query->get();
    }

    public function getCurrentTicketProperty()
    {
        if (! $this->selectedTicketId) {
            return null;
        }

        return \App\Models\SupportTicket::query()
            ->with(['messages.user', 'messages.person', 'school'])
            ->find($this->selectedTicketId);
    }

    public function selectTicket(int $ticketId): void
    {
        $this->selectedTicketId = $ticketId;
    }

    public function openNewTicketModal(): void
    {
        $this->reset(['ticketCategory', 'ticketSubject', 'ticketDescription']);
        $this->showNewTicketModal = true;
    }

    public function createTicket(): void
    {
        $this->validate([
            'ticketCategory' => 'required|string',
            'ticketSubject' => 'required|string|min:3|max:150',
            'ticketDescription' => 'required|string|min:5|max:2000',
        ]);

        $ticket = \App\Models\SupportTicket::create([
            'type' => 'student_support',
            'category' => $this->ticketCategory,
            'priority' => 'medium',
            'status' => 'open',
            'school_id' => $this->contract?->school_id,
            'contract_id' => $this->contract?->id,
            'student_id' => $this->contract?->student_id,
            'subject' => $this->ticketSubject,
            'description' => $this->ticketDescription,
        ]);

        \App\Models\SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'person_id' => $this->contract?->student_id,
            'author_name' => $this->studentName ?: 'Aluno',
            'author_type' => 'student',
            'message' => $this->ticketDescription,
        ]);

        // Notifica os colaboradores da escola no sino do painel Filament
        try {
            $schoolId = $this->contract?->school_id;
            $staffUsers = \App\Models\User::query()
                ->where('panel_access', true)
                ->where(function ($q) use ($schoolId) {
                    if ($schoolId) {
                        $q->whereHas('schools', fn ($sq) => $sq->where('schools.id', $schoolId))
                          ->orWhereDoesntHave('schools');
                    }
                })
                ->get();

            if ($staffUsers->isEmpty()) {
                $staffUsers = \App\Models\User::query()->where('panel_access', true)->get();
            }

            foreach ($staffUsers as $staff) {
                $notif = \Filament\Notifications\Notification::make()
                    ->title('Novo Chamado de Aluno')
                    ->body("O aluno {$this->studentName} abriu o chamado [{$ticket->protocol}]: {$ticket->subject}")
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->iconColor('warning')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view_ticket')
                            ->label('Ver Chamado')
                            ->url(url("/" . ($schoolId ?? 1) . "/support-tickets"))
                            ->markAsRead(),
                    ]);
                $staff->notifyNow($notif->toDatabase());
            }
        } catch (\Throwable) {
            // Silencioso se notificação falhar
        }

        $this->showNewTicketModal = false;
        $this->selectedTicketId = $ticket->id;
        $this->reset(['ticketSubject', 'ticketDescription']);

        session()->flash('ticket_success', 'Seu chamado foi aberto com sucesso! A equipe da escola responderá em breve.');
    }

    public function replyTicket(): void
    {
        $this->validate([
            'ticketReplyMessage' => 'required|string|min:2|max:2000',
        ]);

        if (! $this->selectedTicketId) {
            return;
        }

        $ticket = \App\Models\SupportTicket::find($this->selectedTicketId);
        if (! $ticket) {
            return;
        }

        \App\Models\SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'person_id' => $this->contract?->student_id,
            'author_name' => $this->studentName ?: 'Aluno',
            'author_type' => 'student',
            'message' => $this->ticketReplyMessage,
        ]);

        $ticket->update([
            'status' => 'in_progress',
            'updated_at' => now(),
        ]);

        // Notifica a equipe da escola sobre a resposta do aluno
        try {
            $schoolId = $this->contract?->school_id ?? $ticket->school_id;
            $staffUsers = \App\Models\User::query()
                ->where('panel_access', true)
                ->where(function ($q) use ($schoolId) {
                    if ($schoolId) {
                        $q->whereHas('schools', fn ($sq) => $sq->where('schools.id', $schoolId))
                          ->orWhereDoesntHave('schools');
                    }
                })
                ->get();

            if ($staffUsers->isEmpty()) {
                $staffUsers = \App\Models\User::query()->where('panel_access', true)->get();
            }

            foreach ($staffUsers as $staff) {
                $notif = \Filament\Notifications\Notification::make()
                    ->title('Nova Resposta no Chamado')
                    ->body("O aluno {$this->studentName} respondeu ao chamado [{$ticket->protocol}]: {$this->ticketReplyMessage}")
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->iconColor('info')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view_ticket')
                            ->label('Atender Chamado')
                            ->url(url("/" . ($schoolId ?? 1) . "/support-tickets"))
                            ->markAsRead(),
                    ]);
                $staff->notifyNow($notif->toDatabase());
            }
        } catch (\Throwable) {
        }

        $this->reset(['ticketReplyMessage']);
    }
}
