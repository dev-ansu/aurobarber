<x-filament-panels::page>
    @php
        $backups = $this->backups;
        $allBackups = app(\App\Services\SaasBackupManager::class)->list();
        $totalCount = $allBackups->count();
        $totalBytes = $allBackups->sum('size_bytes');
        $totalFormatted = $totalBytes < 1048576 
            ? round($totalBytes / 1024, 1) . ' KB' 
            : ($totalBytes < 1073741824 ? round($totalBytes / 1048576, 2) . ' MB' : round($totalBytes / 1073741824, 2) . ' GB');
        $latest = $allBackups->first();
    @endphp

    <!-- Cards de Resumo e Status da Retenção -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 shadow-xs">
            <div class="flex items-center gap-3 mb-2">
                <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                    <x-filament::icon icon="heroicon-o-circle-stack" class="w-5 h-5" />
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Total de Backups</span>
                    <h3 class="text-xl font-bold text-gray-950 dark:text-white">{{ $totalCount }} arquivo(s)</h3>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Espaço total em disco: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $totalFormatted }}</span></p>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 shadow-xs">
            <div class="flex items-center gap-3 mb-2">
                <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                    <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5" />
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Retenção Automática</span>
                    <h3 class="text-xl font-bold text-gray-950 dark:text-white">7 Dias</h3>
                </div>
            </div>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Arquivos mais antigos são purgados automaticamente</p>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 shadow-xs">
            <div class="flex items-center gap-3 mb-2">
                <div class="h-10 w-10 rounded-xl bg-primary-500/10 text-primary-500 flex items-center justify-center">
                    <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5" />
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Último Backup</span>
                    <h3 class="text-sm font-bold text-gray-950 dark:text-white">
                        {{ $latest ? $latest['created_at_formatted'] : 'Nenhum ainda' }}
                    </h3>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $latest ? "Arquivo: {$latest['filename']}" : 'Gere um backup no botão acima' }}
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 shadow-xs">
            <div class="flex items-center gap-3 mb-2">
                <div class="h-10 w-10 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5" />
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Rotina no Cron da VPS</span>
                    <h3 class="text-sm font-bold text-gray-950 dark:text-white">Diário às 03:00</h3>
                </div>
            </div>
            <p class="text-xs text-sky-600 dark:text-sky-400 font-medium">Executa dump global + schemas individuais</p>
        </div>
    </div>

    <!-- Filtros de Visualização -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="inline-flex rounded-xl p-1 bg-gray-100 dark:bg-gray-800 text-xs font-medium">
            <button 
                wire:click="$set('filterScope', 'all')"
                type="button"
                class="px-3 py-1.5 rounded-lg transition {{ $filterScope === 'all' ? 'bg-white dark:bg-gray-900 shadow-xs font-bold text-gray-950 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                Todos os Backups ({{ $allBackups->count() }})
            </button>
            <button 
                wire:click="$set('filterScope', 'global')"
                type="button"
                class="px-3 py-1.5 rounded-lg transition {{ $filterScope === 'global' ? 'bg-white dark:bg-gray-900 shadow-xs font-bold text-gray-950 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                🌐 Base Completa (Global) ({{ $allBackups->filter(fn($b) => $b['is_global'])->count() }})
            </button>
            <button 
                wire:click="$set('filterScope', 'tenants')"
                type="button"
                class="px-3 py-1.5 rounded-lg transition {{ $filterScope === 'tenants' ? 'bg-white dark:bg-gray-900 shadow-xs font-bold text-gray-950 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                🏫 Por Cliente / Tenant ({{ $allBackups->filter(fn($b) => !$b['is_global'])->count() }})
            </button>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
            <x-filament::icon icon="heroicon-o-information-circle" class="w-4 h-4 text-amber-500" />
            <span>Formato oficial compactado <code>PostgreSQL Custom Dump (-Fc)</code> com compressão zlib.</span>
        </div>
    </div>

    <!-- Tabela do Quadro Geral -->
    <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 overflow-hidden shadow-xs">
        @if($backups->isEmpty())
            <div class="p-12 text-center">
                <div class="h-16 w-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400">
                    <x-filament::icon icon="heroicon-o-archive-box" class="w-8 h-8" />
                </div>
                <h4 class="text-base font-bold text-gray-900 dark:text-white mb-1">Nenhum backup encontrado neste filtro</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-5">
                    Utilize os botões no topo para gerar o primeiro backup unificado ou individual dos clientes.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-bold uppercase tracking-wider text-[11px]">
                            <th class="px-4 py-3">Escopo</th>
                            <th class="px-4 py-3">Cliente / Descrição</th>
                            <th class="px-4 py-3">Nome do Arquivo</th>
                            <th class="px-4 py-3">Tamanho</th>
                            <th class="px-4 py-3">Criado em</th>
                            <th class="px-4 py-3">Retenção / Expiração</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($backups as $item)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition">
                                <td class="px-4 py-3.5">
                                    @if($item['is_global'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                            <span>GLOBAL</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                            <span>TENANT</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
                                    {{ $item['client_name'] }}
                                    @if(!$item['is_global'])
                                        <span class="block text-[10px] font-mono text-gray-400 font-normal">schema: {{ $item['scope'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 font-mono text-[11px] text-gray-600 dark:text-gray-300">
                                    {{ $item['filename'] }}
                                </td>
                                <td class="px-4 py-3.5 font-bold text-gray-700 dark:text-gray-200">
                                    {{ $item['size_formatted'] }}
                                </td>
                                <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400 font-medium">
                                    {{ $item['created_at_formatted'] }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        <x-filament::icon icon="heroicon-m-clock" class="w-3.5 h-3.5" />
                                        <span>Expira em {{ $item['days_remaining'] }} dia(s)</span>
                                    </span>
                                    <span class="block text-[10px] text-gray-400">até {{ $item['expires_at_formatted'] }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a 
                                            href="{{ route('saas.backups.download', ['filename' => $item['filename']]) }}"
                                            title="Baixar cópia segura deste backup"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold text-xs transition">
                                            <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-3.5 h-3.5" />
                                            <span>Baixar</span>
                                        </a>

                                        <button 
                                            wire:click="deleteBackup('{{ $item['filename'] }}')"
                                            wire:confirm="Tem certeza de que deseja excluir permanentemente o arquivo {{ $item['filename'] }}?"
                                            type="button"
                                            title="Excluir este arquivo"
                                            class="inline-flex items-center justify-center p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-500/10 transition">
                                            <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
