<x-filament-panels::page>
    <div
        x-data="{
            announcements: @js($studentAnnouncements),
            open: false,
            current: 0,
            time: '',
            date: '',
            lmsRedirectUrl: null,
            lmsRedirectMessage: 'Conectando você ao seu curso...',
            lmsRedirectButtonText: 'Entrar na aula agora ➜',
            init() {
                this.updateClock();
                setInterval(() => this.updateClock(), 1000);
                if (this.announcements.length > 1) {
                    setInterval(() => {
                        if (!this.open) {
                            this.current = (this.current + 1) % this.announcements.length;
                        }
                    }, 7000);
                }
                window.addEventListener('open-lms-autologin', (event) => {
                    const url = event.detail.url;
                    this.lmsRedirectUrl = url;
                    if (event.detail.message) {
                        this.lmsRedirectMessage = event.detail.message;
                    }
                    if (event.detail.buttonText) {
                        this.lmsRedirectButtonText = event.detail.buttonText;
                    }
                    const target = event.detail.target || 'AurosoftLMS';
                    const width = window.screen.availWidth || window.innerWidth;
                    const height = window.screen.availHeight || window.innerHeight;

                    let popup = null;
                    try {
                        popup = window.open(url, target, `width=${width},height=${height},left=0,top=0,fullscreen=yes,scrollbars=yes`);
                    } catch (e) {
                        popup = null;
                    }

                    if (!popup || popup.closed || typeof popup.closed === 'undefined') {
                        // Navegador bloqueou popup: redireciona diretamente na mesma janela
                        window.location.href = url;
                    } else {
                        popup.moveTo(0, 0);
                        popup.focus();
                    }
                });
            },
            updateClock() {
                const now = new Date();
                this.time = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                const rawDate = now.toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                this.date = rawDate.charAt(0).toUpperCase() + rawDate.slice(1);
            },
            show(index) {
                this.current = index;
                this.open = true;
            },
            previous() {
                this.current = (this.current - 1 + this.announcements.length) % this.announcements.length;
            },
            next() {
                this.current = (this.current + 1) % this.announcements.length;
            }
        }"
        class="relative flex flex-col justify-between min-h-[90vh] py-2 px-3 sm:px-6"
    >
        <!-- TOPO: CABEÇALHO DO TOTEM + RELÓGIO DIGITAL -->
        <header class="w-full max-w-5xl mx-auto flex flex-col gap-3">
            <div class="flex items-center justify-between border-b border-gray-200/70 pb-3 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    @if (!empty($schoolLogoUrl))
                        <div class="flex h-12 w-auto max-w-[140px] items-center justify-center rounded-2xl bg-white p-1.5 shadow-md shadow-gray-200/50 ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10 dark:shadow-none">
                            <img
                                src="{{ $schoolLogoUrl }}"
                                alt="{{ $schoolName ?? 'Logo da Escola' }}"
                                class="h-full w-auto max-h-9 object-contain select-none"
                                onerror="this.parentElement.style.display='none'; document.getElementById('kiosk-fallback-icon').style.display='flex';"
                            />
                        </div>
                        <div id="kiosk-fallback-icon" style="display: none;" class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-md shadow-primary-500/20">
                            <x-heroicon-o-academic-cap class="h-6 w-6" />
                        </div>
                    @else
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-md shadow-primary-500/20">
                            <x-heroicon-o-academic-cap class="h-6 w-6" />
                        </div>
                    @endif
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-base font-bold text-gray-900 tracking-tight dark:text-white sm:text-lg">
                                {{ $schoolName ?? \App\Models\School::getSchoolName() }}
                            </h1>
                            <span class="text-xs font-semibold uppercase px-2 py-0.5 rounded-full bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-300">Terminal de Presença</span>
                            @if($hasMultipleSchools)
                                @if($isSupervisor)
                                    <button
                                        type="button"
                                        wire:click="openSchoolSwitcher"
                                        class="text-[11px] font-semibold text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-400 transition"
                                        title="Alterar unidade escolar vinculada a este terminal (Supervisor conectado)"
                                    >
                                        (Trocar Unidade)
                                    </button>
                                @elseif($kioskSchoolId === null)
                                    <button
                                        type="button"
                                        wire:click="openSchoolSwitcher"
                                        class="text-[11px] font-semibold text-amber-600 hover:text-amber-700 hover:underline dark:text-amber-400 transition animate-pulse"
                                        title="Definir a unidade deste terminal"
                                    >
                                        (Definir Unidade)
                                    </button>
                                @endif
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="date"></p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <span class="font-mono text-xl font-extrabold tracking-wider text-gray-900 dark:text-white sm:text-2xl" x-text="time"></span>
                        <div class="flex items-center justify-end gap-1.5 text-xs text-success-600 dark:text-success-400">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-success-500"></span>
                            </span>
                            <span>Sistema ativo</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BANNER DE ANÚNCIO SUPERIOR (ESTILO NOTÍCIA / LETREIRO ROTATIVO) -->
            <template x-if="announcements.length > 0">
                <div
                    x-on:click="open = true"
                    class="group relative flex cursor-pointer items-center justify-between gap-3 overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500/10 via-primary-500/10 to-amber-500/10 p-3.5 ring-1 ring-primary-500/30 transition-all hover:ring-primary-500 hover:shadow-md dark:from-primary-950/40 dark:via-gray-900 dark:to-primary-950/40"
                >
                    <div class="flex items-center gap-3 overflow-hidden">
                        <template x-if="announcements[current].image">
                            <img
                                :src="announcements[current].image"
                                :alt="announcements[current].title"
                                class="h-8 w-8 rounded-xl object-cover shrink-0 ring-1 ring-primary-500/30"
                                x-on:error="$el.style.display = 'none'"
                            >
                        </template>
                        <template x-if="!announcements[current].image">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary-600 text-white shadow-sm">
                                <x-heroicon-o-megaphone class="h-4 w-4" />
                            </span>
                        </template>
                        <div class="flex items-center gap-2 overflow-hidden text-sm">
                            <span class="shrink-0 font-bold uppercase tracking-wider text-primary-700 dark:text-primary-400">Aviso:</span>
                            <span class="truncate font-semibold text-gray-900 dark:text-white" x-text="announcements[current].title"></span>
                            <span class="hidden text-xs text-gray-500 dark:text-gray-400 sm:inline truncate" x-show="announcements[current].body" x-text="`— ${announcements[current].body}`"></span>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <span class="hidden text-xs font-semibold text-primary-700 dark:text-primary-400 sm:inline group-hover:underline">
                            Ver completo
                        </span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/80 text-primary-700 shadow-sm ring-1 ring-gray-950/5 group-hover:bg-primary-600 group-hover:text-white transition-all dark:bg-gray-800 dark:text-primary-300">
                            <x-heroicon-m-arrow-right class="h-4 w-4" />
                        </span>
                    </div>
                </div>
            </template>
        </header>

        <!-- CENTRO: FORMULÁRIO PRINCIPAL PERFEITAMENTE CENTRALIZADO -->
        <main class="w-full flex-1 flex items-center justify-center py-6">
            <div class="w-full max-w-xl mx-auto">

                @if (! $kioskSchoolId && $hasMultipleSchools)
                    <!-- AVISO DE TERMINAL NÃO VINCULADO A UMA UNIDADE ESPECÍFICA -->
                    <div class="mb-4 rounded-2xl border border-amber-500/30 bg-amber-50/90 p-4 text-xs text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <x-heroicon-o-building-library class="h-5 w-5 text-amber-600 shrink-0 dark:text-amber-400" />
                            <span>Este terminal está no <strong>Modo Geral</strong>. Fixe este computador na unidade escolar correspondente:</span>
                        </div>
                        <button
                            type="button"
                            wire:click="openSchoolSwitcher"
                            class="shrink-0 rounded-xl bg-amber-600 px-3.5 py-1.5 font-bold text-white hover:bg-amber-700 transition shadow-sm"
                        >
                            Selecionar Unidade
                        </button>
                    </div>
                @endif

                @if (! $isAnsweringSurvey)
                    <!-- CARD CENTRAL: CONFIRMAR PRESENÇA -->
                    <div class="rounded-3xl bg-white shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 transition-all">
                        <div class="border-b border-gray-100 px-8 py-8 text-center dark:border-gray-800">
                            @if (!empty($schoolLogoUrl))
                                <div class="mb-4 flex justify-center">
                                    <div class="inline-flex items-center justify-center rounded-2xl bg-gray-50/80 p-2 ring-1 ring-gray-950/5 dark:bg-gray-800/60 dark:ring-white/10 shadow-sm">
                                        <img src="{{ $schoolLogoUrl }}" alt="{{ $schoolName ?? 'Logo' }}" class="h-8 w-auto max-w-[160px] object-contain select-none" onerror="this.parentElement.style.display='none'" />
                                    </div>
                                </div>
                            @endif

                            <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary-100/80 dark:bg-primary-500/10">
                                <span class="absolute inset-0 rounded-full animate-ping bg-primary-400/20"></span>
                                <x-heroicon-o-finger-print class="h-10 w-10 text-primary-600 dark:text-primary-400" />
                            </div>

                            <h2 class="mt-4 text-2xl font-black text-gray-950 tracking-tight dark:text-white sm:text-3xl">
                                Confirmar presença
                            </h2>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Digite o número do seu contrato para registrar sua presença na aula.
                            </p>
                        </div>

                        <form wire:submit="confirmPresence" class="space-y-6 p-8">
                            <div class="[&_input]:h-16 [&_input]:rounded-2xl [&_input]:text-center [&_input]:text-3xl [&_input]:font-extrabold [&_input]:tracking-[0.25em] [&_input]:shadow-inner">
                                {{ $this->form }}
                            </div>

                            <div x-data="{ lookupOpen: false }" class="-mt-2">
                                <button
                                    type="button"
                                    x-on:click="lookupOpen = ! lookupOpen"
                                    class="w-full text-center text-sm font-semibold text-primary-700 underline underline-offset-4 hover:text-primary-800 dark:text-primary-400"
                                >
                                    Não sei meu número de contrato
                                </button>

                                <div x-cloak x-show="lookupOpen" x-transition class="mt-4 space-y-4 rounded-2xl bg-gray-50 p-5 ring-1 ring-gray-950/5 dark:bg-gray-950 dark:ring-white/10">
                                    <div>
                                        <h3 class="font-bold text-gray-950 dark:text-white">Consultar número do contrato</h3>
                                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Informe seu nome completo e sua data de nascimento conforme cadastrado na secretaria.</p>
                                    </div>

                                    <div>
                                        <label for="lookup-name" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">Nome completo</label>
                                        <input
                                            id="lookup-name"
                                            type="text"
                                            wire:model="lookupName"
                                            autocomplete="name"
                                            maxlength="255"
                                            placeholder="Ex.: João da Silva"
                                            class="h-12 w-full rounded-xl border-0 bg-white px-4 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10"
                                        >
                                        @error('name') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="lookup-birth-date" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">Data de nascimento</label>
                                        <input
                                            id="lookup-birth-date"
                                            type="date"
                                            wire:model="lookupBirthDate"
                                            autocomplete="bday"
                                            max="{{ now()->toDateString() }}"
                                            class="h-12 w-full rounded-xl border-0 bg-white px-4 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10"
                                        >
                                        @error('birth_date') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                                    </div>

                                    <x-filament::button
                                        type="button"
                                        wire:click="lookupContractNumber"
                                        wire:loading.attr="disabled"
                                        wire:target="lookupContractNumber"
                                        class="w-full"
                                    >
                                        <span wire:loading.remove wire:target="lookupContractNumber">Consultar contrato</span>
                                        <span wire:loading wire:target="lookupContractNumber">Consultando...</span>
                                    </x-filament::button>

                                    @if ($lookupSubmitted && count($lookupContractNumbers) > 0)
                                        <div class="rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-900 dark:bg-success-950/40" role="status">
                                            <p class="text-xs font-bold text-success-800 dark:text-success-300">Encontramos estes contratos ativos:</p>
                                            <ul class="mt-2 space-y-1">
                                                @foreach ($lookupContractNumbers as $contractNumber)
                                                    <li class="font-mono text-base font-extrabold tracking-wider text-success-900 dark:text-success-200">{{ $contractNumber }}</li>
                                                @endforeach
                                            </ul>
                                            <p class="mt-2 text-xs text-success-700 dark:text-success-400">Digite seu contrato no campo acima para confirmar presença.</p>
                                        </div>
                                    @elseif ($lookupSubmitted)
                                        <p class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-xs font-medium text-warning-800 dark:border-warning-900 dark:bg-warning-950/40 dark:text-warning-300" role="status">
                                            Não encontramos contrato ativo com esses dados. Confira o nome e a data de nascimento ou solicite auxílio na secretaria.
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <x-filament::button
                                type="submit"
                                size="xl"
                                class="h-16 w-full rounded-2xl text-lg font-bold shadow-lg shadow-primary-500/20"
                            >
                                Confirmar presença
                            </x-filament::button>
                        </form>
                    </div>

                @else
                    <!-- CARD CENTRAL: PESQUISA DE SATISFAÇÃO OBRIGATÓRIA -->
                    <div class="rounded-3xl bg-white shadow-2xl ring-2 ring-primary-500/30 dark:bg-gray-900 dark:ring-white/10 max-w-2xl mx-auto">
                        <div class="border-b border-gray-100 bg-gradient-to-r from-primary-50 via-amber-50/50 to-primary-50 px-8 py-6 dark:border-gray-800 dark:from-primary-950/30 dark:via-gray-900 dark:to-primary-950/30 rounded-t-3xl">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-md">
                                    <x-heroicon-o-chat-bubble-bottom-center-text class="h-6 w-6" />
                                </div>
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-primary-700 dark:text-primary-400">Pesquisa de Satisfação Obrigatória</span>
                                    <h2 class="text-xl font-black text-gray-950 dark:text-white">
                                        {{ $pendingSurveyData['title'] ?? 'Pesquisa de Satisfação' }}
                                    </h2>
                                </div>
                            </div>
                            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                Olá, <strong class="text-primary-800 dark:text-primary-300">{{ $pendingStudentName }}</strong>! Responda às perguntas abaixo para concluir sua presença na aula:
                            </p>
                            @if (!empty($pendingSurveyData['description']))
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 italic">
                                    {{ $pendingSurveyData['description'] }}
                                </p>
                            @endif
                        </div>

                        <form wire:submit="submitSurveyAndConfirmPresence" class="space-y-6 p-8">
                            <div class="space-y-6 max-h-[50vh] overflow-y-auto pr-2">
                                @foreach ($pendingSurveyData['questions'] ?? [] as $index => $q)
                                    <div class="rounded-2xl bg-gray-50 p-5 ring-1 ring-gray-950/5 dark:bg-gray-950/70 dark:ring-white/10">
                                        <label class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $index + 1 }}. {{ $q['question'] }}
                                            @if ($q['is_required'])
                                                <span class="text-danger-500 font-bold">*</span>
                                            @endif
                                        </label>

                                        <!-- RATING 1 A 5 ESTRELAS -->
                                        @if ($q['type'] === 'rating_5')
                                            <div class="mt-3 flex items-center gap-3">
                                                @for ($star = 1; $star <= 5; $star++)
                                                    @php
                                                        $isSelected = ($surveyAnswers[$q['id']]['rating'] ?? null) === $star;
                                                        $isLowerOrEqual = ($surveyAnswers[$q['id']]['rating'] ?? 0) >= $star;
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="setAnswerRating({{ $q['id'] }}, {{ $star }})"
                                                        class="flex h-12 w-12 items-center justify-center rounded-xl text-2xl transition-all shadow-sm {{ $isLowerOrEqual ? 'bg-amber-100 text-amber-500 ring-2 ring-amber-400 scale-105 dark:bg-amber-950 dark:text-amber-400' : 'bg-white text-gray-300 hover:text-amber-300 dark:bg-gray-900 dark:text-gray-700' }}"
                                                    >
                                                        ★
                                                    </button>
                                                @endfor
                                                @if (isset($surveyAnswers[$q['id']]['rating']))
                                                    <span class="ml-2 text-sm font-bold text-amber-600 dark:text-amber-400">
                                                        {{ $surveyAnswers[$q['id']]['rating'] }}/5
                                                    </span>
                                                @endif
                                            </div>

                                        <!-- NPS 0 A 10 -->
                                        @elseif ($q['type'] === 'nps_10')
                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @for ($score = 0; $score <= 10; $score++)
                                                    @php
                                                        $isSelected = ($surveyAnswers[$q['id']]['rating'] ?? null) === $score;
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="setAnswerRating({{ $q['id'] }}, {{ $score }})"
                                                        class="h-10 w-10 rounded-xl text-sm font-bold transition-all {{ $isSelected ? 'bg-primary-600 text-white shadow-md scale-110' : 'bg-white text-gray-700 ring-1 ring-gray-950/10 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-gray-800' }}"
                                                    >
                                                        {{ $score }}
                                                    </button>
                                                @endfor
                                            </div>

                                        <!-- MÚLTIPLA ESCOLHA -->
                                        @elseif ($q['type'] === 'multiple_choice' && !empty($q['options']))
                                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                @foreach ($q['options'] as $option)
                                                    @php
                                                        $isSelected = ($surveyAnswers[$q['id']]['text'] ?? null) === $option;
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="setAnswerText({{ $q['id'] }}, '{{ addslashes($option) }}')"
                                                        class="flex items-center gap-2 rounded-xl p-3 text-left text-sm font-semibold transition-all {{ $isSelected ? 'bg-primary-50 text-primary-900 ring-2 ring-primary-600 dark:bg-primary-950/40 dark:text-primary-200' : 'bg-white text-gray-700 ring-1 ring-gray-950/10 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-gray-800' }}"
                                                    >
                                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $isSelected ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-400' }}">
                                                            @if ($isSelected)
                                                                <span class="h-2 w-2 rounded-full bg-white"></span>
                                                            @endif
                                                        </span>
                                                        <span>{{ $option }}</span>
                                                    </button>
                                                @endforeach
                                            </div>

                                        <!-- TEXTO LIVRE -->
                                        @elseif ($q['type'] === 'text')
                                            <div class="mt-3">
                                                <textarea
                                                    wire:model.defer="surveyAnswers.{{ $q['id'] }}.text"
                                                    placeholder="Digite sua resposta ou sugestão..."
                                                    rows="3"
                                                    class="w-full rounded-xl border-0 bg-white p-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10"
                                                ></textarea>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                                <x-filament::button
                                    type="submit"
                                    size="xl"
                                    class="flex-1 h-16 rounded-2xl text-base font-bold shadow-lg shadow-primary-500/20"
                                >
                                    Enviar e confirmar presença
                                </x-filament::button>

                                <x-filament::button
                                    type="button"
                                    color="gray"
                                    outlined
                                    wire:click="cancelSurvey"
                                    class="h-16 px-6 rounded-2xl"
                                >
                                    Voltar
                                </x-filament::button>
                            </div>
                        </form>
                    </div>
                @endif

            </div>
        </main>

        <!-- RODAPÉ DISCRETO DO TOTEM -->
        <footer class="w-full max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 border-t border-gray-200/70 pt-3 text-xs text-gray-400 dark:border-gray-800 dark:text-gray-500">
            <div class="flex items-center gap-2">
                <span>Terminal Autônomo de Presença • Aurosoft</span>
                @if($pinnedSchool)
                    <span class="hidden sm:inline">•</span>
                    <span class="font-medium text-gray-600 dark:text-gray-300">Unidade: {{ $pinnedSchool->getFilamentName() }}</span>
                @endif
            </div>
            <div class="flex items-center gap-4">
                @if($hasMultipleSchools)
                    <button
                        type="button"
                        wire:click="openSchoolSwitcher"
                        class="inline-flex items-center gap-1 hover:text-primary-600 dark:hover:text-primary-400 underline transition text-[11px]"
                        title="{{ $isSupervisor ? 'Configurar terminal' : 'Acesso restrito à equipe escolar' }}"
                    >
                        <x-heroicon-m-lock-closed class="h-3 w-3" />
                        <span>{{ $isSupervisor ? 'Configurar Unidade' : 'Configuração do Terminal' }}</span>
                    </button>
                @endif
                <span>Em caso de dúvidas, consulte o(a) educador(a).</span>
            </div>
        </footer>

        <!-- MODAL DE AUTENTICAÇÃO DO SUPERVISOR/COLABORADOR -->
        @if($showSupervisorAuthModal)
            <div class="fixed inset-0 z-[160] flex items-center justify-center bg-gray-950/75 p-4 backdrop-blur-sm">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-white/10">
                        <div class="flex items-center gap-2">
                            <div class="rounded-xl bg-amber-100 p-2 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                <x-heroicon-o-shield-check class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Acesso da Equipe Escolar</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Autentique-se para configurar este terminal:</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="closeSupervisorAuth"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <x-heroicon-m-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <form wire:submit.prevent="authenticateSupervisor" class="mt-4 space-y-4">
                        @if($supervisorAuthError)
                            <div class="rounded-xl bg-danger-50 p-3 text-xs font-medium text-danger-700 ring-1 ring-danger-200 dark:bg-danger-950/50 dark:text-danger-300 dark:ring-danger-800">
                                {{ $supervisorAuthError }}
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                E-mail, CPF ou Usuário institucional
                            </label>
                            <input
                                type="text"
                                wire:model.defer="supervisorLogin"
                                class="w-full rounded-xl border-gray-300 bg-white p-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="Ex.: admin@admin.com ou seu CPF"
                                required
                                autofocus
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Senha
                            </label>
                            <input
                                type="password"
                                wire:model.defer="supervisorPassword"
                                class="w-full rounded-xl border-gray-300 bg-white p-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="Sua senha de acesso"
                                required
                            />
                        </div>

                        <div class="mt-5 flex items-center justify-end gap-2 border-t border-gray-100 pt-3 dark:border-white/10">
                            <button
                                type="button"
                                wire:click="closeSupervisorAuth"
                                class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-primary-700 transition"
                            >
                                <x-heroicon-m-key class="h-4 w-4" />
                                <span>Desbloquear Terminal</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- MODAL DE TROCA DE UNIDADE (SUPERVISÃO) -->
        @if($showSchoolSwitcherModal)
            <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur-sm">
                <div class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-white/10">
                        <div class="flex items-center gap-2">
                            <div class="rounded-xl bg-primary-100 p-2 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                                <x-heroicon-o-building-library class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Alterar Unidade do Totem</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Selecione a qual unidade escolar este terminal ficará vinculado:</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="closeSchoolSwitcher"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <x-heroicon-m-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-4 space-y-2 max-h-72 overflow-y-auto">
                        @foreach($this->availableSchools() as $school)
                            <button
                                type="button"
                                wire:click="selectKioskSchool({{ $school['id'] }})"
                                class="w-full flex items-center justify-between rounded-xl border p-3 text-left transition {{ $kioskSchoolId === $school['id'] ? 'border-primary-500 bg-primary-50/50 dark:border-primary-500/60 dark:bg-primary-950/30' : 'border-gray-200 bg-gray-50/50 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-800/40 dark:hover:bg-gray-800' }}"
                            >
                                <div>
                                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $school['name'] }}</p>
                                    @if($school['city'] || $school['state'])
                                        <p class="text-xs text-gray-500 dark:text-gray-400">📍 {{ $school['city'] ?? '' }}{{ $school['city'] && $school['state'] ? '/' : '' }}{{ $school['state'] ?? '' }}</p>
                                    @endif
                                </div>
                                @if($kioskSchoolId === $school['id'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                                        Atual
                                    </span>
                                @else
                                    <span class="text-xs font-semibold text-primary-600 dark:text-primary-400">
                                        Selecionar ➜
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-5 flex justify-end border-t border-gray-100 pt-3 dark:border-white/10">
                        <button
                            type="button"
                            wire:click="closeSchoolSwitcher"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL DE AVISO POR COMPLETO -->
        @if (count($studentAnnouncements) > 0)
        <div
            x-cloak
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="student-announcement-title"
        >
            <div
                x-transition
                class="relative max-h-[92vh] w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-gray-900"
            >
                <div class="max-h-[92vh] overflow-y-auto">
                    <template x-if="announcements.length > 0">
                        <div>
                            <template x-if="announcements[current].image">
                                <img
                                    :src="announcements[current].image"
                                    :alt="announcements[current].title"
                                    class="max-h-[45vh] w-full bg-gray-100 object-contain dark:bg-gray-950"
                                    x-on:error="$el.style.display = 'none'"
                                >
                            </template>

                            <div class="p-6 sm:p-8">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">Aviso da Escola</p>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400" x-text="`${current + 1} de ${announcements.length}`"></span>
                                </div>
                                <h2 id="student-announcement-title" class="text-2xl font-black text-gray-950 dark:text-white" x-text="announcements[current].title"></h2>
                                <p
                                    x-show="announcements[current].body"
                                    class="mt-4 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300 sm:text-base"
                                    x-text="announcements[current].body"
                                ></p>

                                <a
                                    x-show="announcements[current].url"
                                    :href="announcements[current].url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700"
                                >
                                    Acessar link
                                    <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4" />
                                </a>
                            </div>

                            <div x-show="announcements.length > 1" class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                                <x-filament::button type="button" color="gray" outlined x-on:click="previous()" icon="heroicon-m-chevron-left">
                                    Anterior
                                </x-filament::button>
                                <div class="flex gap-2">
                                    <template x-for="(announcement, index) in announcements" :key="index">
                                        <button
                                            type="button"
                                            x-on:click="show(index)"
                                            class="h-2.5 w-2.5 rounded-full"
                                            :class="current === index ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
                                            :aria-label="`Mostrar aviso ${index + 1}`"
                                        ></button>
                                    </template>
                                </div>
                                <x-filament::button type="button" color="gray" outlined x-on:click="next()" icon="heroicon-m-chevron-right" icon-position="after">
                                    Próximo
                                </x-filament::button>
                            </div>

                            <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                                <x-filament::button
                                    type="button"
                                    x-on:click="open = false"
                                >
                                    Fechar aviso
                                </x-filament::button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @endif

        <!-- OVERLAY DE SUCESSO / REDIRECIONAMENTO LMS -->
        <template x-if="lmsRedirectUrl">
            <div class="fixed inset-0 z-[200] flex items-center justify-center bg-gray-950/80 backdrop-blur-md p-4">
                <div class="rounded-3xl bg-white p-8 text-center max-w-md w-full shadow-2xl dark:bg-gray-900 border border-primary-500/30">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 mb-4 animate-bounce dark:bg-primary-950 dark:text-primary-400">
                        <x-heroicon-o-academic-cap class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-black text-gray-950 dark:text-white">Presença confirmada!</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" x-text="lmsRedirectMessage"></p>
                    <div class="mt-6 flex flex-col gap-2">
                        <a
                            :href="lmsRedirectUrl"
                            class="inline-flex h-14 items-center justify-center rounded-2xl bg-primary-600 px-6 font-bold text-white shadow-lg shadow-primary-500/30 hover:bg-primary-700 transition-all text-base"
                            x-text="lmsRedirectButtonText"
                        >
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-filament-panels::page>
