<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans antialiased"
     x-data="{ 
         sidebarOpen: localStorage.getItem('portal_sidebar_open') !== 'false', 
         mobileNavOpen: false,
         toggleSidebar() {
             this.sidebarOpen = !this.sidebarOpen;
             localStorage.setItem('portal_sidebar_open', this.sidebarOpen);
         }
     }">

    @php
        $portalSchool = ($portalSchoolId ?? null) ? \App\Models\School::find($portalSchoolId) : (\App\Models\School::getActiveSchool() ?? \App\Models\School::first());
        $brandHex = $portalSchool?->brand_color ?: '#F59E0B';
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $brandHex)) {
            $brandHex = '#F59E0B';
        }
        [$r, $g, $b] = sscanf($brandHex, "#%02x%02x%02x");
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        $brandTextColor = $yiq >= 150 ? '#020617' : '#ffffff';
    @endphp

    <style>
        :root {
            --school-brand: {{ $brandHex }};
            --school-brand-rgb: {{ $r }}, {{ $g }}, {{ $b }};
            --school-brand-text: {{ $brandTextColor }};
        }
        .bg-school-brand { background-color: var(--school-brand) !important; color: var(--school-brand-text) !important; }
        .text-school-brand { color: var(--school-brand) !important; }
        .border-school-brand { border-color: var(--school-brand) !important; }
        .bg-school-brand-soft { background-color: rgba(var(--school-brand-rgb), 0.12) !important; color: var(--school-brand) !important; }
        .shadow-school-brand { box-shadow: 0 10px 25px -5px rgba(var(--school-brand-rgb), 0.35) !important; }
    </style>

    {{-- CABEÇALHO SUPERIOR GLOBAL (HEADER FIXO: IDENTIDADE, SELETOR DE MODO E AÇÕES GLOBAIS) --}}
    <header class="sticky top-0 z-40 bg-slate-900/95 border-b border-slate-800/80 backdrop-blur shadow-lg shadow-black/25">
        <div class="w-full px-4 sm:px-6">
            <div class="flex items-center justify-between gap-3 py-2.5">
                {{-- CONTROLES DE ABERTURA, LOGO & IDENTIFICAÇÃO DO USUÁRIO / ESCOLA --}}
                <div class="flex items-center gap-3 min-w-0">
                    {{-- BOTÃO MOSTRAR / OCULTAR SIDEBAR NO DESKTOP --}}
                    <button type="button"
                            @click="toggleSidebar()"
                            class="hidden lg:inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-slate-700 bg-slate-800/80 text-slate-300 hover:text-white hover:bg-slate-700 hover:border-slate-600 transition cursor-pointer shrink-0"
                            :title="sidebarOpen ? 'Ocultar menu lateral' : 'Mostrar menu lateral'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </button>

                    {{-- BOTÃO HAMBÚRGUER NO MOBILE --}}
                    <button type="button"
                            @click="mobileNavOpen = true"
                            class="lg:hidden inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-slate-700 bg-slate-800/80 text-slate-300 hover:text-white hover:bg-slate-700 transition cursor-pointer shrink-0"
                            aria-label="Abrir Menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    @if (!empty($schoolLogoUrl))
                        <div class="flex h-10 w-auto max-w-[120px] shrink-0 items-center justify-center rounded-2xl bg-white p-1 shadow-md shadow-black/20 ring-1 ring-white/10">
                            <img src="{{ $schoolLogoUrl }}" alt="{{ $schoolName }}" class="h-full w-auto max-h-8 object-contain select-none" onerror="this.parentElement.style.display='none'; document.getElementById('student-portal-school-initial').style.display='flex';" />
                        </div>
                        <div id="student-portal-school-initial" style="display: none;" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-school-brand font-black shadow-school-brand text-base sm:text-lg">
                            {{ strtoupper(substr($schoolName, 0, 1)) }}
                        </div>
                    @else
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-school-brand font-black shadow-school-brand text-base sm:text-lg">
                            {{ strtoupper(substr($schoolName, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 truncate">
                            <span class="text-xs font-black text-school-brand uppercase tracking-wider truncate">{{ $schoolName }}</span>
                            <span class="text-slate-600 hidden sm:inline">•</span>
                            <span class="hidden sm:inline">
                                @if($isEducatorMode)
                                    <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                        ● Modo Educador
                                    </span>
                                @elseif($this->isEducatorTestingAsStudent)
                                    <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        ● Teste de Aluno
                                    </span>
                                @elseif($this->contract)
                                    <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        ● Matrícula Ativa ({{ $this->contract->contract_number }})
                                    </span>
                                @endif
                            </span>
                        </div>
                        <h1 class="text-sm sm:text-base font-extrabold text-white truncate">
                            @if($isManagerPreview && $this->contract)
                                {{ $this->contract->student?->name }} <span class="text-xs font-normal text-amber-400">(Simulação)</span>
                            @elseif($this->contract && ! $isEducatorMode)
                                {{ $this->contract->student?->name ?? Auth::user()->name }}
                            @else
                                {{ Auth::user()->name ?? (Auth::user()?->isEducator() ? 'Educador' : 'Colaborador') }}
                            @endif
                        </h1>
                    </div>
                </div>

                {{-- AÇÕES DESKTOP: SELETOR DE MODO, VOLTAR AO ADMIN, LOGOUT --}}
                <div class="flex items-center gap-2.5">
                    {{-- MODO SWITCHER (MINHA MATRÍCULA VS ACESSO EDUCADOR) --}}
                    @if($this->canSwitchPortalMode)
                        <div class="inline-flex p-1 bg-slate-950 border border-slate-800 rounded-2xl shadow-inner">
                            <button type="button" wire:click="switchPortalMode('student')"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $portalMode === 'student' ? 'bg-school-brand shadow-school-brand' : 'text-slate-400 hover:text-white' }}"
                                    title="Modo Aluno: Todos os cursos liberados com progressão sequencial e travas">
                                <span class="text-sm">🎓</span>
                                <span class="hidden sm:inline">Minha Matrícula</span>
                            </button>
                            <button type="button" wire:click="switchPortalMode('educator')"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $portalMode === 'educator' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white' }}"
                                    title="{{ Auth::user()?->isEducator() ? 'Modo Educador: Acesso pedagógico irrestrito' : 'Acesso Equipe' }}">
                                <span class="text-sm">{{ Auth::user()?->isEducator() ? '👨‍🏫' : '💼' }}</span>
                                <span class="hidden sm:inline">{{ Auth::user()?->isEducator() ? 'Acesso Educador' : 'Acesso Equipe' }}</span>
                            </button>
                        </div>
                    @endif

                    @if($canReturnToAdmin)
                        <a href="{{ route('filament.admin.pages.dashboard') }}"
                           class="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-indigo-500/30 bg-indigo-500/10 hover:bg-indigo-500/20 px-3 py-1.5 text-xs font-bold text-indigo-300 hover:text-white transition">
                            <span>🏢</span>
                            <span class="hidden md:inline">Voltar ao Admin</span>
                        </a>
                    @endif

                    <button type="button"
                            wire:click="openChangePasswordModal"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-1.5 text-xs font-bold text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer"
                            title="Alterar Senha Individual">
                        <span>🔑</span>
                        <span class="hidden sm:inline">Alterar Senha</span>
                    </button>

                    <form method="POST" action="{{ route('student.logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-1.5 text-xs font-bold text-rose-300 hover:bg-rose-500 hover:text-white transition cursor-pointer"
                                title="Sair do Portal">
                            <span>🚪</span>
                            <span class="hidden sm:inline">Sair</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- CORPO PRINCIPAL COM SIDEBAR LATERAL E ÁREA DE CONTEÚDO --}}
    <div class="flex flex-1 min-h-[calc(100vh-65px)]">

        {{-- SIDEBAR LATERAL DESKTOP (COM SUPORTE A MOSTRAR / OCULTAR) --}}
        <aside x-show="sidebarOpen"
               x-transition:enter="transition-all ease-out duration-250"
               x-transition:enter-start="-ml-64 opacity-0"
               x-transition:enter-end="ml-0 opacity-100"
               x-transition:leave="transition-all ease-in duration-200"
               x-transition:leave-start="ml-0 opacity-100"
               x-transition:leave-end="-ml-64 opacity-0"
               class="hidden lg:flex flex-col justify-between shrink-0 w-64 border-r border-slate-800/80 bg-slate-900/95 p-3.5 backdrop-blur z-30 sticky top-[65px] h-[calc(100vh-65px)] overflow-y-auto no-scrollbar">

            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-800/80 px-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Navegação</span>
                    <button type="button"
                            @click="toggleSidebar()"
                            class="rounded-lg p-1 text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                            title="Ocultar menu lateral">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                </div>

                {{-- MENU VERTICAL DE ABAS --}}
                <nav class="space-y-1">
                    <button type="button"
                            wire:click="setTab('dashboard')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'dashboard' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                        <span class="text-base shrink-0">📚</span>
                        <span class="truncate">Formações & Cursos</span>
                    </button>

                    <button type="button"
                            wire:click="setTab('courses')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'courses' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                        <span class="text-base shrink-0">📖</span>
                        <span class="truncate">Roteiro de Aulas</span>
                    </button>

                    <button type="button"
                            wire:click="setTab('achievements')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'achievements' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                        <span class="text-base shrink-0">🏅</span>
                        <span class="truncate">Conquistas & Notas</span>
                    </button>

                    <button type="button"
                            wire:click="setTab('ranking')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'ranking' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                        <span class="text-base shrink-0">🏆</span>
                        <span class="truncate">Ranking Escolar</span>
                    </button>

                    @if($showcaseFormations->isNotEmpty())
                        <button type="button"
                                wire:click="setTab('catalog')"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'catalog' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                            <span class="text-base shrink-0">🚀</span>
                            <span class="truncate">Catálogo de Cursos</span>
                        </button>
                    @endif

                    <a href="{{ route('student.typing', ['contract_id' => $this->contract?->id]) }}"
                       class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-300 hover:bg-slate-800/80 hover:text-white transition"
                       title="Treino de Digitação">
                        <span class="text-base shrink-0">⌨️</span>
                        <span class="truncate">Digitação</span>
                    </a>

                    @if($this->contract)
                        <button type="button"
                                wire:click="setTab('documents')"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'documents' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                            <span class="text-base shrink-0">📂</span>
                            <span class="truncate">Meus Documentos</span>
                        </button>
                        <a href="{{ route('student.boletim', ['contract' => $this->contract->id]) }}"
                           target="_blank"
                           class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-300 hover:bg-slate-800/80 hover:text-white transition"
                           title="Meu Boletim Escolar">
                            <span class="text-base shrink-0">📜</span>
                            <span class="truncate">Meu Boletim</span>
                        </a>

                        <button type="button"
                                wire:click="setTab('tickets')"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'tickets' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                            <span class="text-base shrink-0">💬</span>
                            <span class="truncate">Suporte & Chamados</span>
                        </button>
                    @endif
                </nav>
            </div>

            {{-- RODAPÉ DA SIDEBAR: REINICIAR TESTE & BOTÃO DE OCULTAR --}}
            <div class="pt-3 border-t border-slate-800/80 space-y-2">
                @if($this->isEducatorTestingAsStudent && $this->selectedCourse)
                    <button type="button"
                            wire:click="resetCourseTestProgress({{ $this->selectedCourse->id }})"
                            class="w-full flex items-center justify-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs font-bold text-amber-300 hover:bg-amber-500/20 transition cursor-pointer"
                            title="Reiniciar o histórico de teste das aulas deste curso">
                        <span>🔄</span>
                        <span class="truncate">Reiniciar Teste</span>
                    </button>
                @endif

                <button type="button"
                        @click="toggleSidebar()"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs text-slate-400 hover:text-white hover:bg-slate-800/60 transition cursor-pointer">
                    <span class="truncate">Ocultar menu lateral</span>
                    <span class="text-xs">◀</span>
                </button>
            </div>
        </aside>

        {{-- DRAWER OFF-CANVAS MOBILE (ABERTO PELO HAMBÚRGUER) --}}
        <div x-cloak
             x-show="mobileNavOpen"
             class="lg:hidden fixed inset-0 z-50 flex"
             role="dialog"
             aria-modal="true">
            {{-- BACKDROP ESCURO --}}
            <div x-show="mobileNavOpen"
                 x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileNavOpen = false"
                 class="fixed inset-0 bg-black/70 backdrop-blur-xs"></div>

            {{-- PAINEL LATERAL DESLIZANTE MOBILE --}}
            <div x-show="mobileNavOpen"
                 x-transition:enter="transition ease-in-out duration-250 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-200 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="relative mr-16 flex w-full max-w-xs flex-1 flex-col bg-slate-900 border-r border-slate-800 p-4 shadow-2xl justify-between overflow-y-auto">

                <div class="space-y-4">
                    {{-- CABEÇALHO DO DRAWER --}}
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 font-black shadow-md text-base">
                                {{ strtoupper(substr($schoolName, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">{{ $schoolName }}</div>
                                <div class="text-xs font-extrabold text-white">Portal do Aluno</div>
                            </div>
                        </div>
                        <button type="button"
                                @click="mobileNavOpen = false"
                                class="rounded-xl p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition">
                            ✕
                        </button>
                    </div>

                    {{-- CARD DO USUÁRIO NO DRAWER --}}
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-3">
                        <div class="text-xs font-bold text-white truncate">
                            {{ $this->contract?->student?->name ?? Auth::user()->name }}
                        </div>
                        @if($this->contract)
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                Matrícula: <strong class="text-amber-400">{{ $this->contract->contract_number }}</strong>
                            </div>
                        @endif
                        <div class="mt-1.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $isEducatorMode ? 'bg-indigo-500/20 text-indigo-300' : ($this->isEducatorTestingAsStudent ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300') }}">
                                ● {{ $isEducatorMode ? 'Acesso Educador' : ($this->isEducatorTestingAsStudent ? 'Teste de Aluno' : 'Matrícula Ativa') }}
                            </span>
                        </div>
                    </div>

                    {{-- MODO SWITCHER MOBILE --}}
                    @if($this->canSwitchPortalMode)
                        <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-950 border border-slate-800 rounded-2xl">
                            <button type="button" wire:click="switchPortalMode('student')"
                                    @click="mobileNavOpen = false"
                                    class="flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-bold transition {{ $portalMode === 'student' ? 'bg-amber-500 text-slate-950 shadow-sm shadow-amber-500/20' : 'text-slate-400 hover:text-white' }}">
                                <span>🎓</span>
                                <span>Aluno</span>
                            </button>
                            <button type="button" wire:click="switchPortalMode('educator')"
                                    @click="mobileNavOpen = false"
                                    class="flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-bold transition {{ $portalMode === 'educator' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20' : 'text-slate-400 hover:text-white' }}">
                                <span>👨‍🏫</span>
                                <span>Educador</span>
                            </button>
                        </div>
                    @endif

                    {{-- LISTA DE LINKS PRINCIPAIS MOBILE --}}
                    <nav class="space-y-1">
                        <button type="button"
                                wire:click="setTab('dashboard')"
                                @click="mobileNavOpen = false"
                                class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'dashboard' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base">📚</span>
                            <span>Formações & Cursos</span>
                        </button>

                        <button type="button"
                                wire:click="setTab('courses')"
                                @click="mobileNavOpen = false"
                                class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'courses' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base">📖</span>
                            <span>Roteiro de Aulas</span>
                        </button>

                        <button type="button"
                                wire:click="setTab('achievements')"
                                @click="mobileNavOpen = false"
                                class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'achievements' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base">🏅</span>
                            <span>Conquistas & Notas</span>
                        </button>

                        <button type="button"
                                wire:click="setTab('ranking')"
                                @click="mobileNavOpen = false"
                                class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'ranking' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base">🏆</span>
                            <span>Ranking Escolar</span>
                        </button>

                        @if($showcaseFormations->isNotEmpty())
                            <button type="button"
                                    wire:click="setTab('catalog')"
                                    @click="mobileNavOpen = false"
                                    class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'catalog' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="text-base">🚀</span>
                                <span>Catálogo de Cursos</span>
                            </button>
                        @endif

                        <a href="{{ route('student.typing', ['contract_id' => $this->contract?->id]) }}"
                           class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                            <span class="text-base">⌨️</span>
                            <span>Treino de Digitação</span>
                        </a>

                        @if($this->contract)
                            <button type="button"
                                    wire:click="setTab('documents')"
                                    @click="mobileNavOpen = false"
                                    class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'documents' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="text-base">📂</span>
                                <span>Meus Documentos</span>
                            </button>
                            <a href="{{ route('student.boletim', ['contract' => $this->contract->id]) }}"
                               target="_blank"
                               class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                                <span class="text-base">📜</span>
                                <span>Meu Boletim Escolar</span>
                            </a>
                            <button type="button"
                                    wire:click="setTab('tickets')"
                                    @click="mobileNavOpen = false"
                                    class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'tickets' ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="text-base">💬</span>
                                <span>Suporte & Chamados</span>
                            </button>
                        @endif
                    </nav>
                </div>

                <div class="space-y-2 pt-4 border-t border-slate-800">
                    @if($canReturnToAdmin)
                        <a href="{{ route('filament.admin.pages.dashboard') }}"
                           class="w-full flex items-center justify-center gap-2 rounded-xl border border-indigo-500/40 bg-indigo-500/15 px-3 py-2 text-xs font-bold text-indigo-300 hover:text-white">
                            <span>🏢</span>
                            <span>Voltar ao Admin</span>
                        </a>
                    @endif

                    <button type="button"
                            wire:click="openChangePasswordModal"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-1.5 text-xs font-bold text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer"
                            title="Alterar Senha Individual">
                        <span>🔑</span>
                        <span class="hidden sm:inline">Alterar Senha</span>
                    </button>

                    <form method="POST" action="{{ route('student.logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-xs font-bold text-rose-300 hover:bg-rose-500 hover:text-white transition">
                            <span>🚪</span>
                            <span>Sair</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    {{-- CONTEÚDO PRINCIPAL --}}
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

        {{-- RESUMO RÁPIDO DO ALUNO (FIXADO NO TOPO, APÓS O HEADER PRINCIPAL) --}}
        @php
            $overallPct = $totalLessonsInFormation > 0 ? min(100, round(($completedLessonsCount / $totalLessonsInFormation) * 100)) : 0;
            $studyHours = floor($totalStudyTimeSeconds / 3600);
            $studyMinutes = floor(($totalStudyTimeSeconds % 3600) / 60);
            $myRank = $leaderboard['current_student_rank'] ?? null;
        @endphp
        <div class="rounded-3xl border border-slate-800/90 bg-slate-900/60 p-4 shadow-md">
            <div class="flex items-center justify-between mb-3 px-1">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Resumo Rápido do Aluno</span>
                <button type="button" wire:click="setTab('achievements')" class="text-xs font-bold text-amber-400 hover:underline cursor-pointer">
                    Ver Conquistas, Medalhas e Notas ➔
                </button>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <button type="button" wire:click="setTab('achievements')" class="text-left rounded-2xl border border-slate-800 bg-slate-950/70 hover:border-amber-500/40 p-3.5 transition flex items-center justify-between gap-3 cursor-pointer">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Média Geral</span>
                        <span class="text-lg font-black text-white">{{ $overallGrade !== null ? number_format($overallGrade, 1, ',', '.') . '%' : '--' }}</span>
                    </div>
                    <span class="text-lg">📊</span>
                </button>

                <button type="button" wire:click="setTab('achievements')" class="text-left rounded-2xl border border-slate-800 bg-slate-950/70 hover:border-cyan-500/40 p-3.5 transition flex items-center justify-between gap-3 cursor-pointer">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Aulas Concluídas</span>
                        <span class="text-lg font-black text-white">{{ $completedLessonsCount }} <span class="text-xs font-normal text-slate-400">/ {{ $totalLessonsInFormation }} ({{ $overallPct }}%)</span></span>
                    </div>
                    <span class="text-lg">📚</span>
                </button>

                <button type="button" wire:click="setTab('achievements')" class="text-left rounded-2xl border border-slate-800 bg-slate-950/70 hover:border-indigo-500/40 p-3.5 transition flex items-center justify-between gap-3 cursor-pointer">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Tempo de Estudo</span>
                        <span class="text-lg font-black text-white">{{ $studyHours }}h {{ $studyMinutes }}m</span>
                    </div>
                    <span class="text-lg">⏱️</span>
                </button>

                <button type="button" wire:click="setTab('ranking')" class="text-left rounded-2xl border border-slate-800 bg-slate-950/70 hover:border-amber-500/40 p-3.5 transition flex items-center justify-between gap-3 cursor-pointer">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Ranking Geral</span>
                        <span class="text-lg font-black text-amber-400">#{{ $myRank['rank'] ?? 1 }}º Lugar</span>
                    </div>
                    <span class="text-lg">🏆</span>
                </button>
            </div>
        </div>
        @if($isEducatorMode)
            <div class="rounded-2xl bg-gradient-to-r from-indigo-950/80 via-purple-950/70 to-indigo-950/80 border border-indigo-500/40 p-4 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center text-xl shrink-0">
                        {{ Auth::user()?->isEducator() ? '👨‍🏫' : '💼' }}
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-indigo-200">
                            {{ Auth::user()?->isEducator() ? 'Painel do Educador / Gestão da Escola' : 'Ambiente da Equipe / Treinamento do Colaborador' }}
                        </h4>
                        <p class="text-xs text-indigo-300/80">
                            @if($isManagerPreview && $this->contract)
                                👀 Visualizando o portal como o aluno <strong>{{ $this->contract->student?->name }}</strong> (Matrícula {{ $this->contract->contract_number }}).
                            @elseif(Auth::user()?->isEducator())
                                Todos os cursos, módulos e lições estão liberados para você preparar aulas, sem necessidade de contrato de aluno ou horário fixo.
                            @else
                                Cursos e treinamentos liberados pela coordenação para seu desenvolvimento e qualificação profissional.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($isManagerPreview && $this->contract)
                        <button type="button"
                                wire:click="clearPreviewStudent"
                                class="rounded-xl border border-amber-500/30 bg-amber-500/20 px-3 py-1.5 text-xs font-semibold text-amber-200 hover:bg-amber-500/30 transition">
                            ✕ Sair da Pré-visualização
                        </button>
                    @elseif(Auth::user()?->isEducator() && $this->activeContractsForPreview->isNotEmpty())
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] text-indigo-300 hidden sm:inline">Simular aluno:</span>
                            <select wire:change="previewStudent($event.target.value)"
                                    class="rounded-xl border border-indigo-500/40 bg-slate-900/90 text-indigo-200 text-xs px-3 py-1.5 focus:ring-1 focus:ring-indigo-400 focus:outline-none">
                                <option value="">Selecione um aluno para testar...</option>
                                @foreach($this->activeContractsForPreview as $c)
                                    <option value="{{ $c->id }}">
                                        {{ $c->student?->name }} (Matrícula: {{ $c->contract_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/40">
                        ● {{ Auth::user()?->isEducator() ? 'Modo Educador' : 'Colaborador / Equipe' }}
                    </span>
                </div>
            </div>
        @endif

        @if($this->isEducatorTestingAsStudent)
            <div class="rounded-2xl bg-gradient-to-r from-amber-950/70 via-slate-900 to-indigo-950/70 border border-amber-500/30 p-4 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-xl shrink-0">
                        🎓
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-bold text-amber-200">Modo de Teste do Educador (Experiência de Aluno)</h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                Acesso Livre 24/7
                            </span>
                        </div>
                        <p class="text-xs text-slate-300/90 mt-0.5">
                            Você está testando o portal exatamente como um aluno: com bloqueios de aulas sequenciais, cronômetro de tela e desafios práticos, mas com <strong>todos os cursos da escola liberados</strong> e sem restrição de horário letivo.
                        </p>
                    </div>
                </div>
                @if($this->selectedCourse)
                    <button type="button"
                            wire:click="resetCourseTestProgress({{ $this->selectedCourse->id }})"
                            class="shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 text-amber-300 hover:bg-slate-700 hover:text-amber-200 border border-amber-500/30 transition cursor-pointer"
                            title="Reiniciar o histórico de teste das aulas deste curso">
                        <span>🔄</span>
                        <span>Reiniciar Teste Deste Curso</span>
                    </button>
                @endif
            </div>
        @endif

        @if($this->contract?->hasOverdueInstallments() && ! $isEducatorMode)
            <div class="rounded-2xl border border-amber-500/40 bg-gradient-to-r from-amber-500/15 via-slate-900 to-amber-500/10 p-5 text-amber-200 shadow-xl space-y-2">
                <div class="flex items-center gap-2 font-bold text-amber-300 text-sm">
                    <span class="text-xl">📋</span>
                    <span>Atualização de Matrícula Necessária</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Olá, <strong class="text-white">{{ $studentName ?? $this->studentName }}</strong>! Identificamos que sua matrícula precisa de uma rápida validação cadastral. Por gentileza, procure a secretaria ou recepção da sua escola para continuar suas aulas!
                </p>
            </div>
        @elseif(! $scheduleStatus['is_allowed'] && ! $isEducatorMode && ! $this->isEducatorTestingAsStudent)
            <div class="rounded-2xl border border-amber-500/40 bg-gradient-to-r from-amber-500/15 via-slate-900 to-amber-500/10 p-5 text-amber-200 shadow-xl space-y-2">
                <div class="flex items-center gap-2 font-bold text-amber-300 text-sm">
                    <span class="text-xl">
                        @if(($scheduleStatus['reason'] ?? '') === 'early_arrival') ⏰
                        @elseif(($scheduleStatus['reason'] ?? '') === 'after_hours') ⏳
                        @else 📅
                        @endif
                    </span>
                    <span>
                        @if(($scheduleStatus['reason'] ?? '') === 'early_arrival') Aguardando Horário de Aula
                        @elseif(($scheduleStatus['reason'] ?? '') === 'after_hours') Horário Letivo Finalizado
                        @else Fora do Horário Agendado
                        @endif
                    </span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    {{ $scheduleStatus['message'] }} O acesso a novas aulas fica liberado durante o seu turno regular de aula na escola.
                </p>
                <div class="text-[11px] text-slate-400">
                    Seus horários cadastrados: <strong class="text-amber-400">{{ $scheduleStatus['schedules_text'] }}</strong>
                </div>
            </div>
        @endif

        @if(! $this->contract && ! $isEducatorMode && ! $this->isEducatorTestingAsStudent)
            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-12 text-center shadow-xl">
                <div class="text-4xl mb-3">🎓</div>
                <h2 class="text-lg font-bold text-white mb-2">Nenhuma matrícula ativa localizada</h2>
                <p class="text-xs text-slate-400 max-w-md mx-auto mb-6">
                    Não encontramos um contrato de aluno ativo vinculado ao seu usuário. Por favor, consulte a secretaria da escola para regularizar sua matrícula.
                </p>
            </div>
        @elseif($activeTab === 'dashboard')
            {{-- ========================================================================= --}}
            {{-- ABA 1: PAINEL DO ALUNO (ESTATÍSTICAS, NOTAS, PROGRESSOS, ATIVIDADES E RANKING) --}}
            {{-- ========================================================================= --}}
            <div class="space-y-8">
                {{-- BANNER ESPECIAL DE ANIVERSÁRIO DO ALUNO --}}
                @if($isBirthdayToday)
                    <div class="relative overflow-hidden rounded-3xl border-2 border-pink-500/50 bg-gradient-to-r from-pink-950/70 via-purple-950/60 to-amber-950/50 p-6 sm:p-7 shadow-2xl backdrop-blur animate-in fade-in zoom-in-95 duration-300">
                        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-pink-500/20 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5 relative z-10">
                            <div class="flex items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-pink-500/20 border border-pink-500/40 text-3xl shadow-xl shadow-pink-500/20 shrink-0 animate-bounce">
                                    🎂
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-pink-500/20 border border-pink-500/40 px-3 py-0.5 text-[11px] font-black text-pink-300 uppercase tracking-widest">
                                            🎉 Feliz Aniversário!
                                        </span>
                                    </div>
                                    <h3 class="text-xl sm:text-2xl font-black text-white">
                                        Parabéns, {{ explode(' ', trim($this->contract?->student?->name ?? Auth::user()->name))[0] }}!
                                    </h3>
                                    <p class="text-xs sm:text-sm text-pink-200/90 mt-1 max-w-xl leading-relaxed">
                                        Toda a equipe da <strong>{{ $schoolName }}</strong> deseja a você um dia espetacular, repleto de realizações, saúde e muito sucesso nos seus estudos! 🎈✨
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                <span class="rounded-2xl bg-white/10 border border-white/10 px-4 py-3 text-xs font-bold text-white shadow-lg backdrop-blur flex items-center gap-2">
                                    <span>🥳</span>
                                    <span>Seu dia especial!</span>
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- AVISOS DA ESCOLA (ESTILO TOTEM DE PRESENÇA COM LETREIRO E MODAL) --}}
                @if($announcements->isNotEmpty())
                    <div x-data="{
                        current: 0,
                        announcements: @js($announcements->map(fn($a) => [
                            'id' => $a->id,
                            'title' => $a->title,
                            'body' => $a->body,
                            'image' => $a->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($a->image_path) : null,
                            'url' => $a->external_url,
                        ])),
                        modalOpen: false,
                        activeNotice: null,
                        next() { this.current = (this.current + 1) % this.announcements.length; },
                        prev() { this.current = (this.current - 1 + this.announcements.length) % this.announcements.length; },
                        openNotice(item) { this.activeNotice = item; this.modalOpen = true; }
                    }"
                    x-init="setInterval(() => { if (!modalOpen && announcements.length > 1) next(); }, 7000)"
                    class="relative"
                    >
                        <div
                            @click="openNotice(announcements[current])"
                            class="group relative flex cursor-pointer items-center justify-between gap-3 overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500/10 via-amber-600/10 to-amber-500/10 p-3.5 border border-amber-500/30 hover:border-amber-400 transition-all shadow-md hover:shadow-amber-500/10 backdrop-blur"
                        >
                            <div class="flex items-center gap-3 overflow-hidden min-w-0">
                                <template x-if="announcements[current].image">
                                    <img :src="announcements[current].image" class="h-9 w-9 rounded-xl object-cover shrink-0 border border-amber-500/30">
                                </template>
                                <template x-if="!announcements[current].image">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 text-lg border border-amber-500/30">
                                        📢
                                    </span>
                                </template>
                                <div class="flex items-center gap-2 overflow-hidden text-xs sm:text-sm">
                                    <span class="shrink-0 font-extrabold uppercase tracking-wider text-amber-400 text-[11px] bg-amber-500/20 px-2 py-0.5 rounded-md">
                                        Aviso da Escola:
                                    </span>
                                    <span class="truncate font-bold text-white" x-text="announcements[current].title"></span>
                                    <span class="hidden text-xs text-slate-400 sm:inline truncate" x-show="announcements[current].body" x-text="`— ${announcements[current].body}`"></span>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span class="hidden text-xs font-semibold text-amber-400 sm:inline group-hover:underline">
                                    Ler comunicado completo
                                </span>
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/20 text-amber-300 group-hover:bg-amber-500 group-hover:text-slate-950 transition">
                                    ➔
                                </span>
                            </div>
                        </div>

                        {{-- MODAL DE AVISO DA ESCOLA COMPLETO --}}
                        <div
                            x-cloak
                            x-show="modalOpen"
                            x-transition.opacity
                            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-md"
                        >
                            <div
                                @click.outside="modalOpen = false"
                                class="relative max-h-[90vh] w-full max-w-xl overflow-hidden rounded-3xl border border-slate-700 bg-slate-900 shadow-2xl flex flex-col"
                            >
                                <div class="overflow-y-auto p-6 space-y-4">
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">📢</span>
                                            <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Comunicado Oficial da Escola</span>
                                        </div>
                                        <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                                    </div>

                                    <template x-if="activeNotice && activeNotice.image">
                                        <img :src="activeNotice.image" class="w-full max-h-64 object-cover rounded-2xl border border-slate-800">
                                    </template>

                                    <div>
                                        <h2 class="text-xl font-black text-white" x-text="activeNotice ? activeNotice.title : ''"></h2>
                                        <p class="mt-3 text-sm text-slate-300 leading-relaxed whitespace-pre-line" x-text="activeNotice ? activeNotice.body : ''"></p>
                                    </div>

                                    <template x-if="activeNotice && activeNotice.url">
                                        <a :href="activeNotice.url" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-5 py-2.5 text-xs font-bold text-slate-950 transition shadow-lg shadow-amber-500/20">
                                            <span>Acessar Link do Comunicado</span>
                                            <span>➔</span>
                                        </a>
                                    </template>
                                </div>

                                <div class="flex items-center justify-between border-t border-slate-800 bg-slate-950/50 px-6 py-3">
                                    <button type="button" @click="prev()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition">
                                        ← Anterior
                                    </button>
                                    <span class="text-xs text-slate-500" x-text="`${current + 1} de ${announcements.length}`"></span>
                                    <button type="button" @click="next()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition">
                                        Próximo →
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- BANNER DOURADO DE FORMAÇÃO CONCLUÍDA & EMISSÃO DE CERTIFICADO --}}
                @if($isFormationCompleted)
                    <div class="rounded-3xl border-2 border-amber-500/40 bg-gradient-to-r from-amber-500/20 via-amber-950/40 to-slate-900 p-6 sm:p-8 shadow-2xl relative overflow-hidden backdrop-blur">
                        <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                            <div class="flex items-start sm:items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 font-black text-3xl shadow-xl shadow-amber-500/20 shrink-0">
                                    🏆
                                </div>
                                <div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/20 border border-amber-500/40 px-3 py-1 text-[11px] font-extrabold text-amber-300 uppercase tracking-widest mb-1.5">
                                        Formação Integral Concluída
                                    </span>
                                    <h2 class="text-xl sm:text-2xl font-black text-white">Parabéns pela sua Conquista!</h2>
                                    <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
                                        Você concluiu com maestria todas as aulas e etapas dos cursos da sua formação. Seu certificado oficial já está homologado.
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('student.certificate', ['contract' => $contract->id]) }}"
                               target="_blank"
                               class="inline-flex items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-400 px-6 py-4 text-sm font-black text-slate-950 shadow-xl shadow-amber-500/25 hover:from-amber-400 hover:to-amber-300 transition shrink-0 cursor-pointer">
                                <span>🎓</span>
                                <span>Emitir Certificado Oficial</span>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- HERO CARD: CONTINUAR DE ONDE PAROU / AVISOS DE ACESSO --}}
                @if(!$isFormationCompleted && !empty($currentStudyTarget['lesson']))
                    @php
                        $targetCourse = $currentStudyTarget['course'];
                        $targetLesson = $currentStudyTarget['lesson'];
                        $targetType = $currentStudyTarget['type'];
                        $hasOverdue = $this->contract?->hasOverdueInstallments();
                        $isScheduleAllowed = $scheduleStatus['is_allowed'] || $isEducatorMode || $this->isEducatorTestingAsStudent;
                    @endphp

                    @if($hasOverdue && ! $isEducatorMode)
                        {{-- BLOQUEIO ADMINISTRATIVO AMIGÁVEL --}}
                        <div class="rounded-3xl border border-amber-500/30 bg-gradient-to-r from-amber-500/10 via-slate-900 to-slate-900/90 p-6 sm:p-7 shadow-2xl relative overflow-hidden backdrop-blur">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
                                <div class="flex items-start sm:items-center gap-4">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/20 border border-amber-500/30 text-amber-400 text-2xl shadow-lg shrink-0">
                                        📋
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                Atendimento da Secretaria
                                            </span>
                                            <span class="text-xs text-slate-400">• {{ $targetCourse->name ?? 'Curso' }}</span>
                                        </div>
                                        <h3 class="text-lg sm:text-xl font-bold text-white">Atualização Cadastral Necessária</h3>
                                        <p class="text-xs text-slate-300 mt-1 max-w-xl leading-relaxed">
                                            Olá, <strong class="text-white">{{ $studentName ?? $this->studentName }}</strong>! Identificamos que sua matrícula precisa de uma rápida validação cadastral. Por gentileza, procure a secretaria ou recepção da escola para continuar suas aulas!
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center gap-3">
                                    @if(!empty($schoolPhone))
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $schoolPhone) }}?text={{ urlencode('Olá, gostaria de falar com a secretaria da escola sobre a minha matrícula.') }}"
                                           target="_blank"
                                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-3.5 text-xs font-bold shadow-lg shadow-emerald-600/20 transition cursor-pointer">
                                            <span>💬</span>
                                            <span>Falar com a Secretaria</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @elseif(! $isScheduleAllowed)
                        {{-- BLOQUEIO DE HORÁRIO (ANTES DO HORÁRIO, APÓS O TÉRMINO OU FORA DO DIA) --}}
                        <div class="rounded-3xl border border-indigo-500/30 bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 p-6 sm:p-7 shadow-2xl relative overflow-hidden backdrop-blur">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
                                <div class="flex items-start sm:items-center gap-4">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-2xl shadow-lg shrink-0">
                                        @if(($scheduleStatus['reason'] ?? '') === 'early_arrival') ⏰
                                        @elseif(($scheduleStatus['reason'] ?? '') === 'after_hours') ⏳
                                        @else 📅
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ ($scheduleStatus['reason'] ?? '') === 'early_arrival' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                                                @if(($scheduleStatus['reason'] ?? '') === 'early_arrival')
                                                    Aguardando Início da Aula
                                                @elseif(($scheduleStatus['reason'] ?? '') === 'after_hours')
                                                    Horário Letivo Finalizado
                                                @else
                                                    Fora do Horário Agendado
                                                @endif
                                            </span>
                                            <span class="text-xs text-slate-400">• {{ $targetCourse->name ?? 'Curso' }}</span>
                                        </div>
                                        <h3 class="text-lg sm:text-xl font-bold text-white">{{ $targetLesson->title }}</h3>
                                        <p class="text-xs text-slate-300 mt-1 max-w-xl leading-relaxed">
                                            {{ $scheduleStatus['message'] }}
                                        </p>
                                        <div class="text-[11px] text-slate-400 mt-2">
                                            Seus horários regulares: <strong class="text-amber-400">{{ $scheduleStatus['schedules_text'] }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center gap-3">
                                    <button type="button"
                                            disabled
                                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-800/80 border border-slate-700/80 px-6 py-3.5 text-xs font-bold text-slate-500 cursor-not-allowed">
                                        <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span>Aula Bloqueada Fora do Horário</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- EM SESSÃO REGULAR / HORÁRIO PERMITIDO --}}
                        <div class="rounded-3xl border border-indigo-500/30 bg-gradient-to-r from-indigo-950/60 via-slate-900 to-slate-900/90 p-6 sm:p-7 shadow-2xl relative overflow-hidden backdrop-blur">
                            <div class="absolute -right-12 -top-12 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
                                <div class="flex items-start sm:items-center gap-4">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600/30 border border-indigo-500/40 text-indigo-300 text-2xl shadow-lg shrink-0">
                                        {{ $targetType === 'resuming' ? '▶' : '🚀' }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $targetType === 'resuming' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">
                                                {{ $targetType === 'resuming' ? 'Em Andamento' : 'Próxima Aula Liberada' }}
                                            </span>
                                            <span class="text-xs text-slate-400">• {{ $targetCourse->name ?? 'Curso' }}</span>
                                        </div>
                                        <h3 class="text-lg sm:text-xl font-bold text-white">{{ $targetLesson->title }}</h3>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            Duração estimada: {{ $targetLesson->estimated_duration_minutes ?? 60 }} min • Módulo: {{ $targetLesson->module->title ?? 'Módulo' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row items-center gap-3">
                                    @if(! $this->hasCompletedTypingWarmupToday)
                                        <a href="{{ route('student.typing', ['lesson_id' => $targetLesson->id, 'contract_id' => $this->contract?->id, 'mandatory' => 1]) }}"
                                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-slate-950 px-6 py-3.5 text-sm font-black shadow-xl shadow-emerald-500/25 transition cursor-pointer">
                                            <span>⌨️</span>
                                            <span>1º Passo: Aquecimento de Digitação (15 min)</span>
                                            <span>➔</span>
                                        </a>

                                        <a href="{{ route('lms.player', ['lesson' => $targetLesson->id, 'contract_id' => $this->contract?->id]) }}"
                                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-3.5 text-xs font-semibold border border-slate-700 transition cursor-pointer"
                                           title="Entrar direto na aula">
                                            <span>Entrar na Aula</span>
                                        </a>
                                    @else
                                        <a href="{{ route('student.typing', ['contract_id' => $this->contract?->id]) }}"
                                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-emerald-500/30 px-4 py-3.5 text-xs font-bold transition cursor-pointer"
                                           title="Praticar digitação livremente e ver rankings">
                                            <span>⌨️</span>
                                            <span>Praticar Mais Digitação</span>
                                        </a>

                                        <a href="{{ route('lms.player', ['lesson' => $targetLesson->id, 'contract_id' => $this->contract?->id]) }}"
                                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-xl shadow-indigo-600/30 hover:bg-indigo-500 transition cursor-pointer">
                                            <span>{{ $targetType === 'resuming' ? 'Continuar Aula' : 'Iniciar Aula' }}</span>
                                            <span>➔</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- ========================================================================= --}}
                {{-- SEÇÃO PRINCIPAL NO TOPO: MINHAS FORMAÇÕES & SEUS CURSOS                   --}}
                {{-- ========================================================================= --}}
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-white">Seus Cursos na Formação</h2>
                            <p class="text-xs text-slate-400">Os cursos são liberados sequencialmente conforme você conclui todas as aulas do curso anterior.</p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button type="button" wire:click="setTab('courses')" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 px-3.5 py-1.5 text-xs font-bold text-amber-300 hover:bg-amber-500 hover:text-slate-950 transition cursor-pointer">
                                <span>📖</span>
                                <span>Ver Roteiro Detalhado das Aulas ➔</span>
                            </button>
                        </div>
                    </div>

                    {{-- SELETOR DE FORMAÇÕES (MINHAS FORMAÇÕES) --}}
                    @if($enrolledFormations->isNotEmpty())
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Minhas Formações</span>
                                @if($contract && $contract->isFlex())
                                    <span class="inline-flex items-center gap-1 rounded-full bg-cyan-500/15 border border-cyan-500/30 px-2.5 py-0.5 text-[10px] font-bold text-cyan-300">
                                        ⚡ Aluno Flex
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800">
                                <button type="button"
                                        wire:click="selectFormation(null)"
                                        class="rounded-xl px-3.5 py-2 text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $selectedFormationId === null ? 'bg-school-brand shadow-school-brand' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                    <span>📚</span>
                                    <span>Todas as Formações</span>
                                </button>

                                @foreach($enrolledFormations as $f)
                                    <button type="button"
                                            wire:click="selectFormation({{ $f->id }})"
                                            class="rounded-xl px-3.5 py-2 text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $selectedFormationId === $f->id ? 'bg-school-brand shadow-school-brand' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                                        <span>🎓</span>
                                        <span>{{ $f->name }}</span>
                                        <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $selectedFormationId === $f->id ? 'bg-slate-950/30 text-slate-950 font-black' : 'bg-slate-800 text-slate-400' }}">
                                            {{ $f->courses_count }}
                                        </span>
                                        @if($f->is_scheduled_today)
                                            <span class="inline-flex items-center gap-0.5 text-[10px] font-extrabold text-emerald-400" title="Dia agendado para esta formação">
                                                ● Hoje
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- BARRA DE BUSCA DE CURSOS --}}
                    <div class="relative">
                        <input type="text"
                                wire:model.live.debounce.300ms="courseSearch"
                                placeholder="🔍 Buscar curso pelo nome (ex: Informática, Excel, Photoshop, Hardware)..."
                                class="w-full rounded-2xl border border-slate-800 bg-slate-900/90 px-4 py-3 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 shadow-inner">
                        @if(filled($courseSearch))
                            <button type="button" wire:click="$set('courseSearch', '')" class="absolute right-3 top-3 text-xs text-slate-400 hover:text-white">✕</button>
                        @endif
                    </div>

                    @if($this->courses->isEmpty())
                        <div class="rounded-3xl border border-slate-800 bg-slate-900/40 p-8 text-center text-xs text-slate-400">
                            Nenhum curso encontrado com os filtros selecionados.
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($this->courses as $index => $course)
                            @php
                                $accessInfo = $this->getCourseAccess($course, $progressionService);
                                $isCompleted = $accessInfo['status'] === 'completed';
                                $isLocked = $accessInfo['status'] === 'locked';
                                $progressPercentage = $accessInfo['progress_percentage'] ?? 0;
                                $totalCourseLessons = $course->lmsModules->flatMap->lessons->where('is_active', true)->count();
                                $formationName = $this->getCourseFormationName($course);
                                $finalExamAttempt = $course->isAurosoftLms() ? $this->getCourseFinalExamStatus($course) : null;
                            @endphp

                            <div class="rounded-3xl border p-5 flex flex-col justify-between transition {{ $isLocked ? 'border-slate-800 bg-slate-900/40 text-slate-400 opacity-80' : ($isCompleted ? 'border-emerald-500/30 bg-slate-900/80' : 'border-amber-500/30 bg-slate-900/90 shadow-lg shadow-amber-500/5') }}">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $accessInfo['badge'] }}">
                                            @if($isLocked)
                                                🔒 {{ $accessInfo['label'] }}
                                            @elseif($isCompleted)
                                                ✓ {{ $accessInfo['label'] }}
                                            @else
                                                ⚡ {{ $accessInfo['label'] }}
                                            @endif
                                        </span>
                                        <span class="text-[11px] text-slate-500">
                                            Ordem #{{ $index + 1 }}
                                        </span>
                                    </div>

                                    @if($formationName)
                                        <div class="mb-1.5 flex items-center gap-1.5 flex-wrap">
                                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-300 border border-amber-500/20">
                                                🏷️ {{ $formationName }}
                                            </span>
                                            @if(!empty($course->is_contract_unlocked))
                                                <span class="inline-flex items-center gap-0.5 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold text-emerald-400 border border-emerald-500/20">
                                                    🔓 Liberado
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    <h3 class="text-base font-bold text-white mb-1">
                                        {{ $course->name }}
                                    </h3>

                                    <p class="text-xs text-slate-400">
                                        @if($course->isAurosoftLms())
                                            {{ $course->lmsModules->count() }} módulos • {{ $totalCourseLessons }} aulas calibradas
                                        @else
                                            Curso Prático em Laboratório Interativo
                                        @endif
                                    </p>

                                    {{-- BARRA DE PROGRESSO DO CURSO --}}
                                    <div class="mt-4 space-y-1.5">
                                        <div class="flex justify-between text-[11px]">
                                            <span class="text-slate-400">Progresso do curso</span>
                                            <span class="font-bold text-white">{{ $progressPercentage }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full transition-all duration-500 {{ $isCompleted ? 'bg-emerald-500' : 'bg-gradient-to-r from-amber-500 to-amber-300' }}" style="width: {{ $progressPercentage }}%"></div>
                                        </div>
                                    </div>

                                    @if($isLocked)
                                        <p class="text-[11px] text-slate-500 mt-3 italic leading-relaxed">
                                            🔒 {{ $accessInfo['message'] }}
                                        </p>
                                    @endif
                                </div>

                                <div class="mt-6 pt-3 border-t border-slate-800/80 space-y-2">
                                    @if(! $isLocked)
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    wire:click="selectCourseAndOpen({{ $course->id }})"
                                                    class="flex-1 inline-flex items-center justify-center rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-md shadow-amber-500/20 cursor-pointer">
                                                {{ $isCompleted ? 'Revisar Aulas' : 'Acessar Aulas' }} ➔
                                            </button>
                                            <a href="{{ route('student.portal', ['tab' => 'courses', 'course_id' => $course->id]) }}"
                                               target="_blank"
                                               class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800/90 hover:bg-slate-700 p-2 text-amber-400 hover:text-white transition shrink-0"
                                               title="Abrir este curso em uma nova aba do navegador">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>

                                        @php
                                            $courseLink = $this->contract ? \App\Models\ContractCourse::whereIn('contract_item_id', $this->contract->items()->select('id'))->where('course_id', $course->id)->first() : null;
                                        @endphp

                                        @if($courseLink)
                                            <div class="grid grid-cols-2 gap-2 pt-1">
                                                <a href="{{ route('contracts.ouro-moderno-transcript.course', ['contract' => $this->contract->id, 'contractCourse' => $courseLink->id]) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/80 px-2.5 py-1.5 text-[11px] font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition"
                                                   title="Ver boletim e frequência individual deste curso">
                                                    <span>📜</span>
                                                    <span>Boletim</span>
                                                </a>

                                                @if($isCompleted)
                                                    <a href="{{ route('contracts.certificate.course', ['contract' => $this->contract->id, 'contractCourse' => $courseLink->id]) }}"
                                                       target="_blank"
                                                       class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/15 px-2.5 py-1.5 text-[11px] font-bold text-amber-300 hover:bg-amber-500 hover:text-slate-950 transition shadow-sm shadow-amber-500/10"
                                                       title="Emitir Certificado Oficial de Conclusão deste curso">
                                                        <span>🎓</span>
                                                        <span>Certificado</span>
                                                    </a>
                                                @else
                                                    <span class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950/40 px-2.5 py-1.5 text-[11px] font-medium text-slate-500"
                                                          title="Conclua todas as lições para liberar o certificado deste curso">
                                                        <span>🔒</span>
                                                        <span>Certificado</span>
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        @if($course->isAurosoftLms() && ($isCompleted || $isEducatorMode))
                                            <button type="button"
                                                    wire:click="openCourseFinalExam({{ $course->id }})"
                                                    class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border px-4 py-2 text-xs font-bold transition cursor-pointer {{ $finalExamAttempt && $finalExamAttempt->passed ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25' : 'border-indigo-500/40 bg-indigo-500/15 text-indigo-300 hover:bg-indigo-500/25' }}">
                                                <span>🎓</span>
                                                <span>
                                                    {{ $finalExamAttempt && $finalExamAttempt->passed ? 'Certificação Aprovada (' . number_format((float)$finalExamAttempt->score, 0) . '%)' : 'Prova Final de Certificação' }}
                                                </span>
                                            </button>
                                        @endif
                                    @else
                                        <button type="button"
                                                disabled
                                                class="w-full inline-flex items-center justify-center rounded-xl bg-slate-800/60 px-4 py-2 text-xs font-semibold text-slate-500 cursor-not-allowed">
                                            Bloqueado (Cumpra pré-requisito)
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        @elseif($activeTab === 'achievements')
            {{-- ========================================================================= --}}
            {{-- ABA 3: CONQUISTAS, INSÍGNIAS, DESEMPENHO & HISTÓRICO DE ATIVIDADES        --}}
            {{-- ========================================================================= --}}
            @php
                $overallPct = $totalLessonsInFormation > 0 ? min(100, round(($completedLessonsCount / $totalLessonsInFormation) * 100)) : 0;
                $studyHours = floor($totalStudyTimeSeconds / 3600);
                $studyMinutes = floor(($totalStudyTimeSeconds % 3600) / 60);
                $myRank = $leaderboard['current_student_rank'] ?? null;
                $badges = $this->achievementsBadges;
                $unlockedBadgesCount = collect($badges)->where('unlocked', true)->count();
            @endphp
            <div class="space-y-6">
                {{-- CARDS DE INDICADORES / METRICAS DO ALUNO --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Média Geral</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                📊
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-3xl font-black text-white">
                                {{ $overallGrade !== null ? number_format($overallGrade, 1, ',', '.') . '%' : '--' }}
                            </div>
                            <span class="inline-flex items-center gap-1 mt-1 text-[11px] font-semibold {{ $overallGrade !== null && $overallGrade >= 80 ? 'text-emerald-400' : 'text-amber-400' }}">
                                {{ $overallGrade !== null ? ($overallGrade >= 80 ? '● Desempenho excelente' : '● Em evolução') : 'Nenhuma aula avaliada' }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Aulas Concluídas</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                📚
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-3xl font-black text-white">
                                {{ $completedLessonsCount }} <span class="text-sm font-semibold text-slate-400">/ {{ $totalLessonsInFormation }}</span>
                            </div>
                            <div class="mt-2 w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-cyan-500 to-amber-400 h-2 rounded-full transition-all duration-500" style="width: {{ $overallPct }}%"></div>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block">{{ $overallPct }}% da formação concluída</span>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Tempo de Estudo</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                ⏱️
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-3xl font-black text-white">
                                {{ $studyHours }}h {{ $studyMinutes }}m
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block">Dedicação prática acumulada</span>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Ranking Geral</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                🏆
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-3xl font-black text-amber-400">
                                #{{ $myRank['rank'] ?? 1 }}º Lugar
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block">
                                {{ number_format($myRank['total_points'] ?? 0, 0, ',', '.') }} pontos conquistados
                            </span>
                        </div>
                    </div>
                </div>

                {{-- GALERIA DE CONQUISTAS (INSÍGNIAS / BADGES DESBLOQUEÁVEIS) --}}
                <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6 shadow-xl space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 border border-amber-500/30 text-lg">
                                🏅
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-white">Galeria de Conquistas & Insígnias</h2>
                                <p class="text-xs text-slate-400">Desbloqueie medalhas conforme avança nas aulas, digitação e exames finais.</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-amber-500/15 border border-amber-500/30 px-3 py-1 text-xs font-extrabold text-amber-300">
                            🏆 {{ $unlockedBadgesCount }} / {{ count($badges) }} Desbloqueadas
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                        @foreach($badges as $badge)
                            <div class="rounded-2xl border p-3.5 text-center flex flex-col items-center justify-between gap-2 transition {{ $badge['unlocked'] ? 'border-amber-500/40 bg-gradient-to-b from-amber-500/15 to-slate-950/90 shadow-md shadow-amber-500/5' : 'border-slate-800/80 bg-slate-950/40 opacity-55 grayscale' }}">
                                <div class="space-y-1.5 flex flex-col items-center">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-2xl {{ $badge['unlocked'] ? 'bg-amber-500/20 border border-amber-400/40 shadow-inner' : 'bg-slate-900 border border-slate-800' }}">
                                        {{ $badge['icon'] }}
                                    </div>
                                    <h3 class="text-xs font-extrabold {{ $badge['unlocked'] ? 'text-white' : 'text-slate-400' }} leading-tight">
                                        {{ $badge['title'] }}
                                    </h3>
                                    <p class="text-[10px] text-slate-400 leading-snug line-clamp-2">
                                        {{ $badge['description'] }}
                                    </p>
                                </div>
                                <span class="w-full rounded-lg px-2 py-0.5 text-[10px] font-bold {{ $badge['unlocked'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-900 text-slate-500 border border-slate-800' }}">
                                    {{ $badge['unlocked'] ? '✓ Desbloqueada' : $badge['progress_text'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- HISTÓRICO DE ATIVIDADES REALIZADAS --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-white">Histórico de Atividades Realizadas</h2>
                            <p class="text-xs text-slate-400">Todas as aulas, avaliações e tempos dedicados registrados em sua matrícula.</p>
                        </div>
                        <span class="rounded-full bg-slate-900 border border-slate-800 px-3 py-1 text-xs font-bold text-slate-300">
                            {{ $activities->count() }} registradas
                        </span>
                    </div>

                    <div class="rounded-3xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-xl">
                        @if($activities->isEmpty())
                            <div class="p-8 text-center text-xs text-slate-400">
                                <p class="text-3xl mb-2">📝</p>
                                <p class="font-bold text-white mb-1">Nenhuma atividade concluída ainda</p>
                                <p class="max-w-sm mx-auto">Suas notas, exercícios e avaliações concluídas aparecerão detalhadas aqui conforme você avança nos cursos.</p>
                                <button type="button" wire:click="setTab('dashboard')" class="mt-4 inline-flex items-center rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition cursor-pointer">
                                    Ir para Formações & Cursos
                                </button>
                            </div>
                        @else
                            <div class="divide-y divide-slate-800/80">
                                @foreach($activities as $activity)
                                    <div class="p-4 sm:p-5 flex items-center justify-between gap-4 hover:bg-slate-800/40 transition">
                                        <div class="space-y-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-semibold text-amber-400 truncate">
                                                    {{ $activity->lesson?->module?->course?->name ?? 'Curso' }}
                                                </span>
                                                <span class="text-slate-600">•</span>
                                                <span class="text-[11px] text-slate-400">
                                                    {{ $activity->completed_at ? $activity->completed_at->format('d/m/Y H:i') : ($activity->updated_at ? $activity->updated_at->format('d/m/Y H:i') : '') }}
                                                </span>
                                            </div>
                                            <h4 class="text-sm font-bold text-white truncate">
                                                {{ $activity->lesson?->title ?? 'Aula LMS' }}
                                            </h4>
                                            <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                                <span>Tempo dedicado: <strong>{{ floor($activity->time_spent_seconds / 60) }} min</strong></span>
                                            </div>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <div class="text-base font-black {{ (float)$activity->score >= 80 ? 'text-emerald-400' : 'text-amber-400' }}">
                                                {{ number_format((float) $activity->score, 0) }}%
                                            </div>
                                            <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-bold {{ (float)$activity->score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-300 border border-amber-500/20' }}">
                                                {{ (float)$activity->score >= 80 ? 'Maestria' : 'Aprovado' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        @elseif($activeTab === 'ranking')
            {{-- ========================================================================= --}}
            {{-- ABA 4: QUADRO DE LÍDERES & RANKING DA ESCOLA                              --}}
            {{-- ========================================================================= --}}
            <div class="max-w-3xl mx-auto space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black text-white flex items-center gap-2">
                            <span>🏆</span>
                            <span>Quadro de Líderes — {{ $schoolName }}</span>
                        </h2>
                        <p class="text-xs text-slate-400">Pontuação acumulada pelas aulas concluídas e aproveitamento nas avaliações.</p>
                    </div>
                    <a href="{{ route('student.typing', ['contract_id' => $this->contract?->id]) }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 px-4 py-2 text-xs font-bold text-emerald-300 transition shrink-0">
                        <span>⌨️</span>
                        <span>Ver Ranking de Digitação</span>
                    </a>
                </div>

                <div class="rounded-3xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-xl p-6 space-y-5">
                    {{-- POSIÇÃO DO ALUNO LOGADO DESTACADA --}}
                    <div class="rounded-2xl border border-amber-500/40 bg-gradient-to-r from-amber-500/15 via-slate-900 to-amber-500/10 p-5 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500 font-black text-slate-950 text-lg shadow-lg">
                                #{{ $leaderboard['current_student_rank']['rank'] ?? 1 }}
                            </div>
                            <div>
                                <span class="text-sm font-bold text-white flex items-center gap-1.5">
                                    {{ $leaderboard['current_student_rank']['student_name'] ?? 'Você' }}
                                    <span class="rounded bg-amber-500/20 px-2 py-0.5 text-[10px] text-amber-300 font-extrabold">(Sua Posição)</span>
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ $leaderboard['current_student_rank']['completed_count'] ?? 0 }} aulas concluídas
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-400 block">Pontuação Total</span>
                            <span class="font-black text-amber-400 text-lg">
                                {{ number_format($leaderboard['current_student_rank']['total_points'] ?? 0, 0, ',', '.') }} pts
                            </span>
                        </div>
                    </div>

                    {{-- LISTA DO TOP RANKING --}}
                    <div class="space-y-2.5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-1">Top Alunos da Escola</h3>
                        @forelse($leaderboard['top'] as $topStudent)
                            <div class="flex items-center justify-between p-3.5 rounded-2xl transition {{ $topStudent['is_current'] ? 'bg-amber-500/10 border border-amber-500/30' : 'bg-slate-950/60 border border-slate-800/80' }}">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl font-bold text-xs {{ $topStudent['rank'] === 1 ? 'bg-amber-400 text-slate-950' : ($topStudent['rank'] === 2 ? 'bg-slate-300 text-slate-950' : ($topStudent['rank'] === 3 ? 'bg-amber-700 text-white' : 'bg-slate-800 text-slate-400')) }}">
                                        {{ $topStudent['rank'] }}º
                                    </span>
                                    <div class="truncate">
                                        <span class="text-xs font-bold text-white truncate block">
                                            {{ $topStudent['student_name'] }}
                                            @if($topStudent['is_current'])
                                                <span class="text-amber-400 font-semibold">(Você)</span>
                                            @endif
                                        </span>
                                        <span class="text-[11px] text-slate-400 block">
                                            {{ $topStudent['completed_count'] }} aulas • Média: {{ $topStudent['avg_score'] }}%
                                        </span>
                                    </div>
                                </div>
                                <span class="text-xs font-mono font-bold text-amber-400 shrink-0">
                                    {{ number_format($topStudent['total_points'], 0, ',', '.') }} pts
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 text-center py-6">Ranking em construção. Conclua lições para pontuar!</p>
                        @endforelse
                    </div>
                </div>
            </div>

        @elseif($activeTab === 'catalog')
            {{-- ========================================================================= --}}
            {{-- ABA 5: CATÁLOGO DE FORMAÇÕES PROFISSIONAIS DA ESCOLA                      --}}
            {{-- ========================================================================= --}}
            @if($showcaseFormations->isNotEmpty())
                <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 sm:p-7 shadow-xl space-y-5"
                     x-data="{
                         selectedModalFormation: null,
                         openModal(formation) {
                             this.selectedModalFormation = formation;
                         },
                         closeModal() {
                             this.selectedModalFormation = null;
                         }
                     }">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🚀</span>
                                <h3 class="text-base font-bold text-white">Conheça as Formações Profissionais da {{ $schoolName }}</h3>
                                <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-bold text-amber-400 border border-amber-500/20">
                                    Qualificação Profissional
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">
                                Cursos estruturados em trilhas completas de capacitação para o mercado de trabalho com certificado oficial.
                            </p>
                        </div>
                        @php
                            $whatsappDigits = preg_replace('/\D/', '', $schoolPhone);
                        @endphp
                        @if($whatsappDigits)
                            <a href="https://wa.me/55{{ $whatsappDigits }}?text={{ urlencode('Olá! Sou aluno da ' . $schoolName . ' e tenho interesse em conhecer outras formações disponíveis.') }}"
                               target="_blank"
                               class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 px-4 py-2.5 text-xs font-bold transition shadow-sm shrink-0">
                                <span>💬</span>
                                <span>Consultar Secretaria no WhatsApp</span>
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($showcaseFormations as $f)
                            @php
                                $coursesJson = $f->courses->map(function ($c) {
                                    return [
                                        'id' => $c->id,
                                        'name' => $c->name,
                                        'workload' => $c->workload ? $c->workload . 'h' : 'Carga flexível',
                                        'lessons_count' => $c->lmsModules->flatMap->lessons->where('is_active', true)->count(),
                                        'modules_count' => $c->lmsModules->count(),
                                        'description' => $c->description ? Str::limit(strip_tags($c->description), 120) : null,
                                        'is_lms' => $c->isAurosoftLms(),
                                    ];
                                })->values();

                                $formationData = [
                                    'id' => $f->id,
                                    'name' => $f->name,
                                    'description' => $f->description,
                                    'total_workload' => $f->total_workload ? $f->total_workload . ' horas' : 'Carga flexível',
                                    'courses_count' => $f->total_courses_count,
                                    'lessons_count' => $f->total_lessons_count,
                                    'courses' => $coursesJson,
                                ];
                            @endphp

                            <div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-5 transition hover:border-slate-700 flex flex-col justify-between gap-4 group">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-lg border border-amber-500/20">
                                            ⏱️ {{ $f->total_workload ? $f->total_workload . 'h' : 'Formação' }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 font-semibold">
                                            📚 {{ $f->total_courses_count }} cursos
                                        </span>
                                    </div>

                                    <div>
                                        <h4 class="text-base font-bold text-white group-hover:text-amber-400 transition leading-snug">
                                            {{ $f->name }}
                                        </h4>
                                        @if($f->description)
                                            <p class="text-xs text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                                                {{ Str::limit(strip_tags($f->description), 110) }}
                                            </p>
                                        @else
                                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                                Trilha profissionalizante completa com metodologia prática e certificado homologado.
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 text-[11px] text-slate-500 pt-1">
                                        <span>🎯</span>
                                        <span>{{ $f->total_lessons_count > 0 ? $f->total_lessons_count . ' aulas calibradas' : 'Aulas práticas no Totem' }}</span>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-900 flex items-center justify-between gap-2">
                                    <button type="button"
                                            @click='openModal(@json($formationData))'
                                            class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700/80 px-3.5 py-2 text-xs font-bold text-amber-400 hover:text-amber-300 transition cursor-pointer">
                                        <span>Ver Cursos da Formação</span>
                                        <span>➔</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- MODAL DE DETALHES DOS CURSOS DA FORMAÇÃO --}}
                    <div x-show="selectedModalFormation"
                         x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
                         @keydown.escape.window="closeModal()">
                        <div class="relative w-full max-w-2xl rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-7 shadow-2xl space-y-5"
                             @click.away="closeModal()">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-800 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 text-2xl shrink-0">
                                        🎓
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                                            Grade Curricular da Formação
                                        </span>
                                        <h3 class="text-lg font-black text-white mt-1" x-text="selectedModalFormation?.name"></h3>
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="selectedModalFormation?.description || 'Trilha completa de qualificação profissional.'"></p>
                                    </div>
                                </div>
                                <button type="button" @click="closeModal()" class="rounded-xl p-2 text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                                    ✕
                                </button>
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="rounded-2xl bg-slate-950/60 border border-slate-800/80 p-3">
                                    <span class="text-[10px] text-slate-400 block uppercase font-semibold">Carga Horária</span>
                                    <span class="text-sm font-bold text-amber-400" x-text="selectedModalFormation?.total_workload"></span>
                                </div>
                                <div class="rounded-2xl bg-slate-950/60 border border-slate-800/80 p-3">
                                    <span class="text-[10px] text-slate-400 block uppercase font-semibold">Total de Cursos</span>
                                    <span class="text-sm font-bold text-white" x-text="(selectedModalFormation?.courses_count || 0) + ' Cursos'"></span>
                                </div>
                                <div class="rounded-2xl bg-slate-950/60 border border-slate-800/80 p-3">
                                    <span class="text-[10px] text-slate-400 block uppercase font-semibold">Total de Aulas</span>
                                    <span class="text-sm font-bold text-cyan-400" x-text="(selectedModalFormation?.lessons_count || 0) + ' Aulas'"></span>
                                </div>
                            </div>

                            <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Cursos e módulos que compõem esta formação:</h4>
                                <template x-for="(course, idx) in (selectedModalFormation?.courses || [])" :key="course.id">
                                    <div class="rounded-2xl border border-slate-800/80 bg-slate-950/50 p-4 flex items-center justify-between gap-3 hover:border-slate-700 transition">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-amber-400 font-extrabold text-xs" x-text="'#' + (idx + 1)"></span>
                                            <div class="truncate">
                                                <h5 class="text-xs font-bold text-white truncate" x-text="course.name"></h5>
                                                <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                                    <span x-text="'⏱️ ' + course.workload"></span>
                                                    <span>•</span>
                                                    <span x-text="course.lessons_count > 0 ? (course.lessons_count + ' aulas') : 'Aulas práticas'"></span>
                                                    <span class="rounded px-1.5 py-0.2 text-[9px] font-semibold"
                                                          :class="course.is_lms ? 'bg-indigo-500/20 text-indigo-300' : 'bg-cyan-500/20 text-cyan-300'"
                                                          x-text="course.is_lms ? 'Plataforma Interativa' : 'Laboratório Interativo'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="pt-4 border-t border-slate-800 flex items-center justify-between gap-3">
                                <button type="button" @click="closeModal()" class="rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-300 transition">
                                    Fechar
                                </button>
                                @if($whatsappDigits)
                                    <a :href="'https://wa.me/55{{ $whatsappDigits }}?text=' + encodeURIComponent('Olá! Gostaria de mais informações para me matricular na ' + (selectedModalFormation?.name || 'formação') + '.')"
                                       target="_blank"
                                       class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition">
                                        <span>💬</span>
                                        <span>Matricular-se nesta Formação</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        @elseif($activeTab === 'documents')
            {{-- ========================================================================= --}}
            {{-- ABA: CENTRAL DE DOCUMENTOS & FINANCEIRO DO ALUNO                           --}}
            {{-- ========================================================================= --}}
            <div class="space-y-6">
                {{-- CABEÇALHO DA ABA --}}
                <div class="rounded-3xl border border-slate-800/80 bg-slate-900/60 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-2xl shrink-0">
                            📂
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Central de Documentos & Financeiro</h2>
                            <p class="text-xs text-slate-400">
                                Emita seus documentos oficiais, boletins pedagógicos, certificados e acompanhe o pagamento de parcelas.
                            </p>
                        </div>
                    </div>
                    @if($this->contract)
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                Matrícula: {{ $this->contract->contract_number ?? '—' }}
                            </span>
                        </div>
                    @endif
                </div>

                @if(! $this->contract)
                    <div class="rounded-3xl border border-dashed border-slate-800 p-12 text-center bg-slate-900/40 space-y-3">
                        <div class="text-4xl">📄</div>
                        <h3 class="text-base font-bold text-white">Nenhum contrato ativo vinculado</h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">
                            Os documentos oficiais e carnê de pagamento ficam disponíveis assim que sua matrícula for formalizada pela secretaria da escola.
                        </p>
                    </div>
                @else
                    {{-- GRID DE DOCUMENTOS OFICIAIS --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- CARD 1: BOLETIM ESCOLAR --}}
                        <div class="rounded-3xl border border-slate-800/90 bg-slate-900/70 p-5 flex flex-col justify-between space-y-4 hover:border-amber-500/40 transition">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="h-11 w-11 rounded-2xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-xl text-indigo-300">
                                        📜
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                        Histórico Oficial
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">Boletim Escolar / Histórico</h3>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Frequência auditada, notas de avaliação, tempo de estudo e progresso de cada disciplina.
                                    </p>
                                </div>
                                <div class="rounded-2xl bg-slate-950/60 p-3 border border-slate-800/80 text-[11px] space-y-1">
                                    <div class="flex justify-between text-slate-400">
                                        <span>Média de Notas:</span>
                                        <strong class="text-white">{{ $overallGrade !== null ? number_format($overallGrade, 1, ',', '.') . '%' : 'Em andamento' }}</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-400">
                                        <span>Aulas Realizadas:</span>
                                        <strong class="text-white">{{ $completedLessonsCount }} / {{ $totalLessonsInFormation }}</strong>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('student.boletim', ['contract' => $this->contract->id]) }}"
                               target="_blank"
                               class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-600/20 transition cursor-pointer">
                                <span>Visualizar Boletim</span>
                                <span>➔</span>
                            </a>
                        </div>

                        {{-- CARD 2: DECLARAÇÃO DE MATRÍCULA --}}
                        <div class="rounded-3xl border border-slate-800/90 bg-slate-900/70 p-5 flex flex-col justify-between space-y-4 hover:border-cyan-500/40 transition">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="h-11 w-11 rounded-2xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-xl text-cyan-300">
                                        🎓
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                                        Documento Timbrado
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">Declaração de Matrícula</h3>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Comprovante de vínculo estudantil com carimbo da escola, CNPJ, carga horária e grade de horários.
                                    </p>
                                </div>
                                <div class="rounded-2xl bg-slate-950/60 p-3 border border-slate-800/80 text-[11px] space-y-1">
                                    <div class="flex justify-between text-slate-400">
                                        <span>Status do Contrato:</span>
                                        <strong class="text-emerald-400 uppercase font-bold">{{ $this->contract->status }}</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-400">
                                        <span>Validade:</span>
                                        <strong class="text-white">Ano Letivo Vigente</strong>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('contracts.declaracao-matricula', ['contract' => $this->contract->id]) }}"
                               target="_blank"
                               class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-cyan-600/20 transition cursor-pointer">
                                <span>Emitir Declaração</span>
                                <span>➔</span>
                            </a>
                        </div>

                        {{-- CARD 3: CERTIFICADO DE CONCLUSÃO --}}
                        <div class="rounded-3xl border border-slate-800/90 bg-slate-900/70 p-5 flex flex-col justify-between space-y-4 hover:border-amber-500/40 transition">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="h-11 w-11 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-xl text-amber-300">
                                        🏆
                                    </div>
                                    @if($isFormationCompleted)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            ✓ Pronto para Emissão
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            Em Andamento
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">Certificado Profissional</h3>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Certificado oficial frente e verso com autenticação digital por QR Code e fundamentação legal.
                                    </p>
                                </div>
                                <div class="rounded-2xl bg-slate-950/60 p-3 border border-slate-800/80 text-[11px] space-y-1">
                                    <div class="flex justify-between text-slate-400">
                                        <span>Conclusão do Curso:</span>
                                        <strong class="{{ $isFormationCompleted ? 'text-emerald-400' : 'text-amber-400' }}">
                                            {{ $isFormationCompleted ? '100% Concluído' : ($totalLessonsInFormation > 0 ? round(($completedLessonsCount / $totalLessonsInFormation) * 100) . '%' : '0%') }}
                                        </strong>
                                    </div>
                                    <div class="flex justify-between text-slate-400">
                                        <span>Liberação:</span>
                                        <strong class="text-slate-300">{{ $isFormationCompleted ? 'Imediata' : 'Ao concluir as aulas' }}</strong>
                                    </div>
                                </div>
                            </div>
                            @if($isFormationCompleted)
                                <a href="{{ route('student.certificate', ['contract' => $this->contract->id]) }}"
                                   target="_blank"
                                   class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/25 transition cursor-pointer">
                                    <span>Emitir Certificado Oficial</span>
                                    <span>➔</span>
                                </a>
                            @else
                                <button type="button"
                                        disabled
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-xs font-bold text-slate-500 cursor-not-allowed">
                                    <span>🔒 Conclua as Aulas para Liberar</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- MINI-CERTIFICADOS E BOLETINS POR CURSO --}}
                    <div class="rounded-3xl border border-slate-800/90 bg-slate-900/60 p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center gap-2">
                                    <span>🎓</span>
                                    <span>Mini-Certificados & Boletins por Disciplina</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Emita o histórico individual ou o certificado oficial de conclusão de cada curso concluído da sua formação.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            @foreach($this->courses as $course)
                                @php
                                    $acc = $this->getCourseAccess($course, $progressionService);
                                    $courseDone = $acc['status'] === 'completed';
                                    $pct = $acc['progress_percentage'] ?? 0;
                                    $cLink = \App\Models\ContractCourse::whereIn('contract_item_id', $this->contract->items()->select('id'))->where('course_id', $course->id)->first();
                                @endphp
                                @if($cLink)
                                    <div class="rounded-2xl border p-4 flex flex-col justify-between gap-3 {{ $courseDone ? 'border-emerald-500/30 bg-slate-950/60' : 'border-slate-800 bg-slate-950/40' }}">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h4 class="text-xs font-bold text-white">{{ $course->name }}</h4>
                                                <span class="text-[11px] text-slate-400 block mt-0.5">
                                                    {{ $course->workload ? $course->workload . 'h' : 'Carga regular' }} • {{ $pct }}% concluído
                                                </span>
                                            </div>
                                            @if($courseDone)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 shrink-0">
                                                    ✓ Concluído
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700 shrink-0">
                                                    Em andamento
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2 pt-2 border-t border-slate-800/60">
                                            <a href="{{ route('contracts.ouro-moderno-transcript.course', ['contract' => $this->contract->id, 'contractCourse' => $cLink->id]) }}"
                                               target="_blank"
                                               class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition">
                                                <span>📜</span>
                                                <span>Boletim do Curso</span>
                                            </a>

                                            @if($courseDone)
                                                <a href="{{ route('contracts.certificate.course', ['contract' => $this->contract->id, 'contractCourse' => $cLink->id]) }}"
                                                   target="_blank"
                                                   class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/20 px-3 py-1.5 text-xs font-bold text-amber-300 hover:bg-amber-500 hover:text-slate-950 transition shadow-sm shadow-amber-500/10">
                                                    <span>🎓</span>
                                                    <span>Mini-Certificado</span>
                                                </a>
                                            @else
                                                <button type="button"
                                                        disabled
                                                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-medium text-slate-500 cursor-not-allowed"
                                                        title="Conclua todas as aulas do curso para liberar o certificado">
                                                    <span>🔒</span>
                                                    <span>Certificado</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- SEÇÃO FINANCEIRA: CARNÊ, PARCELAS & PIX --}}
                    <div class="rounded-3xl border border-slate-800/90 bg-slate-900/60 p-6 space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center gap-2">
                                    <span>💳</span>
                                    <span>Mensalidades & 2ª Via do Carnê</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Acompanhe os pagamentos do seu plano e gere a 2ª via das parcelas.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('contracts.carne', ['contract' => $this->contract->id]) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-bold text-white border border-slate-700 transition shadow-sm cursor-pointer">
                                    <span>🖨️</span>
                                    <span>Baixar Carnê Completo</span>
                                </a>
                            </div>
                        </div>

                        {{-- BOX DE CHAVE PIX DA ESCOLA (SE HABILITADO) --}}
                        @if(!empty($pixInfo['enabled']) && !empty($pixInfo['key']))
                            <div x-data="{ copied: false }"
                                 class="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-xl shrink-0">
                                        ⚡
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-emerald-300">Pagamento Rápido via PIX da Escola</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase">
                                                {{ $pixInfo['key_type'] ?: 'PIX' }}
                                            </span>
                                        </div>
                                        <div class="text-xs font-mono font-bold text-white mt-1 break-all select-all">
                                            {{ $pixInfo['key'] }}
                                        </div>
                                        @if(!empty($pixInfo['merchant_name']))
                                            <div class="text-[11px] text-slate-400">
                                                Beneficiário: <strong class="text-slate-300">{{ $pixInfo['merchant_name'] }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button"
                                            @click="navigator.clipboard.writeText('{{ $pixInfo['key'] }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2 text-xs font-bold text-white transition shadow-md shadow-emerald-600/20 cursor-pointer">
                                        <span x-show="!copied">📋 Copiar Chave PIX</span>
                                        <span x-show="copied" x-cloak>✓ Chave Copiada!</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- TABELA DE PARCELAS --}}
                        <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/50">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-800 bg-slate-900/80 text-slate-400 font-semibold">
                                        <th class="p-3">Parcela</th>
                                        <th class="p-3">Vencimento</th>
                                        <th class="p-3">Valor</th>
                                        <th class="p-3">Situação</th>
                                        <th class="p-3 text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    @forelse($contractInstallments as $inst)
                                        @php
                                            $isPaid = $inst->status === 'paid';
                                            $isCancelled = $inst->status === 'cancelled';
                                            $isOverdue = ! $isPaid && ! $isCancelled && $inst->due_date && $inst->due_date->isPast();
                                        @endphp
                                        <tr class="hover:bg-slate-900/40 transition">
                                            <td class="p-3 font-bold text-white">
                                                Parcela {{ str_pad((string) $inst->installment_number, 2, '0', STR_PAD_LEFT) }}
                                            </td>
                                            <td class="p-3 text-slate-300">
                                                {{ $inst->due_date ? $inst->due_date->format('d/m/Y') : '—' }}
                                            </td>
                                            <td class="p-3 font-semibold text-white">
                                                R$ {{ number_format((float) $inst->amount, 2, ',', '.') }}
                                            </td>
                                            <td class="p-3">
                                                @if($isPaid)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        ✓ Pago {{ $inst->paid_at ? 'em ' . $inst->paid_at->format('d/m/Y') : '' }}
                                                    </span>
                                                @elseif($isCancelled)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-700/40 text-slate-400 border border-slate-700">
                                                        Cancelada
                                                    </span>
                                                @elseif($isOverdue)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                        Vencida ({{ $inst->due_date->diffInDays(now()) }}d)
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                        Em Aberto
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="p-3 text-right">
                                                <a href="{{ route('contracts.carne', ['contract' => $this->contract->id]) }}"
                                                   target="_blank"
                                                   class="text-[11px] font-bold text-amber-400 hover:text-amber-300 underline">
                                                    2ª Via
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="p-6 text-center text-slate-400 text-xs">
                                                Nenhuma parcela gerada para este contrato.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

        @elseif($activeTab === 'tickets')
            {{-- ========================================================================= --}}
            {{-- ABA: SUPORTE, ATENDIMENTO & CHAMADOS DO ALUNO                            --}}
            {{-- ========================================================================= --}}
            <div class="max-w-5xl mx-auto space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-white flex items-center gap-2">
                            <span>💬</span>
                            <span>Central de Atendimento & Chamados</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">
                            Tire dúvidas pedagógicas, solicite suporte à secretaria ou envie sugestões para a escola.
                        </p>
                    </div>
                    <button type="button"
                            wire:click="openNewTicketModal"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-amber-500 hover:bg-amber-400 px-5 py-3 text-xs font-bold text-slate-950 transition shadow-lg shadow-amber-500/20 cursor-pointer shrink-0">
                        <span>➕</span>
                        <span>Abrir Novo Chamado</span>
                    </button>
                </div>

                @if(session()->has('ticket_success'))
                    <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-300 flex items-center gap-2">
                        <span>✓</span>
                        <span>{{ session('ticket_success') }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- LISTA DE CHAMADOS À ESQUERDA --}}
                    <div class="lg:col-span-1 space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Meus Chamados</span>
                            <span class="text-xs font-mono font-bold text-amber-400">{{ count($studentTickets) }}</span>
                        </div>

                        <div class="space-y-2.5 max-h-[600px] overflow-y-auto pr-1">
                            @forelse($studentTickets as $ticket)
                                <button type="button"
                                        wire:click="selectTicket({{ $ticket->id }})"
                                        class="w-full text-left p-4 rounded-2xl border transition cursor-pointer {{ $selectedTicketId === $ticket->id ? 'border-amber-500 bg-amber-500/10 shadow-md shadow-amber-500/10' : 'border-slate-800 bg-slate-900/80 hover:border-slate-700' }}">
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <span class="text-[10px] font-mono font-bold text-amber-400">{{ $ticket->protocol }}</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $ticket->status === 'open' ? 'bg-amber-500/20 text-amber-300' : ($ticket->status === 'resolved' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-cyan-500/20 text-cyan-300') }}">
                                            {{ $ticket->status_label }}
                                        </span>
                                    </div>
                                    <h4 class="text-xs font-bold text-white line-clamp-1 mb-1">{{ $ticket->subject }}</h4>
                                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                                        <span>{{ $ticket->category_label }}</span>
                                        <span>{{ $ticket->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </button>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-800 p-8 text-center bg-slate-900/40">
                                    <span class="text-3xl block mb-2">📭</span>
                                    <p class="text-xs font-bold text-slate-300">Nenhum chamado aberto</p>
                                    <p class="text-[11px] text-slate-500 mt-1">Quando você precisar de ajuda, abra um chamado acima.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- DETALHES E TIMELINE DO CHAMADO SELECIONADO À DIREITA --}}
                    <div class="lg:col-span-2">
                        @if($currentTicket)
                            <div class="rounded-3xl border border-slate-800 bg-slate-900/90 p-6 flex flex-col h-full min-h-[500px]">
                                {{-- CABEÇALHO DO TICKET --}}
                                <div class="border-b border-slate-800 pb-4 mb-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-mono font-extrabold text-amber-400">{{ $currentTicket->protocol }}</span>
                                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $currentTicket->status === 'open' ? 'bg-amber-500/20 text-amber-300' : ($currentTicket->status === 'resolved' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-cyan-500/20 text-cyan-300') }}">
                                                {{ $currentTicket->status_label }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-slate-400">Aberto em {{ $currentTicket->created_at->format('d/m/Y \à\s H:i') }}</span>
                                    </div>
                                    <h3 class="text-base font-extrabold text-white">{{ $currentTicket->subject }}</h3>
                                    <div class="text-xs text-slate-400 mt-1 flex items-center gap-3">
                                        <span>Categoria: <strong class="text-slate-200">{{ $currentTicket->category_label }}</strong></span>
                                        <span>Unidade: <strong class="text-slate-200">{{ $currentTicket->school?->trade_name ?? $schoolName }}</strong></span>
                                    </div>
                                </div>

                                {{-- HISTÓRICO DE MENSAGENS / TIMELINE --}}
                                <div class="flex-1 space-y-4 overflow-y-auto pr-2 mb-4 max-h-[380px]">
                                    @foreach($currentTicket->messages as $msg)
                                        <div class="flex flex-col {{ $msg->author_type === 'student' ? 'items-end' : 'items-start' }}">
                                            <div class="max-w-[85%] rounded-2xl p-4 {{ $msg->author_type === 'student' ? 'bg-amber-500/15 border border-amber-500/30 text-amber-100 rounded-br-xs' : 'bg-slate-800/80 border border-slate-700/80 text-slate-200 rounded-bl-xs' }}">
                                                <div class="flex items-center justify-between gap-3 text-[11px] mb-1.5 opacity-80 border-b border-white/10 pb-1">
                                                    <span class="font-bold">
                                                        {{ $msg->author_type === 'student' ? 'Você' : ($msg->author_name ?: 'Equipe da Escola') }}
                                                    </span>
                                                    <span>{{ $msg->created_at->format('d/m H:i') }}</span>
                                                </div>
                                                <p class="text-xs whitespace-pre-wrap leading-relaxed">{{ $msg->message }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- FORMULÁRIO DE RESPOSTA --}}
                                @if(!in_array($currentTicket->status, ['resolved', 'closed'], true))
                                    <form wire:submit.prevent="replyTicket" class="pt-3 border-t border-slate-800 flex gap-2">
                                        <textarea
                                            wire:model="ticketReplyMessage"
                                            rows="2"
                                            placeholder="Digite sua resposta ou dúvida complementar..."
                                            class="flex-1 rounded-2xl border border-slate-800 bg-slate-950/80 p-3 text-xs text-white placeholder:text-slate-500 focus:border-amber-500/50 focus:ring-0 focus:outline-none resize-none"
                                            required
                                        ></textarea>
                                        <button
                                            type="submit"
                                            class="rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-5 text-xs font-bold transition shadow-md shadow-amber-500/20 cursor-pointer self-end py-3 shrink-0"
                                        >
                                            Enviar ➔
                                        </button>
                                    </form>
                                @else
                                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-center text-xs font-semibold text-emerald-300">
                                        Este chamado foi encerrado pela equipe da escola.
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="rounded-3xl border border-dashed border-slate-800 bg-slate-900/40 p-12 text-center h-full flex flex-col items-center justify-center min-h-[400px]">
                                <span class="text-4xl mb-3">💬</span>
                                <h4 class="text-sm font-bold text-white">Selecione um chamado ao lado</h4>
                                <p class="text-xs text-slate-500 mt-1 max-w-xs">
                                    Clique em qualquer protocolo da lista para ver o histórico de mensagens e respostas da escola.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- MODAL DE NOVO CHAMADO DO ALUNO --}}
            @if($showNewTicketModal)
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
                    <div class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-2xl">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-5">
                            <h3 class="text-base font-black text-white flex items-center gap-2">
                                <span>➕</span>
                                <span>Novo Chamado de Atendimento</span>
                            </h3>
                            <button type="button" wire:click="$set('showNewTicketModal', false)" class="text-slate-400 hover:text-white text-lg cursor-pointer">
                                ✕
                            </button>
                        </div>

                        <form wire:submit.prevent="createTicket" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Assunto Principal / Categoria
                                </label>
                                <select wire:model="ticketCategory" class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-white focus:border-amber-500/50 focus:outline-none">
                                    <option value="pedagogico">Dúvida Pedagógica / Aulas</option>
                                    <option value="financeiro">Financeiro / Boletos e Pix</option>
                                    <option value="secretaria">Secretaria / Documentos e Ficha</option>
                                    <option value="ouro_moderno">Laboratório Prático Interativo</option>
                                    <option value="geral">Outros Assuntos</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Título Resumido
                                </label>
                                <input
                                    type="text"
                                    wire:model="ticketSubject"
                                    placeholder="Ex: Dúvida no exercício da aula 3 de Excel..."
                                    class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-white placeholder:text-slate-500 focus:border-amber-500/50 focus:outline-none font-medium"
                                    required
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Descrição Detalhada
                                </label>
                                <textarea
                                    wire:model="ticketDescription"
                                    rows="4"
                                    placeholder="Explique com detalhes o que aconteceu ou a sua dúvida..."
                                    class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-white placeholder:text-slate-500 focus:border-amber-500/50 focus:outline-none resize-none"
                                    required
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                                <button type="button" wire:click="$set('showNewTicketModal', false)" class="rounded-xl border border-slate-700 px-4 py-2.5 text-xs font-bold text-slate-300 hover:bg-slate-800 transition cursor-pointer">
                                    Cancelar
                                </button>
                                <button type="submit" class="rounded-xl bg-amber-500 hover:bg-amber-400 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 transition cursor-pointer">
                                    Abrir Chamado ➔
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

        @else
            {{-- ========================================================================= --}}
            {{-- ABA 2: ROTEIRO DE AULAS DO CURSO SELECIONADO (ESTILO ESTÚDIO LMS DEDICADO) --}}
            {{-- ========================================================================= --}}
            @php
                $completedToday = $this->contract ? $progressionService->getStudentDailyCompletedLessonsCount($this->contract) : 0;
                $selectedCourse = $this->selectedCourse;
            @endphp

            @if(! $selectedCourse)
                {{-- ESTADO INICIAL SEM CURSO SELECIONADO --}}
                <div class="rounded-3xl border border-dashed border-slate-800 p-12 text-center bg-slate-900/40 space-y-4">
                    <div class="text-4xl">📚</div>
                    <h3 class="text-lg font-bold text-white">Nenhum curso selecionado</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto">
                        Selecione um curso da sua formação para visualizar as seções, aulas e continuar seu aprendizado.
                    </p>
                    <button type="button" wire:click="backToCoursesList"
                            class="inline-flex items-center gap-2 rounded-2xl bg-amber-500 hover:bg-amber-400 px-5 py-2.5 text-xs font-bold text-slate-950 transition shadow-lg shadow-amber-500/20 cursor-pointer">
                        <span>← Ver Todas as Formações & Cursos</span>
                    </button>
                </div>
            @else
                @php
                    $courseAccess = $this->getCourseAccess($selectedCourse, $progressionService);
                    $courseIsLocked = ! $courseAccess['can_access'];
                    $isLms = $selectedCourse->isAurosoftLms();
                    $totalCourseLessons = $selectedCourse->lmsModules->flatMap->lessons->where('is_active', true)->count();
                    $progressPercentage = $courseAccess['progress_percentage'] ?? 0;
                    $selectedFormationName = $this->getCourseFormationName($selectedCourse);
                    $finalExamAttempt = $isLms ? $this->getCourseFinalExamStatus($selectedCourse) : null;
                    $modalCoursesData = $this->courses->map(function ($c) use ($progressionService, $selectedCourse) {
                        $acc = $this->getCourseAccess($c, $progressionService);
                        return [
                            'id' => $c->id,
                            'name' => $c->name,
                            'workload' => $c->workload ? $c->workload . 'h' : 'Flex',
                            'lessons_count' => $c->lmsModules->flatMap->lessons->count(),
                            'is_current' => $c->id === $selectedCourse->id,
                            'is_locked' => $acc['status'] === 'locked',
                            'progress' => $acc['progress_percentage'] ?? 0,
                            'badge' => $acc['label'],
                            'url' => route('student.portal', ['tab' => 'courses', 'course_id' => $c->id]),
                        ];
                    })->values();
                @endphp

                <div class="space-y-6"
                     x-data="{
                         courseSearchModalOpen: false,
                         courseSearchTerm: '',
                         allCourses: @js($modalCoursesData)
                     }">
                    {{-- BARRA SUPERIOR DE NAVEGAÇÃO, ABERTURA EM NOVA ABA & PESQUISA DE CURSOS --}}
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/90 p-4 sm:p-5 shadow-xl backdrop-blur">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <button type="button"
                                        wire:click="backToCoursesList"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 px-3.5 py-2 text-xs font-bold border border-slate-700 transition cursor-pointer shrink-0"
                                        title="Voltar para a grade com todos os cursos">
                                    <span>←</span>
                                    <span>Voltar aos Cursos</span>
                                </button>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Roteiro de Aulas</span>
                                        @if($selectedFormationName)
                                            <span class="text-slate-600">•</span>
                                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-300 border border-amber-500/20">
                                                🏷️ {{ $selectedFormationName }}
                                            </span>
                                        @endif
                                    </div>
                                    <h2 class="text-base sm:text-lg font-black text-white truncate">
                                        {{ $selectedCourse->name }}
                                    </h2>
                                </div>
                            </div>

                            {{-- AÇÕES RÁPIDAS: ABRIR EM NOVA ABA E PESQUISAR CURSO --}}
                            <div class="flex items-center gap-2 self-stretch md:self-auto shrink-0 flex-wrap">
                                {{-- ABRIR EM NOVA ABA (IGUAL AO ESTÚDIO LMS) --}}
                                <a href="{{ route('student.portal', ['tab' => 'courses', 'course_id' => $selectedCourse->id]) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/90 hover:bg-slate-700 px-3 py-2 text-xs font-bold text-amber-400 hover:text-white transition shadow-sm"
                                   title="Abrir este roteiro em uma nova aba exclusiva do navegador">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span>Abrir em Nova Aba</span>
                                </a>

                                {{-- BOTÃO DE BUSCA RÁPIDA (SUBSTITUI O SELECT NATIVO) --}}
                                <button type="button"
                                        @click="courseSearchModalOpen = true; courseSearchTerm = ''; $nextTick(() => $refs.courseSearchField?.focus())"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/15 hover:bg-amber-500/25 px-3.5 py-2 text-xs font-bold text-amber-300 hover:text-white transition shadow-sm cursor-pointer">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <span>Pesquisar / Trocar Curso</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL DE PESQUISA RÁPIDA DE CURSOS PARA MOBILE E DESKTOP --}}
                    <div x-cloak
                         x-show="courseSearchModalOpen"
                         x-transition.opacity
                         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-md"
                         @keydown.escape.window="courseSearchModalOpen = false">
                        <div @click.outside="courseSearchModalOpen = false"
                             class="relative w-full max-w-xl max-h-[85vh] rounded-3xl border border-slate-700 bg-slate-900 shadow-2xl flex flex-col overflow-hidden">
                            {{-- CABEÇALHO DO MODAL --}}
                            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800 bg-slate-950/70 shrink-0">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl">🔍</span>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-extrabold text-white">Selecionar Curso da Formação</h3>
                                        <p class="text-[11px] text-slate-400">Pesquise pelo nome para trocar de curso ou abrir em nova aba</p>
                                    </div>
                                </div>
                                <button type="button" @click="courseSearchModalOpen = false" class="text-slate-400 hover:text-white text-lg p-1 cursor-pointer">✕</button>
                            </div>

                            {{-- CAMPO DE BUSCA INSTANTÂNEA COM FOCO AUTOMÁTICO --}}
                            <div class="p-4 border-b border-slate-800 bg-slate-900/90 shrink-0">
                                <div class="relative">
                                    <input x-ref="courseSearchField"
                                           x-model="courseSearchTerm"
                                           type="text"
                                           placeholder="Digite para buscar curso (ex: Informática, Excel, Photoshop)..."
                                           class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs sm:text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:ring-1 focus:ring-amber-400 focus:outline-none">
                                    <button x-show="courseSearchTerm"
                                            @click="courseSearchTerm = ''"
                                            type="button"
                                            class="absolute right-3 top-2.5 text-xs text-slate-400 hover:text-white">✕</button>
                                </div>
                            </div>

                            {{-- LISTA DE CURSOS FILTRADOS --}}
                            <div class="p-4 overflow-y-auto space-y-2 flex-1">
                                <template x-for="c in allCourses.filter(item => !courseSearchTerm || item.name.toLowerCase().includes(courseSearchTerm.toLowerCase()))" :key="c.id">
                                    <div class="flex items-center justify-between p-3.5 rounded-2xl border transition"
                                         :class="c.is_current ? 'border-amber-500/60 bg-amber-500/10 shadow-sm' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'">
                                        <div class="min-w-0 pr-3">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-xs sm:text-sm font-bold text-white truncate" x-text="c.name"></span>
                                                <span class="text-[10px] text-slate-400" x-text="'• ' + c.workload"></span>
                                                <template x-if="c.is_current">
                                                    <span class="rounded bg-amber-500/20 px-1.5 py-0.2 text-[9px] font-extrabold text-amber-300 border border-amber-500/30">
                                                        Atual
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400">
                                                <span x-text="c.badge" :class="c.is_locked ? 'text-slate-500 font-semibold' : 'text-emerald-400 font-semibold'"></span>
                                                <span>•</span>
                                                <span x-text="c.lessons_count + ' aulas'"></span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            {{-- BOTÃO SELECIONAR NESTA ABA --}}
                                            <button type="button"
                                                    @click="courseSearchModalOpen = false; $wire.selectCourse(c.id)"
                                                    class="rounded-xl bg-amber-500 hover:bg-amber-400 px-3 py-1.5 text-xs font-bold text-slate-950 transition cursor-pointer">
                                                Selecionar
                                            </button>

                                            {{-- BOTÃO ABRIR EM NOVA ABA --}}
                                            <a :href="c.url"
                                               target="_blank"
                                               class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 p-2 text-amber-400 hover:text-white transition"
                                               title="Abrir este curso em uma nova aba do navegador">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- RODAPÉ DO MODAL --}}
                            <div class="flex items-center justify-between px-5 py-3 border-t border-slate-800 bg-slate-950/70 shrink-0">
                                <span class="text-xs text-slate-500" x-text="allCourses.length + ' cursos encontrados'"></span>
                                <button type="button" @click="courseSearchModalOpen = false" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition cursor-pointer">
                                    Fechar
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- CARD DE RESUMO DO CURSO: PROGRESSO, DETALHES E CERTIFICAÇÃO --}}
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6 shadow-xl backdrop-blur">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $courseAccess['badge'] }}">
                                        @if($courseIsLocked)
                                            🔒 {{ $courseAccess['label'] }}
                                        @elseif($courseAccess['status'] === 'completed')
                                            ✓ {{ $courseAccess['label'] }}
                                        @else
                                            ⚡ {{ $courseAccess['label'] }}
                                        @endif
                                    </span>

                                    <span class="text-xs text-slate-400 font-semibold">
                                        ⏱️ Carga: {{ $selectedCourse->workload ? $selectedCourse->workload . 'h' : 'Flexível' }}
                                    </span>

                                    @if($isLms)
                                        <span class="text-slate-600">•</span>
                                        <span class="text-xs text-indigo-300 font-semibold">
                                            {{ $selectedCourse->lmsModules->count() }} seções • {{ $totalCourseLessons }} aulas
                                        </span>
                                    @else
                                        <span class="text-slate-600">•</span>
                                        <span class="text-xs text-cyan-300 font-semibold">
                                            🌐 Plataforma Ouro Moderno
                                        </span>
                                    @endif
                                </div>

                                {{-- BARRA DE PROGRESSO DO CURSO --}}
                                <div class="space-y-1.5 max-w-md">
                                    <div class="flex justify-between text-[11px]">
                                        <span class="text-slate-400">Progresso deste curso:</span>
                                        <span class="font-bold text-white">{{ $progressPercentage }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                        <div class="h-2.5 rounded-full transition-all duration-500 {{ $courseAccess['status'] === 'completed' ? 'bg-emerald-500' : 'bg-gradient-to-r from-amber-500 to-amber-300' }}" style="width: {{ $progressPercentage }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 shrink-0">
                                @if($isLms && ($courseAccess['status'] === 'completed' || $isEducatorMode))
                                    <button type="button"
                                            wire:click="openCourseFinalExam({{ $selectedCourse->id }})"
                                            class="inline-flex items-center gap-2 rounded-2xl border px-5 py-3 text-xs font-bold transition shadow-lg cursor-pointer {{ $finalExamAttempt && $finalExamAttempt->passed ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25 shadow-emerald-500/10' : 'border-indigo-500/40 bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/25' }}">
                                        <span>🎓</span>
                                        <span>
                                            {{ $finalExamAttempt && $finalExamAttempt->passed ? 'Certificação Aprovada (' . number_format((float)$finalExamAttempt->score, 0) . '%)' : 'Exame Final de Certificação' }}
                                        </span>
                                    </button>
                                @endif

                                <div class="rounded-2xl border border-slate-800 bg-slate-950/60 px-4 py-2.5 text-center">
                                    <span class="text-[10px] uppercase font-semibold text-slate-400">Meta do Dia</span>
                                    <div class="text-sm font-black text-amber-400">
                                        {{ $isEducatorMode ? 'Acesso Livre' : ($completedToday . ' / 1 Aula') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SE O CURSO ESTÁ BLOQUEADO PELA SEQUÊNCIA PEDAGÓGICA --}}
                    @if($courseIsLocked)
                        <div class="rounded-3xl border border-amber-500/30 bg-slate-900/90 p-8 sm:p-12 text-center shadow-2xl backdrop-blur space-y-4">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-400 border border-amber-500/20 text-3xl shadow-inner">
                                🔒
                            </div>
                            <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300 border border-amber-500/20">
                                Sequência Pedagógica • Curso Bloqueado
                            </div>
                            <h3 class="text-xl sm:text-2xl font-bold text-white">
                                {{ $selectedCourse->name }}
                            </h3>
                            <p class="text-sm text-slate-300 max-w-lg mx-auto leading-relaxed">
                                {{ $courseAccess['message'] }}
                            </p>
                            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 max-w-md mx-auto text-xs text-slate-400 text-left">
                                <p class="font-bold text-amber-300 mb-1">📖 Metodologia Sequencial:</p>
                                <p>Os cursos da sua formação são estruturados de forma progressiva. Para garantir o melhor aprendizado, cada novo curso é liberado somente após a conclusão integral de todas as aulas do curso anterior.</p>
                            </div>
                            <div>
                                <button type="button"
                                        wire:click="backToCoursesList"
                                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-5 py-2.5 text-xs font-bold text-slate-950 transition shadow-lg">
                                    <span>← Voltar e Concluir Curso Anterior</span>
                                </button>
                            </div>
                        </div>

                    {{-- CURSO OURO MODERNO --}}
                    @elseif(! $selectedCourse->isAurosoftLms())
                        <div class="rounded-3xl border border-cyan-500/30 bg-slate-900/70 p-6 sm:p-8 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 text-2xl">
                                    🌐
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-cyan-400 uppercase tracking-wider">Integração Ouro Moderno</span>
                                    <h3 class="text-lg font-bold text-white">{{ $selectedCourse->name }}</h3>
                                </div>
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed">
                                As atividades práticas deste curso são realizadas na plataforma interativa Ouro Moderno, instalada nos computadores da sala de aula e no Totem da escola.
                            </p>

                            @if($isEducatorMode && Auth::user()?->isEducator())
                                <div class="rounded-2xl border border-indigo-500/30 bg-indigo-500/10 p-5 text-xs space-y-3">
                                    <div class="flex items-center gap-2 font-bold text-indigo-300">
                                        <span>👨‍🏫</span>
                                        <span>Acesso Pedagógico do Educador</span>
                                    </div>
                                    <p class="text-slate-300 leading-relaxed">
                                        Como educador/professor da escola, você possui acesso direto e irrestrito aos conteúdos da plataforma Ouro Moderno para planejar aulas e orientar seus alunos.
                                    </p>
                                    <a href="{{ route('educator.lms-autologin') }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-2 rounded-xl bg-cyan-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-cyan-400 transition shadow-lg shadow-cyan-500/20">
                                        <span>Conectar ao LMS Ouro Moderno (Educador)</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                            @elseif($isEducatorMode)
                                <div class="rounded-2xl border border-indigo-500/30 bg-indigo-500/10 p-5 text-xs space-y-3">
                                    <div class="flex items-center gap-2 font-bold text-indigo-300">
                                        <span>💼</span>
                                        <span>Acesso da Equipe / Colaborador</span>
                                    </div>
                                    <p class="text-slate-300 leading-relaxed">
                                        Você possui acesso aos cursos autorizados pela direção da escola para sua capacitação profissional. Para realizar as atividades práticas deste curso no laboratório, dirija-se ao Totem ou ao computador da escola.
                                    </p>
                                </div>
                            @elseif($this->contract && $this->contract->student?->isMigratedToOuroModerno())
                                @php
                                    $scheduleCheck = app(\App\Services\LmsProgressionService::class)->checkStudentScheduleWindow($this->contract, $selectedCourse);
                                @endphp
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 text-xs space-y-2">
                                    <p class="font-bold text-white">Como acessar sua aula no laboratório:</p>
                                    <ol class="list-decimal pl-4 space-y-1 text-slate-400">
                                        <li>Dirija-se ao computador do laboratório na escola ou ao Totem de Presença.</li>
                                        <li>Digite seu número de matrícula (<strong>{{ $this->contract->contract_number }}</strong>) ou seu CPF.</li>
                                        <li>O sistema fará a validação de presença e abrirá as aulas interativas da sua turma.</li>
                                    </ol>
                                </div>

                                @if(! $scheduleCheck['is_allowed'])
                                    <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs space-y-2 text-amber-200">
                                        <div class="flex items-center gap-2 font-bold text-amber-300">
                                            <span>⏰</span>
                                            <span>Acesso Disponível Apenas no seu Horário de Aula</span>
                                        </div>
                                        <p class="text-slate-300 leading-relaxed">
                                            {{ $scheduleCheck['message'] }}
                                        </p>
                                        <p class="text-[11px] text-amber-300/80">
                                            Horários Agendados: <strong>{{ $scheduleCheck['schedules_text'] }}</strong>
                                        </p>
                                    </div>
                                @else
                                    <a href="{{ route('kiosk.lms-autologin', ['contract' => $this->contract->id]) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-2 rounded-xl bg-cyan-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-cyan-400 transition shadow-lg shadow-cyan-500/25">
                                        <span>Iniciar Aula no Laboratório Interativo</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @endif
                            @else
                                <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 text-xs space-y-2 text-amber-200">
                                    <div class="flex items-center gap-2 font-bold text-amber-300">
                                        <svg class="h-5 w-5 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>Matrícula em Processamento Pedagógico</span>
                                    </div>
                                    <p class="text-slate-300 leading-relaxed">
                                        Sua formação inclui este curso prático interativo. A coordenação pedagógica da escola está finalizando a liberação do seu módulo nos computadores do laboratório.
                                    </p>
                                    <p class="text-slate-400 text-[11px]">
                                        Enquanto isso, sua presença é registrada normalmente no Totem. Assim que a coordenação pedagógica liberar a turma, seu acesso estará disponível.
                                    </p>
                                </div>
                            @endif
                        </div>

                    {{-- CURSO LMS NATIVO COM MÓDULOS E AULAS EM TELA CHEIA (SEM ROLAGEM DESNECESSÁRIA) --}}
                    @else
                        <div class="space-y-6">
                            @forelse($selectedCourse->lmsModules as $module)
                                <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-5 sm:p-6 space-y-4 shadow-lg">
                                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                                        <div>
                                            <h4 class="text-sm font-bold text-white flex items-center gap-2">
                                                <span>📁</span>
                                                <span>{{ $module->title }}</span>
                                            </h4>
                                            @if($module->description)
                                                <p class="text-xs text-slate-400 mt-0.5">{{ $module->description }}</p>
                                            @endif
                                        </div>
                                        <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold text-slate-400">
                                            {{ $module->lessons->count() }} aulas
                                        </span>
                                    </div>

                                    <div class="space-y-3">
                                        @forelse($module->lessons as $lesson)
                                            @php
                                                $statusInfo = $this->getLessonStatus($lesson, $progressionService);
                                                $canStart = in_array($statusInfo['state'], ['available', 'in_progress', 'completed', 'unlocked', 'ready'], true);
                                            @endphp

                                            <div class="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-4 transition hover:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div class="space-y-1 min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $statusInfo['badge'] }}">
                                                            {{ $statusInfo['label'] }}
                                                        </span>
                                                        <span class="text-[11px] text-slate-500">⏱️ {{ $lesson->estimated_duration_minutes }}m</span>
                                                        <span class="text-[11px] text-slate-500">• Corte: {{ (int)$lesson->passing_score }}%</span>
                                                    </div>
                                                    <h5 class="text-sm font-bold text-white leading-snug">{{ $lesson->title }}</h5>
                                                    <p class="text-[11px] text-slate-400 leading-relaxed">{{ $statusInfo['message'] }}</p>
                                                </div>

                                                <div class="shrink-0 flex items-center gap-2">
                                                    @if($canStart)
                                                        <a href="{{ route('lms.player', ['lesson' => $lesson->id, 'contract_id' => $this->contract?->id]) }}"
                                                           class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold transition {{ $statusInfo['state'] === 'completed' ? 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700' : 'bg-amber-500 text-slate-950 hover:bg-amber-400 shadow-md shadow-amber-500/20' }}">
                                                            <span>{{ $statusInfo['state'] === 'completed' ? 'Revisar Aula' : ($statusInfo['state'] === 'in_progress' ? 'Continuar Aula' : 'Iniciar Aula') }}</span>
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                            </svg>
                                                        </a>
                                                    @else
                                                        <button type="button"
                                                                disabled
                                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-500 cursor-not-allowed border border-slate-800">
                                                            <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                            </svg>
                                                            <span>Bloqueada</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-500 italic">Nenhuma aula cadastrada nesta seção.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-800 p-8 text-center text-xs text-slate-400">
                                    Nenhum módulo de conteúdo cadastrado neste curso ainda.
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </main>
    </div>

    {{-- MODAL: PROVA FINAL DE CONCLUSÃO DE CURSO (EXAME DE CERTIFICAÇÃO) --}}
    @if($showCourseFinalExamModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md">
            <div class="w-full max-w-3xl max-h-[90vh] rounded-3xl border border-indigo-500/40 bg-slate-900 shadow-2xl flex flex-col overflow-hidden">
                {{-- CABEÇALHO --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950/60 shrink-0">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 text-xl">
                            🎓
                        </span>
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-400 block">
                                Exame Somativo de Certificação • Nota de Corte: 70%
                            </span>
                            <h3 class="text-base sm:text-lg font-black text-white">
                                {{ $finalExamCourseName }} ({{ count($finalExamQuestions) }} questões sorteadas)
                            </h3>
                        </div>
                    </div>
                    <button type="button" wire:click="closeCourseFinalExam" class="rounded-xl p-2 text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                        ✕
                    </button>
                </div>

                {{-- CORPO DO EXAME --}}
                <div class="p-6 overflow-y-auto space-y-5 flex-1">
                    @if($finalExamSubmitted)
                        <div class="rounded-3xl border p-6 text-center space-y-3 {{ $finalExamPassed ? 'border-emerald-500/40 bg-emerald-950/20' : 'border-rose-500/40 bg-rose-950/20' }}">
                            <div class="text-4xl">
                                {{ $finalExamPassed ? '🏆' : '🔄' }}
                            </div>
                            <h4 class="text-xl font-black text-white">
                                {{ $finalExamPassed ? 'Parabéns! Você foi aprovado no Exame Final!' : 'Nota abaixo do mínimo para certificação (70%)' }}
                            </h4>
                            <p class="text-sm text-slate-300">
                                Sua nota final: <strong class="{{ $finalExamPassed ? 'text-emerald-400' : 'text-rose-400' }} text-lg">{{ number_format($finalExamScore, 1, ',', '.') }}%</strong>
                            </p>
                            @if(! $finalExamPassed)
                                <button type="button"
                                        wire:click="openCourseFinalExam({{ $finalExamCourseId }})"
                                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-5 py-2.5 text-xs font-black text-slate-950 transition cursor-pointer">
                                    <span>🎲 Sortear Nova Prova Final</span>
                                </button>
                            @endif
                        </div>
                    @endif

                    @foreach($finalExamQuestions as $qIdx => $q)
                        @php
                            $selectedOpt = $finalExamAnswers[$qIdx] ?? null;
                        @endphp
                        <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-400">
                                    Questão #{{ $qIdx + 1 }}
                                </span>
                                <span class="text-[10px] text-slate-500 truncate max-w-xs">
                                    📚 {{ $q['lesson_title'] ?? '' }}
                                </span>
                            </div>
                            <p class="text-sm font-bold text-white leading-relaxed">
                                {{ $q['question'] }}
                            </p>
                            <div class="space-y-2 pt-1">
                                @foreach($q['options'] as $opt)
                                    @php
                                        $optId = $opt['id'] ?? 'A';
                                        $isChosen = $selectedOpt === $optId;
                                        $isCorrectOpt = !empty($opt['is_correct']);
                                    @endphp
                                    <button type="button"
                                            wire:click="selectFinalExamOption({{ $qIdx }}, '{{ $optId }}')"
                                            class="w-full text-left rounded-xl border p-3 text-xs transition flex items-center justify-between gap-3 cursor-pointer {{ $finalExamSubmitted ? ($isCorrectOpt ? 'border-emerald-500/60 bg-emerald-500/15 text-emerald-200' : ($isChosen ? 'border-rose-500/60 bg-rose-500/15 text-rose-200' : 'border-slate-800 bg-slate-900/50 text-slate-400')) : ($isChosen ? 'border-indigo-500 bg-indigo-500/20 text-white font-bold' : 'border-slate-800 bg-slate-900/60 text-slate-300 hover:border-slate-700') }}">
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg font-extrabold text-[11px] {{ $isChosen ? 'bg-indigo-500 text-white' : 'bg-slate-800 text-slate-400' }}">
                                                {{ $optId }}
                                            </span>
                                            <span>{{ $opt['text'] ?? '' }}</span>
                                        </div>
                                        @if($finalExamSubmitted && $isCorrectOpt)
                                            <span class="text-emerald-400 font-bold shrink-0">✓ Correta</span>
                                        @elseif($finalExamSubmitted && $isChosen && ! $isCorrectOpt)
                                            <span class="text-rose-400 font-bold shrink-0">✕ Sua escolha</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- RODAPÉ --}}
                <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-slate-800 bg-slate-950/60 shrink-0">
                    <span class="text-xs text-slate-400">
                        Respondidas: <strong class="text-white">{{ count($finalExamAnswers) }} / {{ count($finalExamQuestions) }}</strong>
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                wire:click="closeCourseFinalExam"
                                class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-bold text-slate-300 transition cursor-pointer">
                            Fechar
                        </button>
                        @if(! $finalExamSubmitted)
                            <button type="button"
                                    wire:click="submitCourseFinalExam"
                                    class="rounded-xl bg-emerald-500 hover:bg-emerald-400 px-5 py-2 text-xs font-black text-slate-950 shadow-lg shadow-emerald-500/25 transition cursor-pointer">
                                ✓ Finalizar e Corrigir Exame
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE TROCA DE SENHA DO ALUNO --}}
    @if($showChangePasswordModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
             x-data
             role="dialog"
             aria-modal="true">
            <div class="relative w-full max-w-md rounded-3xl border border-slate-700 bg-slate-900 p-6 shadow-2xl space-y-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-xl shrink-0">
                            🔐
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">
                                {{ Auth::user()?->must_change_password ? 'Cadastre sua Senha Individual' : 'Alterar Minha Senha' }}
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ Auth::user()?->must_change_password ? 'Primeiro acesso ou atualização de segurança' : 'Defina uma nova senha para acessar o portal' }}
                            </p>
                        </div>
                    </div>
                    @if(! Auth::user()?->must_change_password)
                        <button type="button"
                                wire:click="closeChangePasswordModal"
                                class="rounded-xl p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                            ✕
                        </button>
                    @endif
                </div>

                @if(Auth::user()?->must_change_password)
                    <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 text-xs text-amber-200 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-amber-300">
                            <span>🛡️</span>
                            <span>Segurança da sua Conta</span>
                        </div>
                        <p class="text-[11px] text-slate-300">
                            Para sua segurança e privacidade, substitua sua data de nascimento padrão por uma senha pessoal que apenas você conheça.
                        </p>
                    </div>
                @endif

                <form wire:submit="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nova Senha</label>
                        <input type="password"
                               wire:model="newPassword"
                               placeholder="Mínimo de 6 caracteres"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        @error('newPassword')
                            <span class="text-[11px] font-semibold text-rose-400 block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Confirme a Nova Senha</label>
                        <input type="password"
                               wire:model="newPasswordConfirmation"
                               placeholder="Repita exatamente a nova senha"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        @error('newPasswordConfirmation')
                            <span class="text-[11px] font-semibold text-rose-400 block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        @if(! Auth::user()?->must_change_password)
                            <button type="button"
                                    wire:click="closeChangePasswordModal"
                                    class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-bold text-slate-300 transition cursor-pointer">
                                Cancelar
                            </button>
                        @endif
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-5 py-2.5 text-xs font-bold text-slate-950 transition shadow-lg shadow-amber-500/20 cursor-pointer">
                            <span wire:loading.remove wire:target="updatePassword">Salvar Nova Senha</span>
                            <span wire:loading wire:target="updatePassword">Gravando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

