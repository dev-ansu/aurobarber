<x-filament-panels::page>
    <div class="space-y-6">

        @php
            $groupedAppointments = $this->appointmentsGroupedByDate();
            $tasks = $this->pendingTasks();
            $users = $this->staffUsers();
            $students = $this->studentsList();
            $contracts = $this->contractsList();
            $helpCalls = $this->pendingEducatorHelpCalls();
        @endphp

        <!-- ALERTA AO VIVO DE CHAMADOS DE ALUNOS NO LABORATÓRIO (LMS) -->
        @if($helpCalls->isNotEmpty())
            <div wire:poll.15s class="rounded-2xl border-2 border-rose-500/50 bg-rose-50/90 dark:bg-rose-950/40 p-4 shadow-lg backdrop-blur-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white text-lg font-black animate-pulse shadow-md">
                            🚨
                        </span>
                        <div>
                            <h4 class="text-sm font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                                <span>Alunos Solicitando Ajuda no Laboratório de Informática</span>
                                <span class="rounded-full bg-rose-600 px-2.5 py-0.5 text-xs text-white font-mono font-bold">{{ $helpCalls->count() }}</span>
                            </h4>
                            <p class="text-xs text-rose-700 dark:text-rose-300">
                                Há alunos praticando no Aurosoft LMS aguardando assistência presencial do educador.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            href="{{ route('filament.admin.pages.monitor-ao-vivo') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white shadow hover:bg-rose-500 transition"
                        >
                            <span>🖥️</span>
                            <span>Abrir Monitor de Laboratório</span>
                        </a>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-3 border-t border-rose-200 dark:border-rose-900/60">
                    @foreach($helpCalls as $call)
                        <div class="flex items-center justify-between gap-2 p-2 rounded-xl bg-white/80 dark:bg-gray-900/80 border border-rose-200 dark:border-rose-900/40 text-xs">
                            <div class="min-w-0">
                                <span class="font-bold text-gray-900 dark:text-white truncate block">
                                    {{ $call->contract?->student?->name ?? 'Aluno #' . $call->contract_id }}
                                </span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 truncate block">
                                    {{ $call->lesson?->title ?? 'Aula LMS' }} • {{ $call->help_requested_at?->diffForHumans() ?? 'agora' }}
                                </span>
                            </div>
                            <button
                                type="button"
                                wire:click="resolveEducatorHelp({{ $call->id }})"
                                class="rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white px-2.5 py-1 text-[11px] font-bold shadow-xs transition shrink-0 cursor-pointer"
                                title="Marcar como atendido"
                            >
                                ✓ Atendido
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- BARRA SUPERIOR DE AÇÕES E NAVEGAÇÃO -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                Sua agenda pessoal e compromissos institucionais: reuniões, entrevistas de cancelamento e atendimentos.
            </p>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a
                    href="{{ route('filament.admin.pages.tarefas-trello') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 px-3.5 py-2 text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition whitespace-nowrap shrink-0"
                >
                    <x-heroicon-m-view-columns class="h-4 w-4 shrink-0" />
                    <span>Ver Mini-Trello</span>
                </a>

                <button
                    type="button"
                    wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-500 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition whitespace-nowrap shrink-0"
                >
                    <x-heroicon-m-plus class="h-4 w-4 shrink-0" />
                    <span>Novo Agendamento</span>
                </button>
            </div>
        </div>

        <!-- FILTROS E BUSCA -->
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Período</label>
                    <select
                        wire:model.live="filterPeriod"
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="today">Somente Hoje</option>
                        <option value="week">Próximos 7 Dias</option>
                        <option value="month">Este Mês</option>
                        <option value="all">Todos os Agendamentos</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Visualização</label>
                    <select
                        wire:model.live="filterScope"
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="for_me">👤 Minha Agenda & Equipe (Padrão)</option>
                        <option value="mine">🎯 Somente Meus Compromissos Pessoais</option>
                        <option value="shared">📢 Compartilhados com a Equipe</option>
                        <option value="students">🎓 Com Aluno / Entrevistas</option>
                        <option value="all">🏢 Todos da Unidade</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tipo de Evento</label>
                    <select
                        wire:model.live="filterType"
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todos os tipos</option>
                        <option value="cancelamento">🚨 Entrevista de Cancelamento</option>
                        <option value="reuniao_pais">👨‍👩‍👧 Reunião com Pais / Aluno</option>
                        <option value="reuniao_equipe">👥 Reunião de Equipe</option>
                        <option value="atendimento">💼 Atendimento / Financeiro</option>
                        <option value="evento_escola">🎉 Evento da Escola</option>
                        <option value="geral">📌 Geral / Outro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Buscar</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por título, aluno, sala..."
                        class="block w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>
            </div>
        </div>

        <!-- CONTEÚDO PRINCIPAL: AGENDA (ESQUERDA 2/3) + TO-DO LIST (DIREITA 1/3) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start w-full">

            <!-- COLUNA ESQUERDA: COMPROMISSOS AGENDADOS (2 COLUNAS NO DESKTOP) -->
            <div class="w-full lg:col-span-2 space-y-6">
                @if($groupedAppointments->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-800 bg-white dark:bg-gray-900">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 dark:bg-primary-950 dark:text-primary-400 mb-3">
                            <x-heroicon-o-calendar-days class="h-6 w-6" />
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Nenhum compromisso encontrado</h4>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Não há reuniões ou entrevistas agendadas com os filtros selecionados.
                        </p>
                        <div class="mt-4">
                            <button
                                type="button"
                                wire:click="openCreateModal"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-500 transition"
                            >
                                <x-heroicon-m-plus class="h-4 w-4" />
                                <span>Agendar Primeiro Compromisso</span>
                            </button>
                        </div>
                    </div>
                @else
                    @foreach($groupedAppointments as $dateString => $dayAppointments)
                        @php
                            $dateObj = \Illuminate\Support\Carbon::parse($dateString);
                            $isToday = $dateObj->isToday();
                            $isTomorrow = $dateObj->isTomorrow();
                        @endphp

                        <div class="space-y-3">
                            <!-- Cabeçalho da Data -->
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black text-gray-950 dark:text-white">
                                        {{ $dateObj->format('d/m/Y') }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 capitalize">
                                        • {{ $dateObj->translatedFormat('l') }}
                                    </span>
                                </div>

                                @if($isToday)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                        Hoje
                                    </span>
                                @elseif($isTomorrow)
                                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                        Amanhã
                                    </span>
                                @endif
                                <div class="flex-1 border-t border-gray-200 dark:border-gray-800"></div>
                            </div>

                            <!-- Lista de cards do dia -->
                            <div class="space-y-3">
                                @foreach($dayAppointments as $app)
                                    @php
                                        $typeBadgeClasses = match($app->type) {
                                            'cancelamento' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 ring-1 ring-rose-500/20',
                                            'reuniao_pais' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 ring-1 ring-amber-500/20',
                                            'reuniao_equipe' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 ring-1 ring-indigo-500/20',
                                            'atendimento' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 ring-1 ring-sky-500/20',
                                            'evento_escola' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 ring-1 ring-emerald-500/20',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 ring-1 ring-gray-500/20',
                                        };

                                        $priorityBadgeClasses = match($app->priority) {
                                            'urgente' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 ring-1 ring-rose-500/20',
                                            'alta' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 ring-1 ring-amber-500/20',
                                            'media' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 ring-1 ring-sky-500/20',
                                            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 ring-1 ring-gray-500/20',
                                        };
                                    @endphp

                                    <div class="rounded-2xl border {{ $app->status === 'completed' ? 'border-gray-200 bg-gray-50/60 opacity-70 dark:border-gray-800 dark:bg-gray-900/40' : ($app->type === 'cancelamento' ? 'border-red-200 bg-red-50/20 dark:border-red-950/50 dark:bg-red-950/10' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900') }} p-5 shadow-sm transition hover:shadow-md">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0 flex-1 space-y-2">
                                                <!-- Badges topo -->
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-xs font-bold {{ $typeBadgeClasses }}">
                                                        {{ $app->type_label }}
                                                    </span>

                                                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold {{ $priorityBadgeClasses }}">
                                                        Prioridade: {{ $app->priority_label }}
                                                    </span>

                                                    @if($app->is_shared_with_all)
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                                            <x-heroicon-m-megaphone class="h-3 w-3" />
                                                            Para toda a equipe
                                                        </span>
                                                    @endif

                                                    @if($app->status === 'completed')
                                                        <span class="rounded-lg bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                            ✓ Realizado
                                                        </span>
                                                    @elseif($app->status === 'canceled')
                                                        <span class="rounded-lg bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500 dark:bg-gray-800">
                                                            Cancelado
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Título -->
                                                <h3 class="text-base font-bold text-gray-950 dark:text-white {{ $app->status === 'completed' ? 'line-through text-gray-500' : '' }}">
                                                    {{ $app->title }}
                                                </h3>

                                                <!-- Horário e Local -->
                                                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                                                    <span class="inline-flex items-center gap-1 font-semibold text-primary-600 dark:text-primary-400">
                                                        <x-heroicon-m-clock class="h-3.5 w-3.5" />
                                                        {{ $app->formatted_time }}
                                                    </span>

                                                    @if($app->location)
                                                        <span class="inline-flex items-center gap-1 text-gray-500">
                                                            <x-heroicon-m-map-pin class="h-3.5 w-3.5" />
                                                            {{ $app->location }}
                                                        </span>
                                                    @endif

                                                    @if($app->assignedTo)
                                                        <span class="inline-flex items-center gap-1 text-gray-500">
                                                            <x-heroicon-m-user class="h-3.5 w-3.5" />
                                                            Responsável: <strong>{{ $app->assignedTo->person?->name ?? $app->assignedTo->email }}</strong>
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Aluno / Contrato se houver -->
                                                @if($app->student)
                                                    <div class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 text-xs text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-medium">
                                                        <x-heroicon-m-academic-cap class="h-3.5 w-3.5" />
                                                        <span>Aluno: <strong>{{ $app->student->name }}</strong></span>
                                                        @if($app->contract)
                                                            <span class="text-blue-500">({{ $app->contract->contract_number }})</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                <!-- Descrição / Pauta -->
                                                @if($app->description)
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line mt-1">
                                                        {{ $app->description }}
                                                    </p>
                                                @endif
                                            </div>

                                            <!-- Ações Rápidas no Card -->
                                            <div class="flex items-center gap-1 self-end sm:self-start">
                                                @if($app->status !== 'completed')
                                                    <button
                                                        type="button"
                                                        wire:click="markAsCompleted({{ $app->id }})"
                                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 transition"
                                                        title="Marcar como realizado"
                                                    >
                                                        <x-heroicon-m-check class="h-3.5 w-3.5" />
                                                        <span>Concluir</span>
                                                    </button>
                                                @endif

                                                <button
                                                    type="button"
                                                    wire:click="openEditModal({{ $app->id }})"
                                                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition"
                                                    title="Editar compromisso"
                                                >
                                                    <x-heroicon-m-pencil-square class="h-4 w-4" />
                                                </button>

                                                <button
                                                    type="button"
                                                    wire:click="deleteAppointment({{ $app->id }})"
                                                    wire:confirm="Tem certeza que deseja excluir este compromisso?"
                                                    class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400 transition"
                                                    title="Excluir compromisso"
                                                >
                                                    <x-heroicon-m-trash class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- COLUNA DIREITA: TO-DO LIST DO DIA (1 COLUNA NO DESKTOP) -->
            <div class="w-full lg:col-span-1 space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sticky top-20">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                                <x-heroicon-m-check-circle class="h-4 w-4" />
                            </div>
                            <h3 class="text-sm font-bold text-gray-950 dark:text-white">To-Do List do Dia</h3>
                        </div>
                        <a
                            href="{{ route('filament.admin.pages.tarefas-trello') }}"
                            class="text-xs font-bold text-primary-600 hover:text-primary-500"
                        >
                            Trello ➜
                        </a>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 pt-2">
                        Tarefas prioritárias atribuídas a você ou compartilhadas com a equipe.
                    </p>

                    @if($tasks->isEmpty())
                        <div class="py-8 text-center text-xs text-gray-400">
                            🎉 Nenhuma tarefa pendente no momento!
                        </div>
                    @else
                        <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($tasks as $card)
                                @php
                                    $cardPriority = $card->priorityDetails();
                                @endphp
                                <div class="py-3 flex items-start gap-2.5 group">
                                    <button
                                        type="button"
                                        wire:click="completeTaskCard({{ $card->id }})"
                                        class="mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded border border-gray-300 hover:border-emerald-500 hover:bg-emerald-50 text-emerald-600 dark:border-gray-700 transition"
                                        title="Concluir tarefa"
                                    >
                                        <x-heroicon-m-check class="h-3 w-3 opacity-0 group-hover:opacity-100" />
                                    </button>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-xs font-bold text-gray-900 dark:text-white leading-tight">
                                                {{ $card->title }}
                                            </span>
                                            <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $cardPriority['badge'] }}">
                                                {{ $cardPriority['label'] }}
                                            </span>
                                        </div>

                                        <div class="mt-1 flex items-center gap-2 text-[11px] text-gray-400">
                                            @if($card->due_date)
                                                <span>Prazo: {{ $card->due_date->format('d/m') }}</span>
                                            @endif
                                            @if($card->checklists->count() > 0)
                                                <span>• {{ $card->checklists->where('is_completed', true)->count() }}/{{ $card->checklists->count() }} subtarefas</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- MODAL DE CRIAÇÃO / EDIÇÃO DE COMPROMISSO -->
        @if($showModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800 my-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                                <x-heroicon-o-calendar class="h-5 w-5" />
                            </div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white">
                                {{ $editingId ? 'Editar Compromisso' : 'Novo Agendamento / Entrevista' }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 transition"
                        >
                            <x-heroicon-m-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <form wire:submit.prevent="saveAppointment" class="mt-4 space-y-4">
                        <!-- TÍTULO -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Título do Compromisso *</label>
                            <input
                                type="text"
                                wire:model.defer="appointmentTitle"
                                placeholder="Ex: Entrevista de Cancelamento - Aluno Lucas, Reunião Pedagógica..."
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2.5 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                required
                            />
                        </div>

                        <!-- TIPO E PRIORIDADE -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tipo de Evento *</label>
                                <select
                                    wire:model.defer="type"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2.5 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    required
                                >
                                    <option value="cancelamento">🚨 Entrevista de Cancelamento (Gestor)</option>
                                    <option value="reuniao_pais">👨‍👩‍👧 Reunião com Pais / Aluno</option>
                                    <option value="reuniao_equipe">👥 Reunião de Equipe</option>
                                    <option value="atendimento">💼 Atendimento Geral / Financeiro</option>
                                    <option value="evento_escola">🎉 Evento da Escola</option>
                                    <option value="geral">📌 Geral / Outro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Prioridade *</label>
                                <select
                                    wire:model.defer="priority"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2.5 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    required
                                >
                                    <option value="urgente">🔴 Urgente</option>
                                    <option value="alta">🟠 Alta</option>
                                    <option value="media">🔵 Média</option>
                                    <option value="baixa">⚪ Baixa</option>
                                </select>
                            </div>
                        </div>

                        <!-- DATA E HORÁRIOS -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Data *</label>
                                <input
                                    type="date"
                                    wire:model.defer="date"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Início</label>
                                <input
                                    type="time"
                                    wire:model.defer="start_time"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Término</label>
                                <input
                                    type="time"
                                    wire:model.defer="end_time"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- LOCAL / SALA -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Local / Sala</label>
                            <input
                                type="text"
                                wire:model.defer="location"
                                placeholder="Ex: Sala da Direção, Recepção, Google Meet..."
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        <!-- RESPONSÁVEL E COMPARTILHAMENTO -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Responsável / Gestor</label>
                                <select
                                    wire:model.defer="assigned_to_user_id"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="">Não atribuído / Aberto</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name ?? $user->email }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Vincular a Aluno (opcional)</label>
                                <select
                                    wire:model.defer="student_id"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="">Nenhum aluno específico</option>
                                    @foreach($students as $st)
                                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- CHECKBOX COMPARTILHAR COM TODOS E ESCOPO DE UNIDADE -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Abrangência (Unidade)</label>
                                <select
                                    wire:model.defer="appointmentScope"
                                    class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-xs font-semibold shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="current">🏢 Somente nesta Unidade ({{ \Filament\Facades\Filament::getTenant()?->getFilamentName() ?? 'Unidade Atual' }})</option>
                                    <option value="global">🌐 Pessoal / Global (Visível em todas as unidades)</option>
                                </select>
                            </div>

                            <div class="rounded-xl bg-amber-50/70 p-3 border border-amber-200/60 dark:bg-amber-950/20 dark:border-amber-900/40">
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model.defer="is_shared_with_all"
                                        class="rounded border-amber-400 text-amber-600 focus:ring-amber-500 h-4 w-4"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-amber-950 dark:text-amber-200">
                                            📢 Compartilhar com a equipe
                                        </span>
                                        <p class="text-[10px] text-amber-800/80 dark:text-amber-300/80">
                                            Visível para os colaboradores na agenda.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- DESCRIÇÃO / PAUTA -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Pauta / Observações</label>
                            <textarea
                                wire:model.defer="description"
                                rows="3"
                                placeholder="Motivo do cancelamento, detalhes do que precisa ser conversado, acordos..."
                                class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            ></textarea>
                        </div>

                        <!-- BOTÕES AÇÃO -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="rounded-xl bg-primary-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-500 transition"
                            >
                                Salvar Compromisso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
