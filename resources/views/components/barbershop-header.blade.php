@props([
    'barbershop',
])

@php
    $resolvedLogoUrl = $barbershop->logo_url
        ? (str_starts_with($barbershop->logo_url, 'http') ? $barbershop->logo_url : asset('storage/' . $barbershop->logo_url))
        : null;
@endphp

<header class="bg-slate-900/90 border-b border-slate-800/80 backdrop-blur sticky top-0 z-40"
        x-data="{
            pwaModalOpen: false,
            async installPwa() {
                if (window.deferredPwaPrompt) {
                    window.deferredPwaPrompt.prompt();
                    const { outcome } = await window.deferredPwaPrompt.userChoice;
                    if (outcome === 'accepted') {
                        window.deferredPwaPrompt = null;
                        this.pwaModalOpen = false;
                    }
                } else {
                    this.pwaModalOpen = true;
                }
            }
        }">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between gap-2">
        <div class="flex items-center gap-3">
            @if ($resolvedLogoUrl)
                <img src="{{ $resolvedLogoUrl }}" alt="{{ $barbershop->name }}" class="h-11 w-11 rounded-2xl object-cover border border-slate-700" />
            @else
                <div class="h-11 w-11 rounded-2xl bg-brand text-slate-950 flex items-center justify-center font-black text-xl shadow-lg">
                    💈
                </div>
            @endif
            <div>
                <h1 class="text-base sm:text-lg font-black text-white leading-tight">{{ $barbershop->name }}</h1>
                @if ($barbershop->isBranch() && $barbershop->parent)
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[11px] font-semibold text-amber-400">Filial de {{ $barbershop->parent->name }}</span>
                        @if ($barbershop->city)
                            <span class="text-xs text-slate-400">&bull; 📍 {{ $barbershop->city }}</span>
                        @endif
                    </div>
                @elseif ($barbershop->city)
                    <span class="text-xs text-slate-400">📍 {{ $barbershop->city }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    @click="installPwa()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-amber-400 font-bold text-xs px-3 py-2 transition">
                <span>📲</span>
                <span class="hidden sm:inline">Instalar App</span>
                <span class="sm:hidden">App</span>
            </button>

            @if ($barbershop->formatted_whatsapp)
                <a href="https://api.whatsapp.com/send?phone={{ $barbershop->formatted_whatsapp }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 transition shadow-lg shadow-emerald-900/20">
                    <span>WhatsApp</span>
                </a>
            @endif
        </div>
    </div>

    {{-- MODAL DE INSTALAÇÃO DO APP DA BARBEARIA --}}
    <div x-show="pwaModalOpen"
         x-cloak
         @click.self="pwaModalOpen = false"
         class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📲</span>
                    <div>
                        <h3 class="text-base font-black text-white">Instalar App • {{ $barbershop->name }}</h3>
                        <p class="text-xs text-slate-400">Agende em 1 toque direto da tela inicial do seu celular</p>
                    </div>
                </div>
                <button type="button" @click="pwaModalOpen = false" class="text-slate-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            <div class="space-y-3 text-xs text-slate-300">
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                    <div class="font-extrabold text-amber-400 mb-1.5">🤖 No Android (Chrome / Samsung)</div>
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Toque nos <strong>3 pontinhos (⋮)</strong> no canto superior do navegador.</li>
                        <li>Toque em <strong>"Instalar aplicativo"</strong> ou <strong>"Adicionar à tela inicial"</strong>.</li>
                    </ol>
                </div>

                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                    <div class="font-extrabold text-sky-400 mb-1.5">🍎 No iPhone / iOS (Safari)</div>
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Toque no ícone de <strong>Compartilhar (quadrado com seta ↑)</strong> na barra inferior.</li>
                        <li>Role para baixo e toque em <strong>"Adicionar à Tela de Início"</strong>.</li>
                    </ol>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" @click="pwaModalOpen = false" class="bg-brand text-slate-950 font-black text-xs px-5 py-2.5 rounded-xl">
                    Entendi, Fechar
                </button>
            </div>
        </div>
    </div>
</header>
