<x-layouts.app
    :title="$page->title . ' • AuroBarber PRO'"
    bodyClass="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950 min-h-screen flex flex-col justify-between"
>
    <x-slot:head>
        @if(!empty($page->meta_description))
            <meta name="description" content="{{ $page->meta_description }}">
        @else
            <meta name="description" content="Transforme sua barbearia com agendamento online em 30 segundos, comanda de bar e produtos, cálculo automático de comissões, visão restrita do barbeiro e cartão fidelidade.">
        @endif

        @if(!empty($page->meta_keywords))
            <meta name="keywords" content="{{ $page->meta_keywords }}">
        @else
            <meta name="keywords" content="sistema barbearia, agendamento online barbearia, controle de comissao barbeiro, software gestao barbearia, comanda barbearia, cartao fidelidade barbearia, aurobarber">
        @endif

        <meta name="author" content="Aurosoft Tecnologia">
        <link rel="canonical" href="{{ url('/' . ltrim($page->slug === '/' ? '' : $page->slug, '/')) }}">

        <!-- Open Graph / WhatsApp -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ $page->title }} • AuroBarber PRO">
        <meta property="og:description" content="{{ $page->meta_description ?? 'Página exclusiva de agendamento em 30s, comissões com recibo jurídico, comanda de bar/estoque e cartão fidelidade automático.' }}">
        @if(!empty($page->og_image))
            <meta property="og:image" content="{{ asset('storage/' . $page->og_image) }}">
        @else
            <meta property="og:image" content="{{ asset('pwa-icon.svg') }}">
        @endif
        <meta property="og:locale" content="pt_BR">
        <meta property="og:site_name" content="AuroBarber">

        <!-- Tailwind CDN & Alpine -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

        <style>
            [x-cloak] { display: none !important; }
            .hero-glow {
                background: radial-gradient(circle at 50% 0%, rgba(245, 158, 11, 0.20) 0%, rgba(16, 185, 129, 0.06) 45%, transparent 72%);
            }
            .text-gradient-gold {
                background: linear-gradient(135deg, #fef08a 0%, #fbbf24 50%, #f59e0b 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .metric-box-stable {
                min-height: 68px;
                contain: layout;
            }
        </style>
    </x-slot:head>

    <div x-data="{
        leadModalOpen: false,
        tenantModalOpen: false,
        selectedPlanName: '',
        selectedPlanId: null
    }">
        {{-- Navbar Superior --}}
        <x-landing-navbar :activeBarbershops="$activeBarbershops" />

        <main id="main-content">
            @forelse($page->content ?? [] as $block)
                @php
                    $blockType = str_replace('_', '-', (string) ($block['type'] ?? ''));
                    $blockData = $block['data'] ?? [];
                @endphp
                @if(view()->exists("components.blocks.{$blockType}"))
                    <x-dynamic-component
                        :component="'blocks.' . $blockType"
                        :data="$blockData"
                        :plans="$activeSaasPlans"
                        :barbershops="$activeBarbershops"
                    />
                @endif
            @empty
                <section class="py-32 text-center">
                    <div class="max-w-2xl mx-auto px-4">
                        <h1 class="text-3xl font-black text-white">{{ $page->title }}</h1>
                        <p class="text-slate-400 mt-4">Esta página está em construção ou não possui seções configuradas no CMS.</p>
                    </div>
                </section>
            @endforelse
        </main>

        {{-- Footer --}}
        <x-landing-footer />

        {{-- Modais --}}
        <x-landing-lead-modal
            :activeSaasPlans="$activeSaasPlans"
            :commercialWhatsapp="$commercialWhatsapp"
        />

        <x-landing-barbershop-modal
            :activeBarbershops="$activeBarbershops"
        />
    </div>
</x-layouts.app>
