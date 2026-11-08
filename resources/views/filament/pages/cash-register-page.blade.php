<x-filament-panels::page>
    @php
        $carbonDate = \Carbon\Carbon::parse($selectedDate)->locale('pt_BR');
        $dateFormatted = $carbonDate->translatedFormat('l, d \d\e F \d\e Y');
        $isToday = $selectedDate === now()->format('Y-m-d');
        $isYesterday = $selectedDate === now()->subDay()->format('Y-m-d');
        $stats = $this->cashStats;
        $txs = $this->dailyTransactions;
        $movs = $this->dailyMovements;
        $tenant = filament()->getTenant();
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

        .cr-btn-cta {
            height: 38px;
            padding: 0 18px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cr-btn-move {
            background: #1e293b;
            border: 1px solid #334155;
            color: #fbbf24;
        }

        .cr-btn-move:hover {
            background: #334155;
            color: #ffffff;
        }

        .cr-btn-print {
            background: #d97706;
            color: #020617;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
        }

        .cr-btn-print:hover {
            background: #f59e0b;
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

        .cr-stat-box.drawer {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(15, 23, 42, 0.8) 100%);
            border-color: rgba(16, 185, 129, 0.4);
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
        .cr-stat-val-rose { color: #fb7185; }

        /* CASH DRAWER BREAKDOWN BAR */
        .cr-drawer-bar {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 14px 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            font-size: 13px;
        }

        .cr-drawer-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cr-drawer-item strong {
            color: #ffffff;
            font-weight: 800;
        }

        /* TABLES */
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
            padding: 40px 20px;
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

        .cr-badge-sangria { background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(244, 63, 94, 0.4); color: #fb7185; }
        .cr-badge-suprimento { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; }
        .cr-badge-abertura { background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.4); color: #38bdf8; }

        /* MODAL */
        .cr-modal-mask {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .cr-modal-window {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 24px;
            max-width: 520px;
            width: 100%;
            padding: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .cr-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 14px;
            border-bottom: 1px solid #1e293b;
        }

        .cr-modal-head h3 {
            font-size: 17px;
            font-weight: 900;
            color: #ffffff;
            margin: 0;
        }

        .cr-modal-x {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 26px;
            cursor: pointer;
            line-height: 1;
        }

        .cr-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cr-field label {
            font-size: 11px;
            font-weight: 700;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cr-input, .cr-select {
            width: 100%;
            background: #020617;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 13px;
            color: #ffffff;
            outline: none;
            box-sizing: border-box;
        }

        .cr-input:focus, .cr-select:focus {
            border-color: #d97706;
        }

        .cr-modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid #1e293b;
        }

        .cr-btn-cancel {
            background: #1e293b;
            border: 1px solid #334155;
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            padding: 10px 18px;
            border-radius: 12px;
            cursor: pointer;
        }

        .cr-btn-confirm {
            background: #d97706;
            border: none;
            color: #020617;
            font-size: 12px;
            font-weight: 900;
            padding: 11px 22px;
            border-radius: 12px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
        }

        /* PRINT STYLES FOR THERMAL RECEIPT (80mm) */
        @media print {
            body * {
                visibility: hidden;
            }
            .cr-print-receipt, .cr-print-receipt * {
                visibility: visible;
            }
            .cr-print-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 78mm;
                font-family: 'Courier New', Courier, monospace;
                font-size: 12px;
                color: #000000 !important;
                background: #ffffff !important;
                padding: 5mm;
            }
            .no-print {
                display: none !important;
            }
        }
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

            <div style="display: flex; align-items: center; gap: 10px;">
                <button type="button" wire:click="openMovementModal('outflow')" class="cr-btn-cta cr-btn-move">
                    <span>💸 Sangria / Suprimento</span>
                </button>

                <button type="button" wire:click="$set('printModalOpen', true)" class="cr-btn-cta cr-btn-print">
                    <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>🖨️ Imprimir Fechamento</span>
                </button>
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
                <span class="cr-stat-label">💵 Dinheiro (Cortes)</span>
                <span class="cr-stat-val cr-stat-val-green">R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</span>
                <span style="font-size: 10px; color: #64748b;">Recebido em atendimentos</span>
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

        {{-- AUDITORIA DA GAVETA DE DINHEIRO FÍSICO --}}
        <div class="cr-drawer-bar">
            <div class="cr-drawer-item">
                <span style="color: #94a3b8;">Fundo Inicial:</span>
                <strong>R$ {{ number_format($stats['fundo_abertura'], 2, ',', '.') }}</strong>
            </div>
            <div class="cr-drawer-item">
                <span style="color: #34d399;">+ Suprimentos:</span>
                <strong>R$ {{ number_format($stats['suprimentos'], 2, ',', '.') }}</strong>
            </div>
            <div class="cr-drawer-item">
                <span style="color: #34d399;">+ Vendas Dinheiro:</span>
                <strong>R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</strong>
            </div>
            <div class="cr-drawer-item">
                <span style="color: #fb7185;">- Sangrias / Retiradas:</span>
                <strong style="color: #fb7185;">R$ {{ number_format($stats['sangrias'], 2, ',', '.') }}</strong>
            </div>
            <div class="cr-drawer-item" style="background: rgba(16, 185, 129, 0.15); padding: 6px 14px; border-radius: 10px; border: 1px solid rgba(16, 185, 129, 0.3);">
                <span style="color: #34d399; font-weight: 800;">💵 Dinheiro Esperado na Gaveta:</span>
                <strong style="color: #34d399; font-size: 16px;">R$ {{ number_format($stats['saldo_dinheiro_gaveta'], 2, ',', '.') }}</strong>
            </div>
        </div>

        {{-- SEÇÃO 1: VENDAS DO DIA --}}
        <div>
            <h3 style="font-size: 14px; font-weight: 800; color: #ffffff; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>✂️ Vendas & Atendimentos Concluídos ({{ $txs->count() }})</span>
            </h3>

            <div class="cr-table-container">
                @if ($txs->isEmpty())
                    <div class="cr-empty">
                        <span style="font-size: 28px;">🧾</span>
                        <p style="font-weight: 700; color: #cbd5e1;">Nenhuma transação de atendimento concluída nesta data.</p>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- SEÇÃO 2: MOVIMENTAÇÕES AVULSAS (SANGRIAS E SUPRIMENTOS) --}}
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <h3 style="font-size: 14px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                    <span>💸 Sangrias & Suprimentos da Gaveta ({{ $movs->count() }})</span>
                </h3>

                <div style="display: flex; gap: 6px;">
                    <button type="button" wire:click="openMovementModal('opening')" class="cr-btn-nav" style="height: 30px; font-size: 11px; padding: 0 10px;">
                        + Fundo Inicial
                    </button>
                    <button type="button" wire:click="openMovementModal('inflow')" class="cr-btn-nav" style="height: 30px; font-size: 11px; padding: 0 10px;">
                        + Suprimento
                    </button>
                    <button type="button" wire:click="openMovementModal('outflow')" class="cr-btn-nav" style="height: 30px; font-size: 11px; padding: 0 10px; color: #fb7185; border-color: rgba(244, 63, 94, 0.3);">
                        - Sangria (Retirada)
                    </button>
                </div>
            </div>

            <div class="cr-table-container">
                @if ($movs->isEmpty())
                    <div class="cr-empty" style="padding: 30px 20px;">
                        <span style="font-size: 24px;">⚖️</span>
                        <p style="font-size: 12px; color: #94a3b8;">Nenhuma sangria ou suprimento registrado nesta data.</p>
                    </div>
                @else
                    <table class="cr-table">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Tipo</th>
                                <th>Descrição / Motivo</th>
                                <th>Operador</th>
                                <th>Valor</th>
                                <th style="text-align: right;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($movs as $m)
                                @php
                                    $bClass = match ($m->type) {
                                        'opening' => 'cr-badge-abertura',
                                        'inflow' => 'cr-badge-suprimento',
                                        default => 'cr-badge-sangria',
                                    };
                                    $isOut = $m->type === 'outflow';
                                @endphp
                                <tr>
                                    <td style="font-weight: 700; color: #ffffff;">
                                        {{ $m->created_at->format('H:i') }}
                                    </td>
                                    <td>
                                        <span class="cr-pill-method {{ $bClass }}">
                                            {{ $m->type_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: #cbd5e1;">{{ $m->description }}</span>
                                    </td>
                                    <td>
                                        <span style="font-size: 11px; color: #94a3b8;">{{ $m->user?->name ?? 'Sistema' }}</span>
                                    </td>
                                    <td style="font-weight: 900; font-size: 14px; color: {{ $isOut ? '#fb7185' : '#34d399' }};">
                                        {{ $isOut ? '-' : '+' }} R$ {{ number_format($m->amount, 2, ',', '.') }}
                                    </td>
                                    <td style="text-align: right;">
                                        <button
                                            type="button"
                                            wire:click="deleteMovement({{ $m->id }})"
                                            wire:confirm="Tem certeza que deseja remover esta movimentação?"
                                            style="background: transparent; border: none; color: #64748b; cursor: pointer; font-size: 14px;"
                                            title="Remover movimentação"
                                        >
                                            ✕
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL DE MOVIMENTAÇÃO (SANGRIA / SUPRIMENTO / ABERTURA) --}}
    @if ($movementModalOpen)
        <div class="cr-modal-mask">
            <div class="cr-modal-window">
                <div class="cr-modal-head">
                    <h3>💸 Nova Movimentação de Caixa</h3>
                    <button type="button" wire:click="$set('movementModalOpen', false)" class="cr-modal-x">&times;</button>
                </div>

                <form wire:submit.prevent="saveMovement" style="display: flex; flex-direction: column; gap: 14px;">
                    <div class="cr-field">
                        <label>Tipo de Movimentação</label>
                        <select wire:model="movementType" class="cr-select">
                            <option value="opening">Fundo de Troco (Abertura de Caixa)</option>
                            <option value="inflow">Suprimento (Entrada de Dinheiro)</option>
                            <option value="outflow">Sangria (Retirada de Dinheiro da Gaveta)</option>
                        </select>
                    </div>

                    <div class="cr-field">
                        <label>Valor (R$)</label>
                        <input type="number" step="0.01" wire:model="movementAmount" placeholder="0.00" class="cr-input" style="font-size: 16px; font-weight: 900; color: #fbbf24;" required />
                        @error('movementAmount') <span style="color: #fb7185; font-size: 11px;">{{ $message }}</span> @enderror
                    </div>

                    <div class="cr-field">
                        <label>Motivo / Descrição</label>
                        <input type="text" wire:model="movementDescription" placeholder="Ex: Compra de café, troco inicial..." class="cr-input" required />
                        @error('movementDescription') <span style="color: #fb7185; font-size: 11px;">{{ $message }}</span> @enderror
                    </div>

                    <div class="cr-modal-actions">
                        <button type="button" wire:click="$set('movementModalOpen', false)" class="cr-btn-cancel">
                            Cancelar
                        </button>
                        <button type="submit" class="cr-btn-confirm">
                            Salvar Movimentação ✓
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL / CUPOM DE IMPRESSÃO DO FECHAMENTO (80mm) --}}
    @if ($printModalOpen)
        <div class="cr-modal-mask no-print">
            <div class="cr-modal-window" style="max-width: 440px;">
                <div class="cr-modal-head">
                    <h3>🖨️ Cupom de Fechamento de Caixa</h3>
                    <button type="button" wire:click="$set('printModalOpen', false)" class="cr-modal-x">&times;</button>
                </div>

                {{-- PRÉ-VISUALIZAÇÃO DO CUPOM TÉRMICO --}}
                <div class="cr-print-receipt" style="background: #ffffff; color: #000000; padding: 20px; border-radius: 12px; font-family: monospace; font-size: 12px; line-height: 1.4;">
                    <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 900; text-transform: uppercase;">{{ $tenant?->name ?? 'BARBEARIA' }}</h4>
                        <span style="font-size: 10px; display: block;">FECHAMENTO DIÁRIO DE CAIXA</span>
                        <span style="font-size: 10px; display: block;">{{ $carbonDate->format('d/m/Y') }} • {{ now()->format('H:i:s') }}</span>
                    </div>

                    <div style="border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px;">
                        <div style="font-weight: 800; margin-bottom: 4px;">RESUMO POR FORMA DE PAGAMENTO:</div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>PIX:</span> <strong>R$ {{ number_format($stats['pix'], 2, ',', '.') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>DINHEIRO (CORTES):</span> <strong>R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>CARTÃO DÉBITO:</span> <strong>R$ {{ number_format($stats['debito'], 2, ',', '.') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>CARTÃO CRÉDITO:</span> <strong>R$ {{ number_format($stats['credito'], 2, ',', '.') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 900; margin-top: 6px; border-top: 1px solid #000; padding-top: 4px;">
                            <span>TOTAL FATURADO:</span> <span>R$ {{ number_format($stats['total'], 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div style="border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px;">
                        <div style="font-weight: 800; margin-bottom: 4px;">CONFERÊNCIA DA GAVETA (FÍSICO):</div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>FUNDO INICIAL:</span> <span>R$ {{ number_format($stats['fundo_abertura'], 2, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>+ SUPRIMENTOS:</span> <span>R$ {{ number_format($stats['suprimentos'], 2, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>+ VENDAS DINHEIRO:</span> <span>R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>- SANGRIA/RETIRADAS:</span> <span>R$ {{ number_format($stats['sangrias'], 2, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 900; margin-top: 4px;">
                            <span>SALDO NA GAVETA:</span> <span>R$ {{ number_format($stats['saldo_dinheiro_gaveta'], 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div style="text-align: center; font-size: 10px; margin-top: 15px;">
                        <span>TOTAL DE CLIENTES: {{ $stats['count'] }}</span>
                        <div style="margin-top: 25px; border-top: 1px solid #000; padding-top: 4px;">
                            RESPONSÁVEL PELO CAIXA
                        </div>
                    </div>
                </div>

                <div class="cr-modal-actions no-print">
                    <button type="button" wire:click="$set('printModalOpen', false)" class="cr-btn-cancel">
                        Fechar
                    </button>
                    <button type="button" onclick="window.print()" class="cr-btn-confirm" style="background: #10b981;">
                        Imprimir Agora 🖨️
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
