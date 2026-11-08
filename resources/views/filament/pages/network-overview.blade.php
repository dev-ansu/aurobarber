<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filtro de Período, Abas de Visão e Ações da Rede --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    Visão 360º Consolidada de Todas as Unidades ({{ $totals['total_schools'] ?? 0 }} escolas)
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Acompanhe o desempenho comercial, financeiro, pedagógico e de retenção de cada escola em tempo real.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ \App\Filament\Pages\SchoolMessenger::getUrl() }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3.5 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300"
                >
                    <x-filament::icon icon="heroicon-m-chat-bubble-left-right" class="h-4 w-4" />
                    Comunicação da Rede
                </a>

                @if(($totals['total_schools'] ?? 0) > 1)
                    <button
                        type="button"
                        wire:click="openQuickTransfer"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-500"
                    >
                        <x-filament::icon icon="heroicon-m-arrows-right-left" class="h-4 w-4" />
                        Transferir Aluno entre Unidades
                    </button>
                @endif

                <div class="flex items-center gap-2">
                    <label for="period-select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Período:
                    </label>
                    <select
                        id="period-select"
                        wire:model.live="period"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="current_month">Mês Atual ({{ now()->translatedFormat('F/Y') }})</option>
                        <option value="last_month">Mês Anterior ({{ now()->subMonthNoOverflow()->translatedFormat('F/Y') }})</option>
                        <option value="current_year">Ano Atual ({{ now()->format('Y') }})</option>
                        <option value="all_time">Todo o Histórico</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Modal de Transferência Rápida entre Unidades --}}
        @if($showQuickTransferModal)
            @php
                $allActiveContracts = \App\Models\Contract::query()
                    ->withoutGlobalScopes()
                    ->with(['student', 'school'])
                    ->whereIn('status', ['active', 'draft', 'suspended'])
                    ->orderByDesc('id')
                    ->limit(200)
                    ->get();
                $allSchools = \App\Models\School::query()->withoutGlobalScopes()->orderBy('id')->get();
            @endphp
            <div class="rounded-xl border-2 border-primary-500/40 bg-white p-6 shadow-md dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            🔄 Transferência Rápida de Matrícula entre Unidades
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Transfere o contrato, histórico financeiro, progresso de aulas LMS e libera a vaga de turma na unidade de origem.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('showQuickTransferModal', false)"
                        class="text-sm font-medium text-gray-400 hover:text-gray-600"
                    >
                        ✕ Fechar
                    </button>
                </div>

                <form wire:submit="executeQuickTransfer" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                            Contrato / Aluno
                        </label>
                        <select
                            wire:model="transferContractId"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Selecione o contrato...</option>
                            @foreach($allActiveContracts as $c)
                                <option value="{{ $c->id }}">
                                    #{{ $c->contract_number }} — {{ $c->student?->name ?? 'Aluno' }} (Atual: {{ $c->school?->getFilamentName() ?? '—' }})
                                </option>
                            @endforeach
                        </select>
                        @error('transferContractId')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                            Unidade de Destino
                        </label>
                        <select
                            wire:model="transferTargetSchoolId"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Selecione a unidade de destino...</option>
                            @foreach($allSchools as $s)
                                <option value="{{ $s->id }}">{{ $s->getFilamentName() }}</option>
                            @endforeach
                        </select>
                        @error('transferTargetSchoolId')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                            Motivo / Observação
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                wire:model="transferReason"
                                placeholder="Ex: Mudança de endereço do aluno"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            <button
                                type="submit"
                                class="shrink-0 rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-500"
                            >
                                Confirmar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        {{-- Cards de Totais Consolidados Financeiros e Comerciais da Rede --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Alunos Ativos</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($totals['active_students'] ?? 0, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-primary-600 dark:text-primary-400">
                    +{{ number_format($totals['new_enrollments'] ?? 0, 0, ',', '.') }} matrículas ({{ number_format($totals['conversion_rate'] ?? 0, 1, ',', '.') }}% conv.)
                </p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Receitas (Período)</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    R$ {{ number_format($totals['revenue'] ?? 0, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Parcelas recebidas</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Despesas (Período)</p>
                <p class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
                    R$ {{ number_format($totals['expenses'] ?? 0, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Contas pagas</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Resultado Líquido</p>
                @php $net = $totals['net_balance'] ?? 0; @endphp
                <p class="mt-2 text-2xl font-bold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    R$ {{ number_format($net, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Receitas - Despesas</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Inadimplência Total</p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                    R$ {{ number_format($totals['overdue_amount'] ?? 0, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Parcelas em atraso</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo em Caixas</p>
                <p class="mt-2 text-2xl font-bold text-sky-600 dark:text-sky-400">
                    R$ {{ number_format($totals['cash_balance'] ?? 0, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Caixas abertos agora</p>
            </div>
        </div>

        {{-- Segunda Linha de Cards: Indicadores Pedagógicos, LMS e Retenção da Rede --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 shadow-sm dark:border-indigo-900/50 dark:bg-indigo-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">
                    🎓 Taxa de Presença (Rede)
                </p>
                <p class="mt-2 text-2xl font-bold text-indigo-900 dark:text-white">
                    {{ number_format($totals['attendance_rate'] ?? 0, 1, ',', '.') }}%
                </p>
                <p class="mt-1 text-xs text-indigo-600/80 dark:text-indigo-400">
                    {{ $totals['attendance_present'] ?? 0 }} presenças / {{ $totals['attendance_absent'] ?? 0 }} faltas
                </p>
            </div>

            <div class="rounded-xl border border-violet-100 bg-violet-50/40 p-4 shadow-sm dark:border-violet-900/50 dark:bg-violet-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-300">
                    💻 Aulas LMS Concluídas
                </p>
                <p class="mt-2 text-2xl font-bold text-violet-900 dark:text-white">
                    {{ number_format($totals['lms_completed_lessons'] ?? 0, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-violet-600/80 dark:text-violet-400">
                    Média de nota: {{ number_format($totals['lms_avg_score'] ?? 0, 1, ',', '.') }}% ({{ number_format($totals['lms_study_hours'] ?? 0, 1, ',', '.') }}h estudo)
                </p>
            </div>

            <div class="rounded-xl border border-rose-100 bg-rose-50/40 p-4 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-300">
                    🚨 Alunos em Risco (Evasão)
                </p>
                <p class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
                    {{ number_format($totals['at_risk_students'] ?? 0, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-rose-600/80 dark:text-rose-400">
                    Faltas recorrentes, atraso ou nota &lt; 70%
                </p>
            </div>

            <div class="rounded-xl border border-teal-100 bg-teal-50/40 p-4 shadow-sm dark:border-teal-900/50 dark:bg-teal-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-700 dark:text-teal-300">
                    🏆 Formados vs Cancelados
                </p>
                <p class="mt-2 text-2xl font-bold text-teal-900 dark:text-white">
                    {{ $totals['completed_contracts'] ?? 0 }}
                    <span class="text-sm font-normal text-gray-400">/</span>
                    <span class="text-lg font-semibold text-rose-600 dark:text-rose-400">{{ $totals['canceled_contracts'] ?? 0 }}</span>
                </p>
                <p class="mt-1 text-xs text-teal-600/80 dark:text-teal-400">
                    Concluídos vs Rescindidos no período
                </p>
            </div>

            <div class="rounded-xl border border-amber-100 bg-amber-50/40 p-4 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">
                    ⭐ Satisfação Média
                </p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                    {{ ($totals['avg_satisfaction'] ?? 0) > 0 ? number_format($totals['avg_satisfaction'], 1, ',', '.') . ' / 5.0' : '—' }}
                </p>
                <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-400">
                    Pesquisas e avaliações de aulas
                </p>
            </div>
        </div>

        {{-- Seletor de Abas da Tabela Comparativa --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 pb-2 dark:border-gray-700">
            <button
                type="button"
                wire:click="setTab('overview')"
                class="rounded-lg px-4 py-2 text-xs font-bold transition {{ $activeTab === 'overview' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                🌐 Visão 360º (Comercial + Pedagógico)
            </button>
            <button
                type="button"
                wire:click="setTab('pedagogical')"
                class="rounded-lg px-4 py-2 text-xs font-bold transition {{ $activeTab === 'pedagogical' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                🎓 Desempenho Pedagógico, LMS & Retenção
            </button>
            <button
                type="button"
                wire:click="setTab('financial')"
                class="rounded-lg px-4 py-2 text-xs font-bold transition {{ $activeTab === 'financial' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                💰 Detalhamento Financeiro & Caixa
            </button>
        </div>

        {{-- Tabela Comparativa por Escola --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    Comparativo Individual por Unidade Escolar
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Unidade</th>
                            <th class="px-4 py-3 text-center">Alunos Ativos</th>
                            @if($activeTab === 'overview' || $activeTab === 'financial')
                                <th class="px-4 py-3 text-center">Leads / Matrículas</th>
                                <th class="px-4 py-3 text-right">Receitas</th>
                                <th class="px-4 py-3 text-right">Despesas</th>
                                <th class="px-4 py-3 text-right">Resultado</th>
                                <th class="px-4 py-3 text-right">Inadimplência</th>
                            @endif
                            @if($activeTab === 'overview' || $activeTab === 'pedagogical')
                                <th class="px-4 py-3 text-center">Frequência</th>
                                <th class="px-4 py-3 text-center">Aulas LMS (Média)</th>
                                <th class="px-4 py-3 text-center">Alunos em Risco</th>
                                <th class="px-4 py-3 text-center">Formados / Canc.</th>
                                <th class="px-4 py-3 text-center">Satisfação</th>
                            @endif
                            @if($activeTab === 'financial')
                                <th class="px-4 py-3 text-right">Caixa Atual</th>
                            @endif
                            <th class="px-4 py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($schoolsData as $row)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $row['name'] }}</span>
                                        @if(($row['unread_messages_count'] ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700 dark:bg-red-900/50 dark:text-red-300" title="Mensagens não lidas na unidade">
                                                ✉️ {{ $row['unread_messages_count'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs font-normal text-gray-500">{{ $row['city'] }}</div>
                                </td>

                                <td class="px-4 py-3 text-center font-semibold">
                                    {{ $row['active_students'] }}
                                </td>

                                @if($activeTab === 'overview' || $activeTab === 'financial')
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-semibold text-primary-600 dark:text-primary-400">+{{ $row['new_enrollments'] }}</span>
                                        <span class="text-xs text-gray-400">/ {{ $row['leads_count'] ?? 0 }} leads ({{ number_format($row['conversion_rate'] ?? 0, 0) }}%)</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                        R$ {{ number_format($row['revenue'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-rose-600 dark:text-rose-400">
                                        R$ {{ number_format($row['expenses'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold {{ $row['net_balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        R$ {{ number_format($row['net_balance'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-amber-600 dark:text-amber-400">
                                        R$ {{ number_format($row['overdue_amount'], 2, ',', '.') }}
                                    </td>
                                @endif

                                @if($activeTab === 'overview' || $activeTab === 'pedagogical')
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ ($row['attendance_rate'] ?? 0) >= 75 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' }}">
                                            {{ number_format($row['attendance_rate'] ?? 0, 1, ',', '.') }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-semibold text-violet-700 dark:text-violet-300">{{ $row['lms_completed_lessons'] ?? 0 }}</span>
                                        <span class="text-xs text-gray-400">({{ number_format($row['lms_avg_score'] ?? 0, 0) }}%)</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if(($row['at_risk_students'] ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-900/50 dark:text-rose-300">
                                                ⚠️ {{ $row['at_risk_students'] }}
                                            </span>
                                        @else
                                            <span class="text-xs text-emerald-600 dark:text-emerald-400">0</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-semibold text-teal-600 dark:text-teal-400">{{ $row['completed_contracts'] ?? 0 }}</span>
                                        <span class="text-xs text-gray-400">/</span>
                                        <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $row['canceled_contracts'] ?? 0 }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if(($row['avg_satisfaction'] ?? 0) > 0)
                                            <span class="font-semibold text-amber-600 dark:text-amber-400">⭐ {{ number_format($row['avg_satisfaction'], 1, ',', '.') }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                @endif

                                @if($activeTab === 'financial')
                                    <td class="px-4 py-3 text-right font-medium text-sky-600 dark:text-sky-400">
                                        R$ {{ number_format($row['cash_balance'], 2, ',', '.') }}
                                    </td>
                                @endif

                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ url('/admin/' . $row['id']) }}"
                                        class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-950/50 dark:text-primary-300 dark:hover:bg-primary-900"
                                    >
                                        Acessar Escola →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-6 py-8 text-center text-gray-500">
                                    Nenhuma unidade escolar cadastrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
