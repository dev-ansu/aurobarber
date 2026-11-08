<x-filament-panels::page>
    @php
        $data = $this->metrics;
    @endphp

    {{-- BARRA DE FILTROS SUPERIOR --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-gray-950 dark:text-white flex items-center gap-2">
                    <span class="text-xl">📊</span>
                    <span>Filtros do Relatório de Satisfação</span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Selecione uma pesquisa específica ou veja os resultados consolidados de toda a escola.
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <x-filament::button
                    wire:click="exportCsv"
                    icon="heroicon-m-arrow-down-tray"
                    color="gray"
                    size="sm"
                >
                    Exportar Planilha (CSV)
                </x-filament::button>

                @if($surveyId || $fromDate || $untilDate)
                    <x-filament::button
                        wire:click="clearFilters"
                        icon="heroicon-m-x-mark"
                        color="danger"
                        size="sm"
                        outlined
                    >
                        Limpar Filtros
                    </x-filament::button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-gray-100 dark:border-white/10">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                    Pesquisa de Satisfação
                </label>
                <x-filament::input.wrapper prefix-icon="heroicon-m-clipboard-document-check">
                    <x-filament::input.select wire:model.live="surveyId">
                        <option value="">Consolidado: Todas as Pesquisas</option>
                        @foreach($data['survey_options'] as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                    Data Inicial
                </label>
                <x-filament::input.wrapper prefix-icon="heroicon-m-calendar">
                    <x-filament::input
                        type="date"
                        wire:model.live="fromDate"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                    Data Final
                </label>
                <x-filament::input.wrapper prefix-icon="heroicon-m-calendar">
                    <x-filament::input
                        type="date"
                        wire:model.live="untilDate"
                    />
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>

    @if($data['total_responses'] === 0)
        {{-- EMPTY STATE QUANDO NÃO HÁ RESPOSTAS --}}
        <div class="rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-3xl border border-amber-200 dark:border-amber-800">
                💬
            </div>
            <h3 class="mt-4 text-base font-bold text-gray-900 dark:text-white">Nenhuma resposta localizada</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto leading-relaxed">
                Não há respostas registradas para a pesquisa ou período selecionado. Quando os alunos marcarem presença no Totem ou responderem no portal, as avaliações serão tabuladas automaticamente aqui.
            </p>
        </div>
    @else
        {{-- ========================================================================= --}}
        {{-- 4 CARDS PRINCIPAIS COM DADOS RELEVANTES E MÉDIAS GERAIS                   --}}
        {{-- ========================================================================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. MÉDIA GERAL DE NOTAS --}}
            <div class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Média de Satisfação</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-500 border border-amber-200 dark:border-amber-800/40">
                        ⭐
                    </span>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">
                            {{ $data['average_rating_5'] !== null ? number_format($data['average_rating_5'], 2, ',', '.') : ($data['average_percentage'] !== null ? number_format($data['average_percentage'], 1, ',', '.') . '%' : '—') }}
                        </span>
                        @if($data['average_rating_5'] !== null)
                            <span class="text-xs font-semibold text-gray-400">/ 5.0 estrelas</span>
                        @endif
                    </div>
                    <div class="mt-2 flex items-center gap-1.5">
                        @php
                            $avg = $data['average_rating_5'] ?? ($data['average_percentage'] ? $data['average_percentage'] / 20 : 0);
                        @endphp
                        @if($avg >= 4.5)
                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                                ● Excelente avaliação
                            </span>
                        @elseif($avg >= 4.0)
                            <span class="inline-flex items-center gap-1 rounded-md bg-cyan-50 dark:bg-cyan-950/40 px-2 py-0.5 text-[11px] font-bold text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800/50">
                                ● Muito boa avaliação
                            </span>
                        @elseif($avg >= 3.0)
                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                                ● Avaliação regular
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 text-[11px] font-bold text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
                                ● Requer atenção
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. TAXA DE SATISFAÇÃO POSITIVA --}}
            <div class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Satisfação Positiva</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500 border border-emerald-200 dark:border-emerald-800/40">
                        👍
                    </span>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">
                            {{ $data['positive_percentage'] !== null ? number_format($data['positive_percentage'], 1, ',', '.') . '%' : '—' }}
                        </span>
                    </div>
                    <div class="mt-2 w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-2 rounded-full transition-all" style="width: {{ $data['positive_percentage'] ?? 0 }}%"></div>
                    </div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 block">
                        Alunos que avaliaram com 4 ou 5 estrelas / NPS ≥ 8
                    </span>
                </div>
            </div>

            {{-- 3. ÍNDICE NPS CONSOLIDADO --}}
            <div class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Net Promoter Score</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 border border-indigo-200 dark:border-indigo-800/40">
                        🏆
                    </span>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black {{ ($data['nps_score'] ?? 0) >= 50 ? 'text-emerald-600 dark:text-emerald-400' : (($data['nps_score'] ?? 0) >= 0 ? 'text-amber-500' : 'text-rose-500') }}">
                            {{ $data['nps_score'] !== null ? ($data['nps_score'] > 0 ? '+'.$data['nps_score'] : $data['nps_score']) : '—' }}
                        </span>
                        @if($data['nps_score'] !== null)
                            <span class="text-xs font-semibold text-gray-400">Score NPS</span>
                        @endif
                    </div>
                    @if($data['nps_score'] !== null)
                        <div class="mt-2 flex h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="bg-emerald-500" style="width: {{ $data['promoters_pct'] ?? 0 }}%" title="Promotores ({{ $data['promoters_pct'] }}%)"></div>
                            <div class="bg-amber-400" style="width: {{ $data['neutrals_pct'] ?? 0 }}%" title="Neutros ({{ $data['neutrals_pct'] }}%)"></div>
                            <div class="bg-rose-500" style="width: {{ $data['detractors_pct'] ?? 0 }}%" title="Detratores ({{ $data['detractors_pct'] }}%)"></div>
                        </div>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 flex justify-between">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $data['promoters_pct'] }}% Prom.</span>
                            <span class="text-amber-500 font-bold">{{ $data['neutrals_pct'] }}% Neut.</span>
                            <span class="text-rose-500 font-bold">{{ $data['detractors_pct'] }}% Detr.</span>
                        </span>
                    @else
                        <span class="text-[11px] text-gray-400 mt-2 block">Sem perguntas de escala NPS</span>
                    @endif
                </div>
            </div>

            {{-- 4. TOTAL DE RESPOSTAS E ALUNOS --}}
            <div class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Amostragem Coletada</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-500 border border-purple-200 dark:border-purple-800/40">
                        👥
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-black text-gray-900 dark:text-white">
                        {{ $data['total_responses'] }}
                    </div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 block">
                        de <strong class="text-gray-800 dark:text-gray-200 font-bold">{{ $data['unique_students'] }}</strong> alunos distintos participantes
                    </span>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- GRÁFICOS VISUAIS E DETALHAMENTO PERGUNTA POR PERGUNTA                     --}}
        {{-- ========================================================================= --}}
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Detalhamento e Gráficos por Pergunta</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Distribuição visual das notas, preferências e respostas registradas.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach($data['questions'] as $q)
                    <div class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            {{-- CABEÇALHO DO CARD DA PERGUNTA --}}
                            <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ match($q['type']) {
                                            'rating_5' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50',
                                            'nps_10' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50',
                                            'multiple_choice' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800/50',
                                            default => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200 dark:border-purple-800/50',
                                        } }}">
                                            {{ match($q['type']) {
                                                'rating_5' => '1 a 5 Estrelas',
                                                'nps_10' => 'NPS (0 a 10)',
                                                'multiple_choice' => 'Múltipla Escolha',
                                                default => 'Texto Livre / Sugestão',
                                            } }}
                                        </span>
                                        <span class="text-[11px] text-gray-400">● {{ $q['total_answers'] }} respostas</span>
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-snug">
                                        {{ $q['question'] }}
                                    </h4>
                                </div>

                                @if($q['average'] !== null)
                                    <div class="text-right shrink-0">
                                        <span class="text-xl font-black text-amber-500">
                                            {{ number_format($q['average'], $q['type'] === 'rating_5' ? 2 : 1, ',', '.') }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 block font-semibold">
                                            {{ $q['type'] === 'rating_5' ? 'de 5.0 ⭐' : 'Média' }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- GRÁFICO TIPO 1: RATING 5 ESTRELAS --}}
                            @if($q['type'] === 'rating_5')
                                <div class="mt-4 space-y-2.5">
                                    @foreach([5, 4, 3, 2, 1] as $star)
                                        @php
                                            $count = $q['stars_distribution'][$star] ?? 0;
                                            $pct = $q['stars_percentages'][$star] ?? 0.0;
                                            $colorClass = match($star) {
                                                5 => 'from-emerald-500 to-emerald-400',
                                                4 => 'from-teal-500 to-cyan-400',
                                                3 => 'from-amber-400 to-yellow-300',
                                                2 => 'from-orange-500 to-amber-400',
                                                default => 'from-rose-500 to-red-400',
                                            };
                                        @endphp
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1">
                                                    <span>{{ $star }}</span>
                                                    <span class="text-amber-400">★</span>
                                                </span>
                                                <span class="text-gray-500 dark:text-gray-400 font-medium">
                                                    <strong class="text-gray-900 dark:text-white font-bold">{{ $count }}</strong> ({{ number_format($pct, 1, ',', '.') }}%)
                                                </span>
                                            </div>
                                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                                                <div class="bg-gradient-to-r {{ $colorClass }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            {{-- GRÁFICO TIPO 2: NPS 0 A 10 --}}
                            @elseif($q['type'] === 'nps_10')
                                <div class="mt-4 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Net Promoter Score:</span>
                                        <span class="text-sm font-black {{ ($q['nps_score'] ?? 0) >= 50 ? 'text-emerald-500' : 'text-amber-500' }}">
                                            {{ $q['nps_score'] !== null ? ($q['nps_score'] > 0 ? '+'.$q['nps_score'] : $q['nps_score']) : '—' }}
                                        </span>
                                    </div>

                                    {{-- Régua Visual de Votos de 0 a 10 --}}
                                    <div class="grid grid-cols-11 gap-1 items-end pt-4 pb-1">
                                        @php
                                            $maxVotes = max(array_values($q['nps_distribution']) ?: [1]);
                                            if ($maxVotes == 0) $maxVotes = 1;
                                        @endphp
                                        @for($i = 0; $i <= 10; $i++)
                                            @php
                                                $votes = $q['nps_distribution'][$i] ?? 0;
                                                $barHeight = max(8, round(($votes / $maxVotes) * 45));
                                                $colColor = $i >= 9 ? 'bg-emerald-500' : ($i >= 7 ? 'bg-amber-400' : 'bg-rose-400');
                                            @endphp
                                            <div class="flex flex-col items-center gap-1" title="Nota {{ $i }}: {{ $votes }} votos">
                                                <span class="text-[9px] font-bold text-gray-400">{{ $votes }}</span>
                                                <div class="w-full {{ $colColor }} rounded-t transition-all" style="height: {{ $barHeight }}px"></div>
                                                <span class="text-[10px] font-black {{ $i >= 9 ? 'text-emerald-600 dark:text-emerald-400' : ($i >= 7 ? 'text-amber-500' : 'text-rose-500') }}">{{ $i }}</span>
                                            </div>
                                        @endfor
                                    </div>

                                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-center text-xs">
                                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40">
                                            <span class="text-emerald-700 dark:text-emerald-400 font-bold block">{{ $q['nps_promoters'] }}</span>
                                            <span class="text-[10px] text-emerald-600 dark:text-emerald-400">Promotores (9-10)</span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40">
                                            <span class="text-amber-700 dark:text-amber-400 font-bold block">{{ $q['nps_neutrals'] }}</span>
                                            <span class="text-[10px] text-amber-600 dark:text-amber-400">Neutros (7-8)</span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/40">
                                            <span class="text-rose-700 dark:text-rose-400 font-bold block">{{ $q['nps_detractors'] }}</span>
                                            <span class="text-[10px] text-rose-600 dark:text-rose-400">Detratores (0-6)</span>
                                        </div>
                                    </div>
                                </div>

                            {{-- GRÁFICO TIPO 3: MÚLTIPLA ESCOLHA --}}
                            @elseif($q['type'] === 'multiple_choice')
                                <div class="mt-4 space-y-2.5">
                                    @forelse($q['options_distribution'] as $optTitle => $optData)
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-semibold text-gray-700 dark:text-gray-300 truncate max-w-[200px]" title="{{ $optTitle }}">
                                                    {{ $optTitle }}
                                                </span>
                                                <span class="text-gray-500 dark:text-gray-400 font-medium">
                                                    <strong class="text-gray-900 dark:text-white font-bold">{{ $optData['count'] }}</strong> ({{ number_format($optData['percentage'], 1, ',', '.') }}%)
                                                </span>
                                            </div>
                                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                                                <div class="bg-gradient-to-r from-cyan-500 to-blue-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $optData['percentage'] }}%"></div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-xs text-gray-400 py-3 text-center">Nenhuma opção registrada</p>
                                    @endforelse
                                </div>

                            {{-- LISTA TIPO 4: TEXTO LIVRE / SUGESTÕES RECENTES --}}
                            @elseif($q['type'] === 'text')
                                <div class="mt-4 space-y-2.5 max-h-56 overflow-y-auto pr-1">
                                    @forelse($q['recent_comments'] as $comm)
                                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 text-xs space-y-1">
                                            <div class="flex items-center justify-between text-[11px] text-gray-400">
                                                <span class="font-bold text-gray-800 dark:text-gray-200">{{ $comm['student_name'] }}</span>
                                                <span>{{ $comm['date'] }}</span>
                                            </div>
                                            <p class="text-gray-700 dark:text-gray-300 italic whitespace-pre-line leading-relaxed">
                                                "{{ $comm['text'] }}"
                                            </p>
                                        </div>
                                    @empty
                                        <p class="text-xs text-gray-400 py-3 text-center">Nenhum comentário por escrito registrado nesta questão.</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- MURAL GERAL DE FEEDBACKS E COMENTÁRIOS RECENTES --}}
        @if(!empty($data['recent_comments']))
            <div class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>💬</span>
                            <span>Mural de Sugestões e Comentários Recentes dos Alunos</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Opiniões textuais e feedbacks enviados nos totens e no portal.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($data['recent_comments'] as $c)
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 flex flex-col justify-between space-y-2">
                            <p class="text-xs text-gray-800 dark:text-gray-200 italic leading-relaxed">
                                "{{ $c['text'] }}"
                            </p>
                            <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                                <span class="font-bold text-gray-700 dark:text-gray-300">{{ $c['student_name'] }}</span>
                                <span>{{ $c['date'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
