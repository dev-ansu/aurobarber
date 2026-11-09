<x-filament-panels::page>
    <div style="width: 100%; display: flex; flex-direction: column; gap: 24px;">
        
        {{-- Banner de Alerta de Segurança --}}
        <div style="background: linear-gradient(135deg, rgba(217, 119, 6, 0.15), rgba(15, 23, 42, 0.95)); border: 1px solid rgba(217, 119, 6, 0.4); border-radius: 16px; padding: 20px; display: flex; gap: 16px; align-items: flex-start; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.4);">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(217, 119, 6, 0.2); border: 1px solid rgba(217, 119, 6, 0.5); display: flex; align-items: center; justify-content: center; color: #fbbf24; flex-shrink: 0;">
                <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #f8fafc;">Primeiro Acesso Detectado</h3>
                <p style="margin: 0; font-size: 13px; color: #cbd5e1; line-height: 1.5;">
                    Você está utilizando uma <strong>senha temporária</strong>. Por questões de segurança e privacidade dos seus dados, é obrigatório cadastrar uma nova senha definitiva e exclusiva para liberar o acesso ao painel.
                </p>
            </div>
        </div>

        {{-- Formulário Filament --}}
        <form wire:submit="save" style="display: flex; flex-direction: column; gap: 24px;">
            {{ $this->form }}

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px;">
                <button type="submit" 
                        style="background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 14px; padding: 12px 28px; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(217, 119, 6, 0.35); transition: all 0.2s ease;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salvar Nova Senha e Acessar Painel
                </button>
            </div>
        </form>

    </div>
</x-filament-panels::page>
