<x-filament-panels::page>
    @php
        $ranking = $this->barbersRanking;
        $services = $this->topServices;
        $absent = $this->absentClients;
    @endphp

    <style>
        .rp-wrap {
            display: flex;
            flex-direction: column;
            gap: 24px;
            color: #f1f5f9;
            font-family: inherit;
        }

        /* TOOLBAR */
        .rp-toolbar {
            background: linear-gradient(180deg, #0f172a 0%, #0b1120 100%);
            border: 1px solid #1e293b;
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .rp-preset-pills {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #020617;
            padding: 4px;
            border-radius: 14px;
            border: 1px solid #1e293b;
        }

        .rp-pill-btn {
            height: 32px;
            padding: 0 14px;
            font-size: 12px;
            font-weight: 800;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .rp-pill-btn.active {
            background: #d97706;
            color: #020617;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.3);
        }

        .rp-pill-btn.inactive {
            background: transparent;
            color: #94a3b8;
        }

        .rp-pill-btn.inactive:hover {
            color: #ffffff;
        }

        .rp-input {
            height: 38px;
            background: #020617;
            border: 1px solid #334155;
            border-radius: 12px;
            color: #ffffff;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 700;
            outline: none;
            cursor: pointer;
        }

        /* TABS */
        .rp-tabs-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 4px;
        }

        .rp-tab-link {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 800;
            border: none;
            background: transparent;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.15s ease;
        }

        .rp-tab-link.active {
            color: #fbbf24;
            border-bottom-color: #d97706;
        }

        .rp-tab-link.inactive {
            color: #64748b;
        }

        .rp-tab-link.inactive:hover {
            color: #cbd5e1;
        }

        /* TABLES */
        .rp-table-container {
            background: #0b1120;
            border: 1px solid #1e293b;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4);
        }

        .rp-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .rp-table th {
            background: #0f172a;
            padding: 14px 18px;
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #1e293b;
        }

        .rp-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #131c31;
            color: #cbd5e1;
        }

        .rp-table tr:hover td {
            background: #0f172a/50;
        }

        .rp-badge-rank {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 12px;
        }

        .rp-rank-1 { background: #d97706; color: #020617; box-shadow: 0 0 12px rgba(217, 119, 6, 0.5); }
        .rp-rank-2 { background: #94a3b8; color: #020617; }
        .rp-rank-3 { background: #b45309; color: #ffffff; }
        .rp-rank-other { background: #1e293b; color: #94a3b8; }

        .rp-btn-wa {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
            font-size: 11px;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .rp-btn-wa:hover {
            background: #10b981;
            color: #ffffff;
        }

        .rp-empty {
            padding: 60px 20px;
            text-align: center;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
    </style>

    <div class="rp-wrap">
        {{-- TOOLBAR --}}
        <div class="rp-toolbar">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div class="rp-preset-pills">
                    <button type="button" wire:click="applyPreset('month')" class="rp-pill-btn {{ $periodPreset === 'month' ? 'active' : 'inactive' }}">
                        Este Mês
                    </button>
                    <button type="button" wire:click="applyPreset('30days')" class="rp-pill-btn {{ $periodPreset === '30days' ? 'active' : 'inactive' }}">
                        Últimos 30 Dias
                    </button>
                    <button type="button" wire:click="applyPreset('year')" class="rp-pill-btn {{ $periodPreset === 'year' ? 'active' : 'inactive' }}">
                        Este Ano
                    </button>
                </div>

                <div style="display: flex; align-items: center; gap: 6px;">
                    <input type="date" wire:model.live="startDate" class="rp-input" />
                    <span style="color: #64748b; font-size: 12px;">até</span>
                    <input type="date" wire:model.live="endDate" class="rp-input" />
                </div>
            </div>

            <div style="font-size: 12px; color: #94a3b8;">
                Período: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</strong> a <strong>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</strong>
            </div>
        </div>

        {{-- ABAS --}}
        <div class="rp-tabs-bar">
            <button type="button" wire:click="$set('activeTab', 'ranking')" class="rp-tab-link {{ $activeTab === 'ranking' ? 'active' : 'inactive' }}">
                🏆 Ranking da Equipe
            </button>
            <button type="button" wire:click="$set('activeTab', 'services')" class="rp-tab-link {{ $activeTab === 'services' ? 'active' : 'inactive' }}">
                ✂️ Serviços Mais Lucrativos
            </button>
            <button type="button" wire:click="$set('activeTab', 'absent')" class="rp-tab-link {{ $activeTab === 'absent' ? 'active' : 'inactive' }}">
                📱 Clientes Ausentes / Recuperação
            </button>
        </div>

        {{-- ABA 1: RANKING DA EQUIPE --}}
        @if ($activeTab === 'ranking')
            <div class="rp-table-container">
                @if ($ranking->isEmpty())
                    <div class="rp-empty">
                        <span style="font-size: 32px;">💈</span>
                        <p style="font-weight: 700; color: #cbd5e1;">Nenhum atendimento apurado para este período.</p>
                    </div>
                @else
                    <table class="rp-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Posição</th>
                                <th>Profissional</th>
                                <th>Cortes Realizados</th>
                                <th>Faturamento Bruto</th>
                                <th>Comissão Gerada</th>
                                <th>Ticket Médio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ranking as $index => $item)
                                @php
                                    $rankNum = $index + 1;
                                    $rankClass = match ($rankNum) {
                                        1 => 'rp-rank-1',
                                        2 => 'rp-rank-2',
                                        3 => 'rp-rank-3',
                                        default => 'rp-rank-other',
                                    };
                                    $medal = match ($rankNum) {
                                        1 => '🥇',
                                        2 => '🥈',
                                        3 => '🥉',
                                        default => '#' . $rankNum,
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <span class="rp-badge-rank {{ $rankClass }}">
                                            {{ $medal }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 800; font-size: 14px; color: #ffffff;">{{ $item['professional']->display_name }}</div>
                                        <div style="font-size: 11px; color: #94a3b8;">Comissão de {{ number_format($item['professional']->commission_rate, 0) }}%</div>
                                    </td>
                                    <td style="font-weight: 800; color: #ffffff;">
                                        {{ $item['count'] }} corte(s)
                                    </td>
                                    <td style="font-weight: 900; font-size: 15px; color: #fbbf24;">
                                        R$ {{ number_format($item['total_gross'], 2, ',', '.') }}
                                    </td>
                                    <td style="font-weight: 800; color: #34d399;">
                                        R$ {{ number_format($item['total_commission'], 2, ',', '.') }}
                                    </td>
                                    <td style="color: #cbd5e1;">
                                        R$ {{ number_format($item['avg_ticket'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @elseif ($activeTab === 'services')
            {{-- ABA 2: SERVIÇOS MAIS LUCRATIVOS --}}
            <div class="rp-table-container">
                @if ($services->isEmpty())
                    <div class="rp-empty">
                        <span style="font-size: 32px;">✂️</span>
                        <p style="font-weight: 700; color: #cbd5e1;">Nenhum serviço registrado neste período.</p>
                    </div>
                @else
                    <table class="rp-table">
                        <thead>
                            <tr>
                                <th>Serviço</th>
                                <th>Quantidade Realizada</th>
                                <th>Faturamento Total</th>
                                <th>Preço Médio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($services as $s)
                                <tr>
                                    <td style="font-weight: 800; font-size: 14px; color: #ffffff;">
                                        {{ $s['name'] }}
                                    </td>
                                    <td style="font-weight: 700; color: #ffffff;">
                                        {{ $s['count'] }} vez(es)
                                    </td>
                                    <td style="font-weight: 900; color: #fbbf24; font-size: 15px;">
                                        R$ {{ number_format($s['total_revenue'], 2, ',', '.') }}
                                    </td>
                                    <td style="color: #cbd5e1;">
                                        R$ {{ number_format($s['avg_price'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @else
            {{-- ABA 3: RECUPERAÇÃO DE CLIENTES (CLIENTES AUSENTES) --}}
            <div>
                <div style="background: rgba(217, 119, 6, 0.1); border: 1px solid rgba(217, 119, 6, 0.3); border-radius: 14px; padding: 14px 18px; margin-bottom: 16px; font-size: 12px; color: #cbd5e1;">
                    💡 <strong>Oportunidade de Receita:</strong> Estes clientes já conhecem seu trabalho, mas não cortam o cabelo há mais de <strong>{{ $absentDays }} dias</strong>. Clique no botão de WhatsApp para enviar uma mensagem personalizada de retorno com 1 clique!
                </div>

                <div class="rp-table-container">
                    @if ($absent->isEmpty())
                        <div class="rp-empty">
                            <span style="font-size: 32px;">🎉</span>
                            <p style="font-weight: 700; color: #34d399;">Excelente! Nenhum cliente ausente há mais de {{ $absentDays }} dias.</p>
                        </div>
                    @else
                        <table class="rp-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Último Atendimento</th>
                                    <th>Tempo Ausente</th>
                                    <th>Total de Visitas Anteriores</th>
                                    <th style="text-align: right;">Ação no WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($absent as $c)
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: #ffffff;">{{ $c['client_name'] }}</div>
                                            <div style="font-size: 11px; color: #64748b;">{{ $c['client_phone'] }}</div>
                                        </td>
                                        <td>
                                            <span style="font-weight: 700; color: #cbd5e1;">{{ $c['last_appointment']->format('d/m/Y') }}</span>
                                        </td>
                                        <td>
                                            <span style="background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(244, 63, 94, 0.4); color: #fb7185; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 11px;">
                                                ⚠️ {{ $c['days_away'] }} dias sem vir
                                            </span>
                                        </td>
                                        <td style="color: #94a3b8;">
                                            {{ $c['total_cuts'] }} corte(s) histórico(s)
                                        </td>
                                        <td style="text-align: right;">
                                            @if ($c['whatsapp_url'])
                                            <a href="{{ $c['whatsapp_url'] }}" target="_blank" class="rp-btn-wa">
                                                <svg style="width: 14px; height: 14px;" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                                </svg>
                                                <span>Chamar no WhatsApp</span>
                                            </a>
                                            @else
                                                <span class="text-xs text-gray-500">Contato protegido</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
