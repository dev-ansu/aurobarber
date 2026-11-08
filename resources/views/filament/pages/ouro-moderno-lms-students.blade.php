<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-2xl border border-primary-200 bg-primary-50 p-5 text-sm text-primary-900 dark:border-primary-400/20 dark:bg-primary-400/5 dark:text-primary-100">
            <p class="font-semibold">Consulta e Matrícula de Alunos da Ouro Moderno</p>
            <p class="mt-1">
                Alunos matriculados no LMS Ouro Moderno fora do Aurosoft. Ao clicar em <strong>"Cadastrar e Iniciar Matrícula"</strong>, o aluno é importado como Pessoa, sincronizando seu código na Ouro Moderno, e você é direcionado para a ficha cadastral completa com as abas de Contatos (WhatsApp), Documentos e Endereços antes de formalizar o Contrato.
            </p>
        </section>

        <!-- Barra de Filtros e Ações -->
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 md:grid-cols-12 md:items-end">
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Buscar aluno
                    </label>
                    <input
                        wire:model.live.debounce.400ms="search"
                        placeholder="Nome, cod_int, usuário, e-mail..."
                        class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"
                    />
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Situação no Aurosoft
                    </label>
                    <select
                        wire:model.live="registrationFilter"
                        class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"
                    >
                        <option value="pending">Pendentes de Matrícula (Padrão)</option>
                        <option value="without_contract">Cadastrados sem Contrato</option>
                        <option value="unregistered">Apenas sem Cadastro</option>
                        <option value="with_contract">Matriculados (Com Contrato)</option>
                        <option value="all">Todos os Alunos do LMS</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Situação no LMS
                    </label>
                    <select
                        wire:model.live="statusFilter"
                        class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10"
                    >
                        <option value="active">Somente Ativos</option>
                        <option value="inactive">Somente Inativos</option>
                        <option value="all">Todas as Situações</option>
                    </select>
                </div>

                <div class="md:col-span-3 flex flex-wrap items-center gap-2">
                    <x-filament::button wire:click="loadStudents" icon="heroicon-o-arrow-path">
                        Atualizar lista
                    </x-filament::button>
                    @if ($this->lastUpdated && auth()->user()?->can('Export:OuroModernoLmsStudents'))
                        <x-filament::button wire:click="exportReport" color="gray" icon="heroicon-o-arrow-down-tray">
                            CSV
                        </x-filament::button>
                    @endif
                </div>
            </div>

            @if ($this->lastUpdated)
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Atualizada em {{ $this->lastUpdated }} · {{ $this->filteredStudents()->total() }} aluno(s) exibido(s) ({{ $this->totalCachedStudentsCount() }} total no cache).
                </p>
            @endif
        </section>

        <!-- Tabela de Alunos -->
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Aluno</th>
                            <th class="px-4 py-3">ID LMS / cod_int</th>
                            <th class="px-4 py-3">Usuário / E-mail</th>
                            <th class="px-4 py-3">Situação LMS</th>
                            <th class="px-4 py-3">Status no Aurosoft</th>
                            <th class="px-4 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->filteredStudents() as $student)
                            @php
                                $ident = $student['cod_int'] ?: ($student['id_aluno'] ?? '');
                                $hasContract = !empty($student['contract_id']);
                                $hasPerson = !empty($student['person_id']);
                                $tenantId = \Filament\Facades\Filament::getTenant()?->id;
                            @endphp
                            <tr class="align-top text-gray-800 dark:text-gray-200">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $student['name'] }}</div>
                                    @if (!empty($student['birth_date']))
                                        <div class="text-xs text-gray-400">Nasc: {{ $student['birth_date'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">
                                    @if (!empty($student['cod_int']))
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $student['cod_int'] }}</span>
                                        @if (!empty($student['id_aluno']) && $student['id_aluno'] !== $student['cod_int'])
                                            <span class="text-[10px] text-gray-400 block">ID: {{ $student['id_aluno'] }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-600 dark:text-gray-400">ID: {{ $student['id_aluno'] ?: '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $student['username'] ?: '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ $student['email'] ?: '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ match (mb_strtoupper((string) ($student['active'] ?? ''))) {
                                        'S', '1', 'SIM', 'ATIVO', 'ACTIVE' => 'Ativo',
                                        'N', '0', 'NAO', 'NÃO', 'INATIVO', 'INACTIVE' => 'Inativo',
                                        default => $student['active'] ?: '—'
                                    } }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($hasContract)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-950/60 dark:text-success-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>
                                            Matriculado · {{ $student['contract_number'] }}
                                        </span>
                                    @elseif ($hasPerson)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-800 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-950/60 dark:text-warning-300" title="Cadastro de Pessoa criado, mas ainda sem contrato nesta unidade">
                                            <span class="h-1.5 w-1.5 rounded-full bg-warning-500"></span>
                                            Cadastro sem Contrato
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/20 dark:bg-gray-800 dark:text-gray-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                            Sem Cadastro
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if ($hasContract)
                                            <x-filament::button
                                                size="xs"
                                                color="success"
                                                icon="heroicon-o-eye"
                                                tag="a"
                                                href="{{ route('filament.admin.resources.contracts.view', ['tenant' => $tenantId, 'record' => $student['contract_id']]) }}"
                                            >
                                                Ver Contrato
                                            </x-filament::button>
                                        @elseif ($hasPerson)
                                            <x-filament::button
                                                size="xs"
                                                color="primary"
                                                icon="heroicon-o-pencil-square"
                                                tag="a"
                                                href="{{ route('filament.admin.resources.people.edit', ['tenant' => $tenantId, 'record' => $student['person_id'], 'flow' => 'lms_enrollment']) }}"
                                                title="Revisar contatos, documentos, endereço e avançar para o contrato"
                                            >
                                                Continuar Matrícula
                                            </x-filament::button>

                                            <x-filament::button
                                                size="xs"
                                                color="danger"
                                                icon="heroicon-o-trash"
                                                wire:click="deleteOrphanPerson({{ $student['person_id'] }})"
                                                wire:confirm="Tem certeza que deseja excluir o cadastro desta pessoa? Esta ação removerá o cadastro sem contrato."
                                                title="Excluir cadastro da Pessoa"
                                            >
                                                Excluir
                                            </x-filament::button>
                                        @else
                                            <x-filament::button
                                                size="xs"
                                                color="primary"
                                                icon="heroicon-o-user-plus"
                                                wire:click="importAndStartEnrollment('{{ $ident }}')"
                                                title="Importar como Pessoa e abrir formulário com Relation Managers"
                                            >
                                                Cadastrar e Iniciar Matrícula
                                            </x-filament::button>
                                        @endif

                                        @if ($ident)
                                            <x-filament::button
                                                size="xs"
                                                color="gray"
                                                icon="heroicon-o-document-text"
                                                wire:click="loadStudentReport('{{ $ident }}')"
                                            >
                                                Boletim LMS
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                                    @if ($this->lastUpdated)
                                        Nenhum aluno encontrado para os filtros selecionados.
                                    @else
                                        Carregue a lista da Ouro Moderno para consultar os alunos.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->filteredStudents()->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                    {{ $this->filteredStudents()->links() }}
                </div>
            @endif
        </section>

        <!-- Relatório / Boletim LMS Inline -->
        @if ($this->studentReport)
            @php($student = $this->studentReport['student'])
            <section class="space-y-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <header class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                            Boletim somente leitura · cod_int {{ $student['cod_int'] }}
                        </p>
                        <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $student['name'] }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $student['email'] ?: 'Sem e-mail' }} · {{ $student['username'] ?: 'Sem usuário' }}</p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Consultado em {{ $this->studentReport['loaded_at'] }}</p>
                </header>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                        <p class="text-xs text-gray-500">Data de início</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $student['start_date'] ?: 'Não fornecida pelo LMS' }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                        <p class="text-xs text-gray-500">Data de fim / conclusão</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $student['end_date'] ?: 'Não fornecida pelo LMS' }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                        <p class="text-xs text-gray-500">Cursos no LMS</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ count($this->studentReport['courses']) }}</p>
                    </div>
                </div>

                @forelse ($this->studentReport['courses'] as $courseReport)
                    @php($course = $courseReport['course'])
                    <article class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-950 dark:text-white">{{ $course['curso'] ?? $course['nome'] ?? 'Curso '.($course['idcurso'] ?? '') }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $course['situacao'] ?? 'Sem situação' }}@if (isset($course['data_conclusao'])) · Conclusão: {{ $course['data_conclusao'] ?: 'Não concluído' }}@endif</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">ID LMS {{ $course['idcurso'] ?? '—' }}</span>
                        </div>
                        <x-ouro-moderno-activities :lessons="$courseReport['lessons']" :tests="$courseReport['tests']" />
                    </article>
                @empty
                    <p class="rounded-xl bg-gray-50 p-5 text-center text-sm text-gray-500 dark:bg-gray-800">Este aluno não possui cursos retornados pela API.</p>
                @endforelse
            </section>
        @endif
    </div>
</x-filament-panels::page>
