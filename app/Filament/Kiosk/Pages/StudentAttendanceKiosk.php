<?php

declare(strict_types=1);

namespace App\Filament\Kiosk\Pages;

use App\Actions\MarkStudentAttendanceAtKiosk;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\SatisfactionSurvey;
use App\Models\SatisfactionSurveyAnswer;
use App\Models\SatisfactionSurveyResponse;
use App\Models\School;
use App\Models\StudentAnnouncement;
use App\Models\User;
use App\Services\OuroModernoService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StudentAttendanceKiosk extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = '/';

    protected static ?string $title = '';

    protected string $view = 'filament.kiosk.pages.student-attendance-kiosk';

    /**
     * @var array<string, string>|null
     */
    public ?array $data = [];

    /** @var array<int, array{id: int, title: string, body: string|null, image: string|null, url: string|null}> */
    public array $studentAnnouncements = [];

    public string $lookupName = '';

    public ?string $lookupBirthDate = null;

    /** @var array<int, string> */
    public array $lookupContractNumbers = [];

    public bool $lookupSubmitted = false;

    public ?int $kioskSchoolId = null;

    public bool $showSchoolSwitcherModal = false;

    public bool $showSupervisorAuthModal = false;

    public string $supervisorLogin = '';

    public string $supervisorPassword = '';

    public string $supervisorAuthError = '';

    // Survey State
    public bool $isAnsweringSurvey = false;

    /** @var array<string, mixed> */
    public array $pendingSurveyData = [];

    public ?int $pendingContractId = null;

    public ?string $pendingContractNumber = null;

    public ?string $pendingStudentName = null;

    /** @var array<int, array{rating?: int|null, text?: string|null}> */
    public array $surveyAnswers = [];

    public function mount(): void
    {
        $resolvedSchoolId = null;

        // 1. Prioridade: Parâmetro da URL (?escola=ID/slug ou ?school=ID/slug ou ?school_id=ID)
        $requestedSchool = request()->query('school') ?? request()->query('escola') ?? request()->query('school_id');
        if (filled($requestedSchool)) {
            if (is_numeric($requestedSchool)) {
                $candidate = School::query()->whereKey((int) $requestedSchool)->value('id');
            } else {
                $candidate = School::query()
                    ->where(function ($q) use ($requestedSchool) {
                        $q->where('trade_name', 'ilike', "%{$requestedSchool}%")
                            ->orWhere('legal_name', 'ilike', "%{$requestedSchool}%");
                    })
                    ->value('id');
            }

            if ($candidate) {
                $resolvedSchoolId = $candidate;
                cookie()->queue(cookie()->forever('kiosk_school_id', (string) $candidate));
            }
        }

        // 2. Prioridade: Cookie permanente gravado no terminal deste Totem
        $cookieVal = request()->cookie('kiosk_school_id') ?? ($_COOKIE['kiosk_school_id'] ?? null);
        if (! $resolvedSchoolId && filled($cookieVal)) {
            $cookieId = (int) $cookieVal;
            if (School::query()->whereKey($cookieId)->exists()) {
                $resolvedSchoolId = $cookieId;
            }
        }

        $this->kioskSchoolId = $resolvedSchoolId;

        if (session('open_school_switcher_modal') && $this->isSupervisorAuthenticated()) {
            $this->showSchoolSwitcherModal = true;
        }

        $this->form->fill();
        $this->loadAnnouncements();
    }

    public function loadAnnouncements(): void
    {
        $this->studentAnnouncements = StudentAnnouncement::query()
            ->forSchool($this->kioskSchoolId)
            ->visibleToStudents()
            ->get()
            ->map(fn (StudentAnnouncement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'image' => $announcement->image_path
                    ? (Str::startsWith($announcement->image_path, ['http://', 'https://', '/'])
                        ? $announcement->image_path
                        : '/storage/'.ltrim($announcement->image_path, '/'))
                    : null,
                'url' => $announcement->external_url,
            ])
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('contract_number')
                    ->label('Número do contrato, CPF, e-mail ou usuário')
                    ->placeholder('Ex.: AURO-123, seu CPF, e-mail ou usuário')
                    ->required()
                    ->maxLength(50)
                    ->autofocus()
                    ->extraInputAttributes([
                        'autocapitalize' => 'characters',
                        'spellcheck' => 'false',
                    ]),
            ])
            ->statePath('data');
    }

    public function lookupContractNumber(): void
    {
        $data = Validator::make([
            'name' => $this->lookupName,
            'birth_date' => $this->lookupBirthDate,
        ], [
            'name' => ['required', 'string', 'min:5', 'max:255'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'name.required' => 'Informe seu nome completo.',
            'name.min' => 'Informe seu nome completo.',
            'birth_date.required' => 'Informe sua data de nascimento.',
            'birth_date.before_or_equal' => 'A data de nascimento não pode ser futura.',
        ])->validate();

        $normalizedName = mb_strtolower(preg_replace('/\s+/u', ' ', trim($data['name'])));
        $attendanceDate = today();

        $this->lookupContractNumbers = Contract::withoutGlobalScopes()
            ->where('status', 'active')
            ->when($this->kioskSchoolId !== null, fn (Builder $q): Builder => $q->where('school_id', $this->kioskSchoolId))
            ->whereDate('start_date', '<=', $attendanceDate)
            ->whereHas('items', fn (Builder $items): Builder => $items
                ->whereNull('deleted_at')
                ->whereNull('formation_completed_at')
                ->whereHas('schedules', fn (Builder $schedules): Builder => $schedules
                    ->withoutGlobalScopes()
                    ->where('weekday', $attendanceDate->dayOfWeek)))
            ->whereHas('student', fn ($query) => $query
                ->whereDate('birth_date', $data['birth_date'])
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName]))
            ->orderBy('contract_number')
            ->pluck('contract_number')
            ->all();

        $this->lookupSubmitted = true;
    }

    public function confirmPresence(MarkStudentAttendanceAtKiosk $markStudentAttendance): void
    {
        $targetSchool = $this->kioskSchoolId ? School::query()->find($this->kioskSchoolId) : null;
        if ($targetSchool && $targetSchool->enforce_school_ip_for_attendance && ! $targetSchool->isClientIpAllowed()) {
            Notification::make()
                ->title('Registro de Presença Bloqueado')
                ->danger()
                ->body('O registro de presença no Totem só é permitido quando o terminal está conectado à rede de internet autorizada da escola.')
                ->persistent()
                ->send();

            $this->form->fill();

            return;
        }

        $data = $this->form->getState();
        $rawInput = trim($data['contract_number'] ?? '');
        $cleanDigits = preg_replace('/\D/', '', $rawInput);

        // 1. Prioridade: Verifica se há presença de Aluno agendada para este identificador (contrato, CPF, e-mail ou usuário)
        $scheduledAttendance = $markStudentAttendance->findScheduledAttendance($rawInput, now(), $this->kioskSchoolId);

        if ($scheduledAttendance === null) {
            // Se o totem tem unidade configurada, verifica se o aluno tem presença em outra unidade neste horário
            if ($this->kioskSchoolId !== null) {
                $otherAttendance = $markStudentAttendance->findScheduledAttendance($rawInput, now(), null);
                if ($otherAttendance !== null) {
                    $otherSchoolName = $otherAttendance->studentSchedule?->contractItem?->contract?->school?->getFilamentName() ?? 'outra unidade';
                    $currentSchoolName = School::query()->find($this->kioskSchoolId)?->getFilamentName() ?? 'esta unidade';

                    Notification::make()
                        ->title('Aula agendada em outra unidade escolar!')
                        ->warning()
                        ->body("Identificamos sua presença agendada para a unidade \"{$otherSchoolName}\". Este terminal está restrito à unidade \"{$currentSchoolName}\". Por favor, registre presença no totem da sua escola.")
                        ->persistent()
                        ->send();

                    $this->form->fill();

                    return;
                }
            }

            // 2. Se não há presença de aluno agendada, verifica se é um Educador tentando auto-login no totem
            $educatorUser = User::query()
                ->withoutTechnicalAdmin()
                ->where(function (Builder $q) use ($rawInput, $cleanDigits): void {
                    $q->whereRaw('LOWER(email) = ?', [mb_strtolower($rawInput)]);
                    if (! empty($cleanDigits)) {
                        $q->orWhereHas('person', fn (Builder $pq) => $pq->whereCpf($cleanDigits));
                    }
                    $q->orWhereHas('person', fn (Builder $pq) => $pq->whereRaw('LOWER(email) = ?', [mb_strtolower($rawInput)])->orWhere('ouro_moderno_usuario', $rawInput));
                })
                ->first();

            if ($educatorUser && $educatorUser->isEducator()) {
                if ($this->kioskSchoolId !== null && ! $educatorUser->isSuperAdmin()) {
                    $hasSchoolAccess = $educatorUser->schools()->where('schools.id', $this->kioskSchoolId)->exists();
                    if (! $hasSchoolAccess) {
                        Notification::make()
                            ->title('Acesso negado para este terminal')
                            ->danger()
                            ->body("O(A) educador(a) {$educatorUser->getFilamentName()} não possui vínculo cadastrado com esta unidade escolar.")
                            ->send();

                        $this->form->fill();

                        return;
                    }
                }

                $resolvedEducatorSchoolId = $this->kioskSchoolId ?? $educatorUser->schools()->first()?->id;

                Notification::make()
                    ->title("Educador(a) reconhecido: {$educatorUser->getFilamentName()}!")
                    ->success()
                    ->body('Iniciando auto-login no Portal do Aluno em modo Educador.')
                    ->send();

                $this->dispatch(
                    'open-lms-autologin',
                    url: route('educator.kiosk-autologin', array_filter([
                        'user' => $educatorUser->id,
                        'school_id' => $resolvedEducatorSchoolId,
                    ])),
                    target: 'AurosoftEducatorPortal',
                    message: "Conectando educador {$educatorUser->getFilamentName()} ao Portal do Aluno...",
                    buttonText: 'Acessar Portal do Aluno (Educador) ➜',
                );

                $this->form->fill();

                return;
            }

            Notification::make()
                ->title('Nenhuma presença prevista para este contrato ou credencial neste horário.')
                ->danger()
                ->send();

            $this->form->fill();

            return;
        }

        $contract = $scheduledAttendance->studentSchedule->contractItem->contract;
        $contract->loadMissing(['student', 'school']);
        $studentName = $contract->student->name;
        $actualContractNumber = $contract->contract_number;

        // Check if there is an active survey not answered by this contract
        $pendingSurvey = SatisfactionSurvey::query()
            ->forSchool($this->kioskSchoolId ?? $contract->school_id)
            ->active()
            ->whereDoesntHave('responses', fn (Builder $query): Builder => $query->where('contract_id', $contract->id))
            ->with(['questions' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->first();

        if ($pendingSurvey !== null && $pendingSurvey->questions->isNotEmpty()) {
            // Activate survey mode
            $this->isAnsweringSurvey = true;
            $this->pendingContractId = $contract->id;
            $this->pendingContractNumber = $actualContractNumber;
            $this->pendingStudentName = $studentName;
            $this->pendingSurveyData = [
                'id' => $pendingSurvey->id,
                'title' => $pendingSurvey->title,
                'description' => $pendingSurvey->description,
                'questions' => $pendingSurvey->questions->map(fn ($q) => [
                    'id' => $q->id,
                    'question' => $q->question,
                    'type' => $q->type,
                    'options' => $q->options,
                    'is_required' => $q->is_required,
                ])->all(),
            ];

            $this->surveyAnswers = [];
            foreach ($this->pendingSurveyData['questions'] as $q) {
                $this->surveyAnswers[$q['id']] = [
                    'rating' => null,
                    'text' => null,
                ];
            }

            return;
        }

        // No survey pending -> mark attendance directly
        $attendance = $markStudentAttendance->execute($actualContractNumber, now(), $contract->school_id);

        if ($attendance !== null) {
            $contractItem = $scheduledAttendance->studentSchedule?->contractItem;
            $isOuro = $this->isEligibleForOuroModerno($contract, $contractItem);
            $isAurosoft = $contract->hasActiveAurosoftLmsCourse($contractItem);
            $schoolLabel = $contract->school?->getFilamentName();

            $notification = Notification::make()
                ->title("Presença registrada, {$studentName}!")
                ->success();

            if ($isAurosoft && ! $isOuro) {
                $notification->body($schoolLabel ? "Unidade {$schoolLabel} • Acesse suas aulas no Portal do Aluno (/aluno)." : 'Acesse suas aulas no Portal do Aluno (/aluno).');
            } elseif ($schoolLabel) {
                $notification->body("Unidade: {$schoolLabel}");
            }

            $notification->send();

            $this->triggerAutoLoginAfterAttendance($contract, $contractItem);
        }

        $this->form->fill();
    }

    public function submitSurveyAndConfirmPresence(MarkStudentAttendanceAtKiosk $markStudentAttendance): void
    {
        if (! $this->isAnsweringSurvey || empty($this->pendingContractNumber)) {
            return;
        }

        // Validate required questions
        foreach ($this->pendingSurveyData['questions'] ?? [] as $q) {
            if ($q['is_required']) {
                $answer = $this->surveyAnswers[$q['id']] ?? null;
                $hasRating = isset($answer['rating']) && $answer['rating'] !== null && $answer['rating'] !== '';
                $hasText = isset($answer['text']) && trim((string) $answer['text']) !== '';

                if (in_array($q['type'], ['rating_5', 'nps_10']) && ! $hasRating) {
                    Notification::make()
                        ->title("Por favor, responda à pergunta: {$q['question']}")
                        ->danger()
                        ->send();

                    return;
                }

                if (in_array($q['type'], ['multiple_choice', 'text']) && ! $hasText) {
                    Notification::make()
                        ->title("Por favor, responda à pergunta: {$q['question']}")
                        ->danger()
                        ->send();

                    return;
                }
            }
        }

        $contractNumber = $this->pendingContractNumber;
        $surveyId = $this->pendingSurveyData['id'];

        $contract = $this->pendingContractId
            ? Contract::withoutGlobalScopes()->with(['student', 'school'])->find($this->pendingContractId)
            : Contract::withoutGlobalScopes()
                ->with(['student', 'school'])
                ->where('contract_number', $contractNumber)
                ->when($this->kioskSchoolId !== null, fn (Builder $q): Builder => $q->where('school_id', $this->kioskSchoolId))
                ->first();
        if (! $contract) {
            $this->cancelSurvey();

            return;
        }

        DB::transaction(function () use ($contract, $surveyId, $contractNumber, $markStudentAttendance): void {
            $response = SatisfactionSurveyResponse::create([
                'school_id' => $contract->school_id ?? $this->kioskSchoolId,
                'satisfaction_survey_id' => $surveyId,
                'contract_id' => $contract->id,
                'student_id' => $contract->student_id,
            ]);

            foreach ($this->pendingSurveyData['questions'] ?? [] as $q) {
                $ans = $this->surveyAnswers[$q['id']] ?? null;
                $ratingValue = null;
                $textValue = null;

                if (in_array($q['type'], ['rating_5', 'nps_10'])) {
                    $ratingValue = isset($ans['rating']) && $ans['rating'] !== null ? (int) $ans['rating'] : null;
                } else {
                    $textValue = isset($ans['text']) ? trim((string) $ans['text']) : null;
                }

                if ($ratingValue !== null || $textValue !== null) {
                    SatisfactionSurveyAnswer::create([
                        'satisfaction_survey_response_id' => $response->id,
                        'satisfaction_survey_question_id' => $q['id'],
                        'rating_value' => $ratingValue,
                        'text_value' => $textValue,
                    ]);
                }
            }

            // Mark attendance
            $attendance = $markStudentAttendance->execute($contractNumber, now(), $contract->school_id);
            if ($attendance) {
                $response->update(['student_attendance_id' => $attendance->id]);
            }
        });

        $studentName = $this->pendingStudentName;

        $contractItem = $contract->items()
            ->whereHas('schedules', fn ($q) => $q->withoutGlobalScopes()->where('weekday', now()->dayOfWeek))
            ->first();
        $isOuro = $this->isEligibleForOuroModerno($contract, $contractItem);
        $isAurosoft = $contract->hasActiveAurosoftLmsCourse($contractItem);
        $schoolLabel = $contract->school?->getFilamentName();

        $notification = Notification::make()
            ->title("Pesquisa enviada e presença registrada, {$studentName}!")
            ->success();

        if ($isAurosoft && ! $isOuro) {
            $notification->body($schoolLabel ? "Unidade {$schoolLabel} • Acesse suas aulas no Portal do Aluno (/aluno)." : 'Acesse suas aulas no Portal do Aluno (/aluno).');
        } elseif ($schoolLabel) {
            $notification->body("Unidade: {$schoolLabel}");
        }

        $notification->send();

        $this->triggerAutoLoginAfterAttendance($contract, $contractItem);

        $this->cancelSurvey();
    }

    public function isEligibleForOuroModerno(Contract $contract, ?ContractItem $scheduledItem = null): bool
    {
        $ouroService = app(OuroModernoService::class)->forSchool($contract->school_id);

        if (! $ouroService->isConfigured()) {
            return false;
        }

        $student = $contract->student;
        if (! $student) {
            return false;
        }

        // Se o aluno NÃO foi migrado para a Ouro Moderno, NUNCA redireciona
        if (! $student->isMigratedToOuroModerno()) {
            return false;
        }

        // Verifica se há cursos cadastrados no contrato ou na formação
        $hasAnyCourses = $contract->items()
            ->where(function (Builder $q) {
                $q->whereHas('contractCourses')
                    ->orWhereHas('formation.courses');
            })
            ->exists();

        if ($hasAnyCourses) {
            // Se o contrato tem cursos cadastrados: SÓ é elegível se o aluno tiver um curso ativo da Ouro Moderno
            return $contract->hasActiveOuroModernoCourse($scheduledItem);
        }

        // Fallback para fixtures ou contratos legados sem catálogo de cursos cadastrado
        return true;
    }

    public function triggerAutoLoginAfterAttendance(Contract $contract, ?ContractItem $scheduledItem = null): void
    {
        $contract->loadMissing('school');
        $schoolLabel = $contract->school?->getFilamentName();

        if ($this->isEligibleForOuroModerno($contract, $scheduledItem)) {
            $this->dispatch(
                'open-lms-autologin',
                url: route('kiosk.lms-autologin', ['contract' => $contract->id]),
                target: 'OuroModernoLMS',
                message: $schoolLabel
                    ? "Conectando você ao LMS Ouro Moderno da unidade {$schoolLabel}..."
                    : 'Conectando você ao seu curso na Ouro Moderno...',
                buttonText: 'Entrar na aula Ouro Moderno ➜',
            );

            return;
        }

        $this->dispatch(
            'open-lms-autologin',
            url: route('student.kiosk-autologin', ['contract' => $contract->id]),
            target: 'AurosoftStudentPortal',
            message: $schoolLabel
                ? "Conectando você ao Portal do Aluno ({$schoolLabel})..."
                : 'Conectando você ao Portal do Aluno...',
            buttonText: 'Acessar Portal do Aluno ➜',
        );
    }

    protected function triggerLmsAutoLoginIfEligible(Contract $contract, ?ContractItem $scheduledItem = null): void
    {
        $this->triggerAutoLoginAfterAttendance($contract, $scheduledItem);
    }

    public function availableSchools(): array
    {
        return School::query()
            ->orderBy('trade_name')
            ->orderBy('legal_name')
            ->get()
            ->map(fn (School $s) => [
                'id' => $s->id,
                'name' => $s->getFilamentName(),
                'trade_name' => $s->trade_name,
                'legal_name' => $s->legal_name,
                'city' => $s->city,
                'state' => $s->state,
            ])
            ->all();
    }

    public function isSupervisorAuthenticated(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        // 1. Super Admin possui autorização irrestrita
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // 2. Validação estritamente por permissão explícita (sem papéis fixos)
        return $user->can('Configure:StudentAttendanceKiosk')
            || $user->can('View:StudentAttendanceKiosk')
            || $user->can('Update:School');
    }

    public function selectKioskSchool(int $schoolId): void
    {
        // Se a unidade já estiver definida e a sessão atual não for de supervisor, bloqueia
        if ($this->kioskSchoolId !== null && ! $this->isSupervisorAuthenticated()) {
            $this->openSchoolSwitcher();

            return;
        }

        if (School::query()->whereKey($schoolId)->exists()) {
            $this->kioskSchoolId = $schoolId;
            $this->showSchoolSwitcherModal = false;
            $this->showSupervisorAuthModal = false;
            cookie()->queue(cookie()->forever('kiosk_school_id', (string) $schoolId));
            $this->loadAnnouncements();
            $this->form->fill();

            $schoolName = School::query()->find($schoolId)?->getFilamentName();

            Notification::make()
                ->title('Terminal configurado com sucesso!')
                ->body("Este totem foi vinculado à unidade {$schoolName}.")
                ->success()
                ->send();
        }
    }

    public function openSchoolSwitcher(): void
    {
        // Se já existe uma escola vinculada e quem está na tela NÃO é colaborador/admin autenticado,
        // exige que o supervisor insira as credenciais para desbloquear
        if ($this->kioskSchoolId !== null && ! $this->isSupervisorAuthenticated()) {
            $this->supervisorLogin = '';
            $this->supervisorPassword = '';
            $this->supervisorAuthError = '';
            $this->showSchoolSwitcherModal = false;
            $this->showSupervisorAuthModal = true;

            return;
        }

        $this->showSupervisorAuthModal = false;
        $this->showSchoolSwitcherModal = true;
    }

    public function closeSchoolSwitcher(): void
    {
        $this->showSchoolSwitcherModal = false;
    }

    public function closeSupervisorAuth(): void
    {
        $this->showSupervisorAuthModal = false;
        $this->supervisorLogin = '';
        $this->supervisorPassword = '';
        $this->supervisorAuthError = '';
    }

    public function authenticateSupervisor(): void
    {
        $this->supervisorAuthError = '';

        if (blank($this->supervisorLogin) || blank($this->supervisorPassword)) {
            $this->supervisorAuthError = 'Por favor, informe seu usuário/e-mail e senha.';

            return;
        }

        $login = trim($this->supervisorLogin);
        $password = (string) $this->supervisorPassword;

        // Se for e-mail comum, autentica direto
        $targetEmail = $login;
        if (! filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $cleanDigits = preg_replace('/\D/', '', $login);
            $resolvedUser = null;

            if (strlen((string) $cleanDigits) === 11) {
                $resolvedUser = User::query()
                    ->whereHas('person', fn ($q) => $q->whereCpf($cleanDigits))
                    ->first();
            }

            if (! $resolvedUser) {
                $resolvedUser = User::query()
                    ->whereHas('person', function ($q) use ($login) {
                        $q->whereRaw('LOWER(name) = ?', [mb_strtolower($login)])
                            ->orWhere('ouro_moderno_usuario', $login);
                    })
                    ->orWhereHas('person.collaborators', function ($q) use ($login) {
                        $q->where('ouro_moderno_usuario', $login);
                    })
                    ->first();
            }

            if ($resolvedUser) {
                $targetEmail = $resolvedUser->email;
            }
        }

        if (Auth::attempt(['email' => $targetEmail, 'password' => $password])) {
            session()->regenerate();

            if (! $this->isSupervisorAuthenticated()) {
                Auth::logout();
                $this->supervisorAuthError = 'Seu usuário não possui permissão para configurar este terminal. Solicite a liberação ao administrador.';

                return;
            }

            $userName = auth()->user()?->getFilamentName() ?? 'Colaborador';
            $this->showSupervisorAuthModal = false;
            $this->supervisorPassword = '';
            $this->supervisorAuthError = '';

            session()->flash('open_school_switcher_modal', true);

            Notification::make()
                ->title('Acesso autorizado')
                ->body("Bem-vindo(a), {$userName}. Selecione a unidade deste terminal.")
                ->success()
                ->send();

            $this->redirect(request()->url(), navigate: false);

            return;
        } else {
            $this->supervisorAuthError = 'Credenciais incorretas ou usuário não encontrado.';
        }
    }

    protected function getViewData(): array
    {
        $pinnedSchool = $this->kioskSchoolId
            ? School::query()->find($this->kioskSchoolId)
            : null;

        $hasMultipleSchools = School::query()->count() > 1;

        $schoolName = $pinnedSchool?->getFilamentName()
            ?? ($hasMultipleSchools ? 'Selecione a Unidade' : School::getSchoolName());

        $schoolLogoUrl = $pinnedSchool?->logo_url
            ?? School::getActiveSchool()?->logo_url
            ?? School::first()?->logo_url;

        return [
            'schoolName' => $schoolName,
            'schoolLogoUrl' => $schoolLogoUrl,
            'pinnedSchool' => $pinnedSchool,
            'hasMultipleSchools' => $hasMultipleSchools,
            'isSupervisor' => $this->isSupervisorAuthenticated(),
            'currentSupervisor' => auth()->user(),
        ];
    }

    public function setAnswerRating(int $questionId, int $value): void
    {
        $this->surveyAnswers[$questionId]['rating'] = $value;
    }

    public function setAnswerText(int $questionId, string $value): void
    {
        $this->surveyAnswers[$questionId]['text'] = $value;
    }

    public function cancelSurvey(): void
    {
        $this->isAnsweringSurvey = false;
        $this->pendingSurveyData = [];
        $this->pendingContractId = null;
        $this->pendingContractNumber = null;
        $this->pendingStudentName = null;
        $this->surveyAnswers = [];
        $this->form->fill();
    }
}
