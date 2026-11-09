@php
    $activeBarbershops = collect();
    try {
        $activeBarbershops = \App\Models\Barbershop::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'city', 'logo_url', 'brand_color']);
    } catch (\Throwable $e) {
        $activeBarbershops = collect();
    }
    $demoShop = $activeBarbershops->first();
    $demoUrl = $demoShop ? url('/' . $demoShop->slug) : url('/admin');
    $masterPhone = null;
    try {
        $rawPhone = \App\Models\User::where('role', 'master_admin')->whereNotNull('phone')->value('phone');
        if ($rawPhone) {
            $digits = preg_replace('/\D/', '', $rawPhone);
            if (strlen($digits) >= 10) {
                $masterPhone = str_starts_with($digits, '55') ? $digits : '55' . $digits;
            }
        }
    } catch (\Throwable $e) {
    }
    $commercialWhatsapp = $masterPhone ?: env('AUROSOFT_COMMERCIAL_WHATSAPP', '5585999999999');
@endphp
<!DOCTYPE html>
<html lang="pt-BR" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AuroBarber • Sistema de Gestão, Agendamento Online & Fidelização para Barbearias</title>
    <meta name="description" content="Transforme sua barbearia com agendamento online em 30 segundos, comanda de bar e produtos, cálculo automático de comissões, visão restrita do barbeiro e cartão fidelidade.">
    <meta name="keywords" content="sistema para barbearia, agendamento online barbearia, controle de comissao barbeiro, software gestao barbearia, comanda barbearia, cartao fidelidade barbearia, aurobarber">
    <meta name="author" content="Aurosoft Tecnologia">
    <link rel="canonical" href="https://barber.aurosoft.com.br">
    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <meta name="theme-color" content="#020617">

    <!-- Open Graph / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://barber.aurosoft.com.br">
    <meta property="og:title" content="AuroBarber • Gestão & Agendamento Inteligente para Barbearias">
    <meta property="og:description" content="Página exclusiva de agendamento em 30s, comissões com recibo jurídico, comanda de bar/estoque e cartão fidelidade automático.">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="AuroBarber">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
                    },
                    colors: {
                        amber: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .hero-glow {
            background: radial-gradient(circle at 50% 0%, rgba(245, 158, 11, 0.20) 0%, rgba(16, 185, 129, 0.06) 45%, transparent 72%);
        }
        .card-glass {
            background: rgba(15, 23, 42, 0.78);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .text-gradient-gold {
            background: linear-gradient(135deg, #fef08a 0%, #fbbf24 50%, #d97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .metric-box-stable {
            min-height: 72px;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950 min-h-screen flex flex-col justify-between"
      x-data="{
          leadModalOpen: false,
          leadTab: 'dados',
          leadName: '',
          leadShop: '',
          leadPhone: '',
          leadCity: '',
          leadBarbers: '2 a 4 cadeiras',
          leadChallenge: 'Automatizar agendamentos e controlar comissões',
          tenantModalOpen: false,
          tenantTab: 'painel',
          activeShowcaseTab: 'agenda',
          calcCutsPerDay: 18,
          calcAvgTicket: 45,
          faqOpen: 1,
          sendWhatsAppLead() {
              const msg = `Olá! Vim pelo site do *AuroBarber* e quero conhecer o sistema para minha barbearia! 💈✂️\n\n` +
                  `👤 *Nome:* ${this.leadName || 'Não informado'}\n` +
                  `💈 *Barbearia:* ${this.leadShop || 'Não informada'}\n` +
                  `📱 *WhatsApp:* ${this.leadPhone || 'Não informado'}\n` +
                  `📍 *Cidade:* ${this.leadCity || 'Não informada'}\n` +
                  `🪑 *Porte:* ${this.leadBarbers}\n` +
                  `🎯 *Objetivo:* ${this.leadChallenge}`;
              window.open('https://wa.me/{{ $commercialWhatsapp }}?text=' + encodeURIComponent(msg), '_blank');
              this.leadModalOpen = false;
          }
      }">

    {{-- Top Announcement Bar --}}
    <div class="bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 text-slate-950 text-xs font-black py-2 px-4 text-center shadow-md select-none flex items-center justify-center gap-2">
        <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-slate-950 text-amber-400 text-[10px] font-black shrink-0">★</span>
        <span class="truncate">AuroBarber 2.0 Cloud &middot; <strong>Agendamento Online em 30s, Comanda de Bar, Cartão Fidelidade & Visão Restrita do Barbeiro</strong></span>
        <a href="#recursos" class="hidden md:inline-block underline ml-2 hover:opacity-80 transition font-black whitespace-nowrap">Ver tour interativo &rarr;</a>
    </div>

    {{-- Navbar Superior --}}
    <header class="sticky top-0 z-40 bg-slate-950/90 backdrop-blur-md border-b border-slate-800/80 transition">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-3 xl:gap-6">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-3 group shrink-0">
                <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-gradient-to-tr from-amber-600 via-amber-500 to-amber-300 flex items-center justify-center text-slate-950 font-black text-xl shadow-lg shadow-amber-500/25 group-hover:scale-105 transition-transform shrink-0">
                    💈
                </div>
                <div>
                    <span class="text-sm sm:text-base font-black tracking-tight text-white block group-hover:text-amber-400 transition whitespace-nowrap">
                        Auro<span class="text-amber-400">Barber</span>
                        <span class="text-amber-400 text-[10px] sm:text-xs font-mono font-bold uppercase ml-1 px-1.5 py-0.5 rounded bg-amber-500/15 border border-amber-500/30">PRO</span>
                    </span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 font-medium block leading-tight whitespace-nowrap">
                        by Aurosoft Tecnologia
                    </span>
                </div>
            </a>

            {{-- Links de Navegação Desktop --}}
            <nav class="hidden xl:flex items-center gap-5 xl:gap-6 text-xs font-bold text-slate-300 whitespace-nowrap">
                <a href="#recursos" class="hover:text-amber-400 transition">Tour pelo Sistema</a>
                <a href="#modulos" class="hover:text-amber-400 transition">Módulos</a>
                <a href="#calculadora" class="hover:text-amber-400 transition flex items-center gap-1">
                    <span>Simulador de Lucro</span>
                    <span class="text-[9px] bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded-full font-black">NOVO</span>
                </a>
                <a href="#comparativo" class="hover:text-amber-400 transition">Por que o AuroBarber?</a>
                <a href="#planos" class="hover:text-amber-400 transition">Planos</a>
                <a href="#faq" class="hover:text-amber-400 transition">Dúvidas</a>
            </nav>

            {{-- Ações Direitas --}}
            <div class="hidden sm:flex items-center gap-2 xl:gap-3 shrink-0">
                <button
                    type="button"
                    @click="tenantModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-700 bg-slate-900/80 hover:bg-slate-800 text-slate-200 text-xs font-bold transition shadow-sm whitespace-nowrap shrink-0 cursor-pointer"
                >
                    <span>💈</span>
                    <span>Acessar Barbearia</span>
                </button>

                <a
                    href="{{ url('/admin') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-amber-500/40 bg-amber-950/30 hover:bg-amber-900/50 text-amber-300 text-xs font-bold transition shadow-sm whitespace-nowrap shrink-0"
                >
                    <span>🔐</span>
                    <span>Login do Painel</span>
                </a>

                <button
                    type="button"
                    @click="leadModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:brightness-110 text-slate-950 text-xs font-black transition shadow-lg shadow-amber-500/25 active:scale-95 whitespace-nowrap shrink-0 cursor-pointer"
                >
                    <span>🚀</span>
                    <span>Criar Minha Barbearia</span>
                </button>
            </div>

            {{-- Botões Mobile --}}
            <div class="flex sm:hidden items-center gap-2">
                <a
                    href="{{ url('/admin') }}"
                    class="px-3 py-2 rounded-xl border border-slate-700 bg-slate-900 text-slate-200 font-bold text-xs"
                >
                    Entrar
                </a>
                <button
                    type="button"
                    @click="leadModalOpen = true"
                    class="px-3 py-2 rounded-xl bg-amber-500 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20"
                >
                    Contratar
                </button>
            </div>
        </div>
    </header>

    <main id="main-content">
        {{-- HERO SECTION --}}
        <section class="relative hero-glow pt-14 pb-20 sm:pt-24 sm:pb-32 overflow-hidden border-b border-slate-800/80">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[450px] bg-gradient-to-tr from-amber-500/20 to-emerald-500/10 blur-[130px] rounded-full pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center max-w-4xl mx-auto space-y-7">
                    {{-- Badge de Inovação --}}
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold tracking-wide shadow-inner">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>O Sistema Operacional Completo para Barbearias de Alto Padrão</span>
                    </div>

                    {{-- Headline Principal --}}
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.12]">
                        Transforme sua barbearia em uma máquina de <span class="text-gradient-gold">agenda cheia, comanda lucrativa</span> e gestão no piloto automático.
                    </h1>

                    {{-- Subheadline --}}
                    <p class="text-base sm:text-lg lg:text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed font-normal">
                        Link exclusivo <strong>White-Label</strong> para seu cliente agendar em 30 segundos sem baixar nada, <strong>comanda com estoque de bar e cosméticos</strong>, <strong>cartão fidelidade automático</strong> e <strong>visão restrita individual</strong> no celular de cada barbeiro.
                    </p>

                    {{-- CTAs Principais --}}
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                        <button
                            type="button"
                            @click="leadModalOpen = true"
                            class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-sm tracking-wide transition shadow-xl shadow-amber-500/25 flex items-center justify-center gap-2.5 active:scale-95 group cursor-pointer"
                        >
                            <span>✂️</span>
                            <span>Quero Implantar na Minha Barbearia</span>
                            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </button>

                        <a
                            href="#recursos"
                            class="w-full sm:w-auto px-8 py-4 rounded-2xl border border-slate-700 bg-slate-900/90 hover:bg-slate-800/90 text-white font-bold text-sm tracking-wide transition shadow-lg flex items-center justify-center gap-2.5 active:scale-95"
                        >
                            <span>🖥️</span>
                            <span>Ver Demonstração Interativa</span>
                        </a>
                    </div>

                    {{-- Selos de Confiança / Badges de Autoridade --}}
                    <div class="pt-8 border-t border-slate-800/60 grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto text-left">
                        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                            <div class="text-base sm:text-lg font-black text-amber-400 font-mono">30 seg</div>
                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">Agendamento Online s/ Senha</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                            <div class="text-base sm:text-lg font-black text-emerald-400 font-mono">0% Taxa</div>
                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">100% do Pix e Caixa é Seu</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                            <div class="text-base sm:text-lg font-black text-sky-400 font-mono">App PWA</div>
                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">Instalável no Android & iPhone</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                            <div class="text-base sm:text-lg font-black text-purple-400 font-mono">2FA + 30d</div>
                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">Segurança & Visão por Barbeiro</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- INTERACTIVE PRODUCT SHOWCASE (Abas Interativas do Sistema) --}}
        <section id="recursos" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Tecnologia Desenhada para a Bancada</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                        Conheça por dentro o sistema que organiza sua equipe e fideliza seus clientes
                    </h2>
                    <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                        Navegue pelas abas abaixo e veja como o AuroBarber resolve desde o agendamento no Instagram até o fechamento das comissões no sábado à noite.
                    </p>
                </div>

                {{-- Seletor de Abas do Tour Visual --}}
                <div class="flex flex-wrap items-center justify-center gap-2 mb-8">
                    <button
                        type="button"
                        @click="activeShowcaseTab = 'agenda'"
                        :class="activeShowcaseTab === 'agenda' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                    >
                        <span>📅</span>
                        <span>Agenda Diária & Encaixes</span>
                    </button>

                    <button
                        type="button"
                        @click="activeShowcaseTab = 'vitrine'"
                        :class="activeShowcaseTab === 'vitrine' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                    >
                        <span>📱</span>
                        <span>Site White-Label & App PWA</span>
                    </button>

                    <button
                        type="button"
                        @click="activeShowcaseTab = 'comissoes'"
                        :class="activeShowcaseTab === 'comissoes' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                    >
                        <span>💰</span>
                        <span>Comissões & Recibo Jurídico</span>
                    </button>

                    <button
                        type="button"
                        @click="activeShowcaseTab = 'comanda'"
                        :class="activeShowcaseTab === 'comanda' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                    >
                        <span>🍺</span>
                        <span>Comanda de Bar & Caixa</span>
                    </button>

                    <button
                        type="button"
                        @click="activeShowcaseTab = 'fidelidade'"
                        :class="activeShowcaseTab === 'fidelidade' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                    >
                        <span>🎁</span>
                        <span>Fidelidade & Clientes Sumidos</span>
                    </button>

                    <button
                        type="button"
                        @click="activeShowcaseTab = 'barbeiro'"
                        :class="activeShowcaseTab === 'barbeiro' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                    >
                        <span>💈</span>
                        <span>Visão Restrita do Barbeiro</span>
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
                            <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'agenda'">https://barber.aurosoft.com.br/admin/sua-barbearia/daily-schedule</span>
                            <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'vitrine'">https://barber.aurosoft.com.br/sua-barbearia</span>
                            <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'comissoes'">https://barber.aurosoft.com.br/admin/sua-barbearia/commissions-page</span>
                            <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'comanda'">https://barber.aurosoft.com.br/admin/sua-barbearia/cash-register-page</span>
                            <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'fidelidade'">https://barber.aurosoft.com.br/admin/sua-barbearia/barbershop-clients</span>
                            <span class="text-xs text-slate-400 font-mono ml-2 hidden sm:inline" x-show="activeShowcaseTab === 'barbeiro'">https://barber.aurosoft.com.br/admin/sua-barbearia (Perfil Colaborador)</span>
                        </div>
                        <span class="text-xs font-mono font-bold text-amber-400 bg-amber-500/10 px-3 py-1 rounded-full border border-amber-500/25">
                            AuroBarber Cloud Engine &middot; Online 24/7
                        </span>
                    </div>

                    {{-- ABA 1: AGENDA DIÁRIA & ENCAIXES --}}
                    <div x-show="activeShowcaseTab === 'agenda'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-5 space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 text-amber-300 text-xs font-bold">
                                <span>📅</span>
                                <span>Visão por Cadeira em Tempo Real</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                                Agenda Diária Visual com Bloqueio de Almoço e Encaixe em 5 Segundos
                            </h3>
                            <p class="text-sm text-slate-300 leading-relaxed">
                                Visualize todas as cadeiras da sua barbearia lado a lado. Saiba quem está na cadeira, quem já confirmou e lance encaixes de clientes de balcão com apenas 2 cliques.
                            </p>
                            <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Intervalo de Almoço Individual:</strong> Bloqueia automaticamente a pausa de cada barbeiro</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Confirmação via WhatsApp:</strong> Envie lembrete pronto com horário e serviço em 1 clique</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Múltiplos Serviços no Mesmo Horário:</strong> Soma automática do tempo de Corte + Barba + Sobrancelha</span>
                                </li>
                            </ul>
                        </div>
                        <div class="lg:col-span-7">
                            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-4 sm:p-6 shadow-2xl space-y-4">
                                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                    <div>
                                        <span class="text-xs font-black text-white">Agenda Diária da Equipe • Sábado</span>
                                        <span class="block text-[11px] text-slate-400">18 cortes agendados • R$ 940,00 previsto hoje</span>
                                    </div>
                                    <span class="px-3 py-1.5 rounded-xl bg-amber-500 text-slate-950 font-black text-xs">+ Novo Encaixe</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {{-- Coluna Barbeiro 1 --}}
                                    <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-3 space-y-2.5">
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-amber-500 text-slate-950 font-black text-xs flex items-center justify-center">M</div>
                                                <div>
                                                    <div class="text-xs font-bold text-white">Marinho (Master)</div>
                                                    <div class="text-[10px] text-slate-400">08:00 às 19:00 • Almoço 12h-13h</div>
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">9 cortes</span>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-slate-950 border-l-4 border-emerald-500 flex items-center justify-between">
                                            <div>
                                                <div class="text-xs font-bold text-white">09:00 • Rafael Costa</div>
                                                <div class="text-[10px] text-slate-400">Corte Degradê + Barboterapia (60 min)</div>
                                            </div>
                                            <span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded">✓ Concluído</span>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-slate-950 border-l-4 border-amber-500 flex items-center justify-between">
                                            <div>
                                                <div class="text-xs font-bold text-white">10:00 • Lucas Mendes</div>
                                                <div class="text-[10px] text-slate-400">Corte Social + Sobrancelha (45 min)</div>
                                            </div>
                                            <span class="text-[10px] font-bold bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded">✂️ Na Cadeira</span>
                                        </div>
                                    </div>
                                    {{-- Coluna Barbeiro 2 --}}
                                    <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-3 space-y-2.5">
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-sky-500 text-slate-950 font-black text-xs flex items-center justify-center">G</div>
                                                <div>
                                                    <div class="text-xs font-bold text-white">Gabriel Barber</div>
                                                    <div class="text-[10px] text-slate-400">09:00 às 20:00 • Comissão 50%</div>
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-mono text-sky-400 bg-sky-500/10 px-2 py-0.5 rounded-full">9 cortes</span>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-slate-950 border-l-4 border-sky-500 flex items-center justify-between">
                                            <div>
                                                <div class="text-xs font-bold text-white">09:30 • Bruno Oliveira</div>
                                                <div class="text-[10px] text-slate-400">Combo VIP Corte + Barba (60 min)</div>
                                            </div>
                                            <span class="text-[10px] font-bold bg-sky-500/20 text-sky-300 px-2 py-0.5 rounded">Confirmado</span>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-slate-950 border-l-4 border-purple-500 flex items-center justify-between">
                                            <div>
                                                <div class="text-xs font-bold text-white">10:30 • Thiago Rocha</div>
                                                <div class="text-[10px] text-slate-400">Corte Navalhado + Pomada (30 min)</div>
                                            </div>
                                            <span class="text-[10px] font-bold bg-purple-500/20 text-purple-300 px-2 py-0.5 rounded">Agendado</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ABA 2: PÁGINA PÚBLICA WHITE-LABEL & PWA --}}
                    <div x-show="activeShowcaseTab === 'vitrine'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-5 space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-300 text-xs font-bold">
                                <span>🌐</span>
                                <span>White-Label + App Instalável (PWA)</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                                Seu cliente agenda em 30 segundos pelo link da Bio ou instala o App da sua Barbearia
                            </h3>
                            <p class="text-sm text-slate-300 leading-relaxed">
                                Esqueça aplicativos que obrigam o cliente a lembrar senha ou baixar 80MB na loja. Sua barbearia ganha um endereço exclusivo com a <strong>sua logomarca, foto de capa e cor personalizada</strong>, que também instala como aplicativo direto na tela inicial do celular.
                            </p>
                            <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Identidade Visual Própria:</strong> Escolha a cor da sua marca, logotipo e banner pelo painel</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Atalho de App Nativo (PWA):</strong> Ícone da sua barbearia na tela inicial do Android e iPhone</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Zero Conflito de Horários:</strong> Calcula automaticamente a duração somada dos serviços escolhidos</span>
                                </li>
                            </ul>
                        </div>
                        <div class="lg:col-span-7">
                            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-5 shadow-2xl space-y-4">
                                <div class="flex items-center justify-between bg-slate-900 p-3.5 rounded-2xl border border-slate-800">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-lg">💈</div>
                                        <div>
                                            <div class="text-sm font-black text-white">Barbearia Imperial Club</div>
                                            <div class="text-[11px] text-slate-400">📍 Fortaleza / CE • Link Oficial de Agendamento</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1.5 rounded-lg bg-slate-800 border border-slate-700 text-amber-400 text-[11px] font-bold">📲 Instalar App</span>
                                        <span class="px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] font-bold">WhatsApp</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="p-3.5 rounded-xl bg-slate-900 border-2 border-amber-500 flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-bold text-white">✓ Corte Degradê Navalhado</div>
                                            <div class="text-[11px] text-slate-400">30 min • Toalha quente inclusa</div>
                                        </div>
                                        <span class="text-xs font-black text-amber-400">R$ 45,00</span>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-slate-900 border-2 border-amber-500 flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-bold text-white">✓ Barboterapia com Ozônio</div>
                                            <div class="text-[11px] text-slate-400">30 min • Hidratação completa</div>
                                        </div>
                                        <span class="text-xs font-black text-amber-400">R$ 35,00</span>
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center justify-between">
                                    <div class="text-xs text-slate-300">
                                        <strong>2 serviços selecionados</strong> (60 min) • Horário: <span class="text-amber-400 font-bold">Hoje às 16:00</span>
                                    </div>
                                    <span class="px-4 py-2 rounded-xl bg-amber-500 text-slate-950 font-black text-xs">Confirmar R$ 80,00 ➔</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ABA 3: COMISSÕES & RECIBO JURÍDICO --}}
                    <div x-show="activeShowcaseTab === 'comissoes'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-5 space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 text-amber-300 text-xs font-bold">
                                <span>💰</span>
                                <span>Transparência & Segurança Trabalhista</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                                Fechamento de Comissões em 1 Clique com Recibo de Quitação Assinável
                            </h3>
                            <p class="text-sm text-slate-300 leading-relaxed">
                                Chega de somar comissões na calculadora no fim do expediente. O AuroBarber separa automaticamente a porcentagem de cada barbeiro e a retenção líquida da barbearia, gerando um <strong>Recibo com Canhoto Jurídico de Quitação</strong> pronto para impressão.
                            </p>
                            <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Porcentagem Personalizada:</strong> Defina 40%, 50%, 60% individualmente por barbeiro</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Filtro por Período:</strong> Apure o dia, a semana ou o mês inteiro instantaneamente</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Recibo com Canhoto de Quitação:</strong> Comprovante formal com número de protocolo e assinatura</span>
                                </li>
                            </ul>
                        </div>
                        <div class="lg:col-span-7">
                            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-5 shadow-2xl space-y-4">
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800">
                                        <div class="text-[10px] uppercase font-bold text-slate-400">Produção da Semana</div>
                                        <div class="text-lg font-black text-white mt-0.5">R$ 2.840,00</div>
                                        <div class="text-[10px] text-slate-500">58 cortes concluídos</div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-slate-900 border border-amber-500/30">
                                        <div class="text-[10px] uppercase font-bold text-amber-400">Comissão Barbeiro (50%)</div>
                                        <div class="text-lg font-black text-amber-400 mt-0.5">R$ 1.420,00</div>
                                        <div class="text-[10px] text-slate-500">Líquido a repassar</div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-slate-900 border border-emerald-500/30">
                                        <div class="text-[10px] uppercase font-bold text-emerald-400">Lucro da Barbearia</div>
                                        <div class="text-lg font-black text-emerald-400 mt-0.5">R$ 1.420,00</div>
                                        <div class="text-[10px] text-slate-500">Retenção da casa</div>
                                    </div>
                                </div>
                                <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-bold text-white">Recibo #00142 • Liquidado via PIX</div>
                                        <div class="text-[11px] text-slate-400">Declaração de quitação pronta para impressão térmica ou PDF</div>
                                    </div>
                                    <span class="px-3.5 py-2 rounded-xl bg-emerald-500 text-slate-950 font-black text-xs">🖨️ Imprimir Recibo</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ABA 4: COMANDA DE BAR, PRODUTOS & CAIXA --}}
                    <div x-show="activeShowcaseTab === 'comanda'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-5 space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 text-amber-300 text-xs font-bold">
                                <span>🍺</span>
                                <span>Aumente o Ticket Médio em cada Corte</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                                Comanda Integrada com Estoque de Bebidas, Pomadas e Fechamento de Caixa
                            </h3>
                            <p class="text-sm text-slate-300 leading-relaxed">
                                O cliente pediu uma cerveja gelada ou levou uma pomada modeladora durante o corte? Adicione tudo na mesma comanda ao concluir o atendimento. O sistema soma no total a receber, lança no caixa e dá baixa automática no estoque.
                            </p>
                            <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Alerta de Estoque Mínimo:</strong> Avisa em vermelho quando a cerveja ou lâmina está acabando</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Venda Rápida de Balcão:</strong> Venda produtos avulsos mesmo sem agendamento</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Gaveta & Fluxo Diário:</strong> Separação exata de PIX, Cartão, Dinheiro, Sangrias e Suprimentos</span>
                                </li>
                            </ul>
                        </div>
                        <div class="lg:col-span-7">
                            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-5 shadow-2xl space-y-3">
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Checkout da Comanda #418 • Cliente: Diego Martins</div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
                                    <span class="text-white font-bold">✂️ Corte Degradê + Barba</span>
                                    <span class="text-slate-300 font-mono">R$ 65,00</span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
                                    <span class="text-amber-400 font-bold">🍺 2x Cerveja Heineken Long Neck (Estoque: 24 un.)</span>
                                    <span class="text-amber-400 font-mono">+ R$ 24,00</span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
                                    <span class="text-sky-400 font-bold">💈 1x Pomada Matte Efeito Seco 120g</span>
                                    <span class="text-sky-400 font-mono">+ R$ 35,00</span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between">
                                    <span class="text-xs font-black text-emerald-300">TOTAL RECEBIDO NO PIX</span>
                                    <span class="text-lg font-black text-emerald-400 font-mono">R$ 124,00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ABA 5: CARTÃO FIDELIDADE & CLIENTES SUMIDOS --}}
                    <div x-show="activeShowcaseTab === 'fidelidade'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-5 space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-pink-500/15 text-pink-300 text-xs font-bold">
                                <span>🎁</span>
                                <span>Retenção & Recuperação Automática</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                                Cartão Fidelidade Digital e Alerta de "Clientes Sumidos" com WhatsApp em 1 Clique
                            </h3>
                            <p class="text-sm text-slate-300 leading-relaxed">
                                A cada atendimento concluído, o sistema soma automaticamente <strong>+1 selo no Cartão Fidelidade</strong> do cliente. E quando um cliente passa mais de 20 dias sem cortar, ele entra na fila de recuperação para você chamá-lo no WhatsApp com mensagem pronta!
                            </p>
                            <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Fim do Cartãozinho de Papel:</strong> O sistema controla quantos selos cada cliente tem</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Alerta de Retorno (+20 dias):</strong> Recupere clientes antes que eles cortem no concorrente</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Aniversariantes do Mês:</strong> Envie parabéns e convite especial pelo WhatsApp</span>
                                </li>
                            </ul>
                        </div>
                        <div class="lg:col-span-7">
                            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-5 shadow-2xl space-y-3">
                                <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <div class="text-xs font-black text-white">Henrique Soares • (85) 99812-4400</div>
                                        <div class="text-[11px] text-slate-400">Preferência: Degradê na zero alta, gosta de café expresso</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-black">🎁 PRÊMIO LIBERADO (10/10 selos)</span>
                                        <span class="px-3 py-1 rounded-lg bg-amber-500 text-slate-950 text-[11px] font-black">Resgatar Corte</span>
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <div class="text-xs font-black text-white">Matheus Lima • (85) 98744-1122</div>
                                        <div class="text-[11px] text-rose-400 font-semibold">⏰ Último corte há 26 dias • Hora de chamar!</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-bold">⭐ 7 / 10 selos</span>
                                        <span class="px-3 py-1 rounded-lg bg-emerald-600 text-white text-[11px] font-bold">💬 Chamar WhatsApp</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ABA 6: VISÃO RESTRITA DO BARBEIRO --}}
                    <div x-show="activeShowcaseTab === 'barbeiro'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-5 space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/15 text-purple-300 text-xs font-bold">
                                <span>💈</span>
                                <span>Privacidade Financeira Absoluta</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                                Acesso Individual para cada Barbeiro ver apenas a Própria Agenda e Comissão
                            </h3>
                            <p class="text-sm text-slate-300 leading-relaxed">
                                Crie um login para cada profissional da sua equipe. Quando o barbeiro entra pelo celular dele, o AuroBarber ativa automaticamente a <strong>Visão Restrita</strong>: ele acompanha os próprios agendamentos do dia e quanto já ganhou de comissão, sem ver o faturamento da barbearia nem a agenda dos colegas.
                            </p>
                            <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Sigilo do Caixa Geral:</strong> O barbeiro não vê o lucro da casa nem relatórios estratégicos</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Segurança na Liquidação:</strong> Apenas o dono/gerente pode dar baixa como pago nas comissões</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span><strong>Avaliação 5 Estrelas Pós-Corte:</strong> O cliente avalia o atendimento pelo link enviado no WhatsApp</span>
                                </li>
                            </ul>
                        </div>
                        <div class="lg:col-span-7">
                            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-5 shadow-2xl space-y-4">
                                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                    <span class="text-xs font-bold text-purple-400">🔒 Visão Restrita do Colaborador • Logado como: Gabriel Barber</span>
                                    <span class="text-[10px] bg-purple-500/20 text-purple-300 px-2.5 py-0.5 rounded-full font-bold">Apenas Meus Dados</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                        <div class="text-[10px] text-slate-400">Minha Comissão Hoje</div>
                                        <div class="text-sm font-black text-emerald-400 mt-0.5">R$ 185,00</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                        <div class="text-[10px] text-slate-400">Meus Cortes Hoje</div>
                                        <div class="text-sm font-black text-white mt-0.5">8 clientes</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                        <div class="text-[10px] text-slate-400">Comissão no Mês</div>
                                        <div class="text-sm font-black text-sky-400 mt-0.5">R$ 3.420,00</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                        <div class="text-[10px] text-slate-400">Pendente a Receber</div>
                                        <div class="text-sm font-black text-amber-400 mt-0.5">R$ 640,00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- MÓDULOS INTEGRADOS DO ECOSSISTEMA --}}
        <section id="modulos" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-14">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Ecossistema Completo</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                        Tudo o que sua barbearia precisa em uma única assinatura
                    </h2>
                    <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                        Sem cobrança extra por módulo e sem taxas escondidas sobre os seus cortes.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-amber-500/40 hover:bg-slate-900 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">📱</div>
                        <h3 class="text-base font-bold text-white group-hover:text-amber-400 transition">Página Pública & App PWA</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">Link próprio White-Label com suas cores e logotipo, instalável na tela inicial do celular dos seus clientes.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-emerald-500/40 hover:bg-slate-900 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">💰</div>
                        <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition">Comissões & Recibos</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">Cálculo automático de comissões por barbeiro e emissão de recibo jurídico assinado para segurança trabalhista.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-sky-500/40 hover:bg-slate-900 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">🍺</div>
                        <h3 class="text-base font-bold text-white group-hover:text-sky-400 transition">Bar, Produtos & Estoque</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">Controle de bebidas, pomadas e insumos com alerta de estoque baixo e lançamento direto na comanda do corte.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-pink-500/40 hover:bg-slate-900 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-xl bg-pink-500/10 border border-pink-500/20 text-pink-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">🎁</div>
                        <h3 class="text-base font-bold text-white group-hover:text-pink-400 transition">Cartão Fidelidade & CRM</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">Selos automáticos a cada visita, alerta de clientes sumidos há +20 dias e lista de aniversariantes do mês.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-purple-500/40 hover:bg-slate-900 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">💈</div>
                        <h3 class="text-base font-bold text-white group-hover:text-purple-400 transition">Acesso Restrito do Barbeiro</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">Cada colaborador acessa pelo próprio celular apenas sua agenda e extrato de comissões, sem ver o caixa da casa.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-cyan-500/40 hover:bg-slate-900 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">🏦</div>
                        <h3 class="text-base font-bold text-white group-hover:text-cyan-400 transition">Fechamento de Caixa Diário</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">Controle de abertura de gaveta, sangrias, suprimentos e conferência por PIX, Dinheiro, Crédito e Débito.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-orange-500/40 hover:bg-slate-900 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">⭐</div>
                        <h3 class="text-base font-bold text-white group-hover:text-orange-400 transition">Pesquisa 5 Estrelas & Ranking</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">Link pós-atendimento para o cliente avaliar de 1 a 5 estrelas e relatórios de desempenho da equipe.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-indigo-500/40 hover:bg-slate-900 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">🛡️</div>
                        <h3 class="text-base font-bold text-white group-hover:text-indigo-400 transition">Segurança 2FA + 30 Dias</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">Autenticação em 2 fatores (App ou E-mail) com opção de lembrar seu dispositivo confiável por 30 dias.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- COMPARATIVO BRUTAL --}}
        <section id="comparativo" class="py-20 sm:py-28 bg-slate-900/60 border-b border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">A Evolução da Sua Barbearia</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                        Por que as barbearias mais lucrativas estão migrando para o AuroBarber?
                    </h2>
                    <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                        Compare a rotina de quem ainda depende de caderno ou aplicativos engessados versus a liberdade do AuroBarber Cloud.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                    {{-- Coluna 1: O Jeito Antigo --}}
                    <div class="rounded-3xl border border-rose-500/30 bg-slate-950/80 p-8 space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-2xl bg-rose-500/15 text-rose-400 font-black text-xl flex items-center justify-center">✕</div>
                            <div>
                                <h3 class="text-lg font-black text-white">WhatsApp Manual, Caderno ou Apps Caros</h3>
                                <span class="text-xs text-rose-400 font-semibold">Tempo perdido, furos na agenda e taxas ocultas</span>
                            </div>
                        </div>

                        <ul class="space-y-4 text-xs sm:text-sm text-slate-300">
                            <li class="flex items-start gap-3">
                                <span class="text-rose-400 font-black mt-0.5">✕</span>
                                <span>Parar o corte a cada 10 minutos para responder "Tem vaga hoje às 17h?" no WhatsApp.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-rose-400 font-black mt-0.5">✕</span>
                                <span>Clientes que desistem de agendar porque o aplicativo pede cadastro longo, e-mail e senha.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-rose-400 font-black mt-0.5">✕</span>
                                <span>Confusão no sábado à noite para somar os cortes e descontar vales de cada barbeiro.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-rose-400 font-black mt-0.5">✕</span>
                                <span>Cervejas e pomadas que somem da geladeira/prateleira sem serem anotadas na comanda.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-rose-400 font-black mt-0.5">✕</span>
                                <span>Clientes fiéis que passam 40 dias sem voltar porque ninguém mandou um lembrete.</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Coluna 2: Com AuroBarber --}}
                    <div class="rounded-3xl border-2 border-amber-500/50 bg-slate-950 p-8 space-y-6 shadow-2xl shadow-amber-500/10 relative">
                        <div class="absolute -top-3.5 right-6 px-3 py-1 rounded-full bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 font-black text-[10px] uppercase tracking-wider shadow">
                            Padrão Ouro Aurosoft
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-2xl bg-amber-500/15 text-amber-400 font-black text-xl flex items-center justify-center">✓</div>
                            <div>
                                <h3 class="text-lg font-black text-white">Com AuroBarber Cloud</h3>
                                <span class="text-xs text-amber-400 font-semibold">Agenda 24h, controle absoluto e fidelização</span>
                            </div>
                        </div>

                        <ul class="space-y-4 text-xs sm:text-sm text-slate-200">
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-black mt-0.5">✓</span>
                                <span><strong>Agendamento 24h em 30 segundos:</strong> o cliente escolhe serviço e horário sozinho, até de madrugada.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-black mt-0.5">✓</span>
                                <span><strong>App Instalável (PWA) sem Senha:</strong> atalho direto na tela inicial do celular com a marca da sua barbearia.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-black mt-0.5">✓</span>
                                <span><strong>Comissões Automáticas & Visão Restrita:</strong> cada barbeiro acompanha sua comissão e assina o recibo formal.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-black mt-0.5">✓</span>
                                <span><strong>Comanda de Bar & Estoque Integrado:</strong> bebidas e cosméticos somados no corte com baixa automática.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-black mt-0.5">✓</span>
                                <span><strong>Máquina de Retorno:</strong> cartão fidelidade digital e alerta automático para chamar clientes sumidos no WhatsApp.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- CALCULADORA INTERATIVA DE LUCRO & RETORNO --}}
        <section id="calculadora" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900/90 to-amber-950/20 p-8 sm:p-12 shadow-2xl space-y-10">
                    <div class="text-center max-w-2xl mx-auto space-y-2">
                        <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Simulador de Impacto Financeiro</span>
                        <h2 class="text-2xl sm:text-4xl font-black text-white">Quanto sua barbearia pode faturar a mais por ano?</h2>
                        <p class="text-xs sm:text-sm text-slate-400">
                            Ajuste abaixo a média de atendimentos diários da sua barbearia e veja o ganho estimado com redução de faltas, retorno de clientes e comanda de produtos:
                        </p>
                    </div>

                    {{-- Sliders Interativos --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-3xl mx-auto">
                        <div class="space-y-3 bg-slate-950/60 p-5 rounded-2xl border border-slate-800">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-400">Cortes realizados por dia:</span>
                                <span class="text-xl font-black text-amber-400 font-mono" x-text="calcCutsPerDay + ' cortes/dia'"></span>
                            </div>
                            <input
                                type="range"
                                min="5"
                                max="80"
                                step="1"
                                x-model="calcCutsPerDay"
                                class="w-full h-3 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-500"
                            />
                            <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                                <span>5/dia</span>
                                <span>25/dia</span>
                                <span>50/dia</span>
                                <span>80/dia</span>
                            </div>
                        </div>

                        <div class="space-y-3 bg-slate-950/60 p-5 rounded-2xl border border-slate-800">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-400">Ticket Médio atual:</span>
                                <span class="text-xl font-black text-emerald-400 font-mono" x-text="'R$ ' + calcAvgTicket"></span>
                            </div>
                            <input
                                type="range"
                                min="25"
                                max="120"
                                step="5"
                                x-model="calcAvgTicket"
                                class="w-full h-3 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500"
                            />
                            <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                                <span>R$ 25</span>
                                <span>R$ 50</span>
                                <span>R$ 85</span>
                                <span>R$ 120</span>
                            </div>
                        </div>
                    </div>

                    {{-- Resultados da Calculadora --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                            <span class="text-xs text-slate-400 font-medium block">Tempo Poupado no WhatsApp</span>
                            <div class="text-2xl sm:text-3xl font-black text-amber-400 font-mono mt-1" x-text="Math.round(calcCutsPerDay * 2.2) + 'h / mês'"></div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Com agendamento online automático</span>
                        </div>

                        <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                            <span class="text-xs text-slate-400 font-medium block">Receita Extra (Retorno + Bar)</span>
                            <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono mt-1" x-text="'+ R$ ' + (Math.round(calcCutsPerDay * 26 * calcAvgTicket * 0.16)).toLocaleString('pt-BR') + ' / mês'"></div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Clientes recuperados + produtos na comanda</span>
                        </div>

                        <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                            <span class="text-xs text-slate-400 font-medium block">Crescimento Anual Estimado</span>
                            <div class="text-2xl sm:text-3xl font-black text-sky-400 font-mono mt-1" x-text="'+ R$ ' + (Math.round(calcCutsPerDay * 26 * calcAvgTicket * 0.16 * 12)).toLocaleString('pt-BR') + ' / ano'"></div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Lucro retido sem taxas por agendamento</span>
                        </div>
                    </div>

                    <div class="text-center pt-2">
                        <button
                            type="button"
                            @click="leadModalOpen = true"
                            class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 hover:to-emerald-300 text-slate-950 font-black text-xs transition shadow-lg shadow-emerald-500/20 active:scale-95 cursor-pointer"
                        >
                            <span>🚀</span>
                            <span>Quero Alcançar Esses Números na Minha Barbearia</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- PLANOS TRANSPARENTES --}}
        <section id="planos" class="py-20 sm:py-28 bg-slate-900/40 border-b border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-14">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">InvestimentoAcessível & Sem Pegadinhas</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                        Escolha o plano ideal para o momento da sua barbearia
                    </h2>
                    <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                        Todos os planos incluem página pública de agendamento, atalho PWA, cartão fidelidade e suporte direto.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto items-stretch">
                    {{-- Plano 1: Solo / Starter --}}
                    <div class="rounded-3xl border border-slate-800 bg-slate-950/90 p-7 flex flex-col justify-between">
                        <div class="space-y-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Barbeiro Autônomo / Studio</span>
                            <h3 class="text-xl font-black text-white">Plano Studio</h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-black text-white font-mono">R$ 59,90</span>
                                <span class="text-xs text-slate-400">/mês</span>
                            </div>
                            <p class="text-xs text-slate-400">Ideal para barbeiros individuais ou estúdios com até 2 profissionais.</p>
                            <ul class="space-y-2.5 text-xs text-slate-300 pt-3 border-t border-slate-800">
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Até 2 Barbeiros na Agenda</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Agendamentos Online Ilimitados</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Página White-Label + App PWA</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Cartão Fidelidade & Lembretes WhatsApp</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Fechamento de Caixa Diário</li>
                            </ul>
                        </div>
                        <button type="button" @click="leadBarbers = '1 a 2 cadeiras (Plano Studio)'; leadModalOpen = true" class="mt-8 w-full py-3.5 rounded-xl border border-slate-700 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs transition cursor-pointer">
                            Começar com Plano Studio
                        </button>
                    </div>

                    {{-- Plano 2: Profissional / Equipe (Destaque) --}}
                    <div class="rounded-3xl border-2 border-amber-500 bg-slate-950 p-7 flex flex-col justify-between shadow-2xl shadow-amber-500/10 relative">
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 font-black text-[10px] uppercase tracking-wider shadow">
                            Mais Escolhido
                        </div>
                        <div class="space-y-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Barbearia com Equipe</span>
                            <h3 class="text-xl font-black text-white">Plano Profissional PRO</h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-black text-amber-400 font-mono">R$ 97,00</span>
                                <span class="text-xs text-slate-400">/mês</span>
                            </div>
                            <p class="text-xs text-slate-300">O pacote completo para barbearias em crescimento com equipe comissionada.</p>
                            <ul class="space-y-2.5 text-xs text-slate-200 pt-3 border-t border-slate-800">
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Até 10 Barbeiros</strong> com Login Individual</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Visão Restrita do Barbeiro</strong> no Celular</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Cálculo de Comissões + Recibo Jurídico</strong></li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Comanda de Bar, Bebidas & Estoque</strong></li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>CRM Clientes Sumidos & Avaliação 5★</strong></li>
                            </ul>
                        </div>
                        <button type="button" @click="leadBarbers = '3 a 10 cadeiras (Plano PRO)'; leadModalOpen = true" class="mt-8 w-full py-3.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 hover:brightness-110 text-slate-950 font-black text-xs transition shadow-lg shadow-amber-500/20 cursor-pointer">
                            Contratar Plano Profissional PRO ➔
                        </button>
                    </div>

                    {{-- Plano 3: Rede / Multi-Unidades --}}
                    <div class="rounded-3xl border border-slate-800 bg-slate-950/90 p-7 flex flex-col justify-between">
                        <div class="space-y-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Franquias & Grandes Equipes</span>
                            <h3 class="text-xl font-black text-white">Plano Elite & Redes</h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-black text-white font-mono">R$ 167,00</span>
                                <span class="text-xs text-slate-400">/mês</span>
                            </div>
                            <p class="text-xs text-slate-400">Para grandes barbearias ou donos com mais de uma unidade/filial.</p>
                            <ul class="space-y-2.5 text-xs text-slate-300 pt-3 border-t border-slate-800">
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Barbeiros Ilimitados</strong></li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Gestão Multi-Unidades</strong> no Mesmo Login</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Cargos Customizados (Recepção, Gerente)</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Implantação Assistida + Suporte VIP</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Segurança 2FA Corporativa</li>
                            </ul>
                        </div>
                        <button type="button" @click="leadBarbers = 'Rede / Multi-Unidades (Plano Elite)'; leadModalOpen = true" class="mt-8 w-full py-3.5 rounded-xl border border-slate-700 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs transition cursor-pointer">
                            Falar com Consultor Elite
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ ACORDEÃO --}}
        <section id="faq" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Tire suas dúvidas</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">Perguntas Frequentes</h2>
                </div>

                <div class="space-y-3">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 cursor-pointer" @click="faqOpen = (faqOpen === 1 ? null : 1)">
                        <div class="flex items-center justify-between font-bold text-sm text-white">
                            <span>O meu cliente precisa baixar aplicativo na loja ou criar senha para agendar?</span>
                            <span class="text-amber-400 font-mono text-base" x-text="faqOpen === 1 ? '−' : '+'"></span>
                        </div>
                        <div x-show="faqOpen === 1" x-cloak class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
                            Não! O cliente clica no seu link exclusivo (no Instagram ou WhatsApp), escolhe o serviço, o barbeiro e o horário livre e confirma apenas com Nome e WhatsApp em 30 segundos. E se quiser, ele ainda pode clicar em <strong>"📲 Instalar App"</strong> para deixar o ícone da sua barbearia direto na tela inicial do celular.
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 cursor-pointer" @click="faqOpen = (faqOpen === 2 ? null : 2)">
                        <div class="flex items-center justify-between font-bold text-sm text-white">
                            <span>Meus barbeiros conseguem ver o faturamento total da minha barbearia?</span>
                            <span class="text-amber-400 font-mono text-base" x-text="faqOpen === 2 ? '−' : '+'"></span>
                        </div>
                        <div x-show="faqOpen === 2" x-cloak class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
                            Não! Quando você cria um login de colaborador para o barbeiro, o sistema ativa automaticamente a <strong>Visão Restrita do Barbeiro</strong>: ele vê apenas a própria cadeira/agenda e o próprio extrato de comissões, sem ter acesso ao caixa geral, lucro da casa, estoque ou configurações.
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 cursor-pointer" @click="faqOpen = (faqOpen === 3 ? null : 3)">
                        <div class="flex items-center justify-between font-bold text-sm text-white">
                            <span>O sistema cobra alguma taxa ou porcentagem sobre os meus agendamentos ou Pix?</span>
                            <span class="text-amber-400 font-mono text-base" x-text="faqOpen === 3 ? '−' : '+'"></span>
                        </div>
                        <div x-show="faqOpen === 3" x-cloak class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
                            Zero taxa! 100% do dinheiro pago pelos seus clientes (seja em Pix, cartão ou espécie) vai direto para o seu bolso. Você paga apenas a assinatura fixa mensal do software.
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 cursor-pointer" @click="faqOpen = (faqOpen === 4 ? null : 4)">
                        <div class="flex items-center justify-between font-bold text-sm text-white">
                            <span>Eu mesmo consigo alterar a logo, cor, serviços e horários da minha barbearia?</span>
                            <span class="text-amber-400 font-mono text-base" x-text="faqOpen === 4 ? '−' : '+'"></span>
                        </div>
                        <div x-show="faqOpen === 4" x-cloak class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3">
                            Sim! Pelo menu <strong>Configurações da Barbearia</strong>, o proprietário altera a qualquer momento o nome, link, cor principal da marca, logotipo, foto de capa, horários de funcionamento, intervalos de almoço dos barbeiros e regras do Cartão Fidelidade.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA FINAL --}}
        <section class="py-16 sm:py-24 bg-gradient-to-b from-slate-950 to-slate-900 border-b border-slate-800/80 relative overflow-hidden">
            <div class="max-w-4xl mx-auto px-4 text-center space-y-6 relative z-10">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Pronto para profissionalizar sua barbearia?</span>
                <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight">
                    Coloque sua agenda, comanda e comissões no padrão ouro hoje mesmo
                </h2>
                <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
                    Implantação rápida em minutos: cadastre seus barbeiros, compartilhe seu link exclusivo no Instagram e veja os agendamentos entrarem sozinhos.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 pt-2">
                    <button
                        type="button"
                        @click="leadModalOpen = true"
                        class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-300 hover:brightness-110 text-slate-950 font-black text-sm transition shadow-xl shadow-amber-500/25 active:scale-95 cursor-pointer"
                    >
                        <span>🚀</span>
                        <span>Solicitar Liberação da Minha Barbearia</span>
                    </button>

                    <button
                        type="button"
                        @click="tenantModalOpen = true"
                        class="w-full sm:w-auto px-8 py-4 rounded-2xl border border-slate-700 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition shadow-xl active:scale-95 cursor-pointer"
                    >
                        <span>💈</span>
                        <span>Já Sou Cliente • Acessar Painel</span>
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
                    💈
                </div>
                <div>
                    <span class="font-extrabold text-white text-sm">AuroBarber</span>
                    <span class="block text-[11px] text-slate-400">Uma solução oficial Aurosoft Tecnologia</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-6 text-slate-400">
                <a href="#recursos" class="hover:text-amber-400 transition">Tour Visual</a>
                <a href="#modulos" class="hover:text-amber-400 transition">Módulos</a>
                <a href="#calculadora" class="hover:text-amber-400 transition">Simulador</a>
                <a href="#planos" class="hover:text-amber-400 transition">Planos</a>
                <a href="{{ url('/admin') }}" class="hover:text-amber-400 transition">Painel da Barbearia</a>
                <a href="{{ url('/saas') }}" class="hover:text-amber-400 transition">SaaS Master</a>
            </div>

            <div class="text-center md:text-right text-[11px] text-slate-400">
                &copy; {{ date('Y') }} AuroBarber &middot; Desenvolvido por Aurosoft Tecnologia.
            </div>
        </div>
    </footer>

    {{-- ========================================================================= --}}
    {{-- MODAL 1: ACESSAR MINHA BARBEARIA (EM ABAS + LARGURA TOTAL)                --}}
    {{-- ========================================================================= --}}
    <div
        x-show="tenantModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
        @keydown.escape.window="tenantModalOpen = false"
    >
        <div class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-2xl space-y-6"
             @click.away="tenantModalOpen = false">
            <button
                type="button"
                @click="tenantModalOpen = false"
                class="absolute top-5 right-5 text-slate-400 hover:text-white text-xl cursor-pointer"
            >
                ✕
            </button>

            <div class="space-y-1.5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400 text-2xl border border-amber-500/20">
                    💈
                </div>
                <h3 class="text-xl font-black text-white">Acessar Barbearia</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Escolha se deseja entrar no Painel de Gestão (Dono / Barbeiro) ou abrir a Página de Agendamento Online.
                </p>
            </div>

            {{-- Abas do Modal --}}
            <div class="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-slate-950 border border-slate-800">
                <button
                    type="button"
                    @click="tenantTab = 'painel'"
                    :class="tenantTab === 'painel' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white font-bold'"
                    class="py-2.5 rounded-xl text-xs transition cursor-pointer"
                >
                    🔐 Painel da Equipe
                </button>
                <button
                    type="button"
                    @click="tenantTab = 'publica'"
                    :class="tenantTab === 'publica' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white font-bold'"
                    class="py-2.5 rounded-xl text-xs transition cursor-pointer"
                >
                    📅 Página de Agendamento
                </button>
            </div>

            {{-- Aba 1: Painel Administrativo --}}
            <div x-show="tenantTab === 'painel'" class="space-y-4">
                @if($activeBarbershops->isNotEmpty())
                    <div class="space-y-2">
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Unidades Ativas no AuroBarber:</span>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            @foreach($activeBarbershops as $shop)
                                <a
                                    href="{{ url('/admin/' . $shop->slug) }}"
                                    class="w-full flex items-center justify-between px-4 py-3 rounded-2xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-amber-500/40 transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-base">💈</span>
                                        <div>
                                            <span class="text-xs font-bold text-white block">{{ $shop->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">barber.aurosoft.com.br/admin/{{ $shop->slug }}</span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-amber-400">Entrar &rarr;</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <a
                    href="{{ url('/admin') }}"
                    class="w-full block text-center rounded-2xl bg-gradient-to-r from-amber-500 to-amber-400 hover:brightness-110 py-3.5 text-xs font-black text-slate-950 transition shadow-lg shadow-amber-500/20"
                >
                    Ir para Login Geral do Painel ➔
                </a>
            </div>

            {{-- Aba 2: Página Pública de Agendamento --}}
            <div x-show="tenantTab === 'publica'" x-cloak class="space-y-4">
                @if($activeBarbershops->isNotEmpty())
                    <div class="space-y-2">
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Escolha a Barbearia para Agendar:</span>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            @foreach($activeBarbershops as $shop)
                                <a
                                    href="{{ url('/' . $shop->slug) }}"
                                    class="w-full flex items-center justify-between px-4 py-3 rounded-2xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-emerald-500/40 transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-base">✂️</span>
                                        <div>
                                            <span class="text-xs font-bold text-white block">{{ $shop->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">barber.aurosoft.com.br/{{ $shop->slug }}</span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-emerald-400">Agendar &rarr;</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form onsubmit="event.preventDefault(); const s = document.getElementById('barber-slug-input').value.trim().toLowerCase(); if(s) window.location.href = '/' + s;" class="space-y-3 pt-2 border-t border-slate-800">
                    <label class="text-[11px] font-bold text-slate-300 block">Ou digite o link da barbearia:</label>
                    <input
                        id="barber-slug-input"
                        type="text"
                        required
                        placeholder="ex: barbearia-marinho"
                        class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-xs text-white focus:border-amber-400 focus:outline-none font-mono"
                    />
                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-emerald-500 hover:bg-emerald-400 py-3.5 text-xs font-black text-slate-950 transition cursor-pointer"
                    >
                        Abrir Página de Agendamento ➔
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 2: SOLICITAR DEMONSTRAÇÃO / CRIAR BARBEARIA (EM ABAS + FULL WIDTH)  --}}
    {{-- ========================================================================= --}}
    <div
        x-show="leadModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
        @keydown.escape.window="leadModalOpen = false"
    >
        <div class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-2xl space-y-6 max-h-[92vh] overflow-y-auto"
             @click.away="leadModalOpen = false">
            <button
                type="button"
                @click="leadModalOpen = false"
                class="absolute top-5 right-5 text-slate-400 hover:text-white text-xl cursor-pointer"
            >
                ✕
            </button>

            <div class="space-y-1.5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-400 text-2xl border border-emerald-500/20">
                    🚀
                </div>
                <h3 class="text-xl font-black text-white">Implantar AuroBarber na Minha Barbearia</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Preencha os dados abaixo para liberarmos seu ambiente personalizado com seu link exclusivo.
                </p>
            </div>

            {{-- Abas do Formulário (Padrão Tabs + Full Width) --}}
            <div class="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-slate-950 border border-slate-800">
                <button
                    type="button"
                    @click="leadTab = 'dados'"
                    :class="leadTab === 'dados' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white font-bold'"
                    class="py-2.5 rounded-xl text-xs transition cursor-pointer"
                >
                    1. Dados da Barbearia
                </button>
                <button
                    type="button"
                    @click="leadTab = 'perfil'"
                    :class="leadTab === 'perfil' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white font-bold'"
                    class="py-2.5 rounded-xl text-xs transition cursor-pointer"
                >
                    2. Equipe & Plano
                </button>
            </div>

            <form @submit.prevent="sendWhatsAppLead()" class="space-y-4">
                {{-- ABA 1: DADOS DA BARBEARIA (100% w-full) --}}
                <div x-show="leadTab === 'dados'" class="space-y-4">
                    <div class="space-y-1.5 w-full">
                        <label class="text-xs font-bold text-slate-300 block">Seu Nome Completo *</label>
                        <input
                            type="text"
                            x-model="leadName"
                            required
                            placeholder="Ex: Marcos Oliveira"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-xs text-white focus:border-amber-400 focus:outline-none"
                        />
                    </div>

                    <div class="space-y-1.5 w-full">
                        <label class="text-xs font-bold text-slate-300 block">Nome da Barbearia *</label>
                        <input
                            type="text"
                            x-model="leadShop"
                            required
                            placeholder="Ex: Barbearia Dom Marcos"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-xs text-white focus:border-amber-400 focus:outline-none"
                        />
                    </div>

                    <div class="space-y-1.5 w-full">
                        <label class="text-xs font-bold text-slate-300 block">Seu WhatsApp com DDD *</label>
                        <input
                            type="tel"
                            x-model="leadPhone"
                            required
                            placeholder="(85) 99999-9999"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-xs text-white focus:border-amber-400 focus:outline-none"
                        />
                    </div>

                    <div class="pt-2">
                        <button
                            type="button"
                            @click="leadTab = 'perfil'"
                            class="w-full rounded-2xl bg-amber-500 hover:bg-amber-400 py-3.5 text-xs font-black text-slate-950 transition cursor-pointer"
                        >
                            Próximo Passo: Equipe & Plano &rarr;
                        </button>
                    </div>
                </div>

                {{-- ABA 2: EQUIPE & PLANO (100% w-full) --}}
                <div x-show="leadTab === 'perfil'" x-cloak class="space-y-4">
                    <div class="space-y-1.5 w-full">
                        <label class="text-xs font-bold text-slate-300 block">Cidade / Estado</label>
                        <input
                            type="text"
                            x-model="leadCity"
                            placeholder="Ex: Fortaleza - CE"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-xs text-white focus:border-amber-400 focus:outline-none"
                        />
                    </div>

                    <div class="space-y-1.5 w-full">
                        <label class="text-xs font-bold text-slate-300 block">Quantos barbeiros atendem na unidade?</label>
                        <select
                            x-model="leadBarbers"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-xs text-white focus:border-amber-400 focus:outline-none"
                        >
                            <option value="1 a 2 cadeiras (Plano Studio)">1 a 2 cadeiras (Plano Studio - R$ 59,90)</option>
                            <option value="3 a 10 cadeiras (Plano PRO)">3 a 10 cadeiras (Plano PRO - R$ 97,00)</option>
                            <option value="Rede / Multi-Unidades (Plano Elite)">Mais de 10 cadeiras ou Multi-Unidades (Plano Elite - R$ 167,00)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5 w-full">
                        <label class="text-xs font-bold text-slate-300 block">Principal objetivo com o sistema</label>
                        <select
                            x-model="leadChallenge"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-xs text-white focus:border-amber-400 focus:outline-none"
                        >
                            <option value="Automatizar agendamentos e controlar comissões">Automatizar agendamentos e controlar comissões</option>
                            <option value="Ter site próprio e fidelizar clientes">Ter link próprio de agendamento e cartão fidelidade</option>
                            <option value="Controlar estoque de bebidas, produtos e caixa">Controlar estoque de bebidas, produtos e caixa</option>
                            <option value="Dar acesso restrito individual para cada barbeiro">Dar acesso restrito individual para cada barbeiro</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button
                            type="button"
                            @click="leadTab = 'dados'"
                            class="w-1/3 rounded-2xl border border-slate-700 bg-slate-800 hover:bg-slate-700 py-3.5 text-xs font-bold text-slate-200 transition cursor-pointer"
                        >
                            &larr; Voltar
                        </button>
                        <button
                            type="submit"
                            class="w-2/3 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-400 hover:brightness-110 py-3.5 text-xs font-black text-slate-950 transition shadow-lg shadow-emerald-500/20 cursor-pointer"
                        >
                            💬 Solicitar Liberação no WhatsApp ➔
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
