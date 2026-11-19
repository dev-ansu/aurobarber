@props([
    'data' => [],
])

@php
    $badge = $data['section_badge'] ?? 'O Sistema Operacional Completo para Barbearias de Alto Padrão';
    $title = $data['title'] ?? 'Transforme sua barbearia em uma máquina de agenda cheia, comanda lucrativa e gestão no piloto automático.';
    $subtitle = $data['subtitle'] ?? 'Link exclusivo White-Label para seu cliente agendar em 30 segundos sem baixar nada, comanda com estoque de bar e cosméticos, cartão fidelidade automático e visão restrita individual no celular de cada barbeiro.';
    $primaryCtaText = $data['primary_cta_text'] ?? 'Quero Implantar na Minha Barbearia';
    $secondaryCtaText = $data['secondary_cta_text'] ?? 'Ver Demonstração Interativa';
    $secondaryCtaUrl = $data['secondary_cta_url'] ?? '#recursos';
    $previewImage = $data['preview_image'] ?? null;
    $metrics = $data['highlight_metrics'] ?? [
        ['value' => '30 seg', 'label' => 'Agendamento Online s/ Senha'],
        ['value' => '0% Taxa', 'label' => '100% do Pix e Caixa é Seu'],
        ['value' => 'App PWA', 'label' => 'Instalável no Android & iPhone'],
        ['value' => 'Recibo PJ', 'label' => 'Comissão com Validade Jurídica'],
    ];
@endphp

<section class="relative hero-glow pt-14 pb-20 sm:pt-24 sm:pb-32 overflow-hidden border-b border-slate-800/80">
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[450px] bg-gradient-to-tr from-amber-500/20 to-emerald-500/10 blur-[130px] rounded-full pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-4xl mx-auto space-y-7">
            {{-- Badge de Inovação --}}
            @if(!empty($badge))
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold tracking-wide shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>{{ $badge }}</span>
                </div>
            @endif

            {{-- Headline Principal --}}
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.12]">
                {!! nl2br(e($title)) !!}
            </h1>

            {{-- Subheadline --}}
            @if(!empty($subtitle))
                <p class="text-base sm:text-lg lg:text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed font-normal">
                    {!! nl2br(e($subtitle)) !!}
                </p>
            @endif

            {{-- CTAs Principais --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                <button
                    type="button"
                    @click="leadModalOpen = true"
                    class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-sm tracking-wide transition shadow-xl shadow-amber-500/25 flex items-center justify-center gap-2.5 active:scale-95 group cursor-pointer"
                >
                    <span>✂️</span>
                    <span>{{ $primaryCtaText }}</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </button>

                @if(!empty($secondaryCtaUrl) && str_starts_with($secondaryCtaUrl, 'http'))
                    <a
                        href="{{ $secondaryCtaUrl }}"
                        target="_blank"
                        rel="noopener"
                        class="w-full sm:w-auto px-8 py-4 rounded-2xl border border-slate-700 bg-slate-900/90 hover:bg-slate-800/90 text-white font-bold text-sm tracking-wide transition shadow-lg flex items-center justify-center gap-2.5 active:scale-95"
                    >
                        <span>💬</span>
                        <span>{{ $secondaryCtaText }}</span>
                    </a>
                @else
                    <a
                        href="{{ $secondaryCtaUrl ?: '#recursos' }}"
                        class="w-full sm:w-auto px-8 py-4 rounded-2xl border border-slate-700 bg-slate-900/90 hover:bg-slate-800/90 text-white font-bold text-sm tracking-wide transition shadow-lg flex items-center justify-center gap-2.5 active:scale-95"
                    >
                        <span>🖥️</span>
                        <span>{{ $secondaryCtaText }}</span>
                    </a>
                @endif
            </div>

            {{-- Selos de Confiança / Badges de Autoridade --}}
            @if(!empty($metrics))
                <div class="pt-8 border-t border-slate-800/60 grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto text-left">
                    @foreach($metrics as $metric)
                        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 metric-box-stable">
                            <div class="text-base sm:text-lg font-black text-amber-400 font-mono">{{ $metric['value'] ?? '' }}</div>
                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">{{ $metric['label'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($previewImage))
                <div class="mt-12 rounded-2xl border border-slate-800 bg-slate-900/40 p-2 shadow-2xl">
                    <img src="{{ asset('storage/' . $previewImage) }}" alt="Preview AuroBarber" class="rounded-xl w-full max-h-[500px] object-cover">
                </div>
            @endif
        </div>
    </div>
</section>
