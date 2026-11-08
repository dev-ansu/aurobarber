<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Navegação entre Abas -->
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-800">
            <nav class="flex space-x-2">
                <button
                    type="button"
                    wire:click="setTab('ead_platform')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'ead_platform' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    <x-heroicon-o-cloud-arrow-down class="h-5 w-5" />
                    <span>Consultar Alunos no EAD</span>
                    @if ($this->totalCachedCount() > 0)
                        <span class="ml-1.5 rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'ead_platform' ? 'bg-primary-800 text-white' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                            {{ $this->totalCachedCount() }}
                        </span>
                    @endif
                </button>

                <button
                    type="button"
                    wire:click="setTab('contracts')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'contracts' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    <x-heroicon-o-academic-cap class="h-5 w-5" />
                    <span>Contratos EAD no Aurosoft</span>
                    <span class="ml-1.5 rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'contracts' ? 'bg-primary-800 text-white' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                        {{ $this->contracts->total() }}
                    </span>
                </button>
            </nav>

            <div class="flex items-center gap-3">
                @if ($activeTab === 'ead_platform')
                    <x-filament::button
                        wire:click="loadStudents"
                        icon="heroicon-o-arrow-path"
                        color="primary"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="loadStudents">Consultar / Atualizar Lista da Ouro Moderno</span>
                        <span wire:loading wire:target="loadStudents">Consultando API da Ouro Moderno...</span>
                    </x-filament::button>
                @endif
            </div>
        </div>

        @if ($activeTab === 'ead_platform')
            <!-- ABA 1: CONSULTA DE ALUNOS NA PLATAFORMA EAD (SEM IMPORTAR AUTOMÁTICO) -->
            <section class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex-1 min-w-[280px] max-w-md">
                        <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                            <x-filament::input
                                type="search"
                                wire:model.live.debounce.400ms="search"
                                placeholder="Pesquisar por nome, e-mail, ID EAD ou CPF..."
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($lastUpdated)
                            Última consulta realizada em: <strong class="text-gray-700 dark:text-gray-200">{{ $lastUpdated }}</strong>
                        @else
                            Clique no botão acima para puxar a lista de alunos da plataforma EAD.
                        @endif
                    </div>
                </div>

                <!-- Tabela de Alunos do EAD -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3.5">Aluno</th>
                                    <th class="px-4 py-3.5">ID EAD</th>
                                    <th class="px-4 py-3.5">Contato / E-mail</th>
                                    <th class="px-4 py-3.5">Nascimento</th>
                                    <th class="px-4 py-3.5">Situação Aurosoft</th>
                                    <th class="px-4 py-3.5 text-right">Ações Disponíveis</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($this->eadPlatformStudents as $st)
                                    <tr class="align-middle text-gray-800 dark:text-gray-200 hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                                        <td class="px-4 py-3.5 font-medium">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ $st['name'] }}</div>
                                            @if ($st['cpf'])
                                                <div class="text-xs text-gray-400 font-mono">CPF: {{ $st['cpf'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 font-mono text-xs">
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-gray-700 font-bold dark:bg-gray-800 dark:text-gray-300">
                                                #{{ $st['id_aluno'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-xs">
                                            <div class="text-gray-900 dark:text-gray-200">{{ $st['email'] ?: 'Sem e-mail' }}</div>
                                            @if ($st['phone'])
                                                <div class="text-gray-500">{{ $st['phone'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-xs text-gray-600 dark:text-gray-400">
                                            @if (!empty($st['birth_date']))
                                                {{ \Illuminate\Support\Carbon::parse($st['birth_date'])->format('d/m/Y') }}
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-xs">
                                            @if (!empty($st['contract_id']))
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/30">
                                                    <x-heroicon-s-check-circle class="h-3.5 w-3.5" />
                                                    Contrato Ativo
                                                </span>
                                            @elseif (!empty($st['person_id']))
                                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-600/20 dark:bg-sky-950/50 dark:text-sky-400 dark:ring-sky-500/30">
                                                    <x-heroicon-s-user class="h-3.5 w-3.5" />
                                                    Pessoa Cadastrada
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                    Fora do Aurosoft
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-right">
                                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                                <x-filament::button
                                                    size="xs"
                                                    color="primary"
                                                    icon="heroicon-o-document-plus"
                                                    wire:click="importAndCreateContract('{{ $st['id_aluno'] }}', true)"
                                                    wire:confirm="Deseja cadastrar {{ $st['name'] }} como Pessoa no Aurosoft e criar um Contrato para ele(a)?"
                                                >
                                                    Cadastrar e Criar Contrato
                                                </x-filament::button>

                                                <x-filament::button
                                                    size="xs"
                                                    color="gray"
                                                    icon="heroicon-o-user"
                                                    wire:click="importAndCreateContract('{{ $st['id_aluno'] }}', false)"
                                                    title="Cadastrar e abrir ficha da Pessoa para completar dados"
                                                >
                                                    Pessoa
                                                </x-filament::button>

                                                <x-filament::button
                                                    size="xs"
                                                    color="gray"
                                                    icon="heroicon-o-academic-cap"
                                                    wire:click="loadStudentReport('{{ $st['id_aluno'] }}')"
                                                    title="Consultar cursos e progresso deste aluno no EAD"
                                                >
                                                    Boletim EAD
                                                </x-filament::button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                                            @if ($this->totalCachedCount() > 0)
                                                Nenhum aluno encontrado para os termos da busca.
                                            @else
                                                <div class="flex flex-col items-center justify-center space-y-3">
                                                    <x-heroicon-o-cloud-arrow-down class="h-10 w-10 text-gray-400" />
                                                    <p class="font-medium text-gray-700 dark:text-gray-300">Nenhum aluno carregado no momento.</p>
                                                    <p class="text-xs text-gray-500 max-w-sm">Clique em <strong>"Consultar / Atualizar Lista da Ouro Moderno"</strong> para buscar os alunos na plataforma externa.</p>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($this->eadPlatformStudents->hasPages())
                        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                            {{ $this->eadPlatformStudents->links() }}
                        </div>
                    @endif
                </div>

                <!-- Detalhes do Boletim EAD (Inline quando acionado) -->
                @if (!empty($this->studentReport))
                    @php($repStudent = $this->studentReport['student'])
                    <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                                    Boletim EAD Online · ID {{ $repStudent['id_aluno'] }}
                                </p>
                                <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $repStudent['name'] }}</h2>
                                <p class="text-xs text-gray-500">{{ $repStudent['email'] ?: 'Sem e-mail' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400">Consultado em {{ $this->studentReport['loaded_at'] }}</span>
                                <x-filament::button size="xs" color="gray" wire:click="$set('studentReport', [])">Fechar</x-filament::button>
                            </div>
                        </header>

                        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                                <p class="text-xs text-gray-500">Total de Cursos Atribuídos</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ count($this->studentReport['courses']) }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Cursos Matriculados no EAD:</h3>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @forelse ($this->studentReport['courses'] as $course)
                                    @php($progress = (float)($course['progresso'] ?? $course['porcentagem'] ?? 0))
                                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800 space-y-2 bg-gray-50/50 dark:bg-gray-800/30">
                                        <div class="flex justify-between items-start gap-2">
                                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">{{ $course['nome'] ?? $course['curso'] ?? 'Curso EAD' }}</h4>
                                            <span class="rounded bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-950/50 dark:text-primary-400">
                                                {{ $progress }}%
                                            </span>
                                        </div>
                                        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                            <div class="h-full rounded-full bg-primary-600" style="width: {{ min(100, $progress) }}%"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 flex justify-between">
                                            <span>Situação: {{ $course['situacao'] ?? ($progress >= 100 ? 'Concluído' : 'Em andamento') }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500 col-span-3">Nenhum curso individual retornado pela API para este aluno.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>
                @endif
            </section>
        @else
            <!-- ABA 2: CONTRATOS EAD ATIVOS NO AUROSOFT -->
            <section class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex-1 min-w-[280px] max-w-md">
                        <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                            <x-filament::input
                                type="search"
                                wire:model.live.debounce.400ms="search"
                                placeholder="Buscar por número do contrato ou nome do aluno..."
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-gray-500">Filtrar Progresso:</span>
                        <select
                            wire:model.live="progressFilter"
                            class="rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option value="all">Todos os Progressos</option>
                            <option value="not_started">Não Iniciado (0%)</option>
                            <option value="in_progress">Em Andamento (1% a 99%)</option>
                            <option value="completed">Concluído (100%)</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3.5">Contrato</th>
                                    <th class="px-4 py-3.5">Aluno</th>
                                    <th class="px-4 py-3.5">Nascimento</th>
                                    <th class="px-4 py-3.5">Progresso EAD</th>
                                    <th class="px-4 py-3.5">Última Sincronização</th>
                                    <th class="px-4 py-3.5 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($this->contracts as $contract)
                                    @php($progress = (float)($contract->ead_last_progress_percentage ?? 0))
                                    <tr class="align-middle text-gray-800 dark:text-gray-200 hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                                        <td class="px-4 py-3.5 font-mono text-xs">
                                            <a href="{{ route('filament.admin.resources.contracts.view', ['tenant' => $contract->school_id, 'record' => $contract->id]) }}" class="font-bold text-primary-600 hover:underline">
                                                {{ $contract->contract_number }}
                                            </a>
                                            <span class="block text-[11px] text-gray-400">ID EAD: {{ $contract->ead_student_id ?: '—' }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 font-medium">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ $contract->student?->name ?? '—' }}</div>
                                            <div class="text-xs text-gray-400">{{ $contract->student?->email ?? 'Sem e-mail' }}</div>
                                        </td>
                                        <td class="px-4 py-3.5 text-xs text-gray-600 dark:text-gray-400">
                                            @if ($contract->student?->birth_date)
                                                {{ $contract->student->birth_date->format('d/m/Y') }}
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="w-36 space-y-1">
                                                <div class="flex justify-between text-xs">
                                                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ number_format($progress, 0) }}%</span>
                                                    <span class="text-gray-400">
                                                        {{ $progress >= 100 ? 'Concluído' : ($progress > 0 ? 'Em curso' : 'Não iniciado') }}
                                                    </span>
                                                </div>
                                                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                                    <div class="h-full rounded-full {{ $progress >= 100 ? 'bg-emerald-500' : 'bg-primary-600' }}" style="width: {{ min(100, $progress) }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-xs text-gray-500">
                                            {{ $contract->ead_synced_at ? $contract->ead_synced_at->format('d/m/Y H:i') : 'Nunca sincronizado' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-right">
                                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                                <x-filament::button
                                                    size="xs"
                                                    color="gray"
                                                    icon="heroicon-o-arrow-path"
                                                    wire:click="syncSingleStudent({{ $contract->id }})"
                                                    title="Sincronizar notas e progresso deste aluno"
                                                >
                                                    Sincronizar
                                                </x-filament::button>

                                                <x-filament::button
                                                    size="xs"
                                                    color="success"
                                                    icon="heroicon-o-chat-bubble-left-ellipsis"
                                                    wire:click="sendWhatsAppEncouragement({{ $contract->id }})"
                                                    title="Enviar incentivo pedagógico via WhatsApp"
                                                >
                                                    WhatsApp
                                                </x-filament::button>

                                                <x-filament::button
                                                    size="xs"
                                                    color="primary"
                                                    icon="heroicon-o-arrow-top-right-on-square"
                                                    wire:click="openStudentAutoLogin({{ $contract->id }})"
                                                    title="Acessar o portal do aluno diretamente com login automático"
                                                >
                                                    Portal EAD
                                                </x-filament::button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                                            Nenhum contrato EAD vinculado no Aurosoft para esta escola.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($this->contracts->hasPages())
                        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                            {{ $this->contracts->links() }}
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
