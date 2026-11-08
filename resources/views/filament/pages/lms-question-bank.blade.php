<x-filament-panels::page>
    <div class="space-y-6">

        {{-- CABEÇALHO E SELETOR DE CURSO --}}
        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 text-2xl shadow-inner">
                        🎲
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-gray-950 dark:text-white">
                            Banco de Questões & Sorteio Anti-Cola
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Cadastre pools de questões por aula, configure quantas perguntas serão sorteadas por tentativa e embaralhe as alternativas automaticamente.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <div class="min-w-[250px]">
                        <select
                            wire:model.live="selectedCourseId"
                            class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-3.5 pr-8 text-xs font-bold text-gray-900 shadow-xs focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Selecione um Curso...</option>
                            @foreach($this->courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->workload }}h)</option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedLessonId)
                        <button
                            type="button"
                            wire:click="generateStarterQuestionPack"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:from-indigo-500 hover:to-purple-500 transition cursor-pointer"
                        >
                            <x-heroicon-m-sparkles class="h-4 w-4" />
                            <span>✨ Gerar +5 Questões para a Aula</span>
                        </button>

                        <button
                            type="button"
                            wire:click="openCreateQuestionModal"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-primary-500 transition cursor-pointer"
                        >
                            <x-heroicon-m-plus class="h-4 w-4" />
                            <span>+ Nova Questão</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- CARDS DE ESTATÍSTICAS DO BANCO --}}
            @php
                $stats = $this->courseStats;
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5 pt-5 border-t border-gray-100 dark:border-gray-800">
                <div class="rounded-2xl border border-gray-200/80 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total de Questões no Curso</span>
                        <div class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $stats['total_questions'] }}</div>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 text-lg">📚</span>
                </div>

                <div class="rounded-2xl border border-gray-200/80 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aulas com Pool Anti-Cola</span>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $stats['active_pools'] }} <span class="text-xs font-semibold text-gray-400">/ {{ $stats['total_lessons'] }} aulas</span></div>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 text-lg">🛡️</span>
                </div>

                <div class="rounded-2xl border border-gray-200/80 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Questões na Aula Selecionada</span>
                        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5">
                            {{ count($questions) }}
                            <span class="text-xs font-semibold text-gray-400">(Sorteando {{ min(count($questions) ?: 1, $questionsToDraw) }})</span>
                        </div>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 text-lg">🎯</span>
                </div>
            </div>
        </div>

        @if(! $selectedCourseId)
            <div class="rounded-3xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-white dark:bg-gray-900 space-y-2">
                <p class="text-sm font-bold text-gray-800 dark:text-gray-200">Selecione um curso acima para gerenciar seu Banco de Questões.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- COLUNA ESQUERDA: NAVEGAÇÃO POR MÓDULOS E AULAS --}}
                <div class="lg:col-span-4 space-y-4">
                    <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                            <h2 class="text-xs font-black uppercase tracking-wider text-gray-900 dark:text-white flex items-center gap-1.5">
                                <x-heroicon-m-academic-cap class="h-4 w-4 text-primary-600" />
                                <span>Aulas do Curso</span>
                            </h2>
                            <a
                                href="{{ \App\Filament\Studio\Pages\LmsStudio::getUrl(['course_id' => $selectedCourseId]) }}"
                                class="text-[11px] font-bold text-primary-600 hover:underline dark:text-primary-400"
                            >
                                Ir para Estúdio →
                            </a>
                        </div>

                        <div class="space-y-3 max-h-[65vh] overflow-y-auto pr-1">
                            @forelse($this->selectedCourse?->lmsModules ?? [] as $module)
                                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800/70 px-3.5 py-2.5 text-xs font-bold text-gray-800 dark:text-gray-200 border-b border-gray-200/70 dark:border-gray-800">
                                        📁 {{ $module->title }}
                                    </div>
                                    <div class="divide-y divide-gray-100 dark:divide-gray-800/70 bg-white dark:bg-gray-900">
                                        @forelse($module->lessons as $lesson)
                                            @php
                                                $quizStep = $lesson->steps->where('step_type', 'quiz')->last();
                                                $pool = $quizStep ? ($quizStep->content['question_pool'] ?? []) : [];
                                                $qCount = is_array($pool) && count($pool) > 0
                                                    ? count($pool)
                                                    : ($quizStep && !empty($quizStep->content['question']) ? 1 : 0);
                                                $isSelected = $selectedLessonId === $lesson->id;
                                            @endphp
                                            <button
                                                type="button"
                                                wire:click="selectLesson({{ $lesson->id }})"
                                                class="w-full text-left px-3.5 py-2.5 flex items-center justify-between gap-2 transition cursor-pointer {{ $isSelected ? 'bg-primary-50/90 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 font-bold' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300' }}"
                                            >
                                                <span class="text-xs truncate">{{ $lesson->title }}</span>
                                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $qCount > 1 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($qCount === 1 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400') }}">
                                                    {{ $qCount }} {{ $qCount === 1 ? 'questão' : 'questões' }}
                                                </span>
                                            </button>
                                        @empty
                                            <div class="p-3 text-[11px] text-gray-400 italic">Nenhuma aula nesta seção.</div>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-xs text-gray-400">
                                    Nenhuma seção encontrada neste curso.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- COLUNA DIREITA: CONFIGURAÇÃO ANTI-COLA & LISTA DE QUESTÕES --}}
                <div class="lg:col-span-8 space-y-5">
                    @if(! $this->selectedLesson)
                        <div class="rounded-3xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-white dark:bg-gray-900">
                            <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Selecione uma aula na coluna ao lado para editar seu Banco de Questões.</p>
                        </div>
                    @else
                        {{-- PAINEL DE REGRAS DE SORTEIO E ANTI-COLA --}}
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                                <div>
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                                        {{ $this->selectedLesson->module?->title }}
                                    </span>
                                    <h2 class="text-base font-black text-gray-950 dark:text-white">
                                        {{ $this->selectedLesson->title }}
                                    </h2>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a
                                        href="{{ route('lms.player', ['lesson' => $this->selectedLesson->id, 'preview' => 1]) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition"
                                    >
                                        <x-heroicon-m-play class="h-4 w-4 text-emerald-500" />
                                        <span>Testar Sorteio no Player</span>
                                    </a>

                                    <button
                                        type="button"
                                        wire:click="persistQuestionBank"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500 transition cursor-pointer"
                                    >
                                        <x-heroicon-m-check class="h-4 w-4" />
                                        <span>Salvar Configurações</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center text-xs">
                                <div class="md:col-span-5">
                                    <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Título do Passo de Avaliação</label>
                                    <input
                                        type="text"
                                        wire:model="quizTitle"
                                        class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Questões por Tentativa</label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="30"
                                        wire:model="questionsToDraw"
                                        class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-bold text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div class="md:col-span-4 space-y-2 pt-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="randomizeQuestions" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                        <span class="font-bold text-gray-800 dark:text-gray-200">Sortear questões aleatórias</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="shuffleOptions" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                        <span class="font-bold text-gray-800 dark:text-gray-200">Embaralhar alternativas (A/B/C)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- LISTA DE QUESTÕES DO BANCO --}}
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black text-gray-900 dark:text-white">
                                        Questões Cadastradas no Pool desta Aula ({{ count($questions) }})
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Se o aluno reprovar na tentativa, o sistema sorteará automaticamente um novo conjunto a partir deste banco.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="openCreateQuestionModal"
                                    class="rounded-xl bg-primary-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-primary-500 transition cursor-pointer"
                                >
                                    + Adicionar Questão
                                </button>
                            </div>

                            @if(count($questions) === 0)
                                <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center space-y-3">
                                    <div class="text-3xl">🎲</div>
                                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200">Nenhuma questão cadastrada para esta aula ainda</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                                        Clique em <strong>"✨ Gerar +5 Questões para a Aula"</strong> para criar um banco inicial automaticamente ou adicione manualmente.
                                    </p>
                                    <div class="flex items-center justify-center gap-2 pt-2">
                                        <button
                                            type="button"
                                            wire:click="generateStarterQuestionPack"
                                            class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-500 transition cursor-pointer"
                                        >
                                            ✨ Gerar 5 Questões Agora
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="openCreateQuestionModal"
                                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition cursor-pointer"
                                        >
                                            + Criar Manualmente
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($questions as $idx => $q)
                                        <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40 space-y-3 transition hover:border-primary-500/40">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-start gap-2.5">
                                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-[11px] font-black text-white">
                                                        #{{ $idx + 1 }}
                                                    </span>
                                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white leading-relaxed">
                                                        {{ $q['question'] }}
                                                    </h4>
                                                </div>

                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <button
                                                        type="button"
                                                        wire:click="openEditQuestionModal({{ $idx }})"
                                                        class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-[11px] font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-pointer"
                                                    >
                                                        ✏️ Editar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        wire:click="duplicateQuestion({{ $idx }})"
                                                        class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-[11px] font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-pointer"
                                                    >
                                                        📋 Duplicar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        wire:click="removeQuestion({{ $idx }})"
                                                        class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-600 hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-400 cursor-pointer"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs pl-8">
                                                @foreach(['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c']] as $letter => $optText)
                                                    @php
                                                        $isCorrect = strtoupper($q['correct_option'] ?? 'A') === $letter;
                                                    @endphp
                                                    <div class="rounded-xl border px-3 py-2 flex items-center gap-2 {{ $isCorrect ? 'border-emerald-500/60 bg-emerald-50/80 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-950/30 dark:text-emerald-200 font-bold' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300' }}">
                                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md text-[10px] font-black {{ $isCorrect ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                                            {{ $letter }}
                                                        </span>
                                                        <span class="truncate">{{ $optText }}</span>
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if(! empty($q['explanation_on_error']))
                                                <div class="pl-8 text-[11px] text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
                                                    <span>💡</span>
                                                    <span><strong>Feedback Repetitio:</strong> {{ $q['explanation_on_error'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- MODAL DE CRIAÇÃO / EDIÇÃO DE QUESTÃO --}}
        <div x-cloak x-show="$wire.showQuestionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-xs">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-black text-gray-900 dark:text-white">
                        {{ $editingIndex !== null ? '✏️ Editar Questão do Banco' : '➕ Nova Questão para o Banco' }}
                    </h3>
                    <button type="button" wire:click="$set('showQuestionModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Enunciado da Pergunta</label>
                        <textarea
                            wire:model="formQuestion"
                            rows="2"
                            placeholder="Ex: Qual função do Excel retorna a média aritmética de um intervalo?"
                            class="block w-full rounded-xl border border-gray-300 bg-white p-3 text-xs font-medium text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        ></textarea>
                        @error('formQuestion') <p class="text-danger-600 mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Alternativa A</label>
                        <input
                            type="text"
                            wire:model="formOptionA"
                            placeholder="Texto da Alternativa A"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        @error('formOptionA') <p class="text-danger-600 mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Alternativa B</label>
                        <input
                            type="text"
                            wire:model="formOptionB"
                            placeholder="Texto da Alternativa B"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        @error('formOptionB') <p class="text-danger-600 mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Alternativa C</label>
                        <input
                            type="text"
                            wire:model="formOptionC"
                            placeholder="Texto da Alternativa C"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        @error('formOptionC') <p class="text-danger-600 mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Gabarito (Alternativa Correta)</label>
                            <select
                                wire:model="formCorrectOption"
                                class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-bold text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="A">Alternativa A</option>
                                <option value="B">Alternativa B</option>
                                <option value="C">Alternativa C</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Explicação Pedagógica (Se errar)</label>
                            <input
                                type="text"
                                wire:model="formExplanation"
                                placeholder="Dica exibida caso o aluno erre..."
                                class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="$set('showQuestionModal', false)"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="saveQuestionFromModal"
                        class="rounded-xl bg-primary-600 px-5 py-2 text-xs font-bold text-white hover:bg-primary-500 shadow-sm cursor-pointer"
                    >
                        Salvar Questão no Banco
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
