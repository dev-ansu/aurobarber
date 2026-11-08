<x-filament-panels::page>
    @php
        $formationStats = $this->getFormationStats();
        $courseStats = $this->getCourseStats();
        $formations = $this->getFormationsData();
        $courses = $this->getCoursesData();
        $viewingFormation = $this->getViewingFormation();
        $viewingCourse = $this->getViewingCourse();
    @endphp

    {{-- Barra de Filtros e Ações --}}
    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Tabs de Alternância --}}
            <div class="flex items-center gap-2 border-b lg:border-b-0 border-gray-200 dark:border-gray-700 pb-3 lg:pb-0">
                <button
                    type="button"
                    wire:click="setTab('formations')"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $activeTab === 'formations' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    <x-heroicon-m-academic-cap class="w-5 h-5" />
                    <span>Alunos por Formação</span>
                    <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'formations' ? 'bg-primary-700 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
                        {{ count($formations) }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="setTab('courses')"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $activeTab === 'courses' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    <x-heroicon-m-book-open class="w-5 h-5" />
                    <span>Alunos por Curso</span>
                    <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'courses' ? 'bg-primary-700 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
                        {{ count($courses) }}
                    </span>
                </button>
            </div>

            {{-- Botão de Exportação CSV --}}
            <div class="flex items-center gap-3">
                @if ($activeTab === 'formations')
                    <button
                        type="button"
                        wire:click="exportFormationsCsv"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm"
                    >
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-primary-500" />
                        <span>Exportar Formações (CSV)</span>
                    </button>
                @else
                    <button
                        type="button"
                        wire:click="exportCoursesCsv"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm"
                    >
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-primary-500" />
                        <span>Exportar Cursos (CSV)</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Filtros Avançados --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/60">
            {{-- Busca --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Pesquisar por nome</label>
                <div class="relative">
                    <x-heroicon-m-magnifying-glass class="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="{{ $activeTab === 'formations' ? 'Buscar formação...' : 'Buscar curso...' }}"
                        class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-gray-900 dark:text-gray-100 placeholder-gray-400"
                    />
                </div>
            </div>

            {{-- Status do Contrato --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status do Contrato</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full py-2 px-3 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-gray-900 dark:text-gray-100"
                >
                    <option value="all">Todos os status</option>
                    <option value="active">Apenas Ativos</option>
                    <option value="concluded">Concluídos</option>
                    <option value="suspended">Trancados</option>
                    <option value="canceled">Cancelados</option>
                </select>
            </div>

            {{-- Data Início De --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Data Início (De)</label>
                <input
                    type="date"
                    wire:model.live="fromDate"
                    class="w-full py-2 px-3 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-gray-900 dark:text-gray-100"
                />
            </div>

            {{-- Data Início Até + Botão Limpar --}}
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Data Início (Até)</label>
                    <input
                        type="date"
                        wire:model.live="untilDate"
                        class="w-full py-2 px-3 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-gray-900 dark:text-gray-100"
                    />
                </div>
                @if ($search || $statusFilter !== 'all' || $fromDate || $untilDate)
                    <button
                        type="button"
                        wire:click="resetFilters"
                        title="Limpar filtros"
                        class="p-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 transition"
                    >
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ==================== ABA 1: ALUNOS POR FORMAÇÃO ==================== --}}
    @if ($activeTab === 'formations')
        {{-- Cards de Resumo Executivo das Formações --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-xl">
                    <x-heroicon-o-trophy class="w-8 h-8" />
                </div>
                <div class="overflow-hidden">
                    <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Mais Vendida</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white mt-0.5 truncate" title="{{ $formationStats['top_formation_name'] }}">
                        {{ $formationStats['top_formation_name'] }}
                    </div>
                    <div class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">
                        {{ $formationStats['top_formation_count'] }} matrículas
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl">
                    <x-heroicon-o-user-group class="w-8 h-8" />
                </div>
                <div>
                    <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Total Matrículas</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $formationStats['total_enrollments'] }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Vínculos em formações</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-xl">
                    <x-heroicon-o-check-badge class="w-8 h-8" />
                </div>
                <div>
                    <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Alunos Ativos</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ $formationStats['total_active'] }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Cursando no momento</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                    <x-heroicon-o-currency-dollar class="w-8 h-8" />
                </div>
                <div>
                    <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Volume em Contratos</div>
                    <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                        R$ {{ number_format($formationStats['total_revenue'], 2, ',', '.') }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Faturamento gerado</div>
                </div>
            </div>
        </div>

        {{-- Tabela Principal de Formações --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Ranking de Formações Mais Vendidas</span>
                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full font-normal">
                            {{ count($formations) }} formações
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Ordenadas pelo número total de contratos e alunos vinculados.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-center w-14">#</th>
                            <th class="px-6 py-3">Formação</th>
                            <th class="px-4 py-3 text-center">Cursos / Carga</th>
                            <th class="px-4 py-3 text-center">Total Alunos</th>
                            <th class="px-4 py-3 text-center">Ativos</th>
                            <th class="px-4 py-3 text-center">Concluídos</th>
                            <th class="px-4 py-3 text-center">Trancados</th>
                            <th class="px-4 py-3 text-center">Cancelados</th>
                            <th class="px-6 py-3 text-right">Faturamento</th>
                            <th class="px-6 py-3 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                        @forelse ($formations as $f)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3.5 text-center font-bold text-gray-700 dark:text-gray-300">
                                    @if ($f['rank'] === 1)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 font-extrabold text-xs">
                                            1º
                                        </span>
                                    @elseif ($f['rank'] === 2)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold text-xs">
                                            2º
                                        </span>
                                    @elseif ($f['rank'] === 3)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-500 font-bold text-xs">
                                            3º
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ $f['rank'] }}º</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $f['name'] }}
                                    </div>
                                    @if ($f['description'])
                                        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">
                                            {{ $f['description'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                                        {{ $f['courses_count'] }} cursos · {{ $f['workload'] }}h
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                        {{ $f['total_contracts'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $f['active_count'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center text-blue-600 dark:text-blue-400">
                                    {{ $f['concluded_count'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center text-amber-600 dark:text-amber-400">
                                    {{ $f['suspended_count'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center text-red-600 dark:text-red-400">
                                    {{ $f['canceled_count'] }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-semibold text-gray-900 dark:text-white">
                                    R$ {{ number_format($f['total_revenue'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <button
                                        type="button"
                                        wire:click="openFormationDetails({{ $f['id'] }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-900/30 hover:bg-primary-100 dark:hover:bg-primary-900/50 rounded-lg transition"
                                    >
                                        <x-heroicon-m-eye class="w-4 h-4" />
                                        <span>Ver Alunos</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-academic-cap class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                    <div class="font-medium">Nenhuma formação encontrada para os filtros selecionados.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ==================== ABA 2: ALUNOS POR CURSO ==================== --}}
    @if ($activeTab === 'courses')
        {{-- Cards de Resumo Executivo dos Cursos --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-xl">
                    <x-heroicon-o-fire class="w-8 h-8" />
                </div>
                <div class="overflow-hidden">
                    <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Curso Mais Procurado</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white mt-0.5 truncate" title="{{ $courseStats['top_course_name'] }}">
                        {{ $courseStats['top_course_name'] }}
                    </div>
                    <div class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">
                        {{ $courseStats['top_course_count'] }} alunos inscritos
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl">
                    <x-heroicon-o-clipboard-document-list class="w-8 h-8" />
                </div>
                <div>
                    <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Total de Inscrições</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $courseStats['total_enrollments'] }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Vínculos aluno-curso</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                    <x-heroicon-o-play-circle class="w-8 h-8" />
                </div>
                <div>
                    <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Cursando Agora</div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                        {{ $courseStats['total_in_progress'] }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Em andamento nas aulas</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-xl">
                    <x-heroicon-o-chart-pie class="w-8 h-8" />
                </div>
                <div>
                    <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Taxa de Conclusão</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ $courseStats['global_completion_rate'] }}%
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $courseStats['total_completed'] }} concluídos</div>
                </div>
            </div>
        </div>

        {{-- Tabela Principal de Cursos --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Ranking de Cursos com Mais Alunos</span>
                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full font-normal">
                            {{ count($courses) }} cursos
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Ordenados pelo volume total de alunos matriculados e cursando o módulo.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-center w-14">#</th>
                            <th class="px-6 py-3">Curso / Módulo</th>
                            <th class="px-4 py-3 text-center">Origem</th>
                            <th class="px-4 py-3 text-center">Carga Horária</th>
                            <th class="px-4 py-3 text-center">Total Inscrições</th>
                            <th class="px-4 py-3 text-center">Cursando</th>
                            <th class="px-4 py-3 text-center">Concluídos</th>
                            <th class="px-4 py-3 text-center">A Iniciar</th>
                            <th class="px-4 py-3 text-center">Taxa Conclusão</th>
                            <th class="px-4 py-3 text-center">Média Notas</th>
                            <th class="px-6 py-3 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                        @forelse ($courses as $c)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3.5 text-center font-bold text-gray-700 dark:text-gray-300">
                                    @if ($c['rank'] === 1)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 font-extrabold text-xs">
                                            1º
                                        </span>
                                    @elseif ($c['rank'] === 2)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold text-xs">
                                            2º
                                        </span>
                                    @elseif ($c['rank'] === 3)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-500 font-bold text-xs">
                                            3º
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ $c['rank'] }}º</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 font-semibold text-gray-900 dark:text-white">
                                    {{ $c['name'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if ($c['is_ouro_moderno'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                            Ouro Moderno
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                            Aurosoft LMS
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center font-medium text-gray-600 dark:text-gray-300">
                                    {{ $c['workload'] }}h
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                        {{ $c['total_enrollments'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ $c['in_progress_count'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $c['completed_count'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center text-gray-500 dark:text-gray-400">
                                    {{ $c['not_started_count'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-12 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ min(100, $c['completion_rate']) }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            {{ $c['completion_rate'] }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center font-medium text-gray-700 dark:text-gray-300">
                                    {{ $c['average_grade'] !== null ? number_format($c['average_grade'], 1, ',', '.') : '—' }}
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <button
                                        type="button"
                                        wire:click="openCourseDetails({{ $c['id'] }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-900/30 hover:bg-primary-100 dark:hover:bg-primary-900/50 rounded-lg transition"
                                    >
                                        <x-heroicon-m-eye class="w-4 h-4" />
                                        <span>Ver Alunos</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-book-open class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                    <div class="font-medium">Nenhum curso encontrado para os filtros selecionados.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ==================== MODAL DE DETALHES: ALUNOS DA FORMAÇÃO ==================== --}}
    @if ($viewingFormation)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl max-w-4xl w-full overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900/40">
                    <div>
                        <div class="text-xs uppercase font-semibold text-primary-600 dark:text-primary-400">
                            Detalhamento de Alunos
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mt-0.5">
                            <x-heroicon-m-academic-cap class="w-6 h-6 text-primary-600" />
                            <span>{{ $viewingFormation['name'] }}</span>
                            <span class="text-xs bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 px-2 py-0.5 rounded-full font-normal">
                                {{ count($viewingFormation['students']) }} matrículas
                            </span>
                        </h3>
                    </div>
                    <button
                        type="button"
                        wire:click="closeDetailsModal"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    >
                        <x-heroicon-m-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto p-6">
                    @if (empty($viewingFormation['students']))
                        <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                            Nenhum aluno matriculado nesta formação com os filtros atuais.
                        </div>
                    @else
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold border-b border-gray-200 dark:border-gray-700">
                                    <tr>
                                        <th class="px-4 py-3">Aluno</th>
                                        <th class="px-4 py-3">CPF</th>
                                        <th class="px-4 py-3">Contrato</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-center">Início</th>
                                        <th class="px-4 py-3 text-center">Conclusão</th>
                                        <th class="px-4 py-3 text-center">Contato</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                                    @foreach ($viewingFormation['students'] as $st)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                {{ $st['student_name'] }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $st['student_cpf'] }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-mono text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    {{ $st['contract_number'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $st['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : '' }}
                                                    {{ $st['status'] === 'concluded' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : '' }}
                                                    {{ $st['status'] === 'suspended' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : '' }}
                                                    {{ $st['status'] === 'canceled' ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : '' }}
                                                    {{ !in_array($st['status'], ['active','concluded','suspended','canceled']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                                ">
                                                    {{ $st['status_label'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">
                                                {{ $st['start_date'] }}
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">
                                                {{ $st['formation_completed_at'] ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if ($st['whatsapp_url'])
                                                    <a
                                                        href="{{ $st['whatsapp_url'] }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 transition"
                                                        title="Conversar pelo WhatsApp ({{ $st['phone'] }})"
                                                    >
                                                        <span>📱 {{ $st['phone'] }}</span>
                                                    </a>
                                                @elseif ($st['phone'])
                                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $st['phone'] }}</span>
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 flex justify-end">
                    <button
                        type="button"
                        wire:click="closeDetailsModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== MODAL DE DETALHES: ALUNOS DO CURSO ==================== --}}
    @if ($viewingCourse)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl max-w-4xl w-full overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900/40">
                    <div>
                        <div class="text-xs uppercase font-semibold text-primary-600 dark:text-primary-400">
                            Detalhamento de Alunos
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mt-0.5">
                            <x-heroicon-m-book-open class="w-6 h-6 text-primary-600" />
                            <span>{{ $viewingCourse['name'] }}</span>
                            <span class="text-xs bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 px-2 py-0.5 rounded-full font-normal">
                                {{ count($viewingCourse['students']) }} inscrições
                            </span>
                        </h3>
                    </div>
                    <button
                        type="button"
                        wire:click="closeDetailsModal"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    >
                        <x-heroicon-m-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto p-6">
                    @if (empty($viewingCourse['students']))
                        <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                            Nenhum aluno inscrito neste curso com os filtros atuais.
                        </div>
                    @else
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold border-b border-gray-200 dark:border-gray-700">
                                    <tr>
                                        <th class="px-4 py-3">Aluno</th>
                                        <th class="px-4 py-3">Formação</th>
                                        <th class="px-4 py-3">Contrato</th>
                                        <th class="px-4 py-3 text-center">Situação no Curso</th>
                                        <th class="px-4 py-3 text-center">Nota</th>
                                        <th class="px-4 py-3 text-center">Conclusão</th>
                                        <th class="px-4 py-3 text-center">Contato</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                                    @foreach ($viewingCourse['students'] as $st)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                {{ $st['student_name'] }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                                {{ $st['formation_name'] }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-mono text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    {{ $st['contract_number'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $st['status'] === 'in_progress' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' : '' }}
                                                    {{ $st['status'] === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : '' }}
                                                    {{ $st['status'] === 'not_started' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                                ">
                                                    {{ $st['status_label'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center font-semibold text-gray-800 dark:text-gray-200">
                                                {{ $st['grade'] }}
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">
                                                {{ $st['completed_at'] }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if ($st['whatsapp_url'])
                                                    <a
                                                        href="{{ $st['whatsapp_url'] }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 transition"
                                                        title="Conversar pelo WhatsApp ({{ $st['phone'] }})"
                                                    >
                                                        <span>📱 {{ $st['phone'] }}</span>
                                                    </a>
                                                @elseif ($st['phone'])
                                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $st['phone'] }}</span>
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 flex justify-end">
                    <button
                        type="button"
                        wire:click="closeDetailsModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

