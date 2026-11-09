@php
    $tenant = filament()->getTenant();
@endphp

@if ($tenant)
    <div
        style="display: flex; align-items: center; gap: 8px; margin-right: 12px; flex-wrap: wrap;"
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
            style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 10px; background-color: rgba(217, 119, 6, 0.15); color: #f59e0b; border: 1px solid rgba(217, 119, 6, 0.35); font-size: 11px; font-weight: 700; text-decoration: none; transition: all 0.2s;"
            onmouseover="this.style.backgroundColor='rgba(217, 119, 6, 0.25)'"
            onmouseout="this.style.backgroundColor='rgba(217, 119, 6, 0.15)'"
        >
            <span>💈 barber.aurosoft.com.br/{{ $tenant->slug }}</span>
            <svg style="width: 13px; height: 13px; min-width: 13px; min-height: 13px; display: inline-block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>

        <button 
            type="button"
            @click="navigator.clipboard.writeText('{{ url($tenant->slug) }}'); copied = true; setTimeout(() => copied = false, 2500)"
            style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border-radius: 10px; background-color: #1e293b; color: #cbd5e1; border: 1px solid #334155; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s;"
            onmouseover="this.style.backgroundColor='#334155'; this.style.color='#ffffff'"
            onmouseout="this.style.backgroundColor='#1e293b'; this.style.color='#cbd5e1'"
            :title="copied ? 'Link copiado para a área de transferência!' : 'Copiar link da página para WhatsApp'"
        >
            <span x-show="!copied">📋 Copiar Link</span>
            <span x-show="copied" style="color: #34d399; font-weight: 700;" x-cloak>✓ Link Copiado!</span>
        </button>

        <button
            type="button"
            @click="triggerInstall()"
            style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border-radius: 10px; background-color: rgba(16, 185, 129, 0.14); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.2s;"
            onmouseover="this.style.backgroundColor='rgba(16, 185, 129, 0.25)'"
            onmouseout="this.style.backgroundColor='rgba(16, 185, 129, 0.14)'"
            title="Instalar atalho do aplicativo no celular ou computador"
        >
            <span>📲 Instalar App</span>
        </button>

        {{-- MODAL DE INSTALAÇÃO DO APP (PWA iOS / ANDROID) --}}
        <template x-teleport="body">
            <div
                x-show="pwaModalOpen"
                x-cloak
                style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(6px); z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 16px;"
                @click.self="pwaModalOpen = false"
            >
                <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 22px; max-width: 440px; width: 100%; padding: 24px; color: #f8fafc; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #1e293b;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 24px;">📲</span>
                            <div>
                                <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #ffffff;">Instalar Aplicativo no Celular</h3>
                                <span style="font-size: 11px; color: #94a3b8;">Acesso direto na tela inicial como App nativo</span>
                            </div>
                        </div>
                        <button type="button" @click="pwaModalOpen = false" style="background: transparent; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13px; color: #cbd5e1;">
                        <div style="background: #020617; border: 1px solid #1e293b; border-radius: 14px; padding: 14px;">
                            <div style="font-weight: 800; color: #fbbf24; margin-bottom: 6px;">🤖 No Android (Chrome / Samsung)</div>
                            <ol style="margin: 0; padding-left: 18px; line-height: 1.6; color: #cbd5e1;">
                                <li>Toque nos <strong>3 pontinhos (⋮)</strong> no canto superior do navegador.</li>
                                <li>Selecione <strong>"Instalar aplicativo"</strong> ou <strong>"Adicionar à tela inicial"</strong>.</li>
                            </ol>
                        </div>

                        <div style="background: #020617; border: 1px solid #1e293b; border-radius: 14px; padding: 14px;">
                            <div style="font-weight: 800; color: #38bdf8; margin-bottom: 6px;">🍎 No iPhone / iOS (Safari)</div>
                            <ol style="margin: 0; padding-left: 18px; line-height: 1.6; color: #cbd5e1;">
                                <li>Toque no ícone de <strong>Compartilhar (quadrado com seta ↑)</strong> na barra inferior.</li>
                                <li>Role para baixo e toque em <strong>"Adicionar à Tela de Início"</strong>.</li>
                            </ol>
                        </div>
                    </div>

                    <div style="margin-top: 18px; display: flex; justify-content: flex-end;">
                        <button
                            type="button"
                            @click="pwaModalOpen = false"
                            style="background: #d97706; color: #020617; border: none; border-radius: 12px; padding: 10px 20px; font-size: 12px; font-weight: 900; cursor: pointer;"
                        >
                            Entendi, Fechar
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
