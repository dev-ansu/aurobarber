<x-filament-panels::page>
    @php
        $carbonDate = \Carbon\Carbon::parse($selectedDate)->locale('pt_BR');
        $dateFormatted = $carbonDate->translatedFormat('l, d \d\e F \d\e Y');
        $isToday = $selectedDate === now()->format('Y-m-d');
        $isYesterday = $selectedDate === now()->subDay()->format('Y-m-d');
        $stats = $this->cashStats;
        $txs = $this->dailyTransactions;
        $productSales = $this->dailyProductSales->groupBy(fn (\App\Models\ProductSale $sale): string => $sale->sale_group_id ?? 'single-'.$sale->id);
        $movs = $this->dailyMovements;
        $tenant = filament()->getTenant();
    @endphp

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .cr-print-receipt,
            .cr-print-receipt * {
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

    <div class="space-y-6">
        {{-- TOOLBAR --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2.5">
                    <x-filament::button
                        size="sm"
                        :color="$isToday ? 'primary' : 'gray'"
                        wire:click="setToday"
                    >
                        Hoje
                    </x-filament::button>
                    <x-filament::button
                        size="sm"
                        :color="$isYesterday ? 'primary' : 'gray'"
                        wire:click="setYesterday"
                    >
                        Ontem
                    </x-filament::button>

                    <input
                        type="date"
                        wire:model.live="selectedDate"
                        aria-label="Selecionar data do caixa"
                        class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />

                    <span class="text-sm font-semibold capitalize text-gray-700 dark:text-gray-200">
                        📅 {{ $dateFormatted }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button
                        size="sm"
                        color="gray"
                        wire:click="mountAction('registerProductSale')"
                    >
                        🛍️ Venda de produtos
                    </x-filament::button>

                    <x-filament::button
                        size="sm"
                        color="gray"
                        wire:click="mountAction('registerMovement', @js(['type' => 'outflow']))"
                    >
                        💸 Sangria / Suprimento
                    </x-filament::button>

                    <x-filament::button
                        size="sm"
                        color="primary"
                        icon="heroicon-o-printer"
                        wire:click="$set('printModalOpen', true)"
                    >
                        Imprimir Fechamento
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- 5 CARDS DE DISTRIBUIÇÃO DE FORMAS DE PAGAMENTO --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">📱 PIX</p>
                <p class="mt-1.5 text-xl font-bold text-info-600 dark:text-info-400">R$ {{ number_format($stats['pix'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Transferência direta</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">💵 Dinheiro (Vendas)</p>
                <p class="mt-1.5 text-xl font-bold text-success-600 dark:text-success-400">R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Recebido nas vendas</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">💳 Cartão Débito</p>
                <p class="mt-1.5 text-xl font-bold text-purple-600 dark:text-purple-400">R$ {{ number_format($stats['debito'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maquininha</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">💳 Cartão Crédito</p>
                <p class="mt-1.5 text-xl font-bold text-warning-600 dark:text-warning-400">R$ {{ number_format($stats['credito'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maquininha</p>
            </div>

            <div class="col-span-2 rounded-xl bg-primary-50 p-4 shadow-sm ring-1 ring-primary-600/20 dark:bg-primary-500/10 dark:ring-primary-400/30 lg:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-primary-700 dark:text-primary-400">🌟 Faturamento Total</p>
                <p class="mt-1.5 text-2xl font-bold text-gray-950 dark:text-white">R$ {{ number_format($stats['total'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-primary-700 dark:text-primary-300">Fechamento do dia</p>
            </div>
        </div>

        {{-- AUDITORIA DA GAVETA DE DINHEIRO FÍSICO --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-white px-5 py-3.5 text-sm shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-1.5">
                <span class="text-gray-500 dark:text-gray-400">Fundo Inicial:</span>
                <strong class="font-bold text-gray-950 dark:text-white">R$ {{ number_format($stats['fundo_abertura'], 2, ',', '.') }}</strong>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-success-600 dark:text-success-400">+ Suprimentos:</span>
                <strong class="font-bold text-gray-950 dark:text-white">R$ {{ number_format($stats['suprimentos'], 2, ',', '.') }}</strong>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-success-600 dark:text-success-400">+ Vendas Dinheiro:</span>
                <strong class="font-bold text-gray-950 dark:text-white">R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</strong>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-danger-600 dark:text-danger-400">- Sangrias / Retiradas:</span>
                <strong class="font-bold text-danger-600 dark:text-danger-400">R$ {{ number_format($stats['sangrias'], 2, ',', '.') }}</strong>
            </div>
            <div class="flex items-center gap-2 rounded-lg bg-success-50 px-3.5 py-1.5 ring-1 ring-success-600/20 dark:bg-success-500/10 dark:ring-success-400/30">
                <span class="font-semibold text-success-700 dark:text-success-400">💵 Dinheiro Esperado na Gaveta:</span>
                <strong class="text-base font-bold text-success-700 dark:text-success-300">R$ {{ number_format($stats['saldo_dinheiro_gaveta'], 2, ',', '.') }}</strong>
            </div>
        </div>

        {{-- SEÇÃO 1: VENDAS DO DIA --}}
        <div class="space-y-3">
            <h3 class="text-sm font-bold text-gray-950 dark:text-white">
                ✂️ Vendas &amp; Atendimentos Concluídos ({{ $txs->count() }})
            </h3>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if ($txs->isEmpty())
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center">
                        <span class="text-3xl">🧾</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Nenhuma transação de atendimento concluída nesta data.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Horário</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Barbeiro</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Serviço(s)</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Forma de Pagamento</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Valor Recebido</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($txs as $t)
                                    @php
                                        $methodColor = match ($t->payment_method) {
                                            'pix' => 'info',
                                            'dinheiro' => 'success',
                                            'debito' => 'primary',
                                            'credito' => 'warning',
                                            default => 'gray',
                                        };
                                        $paymentSummary = collect($t->payment_details ?? [])
                                            ->map(fn (array $payment): string => strtoupper($payment['method']).' R$ '.number_format((float) $payment['amount'], 2, ',', '.'))
                                            ->join(' + ');
                                    @endphp
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-950 dark:text-white">
                                            ⏰ {{ $t->scheduled_at->format('H:i') }}
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                            {{ $t->client_name }}
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                            {{ $t->professional?->display_name }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                            {{ $t->services_summary }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-filament::badge :color="$methodColor">
                                                {{ $paymentSummary ?: ($t->payment_method ? strtoupper($t->payment_method) : 'NÃO INFORMADO') }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-success-600 dark:text-success-400">
                                            R$ {{ number_format($t->total_amount, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- VENDAS DE PRODUTOS SEM ATENDIMENTO --}}
        <div class="space-y-3">
            <h3 class="text-sm font-bold text-gray-950 dark:text-white">
                Vendas avulsas de produtos ({{ $productSales->count() }})
            </h3>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if ($productSales->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        Nenhuma venda avulsa de produto nesta data.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Horário</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Produtos e quantidades</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Forma(s) de pagamento</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Valor recebido</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($productSales as $saleLines)
                                    @php
                                        $firstSale = $saleLines->first();
                                        $paymentMethods = collect($firstSale->payment_details ?? [])
                                            ->map(fn (array $payment): string => strtoupper($payment['method']).' R$ '.number_format((float) $payment['amount'], 2, ',', '.'))
                                            ->join(' + ');
                                    @endphp
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $firstSale->created_at->format('H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-950 dark:text-white">{{ $firstSale->client_name ?: 'Venda de balcão' }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                            @foreach ($saleLines as $saleLine)
                                                <span class="block">{{ $saleLine->product?->name }} × {{ $saleLine->quantity }}</span>
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $paymentMethods ?: strtoupper($firstSale->payment_method ?: 'não informado') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-success-600 dark:text-success-400">
                                            R$ {{ number_format((float) $saleLines->sum('total_price'), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- SEÇÃO 2: MOVIMENTAÇÕES AVULSAS (SANGRIAS E SUPRIMENTOS) --}}
        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-bold text-gray-950 dark:text-white">
                    💸 Sangrias &amp; Suprimentos da Gaveta ({{ $movs->count() }})
                </h3>

                <div class="flex flex-wrap gap-2">
                    <x-filament::button
                        size="xs"
                        color="gray"
                        wire:click="mountAction('registerMovement', @js(['type' => 'opening']))"
                    >
                        + Fundo Inicial
                    </x-filament::button>
                    <x-filament::button
                        size="xs"
                        color="gray"
                        wire:click="mountAction('registerMovement', @js(['type' => 'inflow']))"
                    >
                        + Suprimento
                    </x-filament::button>
                    <x-filament::button
                        size="xs"
                        color="danger"
                        outlined
                        wire:click="mountAction('registerMovement', @js(['type' => 'outflow']))"
                    >
                        - Sangria (Retirada)
                    </x-filament::button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if ($movs->isEmpty())
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-8 text-center">
                        <span class="text-2xl">⚖️</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma sangria ou suprimento registrado nesta data.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Horário</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Tipo</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Descrição / Motivo</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Operador</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Valor</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($movs as $m)
                                    @php
                                        $badgeColor = match ($m->type) {
                                            'opening' => 'info',
                                            'inflow' => 'success',
                                            default => 'danger',
                                        };
                                        $isOut = $m->type === 'outflow';
                                    @endphp
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-950 dark:text-white">
                                            {{ $m->created_at->format('H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-filament::badge :color="$badgeColor">
                                                {{ $m->type_label }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200">
                                            {{ $m->description }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $m->user?->name ?? 'Sistema' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold {{ $isOut ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                                            {{ $isOut ? '-' : '+' }} R$ {{ number_format($m->amount, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                wire:click="deleteMovement({{ $m->id }})"
                                                wire:confirm="Tem certeza que deseja remover esta movimentação?"
                                                class="rounded-md p-1 text-gray-400 transition hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10 dark:hover:text-danger-400"
                                                title="Remover movimentação"
                                            >
                                                ✕
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL / CUPOM DE IMPRESSÃO DO FECHAMENTO (80mm) --}}
    @if ($printModalOpen)
        <div class="no-print fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm">
            <div class="flex w-full max-w-md flex-col gap-4 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-white/10">
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">🖨️ Cupom de Fechamento de Caixa</h3>
                    <button
                        type="button"
                        wire:click="$set('printModalOpen', false)"
                        class="text-2xl leading-none text-gray-400 transition hover:text-gray-700 dark:hover:text-gray-200"
                    >
                        &times;
                    </button>
                </div>

                {{-- PRÉ-VISUALIZAÇÃO DO CUPOM TÉRMICO (papel branco 80mm) --}}
                <div class="cr-print-receipt rounded-xl border border-gray-200 bg-white p-5 font-mono text-xs leading-relaxed text-black">
                    <div class="mb-2.5 border-b border-dashed border-black pb-2.5 text-center">
                        <h4 class="m-0 text-base font-black uppercase">{{ $tenant?->name ?? 'BARBEARIA' }}</h4>
                        <span class="block text-[10px]">FECHAMENTO DIÁRIO DE CAIXA</span>
                        <span class="block text-[10px]">{{ $carbonDate->format('d/m/Y') }} • {{ now()->format('H:i:s') }}</span>
                    </div>

                    <div class="mb-2.5 border-b border-dashed border-black pb-2.5">
                        <div class="mb-1 font-extrabold">RESUMO POR FORMA DE PAGAMENTO:</div>
                        <div class="flex justify-between">
                            <span>PIX:</span> <strong>R$ {{ number_format($stats['pix'], 2, ',', '.') }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>DINHEIRO (VENDAS):</span> <strong>R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>CARTÃO DÉBITO:</span> <strong>R$ {{ number_format($stats['debito'], 2, ',', '.') }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>CARTÃO CRÉDITO:</span> <strong>R$ {{ number_format($stats['credito'], 2, ',', '.') }}</strong>
                        </div>
                        <div class="mt-1.5 flex justify-between border-t border-black pt-1 text-sm font-black">
                            <span>TOTAL FATURADO:</span> <span>R$ {{ number_format($stats['total'], 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mb-2.5 border-b border-dashed border-black pb-2.5">
                        <div class="mb-1 font-extrabold">CONFERÊNCIA DA GAVETA (FÍSICO):</div>
                        <div class="flex justify-between">
                            <span>FUNDO INICIAL:</span> <span>R$ {{ number_format($stats['fundo_abertura'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>+ SUPRIMENTOS:</span> <span>R$ {{ number_format($stats['suprimentos'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>+ VENDAS DINHEIRO:</span> <span>R$ {{ number_format($stats['dinheiro'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>- SANGRIA/RETIRADAS:</span> <span>R$ {{ number_format($stats['sangrias'], 2, ',', '.') }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-[13px] font-black">
                            <span>SALDO NA GAVETA:</span> <span>R$ {{ number_format($stats['saldo_dinheiro_gaveta'], 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-4 text-center text-[10px]">
                        <span>TOTAL DE VENDAS: {{ $stats['count'] }}</span>
                        <div class="mt-6 border-t border-black pt-1">
                            RESPONSÁVEL PELO CAIXA
                        </div>
                    </div>
                </div>

                <div class="no-print flex items-center justify-end gap-3 border-t border-gray-200 pt-3 dark:border-white/10">
                    <x-filament::button
                        color="gray"
                        wire:click="$set('printModalOpen', false)"
                    >
                        Fechar
                    </x-filament::button>
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-printer"
                        onclick="window.print()"
                    >
                        Imprimir Agora
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
