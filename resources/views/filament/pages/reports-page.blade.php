<x-filament-panels::page>
    @php
        $ranking = $this->barbersRanking;
        $services = $this->topServices;
        $absent = $this->absentClients;
    @endphp

    <div class="space-y-6">
        {{-- TOOLBAR --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="inline-flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
                        <x-filament::button
                            size="xs"
                            :color="$periodPreset === 'month' ? 'primary' : 'gray'"
                            wire:click="applyPreset('month')"
                        >
                            Este Mês
                        </x-filament::button>
                        <x-filament::button
                            size="xs"
                            :color="$periodPreset === '30days' ? 'primary' : 'gray'"
                            wire:click="applyPreset('30days')"
                        >
                            Últimos 30 Dias
                        </x-filament::button>
                        <x-filament::button
                            size="xs"
                            :color="$periodPreset === 'year' ? 'primary' : 'gray'"
                            wire:click="applyPreset('year')"
                        >
                            Este Ano
                        </x-filament::button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            type="date"
                            wire:model.live="startDate"
                            aria-label="Data inicial"
                            class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        />
                        <span class="text-xs text-gray-500 dark:text-gray-400">até</span>
                        <input
                            type="date"
                            wire:model.live="endDate"
                            aria-label="Data final"
                            class="block h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        />
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Período:
                    <strong class="font-semibold text-gray-950 dark:text-white">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</strong>
                    a
                    <strong class="font-semibold text-gray-950 dark:text-white">{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</strong>
                </div>
            </div>
        </x-filament::section>

        {{-- ABAS --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-white/10">
            <button
                type="button"
                wire:click="$set('activeTab', 'ranking')"
                @class([
                    'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'ranking',
                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'ranking',
                ])
            >
                🏆 Ranking da Equipe
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'services')"
                @class([
                    'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'services',
                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'services',
                ])
            >
                ✂️ Serviços Mais Lucrativos
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'absent')"
                @class([
                    'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'absent',
                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'absent',
                ])
            >
                📱 Clientes Ausentes / Recuperação
            </button>
        </div>

        {{-- ABA 1: RANKING DA EQUIPE --}}
        @if ($activeTab === 'ranking')
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if ($ranking->isEmpty())
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
                        <span class="text-3xl">💈</span>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Nenhum atendimento apurado para este período.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="w-20 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Posição</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Profissional</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cortes Realizados</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Faturamento Bruto</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Comissão Gerada</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ticket Médio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($ranking as $index => $item)
                                    @php
                                        $rankNum = $index + 1;
                                        $rankBadgeClass = match ($rankNum) {
                                            1 => 'bg-primary-500 text-gray-950 shadow-sm',
                                            2 => 'bg-gray-300 text-gray-950 dark:bg-gray-400',
                                            3 => 'bg-amber-700 text-white',
                                            default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                                        };
                                        $medal = match ($rankNum) {
                                            1 => '🥇',
                                            2 => '🥈',
                                            3 => '🥉',
                                            default => '#' . $rankNum,
                                        };
                                    @endphp
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-3">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $rankBadgeClass }}">
                                                {{ $medal }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-950 dark:text-white">{{ $item['professional']->display_name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Comissão de {{ number_format($item['professional']->commission_rate, 0) }}%</div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-950 dark:text-white">
                                            {{ $item['count'] }} corte(s)
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-base font-bold text-primary-600 dark:text-primary-400">
                                            R$ {{ number_format($item['total_gross'], 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-success-600 dark:text-success-400">
                                            R$ {{ number_format($item['total_commission'], 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                            R$ {{ number_format($item['avg_ticket'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @elseif ($activeTab === 'services')
            {{-- ABA 2: SERVIÇOS MAIS LUCRATIVOS --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if ($services->isEmpty())
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
                        <span class="text-3xl">✂️</span>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Nenhum serviço registrado neste período.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Serviço</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Quantidade Realizada</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Faturamento Total</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Preço Médio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($services as $s)
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 font-semibold text-gray-950 dark:text-white">
                                            {{ $s['name'] }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">
                                            {{ $s['count'] }} vez(es)
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-base font-bold text-primary-600 dark:text-primary-400">
                                            R$ {{ number_format($s['total_revenue'], 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                            R$ {{ number_format($s['avg_price'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            {{-- ABA 3: RECUPERAÇÃO DE CLIENTES (CLIENTES AUSENTES) --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-warning-500/30 bg-warning-50 px-4 py-3 text-sm text-warning-900 dark:bg-warning-500/10 dark:text-warning-200">
                    💡 <strong>Oportunidade de Receita:</strong> Estes clientes já conhecem seu trabalho, mas não cortam o cabelo há mais de <strong>{{ $absentDays }} dias</strong>. Clique no botão de WhatsApp para enviar uma mensagem personalizada de retorno com 1 clique!
                </div>

                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    @if ($absent->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
                            <span class="text-3xl">🎉</span>
                            <p class="text-sm font-semibold text-success-600 dark:text-success-400">Excelente! Nenhum cliente ausente há mais de {{ $absentDays }} dias.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                                <thead class="bg-gray-50 dark:bg-white/5">
                                    <tr>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Último Atendimento</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Tempo Ausente</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Total de Visitas Anteriores</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ação no WhatsApp</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                    @foreach ($absent as $c)
                                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-950 dark:text-white">{{ $c['client_name'] }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $c['client_phone'] }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-700 dark:text-gray-200">
                                                {{ $c['last_appointment']->format('d/m/Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <x-filament::badge color="danger">
                                                    ⚠️ {{ $c['days_away'] }} dias sem vir
                                                </x-filament::badge>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ $c['total_cuts'] }} corte(s) histórico(s)
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                @if ($c['whatsapp_url'])
                                                    <x-filament::button
                                                        tag="a"
                                                        href="{{ $c['whatsapp_url'] }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        size="xs"
                                                        color="success"
                                                        icon="heroicon-o-chat-bubble-left-right"
                                                    >
                                                        Chamar no WhatsApp
                                                    </x-filament::button>
                                                @else
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Contato protegido</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
