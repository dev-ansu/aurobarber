<x-filament-panels::page>
    <x-report-document-header title="Progresso dos alunos no LMS" />
    @php($summary = $this->summary())

    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Buscar aluno, contrato ou curso</span><input wire:model.live.debounce.400ms="search" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Plataforma LMS</span><select wire:model.live="provider" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Todos os LMS</option><option value="aurosoft">Aurosoft LMS</option><option value="ouro_moderno">Ouro Moderno</option></select></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Progresso Aurosoft</span><select wire:model.live="progressStatus" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Todos</option><option value="not_started">Não iniciado</option><option value="in_progress">Em andamento</option><option value="completed">Concluído</option></select></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Consulta ao LMS</span><select wire:model.live="lmsConsultation" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Todos</option><option value="checked">Consultados</option><option value="pending">Não consultados</option></select></label>
                @if (auth()->user()?->can('Export:OuroModernoCourseStatusReport'))
                    <div class="flex items-end justify-end"><x-filament::button wire:click="exportReport" icon="heroicon-o-arrow-down-tray">Exportar CSV</x-filament::button></div>
                @endif
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([['Cursos no relatório', $summary['courses']], ['Concluídos', $summary['completed']], ['Em andamento', $summary['in_progress']], ['Não iniciados', $summary['not_started']], ['Média das médias', $summary['average'] === null ? '—' : number_format($summary['average'], 2, ',', '')], ['Aguardando consulta', $summary['not_checked']]] as [$label, $value])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $value }}</p></div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300"><tr><th class="px-4 py-3">Aluno / contrato</th><th class="px-4 py-3">Formação</th><th class="px-4 py-3">Curso</th><th class="px-4 py-3">Progresso Aurosoft</th><th class="px-4 py-3">Feedback do LMS</th><th class="px-4 py-3">Situação retornada pelo LMS</th><th class="px-4 py-3">Ações</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->rows() as $row)
                    <tr class="align-top text-gray-800 dark:text-gray-200"><td class="px-4 py-3"><div class="font-medium">{{ $row->contractItem?->contract?->student?->name ?? 'Aluno removido' }}</div><div class="text-xs text-gray-500">{{ $row->contractItem?->contract?->contract_number }}</div></td><td class="px-4 py-3">{{ $row->contractItem?->formation?->name }}</td><td class="px-4 py-3 font-medium">{{ $row->course?->name }}</td><td class="px-4 py-3">{{ match ($row->status) { 'completed' => 'Concluído', 'in_progress' => 'Em andamento', default => 'Não iniciado' } }}</td><td class="min-w-64 px-4 py-3"><x-ouro-moderno-progress-summary :contract-course="$row" /></td><td class="max-w-xl px-4 py-3">
                        @foreach ([
                            'Curso' => ['ouro_moderno_status', 'ouro_moderno_status_checked_at'],
                        ] as $label => [$statusField, $dateField])
                            @php($response = $row->{$statusField})
                            <details class="mb-2"><summary class="cursor-pointer text-primary-600 dark:text-primary-400">{{ $label }} · {{ $row->{$dateField}?->format('d/m/Y H:i') ?? 'não consultado' }}</summary><div class="mt-2">@if ($response)<x-ouro-moderno-status :data="$response" />@else<p class="text-xs text-gray-500">Sem resposta salva.</p>@endif</div></details>
                        @endforeach
                        <details class="mb-2"><summary class="cursor-pointer text-primary-600 dark:text-primary-400">Atividades (aulas e testes) · {{ collect([$row->ouro_moderno_lesson_status_checked_at, $row->ouro_moderno_test_status_checked_at])->filter()->max()?->format('d/m/Y H:i') ?? 'não consultado' }}</summary><div class="mt-2"><x-ouro-moderno-activities :lessons="$row->ouro_moderno_lesson_status" :tests="$row->ouro_moderno_test_status" :lesson-checked-at="$row->ouro_moderno_lesson_status_checked_at" :test-checked-at="$row->ouro_moderno_test_status_checked_at" /></div></details>
                    </td><td class="px-4 py-3">@if (auth()->user()?->can('Sync:OuroModernoStudentStatus'))<x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-path" wire:click="refreshSelectedCourse({{ $row->id }})">Importar para Aurosoft</x-filament::button>@endif</td></tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">Nenhum curso LMS encontrado com esses filtros.</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $this->rows()->links() }}</div>
        </section>
    </div>
</x-filament-panels::page>
