<x-filament-panels::page>
    <x-report-document-header title="Relatório de contatos dos alunos" />

    @php
        $records = $this->records(50);
        $totalCount = $this->totalRecordsCount();
        $metrics = $this->metrics();
        $weekdaysMap = $this->weekdays();
    @endphp

    <div class="space-y-6">
        <!-- Filtros personalizados -->
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Filtros de consulta e exportação</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Personalize os filtros para listar e exportar os dados de contato dos alunos.</p>
                </div>
                <div class="flex items-center gap-3">
                    @if (auth()->user()?->can('Export:StudentContactsReport'))
                        <x-filament::button wire:click="exportReport" icon="heroicon-o-arrow-down-tray" color="primary">
                            Exportar CSV
                        </x-filament::button>
                    @endif
                    <x-filament::button wire:click="clearFilters" color="gray" icon="heroicon-o-arrow-path">
                        Limpar filtros
                    </x-filament::button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Situação contrato</span>
                    <select wire:model.live="contractStatus" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        @foreach ($this->contractStatuses() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Bairro</span>
                    <select wire:model.live="neighborhood" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Todos os bairros</option>
                        @foreach ($this->neighborhoods() as $n)
                            <option value="{{ $n }}">{{ $n }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Curso</span>
                    <select wire:model.live="courseId" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Todos os cursos</option>
                        @foreach ($this->courses() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
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
                    <span>Dia da aula</span>
                    <select wire:model.live="weekday" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Todos os dias</option>
                        @foreach ($this->weekdays() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Horário da aula</span>
                    <select wire:model.live="startHour" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Todos os horários</option>
                        @foreach ($this->startHours() as $hour => $label)
                            <option value="{{ $hour }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Sala de aula</span>
                    <select wire:model.live="classroomId" class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                        <option value="">Todas as salas</option>
                        @foreach ($this->classrooms() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Buscar aluno</span>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nome, CPF ou contrato..." class="h-10 w-full rounded-xl border-0 bg-gray-50 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                </label>
            </div>
        </section>

        <!-- Indicadores rápidos -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $metrics['total_contracts'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Contratos filtrados</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $metrics['total_students'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Alunos únicos</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $metrics['with_whatsapp'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Com WhatsApp</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $metrics['with_phone'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Com Telefone/Celular</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $metrics['with_responsible'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Com Responsável</div>
            </div>
        </div>

        <!-- Tabela de contatos -->
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div>
                    <h3 class="font-semibold text-gray-950 dark:text-white">Alunos e contatos</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Exibindo {{ $records->count() }} de {{ $totalCount }} contrato(s) encontrado(s). A exportação em CSV inclui todos os registros.
                    </p>
                </div>
                @if (auth()->user()?->can('Export:StudentContactsReport'))
                    <x-filament::button wire:click="exportReport" icon="heroicon-o-arrow-down-tray" color="gray" size="sm">
                        Exportar tudo ({{ $totalCount }})
                    </x-filament::button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Aluno</th>
                            <th class="px-4 py-3">Contatos do aluno</th>
                            <th class="px-4 py-3">Contrato</th>
                            <th class="px-4 py-3">Responsável</th>
                            <th class="px-4 py-3">Turma e horários</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($records as $contract)
                            @php
                                $student = $contract->student;
                                $contacts = $student?->contacts ?? collect();
                                $whatsapps = $contacts->where('type', \App\Enums\ContactTypeEnum::WhatsApp);
                                $phones = $contacts->whereIn('type', [\App\Enums\ContactTypeEnum::Celular, \App\Enums\ContactTypeEnum::Telefone]);
                                $emails = $contacts->where('type', \App\Enums\ContactTypeEnum::Email);
                                if ($emails->isEmpty() && filled($student?->email)) {
                                    $emails = collect([(object) ['value' => $student->email]]);
                                }

                                $responsible = $contract->responsible;
                                $respPerson = $responsible?->person;

                                $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique();
                                $schedules = $contract->items->flatMap(fn ($item) => $item->schedules)->filter();

                                $statusBadge = match ($contract->status) {
                                    'active' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                    'draft' => 'bg-gray-50 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                                    'suspended' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                                    'canceled' => 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400',
                                    'finished' => 'bg-info-50 text-info-700 dark:bg-info-500/10 dark:text-info-400',
                                    default => 'bg-gray-50 text-gray-700',
                                };

                                $statusLabel = match ($contract->status) {
                                    'active' => 'Ativo',
                                    'draft' => 'Rascunho',
                                    'suspended' => 'Suspenso',
                                    'canceled' => 'Cancelado',
                                    'finished' => 'Finalizado',
                                    default => $contract->status,
                                };
                            @endphp
                            <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/5">
                                <!-- Aluno -->
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $student?->name ?? '—' }}
                                    </div>
                                    @if ($student?->cpf)
                                        <div class="text-xs text-gray-500">CPF: {{ \App\Services\DataMaskingService::canViewCpf() ? $student->cpf : \App\Services\DataMaskingService::maskCpf($student->cpf) }}</div>
                                    @endif
                                    @php
                                        $primaryAddress = $student?->addresses->sortByDesc('is_primary')->first();
                                    @endphp
                                    @if ($primaryAddress?->neighborhood)
                                        <div class="text-xs font-semibold text-primary-600 dark:text-primary-400">📍 {{ $primaryAddress->neighborhood }}</div>
                                    @endif
                                    @if ($student?->birth_date)
                                        <div class="text-xs text-gray-400">Nasc: {{ $student->birth_date->format('d/m/Y') }}</div>
                                    @endif
                                </td>

                                <!-- Contatos -->
                                <td class="px-4 py-3 align-top">
                                    <div class="space-y-1">
                                        @php
                                            $canPhone = \App\Services\DataMaskingService::canViewPhone();
                                            $canEmail = \App\Services\DataMaskingService::canViewEmail();
                                        @endphp

                                        @foreach ($whatsapps as $w)
                                            @php
                                                $cleanWa = preg_replace('/[^0-9]/', '', $w->value);
                                                $displayWa = $canPhone ? $w->value : \App\Services\DataMaskingService::maskPhone($w->value);
                                            @endphp
                                            @if ($canPhone)
                                                <a href="https://wa.me/{{ str_starts_with($cleanWa, '55') ? $cleanWa : '55'.$cleanWa }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">
                                                    <x-heroicon-m-chat-bubble-oval-left class="h-3.5 w-3.5" />
                                                    <span>{{ $displayWa }}</span>
                                                </a>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500">
                                                    <x-heroicon-m-chat-bubble-oval-left class="h-3.5 w-3.5 text-gray-400" />
                                                    <span>{{ $displayWa }}</span>
                                                </span>
                                            @endif
                                            <br>
                                        @endforeach

                                        @foreach ($phones as $p)
                                            @php
                                                $displayPhone = $canPhone ? $p->value : \App\Services\DataMaskingService::maskPhone($p->value);
                                            @endphp
                                            @if ($canPhone)
                                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $p->value) }}" class="inline-flex items-center gap-1 text-xs text-gray-700 hover:underline dark:text-gray-300">
                                                    <x-heroicon-m-phone class="h-3.5 w-3.5 text-gray-400" />
                                                    <span>{{ $displayPhone }}</span>
                                                </a>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                                    <x-heroicon-m-phone class="h-3.5 w-3.5 text-gray-400" />
                                                    <span>{{ $displayPhone }}</span>
                                                </span>
                                            @endif
                                            <br>
                                        @endforeach

                                        @foreach ($emails as $e)
                                            @php
                                                $displayEmail = $canEmail ? $e->value : \App\Services\DataMaskingService::maskEmail($e->value);
                                            @endphp
                                            @if ($canEmail)
                                                <a href="mailto:{{ $e->value }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:underline dark:text-gray-400">
                                                    <x-heroicon-m-envelope class="h-3.5 w-3.5 text-gray-400" />
                                                    <span>{{ $displayEmail }}</span>
                                                </a>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                                    <x-heroicon-m-envelope class="h-3.5 w-3.5 text-gray-400" />
                                                    <span>{{ $displayEmail }}</span>
                                                </span>
                                            @endif
                                            <br>
                                        @endforeach

                                        @if ($contacts->isEmpty() && empty($student?->email))
                                            <span class="text-xs text-gray-400 italic">Sem contatos cadastrados</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Contrato -->
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ \App\Filament\Resources\Contracts\ContractsResource::getUrl('view', ['record' => $contract]) }}" class="font-mono text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                            {{ $contract->contract_number }}
                                        </a>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusBadge }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-400">
                                        Início: {{ $contract->start_date?->format('d/m/Y') ?? '—' }}
                                    </div>
                                </td>

                                <!-- Responsável -->
                                <td class="px-4 py-3 align-top">
                                    @if ($respPerson)
                                        <div class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $respPerson->name }}
                                        </div>
                                        @if ($responsible?->relationship)
                                            <div class="text-[11px] text-gray-500">{{ $responsible->relationship->value }}</div>
                                        @endif
                                        <div class="mt-1 space-y-0.5">
                                            @foreach ($respPerson->contacts as $rc)
                                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ $rc->type->getLabel() }}: {{ $rc->value }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>

                                <!-- Turma e Horários -->
                                <td class="px-4 py-3 align-top">
                                    @if ($formations->isNotEmpty())
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $formations->implode(', ') }}
                                        </div>
                                    @endif
                                    <div class="mt-1 space-y-0.5 text-xs text-gray-600 dark:text-gray-400">
                                        @if ($schedules->isNotEmpty())
                                            @foreach ($schedules as $sched)
                                                <div>
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $weekdaysMap[(string) $sched->weekday] ?? 'Dia ' . $sched->weekday }}</span>
                                                    · {{ $sched->formattedStartHour() }} às {{ $sched->formattedEndHour() }}
                                                    @if ($sched->classroom)
                                                        <span class="text-gray-400">({{ $sched->classroom->name }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400 italic">Sem horários agendados</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum aluno/contrato encontrado com os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
