<x-filament-panels::page>
    <x-report-document-header title="Relatório de acompanhamento pedagógico" />
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>De</span><input type="date" wire:model.live="fromDate" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">@error('from')<span class="block text-danger-600">{{ $message }}</span>@enderror</label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Até</span><input type="date" wire:model.live="untilDate" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">@error('until')<span class="block text-danger-600">{{ $message }}</span>@enderror</label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Formação</span><select wire:model.live="formationId" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Todas</option>@foreach ($this->formations() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Sala</span><select wire:model.live="classroomId" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Todas</option>@foreach ($this->classrooms() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Dia da semana</span><select wire:model.live="weekday" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Todos</option>@foreach ($this->weekdays() as $day => $name)<option value="{{ $day }}">{{ $name }}</option>@endforeach</select></label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300"><span>Horário de início</span><select wire:model.live="startHour" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"><option value="">Todos</option>@foreach ($this->startHours() as $hour => $label)<option value="{{ $hour }}">{{ $label }}</option>@endforeach</select></label>
            </div>
            @error('start_hour')<p class="mt-2 text-sm text-danger-600">{{ $message }}</p>@enderror
            @if (auth()->user()?->can('Export:PedagogicalFollowUpReport'))<div class="mt-4 flex justify-end"><x-filament::button wire:click="exportReport" icon="heroicon-o-arrow-down-tray">Exportar alunos em acompanhamento</x-filament::button></div>@endif
        </section>

        @php($report = $this->reportData())

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([['Alunos acompanhados', $report['students_monitored'], 'primary'], ['Em atenção/risco', $report['students_at_risk'].' ('.number_format($report['students_at_risk_rate'], 1, ',', '').'%)', 'danger'], ['Aulas confirmadas', $report['confirmed_sessions'], 'info'], ['Presença geral', number_format($report['attendance_rate'], 1, ',', '').'%', 'success'], ['Faltas confirmadas', $report['absent_count'].' ('.number_format($report['absence_rate'], 1, ',', '').'%)', 'warning'], ['Chamadas pendentes', $report['unmarked_sessions'], 'gray']] as [$label, $value, $color])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p><p @class(['mt-2 text-2xl font-bold', 'text-primary-600 dark:text-primary-400' => $color === 'primary', 'text-danger-600 dark:text-danger-400' => $color === 'danger', 'text-info-600 dark:text-info-400' => $color === 'info', 'text-success-600 dark:text-success-400' => $color === 'success', 'text-warning-600 dark:text-warning-400' => $color === 'warning', 'text-gray-700 dark:text-gray-300' => $color === 'gray'])>{{ $value }}</p></div>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            @foreach ([['Por sala', $report['by_classroom']], ['Por dia da semana', $report['by_weekday']], ['Por horário', $report['by_time']]] as [$title, $rows])
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-950 dark:border-gray-800 dark:text-white">{{ $title }}</h2>
                    <div class="max-h-80 overflow-auto"><table class="w-full text-left text-sm"><thead class="sticky top-0 bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300"><tr><th class="px-4 py-3">Grupo</th><th class="px-4 py-3">Alunos</th><th class="px-4 py-3">Aulas</th><th class="px-4 py-3">Presença</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($rows as $row)<tr class="text-gray-800 dark:text-gray-200"><td class="px-4 py-3 font-medium">{{ $row['label'] }}</td><td class="px-4 py-3">{{ $row['students'] }}</td><td class="px-4 py-3">{{ $row['sessions'] }}</td><td class="px-4 py-3">{{ number_format($row['attendance_rate'], 1, ',', '') }}%</td></tr>@empty<tr><td colspan="4" class="px-4 py-7 text-center text-gray-500">Sem aulas marcadas no período.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="text-base font-semibold text-gray-950 dark:text-white">Saúde do acompanhamento dos alunos</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Somente chamadas marcadas entram nas taxas, para contratos ativos. Chamadas anteriores ainda sem marcação interrompem a contagem de sequência e aparecem no indicador de chamadas pendentes.</p></div>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300"><tr><th class="px-4 py-3">Aluno</th><th class="px-4 py-3">Contrato</th><th class="px-4 py-3">Formação</th><th class="px-4 py-3">Horários / salas</th><th class="px-4 py-3">Presença</th><th class="px-4 py-3">Faltas seguidas</th><th class="px-4 py-3">Última presença</th><th class="px-4 py-3">Indicador</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($report['students'] as $student)
                    <tr class="text-gray-800 dark:text-gray-200"><td class="px-4 py-3 font-medium">{{ $student['student'] }}</td><td class="px-4 py-3">{{ $student['contracts'] }}</td><td class="px-4 py-3">{{ $student['formations'] }}</td><td class="max-w-sm px-4 py-3">{{ $student['schedules'] }}</td><td class="whitespace-nowrap px-4 py-3">{{ number_format($student['attendance_rate'], 1, ',', '') }}% ({{ $student['present'] }}/{{ $student['sessions'] }})</td><td class="px-4 py-3">{{ $student['consecutive_absences'] }}</td><td class="whitespace-nowrap px-4 py-3">{{ $student['last_attended'] }}</td><td class="px-4 py-3"><span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-danger-100 text-danger-700 dark:bg-danger-400/10 dark:text-danger-300' => $student['health']['key'] === 'high_risk', 'bg-warning-100 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300' => $student['health']['key'] === 'attention', 'bg-success-100 text-success-700 dark:bg-success-400/10 dark:text-success-300' => $student['health']['key'] === 'healthy', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $student['health']['key'] === 'insufficient_data'])>{{ $student['health']['label'] }}</span></td></tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-10 text-center text-gray-500">Não há chamadas confirmadas com esses filtros. Chamadas ainda não marcadas ficam fora do cálculo.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-gray-50 p-5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900/60 dark:text-gray-300">
            <h2 class="font-semibold text-gray-900 dark:text-white">Critérios iniciais do indicador</h2>
            <p class="mt-2">Risco alto: três ou mais faltas consecutivas no período ou, com pelo menos três aulas marcadas, presença abaixo de 60%. Atenção: duas faltas consecutivas ou, com pelo menos três aulas marcadas, presença abaixo de 80%. Regular: presença de pelo menos 80%, sem duas faltas consecutivas. Com menos de três aulas e sem faltas consecutivas, os dados são insuficientes para calcular risco por percentual. O indicador não é diagnóstico médico e deve orientar contato da equipe pedagógica.</p>
        </section>
    </div>
</x-filament-panels::page>
