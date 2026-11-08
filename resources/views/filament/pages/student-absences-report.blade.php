<x-filament-panels::page>
    <div class="space-y-6">
        <!-- NAVEGAÇÃO ENTRE ABAS -->
        <div class="flex flex-wrap border-b border-gray-200 dark:border-white/10">
            <button
                type="button"
                wire:click="$set('activeTab', 'day')"
                @class([
                    'flex items-center gap-2 border-b-2 px-6 py-3 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'day',
                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' => $activeTab !== 'day',
                ])
            >
                <x-filament::icon icon="heroicon-o-calendar" class="h-5 w-5" />
                <span>Faltantes do Dia (Busca Ativa Imediata)</span>
            </button>

            <button
                type="button"
                wire:click="$set('activeTab', 'period')"
                @class([
                    'flex items-center gap-2 border-b-2 px-6 py-3 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'period',
                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' => $activeTab !== 'period',
                ])
            >
                <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5" />
                <span>Faltantes por Período (Prevenção de Evasão)</span>
            </button>

            <button
                type="button"
                wire:click="$set('activeTab', 'makeups')"
                @class([
                    'flex items-center gap-2 border-b-2 px-6 py-3 text-sm font-semibold transition',
                    'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $activeTab === 'makeups',
                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' => $activeTab !== 'makeups',
                ])
            >
                <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5" />
                <span>Reposições Agendadas (Liberação do Portal)</span>
            </button>
        </div>

        @if($activeTab === 'day')
            @php
                $dayRows = $this->dayAbsences();
                $recurrentCount = $dayRows->where('recent_absences_count', '>=', 3)->count();
            @endphp

            <!-- FILTROS DO DIA -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Data da Chamada</label>
                        <input
                            type="date"
                            wire:model.live="dayDate"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Sala / Espaço</label>
                        <select
                            wire:model.live="dayClassroomId"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Todas as salas</option>
                            @foreach($this->classrooms() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Horário de Início</label>
                        <select
                            wire:model.live="dayStartHour"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Todos os horários</option>
                            @foreach($this->startHours() as $hour => $label)
                                <option value="{{ $hour }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- CARDS DE RESUMO DO DIA -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-900/50 dark:bg-danger-950/20">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-danger-500/10 p-2 text-danger-600">
                            <x-filament::icon icon="heroicon-o-user-minus" class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase text-danger-700 dark:text-danger-400">Total de Faltas Hoje</div>
                            <div class="text-2xl font-black text-danger-950 dark:text-danger-100">{{ $dayRows->count() }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-900/50 dark:bg-warning-950/20">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-warning-500/10 p-2 text-warning-600">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase text-warning-700 dark:text-warning-400">Faltas Reincidentes (3+ no mês)</div>
                            <div class="text-2xl font-black text-warning-950 dark:text-warning-100">{{ $recurrentCount }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABELA DO DIA -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <tr>
                                <th class="py-3 px-4">Aluno</th>
                                <th class="py-3 px-4">Horário / Sala</th>
                                <th class="py-3 px-4">Curso / Formação</th>
                                <th class="py-3 px-4">Contato do Aluno</th>
                                <th class="py-3 px-4">Responsável</th>
                                <th class="py-3 px-4 text-center">Faltas (30 dias)</th>
                                <th class="py-3 px-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($dayRows as $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-950 dark:text-white">{{ $row['student_name'] }}</div>
                                        <div class="text-xs text-gray-500">Cód: {{ $row['student_code'] }} · Contrato: {{ $row['contract_number'] }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $row['time'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $row['classroom'] }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                        {{ $row['course'] }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="space-y-1">
                                            @forelse($row['student_contacts'] as $contact)
                                                <div class="flex items-center gap-1.5 text-xs">
                                                    <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $contact['is_primary'] ? 'bg-primary-100 text-primary-800 dark:bg-primary-950/60 dark:text-primary-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                                        {{ $contact['type'] }}
                                                    </span>
                                                    @if($contact['is_whatsapp'] && $contact['clean'])
                                                        <a
                                                            href="https://wa.me/55{{ $contact['clean'] }}?text=Ol%C3%A1%20{{ urlencode($row['student_name']) }},%20notamos%20sua%20falta%20na%20aula%20de%20hoje.%20Est%C3%A1%20tudo%20bem?%20Entre%20em%20contato%20conosco%20para%20repor%20seu%20conte%C3%BAdo!"
                                                            target="_blank"
                                                            class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                                        >
                                                            <span>{{ $contact['value'] }}</span>
                                                        </a>
                                                    @elseif($contact['is_email'])
                                                        <a href="mailto:{{ $contact['value'] }}" class="text-sky-600 hover:underline dark:text-sky-400">
                                                            {{ $contact['value'] }}
                                                        </a>
                                                    @else
                                                        <span class="text-gray-800 dark:text-gray-200">{{ $contact['value'] }}</span>
                                                    @endif
                                                </div>
                                            @empty
                                                @if($row['student_phone'])
                                                    <div class="text-xs text-gray-800 dark:text-gray-200">{{ $row['student_phone'] }}</div>
                                                @else
                                                    <span class="text-xs italic text-gray-400">Sem contatos cadastrados</span>
                                                @endif
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($row['responsible_name'])
                                            <div class="text-xs font-medium text-gray-900 dark:text-gray-100">{{ $row['responsible_name'] }}</div>
                                            @if($row['clean_resp_phone'])
                                                <a
                                                    href="https://wa.me/55{{ $row['clean_resp_phone'] }}?text=Ol%C3%A1,%20somos%20da%20escola.%20Gostar%C3%ADamos%20de%20informar%20que%20o(a)%20aluno(a)%20{{ urlencode($row['student_name']) }}%20n%C3%A3o%20compareceu%20%C3%A0%20aula%20de%20hoje."
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:underline"
                                                >
                                                    <span>WhatsApp: {{ $row['responsible_phone'] }}</span>
                                                </a>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($row['recent_absences_count'] >= 3)
                                            <span class="inline-flex items-center rounded-full bg-danger-100 px-2.5 py-0.5 text-xs font-bold text-danger-800 dark:bg-danger-900/40 dark:text-danger-300">
                                                {{ $row['recent_absences_count'] }} faltas
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                                {{ $row['recent_absences_count'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($row['contract_id'])
                                            <button
                                                type="button"
                                                wire:click="openMakeupModal({{ $row['contract_id'] }}, @js($row['student_name']), {{ $row['attendance_id'] }})"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-2.5 py-1.5 text-xs font-bold text-primary-700 hover:bg-primary-100 dark:bg-primary-950/50 dark:text-primary-300 dark:hover:bg-primary-900/60"
                                            >
                                                📅 Agendar Reposição
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-sm text-gray-500">
                                        Nenhuma falta registrada para os filtros selecionados nesta data.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @elseif($activeTab === 'period')
            @php
                $periodRows = $this->periodAbsences();
                $criticalCount = $periodRows->where('risk_color', 'danger')->count();
            @endphp

            <!-- FILTROS DO PERÍODO -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Data Inicial</label>
                        <input
                            type="date"
                            wire:model.live="fromDate"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Data Final</label>
                        <input
                            type="date"
                            wire:model.live="untilDate"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Sala</label>
                        <select
                            wire:model.live="periodClassroomId"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Todas as salas</option>
                            @foreach($this->classrooms() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Formação</label>
                        <select
                            wire:model.live="periodFormationId"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Todas as formações</option>
                            @foreach($this->formations() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">Mínimo de Faltas</label>
                        <select
                            wire:model.live="minAbsences"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="1">1 ou mais faltas</option>
                            <option value="2">2 ou mais faltas</option>
                            <option value="3">3 ou mais faltas</option>
                            <option value="5">5 ou mais faltas</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- CARDS DE RESUMO DO PERÍODO & EXPORTAÇÃO -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-xs font-semibold uppercase text-gray-500">Alunos com Faltas no Período</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white">{{ $periodRows->count() }}</div>
                    </div>

                    <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-900/40 dark:bg-danger-950/20">
                        <div class="text-xs font-semibold uppercase text-danger-700 dark:text-danger-400">Em Risco Crítico de Evasão</div>
                        <div class="text-2xl font-black text-danger-900 dark:text-danger-100">{{ $criticalCount }}</div>
                    </div>
                </div>

                <div>
                    <x-filament::button wire:click="exportPeriodReport" icon="heroicon-o-arrow-down-tray" color="gray">
                        Exportar CSV
                    </x-filament::button>
                </div>
            </div>

            <!-- TABELA DO PERÍODO -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <tr>
                                <th class="py-3 px-4">Aluno</th>
                                <th class="py-3 px-4">Curso / Formação</th>
                                <th class="py-3 px-4 text-center">Faltas</th>
                                <th class="py-3 px-4 text-center">Presenças</th>
                                <th class="py-3 px-4 text-center">Total Aulas</th>
                                <th class="py-3 px-4 text-center">% de Infrequência</th>
                                <th class="py-3 px-4 text-center">Status de Risco</th>
                                <th class="py-3 px-4">Contato / Busca Ativa</th>
                                <th class="py-3 px-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($periodRows as $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-950 dark:text-white">{{ $row['student_name'] }}</div>
                                        <div class="text-xs text-gray-500">Cód: {{ $row['student_code'] }} · Contrato: {{ $row['contract_number'] }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                        {{ $row['course'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-danger-600">
                                        {{ $row['absent_count'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-semibold text-emerald-600">
                                        {{ $row['present_count'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center text-gray-500">
                                        {{ $row['total_sessions'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white">
                                        {{ $row['absence_rate'] }}%
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($row['risk_color'] === 'danger')
                                            <span class="inline-flex items-center rounded-full bg-danger-100 px-2.5 py-0.5 text-xs font-bold text-danger-800 dark:bg-danger-900/40 dark:text-danger-300">
                                                {{ $row['risk_level'] }}
                                            </span>
                                        @elseif($row['risk_color'] === 'warning')
                                            <span class="inline-flex items-center rounded-full bg-warning-100 px-2.5 py-0.5 text-xs font-bold text-warning-800 dark:bg-warning-900/40 dark:text-warning-300">
                                                {{ $row['risk_level'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">
                                                {{ $row['risk_level'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="space-y-1">
                                            @forelse($row['student_contacts'] as $contact)
                                                <div class="flex items-center gap-1.5 text-xs">
                                                    <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $contact['is_primary'] ? 'bg-primary-100 text-primary-800 dark:bg-primary-950/60 dark:text-primary-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                                        {{ $contact['type'] }}
                                                    </span>
                                                    @if($contact['is_whatsapp'] && $contact['clean'])
                                                        <a
                                                            href="https://wa.me/55{{ $contact['clean'] }}?text=Ol%C3%A1%20{{ urlencode($row['student_name']) }},%20somos%20da%20secretaria%20pedag%C3%B3gica.%20Gostar%C3%ADamos%20de%20conversar%20sobre%20sua%20frequ%C3%AAncia%20nas%20aulas!"
                                                            target="_blank"
                                                            class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                                        >
                                                            <span>{{ $contact['value'] }}</span>
                                                        </a>
                                                    @elseif($contact['is_email'])
                                                        <a href="mailto:{{ $contact['value'] }}" class="text-sky-600 hover:underline dark:text-sky-400">
                                                            {{ $contact['value'] }}
                                                        </a>
                                                    @else
                                                        <span class="text-gray-800 dark:text-gray-200">{{ $contact['value'] }}</span>
                                                    @endif
                                                </div>
                                            @empty
                                                @if($row['student_phone'])
                                                    <a
                                                        href="https://wa.me/55{{ $row['clean_phone'] }}?text=Ol%C3%A1%20{{ urlencode($row['student_name']) }},%20somos%20da%20secretaria%20pedag%C3%B3gica.%20Gostar%C3%ADamos%20de%20conversar%20sobre%20sua%20frequ%C3%AAncia%20nas%20aulas!"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:underline"
                                                    >
                                                        <span>WhatsApp: {{ $row['student_phone'] }}</span>
                                                    </a>
                                                @else
                                                    <span class="text-xs text-gray-400">Sem contatos cadastrados</span>
                                                @endif
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($row['contract_id'])
                                            <button
                                                type="button"
                                                wire:click="openMakeupModal({{ $row['contract_id'] }}, @js($row['student_name']))"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-2.5 py-1.5 text-xs font-bold text-primary-700 hover:bg-primary-100 dark:bg-primary-950/50 dark:text-primary-300 dark:hover:bg-primary-900/60"
                                            >
                                                📅 Agendar Reposição
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-sm text-gray-500">
                                        Nenhum aluno atingiu o critério de faltas no período selecionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            @php
                $makeups = $this->scheduledMakeups();
            @endphp

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div>
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">Reposições de Aula Agendadas</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Durante a data e janela de horário agendada, o Portal do Aluno (<code class="font-mono">/aluno</code>) é desbloqueado automaticamente para o aluno realizar a aula.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold uppercase text-gray-500">Filtrar Status:</label>
                    <select
                        wire:model.live="makeupStatusFilter"
                        class="rounded-lg border-gray-300 py-1.5 px-3 text-xs font-semibold shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="scheduled">Agendadas</option>
                        <option value="completed">Concluídas</option>
                        <option value="cancelled">Canceladas</option>
                        <option value="">Todas</option>
                    </select>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <tr>
                                <th class="py-3 px-4">Aluno</th>
                                <th class="py-3 px-4">Data da Reposição</th>
                                <th class="py-3 px-4">Horário Liberado</th>
                                <th class="py-3 px-4">Sala / Laboratório</th>
                                <th class="py-3 px-4">Observações</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($makeups as $mk)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-950 dark:text-white">{{ $mk->contract?->student?->name ?? 'Aluno' }}</div>
                                        <div class="text-xs text-gray-500">Contrato: {{ $mk->contract?->contract_number }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $mk->makeup_date?->format('d/m/Y') }}
                                    </td>
                                    <td class="py-3 px-4 font-mono text-xs font-bold text-primary-600 dark:text-primary-400">
                                        {{ substr((string) $mk->start_hour, 0, 5) }} às {{ substr((string) $mk->end_hour, 0, 5) }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                        {{ $mk->classroom?->name ?? 'Qualquer laboratório' }}
                                    </td>
                                    <td class="py-3 px-4 text-xs text-gray-500">
                                        {{ $mk->notes ?: '—' }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($mk->status === 'scheduled')
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Agendada</span>
                                        @elseif($mk->status === 'completed')
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Concluída</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">Cancelada</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($mk->status === 'scheduled')
                                            <div class="inline-flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    wire:click="completeMakeupClass({{ $mk->id }})"
                                                    class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300"
                                                >
                                                    ✓ Concluir
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="cancelMakeupClass({{ $mk->id }})"
                                                    class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300"
                                                >
                                                    ✕ Cancelar
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-sm text-gray-500">
                                        Nenhuma reposição encontrada para o filtro selecionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($showMakeupModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-xs">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-white/10">
                        <div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white">📅 Agendar Reposição de Aula</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Aluno: <strong class="text-gray-800 dark:text-gray-200">{{ $makeupStudentName }}</strong></p>
                        </div>
                        <button type="button" wire:click="$set('showMakeupModal', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Data da Reposição</label>
                                <input
                                    type="date"
                                    wire:model="makeupDate"
                                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Sala / Laboratório</label>
                                <select
                                    wire:model="makeupClassroomId"
                                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="">Qualquer sala</option>
                                    @foreach($this->classrooms() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Horário Inicial</label>
                                <input
                                    type="time"
                                    wire:model="makeupStartHour"
                                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Horário Final</label>
                                <input
                                    type="time"
                                    wire:model="makeupEndHour"
                                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Observação / Conteúdo a Repor</label>
                            <textarea
                                wire:model="makeupNotes"
                                rows="2"
                                placeholder="Ex: Reposição referente à falta da aula de Excel..."
                                class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-3 text-xs text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
                            💡 <strong>Liberação Automática:</strong> O Portal do Aluno será liberado automaticamente na data e janela de horário escolhida acima, mesmo fora da escala semanal fixa do aluno.
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 border-t border-gray-100 pt-4 dark:border-white/10">
                        <x-filament::button color="gray" wire:click="$set('showMakeupModal', false)">
                            Cancelar
                        </x-filament::button>
                        <x-filament::button color="primary" wire:click="saveMakeupClass" icon="heroicon-o-check">
                            Confirmar Reposição
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
