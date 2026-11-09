<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- 1. HEADER DO SAAS MASTER --}}
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid #334155; border-radius: 20px; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <div>
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #fbbf24;">Plataforma SaaS Central</span>
                <h2 style="font-size: 22px; font-weight: 900; color: #ffffff; margin: 4px 0 0 0;">Gestão Global de Barbearias</h2>
                <p style="font-size: 13px; color: #94a3b8; margin: 4px 0 0 0;">Acompanhe o crescimento da base de clientes, provisionamentos e métricas da plataforma.</p>
            </div>
            <a href="{{ \App\Filament\Resources\BarbershopResource::getUrl('create', panel: 'saas') }}"
               style="background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 13px; padding: 12px 22px; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(217, 119, 6, 0.4);">
                <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                + Cadastrar Nova Barbearia
            </a>
        </div>

        {{-- 2. CARDS DE KPIS GLOBAIS --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            
            {{-- Barbearias Ativas --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">Barbearias Ativas</span>
                <div style="font-size: 28px; font-weight: 900; color: #10b981; margin: 8px 0 4px 0;">{{ $activeBarbershops }}</div>
                <span style="font-size: 12px; color: #64748b;">de {{ $totalBarbershops }} cadastradas</span>
            </div>

            {{-- Barbeiros Parceiros --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">Barbeiros na Rede</span>
                <div style="font-size: 28px; font-weight: 900; color: #38bdf8; margin: 8px 0 4px 0;">{{ $totalBarbers }}</div>
                <span style="font-size: 12px; color: #64748b;">profissionais ativos</span>
            </div>

            {{-- Total de Cortes / Agendamentos --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">Cortes Realizados</span>
                <div style="font-size: 28px; font-weight: 900; color: #fbbf24; margin: 8px 0 4px 0;">{{ $totalAppointments }}</div>
                <span style="font-size: 12px; color: #64748b;">agendamentos na plataforma</span>
            </div>

            {{-- Volume Bruto Transacionado --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">Volume Transacionado</span>
                <div style="font-size: 24px; font-weight: 900; color: #a78bfa; margin: 8px 0 4px 0;">R$ {{ number_format($totalGrossRevenue, 2, ',', '.') }}</div>
                <span style="font-size: 12px; color: #64748b;">em atendimentos concluídos</span>
            </div>

        </div>

        {{-- 3. TABELA DE BARBEARIAS RECENTES CADASTRADAS --}}
        <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 20px; overflow: hidden;">
            <div style="padding: 18px 24px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #ffffff;">Barbearias Clientes Cadastradas</h3>
                <a href="{{ \App\Filament\Resources\BarbershopResource::getUrl('index', panel: 'saas') }}"
                   style="font-size: 12px; font-weight: 800; color: #fbbf24; text-decoration: none;">Ver Todas as Barbearias &rarr;</a>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #cbd5e1;">
                    <thead>
                        <tr style="border-bottom: 1px solid #1e293b; background: rgba(15, 23, 42, 0.6); color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 14px 20px;">Barbearia</th>
                            <th style="padding: 14px 20px;">Cidade</th>
                            <th style="padding: 14px 20px;">Proprietário</th>
                            <th style="padding: 14px 20px;">E-mail do Dono</th>
                            <th style="padding: 14px 20px;">Status</th>
                            <th style="padding: 14px 20px;">Data de Entrada</th>
                            <th style="padding: 14px 20px; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBarbershops as $shop)
                            <tr style="border-bottom: 1px solid #1e293b;">
                                <td style="padding: 16px 20px; font-weight: 800; color: #ffffff;">
                                    {{ $shop['name'] }}
                                    <div style="font-size: 11px; font-weight: 400; color: #94a3b8;">{{ $shop['slug'] }}</div>
                                </td>
                                <td style="padding: 16px 20px;">{{ $shop['city'] }}</td>
                                <td style="padding: 16px 20px; font-weight: 700; color: #f1f5f9;">{{ $shop['owner_name'] }}</td>
                                <td style="padding: 16px 20px; color: #94a3b8;">{{ $shop['owner_email'] }}</td>
                                <td style="padding: 16px 20px;">
                                    @if($shop['is_active'])
                                        <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 9999px; border: 1px solid rgba(16, 185, 129, 0.3);">ATIVA</span>
                                    @else
                                        <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 9999px; border: 1px solid rgba(239, 68, 68, 0.3);">INATIVA</span>
                                    @endif
                                </td>
                                <td style="padding: 16px 20px; color: #94a3b8;">{{ $shop['created_at'] }}</td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <a href="{{ $shop['admin_url'] }}" target="_blank"
                                       style="background: #1e293b; color: #f1f5f9; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 11px; font-weight: 800; border: 1px solid #334155; display: inline-flex; align-items: center; gap: 4px;">
                                        Painel ↗
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 32px 20px; text-align: center; color: #64748b;">
                                    Nenhuma barbearia cadastrada ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
