<x-filament-panels::page>
    @php
        $products = $this->products;
        $cartItems = $this->cartItems;
        $cartTotal = $this->cartTotal;
        $cartCount = $this->cartCount;
        $professionals = $this->professionals;
        $paymentSummary = $this->paymentSummary;
        $todaySales = $this->todaySales;
        $isRestrictedBarber = auth()->user()?->isRestrictedBarber() ?? false;

        $categories = [
            'all' => 'Todos',
            'bar' => '🍺 Bar & Bebidas',
            'cosmetico' => '💈 Cosméticos',
            'insumo' => '🪒 Insumos',
            'outros' => '📦 Outros',
        ];

        $paymentLabels = [
            'pix' => 'PIX',
            'dinheiro' => 'Dinheiro',
            'debito' => 'Débito',
            'credito' => 'Crédito',
        ];
    @endphp

    <style>
        .pos-wrap {
            display: flex;
            flex-direction: column;
            gap: 20px;
            color: #f1f5f9;
            font-family: inherit;
        }

        .pos-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(340px, 1fr);
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .pos-grid {
                grid-template-columns: 1fr;
            }
        }

        .pos-panel {
            background: linear-gradient(180deg, #0f172a 0%, #0b1120 100%);
            border: 1px solid #1e293b;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .pos-sticky {
            position: sticky;
            top: 84px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .pos-search-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 16px;
        }

        .pos-input {
            width: 100%;
            height: 42px;
            background: #020617;
            border: 1px solid #334155;
            border-radius: 12px;
            color: #ffffff;
            padding: 0 14px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .pos-input:focus {
            border-color: #d97706;
        }

        .pos-input:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .pos-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .pos-pill {
            height: 34px;
            padding: 0 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #334155;
            background: #1e293b;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .pos-pill:hover {
            background: #334155;
            color: #ffffff;
        }

        .pos-pill.active {
            background: rgba(217, 119, 6, 0.18);
            border-color: #d97706;
            color: #fbbf24;
        }

        .pos-catalog {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
            gap: 12px;
        }

        .pos-card {
            position: relative;
            text-align: left;
            background: #0b1222;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 10px;
            min-height: 118px;
        }

        .pos-card:hover {
            border-color: #d97706;
            background: #111c33;
            transform: translateY(-1px);
        }

        .pos-card.in-cart {
            border-color: #f59e0b;
            background: rgba(217, 119, 6, 0.10);
            box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.35);
        }

        .pos-card-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #f59e0b;
            color: #020617;
            font-size: 11px;
            font-weight: 900;
            padding: 2px 8px;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.45);
        }

        .pos-card-name {
            font-size: 14px;
            font-weight: 800;
            color: #f8fafc;
            line-height: 1.3;
        }

        .pos-card-footer {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
        }

        .pos-card-price {
            font-size: 16px;
            font-weight: 900;
            color: #fbbf24;
        }

        .pos-stock {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
        }

        .pos-stock.ok {
            background: rgba(16, 185, 129, 0.14);
            color: #34d399;
        }

        .pos-stock.low {
            background: rgba(245, 158, 11, 0.18);
            color: #fbbf24;
        }

        .pos-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .pos-section-title {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pos-count-badge {
            background: rgba(245, 158, 11, 0.18);
            color: #fbbf24;
            font-size: 12px;
            font-weight: 800;
            padding: 2px 9px;
            border-radius: 9999px;
        }

        .pos-link-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
        }

        .pos-link-btn:hover {
            color: #f87171;
            background: rgba(248, 113, 113, 0.1);
        }

        .pos-cart-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 290px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .pos-cart-item {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 14px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .pos-stepper {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 2px;
        }

        .pos-step-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            border: none;
            background: #1e293b;
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .pos-step-btn:hover {
            background: #d97706;
            color: #020617;
        }

        .pos-qty-input {
            width: 42px;
            height: 28px;
            text-align: center;
            background: transparent;
            border: none;
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            outline: none;
        }

        .pos-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        @media (max-width: 640px) {
            .pos-meta-grid {
                grid-template-columns: 1fr;
            }
        }

        .pos-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }

        .pos-total-box {
            background: linear-gradient(135deg, rgba(217, 119, 6, 0.16) 0%, rgba(15, 23, 42, 0.95) 100%);
            border: 1px solid rgba(245, 158, 11, 0.45);
            border-radius: 16px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .pos-total-value {
            font-size: 26px;
            font-weight: 900;
            color: #fbbf24;
            letter-spacing: -0.02em;
        }

        .pos-pay-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .pos-pay-btn {
            height: 40px;
            border-radius: 12px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .pos-pay-btn:hover {
            background: #334155;
            color: #ffffff;
        }

        .pos-pay-btn.active {
            background: rgba(16, 185, 129, 0.18);
            border-color: #10b981;
            color: #34d399;
        }

        .pos-split-row {
            display: grid;
            grid-template-columns: 1.15fr 1fr auto auto;
            gap: 8px;
            align-items: center;
        }

        .pos-mini-btn {
            height: 38px;
            padding: 0 10px;
            border-radius: 10px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #fbbf24;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }

        .pos-mini-btn:hover {
            background: #334155;
        }

        .pos-summary-bar {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 10px 12px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .pos-submit-btn {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 8px 20px -6px rgba(16, 185, 129, 0.55);
        }

        .pos-submit-btn:hover:not(:disabled) {
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        .pos-submit-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            box-shadow: none;
        }

        .pos-recent-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 12px;
        }

        .pos-recent-table th {
            text-align: left;
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
            padding: 8px 10px;
            border-bottom: 1px solid #1e293b;
        }

        .pos-recent-table td {
            padding: 10px;
            border-bottom: 1px solid #1e293b;
            color: #e2e8f0;
        }
    </style>

    <div class="pos-wrap">
        <div class="pos-grid">
            {{-- COLUNA ESQUERDA: CATÁLOGO DE PRODUTOS --}}
            <div class="pos-panel">
                <div class="pos-search-bar">
                    <input
                        type="search"
                        wire:model.live.debounce.250ms="search"
                        class="pos-input"
                        placeholder="Buscar produto por nome (ex: Pomada, Cerveja, Água)..."
                    />
                </div>

                <div class="pos-pills">
                    @foreach ($categories as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('selectedCategory', '{{ $key }}')"
                            class="pos-pill {{ $selectedCategory === $key ? 'active' : '' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                @if ($products->isEmpty())
                    <div style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                        <p style="font-size: 15px; font-weight: 700; color: #cbd5e1;">Nenhum produto disponível encontrado.</p>
                        <p style="font-size: 13px; margin-top: 4px;">Verifique o filtro selecionado ou se há produtos ativos com estoque positivo.</p>
                    </div>
                @else
                    <div class="pos-catalog">
                        @foreach ($products as $product)
                            @php
                                $inCartQty = (int) ($cart[$product->id] ?? 0);
                            @endphp
                            <button
                                type="button"
                                wire:key="pos-product-{{ $product->id }}"
                                wire:click="addToCart({{ $product->id }})"
                                class="pos-card {{ $inCartQty > 0 ? 'in-cart' : '' }}"
                            >
                                @if ($inCartQty > 0)
                                    <span class="pos-card-badge">{{ $inCartQty }}x</span>
                                @endif

                                <div>
                                    <div class="pos-card-name">{{ $product->name }}</div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                                        {{ $categories[$product->category] ?? '📦 Outros' }}
                                    </div>
                                </div>

                                <div class="pos-card-footer">
                                    <span class="pos-card-price">
                                        R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                                    </span>
                                    <span class="pos-stock {{ $product->isLowStock() ? 'low' : 'ok' }}">
                                        {{ $product->stock_quantity }} un.
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- COLUNA DIREITA: CARRINHO E PAGAMENTO --}}
            <div class="pos-panel pos-sticky">
                <div class="pos-section-head">
                    <div class="pos-section-title">
                        <span>Carrinho da Venda</span>
                        @if ($cartCount > 0)
                            <span class="pos-count-badge">{{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'itens' }}</span>
                        @endif
                    </div>

                    @if ($cartCount > 0)
                        <button type="button" wire:click="clearCart" class="pos-link-btn">
                            Limpar carrinho
                        </button>
                    @endif
                </div>

                @if ($cartItems->isEmpty())
                    <div style="background: #020617; border: 1px dashed #334155; border-radius: 14px; padding: 28px 16px; text-align: center; color: #94a3b8; font-size: 13px;">
                        Clique nos produtos ao lado para adicionar à venda.
                    </div>
                @else
                    <div class="pos-cart-list">
                        @foreach ($cartItems as $item)
                            @php
                                /** @var \App\Models\Product $p */
                                $p = $item['product'];
                            @endphp
                            <div class="pos-cart-item" wire:key="cart-item-{{ $p->id }}">
                                <div style="min-width: 0; flex: 1;">
                                    <div style="font-size: 13px; font-weight: 800; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $p->name }}
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8;">
                                        R$ {{ number_format($item['unit_price'], 2, ',', '.') }} / un. · Estoque: {{ $p->stock_quantity }}
                                    </div>
                                </div>

                                <div class="pos-stepper">
                                    <button type="button" wire:click="decrementCart({{ $p->id }})" class="pos-step-btn" title="Diminuir">−</button>
                                    <input
                                        type="number"
                                        min="1"
                                        max="{{ $p->stock_quantity }}"
                                        value="{{ $item['quantity'] }}"
                                        wire:change="setQuantity({{ $p->id }}, $event.target.value)"
                                        class="pos-qty-input"
                                    />
                                    <button type="button" wire:click="addToCart({{ $p->id }})" class="pos-step-btn" title="Aumentar">+</button>
                                </div>

                                <div style="text-align: right; min-width: 78px;">
                                    <div style="font-size: 13px; font-weight: 900; color: #fbbf24;">
                                        R$ {{ number_format($item['subtotal'], 2, ',', '.') }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="removeFromCart({{ $p->id }})"
                                    class="pos-link-btn"
                                    title="Remover produto"
                                    style="font-size: 16px; line-height: 1;"
                                >
                                    ×
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- CLIENTE E VENDEDOR --}}
                <div class="pos-meta-grid">
                    <div>
                        <label class="pos-label">Cliente (opcional)</label>
                        <input
                            type="text"
                            wire:model="clientName"
                            class="pos-input"
                            placeholder="Ex: João Silva"
                            maxlength="255"
                        />
                    </div>
                    <div>
                        <label class="pos-label">Barbeiro / Vendedor (comissão)</label>
                        <select
                            wire:model="professionalId"
                            class="pos-input"
                            @disabled($isRestrictedBarber)
                        >
                            <option value="">Balcão (sem comissão)</option>
                            @foreach ($professionals as $pro)
                                <option value="{{ $pro->id }}">{{ $pro->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- TOTAL DA VENDA --}}
                <div class="pos-total-box">
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.06em;">
                            Total da Venda
                        </div>
                        <div style="font-size: 12px; color: #94a3b8;">
                            {{ $cartCount }} {{ $cartCount === 1 ? 'unidade' : 'unidades' }}
                        </div>
                    </div>
                    <div class="pos-total-value">
                        R$ {{ number_format($cartTotal, 2, ',', '.') }}
                    </div>
                </div>

                {{-- PAGAMENTO --}}
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div class="pos-section-head">
                        <span class="pos-label" style="margin: 0;">Forma de Pagamento</span>
                        @if (! $splitPayment)
                            <button type="button" wire:click="enableSplitPayment" class="pos-mini-btn">
                                + Dividir pagamento
                            </button>
                        @else
                            <button type="button" wire:click="disableSplitPayment" class="pos-link-btn" style="color: #fbbf24;">
                                Usar forma única
                            </button>
                        @endif
                    </div>

                    @if (! $splitPayment)
                        <div class="pos-pay-methods">
                            @foreach ($paymentLabels as $methodKey => $methodLabel)
                                <button
                                    type="button"
                                    wire:click="selectSinglePaymentMethod('{{ $methodKey }}')"
                                    class="pos-pay-btn {{ $singlePaymentMethod === $methodKey ? 'active' : '' }}"
                                >
                                    {{ $methodLabel }}
                                </button>
                            @endforeach
                        </div>

                        @if ($singlePaymentMethod === 'dinheiro' && $cartTotal > 0)
                            <div>
                                <label class="pos-label">Valor recebido em dinheiro (opcional — para troco)</label>
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    wire:model.live.debounce.300ms="cashReceived"
                                    class="pos-input"
                                    placeholder="Ex: {{ number_format(ceil($cartTotal / 10) * 10, 2, ',', '.') }}"
                                />
                                @if ($paymentSummary['change'] > 0)
                                    <div style="margin-top: 6px; font-size: 13px; font-weight: 800; color: #34d399;">
                                        Troco a devolver: R$ {{ number_format($paymentSummary['change'], 2, ',', '.') }}
                                    </div>
                                @elseif ($paymentSummary['error'])
                                    <div style="margin-top: 6px; font-size: 12px; font-weight: 700; color: #f87171;">
                                        {{ $paymentSummary['error'] }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    @else
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            @foreach ($payments as $index => $row)
                                <div class="pos-split-row" wire:key="payment-row-{{ $index }}">
                                    <select wire:model.live="payments.{{ $index }}.method" class="pos-input" style="height: 38px; font-size: 13px;">
                                        @foreach ($paymentLabels as $methodKey => $methodLabel)
                                            <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                                        @endforeach
                                    </select>

                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        wire:model.live.debounce.300ms="payments.{{ $index }}.amount"
                                        class="pos-input"
                                        style="height: 38px; font-size: 13px;"
                                        placeholder="0,00"
                                    />

                                    <button
                                        type="button"
                                        wire:click="fillRemaining({{ $index }})"
                                        class="pos-mini-btn"
                                        title="Preencher com o saldo restante"
                                    >
                                        Restante
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="removePaymentRow({{ $index }})"
                                        class="pos-link-btn"
                                        title="Remover forma"
                                        style="font-size: 16px;"
                                    >
                                        ×
                                    </button>
                                </div>
                            @endforeach

                            <button
                                type="button"
                                wire:click="addPaymentRow"
                                class="pos-mini-btn"
                                style="width: 100%; height: 34px; color: #cbd5e1;"
                            >
                                + Adicionar outra forma de pagamento
                            </button>
                        </div>

                        <div class="pos-summary-bar">
                            <span>Informado: <strong style="color: #ffffff;">R$ {{ number_format($paymentSummary['paid'], 2, ',', '.') }}</strong></span>

                            @if ($paymentSummary['remaining'] > 0)
                                <span style="color: #fbbf24;">
                                    Falta pagar: R$ {{ number_format($paymentSummary['remaining'], 2, ',', '.') }}
                                </span>
                            @elseif ($paymentSummary['change'] > 0)
                                <span style="color: #34d399;">
                                    Troco: R$ {{ number_format($paymentSummary['change'], 2, ',', '.') }}
                                </span>
                            @elseif ($paymentSummary['is_valid'])
                                <span style="color: #34d399;">
                                    Pagamento fechado ✓
                                </span>
                            @endif
                        </div>

                        @if ($paymentSummary['error'])
                            <div style="font-size: 12px; font-weight: 700; color: #f87171;">
                                {{ $paymentSummary['error'] }}
                            </div>
                        @endif
                    @endif
                </div>

                {{-- BOTÃO FINALIZAR --}}
                <button
                    type="button"
                    wire:click="finalizeSale"
                    wire:loading.attr="disabled"
                    @disabled($cartItems->isEmpty() || ! $paymentSummary['is_valid'])
                    class="pos-submit-btn"
                >
                    <span wire:loading.remove wire:target="finalizeSale">
                        Finalizar Venda · R$ {{ number_format($cartTotal, 2, ',', '.') }}
                    </span>
                    <span wire:loading wire:target="finalizeSale">
                        Registrando venda...
                    </span>
                </button>
            </div>
        </div>

        {{-- VENDAS RECENTES DE HOJE --}}
        <div class="pos-panel">
            <div class="pos-section-head">
                <div class="pos-section-title">
                    <span>Vendas de Balcão Hoje</span>
                    <span class="pos-count-badge">{{ $todaySales->count() }}</span>
                </div>
                <button type="button" wire:click="$toggle('showRecentSales')" class="pos-mini-btn">
                    {{ $showRecentSales ? 'Ocultar histórico' : 'Mostrar histórico' }}
                </button>
            </div>

            @if ($showRecentSales)
                @if ($todaySales->isEmpty())
                    <p style="margin-top: 12px; font-size: 13px; color: #94a3b8;">
                        Nenhuma venda avulsa registrada hoje ainda.
                    </p>
                @else
                    <div style="overflow-x: auto;">
                        <table class="pos-recent-table">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Itens</th>
                                    <th>Cliente / Vendedor</th>
                                    <th>Pagamento</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($todaySales as $group)
                                    @php
                                        $first = $group->first();
                                        $groupTotal = (float) $group->sum('total_price');
                                        $details = is_array($first->payment_details) && $first->payment_details !== []
                                            ? collect($first->payment_details)->map(fn ($p): string => ($paymentLabels[$p['method'] ?? ''] ?? ucfirst((string) ($p['method'] ?? ''))) . ': R$ ' . number_format((float) ($p['amount'] ?? 0), 2, ',', '.'))->implode(' + ')
                                            : $first->payment_method_label;
                                    @endphp
                                    <tr>
                                        <td>{{ $first->created_at?->format('H:i') }}</td>
                                        <td>
                                            {{ $group->map(fn ($line): string => "{$line->quantity}x " . ($line->product?->name ?? 'Produto'))->implode(', ') }}
                                        </td>
                                        <td>
                                            {{ $first->client_name ?: 'Balcão' }}
                                            @if ($first->professional)
                                                <span style="color: #94a3b8;">· {{ $first->professional->display_name }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $details }}</td>
                                        <td style="text-align: right; font-weight: 800; color: #fbbf24;">
                                            R$ {{ number_format($groupTotal, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
