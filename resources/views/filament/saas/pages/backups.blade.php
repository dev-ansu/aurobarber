<x-filament-panels::page>
    @php
        $backups = $this->backups;
        $totalCount = $backups->count();
        $totalBytes = $backups->sum('size_bytes');
        $totalFormatted = $totalBytes < 1048576
            ? round($totalBytes / 1024, 1) . ' KB'
            : ($totalBytes < 1073741824 ? round($totalBytes / 1048576, 2) . ' MB' : round($totalBytes / 1073741824, 2) . ' GB');
        $latest = $backups->first();
        $globalCount = $backups->filter(fn($b) => $b['is_global'])->count();
        $tenantCount = $backups->filter(fn($b) => !$b['is_global'])->count();
    @endphp

    <div class="space-y-6">
        {{-- 1. HEADER DO QUADRO GERAL DE BACKUPS --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="max-w-3xl space-y-1.5">
                    <x-filament::badge color="warning">Segurança &amp; Resiliência dos Dados</x-filament::badge>
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">Quadro Geral de Backups &amp; Restauração</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dumps em formato binário nativo PostgreSQL comprimidos em ZIP. Toda restauração cria automaticamente uma cópia pré-restauração por segurança.
                    </p>
                </div>

                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-primary-500/30 bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-server-stack" class="h-7 w-7" />
                </div>
            </div>
        </x-filament::section>

        {{-- 2. CARDS DE KPIS --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            {{-- Card 1: Total de Backups --}}
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total de Backups</span>
                    <div class="font-mono text-2xl font-bold text-gray-950 dark:text-white">
                        {{ $totalCount }} <span class="font-sans text-xs font-normal text-gray-500 dark:text-gray-400">arquivos</span>
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        Espaço em disco: <strong class="text-gray-700 dark:text-gray-200">{{ $totalFormatted }}</strong>
                    </span>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-primary-500/25 bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-circle-stack" class="h-6 w-6" />
                </div>
            </div>

            {{-- Card 2: Segurança Pré-Restauração --}}
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-success-600 dark:text-success-400">Garantia Pré-Restauração</span>
                    <div class="text-2xl font-bold text-success-600 dark:text-success-400">Ativa</div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">Cópia preventiva gerada antes do restore</span>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-success-500/25 bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6" />
                </div>
            </div>

            {{-- Card 3: Google Drive Nuvem --}}
            @php
                $driveService = app(\App\Services\SaasGoogleDriveBackupService::class);
                $isDriveActive = $driveService->isEnabled();
            @endphp
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Nuvem Google Drive</span>
                    <div class="text-2xl font-bold {{ $isDriveActive ? 'text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $isDriveActive ? 'Conectado' : 'Desativado' }}
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        {{ $isDriveActive ? 'Criptografia SecretStream ativa' : 'Configurar credenciais no topo' }}
                    </span>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border {{ $isDriveActive ? 'border-success-500/25 bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' : 'border-gray-300 bg-gray-50 text-gray-400 dark:border-white/10 dark:bg-white/5' }}">
                    <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="h-6 w-6" />
                </div>
            </div>

            {{-- Card 4: Último Backup --}}
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="min-w-0 space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-info-600 dark:text-info-400">Último Arquivo Gerado</span>
                    <div class="truncate text-base font-bold text-gray-950 dark:text-white">
                        {{ $latest ? $latest['created_at_formatted'] : 'Nenhum backup' }}
                    </div>
                    <span class="block truncate font-mono text-xs text-gray-500 dark:text-gray-400">
                        {{ $latest ? $latest['filename'] : 'Clique em Gerar Novo Backup' }}
                    </span>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-info-500/25 bg-info-50 text-info-600 dark:bg-info-500/10 dark:text-info-400">
                    <x-filament::icon icon="heroicon-o-clock" class="h-6 w-6" />
                </div>
            </div>
        </div>

        {{-- 3. FILTROS DE VISUALIZAÇÃO --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="inline-flex flex-wrap items-center gap-1 rounded-xl bg-white p-1 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <x-filament::button
                    size="xs"
                    :color="$filterScope === 'all' ? 'primary' : 'gray'"
                    wire:click="$set('filterScope', 'all')"
                >
                    Todos os Backups ({{ $totalCount }})
                </x-filament::button>
                <x-filament::button
                    size="xs"
                    :color="$filterScope === 'global' ? 'primary' : 'gray'"
                    wire:click="$set('filterScope', 'global')"
                >
                    🌐 Base Completa ({{ $globalCount }})
                </x-filament::button>
                <x-filament::button
                    size="xs"
                    :color="$filterScope === 'tenants' ? 'primary' : 'gray'"
                    wire:click="$set('filterScope', 'tenants')"
                >
                    💈 Por Barbearia ({{ $tenantCount }})
                </x-filament::button>
            </div>

            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4 shrink-0 text-primary-500" />
                <span>
                    Formato nativo
                    <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-700 dark:bg-white/10 dark:text-gray-200">PostgreSQL Custom Dump (-Fc)</code>
                    com compressão zlib.
                </span>
            </div>
        </div>

        {{-- 4. TABELA DE BACKUPS --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @if($backups->isEmpty())
                <div class="px-6 py-12 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 text-gray-400 dark:border-white/10 dark:bg-white/5">
                        <x-filament::icon icon="heroicon-o-archive-box" class="h-7 w-7" />
                    </div>
                    <h4 class="text-base font-bold text-gray-950 dark:text-white">Nenhum backup encontrado neste filtro</h4>
                    <p class="mx-auto mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                        Utilize o botão "+ Gerar Novo Backup" no topo da página para criar o primeiro dump.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Tipo</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Barbearia / Escopo</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Nome do Arquivo</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Tamanho</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Criado em</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($backups as $item)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        @if($item['is_global'])
                                            <x-filament::badge color="warning">🌐 GLOBAL</x-filament::badge>
                                        @else
                                            <x-filament::badge color="success">💈 BARBEARIA</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="font-semibold text-gray-950 dark:text-white">{{ $item['barbershop_name'] }}</div>
                                        @if(!$item['is_global'])
                                            <span class="block font-mono text-xs text-gray-500 dark:text-gray-400">slug: /{{ $item['scope'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-xs">
                                        <span class="inline-block rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
                                            {{ $item['filename'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3.5 font-semibold text-gray-950 dark:text-white">
                                        {{ $item['size_formatted'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-gray-500 dark:text-gray-400">
                                        {{ $item['created_at_formatted'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <x-filament::button
                                                size="xs"
                                                color="success"
                                                outlined
                                                icon="heroicon-m-shield-check"
                                                wire:click="createSandbox('{{ $item['filename'] }}')"
                                                wire:confirm="Deseja inicializar uma Sandbox isolada para validar este backup em um schema seguro? Nenhum dado de produção será alterado."
                                                wire:loading.attr="disabled"
                                                wire:target="createSandbox('{{ $item['filename'] }}')"
                                            >
                                                Criar Sandbox
                                            </x-filament::button>
                                            <x-filament::button
                                                size="xs"
                                                color="info"
                                                outlined
                                                icon="heroicon-m-beaker"
                                                wire:click="simulateBackup('{{ $item['filename'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="simulateBackup('{{ $item['filename'] }}')"
                                            >
                                                Simular Restore
                                            </x-filament::button>
                                            <x-filament::button
                                                size="xs"
                                                color="warning"
                                                outlined
                                                icon="heroicon-m-arrow-uturn-left"
                                                wire:click="restoreBackup('{{ $item['filename'] }}')"
                                                wire:confirm="Tem certeza de que deseja restaurar este backup? Uma cópia de segurança preventiva será gerada automaticamente antes da restauração."
                                                wire:loading.attr="disabled"
                                                wire:target="restoreBackup('{{ $item['filename'] }}')"
                                            >
                                                Restaurar
                                            </x-filament::button>
                                            <x-filament::button
                                                size="xs"
                                                color="danger"
                                                outlined
                                                icon="heroicon-m-trash"
                                                wire:click="deleteBackup('{{ $item['filename'] }}')"
                                                wire:confirm="Excluir este arquivo de backup permanentemente do disco da VPS?"
                                                wire:loading.attr="disabled"
                                                wire:target="deleteBackup('{{ $item['filename'] }}')"
                                            >
                                                Excluir
                                            </x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        {{-- 5. SEÇÃO DE AMBIENTES SANDBOX DE RESTAURAÇÃO (DISASTER RECOVERY) --}}
        @php
            $sandboxRuns = $this->activeSandboxRuns;
        @endphp
        <div class="space-y-4 pt-4">
            <x-filament::section>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <x-filament::badge color="success">Disaster Recovery • Sandbox Isolada</x-filament::badge>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Schemas temporários auditáveis</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white">Ambientes Sandbox de Restauração</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Validação prática de restauração em schemas isolados no PostgreSQL. Permite auditar contagens de registros e assinar laudo humano sem tocar em produção.
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @if($sandboxRuns->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-gray-50 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                            <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6" />
                        </div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">Nenhum ambiente sandbox ativo no momento.</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Clique em "Criar Sandbox" na tabela de backups acima para testar uma restauração isolada.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Schema / Alvo</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Backup Origem</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Smoke Test</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Laudo Humano</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Criado em</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($sandboxRuns as $run)
                                    @php
                                        $runStatusColor = match($run->status) {
                                            'ready' => 'success',
                                            'running' => 'warning',
                                            'failed' => 'danger',
                                            'cleaned' => 'gray',
                                            default => 'info',
                                        };
                                        $runStatusLabel = match($run->status) {
                                            'ready' => 'Pronta / Ativa',
                                            'running' => 'Restaurando...',
                                            'failed' => 'Falha',
                                            'cleaned' => 'Destruída',
                                            default => $run->status,
                                        };
                                        $humanColor = match($run->human_validation_status) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default => 'warning',
                                        };
                                        $humanLabel = match($run->human_validation_status) {
                                            'approved' => '✓ Aprovado',
                                            'rejected' => '✗ Rejeitado',
                                            default => '⏳ Pendente',
                                        };
                                        $tableCount = $run->automated_check_results['table_count'] ?? 0;
                                    @endphp
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-4 py-3.5 font-mono text-xs font-bold text-gray-950 dark:text-white">
                                            {{ $run->target_schema ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3.5 font-mono text-xs text-gray-600 dark:text-gray-300">
                                            {{ $run->backup_file }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5">
                                            <x-filament::badge :color="$runStatusColor" size="xs">
                                                {{ $runStatusLabel }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                            @if($run->automated_check_status === 'passed')
                                                <span class="text-success-600 dark:text-success-400 font-semibold">{{ $tableCount }} tabelas</span>
                                            @else
                                                <span class="text-gray-400">{{ $run->automated_check_status }}</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5">
                                            <x-filament::badge :color="$humanColor" size="xs">
                                                {{ $humanLabel }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $run->created_at?->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <x-filament::button
                                                    size="xs"
                                                    color="info"
                                                    outlined
                                                    icon="heroicon-m-clipboard-document-check"
                                                    wire:click="openAuditModal({{ $run->id }})"
                                                >
                                                    Auditoria
                                                </x-filament::button>

                                                @if($run->status === 'ready')
                                                    <x-filament::button
                                                        size="xs"
                                                        color="danger"
                                                        outlined
                                                        icon="heroicon-m-trash"
                                                        wire:click="cleanupSandbox({{ $run->id }})"
                                                        wire:confirm="Deseja destruir este schema sandbox e liberar espaço em disco?"
                                                        wire:loading.attr="disabled"
                                                        wire:target="cleanupSandbox({{ $run->id }})"
                                                    >
                                                        Destruir
                                                    </x-filament::button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- MODAL DE AUDITORIA & LAUDO DA SANDBOX --}}
        @if($showAuditModal && $this->selectedSandbox)
            @php
                $sandbox = $this->selectedSandbox;
                $results = $sandbox->automated_check_results ?? [];
                $entities = $results['entities'] ?? [];
            @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/60 backdrop-blur-sm">
                <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10 space-y-5 p-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-white/10">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <x-filament::badge color="success">Sandbox Auditada</x-filament::badge>
                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $sandbox->target_schema }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Laudo Clínico de Restauração</h3>
                        </div>
                        <button wire:click="closeAuditModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                            <x-filament::icon icon="heroicon-m-x-mark" class="h-6 w-6" />
                        </button>
                    </div>

                    {{-- Resultados da Restauração --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-center">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tabelas</span>
                            <div class="text-xl font-bold text-gray-950 dark:text-white mt-1">{{ $results['table_count'] ?? 0 }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-center">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Barbearias</span>
                            <div class="text-xl font-bold text-success-600 dark:text-success-400 mt-1">{{ $entities['barbershops'] ?? 0 }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-center">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Usuários</span>
                            <div class="text-xl font-bold text-info-600 dark:text-info-400 mt-1">{{ $entities['users'] ?? 0 }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-center">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Agendamentos</span>
                            <div class="text-xl font-bold text-warning-600 dark:text-warning-400 mt-1">{{ $entities['appointments'] ?? 0 }}</div>
                        </div>
                    </div>

                    <div class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-white/5 p-3.5 rounded-xl border border-gray-200 dark:border-white/10 leading-relaxed">
                        {{ $results['summary'] ?? $sandbox->message }}
                    </div>

                    {{-- Formulário de Laudo Humano --}}
                    <div class="space-y-3 pt-2 border-t border-gray-100 dark:border-white/10">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            Parecer do Auditor (Validação Humana)
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 text-xs text-gray-800 dark:text-gray-200 cursor-pointer">
                                <input type="radio" wire:model.live="validationStatus" value="approved" class="text-success-600 focus:ring-success-500">
                                <span class="font-semibold text-success-600 dark:text-success-400">Aprovar Restauração (Íntegro)</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-800 dark:text-gray-200 cursor-pointer">
                                <input type="radio" wire:model.live="validationStatus" value="rejected" class="text-danger-600 focus:ring-danger-500">
                                <span class="font-semibold text-danger-600 dark:text-danger-400">Rejeitar (Inconsistência)</span>
                            </label>
                        </div>
                        <textarea
                            wire:model.defer="validationNotes"
                            rows="2"
                            placeholder="Adicione observações da auditoria (ex: todos os agendamentos e cadastros foram preservados)..."
                            class="w-full text-xs rounded-xl border-gray-300 dark:border-white/10 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-white/10">
                        <x-filament::button size="sm" color="gray" wire:click="closeAuditModal">
                            Fechar
                        </x-filament::button>
                        <x-filament::button size="sm" color="primary" wire:click="submitValidation" icon="heroicon-m-check">
                            Salvar Laudo de Auditoria
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
