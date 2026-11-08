<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formation->name }} &middot; {{ $school->trade_name ?: $school->legal_name }}</title>
    <meta name="description" content="{{ $formation->short_description ?: 'Conheça a ementa completa e garanta sua vaga na formação ' . $formation->name . ' com aulas práticas e certificado reconhecido.' }}">

    @if ($school->logo_url)
        <link rel="icon" type="image/png" href="{{ $school->logo_url }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- CSS Pré-compilado de Alta Performance via Vite -->
    @vite(['resources/css/app.css'])

@php
    $brandHex = $school->brand_color ?: '#F59E0B';
    $cleanHex = ltrim($brandHex, '#');
    if (strlen($cleanHex) === 3) {
        $r = hexdec(str_repeat(substr($cleanHex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($cleanHex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($cleanHex, 2, 1), 2));
    } elseif (strlen($cleanHex) === 6) {
        $r = hexdec(substr($cleanHex, 0, 2));
        $g = hexdec(substr($cleanHex, 2, 2));
        $b = hexdec(substr($cleanHex, 4, 2));
    } else {
        $r = 245; $g = 158; $b = 11;
    }
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    $brandTextColor = ($yiq >= 150) ? '#020617' : '#ffffff';
@endphp

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        :root {
            --brand-color: {{ $brandHex }};
            --brand-rgb: {{ $r }}, {{ $g }}, {{ $b }};
            --brand-text: {{ $brandTextColor }};
        }
        [x-cloak] { display: none !important; }
        .glass-header {
            background: rgba(10, 15, 30, 0.98);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .bg-school-brand {
            background-color: var(--brand-color) !important;
            color: var(--brand-text) !important;
        }
        .text-school-brand {
            color: var(--brand-color) !important;
        }
        .border-school-brand {
            border-color: var(--brand-color) !important;
        }
        .bg-school-brand-soft {
            background-color: rgba(var(--brand-rgb), 0.12) !important;
            color: var(--brand-color) !important;
            border-color: rgba(var(--brand-rgb), 0.3) !important;
        }
        .shadow-school-brand {
            box-shadow: 0 10px 25px -5px rgba(var(--brand-rgb), 0.35) !important;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950">

    {{-- Top Notification Bar / Aviso Oficial LDB --}}
    <div class="bg-school-brand text-[11px] sm:text-xs font-bold py-2 px-3 sm:px-4 text-center select-none shadow-sm flex items-center justify-center gap-1.5 sm:gap-2">
        <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-slate-950 text-school-brand text-[10px] font-black shrink-0">✓</span>
        <span class="hidden sm:inline">Cursos Livres Profissionalizantes &middot; <strong>Lei nº 9.394/1996 (LDB)</strong> &middot; Certificado Válido em Todo o Território Nacional</span>
        <span class="sm:hidden truncate">Cursos amparados pela <strong>Lei nº 9.394/96 (LDB)</strong> &middot; Válidos em Todo o Brasil</span>
    </div>

    {{-- Navbar / Cabeçalho --}}
    <header class="sticky top-0 z-50 bg-slate-950/98 backdrop-blur-md border-b border-slate-800 transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-3">
            {{-- Logo e Identificação da Escola --}}
            <a href="/" class="flex items-center gap-2.5 sm:gap-3.5 group min-w-0 shrink">
                @if ($school->logo_url)
                    <img src="{{ $school->logo_url }}" alt="{{ $school->trade_name ?: $school->legal_name }}" class="h-9 sm:h-12 w-auto max-w-[130px] sm:max-w-[200px] object-contain rounded transition-transform duration-200 group-hover:scale-105 shrink-0" />
                @else
                    <div class="h-9 w-9 sm:h-11 sm:w-11 rounded-xl bg-school-brand flex items-center justify-center text-lg sm:text-2xl font-black shadow-school-brand shrink-0">
                        🏫
                    </div>
                @endif
                <div class="flex flex-col min-w-0 {{ $school->logo_url ? 'hidden md:flex' : 'flex' }}">
                    <span class="text-xs sm:text-base font-black tracking-tight text-white group-hover:text-school-brand transition truncate">
                        {{ $school->trade_name ?: $school->legal_name }}
                    </span>
                    @if ($school->city)
                        <span class="text-[10px] sm:text-xs font-semibold text-slate-400 hidden sm:flex items-center gap-1 truncate">
                            <svg class="w-3.5 h-3.5 text-school-brand shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                            {{ $school->neighborhood ? $school->neighborhood . ', ' : '' }}{{ $school->city }} - {{ $school->state }}
                        </span>
                    @endif
                </div>
            </a>

            {{-- Atalho para voltar à vitrine principal --}}
            <a href="/" class="hidden md:inline-flex items-center gap-2 text-sm font-bold text-slate-300 hover:text-school-brand transition shrink-0">
                <span>&larr;</span>
                <span>Todas as Formações</span>
            </a>

            {{-- Botões de Acesso (WhatsApp + Portal) --}}
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                @if ($school->clean_whatsapp)
                    <a
                        href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Vi a formação ' . $formation->name . ' no site da escola e gostaria de mais informações.') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition shadow-sm"
                        title="Falar no WhatsApp"
                    >
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        <span>WhatsApp</span>
                    </a>
                @endif

                <a
                    href="/"
                    class="md:hidden inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white transition"
                    title="Voltar ao início"
                >
                    <span>&larr;</span>
                    <span>Cursos</span>
                </a>

                <a
                    href="/aluno/login"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-school-brand hover:opacity-95 font-black text-xs transition shadow-school-brand whitespace-nowrap"
                    title="Acesso exclusivo dos alunos matriculados"
                >
                    <span>🎓</span>
                    <span class="hidden sm:inline">Portal do Aluno</span>
                    <span class="sm:hidden">Sou Aluno</span>
                </a>
            </div>
        </div>
    </header>

    {{-- Breadcrumbs --}}
    <div class="bg-slate-900/60 border-b border-slate-800/80 py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-2 text-xs font-medium text-slate-400">
            <a href="/" class="hover:text-school-brand transition">Início</a>
            <span>&rsaquo;</span>
            <a href="/#formacoes" class="hover:text-school-brand transition">Formações</a>
            <span>&rsaquo;</span>
            <span class="text-white font-semibold truncate">{{ $formation->name }}</span>
        </div>
    </div>

    {{-- Feedback de Mensagem Enviada (Flash) --}}
    @if (session('success'))
        <div class="max-w-7xl mx-auto mt-6 px-4 sm:px-6 lg:px-8">
            <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 flex items-center gap-3">
                <span class="text-2xl">🎉</span>
                <div class="text-sm">
                    <strong>Solicitação Confirmada!</strong> {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    {{-- Conteúdo Principal: Duas Colunas (Ementa & Conteúdo + Card Lateral Fixo de Inscrição) --}}
    <main class="py-10 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

                {{-- COLUNA PRINCIPAL ESQUERDA (8 Colunas): Conteúdo, Detalhes e Ementa --}}
                <div class="lg:col-span-8 space-y-10">

                    {{-- Hero do Curso --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            @if ($formation->highlight_badge)
                                <span class="px-3 py-1 rounded-full bg-school-brand text-xs font-black uppercase tracking-wider shadow-md shadow-school-brand">
                                    ⭐ {{ $formation->highlight_badge }}
                                </span>
                            @endif

                            @if ($formation->has_certificate)
                                <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-xs font-bold">
                                    Certificado Incluso (LDB 9.394/96)
                                </span>
                            @endif

                            <span class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/30 text-xs font-bold">
                                Formação Presencial Prática
                            </span>

                            @if ($school->show_satisfaction_rating)
                                <span class="px-3 py-1 rounded-full bg-school-brand-soft border border-school-brand/30 text-school-brand text-xs font-bold flex items-center gap-1">
                                    <span>★</span>
                                    <span>Satisfação: {{ $school->satisfaction_score }}</span>
                                </span>
                            @endif
                        </div>

                        <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                            {{ $formation->name }}
                        </h1>

                        @if ($formation->short_description)
                            <p class="mt-4 text-base sm:text-lg text-slate-300 leading-relaxed font-normal">
                                {{ $formation->short_description }}
                            </p>
                        @endif
                    </div>

                    {{-- Imagem de Capa em Destaque --}}
                    <div class="rounded-3xl overflow-hidden border border-slate-800 bg-slate-900 shadow-2xl relative">
                        @if ($formation->image_url)
                            <img
                                src="{{ $formation->image_url }}"
                                alt="{{ $formation->name }}"
                                class="w-full h-72 sm:h-96 object-cover"
                            />
                        @else
                            <div class="w-full h-64 sm:h-80 bg-gradient-to-tr from-slate-950 via-slate-900 to-slate-900 flex flex-col items-center justify-center p-8 text-center">
                                <div class="h-16 w-16 rounded-3xl bg-school-brand-soft border border-school-brand/30 flex items-center justify-center text-school-brand text-3xl font-black mb-3 shadow-inner">
                                    🎓
                                </div>
                                <span class="text-base font-bold text-white">Formação Profissionalizante Completa</span>
                                <span class="text-xs text-slate-400 mt-1">Metodologia 100% prática em laboratórios modernos</span>
                            </div>
                        @endif

                        {{-- Barra de Métricas Rápidas abaixo da Foto --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 p-4 bg-slate-900/95 border-t border-slate-800 text-center">
                            <div class="p-2">
                                <div class="text-xs text-slate-400 font-medium">Carga Horária</div>
                                <div class="text-base sm:text-lg font-black text-school-brand font-mono mt-0.5">
                                    {{ $formation->total_workload }} horas
                                </div>
                            </div>
                            <div class="p-2 border-l border-slate-800">
                                <div class="text-xs text-slate-400 font-medium">Duração Estimada</div>
                                <div class="text-base sm:text-lg font-black text-blue-400 font-mono mt-0.5">
                                    {{ $formation->duration_months ?: 'Consulte' }} {{ $formation->duration_months ? 'meses' : '' }}
                                </div>
                            </div>
                            <div class="p-2 border-l border-slate-800">
                                <div class="text-xs text-slate-400 font-medium">Módulos Inclusos</div>
                                <div class="text-base sm:text-lg font-black text-emerald-400 font-mono mt-0.5">
                                    {{ $formation->courses->count() }} cursos
                                </div>
                            </div>
                            <div class="p-2 border-l border-slate-800">
                                <div class="text-xs text-slate-400 font-medium">Metodologia</div>
                                <div class="text-base sm:text-lg font-black text-purple-400 font-mono mt-0.5">
                                    100% Prática
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Seção: Sobre a Formação & Objetivos --}}
                    @if ($formation->description)
                        <section class="p-8 rounded-3xl bg-slate-900/80 border border-slate-800">
                            <h2 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2.5">
                                <span class="text-amber-400">📖</span>
                                <span>Sobre esta Formação</span>
                            </h2>
                            <div class="mt-4 text-slate-300 text-sm sm:text-base leading-relaxed whitespace-pre-line font-normal">
                                {{ $formation->description }}
                            </div>
                        </section>
                    @endif

                    {{-- Seção: Grade Curricular Completa / Ementa dos Módulos --}}
                    <section class="p-8 rounded-3xl bg-slate-900/80 border border-slate-800">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <span class="text-xs font-bold text-amber-400 tracking-wider uppercase">Conteúdo Programático</span>
                                <h2 class="text-xl sm:text-2xl font-black text-white mt-0.5">
                                    Grade Curricular & Módulos da Trilha
                                </h2>
                            </div>
                            <span class="text-xs font-mono font-bold px-3 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-300">
                                {{ $formation->courses->count() }} {{ $formation->courses->count() === 1 ? 'módulo' : 'módulos' }}
                            </span>
                        </div>

                        <p class="text-xs sm:text-sm text-slate-400 mb-6 leading-relaxed">
                            A trilha pedagógica foi desenhada em módulos progressivos. Você aprende desde os fundamentos básicos até as práticas avançadas exigidas pelas empresas no dia a dia.
                        </p>

                        @if ($formation->courses->isEmpty())
                            <div class="text-center py-8 text-slate-500 text-xs">
                                Grade curricular em processo de atualização para o próximo período.
                            </div>
                        @else
                            <div class="space-y-3.5">
                                @foreach ($formation->courses as $course)
                                    <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800/80 hover:border-slate-700 transition">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-start gap-3">
                                                <div class="h-8 w-8 rounded-xl bg-amber-500/10 border border-amber-500/25 flex items-center justify-center font-mono font-black text-sm text-amber-400 shrink-0 mt-0.5">
                                                    {{ $loop->iteration }}
                                                </div>
                                                <div>
                                                    <h3 class="text-base font-bold text-white">
                                                        {{ $course->name }}
                                                    </h3>
                                                    @if ($course->description)
                                                        <p class="text-xs sm:text-sm text-slate-300 mt-1.5 leading-relaxed">
                                                            {{ $course->description }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($course->workload)
                                                <span class="px-3 py-1 rounded-lg bg-slate-900 border border-slate-800 text-xs font-mono text-slate-300 shrink-0 font-semibold">
                                                    {{ $course->workload }}h
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    {{-- Diferenciais Pedagógicos da Escola --}}
                    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                            <span class="text-2xl">💻</span>
                            <h3 class="font-bold text-white text-sm mt-3">1 Aluno por Computador</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Você pratica 100% do tempo no seu próprio computador, sem precisar revezar com ninguém.</p>
                        </div>
                        <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                            <span class="text-2xl">👨‍🏫</span>
                            <h3 class="font-bold text-white text-sm mt-3">Professor em Sala</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Instrutores presenciais dedicados a tirar suas dúvidas individuais no momento em que elas surgem.</p>
                        </div>
                        <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                            <span class="text-2xl">📜</span>
                            <h3 class="font-bold text-white text-sm mt-3">Certificado Nacional</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Válido em todo o Brasil (LDB Lei nº 9.394/96) para processos seletivos e horas complementares.</p>
                        </div>
                    </section>

                </div>

                {{-- COLUNA LATERAL DIREITA (4 Colunas): Card Fixo de Inscrição / Lead Capture --}}
                <div class="lg:col-span-4 sticky top-28">
                    <div class="rounded-3xl bg-slate-900 border border-slate-700/80 p-6 sm:p-8 shadow-2xl space-y-6">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Matrículas Abertas
                            </span>
                            <h3 class="text-xl font-black text-white mt-2.5">
                                Garanta sua Vaga nesta Formação
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">
                                Preencha abaixo para receber valores, horários e condições especiais diretamente no seu WhatsApp:
                            </p>
                        </div>

                        {{-- Feedback de Validação / Erros e Sucesso --}}
                        @if ($errors->any())
                            <div class="mb-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
                                <div class="font-bold flex items-center gap-1.5 mb-1.5 text-rose-200">
                                    <span>⚠️</span>
                                    <span>Atenção aos dados informados:</span>
                                </div>
                                <ul class="list-disc list-inside space-y-1 text-[11px]">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="mb-4 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
                                <span>✓</span>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif

                        {{-- Formulário de Lead Integrado ao CRM da Escola --}}
                        <form method="POST" action="/vitrine/lead" class="space-y-4">
                            @csrf
                            <input type="hidden" name="school_id" value="{{ $school->id }}">
                            <input type="hidden" name="formation_id" value="{{ $formation->id }}">

                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">Nome Completo *</label>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    placeholder="Ex: Lucas Silva"
                                    class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-school-brand transition"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">WhatsApp / Celular com DDD *</label>
                                <input
                                    type="tel"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    required
                                    x-data
                                    @input="
                                        let v = $el.value.replace(/\D/g, '').slice(0, 11);
                                        if (v.length > 10) {
                                            $el.value = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                                        } else if (v.length > 6) {
                                            $el.value = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
                                        } else if (v.length > 2) {
                                            $el.value = v.replace(/^(\d{2})(\d*)$/, '($1) $2');
                                        } else {
                                            $el.value = v;
                                        }
                                    "
                                    placeholder="(85) 99999-9999"
                                    class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-school-brand transition"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">E-mail (Opcional)</label>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="lucas@exemplo.com"
                                    class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-school-brand transition"
                                />
                            </div>

                            <button
                                type="submit"
                                class="w-full py-4 rounded-xl bg-school-brand hover:opacity-95 font-black text-sm uppercase tracking-wide transition shadow-xl shadow-school-brand cursor-pointer"
                            >
                                Quero Saber Valores & Horários
                            </button>
                        </form>

                        {{-- Botão de WhatsApp Direto --}}
                        @if ($school->clean_whatsapp)
                            <div class="pt-2 border-t border-slate-800">
                                <a
                                    href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Gostaria de consultar os valores e turmas para a formação: ' . $formation->name) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-full py-3 px-4 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition flex items-center justify-center gap-2"
                                >
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    <span>Tirar Dúvidas no WhatsApp</span>
                                </a>
                            </div>
                        @endif

                        <div class="text-[11px] text-slate-500 text-center space-y-1">
                            <p>🔒 Atendimento direto da secretaria da escola.</p>
                            <p>Em total conformidade com a LGPD.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    {{-- Seção: Outras Formações que Podem te Interessar --}}
    @if ($relatedFormations->isNotEmpty())
        <section class="py-16 bg-slate-900/60 border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <span class="text-xs font-bold text-amber-400 tracking-wider uppercase">Mais Oportunidades</span>
                        <h2 class="text-2xl font-black text-white mt-0.5">
                            Outras Formações Disponíveis
                        </h2>
                    </div>
                    <a href="/#formacoes" class="text-xs font-bold text-amber-400 hover:text-amber-300 transition">
                        Ver Catálogo Completo &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($relatedFormations as $rel)
                        <div class="rounded-2xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition flex flex-col justify-between overflow-hidden">
                            <div class="p-6">
                                <span class="text-xs font-mono text-amber-400 font-bold">
                                    {{ $rel->total_workload }}h &middot; {{ $rel->courses->count() }} módulos
                                </span>
                                <h3 class="text-lg font-bold text-white mt-1">
                                    {{ $rel->name }}
                                </h3>
                                @if ($rel->short_description)
                                    <p class="text-xs text-slate-400 mt-2 line-clamp-2">
                                        {{ $rel->short_description }}
                                    </p>
                                @endif
                            </div>
                            <div class="p-4 bg-slate-950/60 border-t border-slate-800/80">
                                <a
                                    href="/formacao/{{ $rel->id }}"
                                    class="inline-flex items-center justify-center w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition"
                                >
                                    Conhecer Formação &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Rodapé Institucional --}}
    <footer class="bg-slate-950 border-t border-slate-800 text-slate-400 text-xs py-10 pb-28 sm:pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                <div>
                    <p class="font-bold text-white text-sm">
                        {{ $school->legal_name }}
                    </p>
                    <p class="text-slate-400 mt-1 font-mono">
                        CNPJ: {{ $school->cnpj }}
                    </p>
                    @if ($school->street)
                        <p class="text-slate-500 mt-0.5">
                            {{ $school->street }}, {{ $school->number ?: 'S/N' }} &middot; {{ $school->neighborhood }} &middot; {{ $school->city }}/{{ $school->state }}
                        </p>
                    @endif
                    <div class="mt-3">
                        <a href="/login" class="text-slate-500 hover:text-amber-400 transition inline-flex items-center gap-1.5 text-xs font-semibold">
                            <span class="text-slate-600">🔒</span>
                            <span>Acesso da Equipe / Painel Administrativo</span>
                        </a>
                    </div>
                </div>

                {{-- Selo de Tecnologia Aurosoft --}}
                <div class="flex flex-col items-center md:items-end gap-1.5">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500">Tecnologia educacional:</span>
                        <a href="https://aurosoft.com.br" target="_blank" class="inline-flex items-center gap-1.5 text-slate-300 hover:text-amber-400 transition font-black">
                            <span class="inline-flex h-4 w-4 items-center justify-center rounded bg-amber-500 text-slate-950 text-[10px]">A</span>
                            <span>AUROSOFT</span>
                        </a>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] text-slate-500">
                        <a href="/termos" class="hover:text-slate-400 transition">Termos de Uso</a>
                        <span>&middot;</span>
                        <a href="/privacidade" class="hover:text-slate-400 transition">Privacidade & LGPD</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    {{-- Botão Flutuante do WhatsApp --}}
    @if ($school->clean_whatsapp)
        <aside class="fixed bottom-6 right-6 z-40 flex items-center">
            <a
                href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Gostaria de tirar dúvidas sobre a formação: ' . $formation->name) }}"
                target="_blank"
                rel="noopener noreferrer"
                onclick="trackSchoolEvent('cta_whatsapp', 'Botão Flutuante WhatsApp', {{ $formation->id }})"
                class="group flex items-center gap-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs sm:text-sm px-4 py-3 rounded-full shadow-2xl shadow-emerald-500/40 hover:shadow-emerald-500/60 transition-all duration-300 hover:scale-105"
                title="Tirar dúvidas no WhatsApp"
            >
                <div class="relative flex items-center">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-950 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-950"></span>
                    </span>
                </div>
                <span class="hidden sm:inline">Dúvidas? Fale Conosco</span>
            </a>
        </aside>
    @endif

    <!-- Motor Nativo de Métricas da Escola (100% LGPD) -->
    <script>
        function trackSchoolEvent(eventType, eventLabel, formationId) {
            try {
                const params = new URLSearchParams(window.location.search);
                const payload = JSON.stringify({
                    scope: 'school_showcase',
                    school_id: {{ $school->id }},
                    formation_id: formationId || {{ $formation->id }},
                    event_type: eventType,
                    event_label: eventLabel || '',
                    utm_source: params.get('utm_source') || '',
                    utm_campaign: params.get('utm_campaign') || ''
                });
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('/api/v1/portal/track', new Blob([payload], { type: 'application/json' }));
                } else {
                    fetch('/api/v1/portal/track', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: payload,
                        keepalive: true
                    }).catch(function() {});
                }
            } catch (e) {}
        }
    </script>
</body>
</html>
