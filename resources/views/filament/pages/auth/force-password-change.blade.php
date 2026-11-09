<x-filament-panels::page>
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
        
        {{-- Banner de Alerta de Segurança e Boas-Vindas --}}
        <div style="background: linear-gradient(135deg, rgba(217, 119, 6, 0.18), rgba(15, 23, 42, 0.95)); border: 1px solid rgba(217, 119, 6, 0.45); border-radius: 18px; padding: 24px 28px; display: flex; gap: 20px; align-items: center; box-shadow: 0 12px 30px -8px rgba(0,0,0,0.5);">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(217, 119, 6, 0.25); border: 1px solid rgba(217, 119, 6, 0.6); display: flex; align-items: center; justify-content: center; color: #fbbf24; flex-shrink: 0; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);">
                <svg style="width: 28px; height: 28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px; flex: 1;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #f8fafc; letter-spacing: -0.01em;">Primeiro Acesso ao AuroBarber Detectado</h3>
                <p style="margin: 0; font-size: 14px; color: #cbd5e1; line-height: 1.6;">
                    Você está autenticado utilizando uma <strong>senha temporária provisória</strong>. Para garantir a segurança total da sua barbearia e a confidencialidade dos dados dos seus clientes e finanças, defina agora sua senha definitiva de acesso.
                </p>
            </div>
        </div>

        {{-- Formulário Filament Completo em Abas e 100% de Largura --}}
        <form wire:submit="save" style="width: 100%; display: flex; flex-direction: column; gap: 24px;">
            <div style="width: 100%;">
                {{ $this->form }}
            </div>

            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 16px; margin-top: 8px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                <button type="submit" 
                        style="background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 15px; padding: 14px 32px; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 6px 20px rgba(217, 119, 6, 0.4); transition: all 0.2s ease;">
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salvar Nova Senha e Acessar Painel
                </button>
            </div>
        </form>

    </div>
</x-filament-panels::page>
