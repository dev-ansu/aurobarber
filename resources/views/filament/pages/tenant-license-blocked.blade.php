<x-filament-panels::page>
    @php
        $barbershop = $this->getBarbershop();
        $supportPhone = config('license.support_phone', env('SAAS_SUPPORT_PHONE', '(85) 99999-9999'));
        $cleanPhone = preg_replace('/\D/', '', $supportPhone);
        $barberName = $barbershop?->name ?? 'Barbearia';
        $waMessage = urlencode("Olá! Estou no painel do AuroBarber da barbearia '{$barberName}' e gostaria de regularizar a nossa licença de acesso.");
    @endphp

    <div style="width: 100%; max-width: 720px; margin: 40px auto; display: flex; flex-direction: column; gap: 24px;">
        
        {{-- Card Principal de Bloqueio --}}
        <div style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 24px; padding: 36px 32px; box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7); display: flex; flex-direction: column; align-items: center; text-align: center; gap: 20px;">
            
            {{-- Ícone de Bloqueio / Cadeado Dourado e Vermelho --}}
            <div style="width: 72px; height: 72px; border-radius: 20px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); display: flex; align-items: center; justify-content: center; color: #f87171; box-shadow: 0 8px 24px rgba(239, 68, 68, 0.25);">
                <svg style="width: 36px; height: 36px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <div>
                <span style="display: inline-block; padding: 4px 14px; border-radius: 9999px; background: rgba(239, 68, 68, 0.18); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                    Licença de Software Suspensa
                </span>
                <h2 style="margin: 0; font-size: 24px; font-weight: 900; color: #f8fafc; letter-spacing: -0.02em;">
                    {{ $barberName }}
                </h2>
                <p style="margin: 8px 0 0; font-size: 14px; color: #94a3b8; line-height: 1.6;">
                    O acesso ao painel de agendamentos, clientes e financeiro desta unidade foi suspenso temporariamente devido à pendência na renovação da licença.
                </p>
            </div>

            {{-- Detalhes da Licença --}}
            <div style="width: 100%; background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 18px 20px; display: flex; flex-direction: column; gap: 10px; text-align: left; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Status do Sistema:</span>
                    <span style="color: #f87171; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span>
                        Bloqueado por Inadimplência
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Vencimento Registrado:</span>
                    <span style="color: #f1f5f9; font-weight: 700; font-family: monospace;">
                        {{ $barbershop?->license_expires_at ? $barbershop->license_expires_at->format('d/m/Y') : 'Não definido' }}
                    </span>
                </div>

                @if(!empty($barbershop?->suspension_reason))
                    <div style="display: flex; flex-direction: column; gap: 4px; padding-top: 4px;">
                        <span style="color: #64748b; font-weight: 600;">Motivo Registrado:</span>
                        <span style="color: #e2e8f0; font-style: italic;">
                            {{ $barbershop->suspension_reason }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Botões de Ação --}}
            <div style="width: 100%; display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
                @if(!empty($cleanPhone))
                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ $waMessage }}" 
                       target="_blank" 
                       style="width: 100%; background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; font-weight: 800; font-size: 15px; padding: 14px 24px; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35); transition: all 0.2s ease;">
                        <svg style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                        Falar com o Financeiro no WhatsApp
                    </a>
                @endif

                <a href="{{ request()->url() }}" 
                   style="width: 100%; background: rgba(255, 255, 255, 0.08); color: #f1f5f9; font-weight: 700; font-size: 14px; padding: 12px 24px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.15); text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease;">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Verificar Regularização Agora
                </a>
            </div>

            {{-- Logout --}}
            <form method="POST" action="{{ filament()->getLogoutUrl() }}" style="margin-top: 4px;">
                @csrf
                <button type="submit" style="background: none; border: none; color: #64748b; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: underline;">
                    Sair da Conta (Logout)
                </button>
            </form>

        </div>

        {{-- Rodapé Informativo --}}
        <div style="text-align: center; font-size: 11px; color: #475569;">
            AuroBarber • Plataforma de Gestão Inteligente para Barbearias e Salões
        </div>

    </div>
</x-filament-panels::page>
