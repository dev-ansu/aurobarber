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

    <div class="space-y-6">
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- COLUNA ESQUERDA: CATÁLOGO DE PRODUTOS --}}
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:col-span-7">
                <div class="mb-4">
                    <input
                        type="search"
                        wire:model.live.debounce.250ms="search"
                        placeholder="Buscar produto por nome (ex: Pomada, Cerveja, Água)..."
                        class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3.5 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                    />
                </div>

                <div class="mb-5 flex flex-wrap gap-2">
                    @foreach ($categories as $key => $label)
                        <x-filament::button
                            size="xs"
                            :color="$selectedCategory === $key ? 'primary' : 'gray'"
                            wire:click="$set('selectedCategory', '{{ $key }}')"
                        >
                            {{ $label }}
                        </x-filament::button>
                    @endforeach
                </div>

                @if ($products->isEmpty())
                    <div class="px-4 py-12 text-center">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Nenhum produto disponível encontrado.</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Verifique o filtro selecionado ou se há produtos ativos com estoque positivo.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $product)
                            @php
                                $inCartQty = (int) ($cart[$product->id] ?? 0);
                            @endphp
                            <button
                                type="button"
                                wire:key="pos-product-{{ $product->id }}"
                                wire:click="addToCart({{ $product->id }})"
                                @class([
                                    'relative flex min-h-28 flex-col justify-between gap-3 rounded-xl border p-3.5 text-left transition hover:-translate-y-0.5 hover:shadow-sm',
                                    'border-primary-500 bg-primary-50/70 ring-1 ring-primary-500/30 dark:border-primary-400 dark:bg-primary-500/10' => $inCartQty > 0,
                                    'border-gray-200 bg-gray-50/70 hover:border-primary-400 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500/50' => $inCartQty === 0,
                                ])
                            >
                                @if ($inCartQty > 0)
                                    <span class="absolute right-2.5 top-2.5 inline-flex items-center rounded-full bg-primary-600 px-2 py-0.5 text-[11px] font-bold text-white dark:bg-primary-500 dark:text-gray-950">
                                        {{ $inCartQty }}x
                                    </span>
                                @endif

                                <div class="pr-8">
                                    <div class="text-sm font-bold text-gray-950 dark:text-white">{{ $product->name }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $categories[$product->category] ?? '📦 Outros' }}
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                        R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                                    </span>
                                    <x-filament::badge :color="$product->isLowStock() ? 'danger' : 'success'" size="sm">
                                        {{ $product->stock_quantity }} un.
                                    </x-filament::badge>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- COLUNA DIREITA: CARRINHO E PAGAMENTO --}}
            <div class="flex flex-col gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:sticky lg:top-20 lg:col-span-5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">Carrinho da Venda</h3>
                        @if ($cartCount > 0)
                            <x-filament::badge color="primary" size="sm">
                                {{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'itens' }}
                            </x-filament::badge>
                        @endif
                    </div>

                    @if ($cartCount > 0)
                        <button
                            type="button"
                            wire:click="clearCart"
                            class="text-xs font-semibold text-danger-600 transition hover:text-danger-500 dark:text-danger-400"
                        >
                            Limpar carrinho
                        </button>
                    @endif
                </div>

                @if ($cartItems->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-7 text-center text-sm text-gray-500 dark:border-white/15 dark:bg-white/5 dark:text-gray-400">
                        Clique nos produtos ao lado para adicionar à venda.
                    </div>
                @else
                    <div class="max-h-72 space-y-2.5 overflow-y-auto pr-1">
                        @foreach ($cartItems as $item)
                            @php
                                /** @var \App\Models\Product $p */
                                $p = $item['product'];
                            @endphp
                            <div
                                wire:key="cart-item-{{ $p->id }}"
                                class="flex items-center gap-2.5 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $p->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        R$ {{ number_format($item['unit_price'], 2, ',', '.') }} / un. · Estoque: {{ $p->stock_quantity }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        wire:click="decrementCart({{ $p->id }})"
                                        title="Diminuir"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-300 bg-white text-sm font-bold text-gray-700 transition hover:bg-gray-100 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        −
                                    </button>
                                    <input
                                        type="number"
                                        min="1"
                                        max="{{ $p->stock_quantity }}"
                                        value="{{ $item['quantity'] }}"
                                        wire:change="setQuantity({{ $p->id }}, $event.target.value)"
                                        class="h-7 w-12 rounded-lg border border-gray-300 bg-white text-center text-xs font-bold text-gray-950 outline-none focus:border-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                                    />
                                    <button
                                        type="button"
                                        wire:click="addToCart({{ $p->id }})"
                                        title="Aumentar"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-300 bg-white text-sm font-bold text-gray-700 transition hover:bg-gray-100 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        +
                                    </button>
                                </div>

                                <div class="min-w-20 text-right">
                                    <div class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                        R$ {{ number_format($item['subtotal'], 2, ',', '.') }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="removeFromCart({{ $p->id }})"
                                    title="Remover produto"
                                    class="rounded-md p-1 text-base leading-none text-gray-400 transition hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10 dark:hover:text-danger-400"
                                >
                                    ×
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- CLIENTE E VENDEDOR --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">Cliente (opcional)</label>
                        <input
                            type="text"
                            wire:model="clientName"
                            placeholder="Ex: João Silva"
                            maxlength="255"
                            class="block h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">Barbeiro / Vendedor (comissão)</label>
                        <select
                            wire:model="professionalId"
                            @disabled($isRestrictedBarber)
                            class="block h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="">Balcão (sem comissão)</option>
                            @foreach ($professionals as $pro)
                                <option value="{{ $pro->id }}">{{ $pro->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- TOTAL DA VENDA --}}
                <div class="flex items-center justify-between rounded-xl border border-primary-500/30 bg-primary-50 px-4 py-3 dark:bg-primary-500/10">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-primary-800 dark:text-primary-300">
                            Total da Venda
                        </div>
                        <div class="text-xs text-primary-700/80 dark:text-primary-200/70">
                            {{ $cartCount }} {{ $cartCount === 1 ? 'unidade' : 'unidades' }}
                        </div>
                    </div>
                    <div class="text-2xl font-black text-primary-700 dark:text-primary-400">
                        R$ {{ number_format($cartTotal, 2, ',', '.') }}
                    </div>
                </div>

                {{-- PAGAMENTO --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Forma de Pagamento</span>
                        @if (! $splitPayment)
                            <x-filament::button size="xs" color="gray" wire:click="enableSplitPayment">
                                + Dividir pagamento
                            </x-filament::button>
                        @else
                            <button
                                type="button"
                                wire:click="disableSplitPayment"
                                class="text-xs font-semibold text-primary-600 transition hover:text-primary-500 dark:text-primary-400"
                            >
                                Usar forma única
                            </button>
                        @endif
                    </div>

                    @if (! $splitPayment)
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($paymentLabels as $methodKey => $methodLabel)
                                <x-filament::button
                                    size="sm"
                                    :color="$singlePaymentMethod === $methodKey ? 'primary' : 'gray'"
                                    wire:click="selectSinglePaymentMethod('{{ $methodKey }}')"
                                >
                                    {{ $methodLabel }}
                                </x-filament::button>
                            @endforeach
                        </div>

                        @if ($singlePaymentMethod === 'dinheiro' && $cartTotal > 0)
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Valor recebido em dinheiro (opcional — para troco)
                                </label>
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    wire:model.live.debounce.300ms="cashReceived"
                                    placeholder="Ex: {{ number_format(ceil($cartTotal / 10) * 10, 2, ',', '.') }}"
                                    class="block h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                                />
                                @if ($paymentSummary['change'] > 0)
                                    <div class="mt-1.5 text-sm font-bold text-success-600 dark:text-success-400">
                                        Troco a devolver: R$ {{ number_format($paymentSummary['change'], 2, ',', '.') }}
                                    </div>
                                @elseif ($paymentSummary['error'])
                                    <div class="mt-1.5 text-xs font-semibold text-danger-600 dark:text-danger-400">
                                        {{ $paymentSummary['error'] }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    @else
                        <div class="space-y-2">
                            @foreach ($payments as $index => $row)
                                <div class="flex items-center gap-2" wire:key="payment-row-{{ $index }}">
                                    <select
                                        wire:model.live="payments.{{ $index }}.method"
                                        class="h-9 flex-1 rounded-lg border border-gray-300 bg-white px-2.5 text-sm text-gray-950 shadow-sm outline-none focus:border-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                                    >
                                        @foreach ($paymentLabels as $methodKey => $methodLabel)
                                            <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                                        @endforeach
                                    </select>

                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        wire:model.live.debounce.300ms="payments.{{ $index }}.amount"
                                        placeholder="0,00"
                                        class="h-9 w-28 rounded-lg border border-gray-300 bg-white px-2.5 text-sm text-gray-950 shadow-sm outline-none focus:border-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                    />

                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        wire:click="fillRemaining({{ $index }})"
                                        title="Preencher com o saldo restante"
                                    >
                                        Restante
                                    </x-filament::button>

                                    <button
                                        type="button"
                                        wire:click="removePaymentRow({{ $index }})"
                                        title="Remover forma"
                                        class="rounded-md p-1 text-base leading-none text-gray-400 transition hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10 dark:hover:text-danger-400"
                                    >
                                        ×
                                    </button>
                                </div>
                            @endforeach

                            <x-filament::button
                                size="xs"
                                color="gray"
                                class="w-full"
                                wire:click="addPaymentRow"
                            >
                                + Adicionar outra forma de pagamento
                            </x-filament::button>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">
                            <span>Informado: <strong class="font-bold text-gray-950 dark:text-white">R$ {{ number_format($paymentSummary['paid'], 2, ',', '.') }}</strong></span>

                            @if ($paymentSummary['remaining'] > 0)
                                <span class="font-semibold text-warning-600 dark:text-warning-400">
                                    Falta pagar: R$ {{ number_format($paymentSummary['remaining'], 2, ',', '.') }}
                                </span>
                            @elseif ($paymentSummary['change'] > 0)
                                <span class="font-semibold text-success-600 dark:text-success-400">
                                    Troco: R$ {{ number_format($paymentSummary['change'], 2, ',', '.') }}
                                </span>
                            @elseif ($paymentSummary['is_valid'])
                                <span class="font-semibold text-success-600 dark:text-success-400">
                                    Pagamento fechado ✓
                                </span>
                            @endif
                        </div>

                        @if ($paymentSummary['error'])
                            <div class="text-xs font-semibold text-danger-600 dark:text-danger-400">
                                {{ $paymentSummary['error'] }}
                            </div>
                        @endif
                    @endif
                </div>

                {{-- BOTÃO FINALIZAR --}}
                <x-filament::button
                    color="primary"
                    size="lg"
                    class="w-full"
                    wire:click="finalizeSale"
                    wire:loading.attr="disabled"
                    :disabled="$cartItems->isEmpty() || ! $paymentSummary['is_valid']"
                >
                    <span wire:loading.remove wire:target="finalizeSale">
                        Finalizar Venda · R$ {{ number_format($cartTotal, 2, ',', '.') }}
                    </span>
                    <span wire:loading wire:target="finalizeSale">
                        Registrando venda...
                    </span>
                </x-filament::button>
            </div>
        </div>

        {{-- VENDAS RECENTES DE HOJE --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-white/10">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">Vendas de Balcão Hoje</h3>
                    <x-filament::badge color="primary" size="sm">{{ $todaySales->count() }}</x-filament::badge>
                </div>
                <x-filament::button size="xs" color="gray" wire:click="$toggle('showRecentSales')">
                    {{ $showRecentSales ? 'Ocultar histórico' : 'Mostrar histórico' }}
                </x-filament::button>
            </div>

            @if ($showRecentSales)
                @if ($todaySales->isEmpty())
                    <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">
                        Nenhuma venda avulsa registrada hoje ainda.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Hora</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Itens</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente / Vendedor</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Pagamento</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($todaySales as $group)
                                    @php
                                        $first = $group->first();
                                        $groupTotal = (float) $group->sum('total_price');
                                        $details = is_array($first->payment_details) && $first->payment_details !== []
                                            ? collect($first->payment_details)->map(fn ($p): string => ($paymentLabels[$p['method'] ?? ''] ?? ucfirst((string) ($p['method'] ?? ''))) . ': R$ ' . number_format((float) ($p['amount'] ?? 0), 2, ',', '.'))->implode(' + ')
                                            : $first->payment_method_label;
                                    @endphp
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $first->created_at?->format('H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                            {{ $group->map(fn ($line): string => "{$line->quantity}x " . ($line->product?->name ?? 'Produto'))->implode(', ') }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-950 dark:text-white">
                                            {{ $first->client_name ?: 'Balcão' }}
                                            @if ($first->professional)
                                                <span class="text-gray-500 dark:text-gray-400">· {{ $first->professional->display_name }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $details }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-primary-600 dark:text-primary-400">
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
