<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans select-none antialiased">
    {{-- BARRA SUPERIOR / HEADER --}}
    <header class="border-b border-slate-800/80 bg-slate-900/95 px-4 py-3 sm:px-6 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
            {{-- ESQUERDA: VOLTAR E TÍTULO --}}
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('student.portal', ['tab' => 'courses']) }}"
                   class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white transition"
                   title="Voltar ao Portal do Aluno">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="truncate">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-emerald-400">⌨️ Digitação Vocacional</span>
                        @if($lesson)
                            <span class="text-slate-600">•</span>
                            <span class="text-xs text-amber-400 font-medium truncate">Etapa Pré-Aula (15 min Obrigatório)</span>
                        @endif
                    </div>
                    <h1 class="text-sm sm:text-base font-bold text-white truncate">
                        {{ $exercise?->title ?? 'Laboratório de Digitação' }}
                    </h1>
                </div>
            </div>

            {{-- CENTRO: ABAS DE NAVEGAÇÃO --}}
            <div class="flex items-center gap-1 bg-slate-800/70 p-1 rounded-2xl text-xs font-semibold">
                <button type="button"
                        wire:click="$set('activeTab', 'practice')"
                        class="px-3 py-1.5 rounded-xl transition {{ $activeTab === 'practice' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}">
                    ⌨️ Treino Atual
                </button>
                <button type="button"
                        wire:click="$set('activeTab', 'school_ranking')"
                        class="px-3 py-1.5 rounded-xl transition {{ $activeTab === 'school_ranking' ? 'bg-amber-500 text-slate-950 font-bold shadow-xs' : 'text-slate-400 hover:text-white' }}">
                    🏆 Ranking Geral
                </button>
                <button type="button"
                        wire:click="$set('activeTab', 'exercise_ranking')"
                        class="px-3 py-1.5 rounded-xl transition {{ $activeTab === 'exercise_ranking' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}">
                    🥇 Deste Exercício
                </button>
                <button type="button"
                        wire:click="$set('activeTab', 'my_history')"
                        class="px-3 py-1.5 rounded-xl transition {{ $activeTab === 'my_history' ? 'bg-cyan-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}">
                    📜 Meu Histórico
                </button>
            </div>

            {{-- DIREITA: BOTÃO COMEÇAR AULA OU LIBERAÇÃO --}}
            <div class="flex items-center gap-2">
                @if($lesson)
                    @if($isWarmupCompleted)
                        <button type="button"
                                wire:click="startLesson"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-slate-950 px-4 py-2 text-xs font-black shadow-lg shadow-emerald-500/20 transition cursor-pointer animate-pulse">
                            <span>🚀</span>
                            <span>Começar Aula</span>
                        </button>
                    @else
                        <button type="button"
                                wire:click="$set('showEducatorAuthModal', true)"
                                class="inline-flex items-center gap-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white px-3 py-2 text-xs font-medium border border-slate-700 transition cursor-pointer"
                                title="Educador pode autorizar a liberação imediata">
                            <span>🔑</span>
                            <span class="hidden sm:inline">Liberar com Educador</span>
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </header>

    {{-- CONTEÚDO PRINCIPAL --}}
    <main class="max-w-7xl mx-auto w-full p-4 sm:p-6 flex-1 flex flex-col space-y-6">

        {{-- BANNER DE AQUECIMENTO OBRIGATÓRIO (15 MINUTOS PRÉ-AULA) --}}
        @if($isMandatory && $lesson)
            <div class="rounded-3xl border-2 {{ $isWarmupCompleted ? 'border-emerald-500/40 bg-emerald-950/20' : 'border-amber-500/40 bg-gradient-to-r from-amber-950/30 via-slate-900 to-slate-900' }} p-4 sm:p-6 shadow-xl relative overflow-hidden backdrop-blur">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 relative z-10">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $isWarmupCompleted ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }} text-2xl shrink-0">
                            {{ $isWarmupCompleted ? '✅' : '⏱️' }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black uppercase tracking-wider {{ $isWarmupCompleted ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ $isWarmupCompleted ? 'Aquecimento Concluído' : 'Etapa 1: Treino Obrigatório nos Primeiros 15 Minutos de Aula' }}
                                </span>
                            </div>
                            <h2 class="text-sm sm:text-base font-bold text-white mt-0.5">
                                @if($isWarmupCompleted)
                                    Parabéns! Seus 15 minutos de digitação foram concluídos com sucesso.
                                @else
                                    Pratique digitação para liberar sua aula: <span class="text-amber-300 font-extrabold">{{ $lesson->title }}</span>
                                @endif
                            </h2>
                            <p class="text-xs text-slate-300 mt-0.5 max-w-2xl">
                                @if($isWarmupCompleted)
                                    Você já está com os dedos aquecidos e preparado para o conteúdo da aula. Clique no botão ao lado para começar!
                                @else
                                    Conforme a metodologia da escola, os primeiros 15 minutos são dedicados a exercitar a memória muscular, acentuação e agilidade no teclado físico.
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- PROGRESSO DOS 15 MINUTOS & AÇÃO --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto shrink-0">
                        <div class="rounded-2xl bg-slate-950/80 border border-slate-800 p-3 min-w-[200px] text-center">
                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 mb-1">
                                <span>Tempo Acumulado</span>
                                <span class="{{ $isWarmupCompleted ? 'text-emerald-400' : 'text-amber-400' }} font-mono">
                                    {{ floor($typingTimeSpentToday / 60) }}m {{ str_pad((string)($typingTimeSpentToday % 60), 2, '0', STR_PAD_LEFT) }}s / 15m
                                </span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                <div class="h-2.5 rounded-full transition-all duration-500 {{ $isWarmupCompleted ? 'bg-emerald-500' : 'bg-gradient-to-r from-amber-500 to-amber-400' }}"
                                     style="width: {{ min(100, round(($typingTimeSpentToday / 900) * 100)) }}%"></div>
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1 block">
                                @if($isWarmupCompleted)
                                    Meta atingida (100%)
                                @else
                                    Faltam {{ ceil($remainingSeconds / 60) }} min de prática
                                @endif
                            </span>
                        </div>

                        @if($isWarmupCompleted)
                            <button type="button"
                                    wire:click="startLesson"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-slate-950 px-6 py-3.5 text-sm font-black shadow-xl shadow-emerald-500/25 transition cursor-pointer">
                                <span>🚀</span>
                                <span>Começar Aula Agora</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ABA 1: TREINO PRÁTICO DE DIGITAÇÃO --}}
        @if($activeTab === 'practice')
            @if($exercise)
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

                    {{-- COLUNA ESQUERDA: LISTA E FILTRO DE EXERCÍCIOS --}}
                    <div class="lg:col-span-1 space-y-3">
                        <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4 shadow-xl space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Exercícios ({{ $exercisesList->count() }})
                                </h3>
                                <span class="text-[10px] text-emerald-400 font-mono font-bold">
                                    {{ count($completedExerciseIds) }} feitos
                                </span>
                            </div>

                            {{-- FILTROS DE CATEGORIA --}}
                            <div class="flex flex-wrap gap-1">
                                <button type="button"
                                        wire:click="setCategoryFilter('all')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold transition {{ $categoryFilter === 'all' ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                                    Todos
                                </button>
                                <button type="button"
                                        wire:click="setCategoryFilter('all_letters')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold transition {{ $categoryFilter === 'all_letters' ? 'bg-indigo-500 text-white font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                                    A-Z (Pangramas)
                                </button>
                                <button type="button"
                                        wire:click="setCategoryFilter('words')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold transition {{ $categoryFilter === 'words' ? 'bg-blue-500 text-white font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                                    Palavras
                                </button>
                                <button type="button"
                                        wire:click="setCategoryFilter('accents')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold transition {{ $categoryFilter === 'accents' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                                    Acentos & Ç
                                </button>
                                <button type="button"
                                        wire:click="setCategoryFilter('symbols')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold transition {{ $categoryFilter === 'symbols' ? 'bg-purple-500 text-white font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                                    Símbolos & Aspas
                                </button>
                                <button type="button"
                                        wire:click="setCategoryFilter('short_text')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold transition {{ $categoryFilter === 'short_text' ? 'bg-cyan-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                                    Textos Curtos
                                </button>
                                <button type="button"
                                        wire:click="setCategoryFilter('medium_text')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold transition {{ $categoryFilter === 'medium_text' ? 'bg-teal-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                                    Textos Médios
                                </button>
                                <button type="button"
                                        wire:click="setCategoryFilter('long_text')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold transition {{ $categoryFilter === 'long_text' ? 'bg-rose-500 text-white font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                                    Textos Longos
                                </button>
                            </div>

                            {{-- STATUS DA TRILHA SEQUENCIAL --}}
                            @if($hasCompletedAllExercises)
                                <div class="mb-3 p-2.5 rounded-xl bg-gradient-to-r from-emerald-500/20 via-teal-500/20 to-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[11px] flex items-center gap-2 shadow-sm">
                                    <span class="text-base shrink-0">🏆</span>
                                    <span class="leading-tight"><strong>Parabéns!</strong> Trilha completa! Você liberou <strong>acesso livre</strong> a todos os exercícios.</span>
                                </div>
                            @else
                                <div class="mb-3 p-2 rounded-xl bg-slate-900/80 border border-slate-800 text-[11px] flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="text-amber-400 shrink-0">⚡</span>
                                        <span class="text-slate-400 truncate">Trilha Sequencial (Liberando por metas)</span>
                                    </div>
                                    <span class="font-bold text-slate-300 text-xs shrink-0 bg-slate-800 px-2 py-0.5 rounded-lg border border-slate-700">
                                        {{ count($completedExerciseIds) }}/{{ $exercisesList->count() }}
                                    </span>
                                </div>
                            @endif

                            {{-- LISTA DE EXERCÍCIOS ROLÁVEL --}}
                            <div class="space-y-1.5 max-h-[60vh] overflow-y-auto pr-1">
                                @forelse($exercisesList as $ex)
                                    @php
                                        $isCompleted = in_array($ex->id, $completedExerciseIds, true);
                                        $isUnlocked = in_array($ex->id, $unlockedExerciseIds, true);
                                        $isSelected = $ex->id === $exercise->id;
                                    @endphp
                                    @if($isUnlocked)
                                        <button
                                            type="button"
                                            wire:click="selectExercise({{ $ex->id }})"
                                            class="w-full text-left p-2.5 rounded-xl text-xs font-medium transition flex items-center justify-between gap-2 {{ $isSelected ? 'bg-emerald-600/20 border border-emerald-500/50 text-emerald-200 shadow-sm' : ($isCompleted ? 'bg-slate-800/40 text-slate-300 hover:bg-slate-800' : 'bg-slate-900/50 text-slate-400 hover:bg-slate-800 border border-amber-500/20') }}"
                                        >
                                            <div class="min-w-0">
                                                <span class="block truncate font-semibold text-[11px] {{ $isSelected ? 'text-emerald-300' : 'text-slate-200' }}">
                                                    {{ $ex->title }}
                                                </span>
                                                <span class="text-[10px] text-slate-500 block truncate">
                                                    {{ $ex->categoryLabel() }} • Min {{ $ex->min_wpm }} PPM
                                                </span>
                                            </div>
                                            <div class="shrink-0 text-xs">
                                                @if($isCompleted)
                                                    <span class="text-emerald-400 font-bold" title="Concluído com Sucesso">✓</span>
                                                @elseif($isSelected)
                                                    <span class="text-emerald-400 font-bold" title="Exercício Selecionado">●</span>
                                                @else
                                                    <span class="text-amber-400 font-bold text-[10px] bg-amber-500/10 px-1.5 py-0.5 rounded border border-amber-500/20" title="Próximo Nível Desbloqueado">Disponível</span>
                                                @endif
                                            </div>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="selectExercise({{ $ex->id }})"
                                            title="Bloqueado: atinja a meta do exercício anterior para liberar"
                                            class="w-full text-left p-2.5 rounded-xl text-xs font-medium flex items-center justify-between gap-2 bg-slate-950/40 border border-slate-900/80 text-slate-500 opacity-60 cursor-not-allowed transition hover:border-slate-800"
                                        >
                                            <div class="min-w-0">
                                                <span class="block truncate font-medium text-[11px] text-slate-500">
                                                    {{ $ex->title }}
                                                </span>
                                                <span class="text-[10px] text-slate-600 block truncate">
                                                    {{ $ex->categoryLabel() }} • Min {{ $ex->min_wpm }} PPM
                                                </span>
                                            </div>
                                            <div class="shrink-0 text-xs text-slate-600 flex items-center gap-1">
                                                <span class="text-xs" title="Bloqueado">🔒</span>
                                            </div>
                                        </button>
                                    @endif
                                @empty
                                    <div class="text-center py-6 text-xs text-slate-500">
                                        Nenhum exercício encontrado nesta categoria.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- COLUNA CENTRAL/DIREITA: WORKSPACE INTERATIVO DE DIGITAÇÃO --}}
                    <div class="lg:col-span-3 space-y-4">

                        {{-- CARD PRINCIPAL DO DIGITADOR (ALPINE INTERATIVO) --}}
                        <div
                            wire:key="typing-box-{{ $exercise->id }}"
                            x-data="{
                                targetText: String(@js($currentExerciseText)).replace(/\r?\n/g, ' ').trim(),
                                userInput: '',
                                startTime: null,
                                timerInterval: null,
                                elapsedSeconds: 0,
                                wpm: 0,
                                cpm: 0,
                                accuracy: 100,
                                mistakesCount: 0,
                                isStarted: false,
                                isComplete: false,

                                init() {
                                    this.resetEngine();
                                    this.$watch('$wire.currentExerciseText', (newText) => {
                                        if (newText) {
                                            this.targetText = String(newText).replace(/\r?\n/g, ' ').trim();
                                            this.resetEngine();
                                        }
                                    });
                                    window.addEventListener('reset-typing', () => this.resetEngine());
                                },

                                resetEngine() {
                                    clearInterval(this.timerInterval);
                                    this.userInput = '';
                                    this.startTime = null;
                                    this.elapsedSeconds = 0;
                                    this.wpm = 0;
                                    this.cpm = 0;
                                    this.accuracy = 100;
                                    this.mistakesCount = 0;
                                    this.isStarted = false;
                                    this.isComplete = false;
                                    this.$nextTick(() => {
                                        this.$refs.hiddenInput?.focus();
                                    });
                                },

                                handleInput(e) {
                                    if (this.isComplete) return;

                                    if (!this.isStarted && this.userInput.length > 0) {
                                        this.isStarted = true;
                                        this.startTime = Date.now();
                                        this.timerInterval = setInterval(() => {
                                            this.elapsedSeconds = Math.floor((Date.now() - this.startTime) / 1000);
                                            this.computeMetrics();
                                        }, 500);
                                    }

                                    // Checa erro no último caractere digitado
                                    const currentIndex = this.userInput.length - 1;
                                    if (currentIndex >= 0 && currentIndex < this.targetText.length) {
                                        if (this.userInput[currentIndex] !== this.targetText[currentIndex]) {
                                            this.mistakesCount++;
                                        }
                                    }

                                    this.computeMetrics();

                                    // Verifica se concluiu o texto
                                    if (this.targetText.length > 0 && this.userInput.length >= this.targetText.length) {
                                        this.finishExercise();
                                    }
                                },

                                computeMetrics() {
                                    const totalTyped = this.userInput.length;
                                    if (totalTyped === 0) return;

                                    let correct = 0;
                                    for (let i = 0; i < totalTyped; i++) {
                                        if (this.userInput[i] === this.targetText[i]) {
                                            correct++;
                                        }
                                    }

                                    this.accuracy = Math.max(0, Math.round((correct / totalTyped) * 100));

                                    const minutes = Math.max(this.elapsedSeconds / 60, 0.05);
                                    // Fórmula padrão WPM: (Caracteres corretos / 5) / minutos
                                    this.wpm = Math.max(0, Math.round((correct / 5) / minutes));
                                    this.cpm = Math.max(0, Math.round(correct / minutes));
                                },

                                finishExercise() {
                                    this.isComplete = true;
                                    clearInterval(this.timerInterval);
                                    this.computeMetrics();

                                    const totalTyped = this.userInput.length;
                                    let correct = 0;
                                    for (let i = 0; i < totalTyped; i++) {
                                        if (this.userInput[i] === this.targetText[i]) correct++;
                                    }
                                    const errors = totalTyped - correct;

                                    $wire.submitTypingResult(
                                        this.wpm,
                                        this.cpm,
                                        this.accuracy,
                                        totalTyped,
                                        correct,
                                        errors,
                                        this.elapsedSeconds
                                    ).then(() => {
                                        setTimeout(() => {
                                            document.getElementById('typing-feedback-card')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        }, 100);
                                    });
                                },

                                formatTime(sec) {
                                    const m = String(Math.floor(sec / 60)).padStart(2, '0');
                                    const s = String(sec % 60).padStart(2, '0');
                                    return `${m}:${s}`;
                                }
                            }"
                            class="rounded-3xl border border-slate-800 bg-slate-900/90 p-5 sm:p-7 shadow-2xl space-y-6"
                            @click="$refs.hiddenInput.focus()"
                        >
                            {{-- HUD DE MÉTRICAS EM TEMPO REAL --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-2xl bg-slate-950/80 border border-slate-800/80 shadow-inner">
                                <div class="text-center">
                                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Velocidade</span>
                                    <div class="flex items-baseline justify-center gap-1 mt-0.5">
                                        <span class="text-2xl sm:text-3xl font-black text-amber-400 font-mono" x-text="wpm">0</span>
                                        <span class="text-[10px] text-slate-500 font-bold">PPM / WPM</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">Mínimo: {{ $exercise->min_wpm }} PPM</span>
                                </div>

                                <div class="text-center">
                                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Precisão</span>
                                    <div class="flex items-baseline justify-center gap-1 mt-0.5">
                                        <span class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono" x-text="accuracy + '%'">100%</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">Meta: {{ number_format($exercise->min_accuracy, 0) }}%</span>
                                </div>

                                <div class="text-center">
                                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Tempo do Exercício</span>
                                    <div class="flex items-baseline justify-center gap-1 mt-0.5">
                                        <span class="text-2xl sm:text-3xl font-black text-indigo-400 font-mono" x-text="formatTime(elapsedSeconds)">00:00</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">Sessão ativa</span>
                                </div>

                                <div class="text-center">
                                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Erros</span>
                                    <div class="flex items-baseline justify-center gap-1 mt-0.5">
                                        <span class="text-2xl sm:text-3xl font-black text-rose-400 font-mono" x-text="mistakesCount">0</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">Atenção aos toques</span>
                                </div>
                            </div>

                            {{-- DICA PEDAGÓGICA E DE POSICIONAMENTO DE DEDOS --}}
                            @if($exercise->instructions)
                                <div class="p-3.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-200 flex items-start gap-3">
                                    <span class="text-lg">💡</span>
                                    <div class="leading-relaxed">
                                        <span class="font-bold text-indigo-100 block mb-0.5">Instrução de Teclado & Ergonomia:</span>
                                        <span>{{ $exercise->instructions }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- CAMPO OCULTO DE CAPTURA DO TECLADO --}}
                            <input
                                type="text"
                                x-ref="hiddenInput"
                                x-model="userInput"
                                @input="handleInput($event)"
                                class="sr-only"
                                autofocus
                            />

                            {{-- ÁREA DE EXIBIÇÃO DE TEXTO INTERATIVO COM ROLAGEM SUAVE --}}
                            <div class="relative rounded-2xl border-2 border-slate-700/80 bg-slate-950 p-6 sm:p-8 cursor-text select-none min-h-[160px] max-h-[360px] overflow-y-auto text-left shadow-inner">
                                <div class="text-base sm:text-xl font-mono leading-relaxed tracking-normal break-words max-w-full text-left select-none"><template x-for="(char, index) in (targetText || '').split('')" :key="index"><span class="inline-block transition-colors" :class="{
                                            'text-emerald-400 font-bold': index < userInput.length && userInput[index] === char,
                                            'text-rose-400 font-bold bg-rose-500/30 underline decoration-rose-500': index < userInput.length && userInput[index] !== char,
                                            'bg-amber-400 text-slate-950 font-black rounded-xs ring-2 ring-amber-400/60 animate-pulse min-w-[0.55em] text-center': index === userInput.length,
                                            'text-slate-400': index > userInput.length
                                        }" x-text="char === ' ' ? '\u00A0' : char"></span></template></div>
                            </div>

                            {{-- RODAPÉ DO DIGITADOR: RESET E STATUS --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400 pt-2 border-t border-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Clique sobre o texto para focar e digite no teclado do computador</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="resetEngine()"
                                        class="rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-3 py-1.5 font-semibold transition cursor-pointer"
                                    >
                                        🔄 Reiniciar Exercício
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- CARD DE FEEDBACK E GAMIFICAÇÃO APÓS CONCLUIR --}}
                        @if($isFinished)
                            <div id="typing-feedback-card" class="rounded-3xl border-2 {{ $passed ? 'border-emerald-500/80 bg-emerald-950/20' : 'border-amber-500/80 bg-amber-950/20' }} p-6 sm:p-8 shadow-2xl backdrop-blur-xl text-center space-y-5 animate-in fade-in zoom-in duration-300">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl {{ $passed ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }} text-4xl shadow-xl">
                                    {{ $passed ? '🏆' : '🔄' }}
                                </div>

                                <div>
                                    {{-- ESTRELAS GANHAS --}}
                                    <div class="flex items-center justify-center gap-1.5 text-2xl text-amber-400 mb-2">
                                        @for($i = 1; $i <= 3; $i++)
                                            <span>{{ $i <= $stars ? '⭐' : '☆' }}</span>
                                        @endfor
                                    </div>

                                    <h2 class="text-xl sm:text-2xl font-black text-white">
                                        {{ $passed ? 'Exercício Concluído com Sucesso!' : 'Bom Treino! Vamos Tentar Novamente?' }}
                                    </h2>
                                    <p class="text-xs text-slate-300 mt-1">
                                        {{ $passed ? 'Sua velocidade e precisão foram salvas no histórico e no ranking da escola.' : 'Você completou o texto, mas não alcançou a precisão mínima. Pratique novamente para consolidar os movimentos corretos.' }}
                                    </p>
                                </div>

                                {{-- PLACAR DE RESULTADOS --}}
                                <div class="grid grid-cols-3 gap-3 max-w-md mx-auto p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Sua Velocidade</span>
                                        <span class="text-xl font-black text-amber-400 font-mono">{{ $finalWpm }} PPM</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Sua Precisão</span>
                                        <span class="text-xl font-black text-emerald-400 font-mono">{{ $finalAccuracy }}%</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Tempo</span>
                                        <span class="text-xl font-black text-indigo-400 font-mono">{{ $finalTimeSpent }}s</span>
                                    </div>
                                </div>

                                {{-- AÇÕES DO FEEDBACK --}}
                                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                                    @if($lesson && $isWarmupCompleted)
                                        <button
                                            type="button"
                                            wire:click="startLesson"
                                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-slate-950 px-6 py-3.5 text-sm font-black shadow-xl shadow-emerald-500/20 transition cursor-pointer animate-bounce"
                                        >
                                            <span>🚀</span>
                                            <span>Começar Aula: {{ $lesson->title }}</span>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="nextExercise"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white px-5 py-3 text-xs font-bold border border-slate-700 transition cursor-pointer"
                                    >
                                        <span>Próximo Exercício</span>
                                        <span>→</span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="restartExercise"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-2xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white px-4 py-3 text-xs font-semibold transition cursor-pointer"
                                    >
                                        <span>🔄 Refazer</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            @endif

        {{-- ABA 2: RANKING GERAL DA ESCOLA --}}
        @elseif($activeTab === 'school_ranking')
            <div class="rounded-3xl border border-slate-800 bg-slate-900/90 p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🏆</span>
                            <span>Ranking Geral de Digitação Vocacional da Escola</span>
                        </h2>
                        <p class="text-xs text-slate-400">Classificação dos alunos com maior velocidade e precisão no laboratório.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="py-3 px-3 text-center">Posição</th>
                                <th class="py-3 px-3">Aluno</th>
                                <th class="py-3 px-3">Matrícula</th>
                                <th class="py-3 px-3 text-center">Exercícios Feitos</th>
                                <th class="py-3 px-3 text-center">Velocidade Máxima</th>
                                <th class="py-3 px-3 text-center">Velocidade Média</th>
                                <th class="py-3 px-3 text-center">Precisão Média</th>
                                <th class="py-3 px-3 text-center">Estrelas ⭐</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            @forelse($schoolRankings as $index => $rank)
                                <tr class="hover:bg-slate-800/30 transition {{ $rank->contract_id === $contractId ? 'bg-emerald-500/10 font-bold text-white' : '' }}">
                                    <td class="py-3 px-3 text-center font-bold">
                                        @if($index === 0) 🥇 1º
                                        @elseif($index === 1) 🥈 2º
                                        @elseif($index === 2) 🥉 3º
                                        @else {{ $index + 1 }}º
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-white font-semibold">
                                        {{ $rank->student_name }}
                                        @if($rank->contract_id === $contractId)
                                            <span class="ml-1 text-[10px] text-emerald-400">(Você)</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 font-mono text-slate-400">{{ $rank->contract_number }}</td>
                                    <td class="py-3 px-3 text-center">{{ $rank->completed_exercises_count }}</td>
                                    <td class="py-3 px-3 text-center font-bold text-amber-400 font-mono">{{ $rank->max_wpm }} PPM</td>
                                    <td class="py-3 px-3 text-center text-slate-300 font-mono">{{ $rank->avg_wpm }} PPM</td>
                                    <td class="py-3 px-3 text-center text-emerald-400 font-mono">{{ $rank->avg_accuracy }}%</td>
                                    <td class="py-3 px-3 text-center text-amber-400 font-bold">⭐ {{ $rank->total_stars }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-slate-500">
                                        Ainda não há registros de digitação computados. Seja o primeiro a treinar!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- ABA 3: RANKING DESTE EXERCÍCIO --}}
        @elseif($activeTab === 'exercise_ranking')
            <div class="rounded-3xl border border-slate-800 bg-slate-900/90 p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🥇</span>
                            <span>Melhores Resultados: {{ $exercise?->title }}</span>
                        </h2>
                        <p class="text-xs text-slate-400">Recordes de velocidade e precisão neste desafio específico.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="py-3 px-3 text-center">Posição</th>
                                <th class="py-3 px-3">Aluno</th>
                                <th class="py-3 px-3 text-center">Velocidade (PPM)</th>
                                <th class="py-3 px-3 text-center">Precisão</th>
                                <th class="py-3 px-3 text-center">Tempo</th>
                                <th class="py-3 px-3 text-center">Estrelas</th>
                                <th class="py-3 px-3 text-center">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            @forelse($exerciseRankings as $idx => $record)
                                <tr class="hover:bg-slate-800/30 transition {{ $record->contract_id === $contractId ? 'bg-indigo-500/10 font-bold text-white' : '' }}">
                                    <td class="py-3 px-3 text-center font-bold">
                                        @if($idx === 0) 🥇 1º
                                        @elseif($idx === 1) 🥈 2º
                                        @elseif($idx === 2) 🥉 3º
                                        @else {{ $idx + 1 }}º
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-white font-semibold">{{ $record->student?->name ?? 'Aluno' }}</td>
                                    <td class="py-3 px-3 text-center font-bold text-amber-400 font-mono">{{ $record->wpm }} PPM</td>
                                    <td class="py-3 px-3 text-center text-emerald-400 font-mono">{{ $record->accuracy_percentage }}%</td>
                                    <td class="py-3 px-3 text-center font-mono">{{ $record->time_spent_seconds }}s</td>
                                    <td class="py-3 px-3 text-center text-amber-400">
                                        @for($s = 1; $s <= $record->stars; $s++) ⭐ @endfor
                                    </td>
                                    <td class="py-3 px-3 text-center text-slate-400">{{ $record->completed_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-slate-500">
                                        Nenhum aluno registrou tempo para este exercício ainda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- ABA 4: MEU HISTÓRICO DE TREINOS --}}
        @elseif($activeTab === 'my_history')
            <div class="rounded-3xl border border-slate-800 bg-slate-900/90 p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>📜</span>
                            <span>Meu Histórico de Evolução em Digitação</span>
                        </h2>
                        <p class="text-xs text-slate-400">Acompanhe seu ganho progressivo de velocidade e redução de erros ao longo das aulas.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="py-3 px-3">Exercício</th>
                                <th class="py-3 px-3 text-center">Velocidade (PPM)</th>
                                <th class="py-3 px-3 text-center">Precisão</th>
                                <th class="py-3 px-3 text-center">Erros</th>
                                <th class="py-3 px-3 text-center">Estrelas</th>
                                <th class="py-3 px-3 text-center">Tempo Gasto</th>
                                <th class="py-3 px-3 text-center">Data do Treino</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            @forelse($myHistory as $item)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-3 text-white font-semibold">{{ $item->exercise?->title ?? 'Exercício' }}</td>
                                    <td class="py-3 px-3 text-center font-bold text-amber-400 font-mono">{{ $item->wpm }} PPM</td>
                                    <td class="py-3 px-3 text-center text-emerald-400 font-mono">{{ $item->accuracy_percentage }}%</td>
                                    <td class="py-3 px-3 text-center text-rose-400 font-mono">{{ $item->error_keystrokes }}</td>
                                    <td class="py-3 px-3 text-center text-amber-400">
                                        @for($s = 1; $s <= $item->stars; $s++) ⭐ @endfor
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono">{{ $item->time_spent_seconds }}s</td>
                                    <td class="py-3 px-3 text-center text-slate-400">{{ $item->completed_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-slate-500">
                                        Você ainda não concluiu nenhum exercício de digitação.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </main>

    {{-- MODAL DE AUTORIZAÇÃO DO EDUCADOR (DISPENSA DOS 15 MINUTOS) --}}
    @if($showEducatorAuthModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="w-full max-w-md rounded-3xl border border-slate-700 bg-slate-900 p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 text-lg">🔑</span>
                        <h3 class="text-sm font-bold text-white">Autorização do Educador</h3>
                    </div>
                    <button type="button" wire:click="$set('showEducatorAuthModal', false)" class="text-slate-500 hover:text-white text-lg">✕</button>
                </div>

                <p class="text-xs text-slate-400">
                    Insira as credenciais de professor ou coordenador pedagógico para liberar a aula imediatamente sem a conclusão dos 15 minutos de digitação.
                </p>

                @if($educatorAuthError)
                    <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-xs text-rose-300">
                        {{ $educatorAuthError }}
                    </div>
                @endif

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">E-mail ou Usuário do Educador</label>
                        <input type="text" wire:model="educatorEmail" class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none" placeholder="professor@escola.com ou usuário">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Senha</label>
                        <input type="password" wire:model="educatorPassword" class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none" placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showEducatorAuthModal', false)" class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white transition">
                        Cancelar
                    </button>
                    <button type="button" wire:click="authorizeByEducator" class="rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 text-xs transition">
                        Autorizar e Desbloquear
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
