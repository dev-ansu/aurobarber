<x-filament-panels::page>
    @php
        $rows = $this->agendaRows;
        $isFutureDate = \Illuminate\Support\Carbon::parse($this->date)->isFuture();
        $groups = $rows->groupBy(fn ($row): string => $this->rowStartHour($row));
        $summary = $this->attendanceSummary($rows);
    @endphp

    <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-950 dark:text-white">Agenda de alunos</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ \Illuminate\Support\Carbon::parse($this->date)->translatedFormat('l, d \d\e F \d\e Y') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if (\App\Filament\Resources\StudentAttendances\StudentAttendanceResource::canViewAny())
                    <a
                        href="{{ \App\Filament\Resources\StudentAttendances\StudentAttendanceResource::getUrl('index') }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/15 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10"
                    >
                        <x-filament::icon icon="heroicon-m-table-cells" class="h-4 w-4" />
                        <span>Histórico Geral / Exportar</span>
                    </a>
                @endif
                <a
                    href="{{ url('/' . (\Filament\Facades\Filament::getTenant()?->id ?? session('filament_tenant_id') ?? 1) . '/classroom-live-monitor') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 shadow-sm hover:bg-primary-100 dark:bg-primary-950/50 dark:text-primary-300 border border-primary-500/30"
                    title="Abrir o Monitor de Laboratório ao vivo"
                >
                    <x-filament::icon icon="heroicon-m-computer-desktop" class="h-4 w-4" />
                    <span>Monitor de Laboratório</span>
                </a>
                <a
                    href="{{ route('admin.agenda.folha-presenca', ['date' => $this->date, 'classroom_id' => $this->classroomId, 'start_hour' => $this->startHour, 'school_id' => \Filament\Facades\Filament::getTenant()?->id]) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-800 shadow-sm hover:bg-gray-200 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
                >
                    <x-filament::icon icon="heroicon-m-printer" class="h-4 w-4" />
                    <span>Imprimir Folha de Chamada</span>
                </a>
                @if (\Illuminate\Support\Carbon::parse($this->date)->isToday())
                    <x-filament::button wire:click="generateTodayAttendance" icon="heroicon-o-bolt" color="warning">
                        Gerar chamadas
                    </x-filament::button>
                @endif
                <x-heroicon-o-calendar-days class="h-10 w-10 text-primary-600" />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                <span class="mb-1 block">Data</span>
                <input type="date" wire:model.live="date" class="fi-input-input block w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10">
                @error('date') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                <span class="mb-1 block">Sala</span>
                <select wire:model.live="classroomId" class="fi-select-input block w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10">
                    <option value="">Todas as salas</option>
                    @foreach ($this->classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                    @endforeach
                </select>
                @error('classroomId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                <span class="mb-1 block">Buscar aluno</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Digite o nome..." class="fi-input-input block w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:placeholder:text-gray-500">
                @error('search') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="mt-6">
            <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Horário</span>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="$set('startHour', '')" @class([
                    'rounded-xl px-4 py-2 text-sm font-medium',
                    'bg-primary-600 text-white' => $this->startHour === '',
                    'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' => $this->startHour !== '',
                ])>
                    Todos
                </button>
                @foreach ($this->availableHours as $hour => $label)
                    <button type="button" wire:click="$set('startHour', '{{ $hour }}')" @class([
                        'rounded-xl px-4 py-2 text-sm font-medium',
                        'bg-primary-600 text-white' => $this->startHour === $hour,
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/15' => $this->startHour !== $hour,
                    ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @error('startHour') <span class="mt-2 block text-xs text-danger-600">{{ $message }}</span> @enderror
        </div>
    </div>

    @if ($isFutureDate)
        <div class="mb-4 rounded-lg bg-info-50 px-4 py-3 text-sm text-info-700 dark:bg-info-500/10 dark:text-info-400">
            Esta é uma data futura — exibindo a previsão com base nos horários cadastrados. As chamadas são geradas no dia da aula.
        </div>
    @elseif (! $this->hasSessionsForSelectedDate)
        <div class="mb-4 rounded-lg bg-warning-50 px-4 py-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
            @if (\Illuminate\Support\Carbon::parse($this->date)->isToday())
                A chamada de hoje ainda não foi gerada (ou não há horários ativos neste dia).
            @else
                A chamada desta data ainda não foi gerada (ou não havia horários ativos). Chamadas passadas não são criadas retroativamente.
            @endif
        </div>
    @else
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-xl bg-white p-5 text-center shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $rows->count() }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Agendados</div>
            </div>
            <div class="rounded-xl bg-white p-5 text-center shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $rows->where('status', 'present')->count() }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Presentes</div>
            </div>
            <div class="rounded-xl bg-white p-5 text-center shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $rows->where('status', 'absent')->count() }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Faltas</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold {{ $summary['text'] }}">{{ $summary['percentage'] }}%</span>
                    <span>{{ $summary['icon'] }}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $summary['label'] }} · Presença</div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full rounded-full transition-all duration-500 {{ $summary['bar'] }}" style="width: {{ $summary['percentage'] }}%"></div>
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10 dark:ring-white/10">
        <div class="space-y-8 p-4">
            @forelse ($groups as $hour => $students)
                @php
                    $slotSummary = $this->attendanceSummary($students);
                    $firstStudent = $students->first();
                    $endHour = $this->rowEndHour($firstStudent);
                @endphp
                <section>
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-500/10">
                                <x-heroicon-o-clock class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-950 dark:text-white">{{ $hour }} – {{ $endHour }}</h3>
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ $students->count() }} {{ $students->count() === 1 ? 'aluno(a)' : 'alunos(as)' }}
                                    </span>
                                    @unless ($isFutureDate)
                                        <span class="font-medium {{ $slotSummary['text'] }}">
                                            {{ $slotSummary['icon'] }} {{ $slotSummary['percentage'] }}% · {{ $slotSummary['label'] }}
                                        </span>
                                    @endunless
                                </div>
                            </div>
                        </div>
                        @if ($this->startHour === '')
                            <button
                                type="button"
                                wire:click="$set('startHour', '{{ $hour }}')"
                                class="rounded-xl bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 transition hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/20"
                            >
                                Ver turma
                            </button>
                        @endif
                    </div>

                    <div class="space-y-3">
                        @foreach ($students as $row)
                            @php
                                $isPreview = $row instanceof \App\Models\StudentSchedule;
                                $status = $isPreview ? 'scheduled' : $row->status;
                                $student = $this->studentRecord($row);
                                $studentName = $student->name;
                                $contract = $this->contractRecord($row);
                            @endphp
                            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex min-w-0 items-start gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 text-lg font-bold text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($studentName, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-semibold text-gray-950 dark:text-white">{{ $studentName }}</h4>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $this->formationName($row) }} <span aria-hidden="true">·</span> {{ $this->rowClassroom($row) }}
                                            </p>
                                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                                    Contrato: {{ $contract->contract_number }}
                                                </span>
                                                @if (auth()->user()?->can('view', $contract))
                                                    <a
                                                        href="{{ \App\Filament\Resources\Contracts\ContractsResource::getUrl('view', ['record' => $contract]) }}"
                                                        class="font-medium text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-400"
                                                    >
                                                        Ver contrato
                                                    </a>
                                                @endif
                                                @if (auth()->user()?->can('view', $student))
                                                    <a
                                                        href="{{ \App\Filament\Resources\People\PersonResource::getUrl('view', ['record' => $student]) }}"
                                                        class="font-medium text-gray-600 hover:text-gray-800 hover:underline dark:text-gray-400 dark:hover:text-gray-200"
                                                    >
                                                        Ver dados completos
                                                    </a>
                                                @endif
                                            </div>

                                            @php
                                                $lms = $this->studentLmsProgress($row);
                                                $canSyncLms = auth()->user()?->can('Sync:OuroModernoStudentStatus') ?? false;
                                            @endphp

                                            @if ($lms)
                                                <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20">
                                                        <x-heroicon-m-book-open class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                                                        <span>Curso: <strong class="font-semibold">{{ $lms['course_name'] }}</strong></span>
                                                        @if ($lms['status_label'])
                                                            <span class="text-[10px] opacity-75">({{ $lms['status_label'] }})</span>
                                                        @endif
                                                    </span>

                                                    @if ($lms['current_lesson'] !== null)
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/20">
                                                            <x-heroicon-m-academic-cap class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                                            <span>Aula atual: <strong class="font-semibold">{{ $lms['current_lesson'] }}</strong></span>
                                                            @if ($lms['total_lessons'] > 0)
                                                                <span class="text-[10px] opacity-75">de {{ $lms['total_lessons'] }}</span>
                                                            @endif
                                                        </span>
                                                    @endif

                                                    @if ($lms['average'] !== null)
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">
                                                            <x-heroicon-m-chart-bar class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                                            <span>Média: <strong class="font-semibold">{{ number_format($lms['average'], 1, ',', '.') }}</strong></span>
                                                        </span>
                                                    @endif

                                                    @if ($canSyncLms && $lms['has_lms_id'] && $student->ouro_moderno_cod_int)
                                                        <button
                                                            type="button"
                                                            wire:click="syncStudentLms({{ $contract->id }})"
                                                            wire:loading.attr="disabled"
                                                            title="Sincronizar progresso deste aluno com o LMS"
                                                            class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-primary-500/20 dark:hover:text-primary-300"
                                                        >
                                                            <x-heroicon-m-arrow-path class="h-3.5 w-3.5 text-primary-600 dark:text-primary-400" wire:loading.class="animate-spin" />
                                                            <span>Sincronizar LMS</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                            <button
                                                type="button"
                                                wire:click="toggleContacts({{ $student->id }})"
                                                class="mt-3 inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/15"
                                            >
                                                <x-heroicon-o-phone class="h-4 w-4" />
                                                Contatos
                                                <x-heroicon-o-chevron-down @class([
                                                    'h-4 w-4 transition-transform',
                                                    'rotate-180' => $this->expandedStudentId === $student->id,
                                                ]) />
                                            </button>
                                            @if ($this->expandedStudentId === $student->id)
                                                <div class="mt-4 rounded-xl border border-gray-200 bg-white/50 p-4 backdrop-blur-sm dark:border-white/10 dark:bg-gray-900/50">
                                                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                        @forelse ($student->contacts as $contact)
                                                            <div class="rounded-lg bg-gray-50/80 p-3 ring-1 ring-gray-900/5 dark:bg-white/5 dark:ring-white/10">
                                                                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                                    {{ $contact->type->getLabel() }}
                                                                </dt>
                                                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                                                    {{ $contact->value }}
                                                                </dd>
                                                            </div>
                                                        @empty
                                                            <div class="col-span-full py-2 text-center text-xs text-gray-400">
                                                                Nenhum contato cadastrado.
                                                            </div>
                                                        @endforelse
                                                    </dl>
                                                </div>
                                            @endif
                                            @if (! $isPreview && $row->note)
                                                <p class="mt-3 flex items-start gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-400">
                                                    <x-heroicon-o-chat-bubble-left-ellipsis class="mt-0.5 h-4 w-4 shrink-0" />
                                                    {{ $row->note }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                        @if ($isPreview)
                                            <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                                ⚪ Agendado
                                            </span>
                                        @else
                                            <div class="flex flex-col gap-1 items-start">
                                                <span @class([
                                                    'w-fit rounded-full px-3 py-1 text-xs font-medium',
                                                    'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $status === 'present',
                                                    'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => $status === 'absent',
                                                ])>
                                                    {{ $status === 'present' ? '🟢 Presente' : '🔴 Falta' }}
                                                </span>

                                                @if ($status === 'present')
                                                    @if ($row->marking_source === 'kiosk')
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 ring-1 ring-inset ring-emerald-600/20" title="Validado pelo Totem de Autoatendimento">
                                                            <x-heroicon-m-device-phone-mobile class="h-3 w-3 text-emerald-600" />
                                                            Totem (Auditado)
                                                        </span>
                                                    @elseif ($row->marking_source === 'staff')
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300" title="Lançado manualmente pelo educador">
                                                            <x-heroicon-m-pencil-square class="h-3 w-3 text-slate-500" />
                                                            Manual
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                            <label class="flex flex-col gap-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Situação
                                                <select wire:model="attendanceEdits.{{ $row->id }}.status" class="fi-select-input rounded-lg border-none bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                                                    <option value="absent">Falta</option>
                                                    <option value="present">Presença</option>
                                                </select>
                                            </label>
                                            <label class="flex flex-col gap-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Observação
                                                <input type="text" wire:model="attendanceEdits.{{ $row->id }}.note" maxlength="1000" placeholder="{{ $row->marked_at ? 'Obrigatória ao alterar situação' : 'Opcional' }}" class="fi-input-input rounded-lg border-none bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                                            </label>
                                            <x-filament::button size="sm" wire:click="saveAttendance({{ $row->id }})">
                                                Salvar
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </div>
                                @if (! $isPreview)
                                    @error("attendanceEdits.{$row->id}.status")
                                        <p class="mt-2 text-xs text-danger-600">{{ $message }}</p>
                                    @enderror
                                    @error("attendanceEdits.{$row->id}.note")
                                        <p class="mt-2 text-xs text-danger-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-2xl bg-white p-8 text-center text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                    {{ $isFutureDate ? 'Nenhum aluno agendado nesta data.' : 'Nenhum aluno encontrado.' }}
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
