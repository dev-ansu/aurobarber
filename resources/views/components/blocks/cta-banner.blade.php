@props([
    'data' => [],
])

@php
    $title = $data['title'] ?? 'Pronto para profissionalizar a gestão da sua barbearia?';
    $subtitle = $data['subtitle'] ?? 'Junte-se às barbearias que abandonaram as planilhas e o caderno para ter agenda cheia e controle financeiro total.';
    $buttonText = $data['button_text'] ?? 'Começar Teste Grátis Agora';
    $secondaryButtonText = $data['secondary_button_text'] ?? 'Tirar Dúvidas no WhatsApp';
    $secondaryButtonUrl = $data['secondary_button_url'] ?? null;
@endphp

<section class="py-20 sm:py-28 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 relative overflow-hidden border-b border-slate-800/80">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom,_var(--tw-gradient-stops))] from-amber-500/15 via-transparent to-transparent pointer-events-none"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 space-y-7">
        <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
            {!! nl2br(e($title)) !!}
        </h2>
        @if(!empty($subtitle))
            <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
                {!! nl2br(e($subtitle)) !!}
            </p>
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
            <button
                type="button"
                @click="leadModalOpen = true"
                class="w-full sm:w-auto px-9 py-4 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:brightness-110 text-slate-950 font-black text-sm tracking-wide transition shadow-xl shadow-amber-500/25 active:scale-95 cursor-pointer"
            >
                <span>✂️</span>
                <span>{{ $buttonText }}</span>
            </button>

            @if(!empty($secondaryButtonUrl))
                <a
                    href="{{ $secondaryButtonUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="w-full sm:w-auto px-8 py-4 rounded-2xl border border-slate-700 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm tracking-wide transition shadow-lg flex items-center justify-center gap-2 active:scale-95"
                >
                    <span>💬</span>
                    <span>{{ $secondaryButtonText }}</span>
                </a>
            @else
                <button
                    type="button"
                    @click="leadModalOpen = true"
                    class="w-full sm:w-auto px-8 py-4 rounded-2xl border border-slate-700 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm tracking-wide transition shadow-lg flex items-center justify-center gap-2 active:scale-95 cursor-pointer"
                >
                    <span>💬</span>
                    <span>{{ $secondaryButtonText }}</span>
                </button>
            @endif
        </div>
    </div>
</section>
