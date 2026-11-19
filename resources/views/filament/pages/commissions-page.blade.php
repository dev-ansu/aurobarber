<x-filament-panels::page>
    @php
        $stats = $this->summaryStats;
        $appts = $this->appointments;
        $prodSales = $this->productSales;
        $payouts = $this->payoutsHistory;
        $selectedProf = $this->selectedProfessional;
    @endphp

    <div class="space-y-6">
        {{-- 1. BARRA DE FILTROS & COMANDO --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2.5">
                    <select
                        wire:model.live="selectedProfessionalId"
                        aria-label="Selecionar profissional"
                        class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                    >
                        @foreach ($this->professionals as $pro)
                            <option value="{{ $pro->id }}">💈 {{ $pro->display_name }} ({{ number_format($pro->commission_rate, 0) }}%)</option>
                        @endforeach
                    </select>

                    {{-- PRESETS DE PERÍODO --}}
                    <div class="inline-flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
                        <x-filament::button
                            size="xs"
                            :color="$periodPreset === 'today' ? 'primary' : 'gray'"
                            wire:click="applyPreset('today')"
                        >
                            Hoje
                        </x-filament::button>
                        <x-filament::button
                            size="xs"
                            :color="$periodPreset === 'week' ? 'primary' : 'gray'"
                            wire:click="applyPreset('week')"
                        >
                            Esta Semana
                        </x-filament::button>
                        <x-filament::button
                            size="xs"
                            :color="$periodPreset === 'month' ? 'primary' : 'gray'"
                            wire:click="applyPreset('month')"
                        >
                            Este Mês
                        </x-filament::button>
                    </div>

                    {{-- DATAS INÍCIO E FIM --}}
                    <input
                        type="date"
                        wire:model.live="startDate"
                        title="Data Inicial"
                        class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />
                    <span class="text-xs text-gray-500 dark:text-gray-400">até</span>
                    <input
                        type="date"
                        wire:model.live="endDate"
                        title="Data Final"
                        class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />

                    {{-- STATUS --}}
                    <select
                        wire:model.live="statusFilter"
                        aria-label="Filtrar por status"
                        class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="pending">⏳ Apenas Em Aberto</option>
                        <option value="paid">✅ Apenas Já Liquidados</option>
                        <option value="all">Todos os Atendimentos</option>
                    </select>
                </div>

                {{-- BOTÃO LIQUIDAR COMISSÃO (Apenas Dono / Gestor) --}}
                @if ($selectedProf && !auth()->user()?->isRestrictedBarber())
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-currency-dollar"
                        wire:click="mountAction('payoutCommission')"
                    >
                        Liquidar Comissões
                    </x-filament::button>
                @endif
            </div>
        </x-filament::section>

        {{-- 2. CARDS DE KPI FINANCEIRO --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ auth()->user()?->isRestrictedBarber() ? 'Minha Produção Bruta' : 'Produção Bruta' }}
                </p>
                <p class="mt-1.5 text-2xl font-bold text-gray-950 dark:text-white">
                    R$ {{ number_format($stats['total_gross'], 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $stats['count'] }} corte(s) + {{ $stats['products_count'] }} produto(s)
                </p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ auth()->user()?->isRestrictedBarber() ? 'Minha Comissão Total' : 'Comissão do Barbeiro' }}
                </p>
                <p class="mt-1.5 text-2xl font-bold text-primary-600 dark:text-primary-400">
                    R$ {{ number_format($stats['total_commission'], 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Cortes: R$ {{ number_format($stats['services_commission'], 2, ',', '.') }} | Produtos: R$ {{ number_format($stats['products_commission'], 2, ',', '.') }}
                </p>
            </div>

            @if (!auth()->user()?->isRestrictedBarber())
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Lucro da Barbearia</p>
                    <p class="mt-1.5 text-2xl font-bold text-success-600 dark:text-success-400">
                        R$ {{ number_format($stats['barbershop_cut'], 2, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Retenção líquida da casa</p>
                </div>
            @else
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Recibos Liquidados</p>
                    <p class="mt-1.5 text-2xl font-bold text-success-600 dark:text-success-400">{{ $payouts->count() }} recibo(s)</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Histórico de pagamentos recebidos</p>
                </div>
            @endif

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Venda de Produtos</p>
                <p class="mt-1.5 text-2xl font-bold text-gray-950 dark:text-white">
                    R$ {{ number_format($stats['products_gross'], 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stats['products_count'] }} item(ns) vendidos</p>
            </div>
        </div>

        {{-- 3. ABAS DE VISUALIZAÇÃO --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-white/10">
            <button
                type="button"
                wire:click="$set('activeTab', 'appointments')"
                @class([
                    'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'appointments',
                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'appointments',
                ])
            >
                ✂️ Atendimentos do Período ({{ $appts->count() }})
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'products')"
                @class([
                    'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'products',
                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'products',
                ])
            >
                🧴 Produtos Vendidos ({{ $prodSales->count() }})
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'history')"
                @class([
                    'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'history',
                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'history',
                ])
            >
                📜 Histórico de Fechamentos / Recibos ({{ $payouts->count() }})
            </button>
        </div>

        {{-- 4. ABA 1: LISTA DE ATENDIMENTOS DO PERÍODO --}}
        @if ($activeTab === 'appointments')
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if ($appts->isEmpty())
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
                        <span class="text-3xl">☕</span>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Nenhum atendimento concluído encontrado para os filtros selecionados.</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Mude o período acima ou verifique se os cortes já foram marcados como concluídos na Agenda Diária.</span>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Data / Hora</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Barbeiro</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Serviço(s)</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Valor Bruto</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">% Comis.</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Comissão (R$)</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status Comissão</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($appts as $a)
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="font-semibold text-gray-950 dark:text-white">{{ $a->scheduled_at->format('d/m/Y') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $a->scheduled_at->format('H:i') }} às {{ $a->ends_at->format('H:i') }}</div>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                            {{ $a->professional?->display_name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-950 dark:text-white">{{ $a->client_name }}</span>
                                            @if ($a->payment_method)
                                                <span class="block text-[11px] uppercase text-gray-500 dark:text-gray-400">Pago via {{ $a->payment_method }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                            {{ $a->services_summary }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-950 dark:text-white">
                                            R$ {{ number_format($a->service_price, 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">
                                            {{ number_format($a->commission_rate, 0) }}%
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-primary-600 dark:text-primary-400">
                                            R$ {{ number_format($a->commission_amount, 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            @if ($a->commission_status === 'paid')
                                                <x-filament::badge color="success">✓ Pago (#{{ $a->commission_payout_id }})</x-filament::badge>
                                            @else
                                                <x-filament::badge color="warning">⏳ Em Aberto</x-filament::badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @elseif ($activeTab === 'products')
            {{-- ABA DE PRODUTOS VENDIDOS PELO BARBEIRO --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if ($prodSales->isEmpty())
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
                        <span class="text-3xl">🧴</span>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Nenhuma venda de produto encontrada para o barbeiro no período selecionado.</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Vendas registradas no balcão ou durante o corte com comissão atribuída aparecerão aqui.</span>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Data / Hora</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Barbeiro</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Produto Vendido</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Valor Total</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">% Comis.</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Comissão (R$)</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status Comissão</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($prodSales as $ps)
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="font-semibold text-gray-950 dark:text-white">{{ $ps->created_at->format('d/m/Y') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $ps->created_at->format('H:i') }}</div>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                            {{ $ps->professional?->display_name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-semibold text-info-600 dark:text-info-400">{{ $ps->quantity }}x {{ $ps->product?->name ?? 'Produto' }}</span>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Unitário: R$ {{ number_format($ps->unit_price, 2, ',', '.') }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-gray-700 dark:text-gray-200">{{ $ps->client_name ?: ($ps->appointment?->client_name ?: 'Balcão') }}</span>
                                            <span class="block text-[11px] uppercase text-gray-500 dark:text-gray-400">{{ $ps->payment_method_label }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-950 dark:text-white">
                                            R$ {{ number_format($ps->total_price, 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">
                                            {{ number_format($ps->commission_rate, 0) }}%
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-primary-600 dark:text-primary-400">
                                            R$ {{ number_format($ps->commission_amount, 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            @if ($ps->commission_status === 'paid')
                                                <x-filament::badge color="success">✓ Pago (#{{ $ps->commission_payout_id }})</x-filament::badge>
                                            @else
                                                <x-filament::badge color="warning">⏳ Em Aberto</x-filament::badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            {{-- 5. ABA 2: HISTÓRICO DE RECIBOS / FECHAMENTOS --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if ($payouts->isEmpty())
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
                        <span class="text-3xl">📑</span>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Nenhum fechamento de comissão liquidado até o momento.</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Quando você clicar em "Liquidar Comissões", os comprovantes ficarão salvos aqui.</span>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Recibo</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Data Pagamento</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Barbeiro</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Período Apurado</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Produção</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cortes</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Produtos</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Comissão Paga</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Método</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($payouts as $p)
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-primary-600 dark:text-primary-400">
                                            #{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">
                                            {{ $p->paid_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-gray-950 dark:text-white">
                                            {{ $p->professional?->display_name }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $p->period_start->format('d/m/Y') }} até {{ $p->period_end->format('d/m/Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-700 dark:text-gray-200">
                                            R$ {{ number_format($p->total_gross, 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $p->appointments_count }} corte(s)
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs font-medium text-info-600 dark:text-info-400">
                                            {{ $p->products_count }} item(ns)
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-success-600 dark:text-success-400">
                                            R$ {{ number_format($p->total_commission, 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <x-filament::badge color="gray">
                                                {{ $p->payment_method_label }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <x-filament::button
                                                size="xs"
                                                color="gray"
                                                icon="heroicon-o-printer"
                                                wire:click="openPrintPayoutModal({{ $p->id }})"
                                            >
                                                Recibo
                                            </x-filament::button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- MODAL / CANHOTO DE IMPRESSÃO DO RECIBO DE COMISSÃO --}}
    @if ($printPayoutModalOpen && $selectedPayoutToPrint)
        <div class="no-print fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm">
            <div class="flex w-full max-w-lg flex-col gap-4 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-white/10">
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">📑 Recibo de Pagamento de Comissão</h3>
                    <button
                        type="button"
                        wire:click="$set('printPayoutModalOpen', false)"
                        class="text-2xl leading-none text-gray-400 transition hover:text-gray-700 dark:hover:text-gray-200"
                    >
                        &times;
                    </button>
                </div>

                {{-- PRÉ-VISUALIZAÇÃO DO RECIBO COM CANHOTO (papel branco para impressão) --}}
                <div id="payout-receipt" class="rounded-xl border border-gray-200 bg-white p-5 font-mono text-xs leading-relaxed text-black">
                    <div class="mb-3 border-b-2 border-black pb-2.5 text-center">
                        <h3 class="m-0 text-base font-black uppercase">{{ $selectedPayoutToPrint->barbershop?->name ?? 'AUROBARBER' }}</h3>
                        <span class="mt-0.5 block text-[11px] font-bold">RECIBO DE PAGAMENTO DE COMISSÃO</span>
                        <span class="block text-[10px] text-neutral-700">Recibo Nº #{{ str_pad($selectedPayoutToPrint->id, 5, '0', STR_PAD_LEFT) }} • Emitido em {{ $selectedPayoutToPrint->paid_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="mb-3 border-b border-dashed border-black pb-2">
                        <div><strong>PROFISSIONAL:</strong> {{ $selectedPayoutToPrint->professional?->name }} ({{ $selectedPayoutToPrint->professional?->display_name }})</div>
                        <div><strong>PERÍODO:</strong> {{ $selectedPayoutToPrint->period_start->format('d/m/Y') }} a {{ $selectedPayoutToPrint->period_end->format('d/m/Y') }}</div>
                        <div><strong>MÉTODO DE PAGAMENTO:</strong> {{ $selectedPayoutToPrint->payment_method_label }}</div>
                    </div>

                    <div class="mb-3 border-b border-dashed border-black pb-2">
                        <div class="flex justify-between">
                            <span>SERVIÇOS / CORTES ({{ $selectedPayoutToPrint->appointments_count }} atendimentos):</span>
                            <span>R$ {{ number_format($selectedPayoutToPrint->services_gross ?: $selectedPayoutToPrint->total_gross, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-[11px] text-neutral-700">
                            <span>» Comissão sobre Serviços:</span>
                            <span>R$ {{ number_format($selectedPayoutToPrint->services_commission ?: $selectedPayoutToPrint->total_commission, 2, ',', '.') }}</span>
                        </div>

                        @if ($selectedPayoutToPrint->products_gross > 0)
                            <div class="mt-1.5 flex justify-between">
                                <span>PRODUTOS VENDIDOS ({{ $selectedPayoutToPrint->products_count }} itens):</span>
                                <span>R$ {{ number_format($selectedPayoutToPrint->products_gross, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-[11px] text-neutral-700">
                                <span>» Comissão sobre Produtos:</span>
                                <span>R$ {{ number_format($selectedPayoutToPrint->products_commission, 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="mt-2 flex justify-between border-t border-dashed border-neutral-400 pt-1 font-bold">
                            <span>TOTAL PRODUÇÃO BRUTA:</span>
                            <span>R$ {{ number_format($selectedPayoutToPrint->total_gross, 2, ',', '.') }}</span>
                        </div>
                        <div class="mt-1 flex justify-between border-t border-black pt-1 text-sm font-black">
                            <span>VALOR LÍQUIDO PAGO AO BARBEIRO:</span>
                            <span class="text-base">R$ {{ number_format($selectedPayoutToPrint->total_commission, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    @if ($selectedPayoutToPrint->notes)
                        <div class="mb-2.5 text-[11px] text-neutral-600">
                            <em>Obs: {{ $selectedPayoutToPrint->notes }}</em>
                        </div>
                    @endif

                    {{-- CANHOTO JURÍDICO DE QUITAÇÃO --}}
                    <div class="mt-5 rounded-md border border-black p-2.5 text-justify text-[10px]">
                        <p class="m-0">
                            Declaro que recebi da barbearia acima a quantia líquida de <strong>R$ {{ number_format($selectedPayoutToPrint->total_commission, 2, ',', '.') }}</strong>, referente ao repasse de comissões por serviços prestados e produtos comercializados no período discriminado, dando plena e total quitação.
                        </p>
                        <div class="mt-7 border-t border-black pt-1 text-center">
                            Assinatura do Profissional: <strong>{{ $selectedPayoutToPrint->professional?->display_name }}</strong>
                        </div>
                    </div>
                </div>

                <div class="no-print flex items-center justify-end gap-3 border-t border-gray-200 pt-3 dark:border-white/10">
                    <x-filament::button
                        color="gray"
                        wire:click="$set('printPayoutModalOpen', false)"
                    >
                        Fechar
                    </x-filament::button>
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-printer"
                        onclick="window.print()"
                    >
                        Imprimir Recibo
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
