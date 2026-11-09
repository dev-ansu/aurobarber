<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- 1. HEADER DO SAAS MASTER --}}
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid #334155; border-radius: 20px; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <div>
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #fbbf24;">Plataforma SaaS Central</span>
                <h2 style="font-size: 22px; font-weight: 900; color: #ffffff; margin: 4px 0 0 0;">Gestão Global & Licenciamento de Barbearias</h2>
                <p style="font-size: 13px; color: #94a3b8; margin: 4px 0 0 0;">Monitore o status das licenças de uso, controle carências, suspensões e renovações mensais.</p>
            </div>
            <a href="{{ \App\Filament\Resources\BarbershopResource::getUrl('create', panel: 'saas') }}"
               style="background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 13px; padding: 12px 22px; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(217, 119, 6, 0.4);">
                <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                + Cadastrar Nova Barbearia
            </a>
        </div>

        {{-- 2. CARDS DE KPIS GLOBAIS DE LICENÇA --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            
            {{-- Barbearias Ativas --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">Licenças Ativas</span>
                <div style="font-size: 28px; font-weight: 900; color: #10b981; margin: 8px 0 4px 0;">{{ $activeBarbershops }}</div>
                <span style="font-size: 12px; color: #64748b;">em dia / regulares</span>
            </div>

            {{-- Em Carência (Tolerância 5 dias) --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #f59e0b; letter-spacing: 0.05em;">Em Carência (5d)</span>
                <div style="font-size: 28px; font-weight: 900; color: #f59e0b; margin: 8px 0 4px 0;">{{ $graceBarbershops }}</div>
                <span style="font-size: 12px; color: #64748b;">vencidas com tolerância</span>
            </div>

            {{-- Suspensas por Inadimplência --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #ef4444; letter-spacing: 0.05em;">Suspensas</span>
                <div style="font-size: 28px; font-weight: 900; color: #ef4444; margin: 8px 0 4px 0;">{{ $suspendedBarbershops }}</div>
                <span style="font-size: 12px; color: #64748b;">acesso bloqueado</span>
            </div>

            {{-- Total Cadastradas --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #38bdf8; letter-spacing: 0.05em;">Total de Barbearias</span>
                <div style="font-size: 28px; font-weight: 900; color: #38bdf8; margin: 8px 0 4px 0;">{{ $totalBarbershops }}</div>
                <span style="font-size: 12px; color: #64748b;">clientes na plataforma</span>
            </div>

            {{-- Total de Cortes Realizados --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #a78bfa; letter-spacing: 0.05em;">Total Atendimentos</span>
                <div style="font-size: 28px; font-weight: 900; color: #a78bfa; margin: 8px 0 4px 0;">{{ $totalAppointments }}</div>
                <span style="font-size: 12px; color: #64748b;">agendamentos no ecossistema</span>
            </div>

        </div>

        {{-- 3. TABELA DE BARBEARIAS & LICENCIAMENTO --}}
        <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 20px; overflow: hidden;">
            <div style="padding: 18px 24px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #ffffff;">Status de Licenças das Barbearias</h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Renove prazos ou suspenda o acesso com 1 clique.</p>
                </div>
                <a href="{{ \App\Filament\Resources\BarbershopResource::getUrl('index', panel: 'saas') }}"
                   style="font-size: 12px; font-weight: 800; color: #fbbf24; text-decoration: none;">Gerenciar Todas as Barbearias &rarr;</a>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #cbd5e1;">
                    <thead>
                        <tr style="border-bottom: 1px solid #1e293b; background: rgba(15, 23, 42, 0.6); color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 14px 20px; white-space: nowrap;">Barbearia</th>
                            <th style="padding: 14px 20px; white-space: nowrap;">Proprietário</th>
                            <th style="padding: 14px 20px; white-space: nowrap;">Status da Licença</th>
                            <th style="padding: 14px 20px; white-space: nowrap;">Vencimento</th>
                            <th style="padding: 14px 20px; white-space: nowrap;">Mensalidade</th>
                            <th style="padding: 14px 20px; text-align: right; white-space: nowrap;">Ações de Controle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBarbershops as $shop)
                            <tr style="border-bottom: 1px solid #1e293b;">
                                <td style="padding: 16px 20px; font-weight: 800; color: #ffffff; white-space: nowrap;">
                                    {{ $shop['name'] }}
                                    <div style="font-size: 11px; font-weight: 400; color: #94a3b8;">{{ $shop['slug'] }} • {{ $shop['city'] }}</div>
                                </td>
                                <td style="padding: 16px 20px; white-space: nowrap;">
                                    <div style="font-weight: 700; color: #f1f5f9;">{{ $shop['owner_name'] }}</div>
                                    <div style="font-size: 11px; color: #64748b;">{{ $shop['owner_email'] }}</div>
                                </td>
                                <td style="padding: 16px 20px; white-space: nowrap;">
                                    @if($shop['is_suspended'])
                                        <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 9999px; border: 1px solid rgba(239, 68, 68, 0.3); display: inline-block; white-space: nowrap;">
                                            SUSPENSA (BLOQUEADA)
                                        </span>
                                    @elseif($shop['is_in_grace'])
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 9999px; border: 1px solid rgba(245, 158, 11, 0.3); width: fit-content; white-space: nowrap;">
                                                EM CARÊNCIA
                                            </span>
                                            <span style="font-size: 10px; color: #fbbf24; font-weight: 700; white-space: nowrap;">{{ $shop['grace_days_remaining'] }}d de tolerância</span>
                                        </div>
                                    @else
                                        <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 9999px; border: 1px solid rgba(16, 185, 129, 0.3); display: inline-block; white-space: nowrap;">
                                            ATIVA (REGULAR)
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 16px 20px; white-space: nowrap;">
                                    <div style="font-weight: 700; color: #f1f5f9; font-family: monospace;">{{ $shop['expires_at_formatted'] }}</div>
                                    @if($shop['days_remaining'] < 0)
                                        <div style="font-size: 11px; color: #ef4444; font-weight: 700;">Vencida há {{ abs($shop['days_remaining']) }} dias</div>
                                    @elseif($shop['days_remaining'] === 0)
                                        <div style="font-size: 11px; color: #f59e0b; font-weight: 700;">Vence hoje!</div>
                                    @else
                                        <div style="font-size: 11px; color: #64748b;">Restam {{ $shop['days_remaining'] }} dias</div>
                                    @endif
                                </td>
                                <td style="padding: 16px 20px; font-weight: 700; color: #38bdf8; white-space: nowrap;">
                                    R$ {{ $shop['monthly_price'] }}
                                </td>
                                <td style="padding: 16px 20px; text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: nowrap;">
                                        {{-- Botão Abrir Painel --}}
                                        <a href="{{ $shop['admin_url'] }}" target="_blank"
                                           style="white-space: nowrap; flex-shrink: 0; background: #1e293b; color: #f1f5f9; padding: 7px 13px; border-radius: 8px; text-decoration: none; font-size: 11px; font-weight: 800; border: 1px solid #334155; display: inline-flex; align-items: center; gap: 4px;">
                                            <span>Painel</span> <span>↗</span>
                                        </a>

                                        {{-- Botão Definir Vencimento --}}
                                        <button wire:click="openDueDateModal({{ $shop['id'] }})"
                                                type="button"
                                                style="white-space: nowrap; flex-shrink: 0; background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 7px 11px; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                            📅 Data
                                        </button>

                                        {{-- Botão Renovar +30d --}}
                                        <button wire:click="renew30({{ $shop['id'] }})"
                                                type="button"
                                                wire:confirm="Deseja renovar a licença de '{{ $shop['name'] }}' por mais 30 dias?"
                                                style="white-space: nowrap; flex-shrink: 0; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); padding: 7px 11px; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                            +30 Dias
                                        </button>

                                        {{-- Botão Suspender / Reativar --}}
                                        @if($shop['is_suspended'])
                                            <button wire:click="toggleStatus({{ $shop['id'] }})"
                                                    type="button"
                                                    wire:confirm="Deseja REATIVAR o acesso de '{{ $shop['name'] }}'?"
                                                    style="white-space: nowrap; flex-shrink: 0; background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981; padding: 7px 11px; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 800;">
                                                Reativar
                                            </button>
                                        @else
                                            <button wire:click="toggleStatus({{ $shop['id'] }})"
                                                    type="button"
                                                    wire:confirm="Atenção: Deseja SUSPENDER '{{ $shop['name'] }}'? O acesso dos lojistas será bloqueado."
                                                    style="white-space: nowrap; flex-shrink: 0; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4); padding: 7px 11px; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 800;">
                                                Suspender
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 32px; text-align: center; color: #64748b;">
                                    Nenhuma barbearia cadastrada no momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MODAL DE AJUSTE DIRETO DE VENCIMENTO --}}
        @if($editingBarbershopId)
            <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 99999; padding: 20px;">
                <div style="background: #0f172a; border: 1px solid #334155; border-radius: 20px; max-width: 480px; width: 100%; padding: 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8); display: flex; flex-direction: column; gap: 20px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #fbbf24; letter-spacing: 0.05em;">Acordo de Pagamento</span>
                        <h3 style="margin: 4px 0 0; font-size: 18px; font-weight: 900; color: #ffffff;">Alterar Vencimento: {{ $editingBarbershopName }}</h3>
                        <p style="margin: 6px 0 0; font-size: 13px; color: #94a3b8; line-height: 1.5;">Defina a data exata combinada com o cliente. O sistema regularizará o acesso e a carência de 5 dias passará a valer após esta data.</p>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="font-size: 13px; font-weight: 700; color: #e2e8f0;">Nova Data de Vencimento:</label>
                        <input type="date" wire:model="newDueDate" 
                               style="background: #1e293b; color: #f8fafc; border: 1px solid #475569; border-radius: 10px; padding: 12px 16px; font-size: 14px; outline: none; width: 100%;" />
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                        <button type="button" wire:click="closeDueDateModal" 
                                style="background: transparent; color: #94a3b8; border: 1px solid #475569; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;">
                            Cancelar
                        </button>
                        <button type="button" wire:click="saveDueDate" 
                                style="background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; border: none; padding: 10px 22px; border-radius: 10px; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.4);">
                            Salvar e Liberar Acesso
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
