<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\School;
use App\Models\SchoolAppointment;
use App\Models\SchoolMessage;
use App\Models\TaskBoard;
use App\Models\TaskCard;
use App\Models\TaskColumn;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use UnitEnum;

class SchoolMessenger extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Escola';

    protected static ?string $navigationLabel = 'Comunicação da Rede';

    protected static ?string $title = 'Chat da Rede & Central de Transferências';

    protected static ?string $slug = 'comunicacao-da-rede';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.school-messenger';

    public string $sidebarTab = 'team'; // 'team' (WhatsApp 1-a-1), 'channels' (Unidades/Mural), 'threads' (Comunicados/Transferências)

    public string $activeBox = 'inbox';

    public string $categoryFilter = 'all';

    public string $search = '';

    public ?int $selectedMessageId = null;

    public ?int $selectedChatUserId = null;

    public ?int $selectedChannelSchoolId = null; // null = Mural Geral quando em modo canal

    public string $chatMode = 'thread'; // 'direct_user', 'school_channel', 'thread'

    public string $directMessageText = '';

    public bool $showComposeModal = false;

    public ?string $recipientSchoolId = '';

    public ?int $recipientUserId = null;

    public string $composeCategory = 'general';

    public string $composeSubject = '';

    public string $composeBody = '';

    public ?int $composeContractId = null;

    public string $replyBody = '';

    // Context menu -> Compromisso (Minha Agenda)
    public bool $showAppointmentModal = false;

    public ?int $contextMessageId = null;

    public string $apptTitle = '';

    public string $apptDescription = '';

    public string $apptDate = '';

    public string $apptStartTime = '09:00';

    public string $apptEndTime = '10:00';

    public bool $apptAllDay = false;

    public string $apptPriority = SchoolAppointment::PRIORITY_MEDIA;

    public string $apptType = SchoolAppointment::TYPE_GERAL;

    public string $apptScope = 'current'; // 'current' | 'global'

    public ?int $apptContractId = null;

    public ?int $apptStudentId = null;

    // Context menu -> To-Do (Quadro de Tarefas)
    public bool $showTaskModal = false;

    public string $taskTitle = '';

    public string $taskDescription = '';

    public ?int $taskBoardId = null;

    public ?int $taskColumnId = null;

    public string $taskPriority = 'medium';

    public ?string $taskDueDate = null;

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! Schema::hasTable('school_messages')) {
            return null;
        }

        $tenant = Filament::getTenant();
        if (! $tenant instanceof School) {
            return null;
        }

        $userId = Auth::id();
        $hasRecipientUserColumn = Schema::hasColumn('school_messages', 'recipient_user_id');

        $unread = SchoolMessage::query()
            ->withoutGlobalScopes()
            ->rootMessages()
            ->forSchoolInbox((int) $tenant->id, $hasRecipientUserColumn ? $userId : null)
            ->whereNull('read_at')
            ->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function mount(): void
    {
        if (Auth::check() && method_exists(Auth::user(), 'touchOnlinePresence')) {
            Auth::user()->touchOnlinePresence();
        }

        if (! Schema::hasTable('school_messages')) {
            return;
        }

        $firstMessage = $this->getMessagesQuery()->first();
        if ($firstMessage) {
            $this->selectMessage($firstMessage->id);
        }
    }

    public function refreshChat(): void
    {
        if (Auth::check() && method_exists(Auth::user(), 'touchOnlinePresence')) {
            Auth::user()->touchOnlinePresence();
        }

        if ($this->chatMode === 'direct_user' && $this->selectedChatUserId) {
            $this->markDirectChatAsRead($this->selectedChatUserId);
        }
    }

    public function getCurrentSchool(): ?School
    {
        $tenant = Filament::getTenant();
        if ($tenant instanceof School) {
            return $tenant;
        }

        return School::query()->withoutGlobalScopes()->orderBy('id')->first();
    }

    public function setSidebarTab(string $tab): void
    {
        $this->sidebarTab = $tab;

        if ($tab === 'threads') {
            $this->chatMode = 'thread';
        }
    }

    public function setBox(string $box): void
    {
        $this->activeBox = $box;
        $this->sidebarTab = 'threads';
        $this->chatMode = 'thread';
        $first = $this->getMessagesQuery()->first();
        $this->selectedMessageId = $first?->id;
    }

    public function openDirectChatWithUser(int $userId): void
    {
        $this->selectedChatUserId = $userId;
        $this->chatMode = 'direct_user';
        $this->directMessageText = '';
        $this->markDirectChatAsRead($userId);
    }

    public function openSchoolChannel(?int $schoolId = null): void
    {
        $this->selectedChannelSchoolId = $schoolId;
        $this->chatMode = 'school_channel';
        $this->directMessageText = '';

        $currentSchool = $this->getCurrentSchool();
        if ($schoolId && $currentSchool) {
            SchoolMessage::query()
                ->withoutGlobalScopes()
                ->whereNull('recipient_user_id')
                ->where('sender_school_id', $schoolId)
                ->where('recipient_school_id', $currentSchool->id)
                ->whereNull('read_at')
                ->update([
                    'read_at' => now(),
                    'read_by_user_id' => Auth::id(),
                ]);
        }
    }

    private function markDirectChatAsRead(int $otherUserId): void
    {
        if (! Schema::hasColumn('school_messages', 'recipient_user_id')) {
            return;
        }

        SchoolMessage::query()
            ->withoutGlobalScopes()
            ->where('sender_user_id', $otherUserId)
            ->where('recipient_user_id', Auth::id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'read_by_user_id' => Auth::id(),
            ]);
    }

    public function sendDirectMessage(): void
    {
        if (blank($this->directMessageText)) {
            return;
        }

        $currentSchool = $this->getCurrentSchool();
        $text = trim($this->directMessageText);

        if ($this->chatMode === 'direct_user' && $this->selectedChatUserId) {
            $recipientUser = User::query()->with('schools')->find($this->selectedChatUserId);
            $recipientSchoolId = $recipientUser?->schools->first()?->id ?? $currentSchool?->id;

            SchoolMessage::query()->create([
                'sender_school_id' => $currentSchool?->id,
                'recipient_school_id' => $recipientSchoolId,
                'sender_user_id' => Auth::id(),
                'recipient_user_id' => $this->selectedChatUserId,
                'category' => 'chat',
                'subject' => 'Chat Direto',
                'body' => $text,
            ]);

            $this->directMessageText = '';

            return;
        }

        if ($this->chatMode === 'school_channel') {
            SchoolMessage::query()->create([
                'sender_school_id' => $currentSchool?->id,
                'recipient_school_id' => $this->selectedChannelSchoolId,
                'sender_user_id' => Auth::id(),
                'recipient_user_id' => null,
                'category' => 'general',
                'subject' => $this->selectedChannelSchoolId ? 'Mensagem entre Unidades' : 'Mural Geral da Rede',
                'body' => $text,
            ]);

            $this->directMessageText = '';
        }
    }

    public function selectMessage(int $messageId): void
    {
        $this->selectedMessageId = $messageId;
        $this->chatMode = 'thread';
        $this->replyBody = '';

        $message = SchoolMessage::query()->withoutGlobalScopes()->find($messageId);
        $currentSchool = $this->getCurrentSchool();

        if ($message && $message->read_at === null) {
            $isAddressedToMe = ($message->recipient_user_id && (int) $message->recipient_user_id === (int) Auth::id())
                || ($currentSchool && (int) $message->recipient_school_id === (int) $currentSchool->id);

            if ($isAddressedToMe) {
                $message->markAsRead(Auth::id());
            }
        }
    }

    public function openCompose(?string $defaultCategory = 'general', ?int $defaultRecipientId = null): void
    {
        $this->composeCategory = $defaultCategory ?: 'general';
        $this->recipientSchoolId = $defaultRecipientId ? (string) $defaultRecipientId : '';
        $this->recipientUserId = null;
        $this->composeSubject = '';
        $this->composeBody = '';
        $this->composeContractId = null;
        $this->showComposeModal = true;
    }

    public function sendMessage(): void
    {
        $this->validate([
            'composeCategory' => ['required', 'string'],
            'composeSubject' => ['required', 'string', 'min:3', 'max:255'],
            'composeBody' => ['required', 'string', 'min:3'],
        ], [
            'composeSubject.required' => 'Informe o assunto da mensagem.',
            'composeBody.required' => 'Escreva o conteúdo da mensagem.',
        ]);

        $currentSchool = $this->getCurrentSchool();
        $recipientId = filled($this->recipientSchoolId) ? (int) $this->recipientSchoolId : null;

        $message = SchoolMessage::query()->create([
            'sender_school_id' => $currentSchool?->id,
            'recipient_school_id' => $recipientId,
            'sender_user_id' => Auth::id(),
            'recipient_user_id' => $this->recipientUserId ?: null,
            'contract_id' => $this->composeContractId ?: null,
            'category' => $this->composeCategory,
            'subject' => trim($this->composeSubject),
            'body' => trim($this->composeBody),
        ]);

        $this->showComposeModal = false;
        $this->composeSubject = '';
        $this->composeBody = '';
        $this->composeContractId = null;
        $this->recipientUserId = null;
        $this->chatMode = 'thread';
        $this->selectedMessageId = $message->id;

        Notification::make()
            ->title('Mensagem criptografada enviada!')
            ->body($recipientId ? 'A unidade de destino recebeu o comunicado.' : 'Comunicado transmitido para todas as escolas da rede.')
            ->success()
            ->send();
    }

    public function sendReply(): void
    {
        if (! $this->selectedMessageId || blank($this->replyBody)) {
            return;
        }

        $parent = SchoolMessage::query()->withoutGlobalScopes()->find($this->selectedMessageId);
        if (! $parent) {
            return;
        }

        $currentSchool = $this->getCurrentSchool();

        $replyRecipientId = ((int) $parent->sender_school_id === (int) $currentSchool?->id)
            ? $parent->recipient_school_id
            : $parent->sender_school_id;

        $replyUserId = ((int) $parent->sender_user_id === (int) Auth::id())
            ? $parent->recipient_user_id
            : $parent->sender_user_id;

        SchoolMessage::query()->create([
            'sender_school_id' => $currentSchool?->id,
            'recipient_school_id' => $replyRecipientId,
            'sender_user_id' => Auth::id(),
            'recipient_user_id' => $parent->recipient_user_id ? $replyUserId : null,
            'parent_id' => $parent->id,
            'contract_id' => $parent->contract_id,
            'category' => $parent->category,
            'subject' => 'Re: '.$parent->subject,
            'body' => trim($this->replyBody),
        ]);

        $parent->touch();

        $this->replyBody = '';

        Notification::make()
            ->title('Resposta enviada!')
            ->success()
            ->send();
    }

    public function openAppointmentFromMessage(int $messageId): void
    {
        $msg = SchoolMessage::query()
            ->withoutGlobalScopes()
            ->with(['sender.person', 'senderSchool', 'contract.student'])
            ->find($messageId);

        if (! $msg) {
            return;
        }

        $senderName = $msg->sender?->getFilamentName() ?? $msg->senderSchool?->getFilamentName() ?? 'Rede';
        $schoolLabel = $msg->senderSchool?->getFilamentName();
        $originPrefix = $schoolLabel ? "{$senderName} ({$schoolLabel})" : $senderName;

        $rawHeadline = (! in_array($msg->subject, ['Chat Direto', 'Mensagem entre Unidades', 'Mural Geral da Rede'], true) && filled($msg->subject))
            ? $msg->subject
            : Str::limit(trim((string) preg_replace('/\s+/', ' ', (string) $msg->body)), 70);

        $this->contextMessageId = $msg->id;
        $this->showTaskModal = false;
        $this->apptTitle = $rawHeadline ?: "Compromisso via mensagem de {$senderName}";
        $this->apptDescription = "📌 Gerado a partir de mensagem em Comunicação da Rede\n"
            ."Remetente: {$originPrefix}\n"
            .'Enviada em: '.($msg->created_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'))."\n\n"
            .trim((string) $msg->body);
        $this->apptDate = today()->toDateString();
        $this->apptStartTime = now()->addHour()->format('H:00');
        $this->apptEndTime = now()->addHours(2)->format('H:00');
        $this->apptAllDay = false;
        $this->apptPriority = $msg->category === 'urgent'
            ? SchoolAppointment::PRIORITY_URGENTE
            : SchoolAppointment::PRIORITY_MEDIA;
        $this->apptType = $msg->category === 'financial'
            ? SchoolAppointment::TYPE_ATENDIMENTO
            : SchoolAppointment::TYPE_GERAL;
        $this->apptScope = 'current';
        $this->apptContractId = $msg->contract_id;
        $this->apptStudentId = $msg->contract?->student_id;
        $this->showAppointmentModal = true;
    }

    public function saveAppointmentFromMessage(): void
    {
        $this->validate([
            'apptTitle' => ['required', 'string', 'min:2', 'max:255'],
            'apptDate' => ['required', 'date'],
            'apptType' => ['required', 'string', 'max:40'],
            'apptPriority' => ['required', 'string', 'max:20'],
        ], [
            'apptTitle.required' => 'Informe um título para o compromisso.',
            'apptDate.required' => 'Informe a data do compromisso.',
        ]);

        $currentSchool = $this->getCurrentSchool();
        $resolvedSchoolId = $this->apptScope === 'global' ? null : $currentSchool?->id;

        SchoolAppointment::query()->create([
            'school_id' => $resolvedSchoolId,
            'title' => trim($this->apptTitle),
            'type' => $this->apptType,
            'priority' => $this->apptPriority,
            'status' => SchoolAppointment::STATUS_SCHEDULED,
            'date' => $this->apptDate,
            'start_time' => $this->apptAllDay ? null : ($this->apptStartTime ?: null),
            'end_time' => $this->apptAllDay ? null : ($this->apptEndTime ?: null),
            'all_day' => $this->apptAllDay,
            'description' => trim($this->apptDescription) ?: null,
            'created_by_user_id' => Auth::id(),
            'assigned_to_user_id' => Auth::id(),
            'is_shared_with_all' => false,
            'contract_id' => $this->apptContractId,
            'student_id' => $this->apptStudentId,
        ]);

        $this->showAppointmentModal = false;

        Notification::make()
            ->title('📅 Compromisso salvo na sua Agenda!')
            ->body("O compromisso \"{$this->apptTitle}\" foi agendado para ".Carbon::parse($this->apptDate)->format('d/m/Y').'.')
            ->success()
            ->actions([
                Action::make('open_agenda')
                    ->label('Abrir Minha Agenda')
                    ->url(SchoolAppointmentsCalendar::getUrl()),
            ])
            ->send();
    }

    protected function ensureDefaultTaskBoardForMessenger(): TaskBoard
    {
        $currentSchool = $this->getCurrentSchool();
        $schoolId = $currentSchool?->id;

        $existing = TaskBoard::query()
            ->when($schoolId, fn (Builder $q) => $q->where(fn (Builder $s) => $s->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->orderByRaw('CASE WHEN school_id = ? THEN 0 ELSE 1 END', [$schoolId ?? 0])
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if ($existing) {
            if (! $existing->columns()->exists()) {
                $existing->columns()->createMany([
                    ['name' => 'A Fazer', 'color' => 'gray', 'sort_order' => 1, 'is_done_column' => false],
                    ['name' => 'Em Andamento', 'color' => 'sky', 'sort_order' => 2, 'is_done_column' => false],
                    ['name' => 'Concluído', 'color' => 'emerald', 'sort_order' => 3, 'is_done_column' => true],
                ]);
            }

            return $existing;
        }

        $schoolName = $currentSchool?->getFilamentName();
        $board = TaskBoard::query()->create([
            'school_id' => $schoolId,
            'name' => $schoolName ? "Quadro Geral — {$schoolName}" : 'Quadro Geral de Tarefas',
            'description' => 'Acompanhamento de tarefas e To-Do da equipe.',
            'color' => 'primary',
            'is_default' => true,
            'created_by_user_id' => Auth::id(),
            'sort_order' => 1,
        ]);

        $board->columns()->createMany([
            ['name' => 'A Fazer', 'color' => 'gray', 'sort_order' => 1, 'is_done_column' => false],
            ['name' => 'Em Andamento', 'color' => 'sky', 'sort_order' => 2, 'is_done_column' => false],
            ['name' => 'Concluído', 'color' => 'emerald', 'sort_order' => 3, 'is_done_column' => true],
        ]);

        return $board;
    }

    public function openTaskFromMessage(int $messageId): void
    {
        $msg = SchoolMessage::query()
            ->withoutGlobalScopes()
            ->with(['sender.person', 'senderSchool'])
            ->find($messageId);

        if (! $msg) {
            return;
        }

        $board = $this->ensureDefaultTaskBoardForMessenger();
        $firstTodoColumn = $board->columns()->where('is_done_column', false)->orderBy('sort_order')->first()
            ?? $board->columns()->orderBy('sort_order')->first();

        $senderName = $msg->sender?->getFilamentName() ?? $msg->senderSchool?->getFilamentName() ?? 'Rede';
        $schoolLabel = $msg->senderSchool?->getFilamentName();
        $originPrefix = $schoolLabel ? "{$senderName} ({$schoolLabel})" : $senderName;

        $rawHeadline = (! in_array($msg->subject, ['Chat Direto', 'Mensagem entre Unidades', 'Mural Geral da Rede'], true) && filled($msg->subject))
            ? $msg->subject
            : Str::limit(trim((string) preg_replace('/\s+/', ' ', (string) $msg->body)), 70);

        $this->contextMessageId = $msg->id;
        $this->showAppointmentModal = false;
        $this->taskBoardId = $board->id;
        $this->taskColumnId = $firstTodoColumn?->id;
        $this->taskTitle = $rawHeadline ?: "Tarefa de mensagem: {$senderName}";
        $this->taskDescription = "💬 Criada a partir de mensagem de {$originPrefix} (".($msg->created_at?->format('d/m/Y H:i') ?? '')."):\n\n"
            .trim((string) $msg->body);
        $this->taskPriority = $msg->category === 'urgent' ? 'urgent' : 'medium';
        $this->taskDueDate = today()->toDateString();
        $this->showTaskModal = true;
    }

    public function updatedTaskBoardId($value): void
    {
        if (! $value) {
            return;
        }

        $col = TaskColumn::query()
            ->where('task_board_id', (int) $value)
            ->where('is_done_column', false)
            ->orderBy('sort_order')
            ->first()
            ?? TaskColumn::query()->where('task_board_id', (int) $value)->orderBy('sort_order')->first();

        $this->taskColumnId = $col?->id;
    }

    public function saveTaskFromMessage(): void
    {
        $this->validate([
            'taskTitle' => ['required', 'string', 'min:2', 'max:255'],
            'taskBoardId' => ['required', 'integer', 'exists:task_boards,id'],
            'taskColumnId' => ['required', 'integer', 'exists:task_columns,id'],
            'taskPriority' => ['required', 'string'],
            'taskDueDate' => ['nullable', 'date'],
        ], [
            'taskTitle.required' => 'Informe o título da tarefa (To-Do).',
        ]);

        $highestOrder = (int) (TaskCard::query()
            ->where('task_column_id', $this->taskColumnId)
            ->max('sort_order') ?? 0);

        $card = TaskCard::query()->create([
            'task_board_id' => $this->taskBoardId,
            'task_column_id' => $this->taskColumnId,
            'title' => trim($this->taskTitle),
            'description' => trim($this->taskDescription) ?: null,
            'priority' => $this->taskPriority,
            'due_date' => $this->taskDueDate ?: null,
            'assigned_to_all' => false,
            'sort_order' => $highestOrder + 1,
            'created_by_user_id' => Auth::id(),
        ]);

        if (Auth::id()) {
            $card->assignedUsers()->syncWithoutDetaching([Auth::id()]);
        }

        $this->showTaskModal = false;

        Notification::make()
            ->title('📋 Adicionado ao seu To-Do (Quadro de Tarefas)!')
            ->body("A tarefa \"{$card->title}\" foi cadastrada e atribuída a você.")
            ->success()
            ->actions([
                Action::make('open_kanban')
                    ->label('Ver Quadro de Tarefas')
                    ->url(TaskBoardKanban::getUrl()),
            ])
            ->send();
    }

    public function quoteMessage(int $messageId): void
    {
        $msg = SchoolMessage::query()
            ->withoutGlobalScopes()
            ->with('sender.person')
            ->find($messageId);

        if (! $msg) {
            return;
        }

        $sender = $msg->sender?->getFilamentName() ?? 'Mensagem';
        $snippet = Str::limit(trim((string) preg_replace('/\s+/', ' ', (string) $msg->body)), 100);
        $quotePrefix = "❝ {$sender}: \"{$snippet}\" ❞ — ";

        if ($this->chatMode === 'thread') {
            $rootId = $msg->parent_id ?: $msg->id;
            if ($this->selectedMessageId !== $rootId) {
                $this->selectMessage($rootId);
            }
            $this->replyBody = $quotePrefix.$this->replyBody;
        } else {
            $this->directMessageText = $quotePrefix.$this->directMessageText;
        }
    }

    public function toggleMessageRead(int $messageId): void
    {
        $msg = SchoolMessage::query()->withoutGlobalScopes()->find($messageId);
        if (! $msg) {
            return;
        }

        if ($msg->read_at !== null) {
            $msg->update([
                'read_at' => null,
                'read_by_user_id' => null,
            ]);

            Notification::make()
                ->title('Mensagem marcada como não lida.')
                ->info()
                ->send();
        } else {
            $msg->markAsRead(Auth::id());

            Notification::make()
                ->title('Mensagem marcada como lida.')
                ->success()
                ->send();
        }
    }

    protected function getMessagesQuery(): Builder
    {
        $currentSchool = $this->getCurrentSchool();
        $schoolId = (int) ($currentSchool?->id ?? 0);
        $userId = Auth::id();
        $hasRecipientUserCol = Schema::hasColumn('school_messages', 'recipient_user_id');

        $query = SchoolMessage::query()
            ->withoutGlobalScopes()
            ->with(['senderSchool', 'recipientSchool', 'sender.person', 'recipientUser.person', 'contract.student'])
            ->withCount('replies')
            ->rootMessages()
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($this->activeBox === 'inbox' && $schoolId > 0) {
            $query->forSchoolInbox($schoolId, $hasRecipientUserCol ? $userId : null);
        } elseif ($this->activeBox === 'sent' && $schoolId > 0) {
            $query->where(function (Builder $q) use ($schoolId, $userId) {
                $q->where('sender_school_id', $schoolId);
                if ($userId) {
                    $q->orWhere('sender_user_id', $userId);
                }
            });
        } elseif ($this->activeBox === 'transfers') {
            $query->where('category', 'transfer');
            if (! (Auth::user()?->isSuperAdmin() ?? false) && $schoolId > 0) {
                $query->where(fn (Builder $q) => $q->where('sender_school_id', $schoolId)->orWhere('recipient_school_id', $schoolId));
            }
        } elseif ($this->activeBox === 'all') {
            if (! (Auth::user()?->isSuperAdmin() ?? false) && $schoolId > 0) {
                $query->where(fn (Builder $q) => $q->forSchoolInbox($schoolId, $hasRecipientUserCol ? $userId : null)->orWhere('sender_school_id', $schoolId));
            }
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        if (filled($this->search)) {
            $term = '%'.trim($this->search).'%';
            $query->where('subject', 'like', $term);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $currentSchool = $this->getCurrentSchool();
        $hasTable = Schema::hasTable('school_messages');
        $hasRecipientUserCol = $hasTable && Schema::hasColumn('school_messages', 'recipient_user_id');
        $currentUserId = (int) Auth::id();

        $messages = $hasTable
            ? $this->getMessagesQuery()->limit(60)->get()
            : collect();

        $selectedMessage = ($hasTable && $this->selectedMessageId && $this->chatMode === 'thread')
            ? SchoolMessage::query()
                ->withoutGlobalScopes()
                ->with(['senderSchool', 'recipientSchool', 'sender.person', 'recipientUser.person', 'contract.student', 'replies.senderSchool', 'replies.sender.person'])
                ->find($this->selectedMessageId)
            : null;

        // Lista APENAS Colaboradores ativos que possuem Usuário no sistema (além de super_admins, exceto admin técnico)
        $teamUsers = User::query()
            ->withoutTechnicalAdmin()
            ->with(['person', 'collaborator', 'schools', 'roles'])
            ->where('panel_access', true)
            ->where(function (Builder $q) {
                $q->whereHas('collaborator', fn (Builder $cq) => $cq->where('active', true))
                    ->orWhereHas('roles', fn (Builder $rq) => $rq->where('name', 'super_admin'));
            })
            ->orderBy('id')
            ->get()
            ->map(function (User $u) use ($hasRecipientUserCol, $currentUserId): array {
                $unreadFromUser = ($hasRecipientUserCol && $u->id !== $currentUserId)
                    ? SchoolMessage::query()
                        ->withoutGlobalScopes()
                        ->where('sender_user_id', $u->id)
                        ->where('recipient_user_id', $currentUserId)
                        ->whereNull('read_at')
                        ->count()
                    : 0;

                $schoolsNames = $u->schools->map(fn (School $s) => $s->getFilamentName())->filter()->join(', ');
                $collabRole = $u->collaborator?->role;
                $spatieRole = $u->roles->first()?->name;
                $roleLabel = filled($collabRole)
                    ? $collabRole
                    : ($spatieRole === 'super_admin' ? 'Diretoria / Super Admin' : ($spatieRole ? ucfirst(str_replace('_', ' ', $spatieRole)) : 'Colaborador'));

                $name = $u->getFilamentName();
                $initials = collect(explode(' ', trim($name)))
                    ->filter()
                    ->take(2)
                    ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
                    ->join('');

                return [
                    'id' => $u->id,
                    'name' => $name,
                    'initials' => $initials ?: 'C',
                    'email' => $u->email,
                    'schools' => $schoolsNames ?: 'Rede Geral',
                    'role' => $roleLabel,
                    'is_online' => $u->isOnline(),
                    'last_seen' => $u->lastSeenLabel(),
                    'unread_count' => $unreadFromUser,
                    'is_me' => $u->id === $currentUserId,
                ];
            })
            ->sortByDesc(fn (array $item) => [$item['unread_count'] > 0 ? 1 : 0, $item['is_online'] ? 1 : 0])
            ->values();

        // Histórico de mensagens do Chat Direto 1-a-1 (estilo WhatsApp)
        $directChatMessages = collect();
        $selectedChatUser = null;
        if ($hasRecipientUserCol && $this->chatMode === 'direct_user' && $this->selectedChatUserId) {
            $selectedChatUser = $teamUsers->firstWhere('id', $this->selectedChatUserId);
            $otherId = $this->selectedChatUserId;

            $directChatMessages = SchoolMessage::query()
                ->withoutGlobalScopes()
                ->with(['sender.person', 'senderSchool'])
                ->where(function (Builder $q) use ($currentUserId, $otherId) {
                    $q->where(fn (Builder $a) => $a->where('sender_user_id', $currentUserId)->where('recipient_user_id', $otherId))
                        ->orWhere(fn (Builder $b) => $b->where('sender_user_id', $otherId)->where('recipient_user_id', $currentUserId));
                })
                ->orderBy('created_at', 'asc')
                ->limit(100)
                ->get();
        }

        // Histórico de mensagens de Canal de Unidade / Mural Geral
        $channelMessages = collect();
        $selectedChannelSchool = null;
        if ($hasTable && $this->chatMode === 'school_channel') {
            $channelSchoolId = $this->selectedChannelSchoolId;
            $mySchoolId = (int) ($currentSchool?->id ?? 0);

            if ($channelSchoolId) {
                $selectedChannelSchool = School::query()->withoutGlobalScopes()->find($channelSchoolId);
                $channelMessages = SchoolMessage::query()
                    ->withoutGlobalScopes()
                    ->with(['sender.person', 'senderSchool'])
                    ->when($hasRecipientUserCol, fn (Builder $q) => $q->whereNull('recipient_user_id'))
                    ->where(function (Builder $q) use ($mySchoolId, $channelSchoolId) {
                        $q->where(fn (Builder $a) => $a->where('sender_school_id', $mySchoolId)->where('recipient_school_id', $channelSchoolId))
                            ->orWhere(fn (Builder $b) => $b->where('sender_school_id', $channelSchoolId)->where('recipient_school_id', $mySchoolId));
                    })
                    ->orderBy('created_at', 'asc')
                    ->limit(100)
                    ->get();
            } else {
                // Mural Geral da Rede (Broadcasts)
                $channelMessages = SchoolMessage::query()
                    ->withoutGlobalScopes()
                    ->with(['sender.person', 'senderSchool'])
                    ->when($hasRecipientUserCol, fn (Builder $q) => $q->whereNull('recipient_user_id'))
                    ->whereNull('recipient_school_id')
                    ->orderBy('created_at', 'asc')
                    ->limit(100)
                    ->get();
            }
        }

        $otherSchools = School::query()
            ->withoutGlobalScopes()
            ->when($currentSchool, fn (Builder $q) => $q->where('id', '!=', $currentSchool->id))
            ->orderBy('trade_name')
            ->get();

        $recentContracts = Contract::query()
            ->withoutGlobalScopes()
            ->with('student')
            ->when($currentSchool && ! (Auth::user()?->isSuperAdmin() ?? false), fn (Builder $q) => $q->where('school_id', $currentSchool->id))
            ->whereIn('status', ['active', 'suspended', 'draft'])
            ->latest('id')
            ->limit(100)
            ->get();

        $unreadCount = ($hasTable && $currentSchool)
            ? SchoolMessage::query()
                ->withoutGlobalScopes()
                ->rootMessages()
                ->forSchoolInbox((int) $currentSchool->id, $hasRecipientUserCol ? $currentUserId : null)
                ->whereNull('read_at')
                ->count()
            : 0;

        $onlineCount = $teamUsers->where('is_online', true)->count();

        $availableTaskBoards = Schema::hasTable('task_boards')
            ? TaskBoard::query()
                ->with('school')
                ->when($currentSchool, fn (Builder $q) => $q->where(fn (Builder $s) => $s->where('school_id', $currentSchool->id)->orWhereNull('school_id')))
                ->orderByRaw('CASE WHEN school_id = ? THEN 0 ELSE 1 END', [$currentSchool?->id ?? 0])
                ->orderBy('name')
                ->get()
            : collect();

        $availableTaskColumns = ($this->taskBoardId && Schema::hasTable('task_columns'))
            ? TaskColumn::query()
                ->where('task_board_id', $this->taskBoardId)
                ->orderBy('sort_order')
                ->get()
            : collect();

        return [
            'currentSchool' => $currentSchool,
            'messages' => $messages,
            'selectedMessage' => $selectedMessage,
            'teamUsers' => $teamUsers,
            'onlineCount' => $onlineCount,
            'selectedChatUser' => $selectedChatUser,
            'directChatMessages' => $directChatMessages,
            'selectedChannelSchool' => $selectedChannelSchool,
            'channelMessages' => $channelMessages,
            'otherSchools' => $otherSchools,
            'recentContracts' => $recentContracts,
            'categories' => SchoolMessage::categories(),
            'unreadCount' => $unreadCount,
            'availableTaskBoards' => $availableTaskBoards,
            'availableTaskColumns' => $availableTaskColumns,
        ];
    }
}
