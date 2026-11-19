@php
    $tenant = filament()->getTenant();
@endphp

@if ($tenant)
    <div
        class="mr-3 flex flex-wrap items-center gap-2"
        x-data="{
            copied: false,
            pwaModalOpen: false,
            canPromptInstall: !!window.deferredPwaPrompt,
            init() {
                window.addEventListener('pwa-ready', () => {
                    this.canPromptInstall = true;
                });
            },
            async triggerInstall() {
                if (window.deferredPwaPrompt) {
                    window.deferredPwaPrompt.prompt();
                    const { outcome } = await window.deferredPwaPrompt.userChoice;
                    if (outcome === 'accepted') {
                        window.deferredPwaPrompt = null;
                        this.canPromptInstall = false;
                        this.pwaModalOpen = false;
                    }
                } else {
                    this.pwaModalOpen = true;
                }
            }
        }"
    >
        <a
            href="{{ url($tenant->slug) }}"
            target="_blank"
            title="Abrir página pública de agendamento em nova aba"
            class="inline-flex items-center gap-1.5 rounded-lg border border-primary-500/30 bg-primary-50 px-2.5 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-500/15 dark:text-primary-400 dark:hover:bg-primary-500/25"
        >
            <span>💈 barber.aurosoft.com.br/{{ $tenant->slug }}</span>
            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-3.5 w-3.5 shrink-0" />
        </a>

        <button
            type="button"
            @click="navigator.clipboard.writeText('{{ url($tenant->slug) }}'); copied = true; setTimeout(() => copied = false, 2500)"
            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10"
            :title="copied ? 'Link copiado para a área de transferência!' : 'Copiar link da página para WhatsApp'"
        >
            <span x-show="!copied">📋 Copiar Link</span>
            <span x-show="copied" class="font-bold text-success-600 dark:text-success-400" x-cloak>✓ Link Copiado!</span>
        </button>

        <button
            type="button"
            @click="triggerInstall()"
            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-success-500/30 bg-success-50 px-2.5 py-1.5 text-xs font-semibold text-success-700 transition hover:bg-success-100 dark:bg-success-500/15 dark:text-success-400 dark:hover:bg-success-500/25"
            title="Instalar atalho do aplicativo no celular ou computador"
        >
            <span>📲 Instalar App</span>
        </button>

        {{-- MODAL DE INSTALAÇÃO DO APP (PWA iOS / ANDROID) --}}
        <template x-teleport="body">
            <div
                x-show="pwaModalOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm"
                @click.self="pwaModalOpen = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 text-gray-950 shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                    <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-3 dark:border-white/10">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">📲</span>
                            <div>
                                <h3 class="text-base font-bold text-gray-950 dark:text-white">Instalar Aplicativo no Celular</h3>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Acesso direto na tela inicial como App nativo</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="pwaModalOpen = false"
                            class="text-2xl leading-none text-gray-400 transition hover:text-gray-700 dark:hover:text-gray-200"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 dark:border-white/10 dark:bg-white/5">
                            <div class="mb-1.5 font-bold text-primary-600 dark:text-primary-400">🤖 No Android (Chrome / Samsung)</div>
                            <ol class="list-decimal space-y-1 pl-4 text-xs leading-relaxed">
                                <li>Toque nos <strong>3 pontinhos (⋮)</strong> no canto superior do navegador.</li>
                                <li>Selecione <strong>"Instalar aplicativo"</strong> ou <strong>"Adicionar à tela inicial"</strong>.</li>
                            </ol>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 dark:border-white/10 dark:bg-white/5">
                            <div class="mb-1.5 font-bold text-info-600 dark:text-info-400">🍎 No iPhone / iOS (Safari)</div>
                            <ol class="list-decimal space-y-1 pl-4 text-xs leading-relaxed">
                                <li>Toque no ícone de <strong>Compartilhar (quadrado com seta ↑)</strong> na barra inferior.</li>
                                <li>Role para baixo e toque em <strong>"Adicionar à Tela de Início"</strong>.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <x-filament::button color="primary" @click="pwaModalOpen = false">
                            Entendi, Fechar
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
