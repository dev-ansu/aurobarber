<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $school->trade_name ?: $school->legal_name }} &middot; Cursos Profissionalizantes</title>
    <meta name="description" content="{{ $school->hero_subheadline ?: 'Cursos profissionalizantes com foco prático no mercado de trabalho e certificação reconhecida.' }}">

    @if ($school->logo_url)
        <link rel="icon" type="image/png" href="{{ $school->logo_url }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
        .hero-mesh {
            background-color: #0b1120;
            background-image: 
                radial-gradient(at 0% 0%, rgba(var(--brand-rgb), 0.22) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(var(--brand-rgb), 0.10) 0px, transparent 50%);
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
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950" x-data="{ leadModalOpen: {{ $errors->any() ? 'true' : 'false' }}, selectedFormationId: '{{ old('formation_id', '') }}', selectedFormationName: '' }">

    {{-- Top Notification Bar / Aviso Oficial LDB --}}
    <div class="bg-school-brand text-[11px] sm:text-xs font-bold py-2 px-3 sm:px-4 text-center select-none shadow-sm flex items-center justify-center gap-1.5 sm:gap-2">
        <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-slate-950 text-school-brand text-[10px] font-black shrink-0">✓</span>
        <span class="hidden sm:inline">Cursos Livres e Profissionalizantes devidamente amparados pela <strong>Lei nº 9.394/1996 (LDB)</strong> e <strong>Decreto nº 5.154/2004</strong> &middot; Certificados Válidos em Todo o Brasil</span>
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

            {{-- Links de Navegação (Desktop) --}}
            <nav class="hidden md:flex items-center gap-7 text-sm font-semibold text-slate-300">
                <a href="#formacoes" class="hover:text-school-brand transition">Formações & Cursos</a>
                <a href="#diferenciais" class="hover:text-school-brand transition">Diferenciais</a>
                <a href="#faq" class="hover:text-school-brand transition">Dúvidas Frequentes</a>
                @if ($school->show_network_units && count($networkUnits) > 1)
                    <a href="#unidades" class="hover:text-school-brand transition">Polos & Unidades</a>
                @endif
                <a href="#contato" class="hover:text-school-brand transition">Contato</a>
            </nav>

            {{-- Botões de Acesso (WhatsApp + Portal do Aluno) --}}
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                @if ($school->clean_whatsapp)
                    <a
                        href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Vi o site da escola e gostaria de mais informações.') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition shadow-sm"
                        title="Falar com a secretaria no WhatsApp"
                    >
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        <span class="hidden sm:inline">WhatsApp</span>
                    </a>
                @endif

                <a
                    href="/aluno/login"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-school-brand hover:opacity-95 font-black text-xs transition shadow-school-brand whitespace-nowrap"
                    title="Acesso exclusivo dos alunos matriculados"
                >
                    <span>🎓</span>
                    <span>Portal do Aluno</span>
                </a>
            </div>
        </div>
    </header>

    {{-- Hero Section --}}
    <section class="relative hero-mesh pt-14 pb-20 sm:pt-20 sm:pb-28 border-b border-slate-800 overflow-hidden">
        {{-- Brilhos de fundo decorativos --}}
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[800px] h-[350px] bg-gradient-to-tr from-school-brand/20 to-blue-500/10 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                {{-- Badge de Autoridade --}}
                <div class="flex flex-wrap items-center justify-center gap-2.5 mb-6">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-school-brand-soft border border-school-brand/30 text-school-brand text-xs font-bold uppercase tracking-wider">
                        <span class="w-2 h-2 rounded-full bg-school-brand animate-pulse"></span>
                        Matrículas Abertas para Novas Turmas
                    </div>

                    @if ($school->show_satisfaction_rating)
                        <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-bold">
                            <span class="text-amber-400">★</span>
                            <span>Satisfação dos Alunos: <strong class="text-white">{{ $school->satisfaction_score }}</strong></span>
                        </div>
                    @endif
                </div>

                {{-- Headline Principal --}}
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight">
                    {{ $school->hero_headline ?: 'Formações Profissionais com Foco Prático e Alta Empregabilidade' }}
                </h1>

                {{-- Subheadline --}}
                <p class="mt-6 text-base sm:text-lg text-slate-300 leading-relaxed font-normal">
                    {{ $school->hero_subheadline ?: 'Prepare-se para o mercado com formações completas, metodologia 100% prática em laboratórios modernos, instrutores atuantes e certificação válida em todo o Brasil.' }}
                </p>

                {{-- CTA Buttons --}}
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3.5">
                    <a
                        href="#formacoes"
                        class="w-full sm:w-auto px-7 py-4 rounded-xl bg-school-brand hover:opacity-95 font-black text-sm tracking-wide transition shadow-xl shadow-school-brand flex items-center justify-center gap-2"
                    >
                        <span>Conhecer as Formações</span>
                        <svg class="w-4 h-4 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                    </a>

                    @if ($school->clean_whatsapp)
                        <a
                            href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Gostaria de falar com um consultor pedagógico sobre os cursos disponíveis.') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full sm:w-auto px-7 py-4 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-white border border-slate-700/80 font-bold text-sm tracking-wide transition shadow-lg flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5 text-emerald-400 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            <span>Falar no WhatsApp com a Secretaria</span>
                        </a>
                    @endif
                </div>

                {{-- Números / Prova Social --}}
                <div class="mt-14 grid grid-cols-2 md:grid-cols-4 gap-4 pt-10 border-t border-slate-800/80">
                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                        <div class="text-2xl sm:text-3xl font-black text-school-brand font-mono">
                            {{ $socialProofStudents }}
                        </div>
                        <div class="text-xs text-slate-400 mt-1 font-medium">Alunos Capacitados</div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                        <div class="text-2xl sm:text-3xl font-black text-blue-400 font-mono">
                            {{ count($networkUnits) > 1 ? count($networkUnits) . ' Polos' : 'Unidade Própria' }}
                        </div>
                        <div class="text-xs text-slate-400 mt-1 font-medium">Na Região</div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                        <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono">100%</div>
                        <div class="text-xs text-slate-400 mt-1 font-medium">Aulas Práticas no Lab</div>
                    </div>

                    @if ($school->show_satisfaction_rating)
                        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                            <div class="text-2xl sm:text-3xl font-black text-amber-400 font-mono">
                                {{ $school->satisfaction_score }}
                            </div>
                            <div class="text-xs text-slate-400 mt-1 font-medium">Satisfação dos Alunos</div>
                        </div>
                    @else
                        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                            <div class="text-2xl sm:text-3xl font-black text-purple-400 font-mono">LDB 9.394</div>
                            <div class="text-xs text-slate-400 mt-1 font-medium">Certificado Nacional</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Feedback de Mensagem Enviada (Flash) --}}
    @if (session('success'))
        <div class="max-w-3xl mx-auto mt-6 px-4">
            <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 flex items-center gap-3">
                <span class="text-2xl">🎉</span>
                <div class="text-sm">
                    <strong>Sucesso!</strong> {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    {{-- Seção das Formações / Vitrine de Cursos --}}
    <section id="formacoes" class="py-16 sm:py-24 bg-slate-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
                <div>
                    <span class="text-xs font-bold text-school-brand tracking-wider uppercase">Trilhas de Aprendizagem</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight mt-1">
                        Formações Profissionalizantes Disponíveis
                    </h2>
                    <p class="text-sm sm:text-base text-slate-400 mt-2 max-w-2xl">
                        Cada formação é composta por módulos progressivos que levam você do nível básico ao avançado, garantindo o domínio prático exigido pelas empresas.
                    </p>
                </div>
                <div class="text-xs text-slate-400 font-mono bg-slate-900/80 px-3.5 py-2 rounded-xl border border-slate-800 shrink-0">
                    {{ count($formations) }} {{ count($formations) === 1 ? 'formação disponível' : 'formações disponíveis' }}
                </div>
            </div>

            @if ($formations->isEmpty())
                <div class="text-center py-16 bg-slate-900/40 rounded-3xl border border-slate-800 p-8">
                    <span class="text-4xl">📚</span>
                    <h3 class="text-lg font-bold text-white mt-3">Novas turmas sendo organizadas</h3>
                    <p class="text-sm text-slate-400 mt-1">Entre em contato diretamente com nossa secretaria para consultar o calendário do próximo período.</p>
                    @if ($school->clean_whatsapp)
                        <a
                            href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Gostaria de consultar o calendário das próximas turmas.') }}"
                            target="_blank"
                            class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 rounded-xl bg-school-brand hover:opacity-95 font-bold text-xs"
                        >
                            Falar com a Secretaria
                        </a>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($formations as $formation)
                        <div class="group rounded-3xl bg-slate-900/70 border border-slate-800 hover:border-school-brand/50 transition-all duration-300 flex flex-col overflow-hidden shadow-xl hover:shadow-2xl hover:shadow-school-brand/5 hover:-translate-y-1">
                            {{-- Capa / Imagem da Formação --}}
                            <a href="/formacao/{{ $formation->id }}" class="block relative h-48 sm:h-52 w-full bg-slate-800 overflow-hidden">
                                @if ($formation->image_url)
                                    <img
                                        src="{{ $formation->image_url }}"
                                        alt="{{ $formation->name }}"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                @else
                                    {{-- Placeholder Moderno caso a escola ainda não tenha enviado imagem --}}
                                    <div class="h-full w-full bg-gradient-to-tr from-slate-900 via-slate-800 to-slate-900 flex flex-col items-center justify-center p-6 text-center">
                                        <div class="h-12 w-12 rounded-2xl bg-school-brand-soft border border-school-brand/30 flex items-center justify-center text-school-brand text-xl font-black mb-2 shadow-inner">
                                            🎓
                                        </div>
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Formação Profissional</span>
                                    </div>
                                @endif

                                {{-- Selo de Destaque --}}
                                @if ($formation->highlight_badge)
                                    <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-school-brand text-xs font-black shadow-lg shadow-school-brand/30 tracking-wide uppercase">
                                        ⭐ {{ $formation->highlight_badge }}
                                    </span>
                                @endif

                                {{-- Badges Rápidas sobre a Imagem --}}
                                <div class="absolute bottom-3 right-3 flex items-center gap-1.5">
                                    @if ($formation->has_certificate)
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-950/80 backdrop-blur text-emerald-400 border border-emerald-500/30 text-[11px] font-bold shadow">
                                            Certificado Incluso
                                        </span>
                                    @endif
                                </div>
                            </a>

                            {{-- Conteúdo do Card --}}
                            <div class="p-6 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-white group-hover:text-school-brand transition leading-snug">
                                        <a href="/formacao/{{ $formation->id }}">
                                            {{ $formation->name }}
                                        </a>
                                    </h3>

                                    @if ($formation->short_description)
                                        <p class="text-xs sm:text-sm text-slate-300 mt-2.5 leading-relaxed">
                                            {{ $formation->short_description }}
                                        </p>
                                    @elseif ($formation->description)
                                        <p class="text-xs text-slate-400 mt-2.5 line-clamp-2 leading-relaxed">
                                            {{ \Illuminate\Support\Str::limit($formation->description, 140) }}
                                        </p>
                                    @endif

                                    {{-- Métricas da Formação (Carga Horária, Duração e Qtd Cursos) --}}
                                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-300">
                                        @if ($formation->total_workload > 0)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800/80 border border-slate-700/60 font-mono">
                                                <svg class="w-3.5 h-3.5 text-school-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ $formation->total_workload }}h de carga horária
                                            </span>
                                        @endif

                                        @if ($formation->duration_months)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800/80 border border-slate-700/60 font-mono">
                                                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                {{ $formation->duration_months }} meses
                                            </span>
                                        @endif

                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800/80 border border-slate-700/60 font-mono">
                                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            {{ $formation->courses->count() }} {{ $formation->courses->count() === 1 ? 'módulo' : 'módulos' }}
                                        </span>
                                    </div>

                                    {{-- Módulos e Cursos Inclusos (Lista Interativa) --}}
                                    @if ($formation->courses->isNotEmpty())
                                        <div class="mt-5 pt-4 border-t border-slate-800/80" x-data="{ expanded: false }">
                                            <div class="flex items-center justify-between text-xs font-bold text-slate-300 mb-2.5">
                                                <span class="flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-school-brand"></span>
                                                    Módulos desta formação:
                                                </span>
                                                @if ($formation->courses->count() > 3)
                                                    <button
                                                        type="button"
                                                        @click="expanded = !expanded"
                                                        class="text-[11px] text-school-brand hover:opacity-80 font-semibold cursor-pointer"
                                                        x-text="expanded ? 'Recolher' : 'Ver todos (' + {{ $formation->courses->count() }} + ')'"
                                                    ></button>
                                                @endif
                                            </div>

                                            <div class="space-y-1.5">
                                                @foreach ($formation->courses as $index => $course)
                                                    <div
                                                        class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/60 text-xs"
                                                        x-show="expanded || {{ $index }} < 3"
                                                    >
                                                        <div class="flex items-center gap-2 truncate">
                                                            <span class="w-5 h-5 rounded-md bg-slate-800 flex items-center justify-center font-mono text-[10px] text-school-brand shrink-0 font-bold">
                                                                {{ $loop->iteration }}
                                                            </span>
                                                            <span class="text-slate-200 truncate font-medium">{{ $course->name }}</span>
                                                        </div>
                                                        @if ($course->workload)
                                                            <span class="text-[11px] font-mono text-slate-400 shrink-0 ml-2">{{ $course->workload }}h</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Botões de Ação do Card --}}
                                <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center gap-2">
                                    <a
                                        href="/formacao/{{ $formation->id }}"
                                        class="px-3.5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-bold text-xs transition text-center whitespace-nowrap"
                                        title="Ver ementa completa e detalhes da formação"
                                    >
                                        Ver Ementa
                                    </a>

                                    <button
                                        type="button"
                                        @click="leadModalOpen = true; selectedFormationId = '{{ $formation->id }}'; selectedFormationName = '{{ addslashes($formation->name) }}'"
                                        class="flex-1 px-3 py-3 rounded-xl bg-school-brand hover:opacity-95 font-black text-xs uppercase tracking-wider transition shadow-md shadow-school-brand cursor-pointer text-center"
                                    >
                                        Inscrever-se
                                    </button>

                                    @if ($school->clean_whatsapp)
                                        <a
                                            href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Gostaria de mais informações sobre a formação: ' . $formation->name) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="p-3 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 transition shadow-sm shrink-0"
                                            title="Tirar dúvidas sobre esta formação no WhatsApp"
                                        >
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Diferenciais & Estrutura --}}
    <section id="diferenciais" class="py-16 sm:py-24 bg-slate-900/50 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold text-school-brand tracking-wider uppercase">Por que Escolher Nossa Escola</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight mt-1">
                    Metodologia que Coloca Você à Frente
                </h2>
                <p class="text-sm sm:text-base text-slate-400 mt-2">
                    Estrutura planejada para quem precisa de resultados rápidos e aprendizado real sem enrolação teórica.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800">
                    <div class="h-12 w-12 rounded-2xl bg-school-brand-soft border border-school-brand flex items-center justify-center text-school-brand text-2xl mb-5">
                        💻
                    </div>
                    <h3 class="text-lg font-bold text-white">Laboratórios Individuais Modernos</h3>
                    <p class="text-xs sm:text-sm text-slate-400 mt-2 leading-relaxed">
                        Cada aluno conta com um computador individual e softwares profissionais atualizados. Sem revezamento de máquinas: você pratica 100% do tempo.
                    </p>
                </div>

                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800">
                    <div class="h-12 w-12 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 text-2xl mb-5">
                        👨‍🏫
                    </div>
                    <h3 class="text-lg font-bold text-white">Educadores Dedicados em Sala</h3>
                    <p class="text-xs sm:text-sm text-slate-400 mt-2 leading-relaxed">
                        Instrutores presenciais acompanham passo a passo a sua evolução, tirando dúvidas individuais no momento exato em que elas surgem.
                    </p>
                </div>

                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800">
                    <div class="h-12 w-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-2xl mb-5">
                        📜
                    </div>
                    <h3 class="text-lg font-bold text-white">Certificado Reconhecido Nacionalmente</h3>
                    <p class="text-xs sm:text-sm text-slate-400 mt-2 leading-relaxed">
                        Certificação profissional amparada pela LDB (Lei 9.394/96), válida para comprovação curricular, processos seletivos e horas complementares.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Seção de Unidades / Polos da Rede (Se habilitado) --}}
    @if ($school->show_network_units && count($networkUnits) > 0)
        <section id="unidades" class="py-16 sm:py-24 bg-slate-950 border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-xs font-bold text-school-brand tracking-wider uppercase">Presença & Acessibilidade</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight mt-1">
                        Nossas Unidades & Polos
                    </h2>
                    <p class="text-sm sm:text-base text-slate-400 mt-2">
                        Venha conhecer nossas instalações de perto e tomar um café com nossa equipe pedagógica.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($networkUnits as $unit)
                        <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-base font-bold text-white">
                                        {{ $unit->trade_name ?: $unit->legal_name }}
                                    </h3>
                                    @if ($unit->is_headquarters)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-school-brand-soft text-school-brand border border-school-brand">
                                            Matriz
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-4 space-y-2 text-xs text-slate-300">
                                    @if ($unit->street)
                                        <p class="flex items-start gap-2">
                                            <span class="text-amber-400 shrink-0">📍</span>
                                            <span>
                                                {{ $unit->street }}, {{ $unit->number ?: 'S/N' }}
                                                @if ($unit->complement) ({{ $unit->complement }}) @endif
                                                <br>
                                                {{ $unit->neighborhood }} &middot; {{ $unit->city }} - {{ $unit->state }}
                                                @if ($unit->postal_code) <br><span class="font-mono text-slate-400">CEP: {{ $unit->postal_code }}</span> @endif
                                            </span>
                                        </p>
                                    @endif

                                    @if ($unit->phone)
                                        <p class="flex items-center gap-2">
                                            <span class="text-slate-400 shrink-0">📞</span>
                                            <span class="font-mono text-slate-300">{{ $unit->phone }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if ($unit->clean_whatsapp)
                                <div class="mt-6 pt-4 border-t border-slate-800">
                                    <a
                                        href="https://wa.me/{{ $unit->clean_whatsapp }}?text={{ urlencode('Olá! Gostaria de falar com a unidade ' . ($unit->trade_name ?: $unit->legal_name)) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition"
                                    >
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        <span>Falar com esta Unidade</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Seção de Perguntas Frequentes (FAQ) com Accordion Alpine.js --}}
    <section id="faq" class="py-16 sm:py-24 bg-slate-950 border-t border-slate-800/80">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="text-xs font-bold text-amber-400 tracking-wider uppercase">Tire suas Dúvidas</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight mt-1">
                    Perguntas Frequentes
                </h2>
                <p class="text-sm sm:text-base text-slate-400 mt-3 max-w-2xl mx-auto">
                    Tudo o que você precisa saber sobre nossa metodologia, certificados e aulas práticas.
                </p>
            </div>

            <div class="space-y-3" x-data="{ active: null }">
                {{-- FAQ 1 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden transition-colors duration-200">
                    <button
                        type="button"
                        @click="active = active === 1 ? null : 1"
                        class="w-full p-5 sm:p-6 text-left flex items-center justify-between gap-4 cursor-pointer hover:bg-slate-900/90 transition"
                    >
                        <span class="text-sm sm:text-base font-bold text-white">
                            Preciso ter computador em casa para fazer os cursos?
                        </span>
                        <span class="h-8 w-8 rounded-full bg-slate-800 text-slate-300 flex items-center justify-center shrink-0 transition-transform duration-200 font-bold" :class="{ 'rotate-180 text-amber-400 bg-amber-500/10': active === 1 }">
                            &darr;
                        </span>
                    </button>
                    <div x-show="active === 1" x-cloak class="px-5 sm:px-6 pb-5 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                        Não! Toda a estrutura e os computadores individuais modernos são fornecidos diretamente em nossos laboratórios climatizados durante as suas aulas presenciais.
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden transition-colors duration-200">
                    <button
                        type="button"
                        @click="active = active === 2 ? null : 2"
                        class="w-full p-5 sm:p-6 text-left flex items-center justify-between gap-4 cursor-pointer hover:bg-slate-900/90 transition"
                    >
                        <span class="text-sm sm:text-base font-bold text-white">
                            O certificado é reconhecido e válido em todo o Brasil?
                        </span>
                        <span class="h-8 w-8 rounded-full bg-slate-800 text-slate-300 flex items-center justify-center shrink-0 transition-transform duration-200 font-bold" :class="{ 'rotate-180 text-amber-400 bg-amber-500/10': active === 2 }">
                            &darr;
                        </span>
                    </button>
                    <div x-show="active === 2" x-cloak class="px-5 sm:px-6 pb-5 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                        Sim! Nossos certificados são emitidos com respaldo legal da <strong>Lei de Diretrizes e Bases da Educação Nacional (Lei nº 9.394/1996)</strong> e <strong>Decreto nº 5.154/2004</strong>. São válidos para enriquecer seu currículo, comprovar capacitação profissional e horas complementares em faculdades.
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden transition-colors duration-200">
                    <button
                        type="button"
                        @click="active = active === 3 ? null : 3"
                        class="w-full p-5 sm:p-6 text-left flex items-center justify-between gap-4 cursor-pointer hover:bg-slate-900/90 transition"
                    >
                        <span class="text-sm sm:text-base font-bold text-white">
                            A partir de que idade posso começar a estudar?
                        </span>
                        <span class="h-8 w-8 rounded-full bg-slate-800 text-slate-300 flex items-center justify-center shrink-0 transition-transform duration-200 font-bold" :class="{ 'rotate-180 text-amber-400 bg-amber-500/10': active === 3 }">
                            &darr;
                        </span>
                    </button>
                    <div x-show="active === 3" x-cloak class="px-5 sm:px-6 pb-5 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                        Temos formações para jovens a partir de 12 ou 14 anos que desejam se preparar para o primeiro emprego ou jovem aprendiz, assim como para adultos que buscam atualização rápida ou transição de carreira.
                    </div>
                </div>

                {{-- FAQ 4 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden transition-colors duration-200">
                    <button
                        type="button"
                        @click="active = active === 4 ? null : 4"
                        class="w-full p-5 sm:p-6 text-left flex items-center justify-between gap-4 cursor-pointer hover:bg-slate-900/90 transition"
                    >
                        <span class="text-sm sm:text-base font-bold text-white">
                            Como funcionam os horários das turmas?
                        </span>
                        <span class="h-8 w-8 rounded-full bg-slate-800 text-slate-300 flex items-center justify-center shrink-0 transition-transform duration-200 font-bold" :class="{ 'rotate-180 text-amber-400 bg-amber-500/10': active === 4 }">
                            &darr;
                        </span>
                    </button>
                    <div x-show="active === 4" x-cloak class="px-5 sm:px-6 pb-5 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                        Oferecemos flexibilidade de horários nos turnos da manhã, tarde, noite e aos sábados. Nossos instrutores acompanham você de forma personalizada para que aprenda no seu ritmo ideal.
                    </div>
                </div>

                {{-- FAQ 5 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden transition-colors duration-200">
                    <button
                        type="button"
                        @click="active = active === 5 ? null : 5"
                        class="w-full p-5 sm:p-6 text-left flex items-center justify-between gap-4 cursor-pointer hover:bg-slate-900/90 transition"
                    >
                        <span class="text-sm sm:text-base font-bold text-white">
                            Posso fazer uma aula demonstrativa gratuita?
                        </span>
                        <span class="h-8 w-8 rounded-full bg-slate-800 text-slate-300 flex items-center justify-center shrink-0 transition-transform duration-200 font-bold" :class="{ 'rotate-180 text-amber-400 bg-amber-500/10': active === 5 }">
                            &darr;
                        </span>
                    </button>
                    <div x-show="active === 5" x-cloak class="px-5 sm:px-6 pb-5 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                        Com certeza! Você pode agendar uma visita sem compromisso para conhecer nossos laboratórios e fazer uma aula prática demonstrativa com nossos consultores.
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Seção de Contato & Lead Capture no Rodapé --}}
    <section id="contato" class="py-16 sm:py-20 bg-slate-900 border-t border-slate-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="text-xs font-bold text-school-brand tracking-wider uppercase">Fale Conosco</span>
            <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight mt-1">
                Tire suas Dúvidas ou Agende uma Visita Experimental
            </h2>
            <p class="text-sm sm:text-base text-slate-300 mt-3">
                Conheça nossos laboratórios, converse com nossos educadores e faça uma aula demonstrativa sem compromisso.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <button
                    type="button"
                    @click="leadModalOpen = true; selectedFormationId = ''; selectedFormationName = ''"
                    class="px-8 py-4 rounded-xl bg-school-brand hover:brightness-110 font-black text-sm tracking-wide transition shadow-xl shadow-school-brand cursor-pointer"
                >
                    Solicitar Atendimento
                </button>

                @if ($school->clean_whatsapp)
                    <a
                        href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Gostaria de agendar uma visita para conhecer a escola.') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="px-8 py-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm tracking-wide transition border border-slate-700 flex items-center gap-2"
                    >
                        <svg class="w-5 h-5 text-emerald-400 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        <span>WhatsApp Direto</span>
                    </a>
                @endif
            </div>

            {{-- Redes Sociais da Escola --}}
            @if ($school->instagram || $school->facebook)
                <div class="mt-8 flex items-center justify-center gap-4 text-xs font-semibold text-slate-400">
                    <span>Siga-nos nas redes:</span>
                    @if ($school->instagram)
                        <a
                            href="{{ str_starts_with($school->instagram, 'http') ? $school->instagram : 'https://instagram.com/' . ltrim($school->instagram, '@') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hover:text-amber-400 transition"
                        >
                            Instagram: {{ $school->instagram }}
                        </a>
                    @endif
                    @if ($school->facebook)
                        <a
                            href="{{ str_starts_with($school->facebook, 'http') ? $school->facebook : 'https://' . $school->facebook }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hover:text-amber-400 transition"
                        >
                            Facebook
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>

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

    {{-- Modal de Lead Capture / Pré-Matrícula Interativo em Alpine.js --}}
    <div
        x-cloak
        x-show="leadModalOpen"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            {{-- Backdrop --}}
            <div
                x-show="leadModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"
                @click="leadModalOpen = false"
            ></div>

            {{-- Dialog Box --}}
            <div
                x-show="leadModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative bg-slate-900 border border-slate-700/80 rounded-3xl p-6 sm:p-8 max-w-lg w-full text-left shadow-2xl overflow-hidden z-10"
            >
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <div>
                        <span class="text-xs font-bold text-school-brand uppercase tracking-wider">Garanta sua Vaga</span>
                        <h3 class="text-xl font-black text-white mt-0.5">
                            Quero Conhecer a Formação
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="leadModalOpen = false"
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mt-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
                        <div class="font-bold flex items-center gap-1.5 mb-1.5 text-rose-200">
                            <span>⚠️</span>
                            <span>Por favor, verifique os dados informados:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-[11px]">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mt-4 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
                        <span>✓</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form method="POST" action="/vitrine/lead" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $school->id }}">
                    <input type="hidden" name="formation_id" :value="selectedFormationId">

                    {{-- Formação Selecionada (se houver) --}}
                    <div x-show="selectedFormationName" class="p-3 rounded-xl bg-school-brand-soft border border-school-brand/30 text-school-brand text-xs font-semibold flex items-center gap-2">
                        <span>🎓</span>
                        <span>Formação de interesse: <strong x-text="selectedFormationName"></strong></span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">Seu Nome Completo *</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            placeholder="Ex: Lucas Silva"
                            class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-school-brand transition"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    </div>

                    @if ($formations->isNotEmpty())
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">Formação Desejada</label>
                            <select
                                name="formation_id_select"
                                @change="selectedFormationId = $event.target.value"
                                class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-school-brand transition"
                            >
                                <option value="">Selecione uma formação ou geral...</option>
                                @foreach ($formations as $f)
                                    <option value="{{ $f->id }}" :selected="selectedFormationId == '{{ $f->id }}'">
                                        {{ $f->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full py-4 rounded-xl bg-school-brand hover:opacity-95 font-black text-sm uppercase tracking-wide transition shadow-xl shadow-school-brand cursor-pointer"
                        >
                            Enviar Solicitação de Informações
                        </button>
                    </div>

                    <p class="text-[11px] text-slate-500 text-center">
                        🔒 Seus dados serão utilizados apenas pela secretaria da escola para tirar dúvidas sobre o curso. Em total conformidade com a LGPD.
                    </p>
                </form>
            </div>
        </div>
    </div>

    {{-- Botão Flutuante do WhatsApp --}}
    @if ($school->clean_whatsapp)
        <aside class="fixed bottom-6 right-6 z-40 flex items-center">
            <a
                href="https://wa.me/{{ $school->clean_whatsapp }}?text={{ urlencode('Olá! Vi o site da escola e gostaria de mais informações.') }}"
                target="_blank"
                rel="noopener noreferrer"
                onclick="trackSchoolEvent('cta_whatsapp', 'Botão Flutuante WhatsApp')"
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
                    formation_id: formationId || null,
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
