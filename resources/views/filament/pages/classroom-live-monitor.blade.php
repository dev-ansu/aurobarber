<x-filament-panels::page>
    <div
        @if($autoRefresh) wire:poll.12s @endif
        class="space-y-6"
    >
        {{-- SELETOR DE MODALIDADE: LABORATÓRIO PRESENCIAL vs. ALUNOS EAD ONLINE --}}
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-3 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="$set('activeMode', 'presencial')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeMode === 'presencial' ? 'bg-primary-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Laboratório Presencial</span>
                    <span class="rounded-full px-2 py-0.5 text-[11px] {{ $activeMode === 'presencial' ? 'bg-primary-800 text-white' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                        {{ $this->metrics['total_monitored'] }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="$set('activeMode', 'ead')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeMode === 'ead' ? 'bg-primary-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                    <span>Alunos EAD (Online em Tempo Real)</span>
                    @php
                        $onlineEadCount = count(array_filter($this->eadStudents, fn($s) => $s['is_online'] ?? false));
                    @endphp
                    @if($onlineEadCount > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500 text-white px-2 py-0.5 text-[11px] font-black animate-pulse">
                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                            {{ $onlineEadCount }} online
                        </span>
                    @else
                        <span class="rounded-full px-2 py-0.5 text-[11px] {{ $activeMode === 'ead' ? 'bg-primary-800 text-white' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                            {{ count($this->eadStudents) }}
                        </span>
                    @endif
                </button>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ url('/' . (\Filament\Facades\Filament::getTenant()?->id ?? session('filament_tenant_id') ?? 1) . '/agenda') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 border border-gray-200 dark:border-gray-700 shadow-xs"
                    title="Ir para a Agenda de Chamadas do dia"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Agenda de Chamadas</span>
                    <span class="text-xs">➔</span>
                </a>

                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="h-2 w-2 rounded-full {{ $autoRefresh ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400' }}"></span>
                    <span>Atualização automática {{ $autoRefresh ? 'ativa (12s)' : 'pausada' }}</span>
                </div>
            </div>
        </div>

        @php
            $metrics = $this->metrics;
        @endphp

        @if($activeMode === 'presencial')
        {{-- BARRA SUPERIOR: KPI STATS CARDS --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400 font-black text-lg">
                    👥
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">Alunos Monitorados</span>
                    <span class="text-xl font-black text-gray-900 dark:text-white">{{ $metrics['total_monitored'] }}</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                <div class="relative h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-black text-lg">
                    💻
                    @if($metrics['actively_studying'] > 0)
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                    @endif
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">Estudando Agora</span>
                    <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ $metrics['actively_studying'] }}</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                <div class="relative h-10 w-10 rounded-xl {{ $metrics['needs_help'] > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400 animate-pulse' : 'bg-gray-50 text-gray-400 dark:bg-gray-800' }} flex items-center justify-center font-black text-lg">
                    ⚠️
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">Precisam de Ajuda</span>
                    <span class="text-xl font-black {{ $metrics['needs_help'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $metrics['needs_help'] }}
                    </span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-lg">
                    🏆
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">Aulas Concluídas Hoje</span>
                    <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ $metrics['completed_today'] }}</span>
                </div>
            </div>
        </div>

        {{-- CONTROLES & FILTROS --}}
        <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
            {{-- BUSCA RÁPIDA DE ALUNO --}}
            <div class="w-full md:w-80 relative">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Filtrar por nome ou contrato..."
                    class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2 pl-9 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                />
                <svg class="h-4 w-4 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- BOTÕES DE FILTRO DE STATUS --}}
            <div class="flex flex-wrap items-center gap-1.5 w-full md:w-auto">
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'all')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'all' ? 'bg-primary-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    Todos ({{ $metrics['total_monitored'] }})
                </button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'studying')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'studying' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    🟢 Estudando ({{ $metrics['actively_studying'] }})
                </button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'needs_help')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'needs_help' ? 'bg-rose-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    ⚠️ Dúvidas ({{ $metrics['needs_help'] }})
                </button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'out_of_focus')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'out_of_focus' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    👀 Fora da Aula ({{ $metrics['out_of_focus'] ?? 0 }})
                </button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'completed_today')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'completed_today' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    🏆 Concluídos ({{ $metrics['completed_today'] }})
                </button>
            </div>

            {{-- FERRAMENTAS DO MONITOR: BUSCA AVULSA & AUTO-REFRESH --}}
            <div class="flex items-center gap-2.5 w-full md:w-auto justify-end flex-wrap">
                <button
                    type="button"
                    wire:click="openAssignmentsModal"
                    class="inline-flex items-center gap-1.5 rounded-xl border {{ ($metrics['pending_assignments'] ?? 0) > 0 ? 'border-indigo-400 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300' : 'border-gray-300 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }} px-3 py-2 text-xs font-bold hover:opacity-90 shadow-xs transition"
                >
                    <span>📤</span>
                    <span>Vistos Práticos ({{ $metrics['pending_assignments'] ?? 0 }})</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('showLogsModal', true)"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 shadow-xs transition"
                >
                    <span>📜</span>
                    <span>Logs de Acesso</span>
                </button>

                <button
                    type="button"
                    wire:click="openLookupModal"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-primary-300 bg-primary-50 px-3 py-2 text-xs font-bold text-primary-700 hover:bg-primary-100 dark:border-primary-700 dark:bg-primary-950/40 dark:text-primary-300 transition"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Liberar Qualquer Aluno</span>
                </button>

                <div class="flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-gray-400">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model.live="autoRefresh"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <span>Ao Vivo</span>
                    </label>
                    <span wire:loading class="text-primary-600 dark:text-primary-400 font-bold animate-spin">⏳</span>
                </div>
            </div>
        </div>

        {{-- GRID DE ALUNOS NO LABORATÓRIO --}}
        @php
            $students = $this->monitoredStudents;
        @endphp

        @if(empty($students))
            <div class="rounded-3xl border border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900 shadow-xs">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 text-3xl mb-4">
                    🏫
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Nenhum aluno ativo no monitor neste momento</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Os alunos aparecerão aqui automaticamente quando realizarem check-in de presença ou abrirem uma aula no Player LMS.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button
                        type="button"
                        wire:click="openLookupModal"
                        class="rounded-xl bg-primary-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-primary-500 shadow-sm transition"
                    >
                        Buscar Aluno Específico para Liberar
                    </button>
                    <a
                        href="{{ route('filament.admin.resources.student-attendances.index') }}"
                        class="rounded-xl border border-gray-300 px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                    >
                        Ver Chamadas do Dia
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($students as $st)
                    <div class="rounded-3xl border {{ $st['is_needs_help'] ? 'border-rose-400/80 bg-rose-50/20 dark:border-rose-800/80 dark:bg-rose-950/20 shadow-md ring-2 ring-rose-500/20' : (!empty($st['is_out_of_focus']) ? 'border-amber-400/80 bg-amber-50/20 dark:border-amber-700/80 dark:bg-amber-950/20 shadow-md ring-2 ring-amber-400/20' : ($st['is_actively_studying'] ? 'border-emerald-300 dark:border-emerald-800/70 bg-white dark:bg-gray-900 shadow-sm' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xs')) }} p-5 flex flex-col justify-between transition hover:shadow-md">
                        <div>
                            {{-- CABEÇALHO DO CARD: ALUNO & STATUS --}}
                            <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative flex-shrink-0 flex h-11 w-11 items-center justify-center rounded-2xl {{ $st['is_needs_help'] ? 'bg-rose-600 text-white shadow-md shadow-rose-600/30' : (!empty($st['is_out_of_focus']) ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/30' : ($st['is_actively_studying'] ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-gray-700 text-white')) }} text-xs font-black tracking-wider">
                                        {{ $st['student_initials'] }}
                                        @if($st['is_actively_studying'])
                                            <span class="absolute -bottom-0.5 -right-0.5 flex h-3.5 w-3.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white dark:border-gray-900"></span>
                                            </span>
                                        @elseif($st['is_needs_help'])
                                            <span class="absolute -bottom-0.5 -right-0.5 flex h-3.5 w-3.5">
                                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-rose-500 border-2 border-white dark:border-gray-900"></span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="truncate">
                                        <h4 class="text-sm font-black text-gray-900 dark:text-white truncate flex items-center gap-1.5" title="{{ $st['student_name'] }}">
                                            <span class="truncate">{{ $st['student_name'] }}</span>
                                            @if(!empty($st['is_birthday_today']))
                                                <span title="Aniversariante do dia!" class="shrink-0 text-xs">🎂</span>
                                            @endif
                                        </h4>
                                        <div class="flex items-center gap-1.5 text-[11px] font-semibold text-gray-500 dark:text-gray-400 truncate">
                                            <span>{{ $st['classroom_name'] }}</span>
                                            @if($st['schedule_time'])
                                                <span>•</span>
                                                <span>{{ $st['schedule_time'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- BADGE DE STATUS --}}
                                <div class="flex flex-col items-end gap-1">
                                    @if(!empty($st['is_help_requested']))
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-600 px-2.5 py-1 text-[10px] font-black text-white shadow-md shadow-rose-600/30 animate-pulse">
                                            🚨 Chamou Educador!
                                        </span>
                                    @elseif($st['is_needs_help'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black text-rose-800 dark:bg-rose-950 dark:text-rose-300 animate-pulse">
                                            ⚠️ Atenção ({{ $st['mistakes_count'] }} erros)
                                        </span>
                                    @elseif(!empty($st['is_out_of_focus']))
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-800 dark:bg-amber-950 dark:text-amber-300 animate-pulse">
                                            👀 Fora da Aula ({{ $st['focus_loss_count'] ?? 1 }}x)
                                        </span>
                                    @elseif($st['status_type'] === 'completed_today')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2.5 py-1 text-[10px] font-bold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">
                                            ✓ Concluído Hoje
                                        </span>
                                    @elseif($st['is_actively_studying'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            🟢 Estudando
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            ⏸️ {{ $st['status_label'] }}
                                        </span>
                                    @endif

                                    @if(!empty($st['pending_assignment_id']))
                                        <button
                                            type="button"
                                            wire:click="openAssignmentsModal({{ $st['pending_assignment_id'] }})"
                                            class="inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white hover:bg-indigo-500 transition cursor-pointer"
                                        >
                                            📤 Visto Pendente
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- CORPO: CURSO, AULA E PASSO ATUAL --}}
                            <div class="py-3.5 space-y-2.5">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400 block truncate">
                                        {{ $st['course_name'] }}
                                    </span>
                                    <h5 class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate mt-0.5" title="{{ $st['lesson_title'] }}">
                                        {{ $st['lesson_title'] }}
                                    </h5>
                                </div>

                                {{-- BARRA DE PROGRESSO DO PASSO --}}
                                <div>
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span class="font-semibold text-gray-600 dark:text-gray-400 truncate">
                                            Passo {{ $st['current_step_order'] }}/{{ $st['total_steps'] }}: {{ $st['current_step_title'] }}
                                        </span>
                                        <span class="font-bold text-gray-900 dark:text-white ml-2 flex-shrink-0">{{ $st['progress_pct'] }}%</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <div
                                            class="h-full {{ $st['is_needs_help'] ? 'bg-rose-500' : 'bg-gradient-to-r from-primary-500 to-emerald-500' }} transition-all duration-300 rounded-full"
                                            style="width: {{ $st['progress_pct'] }}%"
                                        ></div>
                                    </div>
                                </div>

                                {{-- ALERTA DE PERDA DE FOCO --}}
                                @if(!empty($st['is_out_of_focus']))
                                    <div class="rounded-xl border border-amber-300 bg-amber-50 p-2.5 text-[11px] font-medium text-amber-900 dark:border-amber-800/70 dark:bg-amber-950/40 dark:text-amber-200 flex items-start gap-2">
                                        <span class="text-base flex-shrink-0">👀</span>
                                        <div>
                                            <strong>Janela da aula sem foco:</strong>
                                            O aluno alternou para outra aba ou aplicativo há mais de 30s ({{ $st['focus_loss_count'] ?? 1 }}x nesta aula).
                                        </div>
                                    </div>
                                @endif

                                {{-- ALERTA DE DÚVIDA / ERRO NO SIMULADOR --}}
                                @if($st['is_needs_help'])
                                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-2.5 text-[11px] font-medium text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300 flex items-start gap-2">
                                        <span class="text-base flex-shrink-0">💡</span>
                                        <div>
                                            <strong>Aluno com dificuldade no passo:</strong>
                                            O simulador detectou {{ $st['mistakes_count'] }} erros seguidos. O scaffolding foi ativado no player; um auxílio presencial é recomendado.
                                        </div>
                                    </div>
                                @endif

                                {{-- TIRA DE MÉTRICAS --}}
                                <div class="grid grid-cols-3 gap-2 py-2 px-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 text-center text-[10px]">
                                    <div>
                                        <span class="text-gray-400 block font-medium">Nota Atual</span>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">
                                            {{ $st['score'] !== null ? number_format($st['score'], 1) . '%' : '--' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block font-medium">Tempo Aula</span>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $st['time_spent_formatted'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block font-medium">Atividade</span>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $st['last_activity_text'] }}</span>
                                    </div>
                                </div>

                                @if(!empty($st['student_rating']))
                                    <div class="px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-800/50 text-[11px] flex items-center justify-between gap-2">
                                        <span class="font-bold text-amber-800 dark:text-amber-300">
                                            Avaliação da Aula: {{ str_repeat('⭐', (int) $st['student_rating']) }} ({{ $st['student_rating'] }}/5)
                                        </span>
                                        @if(!empty($st['student_rating_comment']))
                                            <span class="text-gray-600 dark:text-gray-300 italic truncate" title="{{ $st['student_rating_comment'] }}">
                                                "{{ $st['student_rating_comment'] }}"
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- RODAPÉ: AÇÕES PEDAGÓGICAS --}}
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if(!empty($st['is_help_requested']) && !empty($st['progress_id']))
                                    <button
                                        type="button"
                                        wire:click="resolveHelp({{ $st['progress_id'] }})"
                                        wire:loading.attr="disabled"
                                        title="Marcar chamado como atendido pelo educador"
                                        class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition cursor-pointer animate-pulse"
                                    >
                                        <span>✓</span>
                                        <span>Atender Aluno</span>
                                    </button>
                                @endif

                                @if(!empty($st['progress_id']))
                                    <button
                                        type="button"
                                        wire:click="openDirectMessageModal({{ $st['progress_id'] }}, @js($st['student_name']))"
                                        title="Enviar mensagem ou alerta instantâneo na tela deste aluno"
                                        class="inline-flex items-center gap-1 rounded-xl border border-indigo-300 bg-indigo-50 hover:bg-indigo-100 dark:border-indigo-700/70 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 px-2.5 py-1.5 text-xs font-bold text-indigo-700 dark:text-indigo-300 transition cursor-pointer"
                                    >
                                        <span>💬</span>
                                        <span>Alerta na Tela</span>
                                    </button>
                                @endif

                                {{-- BOTÃO DE LIBERAÇÃO DA PRÓXIMA AULA --}}
                                <button
                                    type="button"
                                    wire:click="unlockNextLesson({{ $st['contract_id'] }})"
                                    wire:loading.attr="disabled"
                                    title="Liberar próxima aula do curso e suspender teto diário"
                                    class="inline-flex items-center gap-1 rounded-xl bg-amber-500 hover:bg-amber-400 px-3 py-1.5 text-xs font-bold text-slate-950 shadow-xs transition cursor-pointer"
                                >
                                    <span>🔓</span>
                                    <span>Liberar Próxima</span>
                                </button>

                                {{-- BOTÃO DE LIBERAÇÃO DO TETO DIÁRIO --}}
                                <button
                                    type="button"
                                    wire:click="unlockDailyLimit({{ $st['contract_id'] }})"
                                    wire:loading.attr="disabled"
                                    title="Permitir mais uma aula na data de hoje"
                                    class="inline-flex items-center gap-1 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 px-2.5 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 transition cursor-pointer"
                                >
                                    <span>⚡</span>
                                    <span class="hidden sm:inline">Teto Diário</span>
                                </button>

                                @if(($st['status_type'] === 'completed_today' || !empty($st['is_birthday_today']) || ($st['score'] ?? 0) >= 100) && !empty($st['whatsapp_congrats_url']))
                                    <a
                                        href="{{ $st['whatsapp_congrats_url'] }}"
                                        target="_blank"
                                        title="Enviar parabéns via WhatsApp pela conquista ou aniversário"
                                        class="inline-flex items-center gap-1 rounded-xl bg-emerald-500/15 border border-emerald-500/40 hover:bg-emerald-500 hover:text-white px-2.5 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 transition cursor-pointer"
                                    >
                                        <span>🎉</span>
                                        <span class="hidden sm:inline">Parabenizar</span>
                                    </a>
                                @endif
                            </div>

                            <div class="flex items-center gap-1">
                                @if(!empty($st['progress_id']))
                                    <button
                                        type="button"
                                        wire:click="resetLessonProgress({{ $st['progress_id'] }})"
                                        wire:confirm="Deseja reiniciar a aula para este aluno do primeiro passo?"
                                        title="Reiniciar aula atual do aluno"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                @endif

                                @if(!empty($st['lesson_id']))
                                    <a
                                        href="{{ route('lms.player', ['lesson' => $st['lesson_id'], 'preview' => 1]) }}"
                                        target="_blank"
                                        title="Abrir aula no visualizador do educador"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/40 transition cursor-pointer"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        @else
            {{-- MODALIDADE EAD: MONITORAMENTO AO VIVO DO PORTAL EAD --}}
            @php
                $eadStudents = $this->eadStudents;
                $onlineCount = count(array_filter($eadStudents, fn($s) => $s['is_online'] ?? false));
            @endphp

            {{-- KPI STATS DO EAD --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400 font-black text-lg">
                        🎓
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">Alunos EAD com Acesso Hoje</span>
                        <span class="text-xl font-black text-gray-900 dark:text-white">{{ count($eadStudents) }}</span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                    <div class="relative h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-black text-lg">
                        🟢
                        @if($onlineCount > 0)
                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                        @endif
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">Estudando Online Agora</span>
                        <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ $onlineCount }}</span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-sky-50 dark:bg-sky-950/50 flex items-center justify-center text-sky-600 dark:text-sky-400 font-black text-lg">
                        ⚡
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">Origem do Monitoramento</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">API Ouro Moderno EAD</span>
                    </div>
                </div>
            </div>

            {{-- CONTROLES & BUSCA DO EAD --}}
            <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="w-full md:w-96 relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Pesquisar por nome ou login do aluno EAD..."
                        class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2 pl-9 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    />
                    <svg class="h-4 w-4 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Acompanhamento de alunos estudando de casa em tempo real no portal da Ouro Moderno.
                </div>
            </div>

            {{-- GRID DE ALUNOS EAD --}}
            @if(empty($eadStudents))
                <div class="p-12 text-center rounded-3xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-3">
                    <div class="h-12 w-12 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 text-2xl">
                        ☁️
                    </div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white">Nenhum aluno EAD com atividade registrada hoje</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                        Assim que os alunos acessarem as aulas ou exercícios no portal EAD, eles aparecerão aqui em tempo real.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($eadStudents as $st)
                        <div class="p-4 rounded-3xl bg-white dark:bg-gray-900 border {{ $st['is_online'] ? 'border-emerald-400 dark:border-emerald-600/70 shadow-md shadow-emerald-500/10 ring-1 ring-emerald-500/30' : 'border-gray-200 dark:border-gray-800 shadow-xs' }} flex flex-col justify-between gap-4 transition">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-2xl {{ $st['is_online'] ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }} flex items-center justify-center font-black text-sm">
                                            {{ mb_substr($st['name'], 0, 2) }}
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-gray-900 dark:text-white leading-tight">
                                                {{ $st['name'] }}
                                            </h4>
                                            <span class="text-[11px] text-gray-400 block font-mono">
                                                Login: {{ $st['login'] ?: '—' }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($st['is_online'])
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 text-[11px] font-black animate-pulse">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            <span>ONLINE AGORA</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-[11px] font-semibold">
                                            <span>Hoje</span>
                                        </span>
                                    @endif
                                </div>

                                <div class="p-3 rounded-2xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 space-y-1.5 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Atividade Atual:</span>
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $st['action'] }}</span>
                                    </div>
                                    @if(!empty($st['lesson']))
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Aula em Curso:</span>
                                            <span class="font-semibold text-primary-600 dark:text-primary-400">Aula {{ $st['lesson'] }} (Curso #{{ $st['course_id'] }})</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Horário:</span>
                                        <span class="text-gray-600 dark:text-gray-300 font-mono">{{ $st['date'] }} ({{ $st['time_ago'] }})</span>
                                    </div>
                                    @if(!empty($st['ip']))
                                        <div class="flex items-center justify-between text-[11px] text-gray-400 font-mono">
                                            <span>Conexão IP:</span>
                                            <span class="truncate max-w-[150px]">{{ $st['ip'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="sendWhatsAppEadStudent('{{ addslashes($st['name']) }}', null, '{{ addslashes($st['action']) }}')"
                                    title="Disparar mensagem pedagógica de apoio no WhatsApp"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:border-emerald-700/70 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/60 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 transition cursor-pointer"
                                >
                                    <span>💬</span>
                                    <span>Apoio WhatsApp</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- MODAL DE BUSCA AVULSA DE ALUNO --}}
        <div x-cloak x-show="$wire.showLookupModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-4 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🔍 Liberar Aula para Qualquer Aluno</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Localize alunos da escola para liberar a próxima aula ou suspender travas diárias.
                        </p>
                    </div>
                    <button type="button" wire:click="$set('showLookupModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- CAMPO DE BUSCA NO MODAL --}}
                <div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="lookupQuery"
                        placeholder="Digite o nome, CPF ou número de contrato do aluno..."
                        class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 shadow-xs focus:border-primary-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                {{-- RESULTADOS DA BUSCA --}}
                @php
                    $results = $this->lookupContracts;
                @endphp

                <div class="space-y-2.5">
                    @if(mb_strlen(trim($lookupQuery)) < 2)
                        <p class="text-center py-6 text-xs text-gray-400">
                            Digite pelo menos 2 caracteres para pesquisar alunos cadastrados.
                        </p>
                    @elseif($results->isEmpty())
                        <p class="text-center py-6 text-xs text-rose-500 font-semibold">
                            Nenhum aluno ou contrato encontrado com o termo informado.
                        </p>
                    @else
                        @foreach($results as $c)
                            <div class="p-3.5 rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-xs font-black text-gray-900 dark:text-white">
                                        {{ $c->student?->name }}
                                    </h4>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        Contrato: {{ $c->contract_number ?? "#{$c->id}" }} • Formação: {{ $c->items->first()?->formation?->name ?? 'Geral' }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="unlockNextLesson({{ $c->id }})"
                                        class="inline-flex items-center gap-1 rounded-xl bg-amber-500 hover:bg-amber-400 px-3 py-1.5 text-xs font-bold text-slate-950 shadow-xs transition cursor-pointer"
                                    >
                                        <span>🔓</span>
                                        <span>Liberar Próxima</span>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="unlockDailyLimit({{ $c->id }})"
                                        class="inline-flex items-center gap-1 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 px-2.5 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 transition cursor-pointer"
                                    >
                                        <span>⚡</span>
                                        <span>Teto Diário</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                    <button
                        type="button"
                        wire:click="$set('showLookupModal', false)"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL: LOGS DE ACESSO DO PORTAL & LMS --}}
        <div x-cloak x-show="$wire.showLogsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-4 max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-950 dark:text-primary-400 text-lg font-black">
                            📜
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Registro de Atividades & Acessos dos Alunos</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Auditoria ao vivo de logins, acessos a aulas, conclusões e saídas do sistema.</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showLogsModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto space-y-2 pr-1 text-xs">
                    @forelse($this->recentAccessLogs as $log)
                        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200/70 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <span class="text-base mt-0.5">
                                    @if($log->action === 'portal_login') 🟢
                                    @elseif($log->action === 'portal_logout') 🚪
                                    @elseif($log->action === 'lesson_start') 💻
                                    @elseif($log->action === 'lesson_exit') ⏹️
                                    @elseif($log->action === 'lesson_completed') 🏆
                                    @elseif($log->action === 'help_requested') 🚨
                                    @else 📝
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-900 dark:text-white truncate">
                                            {{ $log->student?->name ?? 'Aluno #' . $log->student_id }}
                                        </span>
                                        @if($log->contract)
                                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                Matrícula: {{ $log->contract->contract_number }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 mt-0.5 font-medium">
                                        {{ $log->description }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-right shrink-0 text-[11px] text-gray-500 dark:text-gray-400">
                                <span class="font-bold block text-gray-700 dark:text-gray-300">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                <span class="text-[10px] block">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-gray-400">
                            Nenhum registro de acesso recente encontrado.
                        </div>
                    @endforelse
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800 shrink-0">
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                        Exibindo os últimos 40 eventos registrados no sistema.
                    </span>
                    <button
                        type="button"
                        wire:click="$set('showLogsModal', false)"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition cursor-pointer"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL: VISTO DE TRABALHOS PRÁTICOS ENVIADOS PELOS ALUNOS --}}
        <div x-cloak x-show="$wire.showAssignmentsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-4 max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400 text-lg font-black">
                            📤
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Trabalhos Práticos para Visto do Educador</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Baixe o exercício enviado pelo aluno, atribua a nota e dê o visto pedagógico.</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showAssignmentsModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto space-y-3 pr-1 text-xs">
                    @forelse($this->pendingAssignments as $assign)
                        <div class="p-4 rounded-2xl border {{ $assign->status === 'pending' ? 'border-indigo-300 bg-indigo-50/40 dark:border-indigo-800 dark:bg-indigo-950/20' : 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50' }} space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-gray-900 dark:text-white text-sm">
                                            {{ $assign->student?->name ?? 'Aluno' }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $assign->status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($assign->status === 'revision_requested' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300') }}">
                                            {{ $assign->status === 'approved' ? '✓ Visto Aprovado' : ($assign->status === 'revision_requested' ? '🔄 Revisão Solicitada' : '⏳ Aguardando Visto') }}
                                        </span>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 mt-0.5">
                                        Aula: <strong>{{ $assign->lesson?->title }}</strong> • Passo: {{ $assign->step?->title }}
                                    </p>
                                    @if($assign->student_notes)
                                        <p class="text-gray-700 dark:text-gray-300 mt-1 italic">
                                            "{{ $assign->student_notes }}"
                                        </p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a
                                        href="{{ \Illuminate\Support\Facades\Storage::url($assign->file_path) }}"
                                        target="_blank"
                                        download="{{ $assign->original_name }}"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 transition"
                                    >
                                        <span>📥</span>
                                        <span>Baixar ({{ $assign->original_name }})</span>
                                    </a>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-gray-200/70 dark:border-gray-700/70 flex flex-col sm:flex-row items-center gap-2">
                                <input
                                    type="text"
                                    wire:model="gradingFeedback"
                                    placeholder="Feedback pedagógico (ex: Excelente formatação da planilha!)"
                                    class="flex-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                                <div class="flex items-center gap-2 w-full sm:w-auto justify-end shrink-0">
                                    <button
                                        type="button"
                                        wire:click="gradeAssignment({{ $assign->id }}, 'approved', 100)"
                                        class="rounded-xl bg-emerald-600 hover:bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition cursor-pointer"
                                    >
                                        ✓ Dar Visto (Nota 100)
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="gradeAssignment({{ $assign->id }}, 'revision_requested', 60)"
                                        class="rounded-xl border border-amber-400 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 px-3 py-1.5 text-xs font-bold text-amber-800 dark:text-amber-300 transition cursor-pointer"
                                    >
                                        🔄 Pedir Ajuste
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-gray-400">
                            Nenhum trabalho prático enviado pelos alunos até o momento.
                        </div>
                    @endforelse
                </div>

                <div class="flex items-center justify-end pt-3 border-t border-gray-100 dark:border-gray-800 shrink-0">
                    <button
                        type="button"
                        wire:click="$set('showAssignmentsModal', false)"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition cursor-pointer"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL: ENVIAR MENSAGEM / ALERTA DIRETO PARA A TELA DO ALUNO --}}
        <div x-cloak x-show="$wire.showDirectMessageModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400 text-lg font-black">
                            💬
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                Enviar Alerta para {{ $directMessageStudentName ?: 'o Aluno' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                A mensagem aparecerá em destaque com alerta sonoro diretamente na tela da aula do aluno.
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showDirectMessageModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <span class="block font-bold text-gray-700 dark:text-gray-300">Envio Rápido com 1 Clique:</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <button
                            type="button"
                            wire:click="sendDirectMessageToStudent('👀 Por gentileza, volte o foco para a aba da aula e continue o exercício prático!')"
                            class="text-left rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 p-2.5 font-semibold text-amber-900 dark:text-amber-200 transition cursor-pointer"
                        >
                            👀 "Volte o foco para a aula!"
                        </button>
                        <button
                            type="button"
                            wire:click="sendDirectMessageToStudent('🙋 Recebi seu chamado! Já estou indo até sua bancada para te ajudar.')"
                            class="text-left rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 p-2.5 font-semibold text-emerald-900 dark:text-emerald-200 transition cursor-pointer"
                        >
                            🙋 "Já estou indo até sua bancada!"
                        </button>
                        <button
                            type="button"
                            wire:click="sendDirectMessageToStudent('💡 Leia com atenção a dica pedagógica do passo atual antes de clicar no simulador.')"
                            class="text-left rounded-xl border border-indigo-300 bg-indigo-50 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 p-2.5 font-semibold text-indigo-900 dark:text-indigo-200 transition cursor-pointer"
                        >
                            💡 "Atenção à dica do simulador!"
                        </button>
                        <button
                            type="button"
                            wire:click="sendDirectMessageToStudent('👏 Excelente ritmo e dedicação na aula de hoje! Continue assim!')"
                            class="text-left rounded-xl border border-purple-300 bg-purple-50 hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-950/40 p-2.5 font-semibold text-purple-900 dark:text-purple-200 transition cursor-pointer"
                        >
                            👏 "Excelente ritmo na aula!"
                        </button>
                    </div>

                    <div class="pt-2">
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Ou digite uma mensagem personalizada:</label>
                        <textarea
                            wire:model="directMessageText"
                            rows="3"
                            placeholder="Digite a orientação para o aluno..."
                            class="block w-full rounded-xl border border-gray-300 bg-white p-3 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="$set('showDirectMessageModal', false)"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="sendDirectMessageToStudent()"
                        class="rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-xs font-bold text-white shadow-sm cursor-pointer"
                    >
                        💬 Enviar para a Tela do Aluno
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

