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
                                                color="info"
                                                outlined
                                                icon="heroicon-m-beaker"
                                                wire:click="simulateBackup(@js($item['filename']))"
                                            >
                                                Simular Restore
                                            </x-filament::button>
                                            <x-filament::button
                                                size="xs"
                                                color="warning"
                                                outlined
                                                icon="heroicon-m-arrow-uturn-left"
                                                wire:click="restoreBackup(@js($item['filename']))"
                                                wire:confirm="Tem certeza de que deseja restaurar este backup? Uma cópia de segurança preventiva será gerada automaticamente antes da restauração."
                                            >
                                                Restaurar
                                            </x-filament::button>
                                            <x-filament::button
                                                size="xs"
                                                color="danger"
                                                outlined
                                                icon="heroicon-m-trash"
                                                wire:click="deleteBackup(@js($item['filename']))"
                                                wire:confirm="Excluir este arquivo de backup permanentemente do disco da VPS?"
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
    </div>
</x-filament-panels::page>
