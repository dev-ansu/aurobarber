<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px; max-width: 1050px; margin: 0 auto; width: 100%;">

        {{-- 1. HEADER DO SUPORTE --}}
        <div style="background: linear-gradient(135deg, #0b1120 0%, #111827 50%, #064e3b 100%); border: 1px solid #1e293b; border-radius: 20px; padding: 26px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <div style="display: flex; flex-direction: column; gap: 6px; max-width: 720px;">
                <div style="display: inline-flex; align-items: center; gap: 8px; width: fit-content; padding: 4px 12px; border-radius: 9999px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; font-size: 11px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;">
                    <span style="width: 8px; height: 8px; border-radius: 9999px; background: #34d399; display: inline-block;"></span>
                    <span>Canal Oficial de Atendimento</span>
                </div>
                <h2 style="font-size: 24px; font-weight: 900; color: #ffffff; margin: 4px 0 0 0; letter-spacing: -0.02em;">Contato de Suporte ao Cliente</h2>
                <p style="font-size: 13px; color: #94a3b8; margin: 0; line-height: 1.5;">
                    Este WhatsApp será exibido para as barbearias quando estiverem com a licença em carência ou suspensas por faturas em aberto.
                </p>
            </div>

            <div style="display: flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 18px; background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25);">
                <x-filament::icon icon="heroicon-o-chat-bubble-left-right" style="width: 32px; height: 32px;" />
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; align-items: start;">

            {{-- 2. FORMULÁRIO DE EDIÇÃO --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 20px; padding: 28px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); display: flex; flex-direction: column; gap: 20px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #ffffff; margin: 0 0 4px 0;">Configurar Número do WhatsApp</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 0;">
                        Informe o número completo com DDI (55 para Brasil) e DDD sem espaços ou caracteres especiais.
                    </p>
                </div>

                <form wire:submit="save" style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #cbd5e1;">Número de Telefone / WhatsApp *</label>
                        
                        <div style="position: relative; display: flex; align-items: center;">
                            <span style="position: absolute; left: 14px; font-size: 16px; pointer-events: none; opacity: 0.8;">💬</span>
                            <input
                                type="tel"
                                wire:model="supportPhone"
                                placeholder="Ex: 5585999999999"
                                style="width: 100%; padding: 14px 14px 14px 44px; background: #0f172a; border: 1px solid #334155; border-radius: 12px; font-size: 14px; font-family: monospace; color: #ffffff; outline: none; transition: all 0.2s;"
                                onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.2)';"
                                onblur="this.style.borderColor='#334155'; this.style.boxShadow='none';"
                            />
                        </div>

                        @error('supportPhone')
                            <p style="font-size: 12px; color: #ef4444; font-weight: 700; margin: 2px 0 0 0;">{{ $message }}</p>
                        @enderror

                        <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0; line-height: 1.4;">
                            💡 Exemplos válidos: <span style="color: #34d399; font-family: monospace; font-weight: bold;">5585988887777</span> ou <span style="color: #34d399; font-family: monospace; font-weight: bold;">(85) 98888-7777</span>. O sistema salva automaticamente o padrão internacional limpo.
                        </p>
                    </div>

                    <div style="padding-top: 16px; border-top: 1px solid #1e293b; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <button
                            type="submit"
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #022c22; font-weight: 900; font-size: 13px; padding: 12px 24px; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4); transition: transform 0.1s;"
                            onmousedown="this.style.transform='scale(0.97)'"
                            onmouseup="this.style.transform='scale(1)'"
                        >
                            <x-filament::icon icon="heroicon-m-check" style="width: 16px; height: 16px;" />
                            <span>Salvar Contato Oficial</span>
                        </button>

                        @if(!empty($supportPhone))
                            @php
                                $cleanDigits = preg_replace('/\D/', '', $supportPhone);
                            @endphp
                            <a
                                href="https://wa.me/{{ $cleanDigits }}?text=Ol%C3%A1!%20Sou%20da%20equipe%20AuroBarber%20testando%20o%20canal%20de%20suporte."
                                target="_blank"
                                rel="noopener noreferrer"
                                style="background: #1e293b; border: 1px solid #334155; color: #e2e8f0; font-weight: 700; font-size: 13px; padding: 12px 20px; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"
                            >
                                <span>Abrir Teste</span>
                                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" style="width: 14px; height: 14px; color: #94a3b8;" />
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- 3. CARD DE PRÉ-VISUALIZAÇÃO (COMO A BARBEARIA ENXERGA) --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 20px; padding: 28px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between; gap: 20px;">
                <div>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #fbbf24; display: block; margin-bottom: 4px;">Simulador de Tela</span>
                    <h4 style="font-size: 16px; font-weight: 800; color: #ffffff; margin: 0 0 6px 0;">Visão do Cliente Suspenso</h4>
                    <p style="font-size: 12px; color: #94a3b8; margin: 0 0 16px 0; line-height: 1.4;">
                        Veja abaixo como o botão de socorro aparece na tela de bloqueio da barbearia:
                    </p>

                    <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 16px; padding: 18px; display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #ef4444; font-size: 13px; font-weight: 800;">
                            <span>🔒 Acesso Temporariamente Bloqueado</span>
                        </div>
                        <p style="font-size: 12px; color: #cbd5e1; margin: 0; line-height: 1.4;">
                            Sua assinatura está suspensa. Fale com a equipe pelo canal de atendimento:
                        </p>
                        
                        @if(!empty($supportPhone))
                            <div style="width: 100%; padding: 12px; border-radius: 12px; background: #10b981; color: #022c22; font-weight: 900; font-size: 13px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                                <span>💬 Falar no WhatsApp</span>
                            </div>
                        @else
                            <div style="width: 100%; padding: 12px; border-radius: 12px; background: #1e293b; color: #64748b; font-weight: 700; font-size: 12px; text-align: center; border: 1px dashed #334155;">
                                <span>(Nenhum contato cadastrado)</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div style="padding-top: 16px; border-top: 1px solid #1e293b; font-size: 11px; color: #64748b; display: flex; flex-direction: column; gap: 4px;">
                    <div>• Suporte 100% responsivo.</div>
                    <div>• Zero interferência no banco das barbearias.</div>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>