<x-filament-panels::page>
    <div class="space-y-6">

        <!-- CABEÇALHO SUPERIOR REFINADO: IDENTIFICAÇÃO, SELETOR E BARRA DE FERRAMENTAS -->
        <div class="rounded-3xl border border-gray-200 bg-white p-5 sm:p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
            {{-- LINHA 1: TÍTULO DO ESTÚDIO + SELETOR DE CURSO EM DESTAQUE --}}
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-primary-600 to-indigo-500 text-white shadow-md shadow-primary-500/20">
                        <x-heroicon-o-academic-cap class="h-7 w-7" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-black text-gray-950 dark:text-white">Estúdio de Criação de Aulas Interativas</h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                                Método Jesuítico
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Desenvolva cursos com a pedagogia inaciana: Praelectio, Prática Guiada, Repetitio e Desafio.
                        </p>
                    </div>
                </div>

                {{-- SELETOR DE CURSO ESTILIZADO --}}
                <div class="flex items-center gap-2 w-full lg:w-auto">
                    <div class="relative w-full lg:w-72">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <span class="text-sm">📚</span>
                        </div>
                        <select
                            wire:model.live="selectedCourseId"
                            class="block w-full rounded-2xl border-gray-300 py-2.5 pl-9 pr-10 text-xs font-bold text-gray-900 shadow-xs focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white cursor-pointer"
                        >
                            <option value="">Selecione um Curso...</option>
                            @foreach ($this->courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->workload }}h)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- LINHA 2: TOOLBAR ORGANIZADA EM GRUPOS LÓGICOS COM DIVISORES --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-3.5 border-t border-gray-100 dark:border-gray-800">
                {{-- GRUPO 1: CRIAÇÃO DE CONTEÚDO --}}
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="openCreateModuleModal"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition cursor-pointer shadow-2xs"
                    >
                        <x-heroicon-m-folder-plus class="h-4 w-4 text-primary-600" />
                        <span>+ Nova Seção</span>
                    </button>

                    <button
                        type="button"
                        wire:click="openCreateLessonModal"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition cursor-pointer shadow-2xs"
                    >
                        <x-heroicon-m-document-plus class="h-4 w-4 text-emerald-600" />
                        <span>+ Nova Aula</span>
                    </button>

                    <button
                        type="button"
                        wire:click="openAiModal"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:from-purple-500 hover:to-indigo-500 transition cursor-pointer"
                    >
                        <x-heroicon-m-sparkles class="h-4 w-4 text-purple-200" />
                        <span>Gerar com IA (90min)</span>
                    </button>
                </div>

                {{-- GRUPOS SECUNDÁRIOS: FERRAMENTAS & PACOTES --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- FERRAMENTAS PEDAGÓGICAS --}}
                    <div class="inline-flex items-center rounded-xl border border-gray-200 bg-gray-50/80 p-0.5 dark:border-gray-800 dark:bg-gray-800/40">
                        <a
                            href="{{ \App\Filament\Studio\Pages\LmsQuestionBank::getUrl(['course_id' => $selectedCourseId, 'lesson_id' => $selectedLessonId]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-cyan-800 hover:bg-cyan-100/70 rounded-lg dark:text-cyan-300 dark:hover:bg-cyan-950/60 transition"
                            title="Banco de Questões e Exercícios Interativos"
                        >
                            <span>🎲 Questões</span>
                        </a>

                        <button
                            type="button"
                            wire:click="openRatingsModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100/70 rounded-lg dark:text-amber-300 dark:hover:bg-amber-950/60 transition cursor-pointer"
                            title="Avaliações dos Alunos sobre as Aulas"
                        >
                            <span>⭐ Avaliações</span>
                        </button>
                    </div>

                    {{-- PACOTES & EXPORTAÇÃO COMPLETA (.ZIP) --}}
                    <div class="inline-flex items-center rounded-xl border border-purple-200 bg-purple-50/70 p-0.5 shadow-2xs dark:border-purple-900/60 dark:bg-purple-950/30">
                        @if($selectedCourseId)
                            <button
                                type="button"
                                wire:click="exportSelectedCourse"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-purple-800 hover:bg-purple-100 rounded-lg dark:text-purple-300 dark:hover:bg-purple-900/50 transition cursor-pointer"
                                title="Exportar curso selecionado com todos os módulos, lições e mídias em um arquivo .zip"
                            >
                                <span>📦 Pacote (.zip)</span>
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="exportAllCoursesPackage"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-100 rounded-lg dark:text-purple-300 dark:hover:bg-purple-900/50 transition cursor-pointer"
                            title="Exportar acervo completo de cursos e mídias em um único arquivo .zip"
                        >
                            <span>🌐 Backup Geral</span>
                        </button>

                        <button
                            type="button"
                            wire:click="openImportPackageModal"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold bg-purple-600 text-white hover:bg-purple-500 rounded-lg shadow-xs transition cursor-pointer"
                            title="Importar pacote completo de curso (.zip com mídias ou .json)"
                        >
                            <span>📥 Importar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(! $selectedCourseId)
            <!-- ESTADO INICIAL SEM CURSO SELECIONADO -->
            <div class="rounded-3xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-white dark:bg-gray-900 space-y-3">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400">
                    <x-heroicon-o-book-open class="h-8 w-8" />
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Nenhum curso selecionado</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Selecione um curso proprietário no seletor acima para gerenciar suas seções e aulas interativas ou cadastre um novo curso em <em>Pedagógico → Cursos</em>.
                </p>
            </div>
        @else
            @php
                $lessonRatingsMap = $this->lessonRatingsMap;
            @endphp
            <!-- PAINEL PRINCIPAL EM 2 COLUNAS: ESTRUTURA E ROTEIRO (OCULTO QUANDO O ESTÚDIO DE CONTEÚDO EM PÁGINA INTEIRA ESTÁ ABERTO) -->
            <div x-show="!$wire.showStepModal" class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- COLUNA ESQUERDA: SEÇÕES E AULAS (4 COLUNAS) -->
                <div class="lg:col-span-4 space-y-4">
                    <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                            <h2 class="text-xs font-black uppercase tracking-wider text-gray-900 dark:text-white flex items-center gap-1.5">
                                <x-heroicon-m-queue-list class="h-4 w-4 text-primary-600" />
                                <span>Seções & Aulas</span>
                            </h2>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    {{ $this->selectedCourse?->lmsModules->count() ?? 0 }} seções
                                </span>
                                <button
                                    type="button"
                                    wire:click="openCreateModuleModal"
                                    class="text-[10px] font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-0.5 cursor-pointer"
                                    title="Criar Nova Seção"
                                >
                                    + Seção
                                </button>
                            </div>
                        </div>

                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                            @forelse ($this->selectedCourse?->lmsModules ?? [] as $module)
                                <div class="rounded-2xl border {{ $selectedModuleId === $module->id ? 'border-primary-500 bg-primary-50/20 dark:border-primary-600 dark:bg-primary-950/20' : 'border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-800/40' }} p-3 space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <button
                                            type="button"
                                            wire:click="$set('selectedModuleId', {{ $module->id }})"
                                            class="text-left font-bold text-xs text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 flex items-center gap-1.5"
                                        >
                                            <span class="text-primary-600">📁</span>
                                            <span>{{ $module->title }}</span>
                                        </button>
                                        <div class="flex items-center gap-0.5">
                                            <button
                                                type="button"
                                                wire:click="moveModuleUp({{ $module->id }})"
                                                class="text-gray-400 hover:text-primary-600 transition px-1 py-0.5 text-[10px] font-bold"
                                                title="Mover Seção para Cima"
                                            >↑</button>
                                            <button
                                                type="button"
                                                wire:click="moveModuleDown({{ $module->id }})"
                                                class="text-gray-400 hover:text-primary-600 transition px-1 py-0.5 text-[10px] font-bold"
                                                title="Mover Seção para Baixo"
                                            >↓</button>
                                            <button
                                                type="button"
                                                wire:click="openEditModuleModal({{ $module->id }})"
                                                class="text-gray-400 hover:text-primary-600 transition p-1"
                                                title="Editar Seção"
                                            >
                                                <x-heroicon-m-pencil-square class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="deleteModule({{ $module->id }})"
                                                wire:confirm="Tem certeza que deseja excluir esta seção e todas as suas aulas?"
                                                class="text-gray-400 hover:text-danger-600 transition p-1"
                                                title="Excluir Seção"
                                            >
                                                <x-heroicon-m-trash class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- BOTÃO DIRETO PARA ADICIONAR AULA NESTA SEÇÃO -->
                                    <button
                                        type="button"
                                        wire:click="openCreateLessonModal({{ $module->id }})"
                                        class="w-full flex items-center justify-center gap-1.5 rounded-xl border border-dashed border-emerald-500/40 bg-emerald-50/50 py-1.5 px-3 text-[11px] font-bold text-emerald-700 hover:bg-emerald-100/80 dark:border-emerald-500/30 dark:bg-emerald-950/20 dark:text-emerald-300 dark:hover:bg-emerald-950/40 transition cursor-pointer"
                                    >
                                        <x-heroicon-m-plus class="h-3.5 w-3.5 text-emerald-600" />
                                        <span>+ Adicionar Aula nesta Seção</span>
                                    </button>

                                    <!-- AULAS DA SEÇÃO -->
                                    <div class="space-y-1.5 pl-2 border-l-2 border-gray-200 dark:border-gray-700">
                                        @forelse ($module->lessons as $lesson)
                                            @php
                                                $ratingInfo = $lessonRatingsMap[$lesson->id] ?? null;
                                            @endphp
                                            <div
                                                wire:click="$set('selectedLessonId', {{ $lesson->id }})"
                                                class="cursor-pointer group flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium transition {{ $selectedLessonId === $lesson->id ? 'bg-primary-600 text-white shadow-sm' : 'hover:bg-white text-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                                            >
                                                <div class="truncate pr-2">
                                                    <span class="font-bold">{{ $lesson->title }}</span>
                                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-[10px] {{ $selectedLessonId === $lesson->id ? 'text-primary-100' : 'text-gray-400' }}">
                                                        <span>⏱️ {{ $lesson->estimated_duration_minutes }} min</span>
                                                        <span>•</span>
                                                        <span>🎯 {{ (int)$lesson->passing_score }}%</span>
                                                        <span>•</span>
                                                        <span>{{ $lesson->steps->count() }} passos</span>
                                                        @if($ratingInfo)
                                                            <span>•</span>
                                                            <span class="font-bold {{ $selectedLessonId === $lesson->id ? 'text-amber-200' : 'text-amber-500' }}">
                                                                ⭐ {{ number_format($ratingInfo['avg'], 1, ',', '.') }} ({{ $ratingInfo['count'] }})
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-0.5 shrink-0">
                                                    <button
                                                        type="button"
                                                        wire:click.stop="moveLessonUp({{ $lesson->id }})"
                                                        class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-white transition px-1 py-0.5 text-[10px] font-bold"
                                                        title="Mover Aula para Cima"
                                                    >↑</button>
                                                    <button
                                                        type="button"
                                                        wire:click.stop="moveLessonDown({{ $lesson->id }})"
                                                        class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-white transition px-1 py-0.5 text-[10px] font-bold"
                                                        title="Mover Aula para Baixo"
                                                    >↓</button>
                                                    <button
                                                        type="button"
                                                        wire:click.stop="duplicateLesson({{ $lesson->id }})"
                                                        class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-white transition p-0.5 text-[11px]"
                                                        title="Duplicar Aula Completa"
                                                    >📋</button>
                                                    <button
                                                        type="button"
                                                        wire:click.stop="openEditLessonModal({{ $lesson->id }})"
                                                        class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-white transition p-0.5"
                                                        title="Editar Aula"
                                                    >
                                                        <x-heroicon-m-pencil-square class="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        wire:click.stop="deleteLesson({{ $lesson->id }})"
                                                        wire:confirm="Deseja excluir esta aula?"
                                                        class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-danger-400 transition p-0.5"
                                                        title="Excluir Aula"
                                                    >
                                                        <x-heroicon-m-trash class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-[11px] text-gray-400 italic py-1">Nenhuma aula cadastrada nesta seção.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-6 text-gray-400 text-xs">
                                    Nenhuma seção criada. Clique em <strong>+ Nova Seção</strong> ou <strong>+ Nova Aula</strong> acima.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- COLUNA DIREITA: ROTEIRO JESUÍTICO DA AULA (8 COLUNAS) -->
                <div class="lg:col-span-8 space-y-4">
                    @if(! $selectedLessonId)
                        <div class="rounded-3xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-white dark:bg-gray-900 space-y-2">
                            <x-heroicon-o-cursor-arrow-rays class="h-10 w-10 text-gray-400 mx-auto" />
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Selecione uma aula</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Clique em uma aula na coluna à esquerda para ver e editar os passos da metodologia jesuítica.</p>
                        </div>
                    @else
                        <!-- PAINEL DA AULA SELECIONADA -->
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
                            
                            <!-- DETALHES DA AULA -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-base font-black text-gray-950 dark:text-white">{{ $this->selectedLesson?->title }}</h2>
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-black text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            ⏱️ Meta: {{ $this->selectedLesson?->estimated_duration_minutes }} minutos
                                        </span>
                                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-black text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                            🎯 Corte: {{ (int)$this->selectedLesson?->passing_score }}%
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $this->selectedLesson?->description ?: 'Sem descrição informada.' }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="openTutorialModal"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition"
                                    >
                                        <x-heroicon-m-book-open class="h-4 w-4 text-primary-500" />
                                        <span>Guia de Criação</span>
                                    </button>

                                    <a
                                        href="{{ route('lms.player', ['lesson' => $this->selectedLesson?->id, 'preview' => 1]) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-500 transition"
                                    >
                                        <x-heroicon-m-play class="h-4 w-4" />
                                        <span>Testar Aula no Player</span>
                                    </a>
                                </div>
                            </div>

                            <!-- AS 5 FASES DA METODOLOGIA JESUÍTICA -->
                            @php
                                $phases = [
                                    'praelectio' => [
                                        'title' => '1. Praelectio (Contexto & Teoria)',
                                        'icon' => 'heroicon-m-light-bulb',
                                        'color' => 'blue',
                                        'desc' => 'Pílula curta e concisa de explicação sem enrolação.',
                                    ],
                                    'practice' => [
                                        'title' => '2. Experientia (Prática Guiada no Simulador)',
                                        'icon' => 'heroicon-m-cursor-arrow-ripple',
                                        'color' => 'purple',
                                        'desc' => 'Mão na massa imediata: o aluno clica e interage na tela.',
                                    ],
                                    'repetitio' => [
                                        'title' => '3. Repetitio (Fixação & Feedback de Erro)',
                                        'icon' => 'heroicon-m-arrow-path',
                                        'color' => 'amber',
                                        'desc' => 'Se errar, o sistema explica o porquê e força tentar de novo.',
                                    ],
                                    'concertatio' => [
                                        'title' => '4. Concertatio (Desafio Autônomo)',
                                        'icon' => 'heroicon-m-trophy',
                                        'color' => 'rose',
                                        'desc' => 'Estudo de caso onde o aluno faz sozinho sem muletas nem dicas.',
                                    ],
                                    'examen' => [
                                        'title' => '5. Examen (Avaliação de Maestria)',
                                        'icon' => 'heroicon-m-check-badge',
                                        'color' => 'emerald',
                                        'desc' => 'Prova somativa para garantir retenção de 80% antes de liberar.',
                                    ],
                                ];
                            @endphp

                            <div class="space-y-4">
                                @foreach ($phases as $phaseKey => $phaseData)
                                    @php
                                        $phaseSteps = $this->selectedLesson?->steps->where('phase', $phaseKey) ?? collect();
                                    @endphp

                                    <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/30 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <x-dynamic-component :component="$phaseData['icon']" class="h-4 w-4 text-{{ $phaseData['color'] }}-600" />
                                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-white">{{ $phaseData['title'] }}</h3>
                                                <span class="text-[10px] text-gray-400">({{ $phaseSteps->count() }} passos)</span>
                                            </div>

                                            <button
                                                type="button"
                                                wire:click="openCreateStepModal('{{ $phaseKey }}')"
                                                class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-[11px] font-bold text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition"
                                            >
                                                <x-heroicon-m-plus class="h-3 w-3" />
                                                <span>Adicionar Passo</span>
                                            </button>
                                        </div>

                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $phaseData['desc'] }}</p>

                                        <!-- PASSOS CADASTRADOS NESTA FASE -->
                                        @if($phaseSteps->isNotEmpty())
                                            @php
                                                $stepNumbers = $this->getPhaseStepNumbers($phaseSteps);
                                            @endphp
                                            <div class="space-y-2 pt-1">
                                                @foreach ($phaseSteps as $step)
                                                    @php
                                                        $stepNum = $stepNumbers[$step->id] ?? (string) $loop->iteration;
                                                        $isSub = str_contains($stepNum, '.');
                                                    @endphp
                                                    <div class="flex items-center justify-between rounded-xl bg-white p-3 border transition {{ $isSub ? 'ml-5 sm:ml-7 border-amber-300/80 bg-amber-50/25 dark:bg-amber-950/20 dark:border-amber-700/60 shadow-xs' : 'border-gray-200/80 shadow-xs dark:bg-gray-900 dark:border-gray-700/80' }}">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="inline-flex items-center justify-center rounded-md px-1.5 py-0.5 text-[10px] font-black {{ $isSub ? 'bg-amber-500 text-slate-950 shadow-xs font-mono' : 'bg-gray-900 text-white dark:bg-gray-700 font-mono' }}">
                                                                    {{ $isSub ? '↳ ' . $stepNum : $stepNum }}
                                                                </span>

                                                                <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[9px] font-black uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                                    {{ $step->step_type }}
                                                                </span>

                                                                <span class="font-bold text-xs text-gray-900 dark:text-white">{{ $step->title }}</span>

                                                                @if($isSub)
                                                                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                                                        Subpasso
                                                                    </span>
                                                                @endif

                                                                @if(!empty($step->content['min_stay_seconds']))
                                                                    <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] font-bold text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800" title="Tempo de permanência obrigatório">
                                                                        ⏱️ {{ $step->content['min_stay_seconds'] }}s
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if(!empty($step->content['body']))
                                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate max-w-lg">{{ $step->content['body'] }}</p>
                                                            @elseif(!empty($step->content['question']))
                                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate max-w-lg">❓ {{ $step->content['question'] }}</p>
                                                            @elseif(!empty($step->content['instructions']))
                                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate max-w-lg">🖱️ {{ $step->content['instructions'] }}</p>
                                                            @endif
                                                        </div>

                                                        <div class="flex items-center gap-1">
                                                            <button
                                                                type="button"
                                                                wire:click="openCreateSubStepModal({{ $step->id }})"
                                                                class="inline-flex items-center gap-0.5 rounded-lg border border-amber-300 bg-amber-50 px-2 py-1 text-[10px] font-extrabold text-amber-800 hover:bg-amber-100 dark:border-amber-700/60 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/60 transition cursor-pointer"
                                                                title="Adicionar Subpasso / Próxima página deste mesmo tópico"
                                                            >
                                                                <span>+ Subpasso</span>
                                                            </button>

                                                            <button
                                                                type="button"
                                                                wire:click="moveStepUp({{ $step->id }})"
                                                                class="text-gray-400 hover:text-primary-600 transition px-1 py-0.5 text-xs font-bold"
                                                                title="Mover Passo para Cima"
                                                            >↑</button>
                                                            <button
                                                                type="button"
                                                                wire:click="moveStepDown({{ $step->id }})"
                                                                class="text-gray-400 hover:text-primary-600 transition px-1 py-0.5 text-xs font-bold"
                                                                title="Mover Passo para Baixo"
                                                            >↓</button>
                                                            <button
                                                                type="button"
                                                                wire:click="duplicateStep({{ $step->id }})"
                                                                class="text-gray-400 hover:text-primary-600 transition p-1 text-xs"
                                                                title="Duplicar Passo"
                                                            >📋</button>

                                                            @if(!empty($step->content['media_url']))
                                                                <button
                                                                    type="button"
                                                                    wire:click="removeStepMedia({{ $step->id }})"
                                                                    wire:confirm="Deseja remover o arquivo de mídia deste passo ('{{ $step->title }}') sem excluir o passo?"
                                                                    class="text-blue-500 hover:text-danger-600 transition p-1"
                                                                    title="Remover arquivo de mídia deste passo"
                                                                >
                                                                    <x-heroicon-m-paper-clip class="h-3.5 w-3.5" />
                                                                </button>
                                                            @endif

                                                            <button
                                                                type="button"
                                                                wire:click="openEditStepModal({{ $step->id }})"
                                                                class="text-gray-400 hover:text-primary-600 transition p-1"
                                                                title="Editar Passo"
                                                            >
                                                                <x-heroicon-m-pencil-square class="h-3.5 w-3.5" />
                                                            </button>

                                                            <button
                                                                type="button"
                                                                wire:click="deleteStep({{ $step->id }})"
                                                                wire:confirm="Deseja excluir este passo da aula?"
                                                                class="text-gray-400 hover:text-danger-600 transition p-1"
                                                                title="Excluir Passo"
                                                            >
                                                                <x-heroicon-m-trash class="h-3.5 w-3.5" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    @endif
                </div>

            </div>
        @endif

        <!-- MODAL: SEÇÃO (CRIAR / EDITAR) -->
        <div x-cloak x-show="$wire.showModuleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-m-folder-plus class="h-5 w-5 text-primary-600" />
                        <span>{{ $editingModuleId ? 'Editar Seção' : 'Criar Nova Seção' }}</span>
                    </h3>
                    <button type="button" wire:click="$set('showModuleModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">Título da Seção</label>
                        <input
                            type="text"
                            wire:model="moduleTitle"
                            placeholder="Ex: Seção 1 - Fundamentos Operacionais"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-medium text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500 transition"
                        />
                        @error('moduleTitle') <p class="text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">Descrição da Seção (opcional)</label>
                        <textarea
                            wire:model="moduleDescription"
                            rows="3"
                            placeholder="Breve descrição dos objetivos desta seção..."
                            class="block w-full rounded-xl border border-gray-300 bg-white p-3 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500 transition"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="$set('showModuleModal', false)"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="saveModule"
                        class="rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white hover:bg-primary-500 shadow-sm transition"
                    >
                        {{ $editingModuleId ? 'Atualizar Seção' : 'Salvar Seção' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: AULA (CRIAR / EDITAR) -->
        <div x-cloak x-show="$wire.showLessonModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-m-document-plus class="h-5 w-5 text-emerald-600" />
                        <span>{{ $editingLessonId ? 'Editar Aula' : 'Criar Nova Aula' }}</span>
                    </h3>
                    <button type="button" wire:click="$set('showLessonModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- SELETOR DE SEÇÃO ONDE A AULA ESTÁ ALOCADA -->
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">📁 Seção de Destino</label>
                        <select
                            wire:model="selectedModuleId"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-gray-900 shadow-xs focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-primary-500 transition"
                        >
                            @foreach ($this->selectedCourse?->lmsModules ?? [] as $mod)
                                <option value="{{ $mod->id }}">{{ $mod->title }}</option>
                            @endforeach
                        </select>
                        @error('selectedModuleId') <p class="text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">Título da Aula</label>
                        <input
                            type="text"
                            wire:model="lessonTitle"
                            placeholder="Ex: Aula 02 - Criando Fórmulas no Excel"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-medium text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500 transition"
                        />
                        @error('lessonTitle') <p class="text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">⏱️ Duração Meta (minutos)</label>
                            <input
                                type="number"
                                wire:model="lessonDuration"
                                class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-gray-900 shadow-xs focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-primary-500 transition"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">🎯 Nota Mínima (%)</label>
                            <input
                                type="number"
                                wire:model="lessonPassingScore"
                                class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-gray-900 shadow-xs focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-primary-500 transition"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">Descrição da Aula (opcional)</label>
                        <textarea
                            wire:model="lessonDescription"
                            rows="2"
                            placeholder="Resumo do conteúdo programático..."
                            class="block w-full rounded-xl border border-gray-300 bg-white p-3 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500 transition"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="$set('showLessonModal', false)"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="saveLesson"
                        class="rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white hover:bg-primary-500 shadow-sm transition"
                    >
                        {{ $editingLessonId ? 'Atualizar Aula' : 'Salvar Aula' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- PÁGINA / WORKSPACE DEDICADO DE CRIAÇÃO E EDIÇÃO DE CONTEÚDO INTERATIVO (PÁGINA INTEIRA OU TELA CHEIA 100%) -->
        <div
            x-cloak
            x-show="$wire.showStepModal"
            :class="$wire.isStepFullscreen
                ? 'fixed inset-0 z-50 overflow-y-auto bg-gray-100/95 dark:bg-gray-950/98 p-4 sm:p-8 backdrop-blur-md'
                : 'w-full'"
        >
            <div class="w-full max-w-7xl mx-auto rounded-3xl bg-white p-6 sm:p-8 shadow-xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-6">
                <!-- BARRA SUPERIOR DO ESTÚDIO DE CONTEÚDO -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-gray-200 dark:border-gray-800">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="$set('showStepModal', false); $set('isStepFullscreen', false)"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition cursor-pointer"
                            >
                                <x-heroicon-m-arrow-left class="h-4 w-4" />
                                <span>Voltar ao Roteiro da Aula</span>
                            </button>

                            @if($this->selectedCourse && $this->selectedLesson)
                                <span class="rounded-xl bg-primary-50 px-3 py-1 text-[11px] font-bold text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 border border-primary-200/60 dark:border-primary-800/60">
                                    📚 {{ $this->selectedCourse->name }} • 📖 {{ $this->selectedLesson->title }}
                                </span>
                            @endif
                        </div>

                        <h3 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white flex items-center gap-2 pt-1">
                            @if($editingStepId)
                                <x-heroicon-m-pencil-square class="h-6 w-6 text-primary-600" />
                                <span>Editor de Conteúdo em Página Cheia — Editando Passo (Fase: {{ strtoupper($stepPhase) }})</span>
                            @else
                                <x-heroicon-m-plus-circle class="h-6 w-6 text-primary-600" />
                                <span>Estúdio de Criação de Conteúdo — Novo Passo Interativo (Fase: {{ strtoupper($stepPhase) }})</span>
                            @endif
                        </h3>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- BOTÃO MODO TELA CHEIA (100% OVERLAY) / MODO PÁGINA -->
                        <button
                            type="button"
                            wire:click="$toggle('isStepFullscreen')"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-300 bg-indigo-50 px-3.5 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300 dark:hover:bg-indigo-900/60 transition cursor-pointer"
                            title="Alternar entre Página Integrada e Tela Cheia Imersiva (100%)"
                        >
                            @if($isStepFullscreen)
                                <x-heroicon-m-arrows-pointing-in class="h-4 w-4" />
                                <span>Sair da Tela Cheia</span>
                            @else
                                <x-heroicon-m-arrows-pointing-out class="h-4 w-4" />
                                <span>Expandir em Tela Cheia</span>
                            @endif
                        </button>

                        <!-- LINK PARA ABRIR EM UMA NOVA GUIA SEPARADA -->
                        @if($selectedCourseId && $selectedLessonId)
                            <a
                                href="{{ \App\Filament\Studio\Pages\LmsStudio::getUrl(array_filter([
                                    'course_id' => $selectedCourseId,
                                    'lesson_id' => $selectedLessonId,
                                    'edit_step' => $editingStepId,
                                    'create_step_phase' => ! $editingStepId ? $stepPhase : null,
                                ])) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                                title="Abrir este editor em uma aba exclusiva do navegador"
                            >
                                <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4 text-gray-500" />
                                <span>Abrir em Página Separada</span>
                            </a>
                        @endif

                        <!-- BOTÃO SALVAR RÁPIDO NO TOPO -->
                        <button
                            type="button"
                            wire:click="saveStep"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-5 py-2 text-xs font-bold text-white hover:bg-primary-500 shadow-sm transition cursor-pointer"
                        >
                            <x-heroicon-m-check class="h-4 w-4" />
                            <span>Salvar Passo</span>
                        </button>
                    </div>
                </div>
                
                <div class="space-y-6 text-xs">
                    <!-- METADADOS PRINCIPAIS EM 3 COLUNAS AMPLAS -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-3">
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">Fase da Metodologia</label>
                            <select
                                wire:model.live="stepPhase"
                                class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-gray-900 shadow-xs focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-primary-500 transition"
                            >
                                <option value="praelectio">1. Praelectio (Teoria)</option>
                                <option value="practice">2. Experientia (Prática Guiada)</option>
                                <option value="repetitio">3. Repetitio (Fixação & Erro)</option>
                                <option value="concertatio">4. Concertatio (Desafio Autônomo)</option>
                                <option value="examen">5. Examen (Avaliação Final)</option>
                            </select>
                        </div>
                        <div class="lg:col-span-4">
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">Tipo de Interação</label>
                            <select
                                wire:model.live="stepType"
                                class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-gray-900 shadow-xs focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-primary-500 transition"
                            >
                                <option value="theory">Slide de Teoria</option>
                                <option value="quiz">Quiz / Banco de Questões com Sorteio</option>
                                <option value="hotspot_simulation">Simulador Interativo Guiado / Hotspot</option>
                                <option value="challenge">Desafio Autônomo</option>
                                <option value="assignment">📤 Trabalho Prático / Upload de Exercício (Visto)</option>
                            </select>
                        </div>
                        <div class="lg:col-span-5">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-gray-700 dark:text-gray-300">Título do Passo</label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                    <input type="checkbox" wire:model="stepIsSubstep" class="rounded border-gray-300 text-amber-500 shadow-xs focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Subpasso / Próxima Página (ex: 1.1, 1.2)</span>
                                </label>
                            </div>
                            <input
                                type="text"
                                wire:model="stepTitle"
                                placeholder="Ex: Abrindo o Explorador de Arquivos no Windows"
                                class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-medium text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500 transition"
                            />
                            @error('stepTitle') <p class="text-danger-600 mt-1 font-semibold">{{ $message }}</p> @enderror
                        </div>

                        <!-- TEMPO MÍNIMO DE TELA / PERMANÊNCIA OBRIGATÓRIA -->
                        <div class="lg:col-span-12 rounded-xl border border-indigo-200/80 bg-indigo-50/50 p-3 dark:border-indigo-800/80 dark:bg-indigo-950/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="block text-xs font-bold text-indigo-950 dark:text-indigo-200 flex items-center gap-1.5">
                                    <span>⏱️ Tempo de Tela Obrigatório (Pacing)</span>
                                </span>
                                <p class="text-[11px] text-indigo-700/80 dark:text-indigo-300/80">
                                    O aluno não poderá avançar antes que este tempo se esgote (ideal para vídeos, áudios ou leituras).
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 flex-wrap">
                                <div class="flex items-center gap-1">
                                    <input
                                        type="number"
                                        min="0"
                                        step="5"
                                        wire:model="stepMinStaySeconds"
                                        placeholder="0"
                                        class="w-20 rounded-lg border border-indigo-300 bg-white px-2.5 py-1 text-xs font-bold text-indigo-950 focus:border-indigo-500 dark:border-indigo-700 dark:bg-gray-900 dark:text-white text-center"
                                    />
                                    <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300">seg</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="$set('stepMinStaySeconds', 0)" class="px-2 py-1 rounded bg-white text-[10px] font-bold text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">Livre (0s)</button>
                                    <button type="button" wire:click="$set('stepMinStaySeconds', 30)" class="px-2 py-1 rounded bg-white text-[10px] font-bold text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">30s</button>
                                    <button type="button" wire:click="$set('stepMinStaySeconds', 60)" class="px-2 py-1 rounded bg-white text-[10px] font-bold text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">1 min</button>
                                    <button type="button" wire:click="$set('stepMinStaySeconds', 120)" class="px-2 py-1 rounded bg-white text-[10px] font-bold text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">2 min</button>
                                    <button type="button" wire:click="$set('stepMinStaySeconds', 180)" class="px-2 py-1 rounded bg-white text-[10px] font-bold text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">3 min</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEÇÃO DE MÍDIA (IMAGEM, ÁUDIO NARRADO OU VÍDEO COM UPLOAD DO COMPUTADOR) -->
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50 space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <span class="block text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                <x-heroicon-m-photo class="h-4 w-4 text-primary-600" />
                                <span>Mídia de Apoio do Passo (Opcional)</span>
                            </span>

                            <div class="flex items-center gap-1 bg-gray-200/60 dark:bg-gray-700/60 p-0.5 rounded-lg text-[10px] font-bold" x-show="$wire.stepMediaType !== 'none'">
                                <button
                                    type="button"
                                    wire:click="$set('mediaSourceType', 'upload')"
                                    class="px-2 py-0.5 rounded-md transition {{ $mediaSourceType === 'upload' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}"
                                >
                                    📁 Upload do Computador
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('mediaSourceType', 'url')"
                                    class="px-2 py-0.5 rounded-md transition {{ $mediaSourceType === 'url' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}"
                                >
                                    🌐 Link Externo / Web
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Tipo de Mídia</label>
                                <select
                                    wire:model.live="stepMediaType"
                                    class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-900 shadow-xs focus:border-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="none">Nenhuma</option>
                                    <option value="image">🖼️ Imagem / Screenshot</option>
                                    <option value="audio">🎙️ Áudio Narrado</option>
                                    <option value="video">🎥 Vídeo Explicativo</option>
                                    <option value="pdf">📄 Apresentação PDF / Slides</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2" x-show="$wire.stepMediaType !== 'none'">
                                @if($mediaSourceType === 'upload')
                                    <div
                                        x-data="{
                                            isUploading: false,
                                            progress: 0,
                                            uploadError: null,
                                            readChunkAsBase64(blob) {
                                                return new Promise((resolve, reject) => {
                                                    const reader = new FileReader();
                                                    reader.onload = () => resolve(reader.result);
                                                    reader.onerror = () => reject(new Error('Falha ao ler arquivo local.'));
                                                    reader.readAsDataURL(blob);
                                                });
                                            },
                                            async handleChunkedFileUpload(event) {
                                                const file = event.target.files[0];
                                                if (!file) return;

                                                this.uploadError = null;
                                                const maxBytes = 250 * 1024 * 1024; // 250MB
                                                if (file.size > maxBytes) {
                                                    this.uploadError = 'O arquivo excede o limite máximo de 250MB.';
                                                    event.target.value = '';
                                                    return;
                                                }

                                                this.isUploading = true;
                                                this.progress = 1;

                                                try {
                                                    const chunkSize = 512 * 1024; // 512KB por pacote (~680KB em base64, imune a upload_max_filesize=2M e payload.max_size=1M)
                                                    const totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));
                                                    const uploadId = Date.now().toString(36) + '_' + Math.random().toString(36).substring(2, 8);

                                                    for (let i = 0; i < totalChunks; i++) {
                                                        const start = i * chunkSize;
                                                        const end = Math.min(file.size, start + chunkSize);
                                                        const blob = file.slice(start, end);
                                                        const base64Data = await this.readChunkAsBase64(blob);

                                                        const result = await $wire.uploadMediaChunk(
                                                            uploadId,
                                                            file.name,
                                                            i,
                                                            totalChunks,
                                                            base64Data
                                                        );

                                                        if (result && result.error) {
                                                            this.uploadError = result.error;
                                                            this.isUploading = false;
                                                            event.target.value = '';
                                                            return;
                                                        }

                                                        this.progress = Math.round(((i + 1) / totalChunks) * 100);
                                                    }

                                                    this.isUploading = false;
                                                    event.target.value = '';
                                                } catch (err) {
                                                    console.error(err);
                                                    this.isUploading = false;
                                                    this.uploadError = 'Erro durante a transmissão do arquivo. Tente novamente.';
                                                }
                                            }
                                        }"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                                Selecionar Arquivo do Computador
                                            </label>
                                            <span class="text-[10px] font-mono text-gray-500 dark:text-gray-400">
                                                @if($stepMediaType === 'video')
                                                    Limite: 250MB (MP4, WebM, MOV, MKV)
                                                @elseif($stepMediaType === 'image')
                                                    Limite: 15MB (PNG, JPG, WebP)
                                                @elseif($stepMediaType === 'audio')
                                                    Limite: 25MB (MP3, WAV, M4A)
                                                @elseif($stepMediaType === 'pdf')
                                                    Limite: 50MB (PDF)
                                                @else
                                                    Limite: até 250MB
                                                @endif
                                            </span>
                                        </div>
                                        <input
                                            type="file"
                                            @change="handleChunkedFileUpload($event)"
                                            accept="image/*,audio/*,video/*,.pdf,.mkv,.mov"
                                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-950 dark:file:text-primary-300 transition"
                                        />

                                        <!-- BARRA DE PROGRESSO REAL DE UPLOAD EM PACOTES (CHUNKS) -->
                                        <div x-show="isUploading" x-cloak class="mt-2 space-y-1">
                                            <div class="flex items-center justify-between text-xs font-semibold text-primary-600 dark:text-primary-400">
                                                <span class="flex items-center gap-1.5">
                                                    <span class="animate-spin">⏳</span>
                                                    <span>Enviando arquivo em pacotes seguros...</span>
                                                </span>
                                                <span x-text="progress + '%'" class="font-mono font-bold"></span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 overflow-hidden">
                                                <div class="bg-primary-600 h-2 rounded-full transition-all duration-150" :style="'width: ' + progress + '%'"></div>
                                            </div>
                                        </div>

                                        <!-- MENSAGEM DE ERRO DO UPLOAD -->
                                        <div x-show="uploadError" x-cloak class="mt-2 text-xs font-bold text-danger-600 dark:text-danger-400 flex items-start gap-1.5 p-2 bg-red-50 dark:bg-red-950/30 rounded-xl border border-red-200 dark:border-red-900/50">
                                            <span>⚠️</span>
                                            <span x-text="uploadError"></span>
                                        </div>

                                        @error('mediaUpload')
                                            <p class="text-danger-600 dark:text-danger-400 font-bold mt-1 text-xs flex items-center gap-1">
                                                <span>⚠️</span>
                                                <span>{{ $message }}</span>
                                            </p>
                                        @enderror

                                        @if($mediaUpload)
                                            <div class="mt-2.5 p-2.5 rounded-xl border border-primary-200 bg-primary-50/70 dark:border-primary-800 dark:bg-primary-950/40 flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-2 truncate">
                                                    <span class="text-base">📁</span>
                                                    <div class="truncate">
                                                        <span class="font-bold text-primary-950 dark:text-primary-200 block truncate">
                                                            {{ method_exists($mediaUpload, 'getClientOriginalName') ? $mediaUpload->getClientOriginalName() : 'Novo arquivo anexado' }}
                                                        </span>
                                                        <span class="text-[10px] text-primary-600 dark:text-primary-400">
                                                            Aguardando você salvar o passo
                                                        </span>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    wire:click="clearMediaUpload"
                                                    class="shrink-0 ml-2 px-2.5 py-1 text-[11px] font-bold text-danger-600 hover:text-danger-700 bg-white dark:bg-gray-800 rounded-lg border border-danger-200 dark:border-danger-900 shadow-2xs transition"
                                                >
                                                    ✕ Desmarcar
                                                </button>
                                            </div>
                                        @endif

                                        @if($stepMediaUrl && ! $mediaUpload)
                                            <div class="mt-2.5 p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/60 dark:border-emerald-800/60 dark:bg-emerald-950/40 flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-2 truncate">
                                                    <span class="text-base">
                                                        @if($stepMediaType === 'video') 🎥
                                                        @elseif($stepMediaType === 'image') 🖼️
                                                        @elseif($stepMediaType === 'audio') 🎙️
                                                        @elseif($stepMediaType === 'pdf') 📄
                                                        @else 📎
                                                        @endif
                                                    </span>
                                                    <div class="truncate">
                                                        <span class="font-bold text-emerald-950 dark:text-emerald-200 block truncate" title="{{ $stepMediaUrl }}">
                                                            {{ $uploadedFileName ?: (basename(parse_url($stepMediaUrl, PHP_URL_PATH)) ?: $stepMediaUrl) }}
                                                        </span>
                                                        <span class="text-[10px] text-emerald-700 dark:text-emerald-400">
                                                            Arquivo carregado e pronto neste passo
                                                        </span>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    wire:click="removeStepMedia"
                                                    wire:confirm="Tem certeza que deseja remover este arquivo de mídia do passo?"
                                                    class="shrink-0 ml-2 px-2.5 py-1 text-[11px] font-bold text-danger-600 hover:text-danger-700 hover:bg-danger-50 bg-white dark:bg-gray-800 rounded-lg border border-danger-200 dark:border-danger-900 shadow-2xs transition flex items-center gap-1"
                                                >
                                                    <x-heroicon-m-trash class="h-3.5 w-3.5" />
                                                    <span>Remover arquivo</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                                URL ou Link (YouTube, Vimeo, CDN)
                                            </label>
                                            @if($stepMediaUrl)
                                                <button
                                                    type="button"
                                                    wire:click="removeStepMedia"
                                                    wire:confirm="Deseja limpar o link de mídia deste passo?"
                                                    class="text-[10px] font-bold text-danger-600 hover:text-danger-500 transition"
                                                >
                                                    ✕ Limpar Link
                                                </button>
                                            @endif
                                        </div>
                                        <input
                                            type="text"
                                            wire:model="stepMediaUrl"
                                            placeholder="Ex: https://www.youtube.com/watch?v=... ou https://meusite.com/audio.mp3"
                                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                                        />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($stepType === 'theory')
                        <div x-data="{
                            viewMode: 'editor',
                            wrapSelection(before, after, placeholder = 'texto') {
                                const el = this.$refs.theoryTextarea;
                                if (!el) return;
                                const start = el.selectionStart;
                                const end = el.selectionEnd;
                                const text = el.value;
                                const selected = text.substring(start, end) || placeholder;
                                const replacement = before + selected + after;
                                el.value = text.substring(0, start) + replacement + text.substring(end);
                                el.selectionStart = start + before.length;
                                el.selectionEnd = el.selectionStart + selected.length;
                                el.focus();
                                $wire.set('stepBody', el.value);
                            }
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block font-bold text-gray-700 dark:text-gray-300">
                                    Conteúdo Explicativo (Teoria & Conceito)
                                </label>
                                <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                                    Editor Formatado Visual (WYSIWYG)
                                </span>
                            </div>

                            <div class="filament-rich-editor-wrapper">
                                {{ $this->form }}
                            </div>
                        </div>
                    @elseif($stepType === 'hotspot_simulation' || $stepType === 'challenge' || $stepType === 'assignment')
                        <div x-data="{
                            instructionViewMode: 'editor',
                            wrapInstruction(before, after, placeholder = 'texto') {
                                const el = this.$refs.instructionTextarea;
                                if (!el) return;
                                const start = el.selectionStart;
                                const end = el.selectionEnd;
                                const text = el.value;
                                const selected = text.substring(start, end) || placeholder;
                                const replacement = before + selected + after;
                                el.value = text.substring(0, start) + replacement + text.substring(end);
                                el.selectionStart = start + before.length;
                                el.selectionEnd = el.selectionStart + selected.length;
                                el.focus();
                                $wire.set('stepInstructions', el.value);
                            }
                        }" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block font-bold text-gray-700 dark:text-gray-300">
                                    Instruções para o Aluno Executar no Software
                                </label>
                                <div class="flex items-center bg-gray-100 dark:bg-gray-800 p-0.5 rounded-lg text-[10px] font-bold">
                                    <button
                                        type="button"
                                        @click="instructionViewMode = 'editor'"
                                        :class="instructionViewMode === 'editor' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400'"
                                        class="px-2.5 py-1 rounded-md transition"
                                    >
                                        ✏️ Editor Formatado
                                    </button>
                                    <button
                                        type="button"
                                        @click="instructionViewMode = 'preview'"
                                        :class="instructionViewMode === 'preview' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400'"
                                        class="px-2.5 py-1 rounded-md transition"
                                    >
                                        👁️ Pré-visualizar no Player
                                    </button>
                                </div>
                            </div>

                            <!-- BARRA DE FERRAMENTAS DE FORMATAÇÃO RICA PARA INSTRUÇÕES -->
                            <div x-show="instructionViewMode === 'editor'" class="flex flex-wrap items-center gap-1.5 p-1.5 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 text-xs">
                                <button
                                    type="button"
                                    @click="wrapInstruction('**', '**', 'texto em negrito')"
                                    title="Negrito"
                                    class="flex h-7 px-2 items-center justify-center rounded-lg bg-white border border-gray-200 font-black text-gray-800 hover:bg-gray-100 hover:border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700 transition"
                                >
                                    B
                                </button>
                                <button
                                    type="button"
                                    @click="wrapInstruction('*', '*', 'texto em itálico')"
                                    title="Itálico"
                                    class="flex h-7 px-2 items-center justify-center rounded-lg bg-white border border-gray-200 italic font-serif text-gray-800 hover:bg-gray-100 hover:border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700 transition"
                                >
                                    I
                                </button>
                                <button
                                    type="button"
                                    @click="wrapInstruction('<u>', '</u>', 'texto sublinhado')"
                                    title="Sublinhado"
                                    class="flex h-7 px-2 items-center justify-center rounded-lg bg-white border border-gray-200 underline font-semibold text-gray-800 hover:bg-gray-100 hover:border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700 transition"
                                >
                                    U
                                </button>

                                <div class="h-4 w-px bg-gray-300 dark:bg-gray-700 mx-0.5"></div>

                                <button
                                    type="button"
                                    @click="wrapInstruction('## ', '\n', 'Subtítulo da Instrução')"
                                    title="Título de Seção"
                                    class="flex h-7 px-2 items-center justify-center rounded-lg bg-white border border-gray-200 font-bold text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-white transition"
                                >
                                    H2
                                </button>

                                <button
                                    type="button"
                                    @click="wrapInstruction('• ', '\n', 'Passo a executar')"
                                    title="Lista com Marcadores"
                                    class="flex h-7 px-2 items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-white transition"
                                >
                                    • Lista
                                </button>

                                <button
                                    type="button"
                                    @click="wrapInstruction('<kbd>', '</kbd>', 'Ctrl + E')"
                                    title="Tecla de Atalho"
                                    class="flex h-7 px-2 items-center justify-center rounded-lg bg-white border border-gray-200 font-mono text-[11px] text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-white transition"
                                >
                                    ⌨️ Atalho
                                </button>

                                <button
                                    type="button"
                                    @click="wrapInstruction('> 💡 **Atenção:** ', '\n', 'Mantenha pressionado e solte no local indicado.')"
                                    title="Caixa de Dica Prática"
                                    class="flex h-7 px-2 items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-white transition"
                                >
                                    💡 Dica
                                </button>
                            </div>

                            <div x-show="instructionViewMode === 'editor'">
                                <textarea
                                    x-ref="instructionTextarea"
                                    wire:model="stepInstructions"
                                    rows="6"
                                    placeholder="Ex: Pressione <kbd>Windows + E</kbd> para abrir o Explorador ou clique no menu **Iniciar**..."
                                    class="block w-full rounded-2xl border border-gray-300 bg-white p-3.5 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500 leading-relaxed transition"
                                ></textarea>
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    💡 Use os botões acima para destacar comandos em <strong>negrito</strong>, <em>itálico</em>, <u>sublinhado</u> ou <kbd class="px-1 bg-gray-100 dark:bg-gray-800 rounded font-mono">teclas de atalho</kbd>.
                                </p>
                            </div>

                            <!-- PRÉ-VISUALIZAÇÃO IMEDIATA DA INSTRUÇÃO -->
                            <div x-show="instructionViewMode === 'preview'" class="rounded-2xl border border-amber-300 bg-amber-50/60 p-4 dark:border-amber-900/60 dark:bg-amber-950/40 min-h-[90px] text-xs text-amber-950 dark:text-amber-200 leading-relaxed space-y-2">
                                <span class="text-[10px] uppercase font-bold text-amber-700 dark:text-amber-400 tracking-wider block mb-1">
                                    Pré-visualização do Card no Player do Aluno:
                                </span>
                                <div class="prose prose-xs dark:prose-invert max-w-none [&_strong]:font-black [&_u]:underline [&_kbd]:px-1.5 [&_kbd]:py-0.5 [&_kbd]:rounded [&_kbd]:font-mono [&_kbd]:bg-slate-800 [&_kbd]:text-amber-300">
                                    {!! \Illuminate\Support\Str::markdown($stepInstructions ?: '_Nenhuma instrução digitada ainda._') !!}
                                </div>
                            </div>
                        </div>

                        @if($stepType === 'hotspot_simulation')
                            <!-- SELETOR VISUAL DE COORDENADAS DO HOTSPOT (EXPERIENTIA) -->
                            <div class="rounded-2xl border border-purple-200 bg-purple-50/60 p-4 dark:border-purple-800/60 dark:bg-purple-950/30 space-y-4">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-purple-950 dark:text-purple-200 flex items-center gap-1.5">
                                        <x-heroicon-m-cursor-arrow-ripple class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                        <span>Configuração do Alvo Prático no Software (Hotspot / Seleção)</span>
                                    </label>
                                    @if($hotspotX !== null && $hotspotY !== null)
                                        <button
                                            type="button"
                                            wire:click="clearHotspot"
                                            class="text-[11px] font-bold text-danger-600 hover:text-danger-500 transition"
                                        >
                                            ✕ Limpar Marcação
                                        </button>
                                    @endif
                                </div>

                                <!-- SELEÇÃO DO TIPO DE AÇÃO / INTERAÇÃO -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Ação Esperada</label>
                                        <select
                                            wire:model.live="hotspotInteractionType"
                                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-900 shadow-xs focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >
                                            <option value="click">🖱️ Clique Simples (Botão Esquerdo)</option>
                                            <option value="select_text">↔️ Selecionar Texto / Arrastar Área</option>
                                            <option value="type_text">⌨️ Digitar Texto / Comando</option>
                                            <option value="double_click">🖱️🖱️ Duplo Clique (Abrir/Executar)</option>
                                            <option value="right_click">🖱️ Botão Direito (Menu de Contexto)</option>
                                        </select>
                                    </div>

                                    @if($hotspotInteractionType === 'select_text')
                                        <div>
                                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Sentido Obrigatório da Seleção</label>
                                            <select
                                                wire:model="hotspotDirection"
                                                class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-900 shadow-xs focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            >
                                                <option value="left_to_right">➔ Da Esquerda para a Direita (Padrão)</option>
                                                <option value="right_to_left">⬅ Da Direita para a Esquerda</option>
                                                <option value="any">↔ Qualquer Direção (Livre)</option>
                                            </select>
                                        </div>
                                    @elseif($hotspotInteractionType === 'type_text')
                                        <div>
                                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Identificação do Campo</label>
                                            <input
                                                type="text"
                                                wire:model="hotspotLabel"
                                                placeholder="Ex: Barra de Fórmulas ou Campo Executar"
                                                class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-xs focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            />
                                        </div>
                                    @else
                                        <div>
                                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome do Botão/Alvo</label>
                                            <input
                                                type="text"
                                                wire:model="hotspotLabel"
                                                placeholder="Ex: Botão Salvar ou Menu Iniciar"
                                                class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-xs focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            />
                                        </div>
                                    @endif
                                </div>

                                @if($hotspotInteractionType === 'type_text')
                                    <!-- PAINEL DE CONFIGURAÇÃO DE DIGITAÇÃO DE TEXTO -->
                                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-3.5 dark:border-indigo-800/60 dark:bg-indigo-950/30 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                            <div>
                                                <label class="block font-bold text-indigo-950 dark:text-indigo-200 mb-1">
                                                    Texto ou Comando Exato Esperado
                                                </label>
                                                <input
                                                    type="text"
                                                    wire:model="hotspotExpectedText"
                                                    placeholder="Ex: =SOMA(A1:B5) ou Relatório Semestral ou calc"
                                                    class="block w-full rounded-xl border border-indigo-300 bg-white px-3 py-2 text-xs font-mono font-bold text-gray-900 shadow-xs focus:border-indigo-500 dark:border-indigo-700 dark:bg-gray-800 dark:text-white"
                                                />
                                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                    O aluno deverá digitar exatamente este texto para ter a prática validada.
                                                </p>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block font-semibold text-gray-700 dark:text-gray-300">Regras de Confronto e Validação</label>
                                                <div class="flex flex-col gap-1.5 text-xs">
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" wire:model="hotspotCaseSensitive" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                        <span class="text-gray-700 dark:text-gray-300">Diferenciar maiúsculas/minúsculas (Case Sensitive)</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" wire:model="hotspotTrimWhitespace" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                        <span class="text-gray-700 dark:text-gray-300">Ignorar espaços extras no início e fim (Trim)</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                    @if($hotspotInteractionType === 'select_text')
                                        <div>
                                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome do Texto/Elemento</label>
                                            <input
                                                type="text"
                                                wire:model="hotspotLabel"
                                                placeholder="Ex: Palavra ou Título a Selecionar"
                                                class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-xs focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            />
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Tolerância de Proximidade (%)</label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            min="2"
                                            max="30"
                                            wire:model="hotspotRadius"
                                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-900 shadow-xs focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        />
                                    </div>

                                    @if($hotspotInteractionType === 'select_text')
                                        <div>
                                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">1. Início da Seleção (X1% , Y1%)</label>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    wire:model="hotspotX"
                                                    placeholder="Início X"
                                                    class="block w-1/2 rounded-xl border border-gray-300 bg-white px-2.5 py-2 text-xs text-center font-mono font-bold text-purple-700 dark:text-purple-300 shadow-xs dark:border-gray-700 dark:bg-gray-800"
                                                />
                                                <span class="text-gray-400">×</span>
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    wire:model="hotspotY"
                                                    placeholder="Início Y"
                                                    class="block w-1/2 rounded-xl border border-gray-300 bg-white px-2.5 py-2 text-xs text-center font-mono font-bold text-purple-700 dark:text-purple-300 shadow-xs dark:border-gray-700 dark:bg-gray-800"
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">2. Fim da Seleção (X2% , Y2%)</label>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    wire:model="hotspotEndX"
                                                    placeholder="Fim X"
                                                    class="block w-1/2 rounded-xl border border-gray-300 bg-white px-2.5 py-2 text-xs text-center font-mono font-bold text-indigo-700 dark:text-indigo-300 shadow-xs dark:border-gray-700 dark:bg-gray-800"
                                                />
                                                <span class="text-gray-400">×</span>
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    wire:model="hotspotEndY"
                                                    placeholder="Fim Y"
                                                    class="block w-1/2 rounded-xl border border-gray-300 bg-white px-2.5 py-2 text-xs text-center font-mono font-bold text-indigo-700 dark:text-indigo-300 shadow-xs dark:border-gray-700 dark:bg-gray-800"
                                                />
                                            </div>
                                        </div>
                                    @else
                                        <div>
                                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Coordenadas do Alvo (X% , Y%)</label>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    wire:model="hotspotX"
                                                    placeholder="X %"
                                                    class="block w-1/2 rounded-xl border border-gray-300 bg-white px-2.5 py-2 text-xs text-center font-mono font-bold text-purple-700 dark:text-purple-300 shadow-xs dark:border-gray-700 dark:bg-gray-800"
                                                />
                                                <span class="text-gray-400">×</span>
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    wire:model="hotspotY"
                                                    placeholder="Y %"
                                                    class="block w-1/2 rounded-xl border border-gray-300 bg-white px-2.5 py-2 text-xs text-center font-mono font-bold text-purple-700 dark:text-purple-300 shadow-xs dark:border-gray-700 dark:bg-gray-800"
                                                />
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- CANVAS / IMAGEM INTERATIVA PARA MARCAÇÃO -->
                                @php
                                    $previewImg = $this->stepPreviewImageUrl;
                                @endphp

                                @if($previewImg)
                                    <div class="space-y-1.5 pt-1">
                                        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                                            @if($hotspotInteractionType === 'select_text')
                                                <span>↔️ Clique e arraste sobre o texto para marcar a seleção (ou clique no início e depois no fim):</span>
                                                @if($hotspotX !== null && $hotspotEndX !== null)
                                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">De ({{ $hotspotX }}%, {{ $hotspotY }}%) até ({{ $hotspotEndX }}%, {{ $hotspotEndY }}%)</span>
                                                @endif
                                            @elseif($hotspotInteractionType === 'type_text')
                                                <span>⌨️ Clique sobre a imagem para posicionar o campo onde o aluno deverá digitar o texto/comando:</span>
                                                @if($hotspotX !== null && $hotspotY !== null)
                                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">Posicionado em: {{ $hotspotX }}% , {{ $hotspotY }}%</span>
                                                @endif
                                            @else
                                                <span>🎯 Clique sobre a imagem abaixo para posicionar o alvo exatamente onde o aluno deve interagir:</span>
                                                @if($hotspotX !== null && $hotspotY !== null)
                                                    <span class="font-bold text-purple-600 dark:text-purple-400">Marcado em: {{ $hotspotX }}% , {{ $hotspotY }}%</span>
                                                @endif
                                            @endif
                                        </div>

                                        <div
                                            x-data="{
                                                mode: '{{ $hotspotInteractionType }}',
                                                isDragging: false,
                                                dragStartX: 0,
                                                dragStartY: 0,
                                                currentX: 0,
                                                currentY: 0,
                                                handleMouseDown(event) {
                                                    const rect = event.currentTarget.getBoundingClientRect();
                                                    const x = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    const y = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                    if ('{{ $hotspotInteractionType }}' === 'select_text') {
                                                        this.isDragging = true;
                                                        this.dragStartX = x;
                                                        this.dragStartY = y;
                                                        this.currentX = x;
                                                        this.currentY = y;
                                                    } else {
                                                        $wire.setHotspotCoordinates(x, y);
                                                    }
                                                },
                                                handleMouseMove(event) {
                                                    if (!this.isDragging || '{{ $hotspotInteractionType }}' !== 'select_text') return;
                                                    const rect = event.currentTarget.getBoundingClientRect();
                                                    this.currentX = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    this.currentY = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                },
                                                handleMouseUp(event) {
                                                    if (!this.isDragging || '{{ $hotspotInteractionType }}' !== 'select_text') return;
                                                    this.isDragging = false;
                                                    const rect = event.currentTarget.getBoundingClientRect();
                                                    const endX = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    const endY = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                    // Se foi apenas um clique sem arrastar, usa o setHotspotCoordinates normal
                                                    if (Math.abs(endX - this.dragStartX) < 1 && Math.abs(endY - this.dragStartY) < 1) {
                                                        $wire.setHotspotCoordinates(this.dragStartX, this.dragStartY);
                                                    } else {
                                                        $wire.setHotspotSelection(this.dragStartX, this.dragStartY, endX, endY);
                                                    }
                                                }
                                            }"
                                            @mousedown="handleMouseDown($event)"
                                            @mousemove="handleMouseMove($event)"
                                            @mouseup="handleMouseUp($event)"
                                            class="relative overflow-hidden rounded-2xl border-2 border-dashed border-purple-400/80 bg-slate-900 cursor-crosshair shadow-inner select-none"
                                        >
                                            <img
                                                src="{{ $previewImg }}"
                                                alt="Screenshot da Simulação"
                                                class="w-full h-auto object-contain max-h-72 pointer-events-none"
                                            />

                                            <!-- SELEÇÃO DE TEXTO / ÁREA -->
                                            @if($hotspotInteractionType === 'select_text')
                                                <!-- CAIXA DE SELEÇÃO DINÂMICA AO ARRASTAR -->
                                                <div
                                                    x-show="isDragging"
                                                    class="absolute bg-blue-500/30 border-2 border-blue-400 rounded-sm pointer-events-none transition-none"
                                                    :style="`left: ${Math.min(dragStartX, currentX)}%; top: ${Math.min(dragStartY, currentY)}%; width: ${Math.max(1, Math.abs(currentX - dragStartX))}%; height: ${Math.max(2, Math.abs(currentY - dragStartY))}%;`"
                                                ></div>

                                                <!-- CAIXA DE SELEÇÃO SALVA -->
                                                @if($hotspotX !== null && $hotspotEndX !== null)
                                                    @php
                                                        $boxLeft = min($hotspotX, $hotspotEndX);
                                                        $boxTop = min($hotspotY, $hotspotEndY ?? $hotspotY);
                                                        $boxWidth = max(2, abs($hotspotEndX - $hotspotX));
                                                        $boxHeight = max(3, abs(($hotspotEndY ?? $hotspotY) - $hotspotY));
                                                    @endphp
                                                    <div
                                                        class="absolute bg-blue-500/35 border-2 border-blue-400 shadow-md rounded-sm pointer-events-none flex items-center justify-center"
                                                        style="left: {{ $boxLeft }}%; top: {{ $boxTop }}%; width: {{ $boxWidth }}%; height: {{ $boxHeight }}%;"
                                                    >
                                                        <span class="text-[9px] font-bold text-white bg-blue-900/80 px-1 rounded shadow">
                                                            {{ $hotspotDirection === 'right_to_left' ? '⬅ Seleção' : '➔ Seleção' }}
                                                        </span>
                                                    </div>

                                                    <!-- MARCADOR 1: INÍCIO -->
                                                    <div
                                                        class="absolute pointer-events-none transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center"
                                                        style="left: {{ $hotspotX }}%; top: {{ $hotspotY }}%;"
                                                    >
                                                        <div class="h-3.5 w-3.5 rounded-full bg-emerald-500 ring-2 ring-white shadow-md animate-pulse"></div>
                                                        <span class="mt-0.5 whitespace-nowrap rounded bg-emerald-950/90 border border-emerald-500/40 px-1 py-0.2 text-[9px] font-bold text-emerald-200">
                                                            1. Início
                                                        </span>
                                                    </div>

                                                    <!-- MARCADOR 2: FIM -->
                                                    <div
                                                        class="absolute pointer-events-none transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center"
                                                        style="left: {{ $hotspotEndX }}%; top: {{ $hotspotEndY ?? $hotspotY }}%;"
                                                    >
                                                        <div class="h-3.5 w-3.5 rounded-full bg-indigo-500 ring-2 ring-white shadow-md"></div>
                                                        <span class="mt-0.5 whitespace-nowrap rounded bg-indigo-950/90 border border-indigo-500/40 px-1 py-0.2 text-[9px] font-bold text-indigo-200">
                                                            2. Fim
                                                        </span>
                                                    </div>
                                                @elseif($hotspotX !== null)
                                                    <!-- PONTO 1 MARCADO (AGUARDANDO PONTO 2) -->
                                                    <div
                                                        class="absolute pointer-events-none transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center"
                                                        style="left: {{ $hotspotX }}%; top: {{ $hotspotY }}%;"
                                                    >
                                                        <div class="h-4 w-4 rounded-full bg-amber-400 ring-2 ring-white animate-ping"></div>
                                                        <span class="mt-1 whitespace-nowrap rounded bg-amber-950 px-1.5 py-0.5 text-[10px] font-bold text-amber-200">
                                                            Início marcado. Clique no ponto final!
                                                        </span>
                                                    </div>
                                                @endif

                                            <!-- DIGITAÇÃO DE TEXTO / COMANDO -->
                                            @elseif($hotspotInteractionType === 'type_text')
                                                @if($hotspotX !== null && $hotspotY !== null)
                                                    <div
                                                        class="absolute pointer-events-none transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center z-20"
                                                        style="left: {{ $hotspotX }}%; top: {{ $hotspotY }}%; width: {{ $hotspotInputWidth }}%; min-width: 160px;"
                                                    >
                                                        <div class="w-full rounded-lg border-2 border-indigo-400 bg-white/95 px-2.5 py-1 text-xs font-mono font-semibold text-indigo-950 shadow-xl flex items-center justify-between">
                                                            <span class="truncate">{{ $hotspotExpectedText ?: 'Texto esperado...' }}</span>
                                                            <span class="text-[9px] bg-indigo-600 text-white font-sans px-1.5 py-0.5 rounded font-bold">↵ Enter</span>
                                                        </div>
                                                        <span class="mt-1 whitespace-nowrap rounded bg-indigo-950/90 border border-indigo-500/40 px-2 py-0.5 text-[10px] font-bold text-indigo-200 shadow-md">
                                                            ⌨️ Posição do Campo de Digitação
                                                        </span>
                                                    </div>
                                                @endif

                                            <!-- CLIQUE SIMPLES, DUPLO OU DIREITO -->
                                            @else
                                                @if($hotspotX !== null && $hotspotY !== null)
                                                    <!-- MARCADOR VISUAL DO ALVO -->
                                                    <div
                                                        class="absolute pointer-events-none transform -translate-x-1/2 -translate-y-1/2 flex items-center justify-center transition-all duration-150"
                                                        style="left: {{ $hotspotX }}%; top: {{ $hotspotY }}%;"
                                                    >
                                                        <div
                                                            class="rounded-full border-2 border-emerald-400 bg-emerald-500/30 animate-pulse shadow-lg"
                                                            style="width: {{ max(24, $hotspotRadius * 5) }}px; height: {{ max(24, $hotspotRadius * 5) }}px;"
                                                        ></div>
                                                        <div class="absolute h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-white"></div>
                                                        @if(!empty($hotspotLabel))
                                                            <span class="absolute top-full mt-1 whitespace-nowrap rounded-md bg-gray-950/80 px-2 py-0.5 text-[10px] font-bold text-white backdrop-blur">
                                                                {{ $hotspotLabel }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900/50 p-4 text-center text-xs text-gray-500 dark:text-gray-400">
                                        <p class="font-semibold text-gray-700 dark:text-gray-300">💡 Nenhum screenshot carregado ainda</p>
                                        <p class="mt-0.5 text-[11px]">Faça upload de uma screenshot ou imagem do software na seção de "Mídia de Apoio" acima. Assim você poderá clicar diretamente na imagem para marcar o local exato com precisão.</p>
                                    </div>
                                @endif

                                <!-- SEQUÊNCIA DE CLIQUES GUIADOS MULTI-PASSOS (OPCIONAL) -->
                                <div class="pt-3 border-t border-purple-200/70 dark:border-purple-800/60 space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-xs font-bold text-purple-950 dark:text-purple-200 block">
                                                🎯 Sequência Multi-Passos (Simulador Guiado Passo a Passo)
                                            </span>
                                            <span class="text-[11px] text-purple-800/80 dark:text-purple-300/80">
                                                Adicione etapas extras caso o aluno precise clicar em múltiplos menus em sequência (ex: 1. Menu Inserir → 2. Tabela → 3. Confirmar).
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="addSimulationSubStep"
                                            class="shrink-0 rounded-xl bg-purple-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-purple-500 transition shadow-2xs"
                                        >
                                            + Adicionar Sub-passo
                                        </button>
                                    </div>

                                    @foreach($simulationSubSteps as $subIdx => $subStep)
                                        <div class="rounded-xl border border-purple-300/70 bg-white/90 dark:border-purple-800 dark:bg-gray-900/90 p-3 space-y-2 text-xs">
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-purple-700 dark:text-purple-300">
                                                    Sub-passo #{{ $subIdx + 1 }} da Simulação
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="removeSimulationSubStep({{ $subIdx }})"
                                                    class="text-[11px] font-bold text-danger-600 hover:text-danger-500"
                                                >
                                                    ✕ Remover
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <div class="sm:col-span-2">
                                                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-0.5">Instrução deste clique/ação</label>
                                                    <input
                                                        type="text"
                                                        wire:model="simulationSubSteps.{{ $subIdx }}.instruction"
                                                        placeholder="Ex: Agora clique na guia Inserir no topo do Word"
                                                        class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-0.5">Ação</label>
                                                    <select
                                                        wire:model="simulationSubSteps.{{ $subIdx }}.interaction_type"
                                                        class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                                    >
                                                        <option value="click">🖱️ Clique Simples</option>
                                                        <option value="double_click">🖱️🖱️ Duplo Clique</option>
                                                        <option value="right_click">🖱️ Botão Direito</option>
                                                        <option value="type_text">⌨️ Digitar Texto</option>
                                                    </select>
                                                </div>
                                                <div class="grid grid-cols-3 gap-1.5">
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-gray-600 dark:text-gray-400 mb-0.5">X (%)</label>
                                                        <input type="number" step="0.5" wire:model="simulationSubSteps.{{ $subIdx }}.x" class="block w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-center font-mono dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-gray-600 dark:text-gray-400 mb-0.5">Y (%)</label>
                                                        <input type="number" step="0.5" wire:model="simulationSubSteps.{{ $subIdx }}.y" class="block w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-center font-mono dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-gray-600 dark:text-gray-400 mb-0.5">Raio</label>
                                                        <input type="number" step="1" wire:model="simulationSubSteps.{{ $subIdx }}.radius" class="block w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-center font-mono dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($stepType === 'assignment')
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-800/60 dark:bg-emerald-950/30 space-y-3">
                                <div class="flex items-center gap-2 text-xs font-bold text-emerald-950 dark:text-emerald-200">
                                    <span>📤</span>
                                    <span>Configurações de Envio de Trabalho Prático (Visto do Educador)</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Formatos Aceitos</label>
                                        <input
                                            type="text"
                                            wire:model="assignmentAllowedExtensions"
                                            placeholder="xlsx,docx,pdf,png,jpg,zip"
                                            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-mono text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        />
                                    </div>
                                    <div class="flex items-center pt-5">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                wire:model="assignmentRequiresEducatorApproval"
                                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                                Aguardar visto do educador para aprovar (opcional)
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @elseif($stepType === 'quiz')
                        <!-- QUESTÃO PRINCIPAL #1 DO PASSO / BANCO -->
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-5 dark:border-gray-800 dark:bg-gray-800/40 space-y-4">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <label class="block text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-600 text-white text-xs font-black">#1</span>
                                    <span>Pergunta Principal do Quiz (Enunciado Completo)</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <label class="font-bold text-gray-700 dark:text-gray-300">Gabarito da Questão #1:</label>
                                    <select wire:model="stepCorrectOption" class="rounded-xl border border-emerald-400 bg-emerald-50 px-3.5 py-1.5 text-xs font-black text-emerald-900 shadow-xs focus:border-emerald-500 dark:border-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <option value="A">✓ Alternativa A é a Correta</option>
                                        <option value="B">✓ Alternativa B é a Correta</option>
                                        <option value="C">✓ Alternativa C é a Correta</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <textarea
                                    wire:model="stepQuestion"
                                    rows="3"
                                    placeholder="Digite o enunciado completo da questão... (Ex: Sobre o tema abordado nesta aula, qual procedimento ou atalho deve ser executado para atingir o resultado esperado?)"
                                    class="block w-full rounded-xl border border-gray-300 bg-white p-3.5 text-xs font-medium text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500 leading-relaxed transition"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1.5">
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-gray-200 dark:bg-gray-700 text-[10px] font-black">A</span>
                                        <span>Alternativa A</span>
                                    </label>
                                    <textarea
                                        wire:model="stepOptionA"
                                        rows="3"
                                        placeholder="Texto completo da Alternativa A..."
                                        class="block w-full rounded-xl border border-gray-300 bg-white p-3 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 leading-relaxed"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1.5">
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-gray-200 dark:bg-gray-700 text-[10px] font-black">B</span>
                                        <span>Alternativa B</span>
                                    </label>
                                    <textarea
                                        wire:model="stepOptionB"
                                        rows="3"
                                        placeholder="Texto completo da Alternativa B..."
                                        class="block w-full rounded-xl border border-gray-300 bg-white p-3 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 leading-relaxed"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1.5">
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-gray-200 dark:bg-gray-700 text-[10px] font-black">C</span>
                                        <span>Alternativa C</span>
                                    </label>
                                    <textarea
                                        wire:model="stepOptionC"
                                        rows="3"
                                        placeholder="Texto completo da Alternativa C..."
                                        class="block w-full rounded-xl border border-gray-300 bg-white p-3 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 leading-relaxed"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- BANCO DE QUESTÕES COM SORTEIO ALEATÓRIO & ANTI-COLA -->
                        <div class="rounded-2xl border border-cyan-200 bg-cyan-50/50 p-5 dark:border-cyan-800/60 dark:bg-cyan-950/20 space-y-4">
                            <div class="flex items-center justify-between flex-wrap gap-3 pb-3 border-b border-cyan-200/70 dark:border-cyan-800/50">
                                <div class="space-y-1">
                                    <span class="text-sm font-black text-cyan-950 dark:text-cyan-200 flex items-center gap-2">
                                        <span>🎲</span>
                                        <span>Banco de Questões com Sorteio Aleatório, Anti-Cola & Prova Final de Curso</span>
                                    </span>
                                    <p class="text-xs text-cyan-800/90 dark:text-cyan-300/90">
                                        As questões abaixo (junto com a Questão #1 acima) alimentam tanto o <strong>sorteio anti-cola desta aula</strong> quanto o <strong>Exame Final de Certificação do Curso</strong> (que sorteia até 20 questões de todas as aulas e módulos).
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="addQuizPoolQuestion"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-cyan-600 px-4 py-2 text-xs font-bold text-white hover:bg-cyan-500 transition shadow-sm cursor-pointer"
                                >
                                    <x-heroicon-m-plus class="h-4 w-4" />
                                    <span>Adicionar Questão ao Banco ({{ count($quizQuestionPool) + 1 }} no total)</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1 text-xs">
                                <label class="inline-flex items-center gap-2.5 cursor-pointer rounded-xl bg-white/80 dark:bg-gray-900/60 p-3 border border-cyan-200/70 dark:border-cyan-800/60">
                                    <input type="checkbox" wire:model="quizShuffleOptions" class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500" />
                                    <span class="font-bold text-gray-800 dark:text-gray-200">🔀 Embaralhar Alternativas (A/B/C)</span>
                                </label>
                                <label class="inline-flex items-center gap-2.5 cursor-pointer rounded-xl bg-white/80 dark:bg-gray-900/60 p-3 border border-cyan-200/70 dark:border-cyan-800/60">
                                    <input type="checkbox" wire:model="quizRandomize" class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500" />
                                    <span class="font-bold text-gray-800 dark:text-gray-200">🎲 Sortear do Banco de Questões</span>
                                </label>
                                <div class="flex items-center justify-between gap-2 rounded-xl bg-white/80 dark:bg-gray-900/60 p-3 border border-cyan-200/70 dark:border-cyan-800/60">
                                    <label class="font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">Quantas sortear na aula:</label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="20"
                                        wire:model="quizQuestionsToDraw"
                                        class="w-20 rounded-xl border border-gray-300 bg-white px-2.5 py-1 text-xs text-center font-black dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>
                            </div>

                            @if(count($quizQuestionPool) > 0)
                                <div class="grid grid-cols-1 gap-4 pt-2">
                                    @foreach($quizQuestionPool as $qIdx => $poolQ)
                                        <div class="rounded-2xl border border-cyan-300/80 bg-white dark:border-cyan-800 dark:bg-gray-900 p-5 space-y-4 text-xs shadow-2xs">
                                            <div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-gray-100 dark:border-gray-800">
                                                <span class="text-sm font-black text-cyan-700 dark:text-cyan-300 flex items-center gap-2">
                                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-cyan-600 text-white text-xs font-black">#{{ $qIdx + 2 }}</span>
                                                    <span>Questão Extra #{{ $qIdx + 2 }} do Banco</span>
                                                </span>

                                                <div class="flex items-center gap-3">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-bold text-gray-600 dark:text-gray-400">Gabarito:</span>
                                                        <select wire:model="quizQuestionPool.{{ $qIdx }}.correct_option" class="rounded-xl border border-emerald-400 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-900 dark:border-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                            <option value="A">✓ Correta: A</option>
                                                            <option value="B">✓ Correta: B</option>
                                                            <option value="C">✓ Correta: C</option>
                                                        </select>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        wire:click="removeQuizPoolQuestion({{ $qIdx }})"
                                                        class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-2.5 py-1 text-[11px] font-bold text-danger-600 hover:bg-red-100 dark:bg-red-950/40 dark:text-danger-400 transition cursor-pointer"
                                                    >
                                                        <span>✕ Remover Questão</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- ENUNCIADO DA QUESTÃO EM TEXTAREA -->
                                            <div>
                                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Enunciado da Questão #{{ $qIdx + 2 }}</label>
                                                <textarea
                                                    wire:model="quizQuestionPool.{{ $qIdx }}.question"
                                                    rows="3"
                                                    placeholder="Digite o enunciado completo da questão..."
                                                    class="block w-full rounded-xl border border-gray-300 bg-white p-3 text-xs font-medium text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white leading-relaxed focus:border-cyan-500 focus:outline-none"
                                                ></textarea>
                                            </div>

                                            <!-- ALTERNATIVAS A, B, C EM TEXTAREAS AMPLAS (3 COLUNAS NO DESKTOP) -->
                                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                                                <div>
                                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Alternativa A</label>
                                                    <textarea
                                                        wire:model="quizQuestionPool.{{ $qIdx }}.option_a"
                                                        rows="2"
                                                        placeholder="Texto completo da Opção A"
                                                        class="block w-full rounded-xl border border-gray-300 bg-white p-2.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white leading-relaxed"
                                                    ></textarea>
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Alternativa B</label>
                                                    <textarea
                                                        wire:model="quizQuestionPool.{{ $qIdx }}.option_b"
                                                        rows="2"
                                                        placeholder="Texto completo da Opção B"
                                                        class="block w-full rounded-xl border border-gray-300 bg-white p-2.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white leading-relaxed"
                                                    ></textarea>
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Alternativa C</label>
                                                    <textarea
                                                        wire:model="quizQuestionPool.{{ $qIdx }}.option_c"
                                                        rows="2"
                                                        placeholder="Texto completo da Opção C"
                                                        class="block w-full rounded-xl border border-gray-300 bg-white p-2.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white leading-relaxed"
                                                    ></textarea>
                                                </div>
                                            </div>

                                            <!-- EXPLICAÇÃO PEDAGÓGICA EM CASO DE ERRO EM TEXTAREA -->
                                            <div>
                                                <label class="block font-semibold text-amber-800 dark:text-amber-300 mb-1">💡 Explicação Pedagógica em Caso de Erro (Repetitio desta questão)</label>
                                                <textarea
                                                    wire:model="quizQuestionPool.{{ $qIdx }}.explanation_on_error"
                                                    rows="2"
                                                    placeholder="Explique por que a resposta escolhida está incorreta e qual o raciocínio certo..."
                                                    class="block w-full rounded-xl border border-amber-300/80 bg-amber-50/30 p-2.5 text-xs text-gray-900 dark:border-amber-800/60 dark:bg-gray-800 dark:text-white leading-relaxed"
                                                ></textarea>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- JUSTIFICATIVA PEDAGÓGICA (REPETITIO FEEDBACK) COM CONTRASTE IMPECÁVEL -->
                    <div class="rounded-2xl border border-amber-300/80 bg-amber-50/70 p-4 dark:border-amber-700/60 dark:bg-amber-950/30 space-y-2">
                        <label class="block text-xs font-bold text-amber-950 dark:text-amber-200 flex items-center gap-1.5">
                            <x-heroicon-m-light-bulb class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            <span>Justificativa Pedagógica Geral em Caso de Erro (Repetitio Feedback Padrão)</span>
                        </label>
                        <p class="text-[11px] text-amber-800/80 dark:text-amber-300/80 leading-relaxed">
                            Caso o aluno erre, o sistema exibirá este texto explicando o porquê do erro e o direcionará a tentar novamente.
                        </p>
                        <textarea
                            wire:model="stepExplanation"
                            rows="3"
                            placeholder="Explique o motivo do erro de forma encorajadora..."
                            class="block w-full rounded-xl border border-amber-300 bg-white p-3 text-xs text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-amber-700/80 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 transition leading-relaxed"
                        ></textarea>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="$set('showStepModal', false); $set('isStepFullscreen', false)"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition cursor-pointer"
                    >
                        <x-heroicon-m-arrow-left class="h-4 w-4" />
                        <span>Cancelar e Voltar ao Roteiro</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="$toggle('isStepFullscreen')"
                            class="rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300 transition cursor-pointer"
                        >
                            {{ $isStepFullscreen ? '🗗 Sair da Tela Cheia' : '⛶ Expandir Tela Cheia' }}
                        </button>
                        <button
                            type="button"
                            wire:click="saveStep"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-primary-500 shadow-md transition cursor-pointer"
                        >
                            <x-heroicon-m-check class="h-4 w-4" />
                            <span>Salvar Passo Interativo</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: GERADOR COM IA -->
        <div x-cloak x-show="$wire.showAiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-purple-100 text-purple-600 dark:bg-purple-950 dark:text-purple-300">
                            <x-heroicon-m-sparkles class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-gray-900 dark:text-white">Gerador de Aulas Jesuíticas de 90min com IA</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Gera os 5 blocos pedagógicos estruturados para durar 1h30.</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showAiModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">Tema da Aula</label>
                        <input
                            type="text"
                            wire:model="aiTopic"
                            placeholder="Ex: Excel - Fórmulas PROCV e PROCH no Controle de Estoque"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-medium text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-purple-500 transition"
                        />
                        @error('aiTopic') <p class="text-danger-600 mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-2xl bg-purple-50/80 p-4 border border-purple-200/80 dark:bg-purple-950/40 dark:border-purple-900/60 text-purple-950 dark:text-purple-200 space-y-1.5">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-purple-800 dark:text-purple-300">O que a IA vai criar nesta aula:</span>
                        <ul class="list-disc pl-4 space-y-1 text-[11px]">
                            <li><strong>Praelectio</strong>: Contextualização teórica objetiva para o mercado.</li>
                            <li><strong>Prática Guiada</strong>: Roteiro passo a passo simulando o software.</li>
                            <li><strong>Repetitio</strong>: Questão de causa e efeito com justificativa em caso de erro.</li>
                            <li><strong>Concertatio</strong>: Estudo de caso autônomo sem auxílio visual.</li>
                            <li><strong>Examen</strong>: Prova de maestria com nota de corte de 80%.</li>
                        </ul>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="$set('showAiModal', false)"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="generateWithAi"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-purple-600 px-5 py-2 text-xs font-bold text-white hover:bg-purple-500 shadow-md transition"
                    >
                        <x-heroicon-m-sparkles class="h-4 w-4" />
                        <span wire:loading.remove>Gerar Aula Completa (90min)</span>
                        <span wire:loading>Gerando aula...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: GUIA & TUTORIAL DE CRIAÇÃO DE CONTEÚDO NO LMS AUROSOFT -->
        <div x-cloak x-show="$wire.showTutorialModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-3xl rounded-3xl bg-white p-6 sm:p-8 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-500/10 text-primary-600 dark:bg-primary-500/20 dark:text-primary-400">
                            <x-heroicon-m-book-open class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-gray-950 dark:text-white">
                                Guia Completo: Como Criar Conteúdo no LMS Aurosoft
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Aprenda a estruturar aulas de 90 minutos com a Metodologia Jesuítica (*Ratio Studiorum*).
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showTutorialModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-6 text-xs text-gray-600 dark:text-gray-300">
                    <!-- 1. HIERARQUIA -->
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/40 space-y-2">
                        <h4 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                            <span>🏛️ 1. Hierarquia de Conteúdo</span>
                        </h4>
                        <p class="leading-relaxed">
                            No Aurosoft LMS, cada curso é dividido em <strong>Módulos</strong>, e cada módulo contém <strong>Aulas de 90 minutos</strong>. Cada aula é composta pelos 5 blocos do Método Jesuítico.
                        </p>
                    </div>

                    <!-- 2. OS 5 BLOCOS JESUÍTICOS -->
                    <div class="space-y-3">
                        <h4 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                            <span>⚡ 2. As 5 Fases do Método Jesuítico (*Ratio Studiorum*)</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2.5">
                            <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-3 dark:border-blue-900/50 dark:bg-blue-950/20 space-y-1">
                                <span class="font-bold text-blue-900 dark:text-blue-300 block">1. Praelectio</span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-400">Contexto e teoria direta: o que a ferramenta faz e por que ela existe no mercado.</p>
                            </div>
                            <div class="rounded-2xl border border-purple-200 bg-purple-50/60 p-3 dark:border-purple-900/50 dark:bg-purple-950/20 space-y-1">
                                <span class="font-bold text-purple-900 dark:text-purple-300 block">2. Experientia</span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-400">Prática guiada no simulador interativo onde o aluno clica na ferramenta certa.</p>
                            </div>
                            <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-3 dark:border-amber-900/50 dark:bg-amber-950/20 space-y-1">
                                <span class="font-bold text-amber-900 dark:text-amber-300 block">3. Repetitio</span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-400">Fixação ativa. Se o aluno errar, o sistema explica o porquê e força tentar de novo.</p>
                            </div>
                            <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-3 dark:border-rose-900/50 dark:bg-rose-950/20 space-y-1">
                                <span class="font-bold text-rose-900 dark:text-rose-300 block">4. Concertatio</span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-400">Desafio autônomo sem muletas visuais, simulando um problema real de empresa.</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-900/50 dark:bg-emerald-950/20 space-y-1">
                                <span class="font-bold text-emerald-900 dark:text-emerald-300 block">5. Examen</span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-400">Avaliação final de maestria exigindo 80% mínimo para aprovação da aula.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 3. RITMO E TRAVA DIÁRIA (PACING) -->
                    <div class="rounded-2xl border border-amber-300/80 bg-amber-50/60 p-4 dark:border-amber-900/50 dark:bg-amber-950/20 space-y-1.5">
                        <h4 class="font-bold text-amber-950 dark:text-amber-300 flex items-center gap-2">
                            <span>⏱️ 3. Por que cada aula dura 90 minutos e possui Trava Diária?</span>
                        </h4>
                        <p class="leading-relaxed text-amber-900 dark:text-amber-200">
                            Para garantir que alunos rápidos não avancem desenfreadamente sem reter o conteúdo, o Aurosoft LMS limita a conclusão a <strong>no máximo 1 aula completa por dia de aula</strong>. O tempo de intervalo entre dias é pedagogicamente necessário para fixação na memória de longo prazo.
                        </p>
                    </div>

                    <!-- 4. COMO INSERIR MÍDIAS -->
                    <div class="space-y-2">
                        <h4 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                            <span>📁 4. Como Adicionar Mídias (Imagens, Áudio e Vídeos)</span>
                        </h4>
                        <p class="leading-relaxed">
                            Ao criar ou editar qualquer passo, você pode anexar:
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-[11px]">
                            <li><strong>Upload do Computador:</strong> Envie capturas de tela (PNG/JPG), áudios gravados de locução (MP3) ou vídeos (MP4) direto do seu PC.</li>
                            <li><strong>Links Externos:</strong> Cole links de vídeos do YouTube ou Vimeo (o player transforma automaticamente em vídeo interativo responsivo).</li>
                        </ul>
                    </div>

                    <!-- 5. FLUXO RECOMENDADO -->
                    <div class="rounded-2xl border border-purple-200 bg-purple-50/70 p-4 dark:border-purple-900/50 dark:bg-purple-950/20 space-y-1.5">
                        <h4 class="font-bold text-purple-950 dark:text-purple-300 flex items-center gap-2">
                            <span>🚀 5. Fluxo de Trabalho Recomendado</span>
                        </h4>
                        <ol class="list-decimal pl-5 space-y-1 text-[11px] text-purple-900 dark:text-purple-200">
                            <li>Crie ou selecione o Módulo do curso.</li>
                            <li>Clique em <strong>"✨ Criar Aula com IA"</strong> e informe o tema desejado (a IA monta os 5 blocos em segundos).</li>
                            <li>Personalize os passos clicando no botão <strong>✏️ (Editar)</strong> em cada card para adicionar seus próprios exemplos e uploads de mídia.</li>
                            <li>Clique no botão <strong>"▶️ Testar Aula no Player"</strong> para simular a aula como educador antes de liberar aos alunos.</li>
                        </ol>
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="$set('showTutorialModal', false)"
                        class="rounded-xl bg-primary-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-primary-500 transition"
                    >
                        Entendi! Fechar Guia
                    </button>
                </div>
            </div>
        </div>

        @if($showRatingsModal)
            @php
                $feedbacks = $this->courseFeedbacks;
                $stats = $this->ratingsStats;
            @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-xs">
                <div class="w-full max-w-4xl max-h-[85vh] flex flex-col rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:bg-gray-900 dark:border-gray-800 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h3 class="text-base font-black text-gray-950 dark:text-white">⭐ Avaliações e Feedbacks dos Alunos</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Curso: <strong>{{ $this->selectedCourse?->name ?? '—' }}</strong> · Identifique quais aulas têm maior satisfação ou precisam de melhoria.
                            </p>
                        </div>
                        <button type="button" wire:click="$set('showRatingsModal', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-3.5 dark:border-amber-900/50 dark:bg-amber-950/20">
                            <div class="text-[10px] font-bold uppercase text-amber-700 dark:text-amber-300">Média Geral do Curso</div>
                            <div class="mt-1 text-2xl font-black text-amber-950 dark:text-amber-100">⭐ {{ number_format($stats['avg'], 1, ',', '.') }} <span class="text-xs font-normal text-amber-700">/ 5,0</span></div>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3.5 dark:border-gray-800 dark:bg-gray-800/40">
                            <div class="text-[10px] font-bold uppercase text-gray-500">Total de Avaliações</div>
                            <div class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
                        </div>
                        <div class="rounded-2xl border border-rose-200 bg-rose-50/70 p-3.5 dark:border-rose-900/50 dark:bg-rose-950/20">
                            <div class="text-[10px] font-bold uppercase text-rose-700 dark:text-rose-300">Aulas com Alerta (≤ 3 ⭐)</div>
                            <div class="mt-1 text-2xl font-black text-rose-950 dark:text-rose-100">{{ $stats['alerts'] }}</div>
                        </div>
                    </div>

                    <!-- Filtros Rápidos e Busca -->
                    <div class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
                        <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl text-xs font-bold">
                            <button
                                type="button"
                                wire:click="setRatingsFilter('all')"
                                class="px-3 py-1.5 rounded-lg transition {{ $ratingsFilter === 'all' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xs' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}"
                            >
                                Todas ({{ $stats['total'] }})
                            </button>
                            <button
                                type="button"
                                wire:click="setRatingsFilter('alerts')"
                                class="px-3 py-1.5 rounded-lg transition flex items-center gap-1 {{ $ratingsFilter === 'alerts' ? 'bg-rose-500 text-white shadow-xs' : 'text-gray-500 hover:text-rose-600' }}"
                            >
                                ⚠️ Alertas ≤ 3⭐ ({{ $stats['alerts'] }})
                            </button>
                            <button
                                type="button"
                                wire:click="setRatingsFilter('positive')"
                                class="px-3 py-1.5 rounded-lg transition flex items-center gap-1 {{ $ratingsFilter === 'positive' ? 'bg-emerald-600 text-white shadow-xs' : 'text-gray-500 hover:text-emerald-600' }}"
                            >
                                🌟 Positivas ≥ 4⭐ ({{ $stats['positive'] }})
                            </button>
                        </div>

                        <div class="relative flex-1 sm:max-w-xs">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="ratingsSearch"
                                placeholder="Buscar por aluno, aula ou comentário..."
                                class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500"
                            />
                            @if(filled($ratingsSearch))
                                <button type="button" wire:click="$set('ratingsSearch', '')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs">✕</button>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto pr-1">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-[10px] font-bold uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5">Aula</th>
                                    <th class="px-3 py-2.5">Aluno</th>
                                    <th class="px-3 py-2.5 text-center">Nota</th>
                                    <th class="px-3 py-2.5">Comentário / Sugestão do Aluno</th>
                                    <th class="px-3 py-2.5 text-right">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($feedbacks as $fb)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-white/5">
                                        <td class="px-3 py-2.5">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ $fb->lesson?->title ?? 'Aula' }}</div>
                                            <div class="text-[10px] text-gray-400">{{ $fb->lesson?->module?->title }}</div>
                                        </td>
                                        <td class="px-3 py-2.5 font-medium text-gray-700 dark:text-gray-300">
                                            {{ $fb->contract?->student?->name ?? 'Aluno' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 font-bold {{ $fb->student_rating <= 3 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                                                ⭐ {{ $fb->student_rating }}/5
                                            </span>
                                        </td>
                                        <td class="px-3 py-2.5 text-gray-600 dark:text-gray-300">
                                            {{ $fb->student_rating_comment ?: '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-gray-400 whitespace-nowrap">
                                            {{ $fb->student_rated_at ? \Illuminate\Support\Carbon::parse($fb->student_rated_at)->format('d/m/Y H:i') : $fb->updated_at?->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-400">
                                            Nenhuma avaliação encontrada com os filtros selecionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-gray-100 pt-3 dark:border-gray-800">
                        <div class="w-full sm:w-auto text-xs">
                            {{ $feedbacks->links() }}
                        </div>
                        <x-filament::button color="gray" wire:click="$set('showRatingsModal', false)">
                            Fechar
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL DE IMPORTAÇÃO DE PACOTE DE CURSO (.ZIP COM MÍDIAS OU .JSON) -->
        @if($showImportPackageModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 text-base">
                                📥
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Importar Pacote Completo LMS</h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Importação em 1 lance de cursos, aulas e todas as mídias associadas</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('showImportPackageModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg cursor-pointer">✕</button>
                    </div>

                    <div class="space-y-3">
                        <div class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 p-6 text-center hover:border-purple-500 transition bg-gray-50/50 dark:bg-gray-800/40">
                            <input
                                type="file"
                                wire:model="packageFile"
                                accept=".zip,.json,application/zip,application/x-zip-compressed,application/json"
                                id="packageFileInput"
                                class="hidden"
                            />
                            <label for="packageFileInput" class="cursor-pointer space-y-2 block">
                                <span class="text-3xl block">📦</span>
                                <span class="text-xs font-bold text-purple-600 dark:text-purple-400 block hover:underline">
                                    Clique para selecionar o pacote (.zip com mídias ou .json)
                                </span>
                                <span class="text-[10px] text-gray-400 block">
                                    Suporta pacote completo .zip com imagens e áudios ou backup de dados .json (até 500MB)
                                </span>
                            </label>

                            @if($packageFile)
                                <div class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                    <span>✓</span>
                                    <span>{{ $packageFile->getClientOriginalName() }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="rounded-xl bg-purple-500/10 border border-purple-500/20 p-3 text-[11px] text-purple-800 dark:text-purple-200 leading-relaxed">
                            📦 <strong>Tudo em um único lance:</strong> Arquivos <strong>.zip</strong> restauram automaticamente todas as fotos, prints de simulador e áudios na pasta de mídias do LMS, sem necessidade de salvamento avulso!
                        </div>

                        <div class="rounded-xl bg-amber-500/10 border border-amber-500/20 p-3 text-[11px] text-amber-700 dark:text-amber-300 leading-relaxed">
                            💡 <strong>Sincronização Segura:</strong> Cursos com o mesmo nome serão atualizados com os novos módulos e lições. O histórico e progresso dos alunos já matriculados são preservados.
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <button
                            type="button"
                            wire:click="$set('showImportPackageModal', false)"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            wire:click="importPackage"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold bg-purple-600 text-white hover:bg-purple-500 shadow-md transition cursor-pointer disabled:opacity-50"
                        >
                            <span wire:loading wire:target="importPackage" class="animate-spin">⏳</span>
                            <span wire:loading.remove wire:target="importPackage">📥</span>
                            <span>Confirmar e Importar</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>

