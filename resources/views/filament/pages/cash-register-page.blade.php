<x-filament-panels::page>
    @php
        $carbonDate = \Carbon\Carbon::parse($selectedDate)->locale('pt_BR');
        $dateFormatted = $carbonDate->translatedFormat('l, d \d\e F \d\e Y');
        $isToday = $selectedDate === now()->format('Y-m-d');
        $isYesterday = $selectedDate === now()->subDay()->format('Y-m-d');
        $stats = $this->cashStats;
        $txs = $this->dailyTransactions;
    @endphp

    <style>
        .cr-wrap {
            display: flex;
            flex-direction: column;
            gap: 24px;
            color: #f1f5f9;
            font-family: inherit;
        }

        /* TOOLBAR */
        .cr-toolbar {
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

        .cr-date-nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .cr-btn-nav {
            height: 38px;
            padding: 0 16px;
            font-size: 13px;
            font-weight: 800;
            border-radius: 12px;
            border: 1px solid #334155;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .cr-btn-nav.active {
            background: rgba(217, 119, 6, 0.15);
            border-color: #d97706;
            color: #fbbf24;
        }

        .cr-btn-nav.inactive {
            background: #1e293b;
            color: #94a3b8;
        }

        .cr-btn-nav.inactive:hover {
            background: #334155;
            color: #ffffff;
        }

        .cr-date-input {
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

        .cr-date-title {
            font-size: 14px;
            font-weight: 800;
            color: #ffffff;
            text-transform: capitalize;
            margin-left: 8px;
        }

        /* 5 STATS CARDS */
        .cr-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        @media (min-width: 900px) {
            .cr-stats-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        .cr-stat-box {
            background: #0b1120;
            border: 1px solid #1e293b;
            border-radius: 18px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.3);
        }

        .cr-stat-box.highlight {
            background: linear-gradient(135deg, rgba(217, 119, 6, 0.15) 0%, rgba(15, 23, 42, 0.8) 100%);
            border-color: rgba(217, 119, 6, 0.4);
        }

        .cr-stat-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cr-stat-val {
            font-size: 20px;
            font-weight: 900;
            color: #ffffff;
        }

        .cr-stat-val-gold { color: #fbbf24; }
        .cr-stat-val-green { color: #34d399; }
        .cr-stat-val-sky { color: #38bdf8; }
        .cr-stat-val-purple { color: #c084fc; }

        /* TRANSACTIONS TABLE */
        .cr-table-container {
            background: #0b1120;
            border: 1px solid #1e293b;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4);
        }

        .cr-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .cr-table th {
            background: #0f172a;
            padding: 14px 18px;
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #1e293b;
        }

        .cr-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #131c31;
            color: #cbd5e1;
        }

        .cr-table tr:hover td {
            background: #0f172a/50;
        }

        .cr-empty {
            padding: 60px 20px;
            text-align: center;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .cr-pill-method {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid transparent;
        }

        .cr-method-pix { background: rgba(56, 189, 248, 0.12); border-color: rgba(56, 189, 248, 0.35); color: #38bdf8; }
        .cr-method-dinheiro { background: rgba(52, 211, 153, 0.12); border-color: rgba(52, 211, 153, 0.35); color: #34d399; }
        .cr-method-debito { background: rgba(192, 132, 252, 0.12); border-color: rgba(192, 132, 252, 0.35); color: #c084fc; }
        .cr-method-credito { background: rgba(251, 191, 36, 0.12); border-color: rgba(251, 191, 36, 0.35); color: #fbbf24; }
        .cr-method-outros { background: #1e293b; border-color: #334155; color: #94a3b8; }
    </style>

    <div class="cr-wrap">
        {{-- TOOLBAR --}}
        <div class="cr-toolbar">
            <div class="cr-date-nav">
                <button type="button" wire:click="setToday" class="cr-btn-nav {{ $isToday ? 'active' : 'inactive' }}">
                    Hoje
                </button>
                <button type="button" wire:click="setYesterday" class="cr-btn-nav {{ $isYesterday ? 'active' : 'inactive' }}">
                    Ontem
                </button>

                <input type="date" wire:model.live="selectedDate" class="cr-date-input" />

                <span class="cr-date-title">
                    📅 {{ $dateFormatted }}
                </span>
            </div>

            <div style="font-size: 12px; color: #94a3b8;">
                <strong>{{ $stats['count'] }}</strong> corte(s) recebido(s) nesta data
            </div>
        </div>

        {{-- 5 CARDS DE DISTRIBUIÇÃO DE FORMAS DE PAGAMENTO --}}
        <div class="cr-stats-grid">
            <div class="cr-stat-box">
                <span class="cr-stat-label">📱 PIX</span>
                <span class="cr-stat-val cr-stat-val-sky">R$ {{ number_format($stats['pix'], 2, ',', '.') }}</span>
                <span style="font-size: 10px; color: #64748b;">Transferência direta</span>
            </div>

            <div class="cr-stat-box">
                <span class="cr-stat-label">💵 Dinheiro</span>
                <span class="cr-stat-val cr-stat-val-green">R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</span>
                <span style="font-size: 10px; color: #64748b;">Gaveta física</span>
            </div>

            <div class="cr-stat-box">
                <span class="cr-stat-label">💳 Cartão Débito</span>
                <span class="cr-stat-val cr-stat-val-purple">R$ {{ number_format($stats['debito'], 2, ',', '.') }}</span>
                <span style="font-size: 10px; color: #64748b;">Maquininha</span>
            </div>

            <div class="cr-stat-box">
                <span class="cr-stat-label">💳 Cartão Crédito</span>
                <span class="cr-stat-val cr-stat-val-gold">R$ {{ number_format($stats['credito'], 2, ',', '.') }}</span>
                <span style="font-size: 10px; color: #64748b;">Maquininha</span>
            </div>

            <div class="cr-stat-box highlight">
                <span class="cr-stat-label" style="color: #fbbf24;">🌟 Faturamento Total</span>
                <span class="cr-stat-val" style="color: #ffffff; font-size: 22px;">R$ {{ number_format($stats['total'], 2, ',', '.') }}</span>
                <span style="font-size: 10px; color: #fbbf24;">Fechamento do dia</span>
            </div>
        </div>

        {{-- TABELA DE TRANSAÇÕES RECEBIDAS --}}
        <div class="cr-table-container">
            @if ($txs->isEmpty())
                <div class="cr-empty">
                    <span style="font-size: 32px;">🧾</span>
                    <p style="font-weight: 700; color: #cbd5e1;">Nenhuma transação concluída registrada nesta data.</p>
                    <span style="font-size: 12px;">Quando um corte é finalizado na Agenda Diária, a entrada do caixa aparece automaticamente aqui.</span>
                </div>
            @else
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th>Cliente</th>
                            <th>Barbeiro</th>
                            <th>Serviço(s)</th>
                            <th>Forma de Pagamento</th>
                            <th>Valor Recebido</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($txs as $t)
                            @php
                                $methodClass = match ($t->payment_method) {
                                    'pix' => 'cr-method-pix',
                                    'dinheiro' => 'cr-method-dinheiro',
                                    'debito' => 'cr-method-debito',
                                    'credito' => 'cr-method-credito',
                                    default => 'cr-method-outros',
                                };
                            @endphp
                            <tr>
                                <td style="font-weight: 800; color: #ffffff;">
                                    ⏰ {{ $t->scheduled_at->format('H:i') }}
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #ffffff;">{{ $t->client_name }}</span>
                                    @if ($t->client_phone)
                                        <span style="display: block; font-size: 11px; color: #64748b;">{{ $t->client_phone }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #ffffff;">{{ $t->professional?->display_name }}</span>
                                </td>
                                <td>
                                    <span style="color: #cbd5e1;">{{ $t->services_summary }}</span>
                                </td>
                                <td>
                                    <span class="cr-pill-method {{ $methodClass }}">
                                        {{ $t->payment_method ? strtoupper($t->payment_method) : 'NÃO INFORMADO' }}
                                    </span>
                                </td>
                                <td style="font-weight: 900; color: #34d399; font-size: 14px;">
                                    R$ {{ number_format($t->service_price, 2, ',', '.') }}
                                </td>
                                <td>
                                    <span style="font-size: 11px; font-weight: 800; color: #34d399; text-transform: uppercase;">
                                        ✓ Recebido
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-filament-panels::page>
