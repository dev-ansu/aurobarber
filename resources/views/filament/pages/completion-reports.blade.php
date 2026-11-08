<x-filament-panels::page>
    <x-report-document-header title="Relatório de conclusão de alunos" />
    @php($counts = $this->completedStudentCounts)

    <div class="flex flex-col gap-6">
        <x-filament::section>
            <x-slot name="heading">Filtros</x-slot>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Início do período
                    <input type="date" wire:model.live="periodStart" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                    @error('periodStart') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Fim do período
                    <input type="date" wire:model.live="periodEnd" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                    @error('periodEnd') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Aluno ou contrato
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Nome ou número do contrato" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500">
                </label>
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Formação
                    <select wire:model.live="formationId" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Todas as formações</option>
                        @foreach ($this->filterOptions['formations'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Curso
                    <select wire:model.live="courseId" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Todos os cursos</option>
                        @foreach ($this->filterOptions['courses'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @php($contractsChange = $counts['contracts'] - ($counts['previous']['contracts'] ?? 0))
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Alunos com contrato concluído</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $counts['contracts'] }}</p>
                <p class="mt-2 text-xs {{ $contractsChange > 0 ? 'text-success-600' : ($contractsChange < 0 ? 'text-danger-600' : 'text-gray-500') }}">{{ $contractsChange > 0 ? '+' : '' }}{{ $contractsChange }} em relação ao período anterior ({{ $counts['previous']['contracts'] ?? 0 }})</p>
            </x-filament::section>
            @php($formationsChange = $counts['formations'] - ($counts['previous']['formations'] ?? 0))
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Alunos com formação concluída</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $counts['formations'] }}</p>
                <p class="mt-2 text-xs {{ $formationsChange > 0 ? 'text-success-600' : ($formationsChange < 0 ? 'text-danger-600' : 'text-gray-500') }}">{{ $formationsChange > 0 ? '+' : '' }}{{ $formationsChange }} em relação ao período anterior ({{ $counts['previous']['formations'] ?? 0 }})</p>
            </x-filament::section>
            @php($coursesChange = $counts['courses'] - ($counts['previous']['courses'] ?? 0))
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Alunos com curso concluído</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $counts['courses'] }}</p>
                <p class="mt-2 text-xs {{ $coursesChange > 0 ? 'text-success-600' : ($coursesChange < 0 ? 'text-danger-600' : 'text-gray-500') }}">{{ $coursesChange > 0 ? '+' : '' }}{{ $coursesChange }} em relação ao período anterior ({{ $counts['previous']['courses'] ?? 0 }})</p>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Contratos concluídos no período</x-slot>
            <x-slot name="description">Conclusões registradas pela secretaria, filtradas pelo período selecionado.</x-slot>
            @if ($this->canExport())
                <div class="mb-4 flex flex-wrap gap-2">
                    <x-filament::button wire:click="downloadReport('contracts', 'csv')" icon="heroicon-o-arrow-down-tray" color="gray">Exportar CSV</x-filament::button>
                    <x-filament::button wire:click="downloadReport('contracts', 'xlsx')" icon="heroicon-o-table-cells" color="gray">Exportar Excel</x-filament::button>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400"><tr><th class="px-3 py-2">Data</th><th class="px-3 py-2">Aluno</th><th class="px-3 py-2">Contrato</th><th class="px-3 py-2">Observação</th></tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($this->completedContracts as $history)
                            <tr><td class="px-3 py-2">{{ $history->created_at?->format('d/m/Y') ?? '—' }}</td><td class="px-3 py-2">{{ $history->contract?->student?->name ?? 'Aluno removido' }}</td><td class="px-3 py-2">{{ $history->contract?->contract_number ?? '—' }}</td><td class="px-3 py-2">{{ $history->reason ?: '—' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-gray-500">Nenhuma conclusão de contrato neste período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->completedContracts->links() }}</div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Formações concluídas</x-slot>
            @if ($this->canExport())
                <div class="mb-4 flex flex-wrap gap-2">
                    <x-filament::button wire:click="downloadReport('formations', 'csv')" icon="heroicon-o-arrow-down-tray" color="gray">Exportar CSV</x-filament::button>
                    <x-filament::button wire:click="downloadReport('formations', 'xlsx')" icon="heroicon-o-table-cells" color="gray">Exportar Excel</x-filament::button>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400"><tr><th class="px-3 py-2">Data</th><th class="px-3 py-2">Aluno</th><th class="px-3 py-2">Contrato</th><th class="px-3 py-2">Formação</th></tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($this->completedFormations as $history)
                            <tr><td class="px-3 py-2">{{ $history->completed_at?->format('d/m/Y') ?? '—' }}</td><td class="px-3 py-2">{{ $history->contractItem?->contract?->student?->name ?? 'Aluno removido' }}</td><td class="px-3 py-2">{{ $history->contractItem?->contract?->contract_number ?? '—' }}</td><td class="px-3 py-2">{{ $history->contractItem?->formation?->name ?? 'Formação removida' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-gray-500">Nenhuma formação concluída neste período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->completedFormations->links() }}</div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Cursos concluídos</x-slot>
            @if ($this->canExport())
                <div class="mb-4 flex flex-wrap gap-2">
                    <x-filament::button wire:click="downloadReport('courses', 'csv')" icon="heroicon-o-arrow-down-tray" color="gray">Exportar CSV</x-filament::button>
                    <x-filament::button wire:click="downloadReport('courses', 'xlsx')" icon="heroicon-o-table-cells" color="gray">Exportar Excel</x-filament::button>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400"><tr><th class="px-3 py-2">Data</th><th class="px-3 py-2">Aluno</th><th class="px-3 py-2">Contrato</th><th class="px-3 py-2">Formação</th><th class="px-3 py-2">Curso</th><th class="px-3 py-2">Nota</th><th class="px-3 py-2">Origem</th></tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($this->completedCourses as $history)
                            <tr><td class="px-3 py-2">{{ $history->completed_at?->format('d/m/Y') ?? '—' }}</td><td class="px-3 py-2">{{ $history->contractCourse?->contractItem?->contract?->student?->name ?? 'Aluno removido' }}</td><td class="px-3 py-2">{{ $history->contractCourse?->contractItem?->contract?->contract_number ?? '—' }}</td><td class="px-3 py-2">{{ $history->contractCourse?->contractItem?->formation?->name ?? '—' }}</td><td class="px-3 py-2">{{ $history->contractCourse?->course?->name ?? 'Curso removido' }}</td><td class="px-3 py-2">{{ $history->grade ?? '—' }}</td><td class="px-3 py-2">{{ match($history->source) { 'ouro_moderno' => 'Ouro Moderno', 'aurosoft' => 'Aurosoft LMS', default => 'Manual' } }}</td></tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-4 text-gray-500">Nenhum curso concluído neste período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->completedCourses->links() }}</div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Conclusão estimada dos contratos ativos</x-slot>
            <x-slot name="description">Contratos com previsão vencida ou prevista dentro da janela escolhida.</x-slot>
            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Janela de previsão
                    <select wire:model.live="nearCompletionDays" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        @foreach ([7, 14, 30, 60, 90] as $days)<option value="{{ $days }}">Próximos {{ $days }} dias (inclui atrasados)</option>@endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Situação
                    <select wire:model.live="nearCompletionStatus" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="all">Atrasados e próximos</option><option value="upcoming">Próximos</option><option value="overdue">Atrasados</option>
                    </select>
                </label>
            </div>
            @if ($this->canExport())
                <div class="mb-4 flex flex-wrap gap-2">
                    <x-filament::button wire:click="downloadReport('near-completion', 'csv')" icon="heroicon-o-arrow-down-tray" color="gray">Exportar CSV</x-filament::button>
                    <x-filament::button wire:click="downloadReport('near-completion', 'xlsx')" icon="heroicon-o-table-cells" color="gray">Exportar Excel</x-filament::button>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400"><tr><th class="px-3 py-2">Previsão</th><th class="px-3 py-2">Situação</th><th class="px-3 py-2">Aluno</th><th class="px-3 py-2">Contrato</th><th class="px-3 py-2">Formações concluídas</th></tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($this->studentsNearCompletion as $contract)
                            @php($isOverdue = $contract->end_date?->isBefore(today()))
                            <tr><td class="px-3 py-2">{{ $contract->end_date?->format('d/m/Y') ?? '—' }}</td><td class="px-3 py-2"><x-filament::badge :color="$isOverdue ? 'danger' : 'warning'">{{ $isOverdue ? 'Atrasado '.$contract->end_date->diffInDays(today()).' dias' : 'Próximo' }}</x-filament::badge></td><td class="px-3 py-2">{{ $contract->student?->name ?? 'Aluno removido' }}</td><td class="px-3 py-2">{{ $contract->contract_number }}</td><td class="px-3 py-2">{{ $this->formationProgress($contract) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-4 text-gray-500">Nenhum contrato ativo nesta situação e janela.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->studentsNearCompletion->links() }}</div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
