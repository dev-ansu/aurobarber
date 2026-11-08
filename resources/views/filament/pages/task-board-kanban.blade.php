<x-filament-panels::page>
    @php
        $board = $this->board;
        $stats = $this->boardStats();
        $boards = $this->boards();
        $staff = $this->staffUsers();
    @endphp

    <div class="space-y-4">
        <!-- BARRA SUPERIOR: QUADROS, ESTATÍSTICAS E AÇÕES RÁPIDAS -->
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-4">
            <div class="flex flex-col gap-3.5 lg:flex-row lg:items-center lg:justify-between">
                <!-- SELETOR DE QUADROS -->
                <div class="flex flex-wrap items-center gap-2 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 shrink-0">Quadro:</span>
                        <select
                            wire:model.live="selectedBoardId"
                            class="max-w-xs md:max-w-sm truncate rounded-xl border border-gray-300 bg-white text-gray-900 py-1.5 px-3 text-sm font-bold shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            @foreach($boards as $b)
                                <option value="{{ $b->id }}">
                                    {{ $b->school_id ? '🏢 [' . ($b->school?->getFilamentName() ?? 'Unidade') . '] ' : '🌐 [Global] ' }}{{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($board)
                        <x-filament::button
                            type="button"
                            size="xs"
                            color="gray"
                            outlined
                            wire:click="openEditBoardModal({{ $board->id }})"
                            icon="heroicon-m-pencil-square"
                            class="whitespace-nowrap shrink-0"
                        >
                            Editar
                        </x-filament::button>
                    @endif

                    <x-filament::button
                        type="button"
                        size="xs"
                        color="gray"
                        outlined
                        wire:click="openCreateBoardModal"
                        icon="heroicon-m-plus"
                        class="whitespace-nowrap shrink-0"
                    >
                        Novo
                    </x-filament::button>

                    @if($board && $boards->count() > 1)
                        <button
                            type="button"
                            wire:click="deleteBoard({{ $board->id }})"
                            wire:confirm="Tem certeza que deseja excluir este quadro e todos os seus cartões?"
                            class="text-xs text-rose-500 hover:text-rose-700 font-semibold px-2 py-1 whitespace-nowrap shrink-0"
                            title="Excluir quadro atual"
                        >
                            Excluir
                        </button>
                    @endif
                </div>

                <!-- CONTADORES DE TAREFAS + BOTÕES PRINCIPAIS -->
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <!-- CONTADORES DE TAREFAS -->
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300 whitespace-nowrap">
                            Total: <strong>{{ $stats['total'] }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-lg bg-sky-100 px-2 py-1 font-semibold text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 whitespace-nowrap">
                            Pendentes: <strong>{{ $stats['pending'] }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2 py-1 font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 whitespace-nowrap">
                            Concluídas: <strong>{{ $stats['completed'] }}</strong>
                        </span>
                        @if($stats['overdue'] > 0)
                            <span class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-2 py-1 font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 animate-pulse whitespace-nowrap">
                                <x-heroicon-m-exclamation-triangle class="h-3.5 w-3.5" />
                                Atrasadas: <strong>{{ $stats['overdue'] }}</strong>
                            </span>
                        @endif
                    </div>

                    <!-- BOTÕES PRINCIPAIS -->
                    <div class="flex items-center gap-2 shrink-0">
                        <x-filament::button
                            type="button"
                            size="sm"
                            color="primary"
                            wire:click="openCreateCardModal()"
                            icon="heroicon-m-plus-circle"
                            class="whitespace-nowrap shrink-0"
                        >
                            Nova Tarefa
                        </x-filament::button>

                        <x-filament::button
                            type="button"
                            size="sm"
                            color="gray"
                            outlined
                            wire:click="openCreateColumnModal"
                            icon="heroicon-m-view-columns"
                            class="whitespace-nowrap shrink-0"
                        >
                            Nova Coluna
                        </x-filament::button>
                    </div>
                </div>
            </div>

            <!-- FILTROS AVANÇADOS -->
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-5 pt-3 border-t border-gray-100 dark:border-gray-800">
                <!-- BUSCA -->
                <div>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar tarefa..."
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-1.5 px-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <!-- RESPONSÁVEL -->
                <div>
                    <select
                        wire:model.live="filterAssignee"
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-1.5 px-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todos os responsáveis</option>
                        <option value="my_tasks">👤 Minhas Tarefas</option>
                        @foreach($staff as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- SETOR -->
                <div>
                    <select
                        wire:model.live="filterSector"
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-1.5 px-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todos os setores</option>
                        <option value="pedagogico">Pedagógico</option>
                        <option value="administrativo">Administrativo</option>
                        <option value="comercial">Comercial</option>
                        <option value="financeiro">Financeiro</option>
                        <option value="gestao">Gestão</option>
                        <option value="geral">Geral</option>
                    </select>
                </div>

                <!-- PRIORIDADE -->
                <div>
                    <select
                        wire:model.live="filterPriority"
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-1.5 px-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todas as prioridades</option>
                        <option value="urgent">🔴 Urgente</option>
                        <option value="high">🟠 Alta</option>
                        <option value="medium">🟡 Média</option>
                        <option value="low">⚪ Baixa</option>
                    </select>
                </div>

                <!-- PRAZO -->
                <div>
                    <select
                        wire:model.live="filterDueDate"
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-1.5 px-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todos os prazos</option>
                        <option value="overdue">⚠️ Atrasadas</option>
                        <option value="today">📅 Para hoje</option>
                        <option value="this_week">🗓️ Para esta semana</option>
                    </select>
                </div>
            </div>
        </div>

        @if($board)
            <!-- QUADRO KANBAN (TRELLO ESTILO) -->
            <div class="flex gap-4 overflow-x-auto pb-6 pt-1 select-none" style="min-height: 650px;">
                @foreach($board->columns as $column)
                    @php
                        $cards = $this->getCardsForColumn($column);
                        $colBorder = match($column->color) {
                            'emerald' => 'border-emerald-300 dark:border-emerald-800/60 bg-emerald-50/30 dark:bg-emerald-950/10',
                            'sky' => 'border-sky-300 dark:border-sky-800/60 bg-sky-50/30 dark:bg-sky-950/10',
                            'amber' => 'border-amber-300 dark:border-amber-800/60 bg-amber-50/30 dark:bg-amber-950/10',
                            'rose' => 'border-rose-300 dark:border-rose-800/60 bg-rose-50/30 dark:bg-rose-950/10',
                            'purple' => 'border-purple-300 dark:border-purple-800/60 bg-purple-50/30 dark:bg-purple-950/10',
                            default => 'border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900/40',
                        };
                        $colBadge = match($column->color) {
                            'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'sky' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-300',
                            'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
                            'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300',
                            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
                            default => 'bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                        };
                    @endphp

                    <!-- COLUNA -->
                    <div
                        class="flex w-80 flex-shrink-0 flex-col rounded-2xl border {{ $colBorder }} p-3.5 shadow-sm transition"
                        ondragover="event.preventDefault(); this.classList.add('ring-2', 'ring-primary-500');"
                        ondragleave="this.classList.remove('ring-2', 'ring-primary-500');"
                        ondrop="this.classList.remove('ring-2', 'ring-primary-500'); handleDrop(event, {{ $column->id }});"
                    >
                        <!-- CABEÇALHO DA COLUNA -->
                        <div class="mb-3 flex items-center justify-between pb-2 border-b border-gray-200/60 dark:border-white/10">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-black uppercase tracking-wider text-gray-800 dark:text-gray-200">
                                    {{ $column->name }}
                                </h3>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-black {{ $colBadge }}">
                                    {{ $cards->count() }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    wire:click="openCreateCardModal({{ $column->id }})"
                                    class="rounded-lg p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition"
                                    title="Adicionar cartão nesta coluna"
                                >
                                    <x-heroicon-m-plus class="h-4 w-4" />
                                </button>
                                @if($board->columns->count() > 1 && $cards->isEmpty())
                                    <button
                                        type="button"
                                        wire:click="deleteColumn({{ $column->id }})"
                                        wire:confirm="Excluir coluna vazia?"
                                        class="rounded-lg p-1 text-gray-400 hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-950 transition"
                                        title="Excluir coluna"
                                    >
                                        <x-heroicon-m-trash class="h-3.5 w-3.5" />
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- CARDS NA COLUNA -->
                        <div class="flex flex-1 flex-col gap-2.5 overflow-y-auto pr-0.5" style="max-height: 720px;">
                            @forelse($cards as $card)
                                @php
                                    $prio = $card->priorityDetails();
                                    $sect = $card->sectorDetails();
                                    $prog = $card->checklistProgress();
                                    $isOverdue = $card->isOverdue();
                                    $isToday = $card->isDueToday();
                                @endphp

                                <div
                                    draggable="true"
                                    ondragstart="handleDragStart(event, {{ $card->id }})"
                                    class="group relative cursor-grab rounded-xl border border-gray-200/80 bg-white p-3.5 shadow-sm hover:shadow-md hover:border-primary-400 transition dark:border-gray-800 dark:bg-gray-900 active:cursor-grabbing"
                                >
                                    <!-- TOPO DO CARD: SETOR & PRIORIDADE -->
                                    <div class="flex flex-wrap items-center justify-between gap-1.5 mb-2">
                                        <div class="flex flex-wrap items-center gap-1">
                                            @if($sect)
                                                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold {{ $sect['badge'] }}">
                                                    {{ $sect['label'] }}
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-bold {{ $prio['badge'] }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $prio['dot'] }}"></span>
                                                {{ $prio['label'] }}
                                            </span>
                                        </div>

                                        <!-- BOTÃO DE EDIÇÃO RÁPIDA -->
                                        <button
                                            type="button"
                                            wire:click="openEditCardModal({{ $card->id }})"
                                            class="rounded p-1 text-gray-400 opacity-60 group-hover:opacity-100 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition"
                                            title="Editar tarefa"
                                        >
                                            <x-heroicon-m-pencil-square class="h-3.5 w-3.5" />
                                        </button>
                                    </div>

                                    <!-- TÍTULO DA TAREFA -->
                                    <h4
                                        wire:click="openEditCardModal({{ $card->id }})"
                                        class="cursor-pointer font-bold text-sm text-gray-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400 leading-snug line-clamp-2"
                                    >
                                        {{ $card->title }}
                                    </h4>

                                    @if($card->description)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                            {{ $card->description }}
                                        </p>
                                    @endif

                                    <!-- CHECKLIST DE SUBTAREFAS PROGRESS BAR -->
                                    @if($prog['total'] > 0)
                                        <div class="mt-2.5 space-y-1">
                                            <div class="flex items-center justify-between text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                                                <span class="flex items-center gap-1">
                                                    <x-heroicon-m-check-circle class="h-3.5 w-3.5 text-primary-600" />
                                                    Checklist
                                                </span>
                                                <span>{{ $prog['completed'] }}/{{ $prog['total'] }} ({{ $prog['percent'] }}%)</span>
                                            </div>
                                            <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden dark:bg-gray-800">
                                                <div
                                                    class="h-full rounded-full {{ $prog['percent'] === 100 ? 'bg-emerald-500' : 'bg-primary-600' }}"
                                                    style="width: {{ $prog['percent'] }}%"
                                                ></div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- RODAPÉ DO CARD: PRAZO & ATRIBUÍDOS -->
                                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 text-xs dark:border-gray-800/80">
                                        <!-- DATA DE ENTREGA -->
                                        @if($card->due_date)
                                            <span class="inline-flex items-center gap-1 font-semibold text-[11px] {{ $isOverdue ? 'text-rose-600 font-bold dark:text-rose-400' : ($isToday ? 'text-amber-600 font-bold dark:text-amber-400' : 'text-gray-500 dark:text-gray-400') }}">
                                                <x-heroicon-m-calendar class="h-3.5 w-3.5" />
                                                {{ $card->due_date->format('d/m') }}
                                                @if($isOverdue)
                                                    <span class="text-[9px] uppercase px-1 rounded bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">Atrasada</span>
                                                @elseif($isToday)
                                                    <span class="text-[9px] uppercase px-1 rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">Hoje</span>
                                                @endif
                                            </span>
                                        @else
                                            <span></span>
                                        @endif

                                        <!-- RESPONSÁVEIS -->
                                        <div class="flex items-center gap-1">
                                            @if($card->assigned_to_all)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300" title="Atribuída a todos os funcionários">
                                                    👥 Todos
                                                </span>
                                            @elseif($card->assignedUsers->isNotEmpty())
                                                <div class="flex -space-x-1.5 overflow-hidden">
                                                    @foreach($card->assignedUsers->take(3) as $user)
                                                        @php
                                                            $initials = collect(explode(' ', $user->name))->map(fn ($segment) => mb_substr($segment, 0, 1))->take(2)->join('');
                                                        @endphp
                                                        <span
                                                            class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary-600 text-[10px] font-black text-white ring-2 ring-white dark:ring-gray-900"
                                                            title="{{ $user->name }}"
                                                        >
                                                            {{ $initials ?: 'U' }}
                                                        </span>
                                                    @endforeach
                                                    @if($card->assignedUsers->count() > 3)
                                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-300 text-[9px] font-bold text-gray-800 ring-2 ring-white dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-900">
                                                            +{{ $card->assignedUsers->count() - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-300/80 p-6 text-center dark:border-gray-800">
                                    <p class="text-xs text-gray-400">Nenhuma tarefa nesta coluna.</p>
                                    <button
                                        type="button"
                                        wire:click="openCreateCardModal({{ $column->id }})"
                                        class="mt-2 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400"
                                    >
                                        + Criar tarefa
                                    </button>
                                </div>
                            @endforelse
                        </div>

                        <!-- BOTÃO DE ADICIONAR CARTÃO NO RODAPÉ DA COLUNA -->
                        <div class="pt-2">
                            <button
                                type="button"
                                wire:click="openCreateCardModal({{ $column->id }})"
                                class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-transparent py-2 text-xs font-bold text-gray-600 hover:bg-white/80 hover:border-gray-200 hover:text-primary-700 hover:shadow-sm dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-primary-300 transition"
                            >
                                <x-heroicon-m-plus class="h-4 w-4" />
                                <span>Adicionar cartão</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- MODAL: DETALHES / CRIAÇÃO / EDIÇÃO DO CARTÃO -->
        @if($isCardModalOpen)
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur-sm overflow-y-auto"
                role="dialog"
                aria-modal="true"
            >
                <div class="relative max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-600 text-white">
                                <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
                            </span>
                            <h2 class="text-lg font-black text-gray-950 dark:text-white">
                                {{ $editingCardId ? 'Editar Tarefa' : 'Nova Tarefa' }}
                            </h2>
                        </div>
                        <button
                            type="button"
                            wire:click="$set('isCardModalOpen', false)"
                            class="rounded-xl p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        >
                            <x-heroicon-m-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <form wire:submit="saveCard" class="space-y-4 pt-4">
                        <!-- TÍTULO -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                Título da Tarefa *
                            </label>
                            <input
                                type="text"
                                wire:model="cardTitle"
                                placeholder="Ex.: Ligar para alunos faltantes de quarta-feira..."
                                required
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2.5 px-3.5 text-sm font-semibold shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            @error('cardTitle') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <!-- COLUNA, SETOR E PRIORIDADE -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <!-- COLUNA -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Coluna *
                                </label>
                                <select
                                    wire:model="cardColumnId"
                                    required
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-xs font-semibold shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    @foreach($board->columns as $col)
                                        <option value="{{ $col->id }}">{{ $col->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- SETOR -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Setor / Categoria
                                </label>
                                <select
                                    wire:model="cardSector"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-xs font-semibold shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="">Geral</option>
                                    <option value="pedagogico">Pedagógico</option>
                                    <option value="administrativo">Administrativo</option>
                                    <option value="comercial">Comercial</option>
                                    <option value="financeiro">Financeiro</option>
                                    <option value="gestao">Gestão</option>
                                </select>
                            </div>

                            <!-- PRIORIDADE -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Prioridade *
                                </label>
                                <select
                                    wire:model="cardPriority"
                                    required
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-xs font-semibold shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="low">⚪ Baixa</option>
                                    <option value="medium">🟡 Média</option>
                                    <option value="high">🟠 Alta</option>
                                    <option value="urgent">🔴 Urgente</option>
                                </select>
                            </div>
                        </div>

                        <!-- DATA DE ENTREGA (PRAZO) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                Prazo de Entrega (Data Limite)
                            </label>
                            <input
                                type="date"
                                wire:model="cardDueDate"
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        <!-- ATRIBUIÇÃO A FUNCIONÁRIOS -->
                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/50 border border-gray-200/80 dark:border-gray-800">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">
                                    Atribuição de Funcionários
                                </label>
                            </div>

                            <!-- OPÇÃO: ATRIBUIR A TODOS -->
                            <label class="flex items-center gap-2 cursor-pointer mb-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="cardAssignedToAll"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600"
                                />
                                <span class="text-xs font-bold text-gray-900 dark:text-white">
                                    👥 Atribuir a todos os funcionários com acesso ao sistema
                                </span>
                            </label>

                            <!-- SELEÇÃO DE FUNCIONÁRIOS ESPECÍFICOS -->
                            @if(! $cardAssignedToAll)
                                <div class="mt-2 space-y-1.5 max-h-40 overflow-y-auto pr-1">
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-1">Ou selecione funcionários específicos:</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($staff as $u)
                                            <label class="flex items-center gap-2 rounded-lg bg-white p-2 text-xs font-medium text-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-200 dark:ring-white/10 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                                <input
                                                    type="checkbox"
                                                    wire:model="cardAssignedUserIds"
                                                    value="{{ $u->id }}"
                                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600"
                                                />
                                                <span class="truncate">{{ $u->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- DESCRIÇÃO -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                Descrição / Detalhes da Tarefa
                            </label>
                            <textarea
                                wire:model="cardDescription"
                                rows="3"
                                placeholder="Orientações detalhadas para a execução da tarefa..."
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 p-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            ></textarea>
                        </div>

                        <!-- CHECKLIST DE SUBTAREFAS (QUANDO O CARTÃO JÁ FOI SALVO OU ESTÁ SENDO EDITADO) -->
                        @if($editingCardId)
                            @php
                                $currentEditingCard = \App\Models\TaskCard::with('checklists')->find($editingCardId);
                            @endphp

                            <div class="rounded-2xl border border-gray-200/80 p-4 dark:border-gray-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                        <x-heroicon-m-check-badge class="h-4 w-4 text-primary-600" />
                                        Subtarefas & Checklist
                                    </h3>
                                    @if($currentEditingCard && $currentEditingCard->checklists->isNotEmpty())
                                        @php $p = $currentEditingCard->checklistProgress(); @endphp
                                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                                            {{ $p['completed'] }}/{{ $p['total'] }} concluídos
                                        </span>
                                    @endif
                                </div>

                                <!-- ITENS DO CHECKLIST -->
                                @if($currentEditingCard && $currentEditingCard->checklists->isNotEmpty())
                                    <div class="space-y-1.5">
                                        @foreach($currentEditingCard->checklists as $item)
                                            <div class="flex items-center justify-between gap-2 rounded-xl bg-gray-50 p-2 text-xs dark:bg-gray-800/60">
                                                <label class="flex items-center gap-2 cursor-pointer flex-1">
                                                    <input
                                                        type="checkbox"
                                                        wire:click="toggleChecklistItem({{ $item->id }})"
                                                        {{ $item->is_completed ? 'checked' : '' }}
                                                        class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600"
                                                    />
                                                    <span class="{{ $item->is_completed ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-gray-100 font-semibold' }}">
                                                        {{ $item->title }}
                                                    </span>
                                                </label>
                                                <button
                                                    type="button"
                                                    wire:click="deleteChecklistItem({{ $item->id }})"
                                                    class="text-gray-400 hover:text-rose-500 p-1"
                                                    title="Excluir item"
                                                >
                                                    <x-heroicon-m-trash class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- ADICIONAR NOVO ITEM AO CHECKLIST -->
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        wire:model="newChecklistTitle"
                                        wire:keydown.enter.prevent="addChecklistItem"
                                        placeholder="Adicionar item no checklist (Enter para adicionar)..."
                                        class="flex-1 rounded-xl border border-gray-300 bg-white text-gray-900 py-1.5 px-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                    <x-filament::button
                                        type="button"
                                        size="xs"
                                        color="gray"
                                        wire:click="addChecklistItem"
                                    >
                                        Adicionar
                                    </x-filament::button>
                                </div>
                            </div>
                        @endif

                        <!-- BOTÕES DE AÇÃO -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800">
                            <div>
                                @if($editingCardId)
                                    <button
                                        type="button"
                                        wire:click="deleteCard({{ $editingCardId }})"
                                        wire:confirm="Tem certeza que deseja excluir esta tarefa?"
                                        class="text-xs font-bold text-rose-600 hover:text-rose-800 dark:text-rose-400"
                                    >
                                        Excluir tarefa
                                    </button>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <x-filament::button
                                    type="button"
                                    color="gray"
                                    outlined
                                    wire:click="$set('isCardModalOpen', false)"
                                >
                                    Cancelar
                                </x-filament::button>

                                <x-filament::button
                                    type="submit"
                                    color="primary"
                                >
                                    {{ $editingCardId ? 'Salvar Alterações' : 'Criar Cartão' }}
                                </x-filament::button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- MODAL: NOVO / EDITAR QUADRO -->
        @if($isBoardModalOpen)
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
            >
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <h2 class="text-lg font-black text-gray-950 dark:text-white mb-4">
                        {{ $editingBoardId ? 'Editar Quadro' : 'Novo Quadro de Tarefas' }}
                    </h2>

                    <form wire:submit="saveBoard" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                Abrangência do Quadro (Unidade / Rede) *
                            </label>
                            <select
                                wire:model="boardScope"
                                required
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-xs font-semibold shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                @foreach($this->availableBoardScopes() as $scopeVal => $scopeLabel)
                                    <option value="{{ $scopeVal }}">{{ $scopeLabel }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Quadros globais aparecem em todas as unidades; quadros por unidade são exclusivos da escola selecionada.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                Nome do Quadro *
                            </label>
                            <input
                                type="text"
                                wire:model="boardName"
                                placeholder="Ex.: Ações Comerciais e Captação..."
                                required
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            @error('boardName') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                Descrição
                            </label>
                            <textarea
                                wire:model="boardDescription"
                                rows="2"
                                placeholder="Finalidade deste quadro de tarefas..."
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 p-2.5 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <x-filament::button
                                type="button"
                                color="gray"
                                outlined
                                wire:click="$set('isBoardModalOpen', false)"
                            >
                                Cancelar
                            </x-filament::button>
                            <x-filament::button
                                type="submit"
                                color="primary"
                            >
                                Salvar Quadro
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- MODAL: NOVA / EDITAR COLUNA -->
        @if($isColumnModalOpen)
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
            >
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <h2 class="text-lg font-black text-gray-950 dark:text-white mb-4">
                        {{ $editingColumnId ? 'Editar Coluna' : 'Nova Coluna no Quadro' }}
                    </h2>

                    <form wire:submit="saveColumn" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                Nome da Coluna *
                            </label>
                            <input
                                type="text"
                                wire:model="columnName"
                                placeholder="Ex.: A Fazer, Aguardando Fornecedor..."
                                required
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            @error('columnName') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                Cor de Destaque
                            </label>
                            <select
                                wire:model="columnColor"
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-xs shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="gray">⚪ Padrão (Cinza)</option>
                                <option value="sky">🔵 Azul / Em Andamento</option>
                                <option value="amber">🟡 Amarelo / Atenção</option>
                                <option value="emerald">🟢 Verde / Sucesso</option>
                                <option value="purple">🟣 Roxo / Especial</option>
                                <option value="rose">🔴 Vermelho / Crítico</option>
                            </select>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model="columnIsDone"
                                class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600"
                            />
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                Esta é uma coluna de tarefas concluídas
                            </span>
                        </label>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <x-filament::button
                                type="button"
                                color="gray"
                                outlined
                                wire:click="$set('isColumnModalOpen', false)"
                            >
                                Cancelar
                            </x-filament::button>
                            <x-filament::button
                                type="submit"
                                color="primary"
                            >
                                Salvar Coluna
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- SCRIPT DE DRAG AND DROP ESTILO TRELLO -->
    <script>
        function handleDragStart(event, cardId) {
            event.dataTransfer.setData('text/plain', cardId);
            event.dataTransfer.effectAllowed = 'move';
        }

        function handleDrop(event, targetColumnId) {
            event.preventDefault();
            const cardId = event.dataTransfer.getData('text/plain');
            if (cardId && targetColumnId) {
                @this.moveCard(parseInt(cardId, 10), parseInt(targetColumnId, 10));
            }
        }
    </script>
</x-filament-panels::page>

