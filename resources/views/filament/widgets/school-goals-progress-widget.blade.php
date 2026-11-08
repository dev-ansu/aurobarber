<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                        <x-heroicon-o-trophy class="h-5 w-5" />
                    </div>
                    <div>
                        <span class="text-base font-bold text-gray-950 dark:text-white">Metas & Desempenho Escolar</span>
                        <span class="ml-2 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                            {{ $monthName }} / {{ $year }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 font-medium">
                        <x-heroicon-m-calendar class="h-4 w-4" />
                        Dia {{ $currentDay }} de {{ $totalDays }} ({{ $monthElapsedPercent }}% do mês) • Faltam {{ $daysRemaining }} dias
                    </span>

                    <a
                        href="{{ route('filament.admin.resources.school-goals.index') }}"
                        class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                    >
                        <x-heroicon-m-cog-6-tooth class="h-3.5 w-3.5" />
                        Gerenciar Metas
                    </a>
                </div>
            </div>
        </x-slot>

        @if (! $hasGoal)
            <div class="rounded-2xl border border-dashed border-gray-300 p-6 text-center dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400 mb-3">
                    <x-heroicon-o-flag class="h-6 w-6" />
                </div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Nenhuma meta definida para {{ $monthName }} de {{ $year }}</h4>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Estabeleça metas de matrículas, novas vendas e arrecadação para acompanhar o crescimento da escola e engajar toda a equipe.
                </p>
                <div class="mt-4">
                    <a
                        href="{{ route('filament.admin.resources.school-goals.index') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-500 transition"
                    >
                        <x-heroicon-m-plus class="h-4 w-4" />
                        Definir Metas de {{ $monthName }}
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                <!-- CARD 1: MATRÍCULAS -->
                <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                <x-heroicon-o-user-plus class="h-4 w-4" />
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Novas Matrículas</span>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $enrollmentsPercent >= 100 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' }}">
                            {{ $enrollmentsPercent }}%
                        </span>
                    </div>

                    <div class="mt-4 flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl font-black text-gray-950 dark:text-white">{{ number_format($actualEnrollments, 0, ',', '.') }}</span>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">/ {{ number_format($targetEnrollments, 0, ',', '.') }}</span>
                        </div>
                        <span class="text-xs text-gray-400">alunos</span>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div
                            class="h-full rounded-full transition-all duration-500 {{ $enrollmentsPercent >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}"
                            style="width: {{ min(100, $enrollmentsPercent) }}%"
                        ></div>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        @if ($enrollmentsPercent >= 100)
                            <span class="text-emerald-600 font-bold dark:text-emerald-400">🎉 Meta superada!</span>
                        @else
                            <span>Faltam <strong>{{ $remainingEnrollments }}</strong> alunos</span>
                        @endif
                        <span class="text-gray-400">Ritmo: ~{{ $projectedEnrollments }} proj.</span>
                    </div>
                </div>

                <!-- CARD 2: FATURAMENTO (VENDAS) -->
                <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400">
                                <x-heroicon-o-banknotes class="h-4 w-4" />
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Vendas (Contratos)</span>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $salesPercent >= 100 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300' }}">
                            {{ $salesPercent }}%
                        </span>
                    </div>

                    <div class="mt-4 flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl font-black text-gray-950 dark:text-white">R$ {{ number_format($actualSales, 2, ',', '.') }}</span>
                        </div>
                        <span class="text-xs text-gray-400">Meta: R$ {{ number_format($targetSales, 2, ',', '.') }}</span>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div
                            class="h-full rounded-full transition-all duration-500 {{ $salesPercent >= 100 ? 'bg-emerald-500' : 'bg-violet-600' }}"
                            style="width: {{ min(100, $salesPercent) }}%"
                        ></div>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        @if ($salesPercent >= 100)
                            <span class="text-emerald-600 font-bold dark:text-emerald-400">🎯 Meta atingida!</span>
                        @else
                            <span>Restam <strong>R$ {{ number_format($remainingSales, 2, ',', '.') }}</strong></span>
                        @endif
                        <span class="text-gray-400">{{ $targetSales > 0 ? round(($actualSales / $targetSales) * 100) : 0 }}% fechado</span>
                    </div>
                </div>

                <!-- CARD 3: ARRECADAÇÃO (CAIXA / RECEBIDO) -->
                <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <x-heroicon-o-currency-dollar class="h-4 w-4" />
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Arrecadação (Caixa)</span>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $revenuePercent >= 100 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' }}">
                            {{ $revenuePercent }}%
                        </span>
                    </div>

                    <div class="mt-4 flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl font-black text-gray-950 dark:text-white">R$ {{ number_format($actualRevenue, 2, ',', '.') }}</span>
                        </div>
                        <span class="text-xs text-gray-400">Meta: R$ {{ number_format($targetRevenue, 2, ',', '.') }}</span>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div
                            class="h-full rounded-full transition-all duration-500 bg-emerald-500"
                            style="width: {{ min(100, $revenuePercent) }}%"
                        ></div>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        @if ($revenuePercent >= 100)
                            <span class="text-emerald-600 font-bold dark:text-emerald-400">💰 Caixa superado!</span>
                        @else
                            <span>A receber <strong>R$ {{ number_format($remainingRevenue, 2, ',', '.') }}</strong></span>
                        @endif
                        <span class="text-gray-400">Mensalidades</span>
                    </div>
                </div>
            </div>

            <!-- FUNIL COMERCIAL DO MÊS (INTERESSADOS -> VISITAS -> MATRÍCULAS) -->
            <div class="mt-4 rounded-2xl bg-gray-50/80 p-4 border border-gray-100 dark:bg-gray-800/40 dark:border-gray-800/80 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <span class="font-bold text-gray-800 dark:text-gray-200">🎯 Funil Comercial:</span>
                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                        <a
                            href="{{ route('filament.admin.resources.commercial-leads.index') }}"
                            class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:underline dark:text-blue-400"
                            title="Ver Interessados no CRM"
                        >
                            <x-heroicon-m-user-group class="h-3.5 w-3.5" />
                            <strong>{{ $leadsCount }}</strong> Interessados
                        </a>
                        <span class="text-gray-400">➔</span>
                        <a
                            href="{{ route('filament.admin.resources.commercial-visits.index') }}"
                            class="inline-flex items-center gap-1 font-semibold text-amber-600 hover:underline dark:text-amber-400"
                            title="Ver Visitas Agendadas/Realizadas"
                        >
                            <x-heroicon-m-building-office-2 class="h-3.5 w-3.5" />
                            <strong>{{ $visitsCount }}</strong> Visitas
                        </a>
                        <span class="text-gray-400">➔</span>
                        <span class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400">
                            <x-heroicon-m-academic-cap class="h-3.5 w-3.5" />
                            <strong>{{ $actualEnrollments }}</strong> Matrículas
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start md:self-auto">
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        Taxa de Conversão: {{ $conversionRate }}%
                    </span>
                    <a
                        href="{{ route('filament.admin.pages.commercial-leads-kanban') }}"
                        class="text-xs font-bold text-primary-600 hover:text-primary-500 underline"
                    >
                        Abrir Funil Kanban ➜
                    </a>
                </div>
            </div>

            <!-- SEÇÃO DE METAS PEDAGÓGICAS E RETENÇÃO ESCOLAR -->
            <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between pb-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                            <x-heroicon-m-academic-cap class="h-4 w-4" />
                        </div>
                        <h4 class="text-sm font-bold text-gray-950 dark:text-white">Metas Pedagógicas & Retenção de Alunos</h4>
                    </div>

                    <a
                        href="{{ route('filament.admin.pages.agenda') }}"
                        class="text-xs font-bold text-primary-600 hover:text-primary-500"
                    >
                        Abrir Chamada Diária ➜
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- CARD 1: ASSIDUIDADE / FREQUÊNCIA -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                    <x-heroicon-o-check-badge class="h-4 w-4" />
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Assiduidade Geral</span>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $attendanceGoalReached ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                {{ $actualAttendanceRate }}% / Meta: {{ $targetAttendanceRate }}%
                            </span>
                        </div>

                        <div class="mt-4 flex items-baseline justify-between">
                            <div>
                                <span class="text-2xl font-black text-gray-950 dark:text-white">{{ $actualAttendanceRate }}%</span>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">de presença</span>
                            </div>
                            <span class="text-xs {{ $attendanceGoalReached ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold' }}">
                                {{ $attendanceGoalReached ? '✓ Meta Atingida' : '⚠️ Atenção Frequência' }}
                            </span>
                        </div>

                        <!-- Progress bar -->
                        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div
                                class="h-full rounded-full transition-all duration-500 {{ $attendanceGoalReached ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                style="width: {{ min(100, $actualAttendanceRate) }}%"
                            ></div>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ number_format($presentAttendances, 0, ',', '.') }} presenças</span>
                            <span>{{ number_format($totalAttendances, 0, ',', '.') }} chamadas</span>
                        </div>
                    </div>

                    <!-- CARD 2: TETO DE CANCELAMENTOS / EVASÃO -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                                    <x-heroicon-o-shield-exclamation class="h-4 w-4" />
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Evasão & Cancelamentos</span>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $cancellationsSafe ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' }}">
                                {{ $cancellationsSafe ? '🟢 Sob Controle' : '🔴 Alerta Evasão' }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-baseline justify-between">
                            <div>
                                <span class="text-2xl font-black text-gray-950 dark:text-white">{{ $actualCancellations }}</span>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">/ Máx tolerado: {{ $targetMaxCancellations }}</span>
                            </div>
                            <span class="text-xs text-gray-400">contratos</span>
                        </div>

                        <!-- Progress bar inverso (evasão) -->
                        @php
                            $cancellationPercent = $targetMaxCancellations > 0 ? min(100, round(($actualCancellations / $targetMaxCancellations) * 100)) : ($actualCancellations > 0 ? 100 : 0);
                        @endphp
                        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div
                                class="h-full rounded-full transition-all duration-500 {{ $cancellationsSafe ? 'bg-emerald-500' : 'bg-rose-500' }}"
                                style="width: {{ $cancellationPercent }}%"
                            ></div>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $cancellationsSafe ? 'Dentro do teto previsto' : 'Acima do limite estipulado' }}</span>
                            <a href="{{ route('filament.admin.resources.contracts.index') }}" class="text-primary-600 hover:underline">Ver Alunos</a>
                        </div>
                    </div>

                    <!-- CARD 3: ACOMPANHAMENTOS DE RETENÇÃO PEDAGÓGICA -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                                    <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Resgate & Retenção</span>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $retentionInterviewsPercent >= 100 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300' }}">
                                {{ $retentionInterviewsPercent }}%
                            </span>
                        </div>

                        <div class="mt-4 flex items-baseline justify-between">
                            <div>
                                <span class="text-2xl font-black text-gray-950 dark:text-white">{{ $actualRetentionInterviews }}</span>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">/ {{ $targetRetentionInterviews }}</span>
                            </div>
                            <span class="text-xs text-gray-400">entrevistas</span>
                        </div>

                        <!-- Progress bar -->
                        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div
                                class="h-full rounded-full transition-all duration-500 bg-purple-600"
                                style="width: {{ min(100, $retentionInterviewsPercent) }}%"
                            ></div>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Reuniões com faltosos concluídas</span>
                            <a href="{{ route('filament.admin.pages.agenda-compromissos') }}" class="text-primary-600 hover:underline">Ver Agenda</a>
                        </div>
                    </div>
                </div>

                <!-- FAIXA DE AUDITORIA ANTI-FRAUDE DE FREQUÊNCIA -->
                <div class="mt-4 rounded-2xl bg-slate-50 p-4 border border-slate-200/80 dark:bg-gray-800/60 dark:border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                            <x-heroicon-m-shield-check class="h-4 w-4 text-emerald-600" />
                            <span>Auditoria de Chamada:</span>
                        </div>

                        <div class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 dark:text-emerald-400">
                                📱 Totem de Autoatendimento: <strong>{{ number_format($kioskPresences, 0, ',', '.') }}</strong> ({{ $kioskAuditRate }}%)
                            </span>
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <span class="inline-flex items-center gap-1 font-semibold {{ $kioskAuditRate < 60 && $manualPresences > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400' }}">
                                ✍️ Lançamento Manual (Educador): <strong>{{ number_format($manualPresences, 0, ',', '.') }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($kioskAuditRate >= 70)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                ✓ Frequência Altamente Auditada
                            </span>
                        @elseif ($totalAttendances > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300" title="Grande parte das presenças foi lançada manualmente pelo educador">
                                ⚠️ Atenção: Alta proporção manual
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if ($goal->notes)
                <div class="mt-4 rounded-xl bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-800/60 dark:text-gray-300 flex items-start gap-2">
                    <span class="font-bold text-gray-700 dark:text-gray-200">📌 Estratégia do Mês:</span>
                    <span>{{ $goal->notes }}</span>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

