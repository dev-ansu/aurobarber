<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\LmsStudentLessonProgress;
use App\Models\Person;
use App\Models\SchoolAppointment;
use App\Models\TaskCard;
use App\Models\TaskColumn;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class SchoolAppointmentsCalendar extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Agenda de Compromissos';

    protected static ?string $title = 'Minha Agenda & Compromissos';

    protected static ?string $slug = 'agenda-compromissos';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected string $view = 'filament.pages.school-appointments-calendar';

    public string $filterPeriod = 'week'; // today, week, month, all

    public string $filterScope = 'for_me'; // for_me (padrão pessoal + equipe), mine, shared, students, all

    public string $filterType = '';

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public string $appointmentTitle = '';

    public string $type = SchoolAppointment::TYPE_GERAL;

    public string $priority = SchoolAppointment::PRIORITY_MEDIA;

    public string $date = '';

    public string $start_time = '';

    public string $end_time = '';

    public bool $all_day = false;

    public string $location = '';

    public string $description = '';

    public ?int $assigned_to_user_id = null;

    public bool $is_shared_with_all = false;

    /**
     * 'current' = unidade atual, 'global' = pessoal/todas as unidades (school_id = null)
     */
    public string $appointmentScope = 'current';

    public ?int $student_id = null;

    public ?int $contract_id = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:SchoolAppointmentsCalendar') || $user->can('page_SchoolAppointmentsCalendar'));
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if (! $user instanceof User || ! Schema::hasTable('school_appointments')) {
            return null;
        }

        $tenantId = Filament::getTenant()?->id;

        $count = SchoolAppointment::query()
            ->where('status', SchoolAppointment::STATUS_SCHEDULED)
            ->whereDate('date', '<=', today())
            ->forUserOrShared((int) $user->id)
            ->when($tenantId, fn (Builder $q) => $q->where(fn (Builder $s) => $s->where('school_id', $tenantId)->orWhereNull('school_id')))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function mount(): void
    {
        $this->date = today()->toDateString();
        $this->start_time = '09:00';
        $this->end_time = '10:00';
        $this->assigned_to_user_id = Auth::id();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->date = today()->toDateString();
        $this->start_time = '09:00';
        $this->end_time = '10:00';
        $this->assigned_to_user_id = Auth::id();
        $this->appointmentScope = 'current';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $appointment = SchoolAppointment::findOrFail($id);

        $this->editingId = $appointment->id;
        $this->appointmentTitle = $appointment->title;
        $this->type = $appointment->type;
        $this->priority = $appointment->priority;
        $this->date = $appointment->date->toDateString();
        $this->start_time = $appointment->start_time ? Carbon::parse($appointment->start_time)->format('H:i') : '';
        $this->end_time = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('H:i') : '';
        $this->all_day = (bool) $appointment->all_day;
        $this->location = (string) $appointment->location;
        $this->description = (string) $appointment->description;
        $this->assigned_to_user_id = $appointment->assigned_to_user_id;
        $this->is_shared_with_all = (bool) $appointment->is_shared_with_all;
        $this->appointmentScope = $appointment->school_id === null ? 'global' : 'current';
        $this->student_id = $appointment->student_id;
        $this->contract_id = $appointment->contract_id;

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->appointmentTitle = '';
        $this->type = SchoolAppointment::TYPE_GERAL;
        $this->priority = SchoolAppointment::PRIORITY_MEDIA;
        $this->date = today()->toDateString();
        $this->start_time = '';
        $this->end_time = '';
        $this->all_day = false;
        $this->location = '';
        $this->description = '';
        $this->assigned_to_user_id = Auth::id();
        $this->is_shared_with_all = false;
        $this->appointmentScope = 'current';
        $this->student_id = null;
        $this->contract_id = null;
    }

    public function saveAppointment(): void
    {
        $this->validate([
            'appointmentTitle' => 'required|string|max:255',
            'type' => 'required|string|max:40',
            'priority' => 'required|string|max:20',
            'date' => 'required|date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'location' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'student_id' => 'nullable|exists:people,id',
            'contract_id' => 'nullable|exists:contracts,id',
        ]);

        $userId = Auth::id() ?? 1;
        $tenantId = Filament::getTenant()?->id;
        $resolvedSchoolId = $this->appointmentScope === 'global' ? null : $tenantId;

        $data = [
            'school_id' => $resolvedSchoolId,
            'title' => $this->appointmentTitle,
            'type' => $this->type,
            'priority' => $this->priority,
            'date' => $this->date,
            'start_time' => $this->all_day ? null : ($this->start_time ?: null),
            'end_time' => $this->all_day ? null : ($this->end_time ?: null),
            'all_day' => $this->all_day,
            'location' => $this->location ?: null,
            'description' => $this->description ?: null,
            'assigned_to_user_id' => $this->assigned_to_user_id ?: $userId,
            'is_shared_with_all' => $this->is_shared_with_all,
            'student_id' => $this->student_id,
            'contract_id' => $this->contract_id,
        ];

        if ($this->editingId) {
            $appointment = SchoolAppointment::findOrFail($this->editingId);
            $appointment->update($data);
            $msg = 'Compromisso atualizado com sucesso!';
        } else {
            $data['created_by_user_id'] = $userId;
            $data['status'] = SchoolAppointment::STATUS_SCHEDULED;
            $appointment = SchoolAppointment::create($data);
            $msg = 'Compromisso agendado com sucesso!';

            $this->notifyParticipants($appointment);
        }

        Notification::make()
            ->title($msg)
            ->success()
            ->send();

        $this->closeModal();
    }

    private function notifyParticipants(SchoolAppointment $appointment): void
    {
        $creatorName = Auth::user()?->name ?? 'Aurosoft';
        $timeStr = $appointment->formatted_time;
        $dateStr = $appointment->date->format('d/m/Y');

        $notification = Notification::make()
            ->title("📅 Novo compromisso: {$appointment->title}")
            ->body("{$appointment->type_label} em {$dateStr} ({$timeStr}) por {$creatorName}.")
            ->actions([
                Action::make('view')
                    ->label('Ver na Agenda')
                    ->url(static::getUrl()),
            ]);

        if ($appointment->is_shared_with_all) {
            $allStaff = User::query()
                ->where('id', '!=', Auth::id())
                ->when($appointment->school_id, fn (Builder $q) => $q->whereHas('schools', fn (Builder $sq) => $sq->where('schools.id', $appointment->school_id)))
                ->get();
            $notification->sendToDatabase($allStaff);
        } elseif ($appointment->assigned_to_user_id && $appointment->assigned_to_user_id !== Auth::id()) {
            $assignee = User::find($appointment->assigned_to_user_id);
            if ($assignee) {
                $notification->sendToDatabase($assignee);
            }
        }
    }

    public function markAsCompleted(int $id): void
    {
        $appointment = SchoolAppointment::findOrFail($id);
        $appointment->update([
            'status' => SchoolAppointment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        Notification::make()
            ->title('Compromisso marcado como realizado!')
            ->success()
            ->send();
    }

    public function cancelAppointment(int $id): void
    {
        $appointment = SchoolAppointment::findOrFail($id);
        $appointment->update([
            'status' => SchoolAppointment::STATUS_CANCELED,
        ]);

        Notification::make()
            ->title('Compromisso cancelado.')
            ->warning()
            ->send();
    }

    public function deleteAppointment(int $id): void
    {
        $appointment = SchoolAppointment::findOrFail($id);
        $appointment->delete();

        Notification::make()
            ->title('Compromisso excluído com sucesso.')
            ->success()
            ->send();
    }

    /**
     * @return Collection<string, Collection<int, SchoolAppointment>>
     */
    public function appointmentsGroupedByDate(): Collection
    {
        $userId = Auth::id() ?? 1;
        $tenantId = Filament::getTenant()?->id;

        $query = SchoolAppointment::query()
            ->with(['school', 'assignedTo.person', 'createdBy.person', 'student', 'contract'])
            ->when($tenantId, function (Builder $q) use ($tenantId, $userId): void {
                // Exibe compromissos desta unidade, compromissos globais (school_id = null)
                // ou compromissos pessoais atribuídos diretamente ao usuário logado
                $q->where(function (Builder $sub) use ($tenantId, $userId): void {
                    $sub->where('school_id', $tenantId)
                        ->orWhereNull('school_id')
                        ->orWhere('assigned_to_user_id', $userId);
                });
            })
            ->when($this->filterScope === 'for_me', fn (Builder $q) => $q->forUserOrShared($userId))
            ->when($this->filterScope === 'mine', fn (Builder $q) => $q->where(function (Builder $sub) use ($userId): void {
                $sub->where('assigned_to_user_id', $userId)
                    ->orWhere('created_by_user_id', $userId);
            }))
            ->when($this->filterScope === 'shared', fn (Builder $q) => $q->where('is_shared_with_all', true))
            ->when($this->filterScope === 'students', fn (Builder $q) => $q->whereNotNull('student_id'))
            ->when($this->filterType !== '', fn (Builder $q) => $q->where('type', $this->filterType))
            ->when($this->search !== '', fn (Builder $q) => $q->where(function (Builder $sub) {
                $sub->where('title', 'like', "%{$this->search}%")
                    ->orWhere('location', 'like', "%{$this->search}%")
                    ->orWhereHas('student', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"));
            }));

        // Período
        $today = today();
        if ($this->filterPeriod === 'today') {
            $query->whereDate('date', $today);
        } elseif ($this->filterPeriod === 'week') {
            $query->whereBetween('date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()->addDays(7)]);
        } elseif ($this->filterPeriod === 'month') {
            $query->whereBetween('date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()]);
        }

        return $query
            ->orderBy('date', 'asc')
            ->orderByRaw('CASE WHEN all_day THEN 0 ELSE 1 END')
            ->orderBy('start_time', 'asc')
            ->get()
            ->groupBy(fn (SchoolAppointment $app) => $app->date->toDateString());
    }

    /**
     * Retorna a lista de tarefas pendentes (To-Do list) do usuário sincronizadas do Mini-Trello
     * (tanto dos quadros da unidade atual quanto dos quadros globais da rede)
     *
     * @return Collection<int, TaskCard>
     */
    public function pendingTasks(): Collection
    {
        $userId = Auth::id() ?? 1;
        $tenantId = Filament::getTenant()?->id;

        return TaskCard::query()
            ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
            ->whereHas('board', function (Builder $bq) use ($tenantId): void {
                $bq->when($tenantId, function (Builder $sub) use ($tenantId): void {
                    $sub->where('school_id', $tenantId)
                        ->orWhereNull('school_id');
                });
            })
            ->where(function (Builder $q) use ($userId) {
                $q->where('assigned_to_all', true)
                    ->orWhere('created_by_user_id', $userId)
                    ->orWhereHas('assignedUsers', fn ($aq) => $aq->where('users.id', $userId));
            })
            ->with(['checklists', 'column', 'board.school'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'urgente' THEN 1 WHEN 'high' THEN 2 WHEN 'alta' THEN 2 WHEN 'medium' THEN 3 WHEN 'media' THEN 3 ELSE 4 END")
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();
    }

    public function completeTaskCard(int $cardId): void
    {
        $card = TaskCard::findOrFail($cardId);
        $doneColumn = TaskColumn::where('task_board_id', $card->task_board_id)->where('is_done_column', true)->first();

        if ($doneColumn) {
            $card->update([
                'task_column_id' => $doneColumn->id,
                'completed_at' => now(),
            ]);

            Notification::make()
                ->title("Tarefa '{$card->title}' concluída!")
                ->success()
                ->send();
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function staffUsers(): Collection
    {
        $tenantId = Filament::getTenant()?->id;

        return User::query()
            ->withoutTechnicalAdmin()
            ->with('person')
            ->where(function (Builder $q): void {
                $q->whereHas('collaborator', fn (Builder $cq) => $cq->where('active', true))
                    ->orWhereHas('roles', fn (Builder $rq) => $rq->where('name', 'super_admin'));
            })
            ->when($tenantId, function (Builder $q) use ($tenantId): void {
                $q->where(function (Builder $sub) use ($tenantId): void {
                    $sub->whereHas('schools', fn (Builder $sq) => $sq->where('schools.id', $tenantId))
                        ->orWhereHas('roles', fn (Builder $rq) => $rq->where('name', 'super_admin'));
                });
            })
            ->get()
            ->sortBy(fn ($u) => $u->name ?? $u->email)
            ->values();
    }

    /**
     * @return Collection<int, Person>
     */
    public function studentsList(): Collection
    {
        $tenantId = Filament::getTenant()?->id;

        return Person::query()
            ->whereHas('contracts', fn (Builder $cq) => $cq->withoutGlobalScopes()->when($tenantId, fn ($q) => $q->where('school_id', $tenantId)))
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, Contract>
     */
    public function contractsList(): Collection
    {
        $tenantId = Filament::getTenant()?->id;

        return Contract::query()
            ->withoutGlobalScopes()
            ->when($tenantId, fn (Builder $q) => $q->where('school_id', $tenantId))
            ->where('status', 'active')
            ->with('student')
            ->orderBy('contract_number')
            ->limit(50)
            ->get();
    }

    /**
     * Retorna chamados de ajuda ativos feitos por alunos no LMS Player nesta unidade
     *
     * @return Collection<int, LmsStudentLessonProgress>
     */
    public function pendingEducatorHelpCalls(): Collection
    {
        $tenantId = Filament::getTenant()?->id;

        return LmsStudentLessonProgress::query()
            ->where('help_requested', true)
            ->whereHas('contract', fn (Builder $cq) => $cq->withoutGlobalScopes()->when($tenantId, fn ($q) => $q->where('school_id', $tenantId)))
            ->with(['contract.student', 'lesson'])
            ->orderBy('help_requested_at', 'asc')
            ->get();
    }

    public function resolveEducatorHelp(int $progressId): void
    {
        $progress = LmsStudentLessonProgress::find($progressId);
        if ($progress) {
            $progress->update([
                'help_requested' => false,
                'help_requested_at' => null,
            ]);

            Notification::make()
                ->title('Chamado do aluno atendido com sucesso!')
                ->success()
                ->send();
        }
    }
}
