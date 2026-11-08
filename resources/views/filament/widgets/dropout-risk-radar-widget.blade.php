<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-4 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                    <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="text-base font-black text-gray-950 dark:text-white">
                        🎯 Radar de Evasão & Alerta Preventivo
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Cruzamento automático de faltas recentes (30d), estagnação no EAD (&ge; 7d), baixo desempenho (&lt; 70%) e parcelas em atraso.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                    🔴 Crítico: {{ $criticalCount }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                    🟡 Alerta: {{ $warningCount }}
                </span>
                <a
                    href="{{ \App\Filament\Pages\StudentAbsencesReport::getUrl() }}"
                    class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                    Ver Relatório Completo →
                </a>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2.5">Aluno</th>
                        <th class="px-4 py-2.5">Modalidade & Formação</th>
                        <th class="px-4 py-2.5">Fatores de Risco Detectados</th>
                        <th class="px-4 py-2.5 text-center">Nível</th>
                        <th class="px-4 py-2.5 text-right">Ações Preventivas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($students as $st)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="font-bold text-gray-950 dark:text-white">{{ $st['student_name'] }}</div>
                                <div class="text-xs text-gray-500">Contrato: {{ $st['contract_number'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $st['course_name'] }}</div>
                                <div class="mt-0.5">
                                    @if($st['is_ead'])
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">
                                            EAD Online
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">
                                            Presencial
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($st['reasons'] as $reason)
                                        <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-bold {{ $reason['color'] === 'rose' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                                            {{ $reason['label'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($st['risk_level'] === 'Crítico')
                                    <span class="inline-flex items-center rounded-full bg-rose-600 px-2.5 py-0.5 text-xs font-black text-white">
                                        Crítico ({{ $st['risk_score'] }}%)
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-black text-gray-950">
                                        Alerta ({{ $st['risk_score'] }}%)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex flex-wrap items-center justify-end gap-1.5">
                                    @if($st['clean_phone'])
                                        <a
                                            href="https://wa.me/55{{ $st['clean_phone'] }}?text={{ urlencode($st['wa_message']) }}"
                                            target="_blank"
                                            title="Abrir WhatsApp com mensagem preventiva personalizada"
                                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300"
                                        >
                                            💬 WhatsApp
                                        </a>
                                    @endif
                                    @if(!$st['is_ead'])
                                        <a
                                            href="{{ \App\Filament\Pages\StudentAbsencesReport::getUrl(['schedule_makeup_contract' => $st['contract_id']]) }}"
                                            class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700 hover:bg-primary-100 dark:bg-primary-950/50 dark:text-primary-300"
                                        >
                                            📅 Reposição
                                        </a>
                                    @endif
                                    <a
                                        href="{{ \App\Filament\Resources\Contracts\ContractsResource::getUrl('view', ['record' => $st['contract_id']]) }}"
                                        class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        📄 Contrato
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-xs text-gray-500">
                                🎉 Nenhum aluno em risco crítico ou alerta de evasão detectado no momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

