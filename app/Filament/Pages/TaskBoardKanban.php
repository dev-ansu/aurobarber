<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\School;
use App\Models\TaskBoard;
use App\Models\TaskCard;
use App\Models\TaskChecklist;
use App\Models\TaskColumn;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class TaskBoardKanban extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Quadro de Tarefas (Trello)';

    protected static ?string $title = 'Quadro de Tarefas da Equipe';

    protected static ?string $slug = 'tarefas-trello';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected string $view = 'filament.pages.task-board-kanban';

    public ?int $selectedBoardId = null;

    // Filters
    public string $search = '';

    public string $filterAssignee = '';

    public string $filterSector = '';

    public string $filterPriority = '';

    public string $filterDueDate = '';

    // Card Modal State
    public bool $isCardModalOpen = false;

    public ?int $editingCardId = null;

    public ?int $cardColumnId = null;

    public string $cardTitle = '';

    public ?string $cardDescription = null;

    public string $cardPriority = 'medium';

    public ?string $cardSector = null;

    public ?string $cardDueDate = null;

    public bool $cardAssignedToAll = false;

    /** @var array<int, int|string> */
    public array $cardAssignedUserIds = [];

    public string $newChecklistTitle = '';

    // Board Modal State
    public bool $isBoardModalOpen = false;

    public ?int $editingBoardId = null;

    public string $boardName = '';

    public ?string $boardDescription = null;

    public string $boardColor = 'primary';

    /**
     * 'global' para todas as escolas (school_id = null) ou ID da escola como string
     */
    public string $boardScope = '';

    // Column Modal State
    public bool $isColumnModalOpen = false;

    public ?int $editingColumnId = null;

    public string $columnName = '';

    public string $columnColor = 'gray';

    public bool $columnIsDone = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:TaskBoard') || $user->can('ViewAny:TaskBoard'));
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user instanceof User || ! Schema::hasTable('task_cards')) {
            return null;
        }

        $tenant = Filament::getTenant();
        $schoolId = $tenant instanceof School ? $tenant->id : null;

        $dueOrOverdue = TaskCard::query()
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', today())
            ->whereHas('column', fn (Builder $cq) => $cq->where('is_done_column', false))
            ->whereHas('board', fn (Builder $bq) => $bq->when(
                $schoolId,
                fn (Builder $s) => $s->where(fn (Builder $w) => $w->where('school_id', $schoolId)->orWhereNull('school_id'))
            ))
            ->where(function (Builder $q) use ($user): void {
                $q->where('assigned_to_all', true)
                    ->orWhere('created_by_user_id', $user->id)
                    ->orWhereHas('assignedUsers', fn (Builder $uq) => $uq->where('users.id', $user->id));
            })
            ->count();

        return $dueOrOverdue > 0 ? (string) $dueOrOverdue : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected function currentSchoolId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    public function mount(): void
    {
        $schoolId = $this->currentSchoolId();
        $this->boardScope = $schoolId ? (string) $schoolId : 'global';
        $this->ensureDefaultBoardExists();

        if (! $this->selectedBoardId) {
            $board = $this->accessibleBoardsQuery()
                ->orderByRaw('CASE WHEN school_id = ? THEN 0 ELSE 1 END', [$schoolId ?? 0])
                ->orderBy('is_default', 'desc')
                ->orderBy('id')
                ->first();
            $this->selectedBoardId = $board?->id;
        }
    }

    protected function accessibleBoardsQuery(): Builder
    {
        $schoolId = $this->currentSchoolId();
        $user = auth()->user();
        $isSuperAdmin = $user instanceof User && $user->isSuperAdmin();

        return TaskBoard::query()
            ->with('school')
            ->when(! $isSuperAdmin && $schoolId, function (Builder $q) use ($schoolId): void {
                $q->where(function (Builder $sub) use ($schoolId): void {
                    $sub->where('school_id', $schoolId)
                        ->orWhereNull('school_id');
                });
            })
            ->when($isSuperAdmin && $schoolId, function (Builder $q) use ($schoolId): void {
                // Super admin vê os quadros da unidade atual, os quadros globais e os das demais unidades
                $q->where(function (Builder $sub) use ($schoolId): void {
                    $sub->where('school_id', $schoolId)
                        ->orWhereNull('school_id')
                        ->orWhereNotNull('school_id');
                });
            });
    }

    protected function ensureDefaultBoardExists(): void
    {
        $schoolId = $this->currentSchoolId();

        if (TaskBoard::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->exists()) {
            return;
        }

        $schoolName = Filament::getTenant()?->getFilamentName();

        $defaultBoard = TaskBoard::create([
            'school_id' => $schoolId,
            'name' => $schoolName ? "Quadro Geral — {$schoolName}" : 'Quadro Geral da Escola',
            'description' => 'Acompanhamento de rotinas, pendências e ações da gestão escolar desta unidade.',
            'color' => 'primary',
            'is_default' => true,
            'created_by_user_id' => auth()->id(),
            'sort_order' => 1,
        ]);

        $columns = [
            ['name' => 'A Fazer', 'color' => 'gray', 'sort_order' => 1, 'is_done_column' => false],
            ['name' => 'Em Andamento', 'color' => 'sky', 'sort_order' => 2, 'is_done_column' => false],
            ['name' => 'Aguardando / Revisão', 'color' => 'amber', 'sort_order' => 3, 'is_done_column' => false],
            ['name' => 'Concluído', 'color' => 'emerald', 'sort_order' => 4, 'is_done_column' => true],
        ];

        foreach ($columns as $columnData) {
            $defaultBoard->columns()->create($columnData);
        }
    }

    public function getBoardProperty(): ?TaskBoard
    {
        if (! $this->selectedBoardId) {
            return null;
        }

        return TaskBoard::query()
            ->with(['school', 'columns' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->find($this->selectedBoardId);
    }

    public function boards(): Collection
    {
        $schoolId = $this->currentSchoolId();

        return $this->accessibleBoardsQuery()
            ->orderByRaw('CASE WHEN school_id IS NULL THEN 1 WHEN school_id = ? THEN 0 ELSE 2 END', [$schoolId ?? 0])
            ->orderBy('name')
            ->get();
    }

    /**
     * Opções de escopo de unidade para criação/edição de quadro
     *
     * @return array<string, string>
     */
    public function availableBoardScopes(): array
    {
        $user = auth()->user();
        $currentSchool = Filament::getTenant();
        $options = [];

        if ($currentSchool instanceof School) {
            $options[(string) $currentSchool->id] = "🏢 Somente unidade atual ({$currentSchool->getFilamentName()})";
        }

        $options['global'] = '🌐 Global — Todas as escolas da rede';

        $schools = ($user instanceof User && $user->isSuperAdmin())
            ? School::query()->withoutGlobalScopes()->orderBy('trade_name')->get()
            : ($user instanceof User ? $user->schools()->orderBy('trade_name')->get() : collect());

        foreach ($schools as $school) {
            if ($currentSchool && (int) $school->id === (int) $currentSchool->id) {
                continue;
            }
            $options[(string) $school->id] = "🏫 Outra unidade: {$school->getFilamentName()}";
        }

        return $options;
    }

    public function staffUsers(): Collection
    {
        $boardSchoolId = $this->board?->school_id;

        return User::query()
            ->withoutTechnicalAdmin()
            ->with(['person', 'schools'])
            ->where(function (Builder $q): void {
                $q->whereHas('collaborator', fn (Builder $cq) => $cq->where('active', true))
                    ->orWhereHas('roles', fn (Builder $rq) => $rq->where('name', 'super_admin'));
            })
            ->when($boardSchoolId, function (Builder $q) use ($boardSchoolId): void {
                $q->where(function (Builder $sub) use ($boardSchoolId): void {
                    $sub->whereHas('schools', fn (Builder $sq) => $sq->where('schools.id', $boardSchoolId))
                        ->orWhereHas('roles', fn (Builder $rq) => $rq->where('name', 'super_admin'));
                });
            })
            ->get()
            ->sortBy(fn (User $u) => $u->name ?? $u->email)
            ->values();
    }

    public function getCardsForColumn(TaskColumn $column): Collection
    {
        return TaskCard::query()
            ->where('task_column_id', $column->id)
            ->with(['assignedUsers.person', 'checklists', 'createdByUser.person'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = mb_strtolower(trim($this->search));
                $query->where(function (Builder $sub) use ($search): void {
                    $sub->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($this->filterSector !== '', fn (Builder $q) => $q->where('sector', $this->filterSector))
            ->when($this->filterPriority !== '', fn (Builder $q) => $q->where('priority', $this->filterPriority))
            ->when($this->filterAssignee !== '', function (Builder $query): void {
                if ($this->filterAssignee === 'my_tasks') {
                    $userId = auth()->id();
                    $query->where(function (Builder $sub) use ($userId): void {
                        $sub->where('assigned_to_all', true)
                            ->orWhereHas('assignedUsers', fn (Builder $u) => $u->where('users.id', $userId));
                    });
                } else {
                    $targetUserId = (int) $this->filterAssignee;
                    $query->where(function (Builder $sub) use ($targetUserId): void {
                        $sub->where('assigned_to_all', true)
                            ->orWhereHas('assignedUsers', fn (Builder $u) => $u->where('users.id', $targetUserId));
                    });
                }
            })
            ->when($this->filterDueDate !== '', function (Builder $query): void {
                $today = today();
                if ($this->filterDueDate === 'overdue') {
                    $query->whereDate('due_date', '<', $today)
                        ->whereNull('completed_at');
                } elseif ($this->filterDueDate === 'today') {
                    $query->whereDate('due_date', $today);
                } elseif ($this->filterDueDate === 'this_week') {
                    $query->whereBetween('due_date', [$today, $today->copy()->endOfWeek()]);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function boardStats(): array
    {
        if (! $this->selectedBoardId) {
            return ['total' => 0, 'completed' => 0, 'pending' => 0, 'overdue' => 0];
        }

        $allCards = TaskCard::query()->where('task_board_id', $this->selectedBoardId)->get();
        $total = $allCards->count();
        $completed = $allCards->filter(fn (TaskCard $c) => $c->isCompleted())->count();
        $pending = $total - $completed;
        $overdue = $allCards->filter(fn (TaskCard $c) => $c->isOverdue())->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'overdue' => $overdue,
        ];
    }

    // Drag-and-drop or explicit move card
    public function moveCard(int $cardId, int $targetColumnId): void
    {
        $card = TaskCard::find($cardId);
        $targetColumn = TaskColumn::find($targetColumnId);

        if (! $card || ! $targetColumn || $card->task_board_id !== $targetColumn->task_board_id) {
            return;
        }

        $highestOrder = TaskCard::query()
            ->where('task_column_id', $targetColumnId)
            ->max('sort_order') ?? 0;

        $card->moveToColumn($targetColumn, $highestOrder + 1);

        Notification::make()
            ->title("Cartão movido para \"{$targetColumn->name}\"")
            ->success()
            ->send();

        if ($targetColumn->is_done_column && $card->created_by_user_id && $card->created_by_user_id !== auth()->id()) {
            $creator = User::find($card->created_by_user_id);
            if ($creator) {
                $completedByName = auth()->user()?->person?->name ?? auth()->user()?->name ?? 'Colaborador';
                Notification::make()
                    ->title("Tarefa concluída: \"{$card->title}\"")
                    ->body("A tarefa foi finalizada por {$completedByName}.")
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->actions([
                        Action::make('view')
                            ->label('Ver no Trello')
                            ->url(static::getUrl())
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($creator);
            }
        }
    }

    // Card Modal Operations
    public function openCreateCardModal(?int $columnId = null): void
    {
        $this->resetCardModal();
        $this->cardColumnId = $columnId ?: $this->board?->columns->first()?->id;
        $this->isCardModalOpen = true;
    }

    public function openEditCardModal(int $cardId): void
    {
        $card = TaskCard::with(['assignedUsers', 'checklists'])->find($cardId);
        if (! $card) {
            return;
        }

        $this->editingCardId = $card->id;
        $this->cardColumnId = $card->task_column_id;
        $this->cardTitle = $card->title;
        $this->cardDescription = $card->description;
        $this->cardPriority = $card->priority;
        $this->cardSector = $card->sector;
        $this->cardDueDate = $card->due_date?->format('Y-m-d');
        $this->cardAssignedToAll = (bool) $card->assigned_to_all;
        $this->cardAssignedUserIds = $card->assignedUsers->pluck('id')->all();
        $this->newChecklistTitle = '';
        $this->isCardModalOpen = true;
    }

    public function saveCard(): void
    {
        $this->validate([
            'cardTitle' => ['required', 'string', 'max:255'],
            'cardColumnId' => ['required', 'exists:task_columns,id'],
            'cardPriority' => ['required', 'in:low,medium,high,urgent'],
            'cardSector' => ['nullable', 'string', 'max:50'],
            'cardDueDate' => ['nullable', 'date'],
        ]);

        $column = TaskColumn::findOrFail($this->cardColumnId);

        $isNew = ! $this->editingCardId;

        if ($this->editingCardId) {
            $card = TaskCard::findOrFail($this->editingCardId);
            $card->update([
                'task_column_id' => $column->id,
                'title' => trim($this->cardTitle),
                'description' => $this->cardDescription ? trim($this->cardDescription) : null,
                'priority' => $this->cardPriority,
                'sector' => $this->cardSector ?: null,
                'due_date' => $this->cardDueDate ?: null,
                'assigned_to_all' => $this->cardAssignedToAll,
            ]);

            if ($column->is_done_column && $card->completed_at === null) {
                $card->update(['completed_at' => now()]);
            } elseif (! $column->is_done_column) {
                $card->update(['completed_at' => null]);
            }

            Notification::make()->title('Cartão atualizado com sucesso!')->success()->send();
        } else {
            $highestOrder = TaskCard::query()->where('task_column_id', $column->id)->max('sort_order') ?? 0;

            $card = TaskCard::create([
                'task_board_id' => $column->task_board_id,
                'task_column_id' => $column->id,
                'title' => trim($this->cardTitle),
                'description' => $this->cardDescription ? trim($this->cardDescription) : null,
                'priority' => $this->cardPriority,
                'sector' => $this->cardSector ?: null,
                'due_date' => $this->cardDueDate ?: null,
                'assigned_to_all' => $this->cardAssignedToAll,
                'sort_order' => $highestOrder + 1,
                'created_by_user_id' => auth()->id(),
                'completed_at' => $column->is_done_column ? now() : null,
            ]);

            $this->editingCardId = $card->id;
            Notification::make()->title('Cartão criado com sucesso!')->success()->send();
        }

        if ($this->cardAssignedToAll) {
            $card->assignedUsers()->sync([]);
        } else {
            $card->assignedUsers()->sync($this->cardAssignedUserIds);
        }

        $this->notifyAssignedUsers($card, $isNew);

        $this->isCardModalOpen = false;
    }

    protected function notifyAssignedUsers(TaskCard $card, bool $isNew = true): void
    {
        $creator = auth()->user();
        $creatorName = $creator ? ($creator->person?->name ?? $creator->name ?? 'Gestão') : 'Gestão';
        $actionUrl = static::getUrl();

        $boardSchoolId = $card->board?->school_id;
        $scopeTag = $boardSchoolId
            ? ($card->board?->school?->getFilamentName() ?? 'Unidade')
            : 'Rede Global';

        $prefix = $isNew ? 'Nova tarefa atribuída' : 'Tarefa atualizada';
        $title = $card->assigned_to_all
            ? "[{$scopeTag}] {$prefix} para a equipe: \"{$card->title}\""
            : "[{$scopeTag}] {$prefix}: \"{$card->title}\"";

        $details = [];
        if ($sect = $card->sectorDetails()) {
            $details[] = "Setor: {$sect['label']}";
        }
        $prio = $card->priorityDetails();
        $details[] = "Prioridade: {$prio['label']}";
        if ($card->due_date) {
            $details[] = "Prazo: {$card->due_date->format('d/m/Y')}";
        }
        $body = "Por {$creatorName} • ".implode(' • ', $details);

        if ($card->assigned_to_all) {
            $recipients = User::query()
                ->when($creator, fn ($q) => $q->where('id', '!=', $creator->id))
                ->when($boardSchoolId, fn (Builder $q) => $q->whereHas('schools', fn (Builder $sq) => $sq->where('schools.id', $boardSchoolId)))
                ->get();
        } else {
            $recipients = $card->assignedUsers()
                ->when($creator, fn ($q) => $q->where('users.id', '!=', $creator->id))
                ->get();
        }

        foreach ($recipients as $recipient) {
            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->actions([
                    Action::make('view')
                        ->label('Ver no Trello')
                        ->url($actionUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($recipient);
        }
    }

    public function deleteCard(int $cardId): void
    {
        $card = TaskCard::find($cardId);
        if ($card) {
            $card->delete();
            $this->isCardModalOpen = false;
            Notification::make()->title('Cartão excluído com sucesso!')->success()->send();
        }
    }

    public function addChecklistItem(): void
    {
        if (! $this->editingCardId || blank($this->newChecklistTitle)) {
            return;
        }

        $card = TaskCard::find($this->editingCardId);
        if (! $card) {
            return;
        }

        $highestOrder = $card->checklists()->max('sort_order') ?? 0;

        $card->checklists()->create([
            'title' => trim($this->newChecklistTitle),
            'sort_order' => $highestOrder + 1,
        ]);

        $this->newChecklistTitle = '';
    }

    public function toggleChecklistItem(int $itemId): void
    {
        $item = TaskChecklist::find($itemId);
        if ($item) {
            $item->toggleComplete(auth()->id());
        }
    }

    public function deleteChecklistItem(int $itemId): void
    {
        $item = TaskChecklist::find($itemId);
        if ($item) {
            $item->delete();
        }
    }

    public function resetCardModal(): void
    {
        $this->editingCardId = null;
        $this->cardColumnId = null;
        $this->cardTitle = '';
        $this->cardDescription = null;
        $this->cardPriority = 'medium';
        $this->cardSector = null;
        $this->cardDueDate = null;
        $this->cardAssignedToAll = false;
        $this->cardAssignedUserIds = [];
        $this->newChecklistTitle = '';
    }

    // Board Modal Operations
    public function openCreateBoardModal(): void
    {
        $this->editingBoardId = null;
        $this->boardName = '';
        $this->boardDescription = null;
        $this->boardColor = 'primary';
        $this->boardScope = $this->currentSchoolId() ? (string) $this->currentSchoolId() : 'global';
        $this->isBoardModalOpen = true;
    }

    public function openEditBoardModal(int $boardId): void
    {
        $board = TaskBoard::find($boardId);
        if (! $board) {
            return;
        }

        $this->editingBoardId = $board->id;
        $this->boardName = $board->name;
        $this->boardDescription = $board->description;
        $this->boardColor = $board->color ?? 'primary';
        $this->boardScope = $board->school_id ? (string) $board->school_id : 'global';
        $this->isBoardModalOpen = true;
    }

    public function saveBoard(): void
    {
        $this->validate([
            'boardName' => ['required', 'string', 'max:255'],
            'boardScope' => ['required', 'string'],
        ]);

        $resolvedSchoolId = $this->boardScope === 'global' || $this->boardScope === ''
            ? null
            : (int) $this->boardScope;

        if ($this->editingBoardId) {
            $board = TaskBoard::findOrFail($this->editingBoardId);
            $board->update([
                'school_id' => $resolvedSchoolId,
                'name' => trim($this->boardName),
                'description' => $this->boardDescription ? trim($this->boardDescription) : null,
                'color' => $this->boardColor,
            ]);
            Notification::make()->title('Quadro atualizado com sucesso!')->success()->send();
        } else {
            $board = TaskBoard::create([
                'school_id' => $resolvedSchoolId,
                'name' => trim($this->boardName),
                'description' => $this->boardDescription ? trim($this->boardDescription) : null,
                'color' => $this->boardColor,
                'created_by_user_id' => auth()->id(),
            ]);

            // Add standard default columns to new board
            $board->columns()->createMany([
                ['name' => 'A Fazer', 'color' => 'gray', 'sort_order' => 1, 'is_done_column' => false],
                ['name' => 'Em Andamento', 'color' => 'sky', 'sort_order' => 2, 'is_done_column' => false],
                ['name' => 'Concluído', 'color' => 'emerald', 'sort_order' => 3, 'is_done_column' => true],
            ]);

            $this->selectedBoardId = $board->id;
            Notification::make()->title('Novo quadro criado com sucesso!')->success()->send();
        }

        $this->isBoardModalOpen = false;
    }

    public function deleteBoard(int $boardId): void
    {
        $board = TaskBoard::findOrFail($boardId);

        if ($this->accessibleBoardsQuery()->count() <= 1) {
            Notification::make()->title('Não é possível excluir o único quadro existente.')->danger()->send();

            return;
        }

        $board->delete();

        $firstBoard = $this->accessibleBoardsQuery()->first();
        $this->selectedBoardId = $firstBoard?->id;

        Notification::make()->title('Quadro excluído com sucesso!')->success()->send();
    }

    // Column Modal Operations
    public function openCreateColumnModal(): void
    {
        $this->editingColumnId = null;
        $this->columnName = '';
        $this->columnColor = 'gray';
        $this->columnIsDone = false;
        $this->isColumnModalOpen = true;
    }

    public function saveColumn(): void
    {
        if (! $this->selectedBoardId) {
            return;
        }

        $this->validate([
            'columnName' => ['required', 'string', 'max:100'],
            'columnColor' => ['required', 'string'],
        ]);

        if ($this->editingColumnId) {
            $column = TaskColumn::findOrFail($this->editingColumnId);
            $column->update([
                'name' => trim($this->columnName),
                'color' => $this->columnColor,
                'is_done_column' => $this->columnIsDone,
            ]);
            Notification::make()->title('Coluna atualizada com sucesso!')->success()->send();
        } else {
            $board = TaskBoard::findOrFail($this->selectedBoardId);
            $highestOrder = $board->columns()->max('sort_order') ?? 0;

            $board->columns()->create([
                'name' => trim($this->columnName),
                'color' => $this->columnColor,
                'sort_order' => $highestOrder + 1,
                'is_done_column' => $this->columnIsDone,
            ]);
            Notification::make()->title('Coluna adicionada com sucesso!')->success()->send();
        }

        $this->isColumnModalOpen = false;
    }

    public function deleteColumn(int $columnId): void
    {
        $column = TaskColumn::findOrFail($columnId);
        $column->delete();

        Notification::make()->title('Coluna excluída com sucesso!')->success()->send();
    }
}
