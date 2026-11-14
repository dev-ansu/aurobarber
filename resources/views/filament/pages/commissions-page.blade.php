<x-filament-panels::page>
    @php
        $stats = $this->summaryStats;
        $appts = $this->appointments;
        $prodSales = $this->productSales;
        $payouts = $this->payoutsHistory;
        $selectedProf = $this->selectedProfessional;
    @endphp

    <style>
        .cp-wrap {
            display: flex;
            flex-direction: column;
            gap: 24px;
            color: #f1f5f9;
            font-family: inherit;
        }

        /* 1. TOOLBAR & FILTERS */
        .cp-toolbar {
            background: linear-gradient(180deg, #0f172a 0%, #0b1120 100%);
            border: 1px solid #1e293b;
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .cp-toolbar-row1 {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .cp-filter-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .cp-select, .cp-input {
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

        .cp-select:focus, .cp-input:focus {
            border-color: #d97706;
        }

        .cp-preset-pills {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #020617;
            padding: 4px;
            border-radius: 14px;
            border: 1px solid #1e293b;
        }

        .cp-pill-btn {
            height: 30px;
            padding: 0 14px;
            font-size: 12px;
            font-weight: 800;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .cp-pill-btn.active {
            background: #d97706;
            color: #020617;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.3);
        }

        .cp-pill-btn.inactive {
            background: transparent;
            color: #94a3b8;
        }

        .cp-pill-btn.inactive:hover {
            color: #ffffff;
        }

        .cp-btn-liquidate {
            background: #10b981;
            color: #ffffff;
            font-weight: 900;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 11px 22px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.35);
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cp-btn-liquidate:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        /* 2. STATS KPI CARDS */
        .cp-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        @media (min-width: 900px) {
            .cp-stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .cp-stat-box {
            background: #0b1120;
            border: 1px solid #1e293b;
            border-radius: 18px;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.3);
        }

        .cp-stat-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cp-stat-val {
            font-size: 22px;
            font-weight: 900;
            color: #ffffff;
        }

        .cp-stat-val-amber { color: #fbbf24; }
        .cp-stat-val-emerald { color: #34d399; }

        .cp-stat-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        /* 3. TABS */
        .cp-tabs-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 4px;
        }

        .cp-tab-link {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 800;
            border: none;
            background: transparent;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.15s ease;
        }

        .cp-tab-link.active {
            color: #fbbf24;
            border-bottom-color: #d97706;
        }

        .cp-tab-link.inactive {
            color: #64748b;
        }

        .cp-tab-link.inactive:hover {
            color: #cbd5e1;
        }

        /* 4. TABLE STYLING */
        .cp-table-container {
            background: #0b1120;
            border: 1px solid #1e293b;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4);
        }

        .cp-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .cp-table th {
            background: #0f172a;
            padding: 14px 18px;
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #1e293b;
        }

        .cp-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #131c31;
            color: #cbd5e1;
        }

        .cp-table tr:hover td {
            background: #0f172a/50;
        }

        .cp-badge-pending {
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 9px;
            border-radius: 9999px;
            background: rgba(217, 119, 6, 0.15);
            border: 1px solid rgba(217, 119, 6, 0.4);
            color: #fbbf24;
        }

        .cp-badge-paid {
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 9px;
            border-radius: 9999px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
        }

        .cp-empty {
            padding: 60px 20px;
            text-align: center;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        /* 5. MODAL */
        .cp-modal-mask {
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

        .cp-modal-window {
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

        .cp-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 14px;
            border-bottom: 1px solid #1e293b;
        }

        .cp-modal-head h3 {
            font-size: 17px;
            font-weight: 900;
            color: #ffffff;
            margin: 0;
        }

        .cp-modal-x {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 26px;
            cursor: pointer;
            line-height: 1;
        }

        .cp-modal-summary-box {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .cp-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }

        .cp-summary-row.highlight {
            padding-top: 8px;
            border-top: 1px solid #1e293b;
            font-size: 15px;
            font-weight: 900;
        }

        .cp-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cp-field label {
            font-size: 11px;
            font-weight: 700;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cp-modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid #1e293b;
        }

        .cp-btn-cancel {
            background: #1e293b;
            border: 1px solid #334155;
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            padding: 10px 18px;
            border-radius: 12px;
            cursor: pointer;
        }

        .cp-btn-confirm {
            background: #10b981;
            border: none;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            padding: 11px 22px;
            border-radius: 12px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }

        .cp-btn-confirm:hover {
            background: #059669;
        }
    </style>

    <div class="cp-wrap">
        {{-- 1. BARRA DE FILTROS & COMANDO --}}
        <div class="cp-toolbar">
            <div class="cp-toolbar-row1">
                {{-- SELETOR DE PROFISSIONAL --}}
                <div class="cp-filter-group">
                    <select wire:model.live="selectedProfessionalId" class="cp-select">
                        @foreach ($this->professionals as $pro)
                            <option value="{{ $pro->id }}">💈 {{ $pro->display_name }} ({{ number_format($pro->commission_rate, 0) }}%)</option>
                        @endforeach
                    </select>

                    {{-- PRESETS DE PERÍODO --}}
                    <div class="cp-preset-pills">
                        <button type="button" wire:click="applyPreset('today')" class="cp-pill-btn {{ $periodPreset === 'today' ? 'active' : 'inactive' }}">
                            Hoje
                        </button>
                        <button type="button" wire:click="applyPreset('week')" class="cp-pill-btn {{ $periodPreset === 'week' ? 'active' : 'inactive' }}">
                            Esta Semana
                        </button>
                        <button type="button" wire:click="applyPreset('month')" class="cp-pill-btn {{ $periodPreset === 'month' ? 'active' : 'inactive' }}">
                            Este Mês
                        </button>
                    </div>

                    {{-- DATAS INÍCIO E FIM --}}
                    <input type="date" wire:model.live="startDate" class="cp-input" title="Data Inicial" />
                    <span style="color: #64748b; font-size: 12px;">até</span>
                    <input type="date" wire:model.live="endDate" class="cp-input" title="Data Final" />

                    {{-- STATUS --}}
                    <select wire:model.live="statusFilter" class="cp-select">
                        <option value="pending">⏳ Apenas Em Aberto</option>
                        <option value="paid">✅ Apenas Já Liquidados</option>
                        <option value="all">Todos os Atendimentos</option>
                    </select>
                </div>

                {{-- BOTÃO LIQUIDAR COMISSÃO (Apenas Dono / Gestor) --}}
                @if ($selectedProf && !auth()->user()?->isRestrictedBarber())
                    <button type="button" wire:click="openPayoutModal" class="cp-btn-liquidate">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Liquidar Comissões</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. CARDS DE KPI FINANCEIRO --}}
        <div class="cp-stats-grid">
            <div class="cp-stat-box">
                <span class="cp-stat-label">{{ auth()->user()?->isRestrictedBarber() ? 'Minha Produção Bruta' : 'Produção Bruta' }}</span>
                <span class="cp-stat-val">R$ {{ number_format($stats['total_gross'], 2, ',', '.') }}</span>
                <span class="cp-stat-sub">{{ $stats['count'] }} corte(s) + {{ $stats['products_count'] }} produto(s)</span>
            </div>

            <div class="cp-stat-box">
                <span class="cp-stat-label">{{ auth()->user()?->isRestrictedBarber() ? 'Minha Comissão Total' : 'Comissão do Barbeiro' }}</span>
                <span class="cp-stat-val cp-stat-val-amber">R$ {{ number_format($stats['total_commission'], 2, ',', '.') }}</span>
                <span class="cp-stat-sub">
                    Cortes: R$ {{ number_format($stats['services_commission'], 2, ',', '.') }} | Produtos: R$ {{ number_format($stats['products_commission'], 2, ',', '.') }}
                </span>
            </div>

            @if (!auth()->user()?->isRestrictedBarber())
                <div class="cp-stat-box">
                    <span class="cp-stat-label">Lucro da Barbearia</span>
                    <span class="cp-stat-val cp-stat-val-emerald">R$ {{ number_format($stats['barbershop_cut'], 2, ',', '.') }}</span>
                    <span class="cp-stat-sub">Retenção líquida da casa</span>
                </div>
            @else
                <div class="cp-stat-box">
                    <span class="cp-stat-label">Recibos Liquidados</span>
                    <span class="cp-stat-val cp-stat-val-emerald">{{ $payouts->count() }} recibo(s)</span>
                    <span class="cp-stat-sub">Histórico de pagamentos recebidos</span>
                </div>
            @endif

            <div class="cp-stat-box">
                <span class="cp-stat-label">Venda de Produtos</span>
                <span class="cp-stat-val">R$ {{ number_format($stats['products_gross'], 2, ',', '.') }}</span>
                <span class="cp-stat-sub">{{ $stats['products_count'] }} item(ns) vendidos</span>
            </div>
        </div>

        {{-- 3. ABAS DE VISUALIZAÇÃO --}}
        <div class="cp-tabs-bar">
            <button type="button" wire:click="$set('activeTab', 'appointments')" class="cp-tab-link {{ $activeTab === 'appointments' ? 'active' : 'inactive' }}">
                ✂️ Atendimentos do Período ({{ $appts->count() }})
            </button>
            <button type="button" wire:click="$set('activeTab', 'products')" class="cp-tab-link {{ $activeTab === 'products' ? 'active' : 'inactive' }}">
                🧴 Produtos Vendidos ({{ $prodSales->count() }})
            </button>
            <button type="button" wire:click="$set('activeTab', 'history')" class="cp-tab-link {{ $activeTab === 'history' ? 'active' : 'inactive' }}">
                📜 Histórico de Fechamentos / Recibos ({{ $payouts->count() }})
            </button>
        </div>

        {{-- 4. ABA 1: LISTA DE ATENDIMENTOS DO PERÍODO --}}
        @if ($activeTab === 'appointments')
            <div class="cp-table-container">
                @if ($appts->isEmpty())
                    <div class="cp-empty">
                        <span style="font-size: 32px;">☕</span>
                        <p style="font-weight: 700; color: #cbd5e1;">Nenhum atendimento concluído encontrado para os filtros selecionados.</p>
                        <span style="font-size: 12px;">Mude o período acima ou verifique se os cortes já foram marcados como concluídos na Agenda Diária.</span>
                    </div>
                @else
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>Data / Hora</th>
                                <th>Barbeiro</th>
                                <th>Cliente</th>
                                <th>Serviço(s)</th>
                                <th>Valor Bruto</th>
                                <th>% Comis.</th>
                                <th>Comissão (R$)</th>
                                <th>Status Comissão</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appts as $a)
                                <tr>
                                    <td>
                                        <div style="font-weight: 800; color: #ffffff;">{{ $a->scheduled_at->format('d/m/Y') }}</div>
                                        <div style="font-size: 11px; color: #64748b;">{{ $a->scheduled_at->format('H:i') }} às {{ $a->ends_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 700; color: #ffffff;">{{ $a->professional?->display_name }}</span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 700; color: #ffffff;">{{ $a->client_name }}</span>
                                        @if ($a->payment_method)
                                            <span style="display: block; font-size: 10px; color: #94a3b8; text-transform: uppercase;">Pago via {{ $a->payment_method }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="color: #cbd5e1;">{{ $a->services_summary }}</span>
                                    </td>
                                    <td style="font-weight: 800; color: #ffffff;">
                                        R$ {{ number_format($a->service_price, 2, ',', '.') }}
                                    </td>
                                    <td style="color: #94a3b8;">
                                        {{ number_format($a->commission_rate, 0) }}%
                                    </td>
                                    <td style="font-weight: 900; color: #fbbf24;">
                                        R$ {{ number_format($a->commission_amount, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        @if ($a->commission_status === 'paid')
                                            <span class="cp-badge-paid">✓ Pago (#{{ $a->commission_payout_id }})</span>
                                        @else
                                            <span class="cp-badge-pending">⏳ Em Aberto</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @elseif ($activeTab === 'products')
            {{-- ABA DE PRODUTOS VENDIDOS PELO BARBEIRO --}}
            <div class="cp-table-container">
                @if ($prodSales->isEmpty())
                    <div class="cp-empty">
                        <span style="font-size: 32px;">🧴</span>
                        <p style="font-weight: 700; color: #cbd5e1;">Nenhuma venda de produto encontrada para o barbeiro no período selecionado.</p>
                        <span style="font-size: 12px;">Vendas registradas no balcão ou durante o corte com comissão atribuída aparecerão aqui.</span>
                    </div>
                @else
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>Data / Hora</th>
                                <th>Barbeiro</th>
                                <th>Produto Vendido</th>
                                <th>Cliente</th>
                                <th>Valor Total</th>
                                <th>% Comis.</th>
                                <th>Comissão (R$)</th>
                                <th>Status Comissão</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prodSales as $ps)
                                <tr>
                                    <td>
                                        <div style="font-weight: 800; color: #ffffff;">{{ $ps->created_at->format('d/m/Y') }}</div>
                                        <div style="font-size: 11px; color: #64748b;">{{ $ps->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 700; color: #ffffff;">{{ $ps->professional?->display_name }}</span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 800; color: #38bdf8;">{{ $ps->quantity }}x {{ $ps->product?->name ?? 'Produto' }}</span>
                                        <div style="font-size: 11px; color: #94a3b8;">Unitário: R$ {{ number_format($ps->unit_price, 2, ',', '.') }}</div>
                                    </td>
                                    <td>
                                        <span style="color: #cbd5e1;">{{ $ps->client_name ?: ($ps->appointment?->client_name ?: 'Balcão') }}</span>
                                        <span style="display: block; font-size: 10px; color: #94a3b8; text-transform: uppercase;">{{ $ps->payment_method_label }}</span>
                                    </td>
                                    <td style="font-weight: 800; color: #ffffff;">
                                        R$ {{ number_format($ps->total_price, 2, ',', '.') }}
                                    </td>
                                    <td style="color: #94a3b8;">
                                        {{ number_format($ps->commission_rate, 0) }}%
                                    </td>
                                    <td style="font-weight: 900; color: #fbbf24;">
                                        R$ {{ number_format($ps->commission_amount, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        @if ($ps->commission_status === 'paid')
                                            <span class="cp-badge-paid">✓ Pago (#{{ $ps->commission_payout_id }})</span>
                                        @else
                                            <span class="cp-badge-pending">⏳ Em Aberto</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @else
            {{-- 5. ABA 2: HISTÓRICO DE RECIBOS / FECHAMENTOS --}}
            <div class="cp-table-container">
                @if ($payouts->isEmpty())
                    <div class="cp-empty">
                        <span style="font-size: 32px;">📑</span>
                        <p style="font-weight: 700; color: #cbd5e1;">Nenhum fechamento de comissão liquidado até o momento.</p>
                        <span style="font-size: 12px;">Quando você clicar em "Liquidar Comissões", os comprovantes ficarão salvos aqui.</span>
                    </div>
                @else
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>Recibo</th>
                                <th>Data Pagamento</th>
                                <th>Barbeiro</th>
                                <th>Período Apurado</th>
                                <th>Produção</th>
                                <th>Cortes</th>
                                <th>Produtos</th>
                                <th>Comissão Paga</th>
                                <th>Método</th>
                                <th style="text-align: right;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payouts as $p)
                                <tr>
                                    <td>
                                        <span style="font-weight: 900; color: #d97706;">#{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 700; color: #ffffff;">{{ $p->paid_at->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 800; color: #ffffff;">{{ $p->professional?->display_name }}</span>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; color: #94a3b8;">{{ $p->period_start->format('d/m/Y') }} até {{ $p->period_end->format('d/m/Y') }}</span>
                                    </td>
                                    <td style="color: #cbd5e1; font-weight: 700;">
                                        R$ {{ number_format($p->total_gross, 2, ',', '.') }}
                                    </td>
                                    <td style="font-size: 12px; color: #94a3b8;">
                                        {{ $p->appointments_count }} corte(s)
                                    </td>
                                    <td style="font-size: 12px; color: #38bdf8;">
                                        {{ $p->products_count }} item(ns)
                                    </td>
                                    <td style="font-weight: 900; color: #34d399;">
                                        R$ {{ number_format($p->total_commission, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; background: #1e293b; padding: 3px 8px; border-radius: 6px; color: #cbd5e1;">
                                            {{ $p->payment_method_label }}
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <button
                                            type="button"
                                            wire:click="openPrintPayoutModal({{ $p->id }})"
                                            style="padding: 6px 12px; border-radius: 8px; background: #1e293b; border: 1px solid #334155; color: #fbbf24; font-size: 11px; font-weight: 800; cursor: pointer;"
                                        >
                                            🖨️ Recibo
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif
    </div>

    {{-- MODAL DE LIQUIDAÇÃO / PAGAMENTO DE COMISSÃO --}}
    @if ($payoutModalOpen)
        <div class="cp-modal-mask">
            <div class="cp-modal-window">
                <div class="cp-modal-head">
                    <h3>💰 Fechamento & Pagamento de Comissão</h3>
                    <button type="button" wire:click="$set('payoutModalOpen', false)" class="cp-modal-x">&times;</button>
                </div>

                <div class="cp-modal-summary-box">
                    <div class="cp-summary-row">
                        <span style="color: #94a3b8;">Profissional:</span>
                        <strong style="color: #ffffff;">{{ $selectedProf?->display_name }}</strong>
                    </div>
                    <div class="cp-summary-row">
                        <span style="color: #94a3b8;">Período Apurado:</span>
                        <span>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</span>
                    </div>
                    <div class="cp-summary-row">
                        <span style="color: #94a3b8;">Cortes / Serviços em Aberto:</span>
                        <strong style="color: #ffffff;">{{ $appts->where('commission_status', '!=', 'paid')->count() }} corte(s) (R$ {{ number_format($appts->where('commission_status', '!=', 'paid')->sum('service_price'), 2, ',', '.') }})</strong>
                    </div>
                    <div class="cp-summary-row">
                        <span style="color: #94a3b8;">Produtos Vendidos em Aberto:</span>
                        <strong style="color: #38bdf8;">{{ $prodSales->where('commission_status', '!=', 'paid')->sum('quantity') }} un. (R$ {{ number_format($prodSales->where('commission_status', '!=', 'paid')->sum('total_price'), 2, ',', '.') }})</strong>
                    </div>
                    <div class="cp-summary-row">
                        <span style="color: #94a3b8;">Comissão de Cortes:</span>
                        <span style="color: #cbd5e1;">R$ {{ number_format($stats['services_commission'], 2, ',', '.') }}</span>
                    </div>
                    <div class="cp-summary-row">
                        <span style="color: #94a3b8;">Comissão de Produtos:</span>
                        <span style="color: #38bdf8;">R$ {{ number_format($stats['products_commission'], 2, ',', '.') }}</span>
                    </div>
                    <div class="cp-summary-row highlight">
                        <span style="color: #fbbf24;">Total Geral a Pagar ao Barbeiro:</span>
                        <strong style="color: #34d399; font-size: 18px;">R$ {{ number_format($stats['total_commission'], 2, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="cp-field">
                    <label>Forma de Pagamento da Comissão</label>
                    <select wire:model="payoutMethod" class="cp-select" style="width: 100%;">
                        <option value="pix">PIX (Transferência Instantânea)</option>
                        <option value="dinheiro">Dinheiro (Em Espécie)</option>
                        <option value="transferencia">Transferência Bancária / TED</option>
                    </select>
                </div>

                <div class="cp-field">
                    <label>Observações / Comprovante (Opcional)</label>
                    <input type="text" wire:model="payoutNotes" placeholder="Ex: Pago via chave PIX do barbeiro" class="cp-input" style="width: 100%;" />
                </div>

                <div class="cp-modal-actions">
                    <button type="button" wire:click="$set('payoutModalOpen', false)" class="cp-btn-cancel">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmPayout" class="cp-btn-confirm">
                        Confirmar Pagamento e Liquidar Recibo ✓
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL / CANHOTO DE IMPRESSÃO DO RECIBO DE COMISSÃO --}}
    @if ($printPayoutModalOpen && $selectedPayoutToPrint)
        <div class="cp-modal-mask no-print">
            <div class="cp-modal-window" style="max-width: 520px;">
                <div class="cp-modal-head">
                    <h3>📑 Recibo de Pagamento de Comissão</h3>
                    <button type="button" wire:click="$set('printPayoutModalOpen', false)" class="cp-modal-x">&times;</button>
                </div>

                {{-- PRÉ-VISUALIZAÇÃO DO RECIBO COM CANHOTO --}}
                <div id="payout-receipt" style="background: #ffffff; color: #000000; padding: 22px; border-radius: 12px; font-family: monospace; font-size: 12px; line-height: 1.4;">
                    <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 12px;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 900; text-transform: uppercase;">{{ $selectedPayoutToPrint->barbershop?->name ?? 'AUROBARBER' }}</h3>
                        <span style="font-size: 11px; font-weight: bold; display: block; margin-top: 2px;">RECIBO DE PAGAMENTO DE COMISSÃO</span>
                        <span style="font-size: 10px; color: #333; display: block;">Recibo Nº #{{ str_pad($selectedPayoutToPrint->id, 5, '0', STR_PAD_LEFT) }} • Emitido em {{ $selectedPayoutToPrint->paid_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div style="margin-bottom: 12px; border-bottom: 1px dashed #000; padding-bottom: 8px;">
                        <div><strong>PROFISSIONAL:</strong> {{ $selectedPayoutToPrint->professional?->name }} ({{ $selectedPayoutToPrint->professional?->display_name }})</div>
                        <div><strong>PERÍODO:</strong> {{ $selectedPayoutToPrint->period_start->format('d/m/Y') }} a {{ $selectedPayoutToPrint->period_end->format('d/m/Y') }}</div>
                        <div><strong>MÉTODO DE PAGAMENTO:</strong> {{ $selectedPayoutToPrint->payment_method_label }}</div>
                    </div>

                    <div style="margin-bottom: 12px; border-bottom: 1px dashed #000; padding-bottom: 8px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>SERVIÇOS / CORTES ({{ $selectedPayoutToPrint->appointments_count }} atendimentos):</span>
                            <span>R$ {{ number_format($selectedPayoutToPrint->services_gross ?: $selectedPayoutToPrint->total_gross, 2, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: #333; font-size: 11px;">
                            <span>» Comissão sobre Serviços:</span>
                            <span>R$ {{ number_format($selectedPayoutToPrint->services_commission ?: $selectedPayoutToPrint->total_commission, 2, ',', '.') }}</span>
                        </div>

                        @if ($selectedPayoutToPrint->products_gross > 0)
                            <div style="display: flex; justify-content: space-between; margin-top: 6px;">
                                <span>PRODUTOS VENDIDOS ({{ $selectedPayoutToPrint->products_count }} itens):</span>
                                <span>R$ {{ number_format($selectedPayoutToPrint->products_gross, 2, ',', '.') }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; color: #333; font-size: 11px;">
                                <span>» Comissão sobre Produtos:</span>
                                <span>R$ {{ number_format($selectedPayoutToPrint->products_commission, 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <div style="display: flex; justify-content: space-between; font-weight: bold; margin-top: 8px; border-top: 1px dashed #ccc; padding-top: 4px;">
                            <span>TOTAL PRODUÇÃO BRUTA:</span>
                            <span>R$ {{ number_format($selectedPayoutToPrint->total_gross, 2, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 900; margin-top: 4px; border-top: 1px solid #000; padding-top: 4px;">
                            <span>VALOR LÍQUIDO PAGO AO BARBEIRO:</span>
                            <span style="font-size: 16px;">R$ {{ number_format($selectedPayoutToPrint->total_commission, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    @if ($selectedPayoutToPrint->notes)
                        <div style="margin-bottom: 10px; font-size: 11px; color: #444;">
                            <em>Obs: {{ $selectedPayoutToPrint->notes }}</em>
                        </div>
                    @endif

                    {{-- CANHOTO JURÍDICO DE QUITAÇÃO --}}
                    <div style="margin-top: 20px; border: 1px solid #000; padding: 10px; border-radius: 6px; font-size: 10px; text-align: justify;">
                        <p style="margin: 0;">
                            Declaro que recebi da barbearia acima a quantia líquida de <strong>R$ {{ number_format($selectedPayoutToPrint->total_commission, 2, ',', '.') }}</strong>, referente ao repasse de comissões por serviços prestados e produtos comercializados no período discriminado, dando plena e total quitação.
                        </p>
                        <div style="margin-top: 28px; text-align: center; border-top: 1px solid #000; padding-top: 4px;">
                            Assinatura do Profissional: <strong>{{ $selectedPayoutToPrint->professional?->display_name }}</strong>
                        </div>
                    </div>
                </div>

                <div class="cp-modal-actions no-print">
                    <button type="button" wire:click="$set('printPayoutModalOpen', false)" class="cp-btn-cancel">
                        Fechar
                    </button>
                    <button type="button" onclick="window.print()" class="cp-btn-confirm">
                        Imprimir Recibo 🖨️
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
