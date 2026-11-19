<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 1. HEADER DO SAAS MASTER --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">Plataforma SaaS Central</span>
                    <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">Gestão Global &amp; Licenciamento de Barbearias</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitore o status das licenças de uso, controle carências, suspensões e renovações mensais.</p>
                </div>
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Resources\BarbershopResource::getUrl('create', panel: 'saas') }}"
                    color="primary"
                    icon="heroicon-o-plus"
                >
                    Cadastrar Nova Barbearia
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- 2. CARDS DE KPIS GLOBAIS DE LICENÇA --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Licenças Ativas</span>
                <div class="mt-2 text-2xl font-bold text-success-600 dark:text-success-400">{{ $activeBarbershops }}</div>
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">em dia / regulares</span>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-semibold uppercase tracking-wider text-warning-600 dark:text-warning-400">Em Carência (5d)</span>
                <div class="mt-2 text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $graceBarbershops }}</div>
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">vencidas com tolerância</span>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-semibold uppercase tracking-wider text-danger-600 dark:text-danger-400">Suspensas</span>
                <div class="mt-2 text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $suspendedBarbershops }}</div>
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">acesso bloqueado</span>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-semibold uppercase tracking-wider text-info-600 dark:text-info-400">Total de Barbearias</span>
                <div class="mt-2 text-2xl font-bold text-info-600 dark:text-info-400">{{ $totalBarbershops }}</div>
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $totalBranches }} filiais • {{ $totalUnits }} un. totais</span>
            </div>

            <div class="col-span-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:col-span-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">Total Atendimentos</span>
                <div class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $totalAppointments }}</div>
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">agendamentos no ecossistema</span>
            </div>
        </div>

        {{-- 3. TABELA DE BARBEARIAS & LICENCIAMENTO --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-white/10">
                <div>
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">Status de Licenças das Barbearias</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Renove prazos ou suspenda o acesso com 1 clique.</p>
                </div>
                <a
                    href="{{ \App\Filament\Resources\BarbershopResource::getUrl('index', panel: 'saas') }}"
                    class="text-xs font-bold text-primary-600 transition hover:text-primary-500 dark:text-primary-400"
                >
                    Gerenciar Todas as Barbearias &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Barbearia</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Proprietário</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status da Licença</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Vencimento</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Mensalidade</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ações de Controle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($recentBarbershops as $shop)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <div class="flex items-center gap-1.5 font-semibold text-gray-950 dark:text-white">
                                        <span>{{ $shop['name'] }}</span>
                                        @if($shop['is_branch'])
                                            <span class="inline-flex items-center rounded-md bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-500 border border-amber-500/20">
                                                FILIAL
                                            </span>
                                        @elseif(($shop['branches_count'] ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-md bg-primary-500/10 px-1.5 py-0.5 text-[10px] font-bold text-primary-500 border border-primary-500/20">
                                                MATRIZ ({{ $shop['branches_count'] }} {{ $shop['branches_count'] === 1 ? 'filial' : 'filiais' }})
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $shop['slug'] }} • {{ $shop['city'] }}
                                        @if($shop['is_branch'] && !empty($shop['parent_name']))
                                            <span class="block text-[11px] text-amber-600 dark:text-amber-400 font-medium">↳ Filial vinculada à Matriz: {{ $shop['parent_name'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $shop['owner_name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $shop['owner_email'] }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    @if($shop['is_suspended'])
                                        <x-filament::badge color="danger">SUSPENSA (BLOQUEADA)</x-filament::badge>
                                    @elseif($shop['is_in_grace'])
                                        <div class="flex flex-col items-start gap-0.5">
                                            <x-filament::badge color="warning">EM CARÊNCIA</x-filament::badge>
                                            <span class="text-[11px] font-semibold text-warning-600 dark:text-warning-400">{{ $shop['grace_days_remaining'] }}d de tolerância</span>
                                        </div>
                                    @else
                                        <x-filament::badge color="success">ATIVA (REGULAR)</x-filament::badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <div class="font-mono font-semibold text-gray-950 dark:text-white">{{ $shop['expires_at_formatted'] }}</div>
                                    @if($shop['days_remaining'] < 0)
                                        <div class="text-xs font-semibold text-danger-600 dark:text-danger-400">Vencida há {{ abs($shop['days_remaining']) }} dias</div>
                                    @elseif($shop['days_remaining'] === 0)
                                        <div class="text-xs font-semibold text-warning-600 dark:text-warning-400">Vence hoje!</div>
                                    @else
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Restam {{ $shop['days_remaining'] }} dias</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 font-semibold text-info-600 dark:text-info-400">
                                    @if($shop['is_branch'])
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">Incluso na Matriz</span>
                                    @else
                                        R$ {{ $shop['monthly_price'] }}
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                    <div class="inline-flex flex-nowrap items-center justify-end gap-2">
                                        <x-filament::button
                                            size="xs"
                                            color="warning"
                                            outlined
                                            wire:click="openDueDateModal({{ $shop['id'] }})"
                                        >
                                            📅 Data
                                        </x-filament::button>

                                        <x-filament::button
                                            size="xs"
                                            color="success"
                                            outlined
                                            wire:click="renew30({{ $shop['id'] }})"
                                            wire:confirm="Deseja renovar a licença de '{{ $shop['name'] }}' por mais 30 dias?"
                                        >
                                            +30 Dias
                                        </x-filament::button>

                                        @if($shop['is_suspended'])
                                            <x-filament::button
                                                size="xs"
                                                color="success"
                                                wire:click="toggleStatus({{ $shop['id'] }})"
                                                wire:confirm="Deseja REATIVAR o acesso de '{{ $shop['name'] }}'?"
                                            >
                                                Reativar
                                            </x-filament::button>
                                        @else
                                            <x-filament::button
                                                size="xs"
                                                color="danger"
                                                outlined
                                                wire:click="toggleStatus({{ $shop['id'] }})"
                                                wire:confirm="Atenção: Deseja SUSPENDER '{{ $shop['name'] }}'? O acesso dos lojistas será bloqueado."
                                            >
                                                Suspender
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Nenhuma barbearia cadastrada no momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MODAL DE AJUSTE DIRETO DE VENCIMENTO --}}
        @if($editingBarbershopId)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm">
                <div class="flex w-full max-w-md flex-col gap-5 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">Acordo de Pagamento</span>
                        <h3 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">Alterar Vencimento: {{ $editingBarbershopName }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Defina a data exata combinada com o cliente. O sistema regularizará o acesso e a carência de 5 dias passará a valer após esta data.
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Nova Data de Vencimento:</label>
                        <input
                            type="date"
                            wire:model="newDueDate"
                            class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3.5 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                        <x-filament::button color="gray" wire:click="closeDueDateModal">
                            Cancelar
                        </x-filament::button>
                        <x-filament::button color="primary" wire:click="saveDueDate">
                            Salvar e Liberar Acesso
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
