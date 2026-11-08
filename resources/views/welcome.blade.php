@php
    $currentHost = request()->getHost();
    $isRootDomain = in_array($currentHost, ['aurosoft.com.br', 'www.aurosoft.com.br', 'localhost', '127.0.0.1', '2.25.75.35']);
    $tenantLicense = app()->bound('current_tenant_license') ? app('current_tenant_license') : null;
    $schoolName = $tenantLicense ? ($tenantLicense->trade_name ?: $tenantLicense->client_name) : null;

    // Busca escolas ativas para facilitar o atalho no modal de acesso
    $activeSchools = [];
    try {
        $activeSchools = \App\Models\License::query()
            ->where('type', 'cloud_saas')
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->orderBy('trade_name')
            ->get(['id', 'slug', 'trade_name', 'client_name', 'city', 'state']);
    } catch (\Throwable) {
    }
@endphp
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $schoolName ? $schoolName . ' • Aurosoft' : 'Aurosoft • Sistema Operacional para Escolas Profissionalizantes & Cursos Livres' }}</title>
    <meta name="description" content="Gestão escolar completa, LMS interativo integrado à Ouro Moderno, controle de mensalidades e recibos, totem de presença e secretaria digital amparada na LDB 9.394/96.">
    <meta name="keywords" content="sistema para escola de cursos livres, software gestao escolar profissionalizante, controle de mensalidades escola, lms integrado ouro moderno, certificado ldb 9394, crm escolar captação de alunos, quadro de horarios salas">
    <meta name="author" content="Aurosoft Tecnologia">
    <link rel="canonical" href="https://aurosoft.com.br">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://aurosoft.com.br">
    <meta property="og:title" content="Aurosoft • Sistema de Gestão para Escolas Profissionalizantes">
    <meta property="og:description" content="Gestão escolar completa, CRM de captação de alunos, controle de mensalidades no balcão e 15 documentos emitidos em 1 clique.">
    <meta property="og:image" content="{{ asset('images/landing/painel-controle-dashboard.jpg') }}">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Aurosoft">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://aurosoft.com.br">
    <meta name="twitter:title" content="Aurosoft • Sistema para Escolas Profissionalizantes">
    <meta name="twitter:description" content="Gestão escolar ágil, CRM com WhatsApp e Secretaria Digital amparada na LDB 9.394/96.">
    <meta name="twitter:image" content="{{ asset('images/landing/painel-controle-dashboard.jpg') }}">

    <!-- Schema.org JSON-LD Rich Snippet -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Aurosoft",
      "operatingSystem": "Web Cloud, Windows, macOS, Android, iOS",
      "applicationCategory": "EducationalApplication",
      "description": "Sistema de Gestão Escolar (SaaS ERP) e LMS completo para Escolas de Cursos Livres e Profissionalizantes.",
      "url": "https://aurosoft.com.br",
      "offers": {
        "@type": "Offer",
        "price": "147.00",
        "priceCurrency": "BRL"
      },
      "publisher": {
        "@type": "Organization",
        "name": "Aurosoft Tecnologia Educacional",
        "url": "https://aurosoft.com.br"
      }
    }
    </script>

    <!-- Fonts otimizadas assíncronas (elimina render-blocking e CLS) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap">
    </noscript>

    <!-- CSS Pré-compilado de Alta Performance via Vite (Substitui os 3MB do CDN) -->
    @vite(['resources/css/app.css'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .hero-glow {
            background: radial-gradient(circle at 50% 0%, rgba(245, 158, 11, 0.18) 0%, rgba(99, 102, 241, 0.08) 45%, transparent 70%);
        }
        .card-glass {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .font-mono {
            font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-feature-settings: 'tnum' 1;
        }
        .metric-box-stable {
            min-height: 68px;
            contain: layout;
        }
        .text-gradient-gold {
            background: linear-gradient(135deg, #fef08a 0%, #fbbf24 50%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950 min-h-screen flex flex-col justify-between"
      x-data="{
          leadModalOpen: {{ $errors->any() ? 'true' : 'false' }},
          tenantModalOpen: false,
          activeShowcaseTab: 'secretaria',
          schoolFilter: '',
          calcStudents: 250,
          faqOpen: null
      }">

    {{-- Top Announcement Bar --}}
    <div class="bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 text-slate-950 text-xs font-black py-2 px-4 text-center shadow-md select-none flex items-center justify-center gap-2">
        <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-slate-950 text-amber-400 text-[10px] font-black shrink-0">★</span>
        <span class="truncate">Aurosoft 2.0 Cloud &middot; <strong>LMS Integrado, Totem de Presença & Gestão Escolar com Pix</strong></span>
        <a href="{{ url('/demo') }}" class="hidden md:inline-block underline ml-2 hover:opacity-80 transition font-black whitespace-nowrap">Testar demonstração &rarr;</a>
    </div>

    {{-- Alerta de Sucesso Contato --}}
    @if(session('contact_success'))
        <div class="bg-emerald-500/15 border-b border-emerald-500/30 text-emerald-300 px-4 py-3 text-center text-xs font-bold flex items-center justify-center gap-2 shadow-inner">
            <span class="text-base">🎉</span>
            <span>Sua solicitação foi enviada com sucesso! Um especialista da Aurosoft entrará em contato em breve via WhatsApp.</span>
        </div>
    @endif

    {{-- Navbar Superior --}}
    <header class="sticky top-0 z-40 bg-slate-950/90 backdrop-blur-md border-b border-slate-800/80 transition">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-3 xl:gap-6">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-3 group shrink-0">
                <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-gradient-to-tr from-amber-500 via-amber-400 to-amber-300 flex items-center justify-center text-slate-950 font-black text-xl shadow-lg shadow-amber-500/25 group-hover:scale-105 transition-transform shrink-0">
                    A
                </div>
                <div>
                    <span class="text-sm sm:text-base font-black tracking-tight text-white block group-hover:text-amber-400 transition whitespace-nowrap">
                        Aurosoft <span class="text-amber-400 text-[10px] sm:text-xs font-mono font-bold uppercase ml-1 px-1.5 py-0.5 rounded bg-amber-500/15 border border-amber-500/30">SaaS</span>
                    </span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 font-medium block leading-tight whitespace-nowrap">
                        Gestão Escolar & LMS Cloud
                    </span>
                </div>
            </a>

            {{-- Links de Navegação Desktop --}}
            <nav class="hidden xl:flex items-center gap-5 xl:gap-6 text-xs font-bold text-slate-300 whitespace-nowrap">
                <a href="#recursos" class="hover:text-amber-400 transition">Recursos</a>
                <a href="#modulos" class="hover:text-amber-400 transition">Módulos</a>
                <a href="#calculadora" class="hover:text-amber-400 transition flex items-center gap-1">
                    <span>Calculadora ROI</span>
                    <span class="text-[9px] bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded-full font-black">NOVO</span>
                </a>
                <a href="#comparativo" class="hover:text-amber-400 transition">Por que nós?</a>
                <a href="#faq" class="hover:text-amber-400 transition">Dúvidas</a>
            </nav>

            {{-- Ações Direitas (Botões com proteção anti-quebra) --}}
            <div class="hidden sm:flex items-center gap-2 xl:gap-3 shrink-0">
                <button
                    type="button"
                    @click="tenantModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-700 bg-slate-900/80 hover:bg-slate-800 text-slate-200 text-xs font-bold transition shadow-sm whitespace-nowrap shrink-0"
                >
                    <span>🏫</span>
                    <span>Acessar Escola</span>
                </button>

                <a
                    href="{{ url('/demo') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-indigo-500/40 bg-indigo-950/40 hover:bg-indigo-900/60 text-indigo-300 text-xs font-bold transition shadow-sm whitespace-nowrap shrink-0"
                    title="Acesse o ambiente com dados fictícios para testar sem cadastro"
                >
                    <span>✨</span>
                    <span>Testar Demo</span>
                </a>

                <button
                    type="button"
                    @click="leadModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:brightness-110 text-slate-950 text-xs font-black transition shadow-lg shadow-amber-500/25 active:scale-95 whitespace-nowrap shrink-0"
                >
                    <span>💬</span>
                    <span>Solicitar Demonstração</span>
                </button>
            </div>

            {{-- Botão Mobile --}}
            <div class="flex sm:hidden items-center gap-2">
                <button
                    type="button"
                    @click="leadModalOpen = true"
                    class="px-3 py-2 rounded-xl bg-amber-500 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20"
                >
                    Demonstração
                </button>
            </div>
        </div>
    </header>

    <main id="main-content">
    {{-- HERO SECTION --}}
    <section class="relative hero-glow pt-14 pb-20 sm:pt-24 sm:pb-32 overflow-hidden border-b border-slate-800/80">
        {{-- Efeitos de Luz Decorativos --}}
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[450px] bg-gradient-to-tr from-amber-500/20 to-indigo-500/10 blur-[130px] rounded-full pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-4xl mx-auto space-y-7">
                {{-- Badge de Inovação --}}
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold tracking-wide shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>O Único Ecossistema Integrado para Escolas Profissionalizantes & Cursos Livres</span>
                </div>

                {{-- Headline Principal --}}
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.12]">
                    Transforme sua escola em uma máquina de <span class="text-gradient-gold">captação, ensino prático</span> e lucro previsível.
                </h1>

                {{-- Subheadline --}}
                <p class="text-base sm:text-lg lg:text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed font-normal">
                    LMS integrado à <strong>Ouro Moderno</strong>, controle de <strong>mensalidades e pagamentos via Pix</strong>, totem de presença em 2 segundos e secretaria digital 100% amparada pela <strong>Lei nº 9.394/96 (LDB)</strong>.
                </p>

                {{-- CTAs Principais --}}
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                    <a
                        href="{{ url('/demo') }}"
                        onclick="trackAurosoftEvent('cta_demo', 'Hero Testar Demo')"
                        class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-sm tracking-wide transition shadow-xl shadow-amber-500/25 flex items-center justify-center gap-2.5 active:scale-95 group"
                    >
                        <span>✨</span>
                        <span>Experimentar Demonstração ao Vivo</span>
                        <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </a>

                    <button
                        type="button"
                        onclick="trackAurosoftEvent('cta_lead_modal', 'Hero Solicitar Demonstracao')"
                        @click="leadModalOpen = true"
                        class="w-full sm:w-auto px-8 py-4 rounded-2xl border border-slate-700 bg-slate-900/90 hover:bg-slate-800/90 text-white font-bold text-sm tracking-wide transition shadow-lg flex items-center justify-center gap-2.5 active:scale-95"
                    >
                        <span>💬</span>
                        <span>Falar com um Especialista</span>
                    </button>
                </div>

                {{-- Selos de Confiança / Badges de Autoridade --}}
                <div class="pt-8 border-t border-slate-800/60 grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto text-left">
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                        <div class="text-base sm:text-lg font-black text-amber-400 font-mono">99.9%</div>
                        <div class="text-[11px] text-slate-400 font-medium mt-0.5">Disponibilidade Cloud</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                        <div class="text-base sm:text-lg font-black text-emerald-400 font-mono">2s</div>
                        <div class="text-[11px] text-slate-400 font-medium mt-0.5">Presença no Totem</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                        <div class="text-base sm:text-lg font-black text-blue-400 font-mono">100%</div>
                        <div class="text-[11px] text-slate-400 font-medium mt-0.5">Controle de Mensalidades & Pix</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                        <div class="text-base sm:text-lg font-black text-purple-400 font-mono">LDB 9.394</div>
                        <div class="text-[11px] text-slate-400 font-medium mt-0.5">Conformidade Jurídica</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- INTERACTIVE PRODUCT SHOWCASE (Abas Interativas do Sistema) --}}
    <section id="recursos" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Tecnologia de Ponta a Ponta</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                    Conheça a interface moderna que vai transformar a rotina da sua equipe
                </h2>
                <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                    Esqueça softwares lentos de antigamente instalados em computadores velhos. O Aurosoft roda em nuvem com alta velocidade em computadores, tablets ou celulares.
                </p>
            </div>

            {{-- Seletor de Abas do Tour Visual --}}
            <div class="flex flex-wrap items-center justify-center gap-2 mb-8">
                <button
                    type="button"
                    @click="activeShowcaseTab = 'secretaria'"
                    :class="activeShowcaseTab === 'secretaria' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>📋</span>
                    <span>Secretaria em 1 Clique</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'crm'"
                    :class="activeShowcaseTab === 'crm' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>🎯</span>
                    <span>Funil de Matrículas (CRM)</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'vitrine'"
                    :class="activeShowcaseTab === 'vitrine' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>🌟</span>
                    <span>Mini-Site da Escola</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'salas'"
                    :class="activeShowcaseTab === 'salas' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>📅</span>
                    <span>Ocupação & Salas</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'financeiro'"
                    :class="activeShowcaseTab === 'financeiro' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>💳</span>
                    <span>Financeiro & Cobrança</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'dashboard'"
                    :class="activeShowcaseTab === 'dashboard' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>📊</span>
                    <span>Painel de Gestão</span>
                </button>
            </div>

            {{-- Painel Visual das Abas --}}
            <div class="rounded-3xl border border-slate-800 bg-slate-900/90 shadow-2xl p-6 sm:p-10 relative overflow-hidden">
                {{-- Mockup Topbar (Janela de Navegador) --}}
                <div class="flex items-center justify-between pb-6 mb-8 border-b border-slate-800/80">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                        <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'secretaria'">https://escola.aurosoft.com.br/admin/contracts</span>
                        <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'crm'">https://escola.aurosoft.com.br/admin/commercial-leads/kanban</span>
                        <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'vitrine'">https://suaescola.aurosoft.com.br/vitrine</span>
                        <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'salas'">https://escola.aurosoft.com.br/admin/schedule-matrix</span>
                        <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'financeiro'">https://escola.aurosoft.com.br/admin/installments</span>
                        <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'dashboard'">https://escola.aurosoft.com.br/admin</span>
                    </div>
                    <span class="text-xs font-mono font-bold text-amber-400 bg-amber-500/10 px-3 py-1 rounded-full border border-amber-500/25">
                        Aurosoft Cloud Engine &middot; Ativo
                    </span>
                </div>

                {{-- Conteúdo Aba 1: Secretaria em 1 Clique --}}
                <div x-show="activeShowcaseTab === 'secretaria'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 text-amber-300 text-xs font-bold">
                            <span>⚡</span>
                            <span>Agilidade Documental & LDB</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            15 Documentos Oficiais emitidos em 1 clique, sem modelos do Word
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Acabe com horas perdidas caçando arquivos no computador ou carimbando papéis manualmente. Com apenas um clique no contrato do aluno, a secretaria emite toda a papelada oficial personalizada e pronta para impressão.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Contrato de Matrícula, Termos de Rescisão e Trancamento</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Declaração de Matrícula, Quitação, Frequência e Comparecimento</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Carteirinha Estudantil com foto, Dossiê Cadastral e Certificado LDB 9.394</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-700/80 bg-slate-950 p-2 sm:p-3 shadow-2xl overflow-hidden group">
                            <div class="relative overflow-hidden rounded-xl border border-slate-800">
                                <img
                                    src="{{ asset('images/landing/secretaria-documentos.jpg') }}"
                                    alt="Menu com 15 Documentos Oficiais Emitidos em 1 Clique"
                                    width="1024"
                                    height="680"
                                    loading="lazy"
                                    decoding="async"
                                    class="w-full h-auto object-cover rounded-lg group-hover:scale-[1.02] transition-transform duration-500 shadow-xl"
                                />
                                <div class="absolute bottom-3 right-3 bg-slate-950/90 backdrop-blur-md px-3 py-1.5 rounded-lg border border-amber-500/40 text-xs text-amber-300 font-mono font-bold shadow-lg">
                                    ⚡ 15 Documentos em 1 Clique
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conteúdo Aba 2: Funil de Vendas / CRM --}}
                <div x-show="activeShowcaseTab === 'crm'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-300 text-xs font-bold">
                            <span>🚀</span>
                            <span>Captação & Vendas Integrada</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Funil de Matrículas estilo Kanban com WhatsApp integrado
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Quase nenhum sistema escolar tem CRM nativo. O Aurosoft entrega um funil visual para organizar cada interessado em colunas (Novo Contato, Em Contato, Visita Agendada). O atendente vê a origem do lead e transforma o interessado em aluno matriculado com 1 clique.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Colunas Kanban intuitivas com controle de status em tempo real</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Botão direto "Matricular", preenchendo o contrato sem digitação dupla</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Rastreamento de origem: WhatsApp, Instagram, Google ou Indicação</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-700/80 bg-slate-950 p-2 sm:p-3 shadow-2xl overflow-hidden group">
                            <div class="relative overflow-hidden rounded-xl border border-slate-800">
                                <img
                                    src="{{ asset('images/landing/crm-funil-vendas.png') }}"
                                    alt="Funil de Vendas e Captação de Alunos no Aurosoft"
                                    width="1024"
                                    height="680"
                                    loading="lazy"
                                    decoding="async"
                                    class="w-full h-auto object-cover rounded-lg group-hover:scale-[1.02] transition-transform duration-500 shadow-xl"
                                />
                                <div class="absolute bottom-3 right-3 bg-slate-950/90 backdrop-blur-md px-3 py-1.5 rounded-lg border border-emerald-500/40 text-xs text-emerald-300 font-mono font-bold shadow-lg">
                                    🎯 CRM & Funil de Captação
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conteúdo Aba 3: Mini-Site / Vitrine Pública da Escola --}}
                <div x-show="activeShowcaseTab === 'vitrine'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 text-amber-300 text-xs font-bold">
                            <span>🌐</span>
                            <span>Presença Digital Gratuita</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            A sua Escola ganha uma Landing Page Profissional pronta para captar alunos
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Enquanto outros sistemas cobram caro pela criação de sites ou exigem agências web, o Aurosoft já entrega para cada unidade da sua escola um <strong>mini-site moderno, responsivo e com SEO local</strong>. O aluno encontra o catálogo de cursos e entra em contato direto no seu WhatsApp.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span><strong>Mini-Site com seu Logotipo e Cores:</strong> Apresentação impecável das suas formações</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span><strong>Filtro Antifraude de Leads:</strong> Valida DDD ANATEL e bloqueia contatos fictícios</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span><strong>WhatsApp Inteligente:</strong> O interessado clica e já envia a mensagem com o curso escolhido</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-700/80 bg-slate-950 p-3 sm:p-4 shadow-2xl overflow-hidden group">
                            {{-- Mini mockup do mini-site da escola --}}
                            <div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden shadow-xl">
                                <div class="bg-slate-950 px-4 py-2 border-b border-slate-800 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500/80"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
                                        <span class="text-[11px] text-slate-400 font-mono ml-2">suaescola.aurosoft.com.br/vitrine</span>
                                    </div>
                                    <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded font-mono font-bold">● Online 24/7</span>
                                </div>
                                <div class="p-5 sm:p-6 bg-gradient-to-b from-slate-900 to-slate-950 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center text-slate-950 font-black text-xs">🏫</div>
                                            <div>
                                                <h4 class="text-xs font-black text-white">Aurosoft Academy</h4>
                                                <p class="text-[10px] text-slate-400">Fortaleza - CE &middot; Cursos Profissionalizantes LDB</p>
                                            </div>
                                        </div>
                                        <span class="text-[10px] bg-amber-500/10 border border-amber-500/30 text-amber-300 px-2.5 py-1 rounded-full font-bold">Matrículas Abertas</span>
                                    </div>
                                    <div class="rounded-xl bg-slate-950/80 border border-slate-800 p-4 space-y-2">
                                        <div class="text-[10px] uppercase font-bold text-amber-400 tracking-wider">Destaque da Semana</div>
                                        <h5 class="text-sm font-bold text-white">Formação Profissional em Gestão & Tecnologia</h5>
                                        <p class="text-xs text-slate-400 leading-relaxed">Carga horária de 120 horas práticas com metodologia interativa e certificado válido em todo o território nacional.</p>
                                        <div class="pt-2 flex flex-wrap items-center gap-2">
                                            <span class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black inline-flex items-center gap-1.5 shadow-md">
                                                <span>💬</span> Tirar Dúvidas no WhatsApp
                                            </span>
                                            <span class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 text-xs font-semibold">
                                                Ver Grade Completa ➔
                                            </span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 pt-1 text-center">
                                        <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800/80">
                                            <div class="text-xs font-bold text-white">100% Prático</div>
                                            <div class="text-[9px] text-slate-500">Sem enrolação</div>
                                        </div>
                                        <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800/80">
                                            <div class="text-xs font-bold text-white">Lei 9.394/96</div>
                                            <div class="text-[9px] text-slate-500">Válido no Brasil</div>
                                        </div>
                                        <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800/80">
                                            <div class="text-xs font-bold text-white">Captação Ativa</div>
                                            <div class="text-[9px] text-slate-500">Direto no CRM</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conteúdo Aba 4: Salas & Quadro de Horários --}}
                <div x-show="activeShowcaseTab === 'salas'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/15 text-blue-300 text-xs font-bold">
                            <span>📅</span>
                            <span>Capacidade & Ocupação</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Quadro de Horários e Ocupação de Salas em Tempo Real
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Nunca mais matricule alunos além da capacidade física do seu laboratório. O mapa matricial semanal mostra instantaneamente a taxa de ocupação de cada sala e horário, colorindo em verde, amarelo ou vermelho.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Alerta de lotação máxima para evitar superlotação de computadores</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Visão por dia da semana (Seg a Dom) e faixa horária (08h às 22h)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Alocação rápida no ato da matrícula com verificação de vaga</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-700/80 bg-slate-950 p-2 sm:p-3 shadow-2xl overflow-hidden group">
                            <div class="relative overflow-hidden rounded-xl border border-slate-800">
                                <img
                                    src="{{ asset('images/landing/quadro-horarios-salas.jpg') }}"
                                    alt="Quadro de Horários e Ocupação das Salas no Aurosoft"
                                    width="1024"
                                    height="680"
                                    loading="lazy"
                                    decoding="async"
                                    class="w-full h-auto object-cover rounded-lg group-hover:scale-[1.02] transition-transform duration-500 shadow-xl"
                                />
                                <div class="absolute bottom-3 right-3 bg-slate-950/90 backdrop-blur-md px-3 py-1.5 rounded-lg border border-blue-500/40 text-xs text-blue-300 font-mono font-bold shadow-lg">
                                    📅 Grade Semanal de Ocupação
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conteúdo Aba 5: Financeiro & Cobrança --}}
                <div x-show="activeShowcaseTab === 'financeiro'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-300 text-xs font-bold">
                            <span>💰</span>
                            <span>Controle de Caixa & Parcelas</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Gestão de Parcelas e Baixa Ágil sem Taxas Bancárias Abusivas
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Tenha clareza total do que tem a receber hoje, no mês e em atraso. O Aurosoft permite receber pagamentos em dinheiro, cartão ou Pix da própria escola (sem intermediários comendo sua margem) e dar baixa instantânea emitindo o recibo para o aluno.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Painel com Total Previsto, Vencidas, Vencem Hoje e Recebidas</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Filtros rápidos de inadimplência e exportação de relatórios</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Recibos térmicos e carnês em PDF gerados automaticamente</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-700/80 bg-slate-950 p-2 sm:p-3 shadow-2xl overflow-hidden group">
                            <div class="relative overflow-hidden rounded-xl border border-slate-800">
                                <img
                                    src="{{ asset('images/landing/gestao-cobrancas-financeiro.jpg') }}"
                                    alt="Gestão de Parcelas e Cobranças Financeiras no Aurosoft"
                                    width="1024"
                                    height="680"
                                    loading="lazy"
                                    decoding="async"
                                    class="w-full h-auto object-cover rounded-lg group-hover:scale-[1.02] transition-transform duration-500 shadow-xl"
                                />
                                <div class="absolute bottom-3 right-3 bg-slate-950/90 backdrop-blur-md px-3 py-1.5 rounded-lg border border-emerald-500/40 text-xs text-emerald-300 font-mono font-bold shadow-lg">
                                    💰 Gestão de Parcelas & Caixa
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conteúdo Aba 6: Painel Central / Dashboard --}}
                <div x-show="activeShowcaseTab === 'dashboard'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/15 text-purple-300 text-xs font-bold">
                            <span>📊</span>
                            <span>Visão 360º da Operação</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Painel de Controle com Atalhos Rápidos e Metas Escolares
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Ao abrir o sistema pela manhã, o diretor e a recepção visualizam exatamente o que importa: contratos ativos, novas matrículas no mês, contas a receber hoje, oportunidades em aberto no CRM e o progresso das metas mensais.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Atalhos Rápidos de Gestão: Nova Matrícula, Agenda, Mensalidades e DRE</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Barra de Metas & Desempenho com contagem regressiva dos dias úteis</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Quadro de Tarefas da Equipe integrado para não esquecer nenhum recado</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-700/80 bg-slate-950 p-2 sm:p-3 shadow-2xl overflow-hidden group">
                            <div class="relative overflow-hidden rounded-xl border border-slate-800">
                                <img
                                    src="{{ asset('images/landing/painel-controle-dashboard.jpg') }}"
                                    alt="Painel de Controle e Métricas Gerais no Aurosoft"
                                    width="1024"
                                    height="680"
                                    loading="lazy"
                                    decoding="async"
                                    class="w-full h-auto object-cover rounded-lg group-hover:scale-[1.02] transition-transform duration-500 shadow-xl"
                                />
                                <div class="absolute bottom-3 right-3 bg-slate-950/90 backdrop-blur-md px-3 py-1.5 rounded-lg border border-purple-500/40 text-xs text-purple-300 font-mono font-bold shadow-lg">
                                    📊 Painel Central de Controle
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- MÓDULOS INTEGRADOS DO SISTEMA --}}
    <section id="modulos" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Ecossistema Completo</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                    Tudo o que sua instituição precisa em uma só plataforma
                </h2>
                <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                    Módulos nativamente integrados para acabar com retrabalho, planilhas paralelas e sistemas desconexos.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                {{-- Módulo 1 --}}
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-amber-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🎓
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-amber-400 transition">Pedagógico & LMS</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Sincronização com o laboratório Ouro Moderno, turmas, aulas práticas, gamificação com medalhas e controle de notas.
                    </p>
                </div>

                {{-- Módulo 2 --}}
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-emerald-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🏛️
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition">Secretaria Digital</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Matrículas, contratos customizáveis, emissão de certificados oficiais com validação QR Code e amparo na Lei 9.394/96.
                    </p>
                </div>

                {{-- Módulo 3 --}}
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-blue-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        💳
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-blue-400 transition">Financeiro & Mensalidades</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Controle de parcelas, apoio a pagamentos Pix com chave da escola, baixa ágil no balcão e recibos no ato.
                    </p>
                </div>

                {{-- Módulo 4 --}}
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-cyan-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🪪
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-cyan-400 transition">Totem de Presença</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Totem de autoatendimento para entrada dos alunos. Registro de frequência em 2 segundos sem papelada na recepção.
                    </p>
                </div>

                {{-- Módulo 5 --}}
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-purple-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        📱
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-purple-400 transition">Portal do Aluno PWA</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        O aluno consulta frequência, notas, histórico escolar, medalhas conquistadas e abre chamados direto do celular.
                    </p>
                </div>

                {{-- Módulo 6 --}}
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-pink-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-pink-500/10 border border-pink-500/20 text-pink-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🌟
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-pink-400 transition">Vitrine Pública & Leads</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Mini-site moderno da escola para divulgação de cursos, grade de formações e captação de leads para WhatsApp.
                    </p>
                </div>

                {{-- Módulo 7 --}}
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-orange-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🎧
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-orange-400 transition">Helpdesk & Suporte</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Central de chamados e atendimento com histórico de mensagens, respostas rápidas e alertas instantâneos.
                    </p>
                </div>

                {{-- Módulo 8 --}}
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-indigo-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🛡️
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-indigo-400 transition">AuroDoctor & Nuvem</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Diagnóstico autônomo de integridade, telemetria, backups diários automatizados e segurança multi-tenant.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- COMPARATIVO BRUTAL (Sua Escola Hoje vs Com Aurosoft) --}}
    <section id="comparativo" class="py-20 sm:py-28 bg-slate-900/60 border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">A Evolução da sua Escola</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                    Por que as melhores escolas estão migrando para o Aurosoft?
                </h2>
                <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                    Compare como sua escola funciona hoje com sistemas tradicionais ou planilhas versus a eficiência da nuvem do Aurosoft.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                {{-- Coluna 1: O Jeito Antigo --}}
                <div class="rounded-3xl border border-rose-500/30 bg-slate-950/80 p-8 space-y-6">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-rose-500/15 text-rose-400 font-black text-xl flex items-center justify-center">
                            ✕
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white">Sistemas Legados & Planilhas</h3>
                            <span class="text-xs text-rose-400 font-semibold">Lentidão, retrabalho e riscos diários</span>
                        </div>
                    </div>

                    <ul class="space-y-4 text-xs sm:text-sm text-slate-300">
                        <li class="flex items-start gap-3">
                            <span class="text-rose-400 font-black mt-0.5">✕</span>
                            <span>Filas enormes na secretaria antes das aulas para registrar chamada no papel.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-rose-400 font-black mt-0.5">✕</span>
                            <span>Taxas bancárias pesadas por emissão de boletos e demora de 48h para compensar.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-rose-400 font-black mt-0.5">✕</span>
                            <span>Medo constante de perder todos os dados se o computador principal queimar.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-rose-400 font-black mt-0.5">✕</span>
                            <span>Alunos desmotivados sem saber suas notas nem quanto falta para se formarem.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-rose-400 font-black mt-0.5">✕</span>
                            <span>Dificuldade para gerenciar mais de uma unidade ou filial da escola.</span>
                        </li>
                    </ul>
                </div>

                {{-- Coluna 2: Com Aurosoft --}}
                <div class="rounded-3xl border-2 border-amber-500/50 bg-slate-950 p-8 space-y-6 shadow-2xl shadow-amber-500/10 relative">
                    <div class="absolute -top-3.5 right-6 px-3 py-1 rounded-full bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 font-black text-[10px] uppercase tracking-wider shadow">
                        Padrão Ouro
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-amber-500/15 text-amber-400 font-black text-xl flex items-center justify-center">
                            ✓
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white">Com Aurosoft SaaS Cloud</h3>
                            <span class="text-xs text-amber-400 font-semibold">Agilidade, controle financeiro e retenção</span>
                        </div>
                    </div>

                    <ul class="space-y-4 text-xs sm:text-sm text-slate-200">
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 font-black mt-0.5">✓</span>
                            <span><strong>Presença em 2 segundos</strong> no Totem de autoatendimento, sem papel nem filas.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 font-black mt-0.5">✓</span>
                            <span><strong>Gestão Financeira & Pix:</strong> mensalidades organizadas, chave Pix da escola e baixa rápida com recibos oficiais.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 font-black mt-0.5">✓</span>
                            <span><strong>Nuvem Blindada & Multi-Tenant:</strong> backups automáticos diários e segurança de nível bancário.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 font-black mt-0.5">✓</span>
                            <span><strong>Portal do Aluno Gamificado:</strong> medalhas, moedas e histórico escolar acessíveis pelo celular.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 font-black mt-0.5">✓</span>
                            <span><strong>Multi-Unidades Nativo:</strong> controle todas as suas filiais em um único painel Master.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- CALCULADORA INTERATIVA DE RETORNO / ROI --}}
    <section id="calculadora" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900/90 to-amber-950/20 p-8 sm:p-12 shadow-2xl space-y-10">
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Calculadora de Economia & Eficiência</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white">Quanto sua escola pode economizar por ano?</h2>
                    <p class="text-xs sm:text-sm text-slate-400">
                        Arraste o seletor abaixo com a quantidade de alunos da sua instituição e veja o impacto estimado:
                    </p>
                </div>

                {{-- Slider Interativo --}}
                <div class="max-w-xl mx-auto space-y-4">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-400">Alunos Ativos:</span>
                        <span class="text-2xl font-black text-amber-400 font-mono" x-text="calcStudents + ' alunos'"></span>
                    </div>
                    <input
                        type="range"
                        id="calc-students-range"
                        name="calc_students"
                        aria-label="Número de alunos matriculados para cálculo de economia"
                        min="50"
                        max="1200"
                        step="25"
                        x-model="calcStudents"
                        class="w-full h-3 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-500"
                    />
                    <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                        <span>50 alunos</span>
                        <span>300 alunos</span>
                        <span>600 alunos</span>
                        <span>1.200 alunos</span>
                    </div>
                </div>

                {{-- Resultados da Calculadora --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
                    <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                        <span class="text-xs text-slate-400 font-medium block">Horas da Secretaria Poupadas</span>
                        <div class="text-2xl sm:text-3xl font-black text-amber-400 font-mono mt-1" x-text="Math.round(calcStudents * 0.18) + 'h / mês'"></div>
                        <span class="text-[11px] text-slate-500 mt-1 block">Menos filas e papéis manuais</span>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                        <span class="text-xs text-slate-400 font-medium block">Redução de Custos Administrativos</span>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono mt-1" x-text="'R$ ' + (Math.round(calcStudents * 2.80 * 12)).toLocaleString('pt-BR') + ' / ano'"></div>
                        <span class="text-[11px] text-slate-500 mt-1 block">Economia em papel, carnês e retrabalho</span>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                        <span class="text-xs text-slate-400 font-medium block">Redução Estimada de Evasão</span>
                        <div class="text-2xl sm:text-3xl font-black text-blue-400 font-mono mt-1" x-text="'+ ' + Math.max(3, Math.round(calcStudents * 0.06)) + ' alunos'"></div>
                        <span class="text-[11px] text-slate-500 mt-1 block">Com engajamento e gamificação</span>
                    </div>
                </div>

                <div class="text-center pt-2">
                    <button
                        type="button"
                        @click="leadModalOpen = true"
                        class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 hover:to-emerald-300 text-slate-950 font-black text-xs transition shadow-lg shadow-emerald-500/20 active:scale-95"
                    >
                        <span>💬</span>
                        <span>Quero Aplicar Esses Resultados na Minha Escola</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ ACORDEÃO --}}
    <section id="faq" class="py-20 sm:py-28 bg-slate-900/40 border-b border-slate-800/80">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Tire suas dúvidas</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">Perguntas Frequentes</h2>
            </div>

            <div class="space-y-3">
                {{-- FAQ 1 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 cursor-pointer" @click="faqOpen = (faqOpen === 1 ? null : 1)">
                    <div class="flex items-center justify-between font-bold text-sm text-white">
                        <span>O Aurosoft integra com a Ouro Moderno?</span>
                        <span class="text-amber-400 font-mono text-base" x-text="faqOpen === 1 ? '−' : '+'"></span>
                    </div>
                    <div x-show="faqOpen === 1" x-cloak class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
                        Sim! O Aurosoft possui integração nativa e homologada com o servidor local do LMS Ouro Moderno. O sistema sincroniza cursos, turmas, notas e presença sem retrabalho da secretaria.
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 cursor-pointer" @click="faqOpen = (faqOpen === 2 ? null : 2)">
                    <div class="flex items-center justify-between font-bold text-sm text-white">
                        <span>Preciso instalar algum servidor caro na minha escola?</span>
                        <span class="text-amber-400 font-mono text-base" x-text="faqOpen === 2 ? '−' : '+'"></span>
                    </div>
                    <div x-show="faqOpen === 2" x-cloak class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
                        Não! O Aurosoft opera 100% em nuvem gerenciada. Você só precisa de um navegador comum no computador da recepção ou celular. Para o Totem de presença, qualquer tablet ou monitor touch simples funciona com perfeição.
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 cursor-pointer" @click="faqOpen = (faqOpen === 3 ? null : 3)">
                    <div class="flex items-center justify-between font-bold text-sm text-white">
                        <span>Meus dados e os dados dos alunos ficam seguros?</span>
                        <span class="text-amber-400 font-mono text-base" x-text="faqOpen === 3 ? '−' : '+'"></span>
                    </div>
                    <div x-show="faqOpen === 3" x-cloak class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
                        Totalmente. Utilizamos arquitetura multi-tenant com isolamento rigoroso por schema no PostgreSQL. Os dados da sua escola nunca se misturam com os de outra instituição. Além disso, backups automatizados diários são executados com retenção de segurança.
                    </div>
                </div>

                {{-- FAQ 4 --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 cursor-pointer" @click="faqOpen = (faqOpen === 4 ? null : 4)">
                    <div class="flex items-center justify-between font-bold text-sm text-white">
                        <span>Como funciona o suporte técnico e pedagógico?</span>
                        <span class="text-amber-400 font-mono text-base" x-text="faqOpen === 4 ? '−' : '+'"></span>
                    </div>
                    <div x-show="faqOpen === 4" x-cloak class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
                        Você tem suporte direto via WhatsApp e também pela Central de Chamados integrada dentro do próprio painel administrativo. E o robô AuroDoctor monitora sua instalação 24 horas por dia para prevenir instabilidades.
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA FINAL --}}
    <section class="py-16 sm:py-24 bg-gradient-to-b from-slate-950 to-slate-900 border-b border-slate-800/80 relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 text-center space-y-6 relative z-10">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Pronto para dar o próximo passo?</span>
            <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight">
                Leve a gestão da sua escola para o mais alto nível profissional
            </h2>
            <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Junte-se a escolas profissionalizantes que reduziram a inadimplência, zeraram filas e aumentaram suas matrículas com o Aurosoft.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 pt-2">
                <a
                    href="{{ url('/demo') }}"
                    class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-300 hover:brightness-110 text-slate-950 font-black text-sm transition shadow-xl shadow-amber-500/25 active:scale-95"
                >
                    <span>✨</span>
                    <span>Testar Demonstração Grátis</span>
                </a>

                <button
                    type="button"
                    @click="leadModalOpen = true"
                    class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-400 hover:brightness-110 text-slate-950 font-black text-sm transition shadow-xl shadow-emerald-500/20 active:scale-95"
                >
                    <span>💬</span>
                    <span>Falar no WhatsApp com Especialista</span>
                </button>
            </div>
        </div>
    </section>
    </main>

    {{-- FOOTER --}}
    <footer class="bg-slate-950 text-slate-400 text-xs py-12 px-4 sm:px-6 lg:px-8 border-t border-slate-800/80">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-amber-500 flex items-center justify-center text-slate-950 font-black text-base shadow-sm">
                    A
                </div>
                <div>
                    <span class="font-extrabold text-white text-sm">Aurosoft</span>
                    <span class="block text-[11px] text-slate-400">Ecossistema de Gestão Escolar & LMS Cloud</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-6 text-slate-400">
                <a href="#recursos" class="hover:text-amber-400 transition">Recursos</a>
                <a href="{{ url('/demo') }}" class="hover:text-amber-400 transition">Demonstração</a>
                <a href="{{ route('terms') }}" class="hover:text-amber-400 transition">Termos de Uso</a>
                <a href="{{ route('privacy') }}" class="hover:text-amber-400 transition">Privacidade (LGPD)</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="hover:text-amber-400 transition">Login Administrativo</a>
            </div>

            <div class="text-center md:text-right text-[11px] text-slate-400">
                &copy; {{ date('Y') }} Aurosoft Inc. &middot; Todos os direitos reservados.
            </div>
        </div>
    </footer>

    {{-- ========================================================================= --}}
    {{-- MODAL: ACESSAR MINHA ESCOLA                                                --}}
    {{-- ========================================================================= --}}
    <div
        x-show="tenantModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
        @keydown.escape.window="tenantModalOpen = false"
    >
        <div class="relative w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-2xl space-y-6"
             @click.away="tenantModalOpen = false">
            <button
                type="button"
                @click="tenantModalOpen = false"
                class="absolute top-5 right-5 text-slate-400 hover:text-white text-xl cursor-pointer"
            >
                ✕
            </button>
            <div class="space-y-2">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400 text-2xl border border-amber-500/20">
                    🏫
                </div>
                <h3 class="text-xl font-bold text-white">Acesse o portal da sua escola</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Digite o subdomínio ou nome exclusivo fornecido no contrato de implantação da sua instituição.
                </p>
            </div>

            {{-- Lista rápida de escolas ativas no sistema --}}
            @if(!empty($activeSchools) && count($activeSchools) > 0)
                <div class="space-y-2 border-b border-slate-800 pb-4">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Escolas Cadastradas na Rede:</span>
                    <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto pr-1">
                        @foreach($activeSchools as $school)
                            <a
                                href="https://{{ $school->slug }}.aurosoft.com.br/admin"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800/80 hover:bg-amber-500/15 hover:border-amber-500/30 border border-slate-700/80 text-xs font-semibold text-slate-200 hover:text-amber-300 transition"
                            >
                                <span>🏫</span>
                                <span>{{ $school->trade_name ?: $school->client_name }}</span>
                                <span class="text-[10px] font-mono text-slate-400">({{ $school->city ?? $school->slug }})</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <form onsubmit="event.preventDefault(); const slug = document.getElementById('school-slug-input').value.trim().toLowerCase(); if(slug) window.location.href = 'https://' + slug + '.aurosoft.com.br/admin';">
                <div class="space-y-4">
                    <div class="relative flex items-center">
                        <input id="school-slug-input"
                               type="text"
                               required
                               placeholder="nome-da-escola"
                               class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3.5 pr-36 text-sm text-white focus:border-amber-400 focus:outline-none font-mono" />
                        <span class="absolute right-4 text-xs font-semibold text-slate-500 pointer-events-none">
                            .aurosoft.com.br
                        </span>
                    </div>
                    <button type="submit"
                            class="w-full rounded-2xl bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 py-3.5 text-xs font-extrabold text-slate-950 transition shadow-lg shadow-amber-500/20 cursor-pointer">
                        Acessar Minha Escola ➔
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: SOLICITAR DEMONSTRAÇÃO / CONTATO                                    --}}
    {{-- ========================================================================= --}}
    <div
        x-show="leadModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
        @keydown.escape.window="leadModalOpen = false"
    >
        <div class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto"
             @click.away="leadModalOpen = false">
            <button
                type="button"
                @click="leadModalOpen = false"
                class="absolute top-5 right-5 text-slate-400 hover:text-white text-xl cursor-pointer"
            >
                ✕
            </button>
            <div class="space-y-2">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-400 text-2xl border border-emerald-500/20">
                    🚀
                </div>
                <h3 class="text-xl font-bold text-white">Solicite uma Demonstração</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Preencha os dados da sua instituição para receber uma apresentação personalizada e falar diretamente com nossa equipe.
                </p>
            </div>

            @if($errors->any())
                <div class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                    <span class="font-bold block">⚠️ Corrija os pontos abaixo para continuar:</span>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('landing.lead.store') }}" method="POST" class="space-y-4">
                @csrf
                {{-- Honeypot anti-spam (invisível) --}}
                <input type="text" name="website_url_check" class="hidden" tabindex="-1" autocomplete="off" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-300">Seu Nome *</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name') }}"
                               required
                               placeholder="Ex: Carlos Silva"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-emerald-400 focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-300">Nome da Escola *</label>
                        <input type="text"
                               name="school_name"
                               value="{{ old('school_name') }}"
                               required
                               placeholder="Ex: Instituto Educar"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-emerald-400 focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-300">WhatsApp / Telefone *</label>
                        <input type="text"
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
                               class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-emerald-400 focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-300">E-mail Corporativo *</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               placeholder="diretoria@escola.com.br"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-emerald-400 focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-300">Cidade e Estado</label>
                        <input type="text"
                               name="city"
                               value="{{ old('city') }}"
                               placeholder="Ex: Fortaleza - CE"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-emerald-400 focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-300">Nº de Alunos Ativos</label>
                        <select name="students_count"
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-emerald-400 focus:outline-none">
                            <option value="">Selecione uma faixa...</option>
                            <option value="Até 100 alunos">Até 100 alunos</option>
                            <option value="100 a 300 alunos">100 a 300 alunos</option>
                            <option value="300 a 600 alunos">300 a 600 alunos</option>
                            <option value="Mais de 600 alunos">Mais de 600 alunos</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Mensagem / Principais Desafios (Opcional)</label>
                    <textarea name="message"
                              rows="2"
                              placeholder="Conte um pouco sobre as necessidades da sua instituição..."
                              class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-xs text-white focus:border-emerald-400 focus:outline-none"></textarea>
                </div>

                <p class="text-[10px] text-slate-500 text-center leading-tight">
                    Ao enviar, você concorda com nossos 
                    <a href="{{ route('terms') }}" target="_blank" class="text-amber-400 underline hover:text-amber-300">Termos de Uso</a> e 
                    <a href="{{ route('privacy') }}" target="_blank" class="text-amber-400 underline hover:text-amber-300">Política de Privacidade (LGPD)</a>.
                </p>

                <button type="submit"
                        class="w-full rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 hover:to-emerald-300 py-3.5 text-xs font-extrabold text-slate-950 transition shadow-lg shadow-emerald-500/20 cursor-pointer">
                    Enviar Solicitação de Demonstração ➔
                </button>
            </form>
        </div>
    </div>

    <!-- Aurosoft Native Analytics Tracker (Imune a AdBlockers & 100% LGPD) -->
    <script>
        function trackAurosoftEvent(eventType, eventLabel) {
            try {
                const params = new URLSearchParams(window.location.search);
                const payload = JSON.stringify({
                    scope: 'aurosoft_landing',
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
