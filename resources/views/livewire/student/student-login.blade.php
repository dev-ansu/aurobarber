<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-4 sm:p-6 font-sans antialiased relative overflow-hidden"
     x-data="{ mode: @entangle('loginType').live, showPassword: false }">

    @php
        $loginSchool = \App\Models\School::getActiveSchool() ?? \App\Models\School::first();
        $loginSchoolLogo = $loginSchool?->logo_url;
        $loginSchoolName = $loginSchool?->trade_name ?? $loginSchool?->legal_name;
        $brandHex = $loginSchool?->brand_color ?: '#F59E0B';
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

    {{-- EFEITOS DE FUNDO (AMBIENT GLOW) --}}
    <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 h-[500px] w-[600px] rounded-full blur-3xl opacity-70"
         style="background: radial-gradient(circle, rgba(var(--school-brand-rgb), 0.22) 0%, rgba(99, 102, 241, 0.1) 60%, transparent 100%);"></div>
    <div class="pointer-events-none absolute -bottom-40 right-10 h-[400px] w-[400px] rounded-full bg-cyan-500/10 blur-3xl opacity-50"></div>

    <div class="relative max-w-md w-full rounded-3xl border border-slate-800/80 bg-slate-900/90 p-6 sm:p-8 shadow-2xl backdrop-blur-2xl">
        {{-- LOGO & IDENTIDADE DA ESCOLA (EM DESTAQUE, IGUAL AO PAINEL ADMIN) --}}
        <div class="text-center mb-6">
            @if ($loginSchoolLogo)
                <div class="mb-4 flex justify-center">
                    <div class="inline-flex items-center justify-center rounded-2xl bg-white p-2.5 shadow-xl shadow-black/30 ring-1 ring-white/10">
                        <img src="{{ $loginSchoolLogo }}" alt="{{ $loginSchoolName }}" class="h-12 w-auto max-w-[200px] object-contain select-none" onerror="this.parentElement.style.display='none'; document.getElementById('student-login-default-brand').style.display='inline-flex';" />
                    </div>
                </div>
                <div id="student-login-default-brand" style="display: none;" class="justify-center items-center gap-2.5 mb-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-school-brand font-black shadow-school-brand text-xl">
                        {{ strtoupper(mb_substr($loginSchoolName ?: 'E', 0, 1)) }}
                    </div>
                    <div class="text-left">
                        <span class="text-base font-black text-white block">{{ $loginSchoolName }}</span>
                        <span class="text-[11px] font-semibold text-school-brand block">Ambiente do Aluno</span>
                    </div>
                </div>
            @else
                <div class="inline-flex items-center justify-center gap-2.5 mb-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-school-brand font-black shadow-school-brand text-xl">
                        {{ strtoupper(mb_substr($loginSchoolName ?: 'E', 0, 1)) }}
                    </div>
                    <div class="text-left">
                        <span class="text-base font-black text-white block leading-tight">{{ $loginSchoolName ?: 'Escola Profissionalizante' }}</span>
                        <span class="text-[11px] font-semibold text-school-brand block">Ambiente do Aluno</span>
                    </div>
                </div>
            @endif
            
            <div class="mt-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-school-brand-soft px-3 py-1 text-xs font-semibold text-school-brand border border-school-brand mb-2">
                    <span class="h-1.5 w-1.5 rounded-full animate-pulse" style="background-color: var(--school-brand);"></span>
                    <span x-text="mode === 'student' ? 'Portal do Aluno • Sala de Aula Online' : 'Ambiente do Educador & Instrutores'"></span>
                </span>
                
                <h1 class="text-xl sm:text-2xl font-black text-white" x-text="mode === 'student' ? 'Acesso ao Portal do Aluno' : 'Acesso do Educador'">
                    Acesso ao Portal do Aluno
                </h1>
                <p class="text-xs text-slate-400 mt-1.5 leading-relaxed" x-text="mode === 'student' ? 'Informe sua matrícula ou CPF para acessar suas aulas, simulados e materiais.' : 'Entre com suas credenciais institucionais para acompanhar os cursos e turmas.'">
                    Informe sua matrícula ou CPF para acessar suas aulas, simulados e materiais.
                </p>
            </div>
        </div>

        {{-- SELETOR DE PERFIL (ALUNO / EDUCADOR) --}}
        <div class="grid grid-cols-2 p-1 mb-6 rounded-2xl bg-slate-950/80 border border-slate-800/90 text-xs font-bold">
            <button
                type="button"
                wire:click="setLoginType('student')"
                @click="mode = 'student'"
                :class="mode === 'student' ? 'bg-school-brand shadow-school-brand' : 'text-slate-400 hover:text-white'"
                class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl transition duration-200 cursor-pointer"
            >
                <span>🎓</span>
                <span>Sou Aluno</span>
            </button>
            <button
                type="button"
                wire:click="setLoginType('educator')"
                @click="mode = 'educator'"
                :class="mode === 'educator' ? 'bg-school-brand shadow-school-brand' : 'text-slate-400 hover:text-white'"
                class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl transition duration-200 cursor-pointer"
            >
                <span>👨‍🏫</span>
                <span>Educador / Equipe</span>
            </button>
        </div>

        {{-- FORMULÁRIO DE LOGIN --}}
        <form wire:submit="login" class="space-y-4 text-xs">
            <input type="hidden" wire:model="loginType" :value="mode">
            @if($errorMessage)
                <div class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-3.5 text-xs text-rose-300 flex items-start gap-2.5 animate-shake">
                    <span class="text-base leading-none">⚠️</span>
                    <p class="flex-1 leading-relaxed">{{ $errorMessage }}</p>
                </div>
            @endif

            {{-- CAMPO IDENTIFICADOR --}}
            <div>
                <label class="block font-bold text-slate-300 mb-1.5" x-text="mode === 'student' ? 'Número de Matrícula ou CPF' : 'E-mail Institucional ou Usuário'">
                    Número de Matrícula ou CPF
                </label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model="identifier"
                        :placeholder="mode === 'student' ? 'Ex: MAT-2026-001 ou 123.456.789-00' : 'Ex: professor@aurosoft.local ou usuário Ouro'"
                        autocomplete="username"
                        class="block w-full rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3 text-xs font-medium text-white placeholder:text-slate-500 focus:outline-none transition shadow-inner"
                        style="--tw-ring-color: var(--school-brand);"
                        onfocus="this.style.borderColor='var(--school-brand)'"
                        onblur="this.style.borderColor=''"
                    />
                </div>
                @error('identifier') <p class="text-rose-400 mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            {{-- CAMPO SENHA / DATA NASCIMENTO --}}
            <div>
                <label class="block font-bold text-slate-300 mb-1.5" x-text="mode === 'student' ? 'Data de Nascimento ou Senha' : 'Senha do Sistema'">
                    Data de Nascimento ou Senha
                </label>
                <div class="relative">
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        wire:model="passwordOrBirthDate"
                        :placeholder="mode === 'student' ? 'Ex: 15/03/2005 ou sua senha' : 'Sua senha institucional'"
                        autocomplete="current-password"
                        class="block w-full rounded-2xl border border-slate-800 bg-slate-950/80 pl-4 pr-11 py-3 text-xs font-medium text-white placeholder:text-slate-500 focus:outline-none transition shadow-inner"
                        onfocus="this.style.borderColor='var(--school-brand)'"
                        onblur="this.style.borderColor=''"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition p-1"
                        title="Alternar visualização da senha"
                    >
                        <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                
                <p x-show="mode === 'student'" class="text-[11px] text-slate-400 mt-1.5 leading-relaxed bg-slate-950/40 p-2.5 rounded-xl border border-slate-800/60">
                    💡 <strong class="text-school-brand">Primeiro acesso?</strong> Digite sua data de nascimento com barras ou apenas números (ex: <span class="font-mono text-school-brand">15/03/2005</span> ou <span class="font-mono text-school-brand">15032005</span>).
                </p>

                <p x-show="mode === 'educator'" x-cloak class="text-[11px] text-slate-400 mt-1.5 leading-relaxed bg-slate-950/40 p-2.5 rounded-xl border border-slate-800/60">
                    👨‍🏫 <strong class="text-school-brand">Educadores e Instrutores:</strong> Acesse diretamente o ambiente de cursos, simulações de turmas e suporte aos alunos.
                </p>

                @error('passwordOrBirthDate') <p class="text-rose-400 mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            {{-- BOTÃO DE SUBMIT --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-school-brand hover:brightness-110 px-5 py-3.5 text-xs font-black shadow-school-brand transition cursor-pointer disabled:opacity-50"
            >
                <span wire:loading.remove x-text="mode === 'student' ? 'Acessar Minhas Aulas' : 'Acessar como Educador'">Acessar Minhas Aulas</span>
                <span wire:loading>Autenticando acesso...</span>
                <svg wire:loading.remove class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </form>

        {{-- ATALHOS COMPLEMENTARES --}}
        <div class="mt-8 pt-6 border-t border-slate-800/80 space-y-3">
            <div class="grid grid-cols-2 gap-2 text-center text-xs">
                <a href="{{ url('/presenca') }}" class="flex items-center justify-center gap-1.5 rounded-xl bg-slate-950/60 hover:bg-slate-800/60 border border-slate-800 px-3 py-2 text-cyan-300 font-semibold transition hover:border-cyan-500/40">
                    <span>🪪</span>
                    <span>Totem de Presença</span>
                </a>
                <a href="{{ route('filament.admin.auth.login') }}" class="flex items-center justify-center gap-1.5 rounded-xl bg-slate-950/60 hover:bg-slate-800/60 border border-slate-800 px-3 py-2 text-school-brand font-semibold transition hover:border-school-brand">
                    <span>🔒</span>
                    <span>Painel Admin</span>
                </a>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-800/60">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-slate-400 hover:text-white transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Voltar ao início</span>
                </a>
                
                <div class="inline-flex items-center gap-1.5 select-none">
                    <div class="flex h-4 w-4 items-center justify-center rounded bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 font-black text-[9px] shadow-sm">
                        A
                    </div>
                    <span class="font-bold text-slate-300">Aurosoft <span class="font-normal text-slate-400">&middot; LMS Escolar</span></span>
                </div>
            </div>
        </div>
    </div>
</div>
