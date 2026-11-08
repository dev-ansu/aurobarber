<x-filament-panels::page>
    <x-report-document-header title="Relatório de Alunos Flex e Cursos Gratuitos" />

    @php
        $records = $this->records(100);
        $metrics = $this->metrics();
    @endphp

    <div class="space-y-6">
        <!-- Cards de Indicadores Rápidos -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400">
                        <x-filament::icon icon="heroicon-o-bolt" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Alunos Flex Ativos</p>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $metrics['active_flex'] }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Acesso livre a múltiplas disciplinas</p>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <x-filament::icon icon="heroicon-o-gift" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Cursos Gratuitos Ativos</p>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $metrics['active_free'] }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Isentos de cobrança / bolsistas</p>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                        <x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Flex & Gratuitos</p>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $metrics['active_both'] }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Contratos com as duas condições</p>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-funnel" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total no Filtro</p>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $metrics['total_filtered'] }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Registros correspondentes</p>
            </div>
        </div>

        <!-- Filtros do Relatório -->
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Filtros de Consulta</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Refine os alunos especiais listados e exporte o resultado em CSV.</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-filament::button wire:click="exportCsv" icon="heroicon-o-arrow-down-tray" color="primary">
                        Exportar CSV
                    </x-filament::button>
                    <x-filament::button wire:click="clearFilters" color="gray" icon="heroicon-o-arrow-path">
                        Limpar filtros
                    </x-filament::button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Modalidade Especial</span>
                    <select wire:model.live="filterType" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        @foreach ($this->filterTypes() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Situação do Contrato</span>
                    <select wire:model.live="contractStatus" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        @foreach ($this->contractStatuses() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Formação</span>
                    <select wire:model.live="formationId" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Todas as formações</option>
                        @foreach ($this->formations() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Buscar Aluno ou Contrato</span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nome, CPF ou Nº Contrato..." class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10" />
                </label>
            </div>
        </section>

        <!-- Tabela de Resultados -->
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50/50 text-xs uppercase tracking-wider text-gray-500 dark:border-white/5 dark:bg-gray-800/50 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Aluno</th>
                            <th class="px-4 py-3">Nº Contrato</th>
                            <th class="px-4 py-3">Modalidade</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Formações / Cursos</th>
                            <th class="px-4 py-3">Responsável</th>
                            <th class="px-4 py-3">Início</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($records as $contract)
                            @php
                                $student = $contract->student;
                                $isFree = $contract->isFree();
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $student?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">
                                        @if ($student?->cpf) CPF: {{ $student->cpf }} @endif
                                        @if ($student?->email) • {{ $student->email }} @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-gray-100">
                                    {{ $contract->contract_number }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($contract->is_flex)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-950 dark:text-primary-300">
                                                <x-filament::icon icon="heroicon-m-bolt" class="h-3 w-3" />
                                                Flex
                                            </span>
                                        @endif
                                        @if ($isFree)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950 dark:text-emerald-300">
                                                <x-filament::icon icon="heroicon-m-gift" class="h-3 w-3" />
                                                Gratuito / Bolsista
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-300',
                                            'draft' => 'bg-gray-100 text-gray-700 ring-gray-600/20 dark:bg-gray-800 dark:text-gray-300',
                                            'finished' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950 dark:text-blue-300',
                                            'canceled' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950 dark:text-rose-300',
                                            'suspended' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950 dark:text-amber-300',
                                        ];
                                        $statusLabels = [
                                            'active' => 'Ativo',
                                            'draft' => 'Rascunho',
                                            'finished' => 'Finalizado',
                                            'canceled' => 'Cancelado',
                                            'suspended' => 'Suspenso',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColors[$contract->status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $statusLabels[$contract->status] ?? $contract->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ $contract->items->map(fn ($item) => $item->formation?->name)->filter()->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ $contract->responsible?->person?->name ?? ($isFree ? 'Isento' : '—') }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ $contract->start_date ? $contract->start_date->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('filament.admin.resources.contracts.edit', ['record' => $contract->id]) }}" target="_blank" class="rounded p-1 text-gray-500 hover:bg-gray-100 hover:text-primary-600 dark:hover:bg-gray-800">
                                            <x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4" />
                                        </a>
                                        @if ($student)
                                            <a href="{{ route('filament.admin.resources.people.edit', ['record' => $student->id]) }}" target="_blank" class="rounded p-1 text-gray-500 hover:bg-gray-100 hover:text-primary-600 dark:hover:bg-gray-800" title="Editar dados do aluno">
                                                <x-filament::icon icon="heroicon-o-user" class="h-4 w-4" />
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Nenhum contrato encontrado para os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>

