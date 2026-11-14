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
        <div class="relative overflow-hidden rounded-3xl border border-slate-800 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-6 sm:p-8 shadow-2xl">
            <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-bold tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>Segurança & Resiliência dos Dados</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Quadro Geral de Backups & Restauração</h2>
                    <p class="text-xs sm:text-sm text-slate-400 max-w-2xl">
                        Dumps em formato binário nativo PostgreSQL comprimidos em ZIP. Toda restauração cria automaticamente uma cópia pré-restauração por segurança.
                    </p>
                </div>

                <div class="shrink-0 flex items-center justify-center h-16 w-16 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 shadow-inner">
                    <x-filament::icon icon="heroicon-o-server-stack" class="w-8 h-8" />
                </div>
            </div>
        </div>

        {{-- 2. CARDS DE KPIS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Card 1: Total de Backups --}}
            <div class="rounded-3xl border border-slate-800 bg-slate-950/90 p-5 shadow-xl flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total de Backups</span>
                    <div class="text-2xl sm:text-3xl font-black text-white font-mono">{{ $totalCount }} <span class="text-xs font-normal text-slate-400">arquivos</span></div>
                    <p class="text-xs text-slate-400">Espaço em disco: <span class="font-bold text-slate-200">{{ $totalFormatted }}</span></p>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20">
                    <x-filament::icon icon="heroicon-o-circle-stack" class="w-6 h-6" />
                </div>
            </div>

            {{-- Card 2: Segurança Pré-Restauração --}}
            <div class="rounded-3xl border border-slate-800 bg-slate-950/90 p-5 shadow-xl flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Garantia Pré-Restauração</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-400">Ativa</div>
                    <p class="text-xs text-slate-400">Backup preventivo gerado antes do restore</p>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                    <x-filament::icon icon="heroicon-o-shield-check" class="w-6 h-6" />
                </div>
            </div>

            {{-- Card 3: Último Backup --}}
            <div class="rounded-3xl border border-slate-800 bg-slate-950/90 p-5 shadow-xl flex items-center justify-between">
                <div class="space-y-1 min-w-0 pr-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Último Arquivo Gerado</span>
                    <div class="text-base sm:text-lg font-bold text-white truncate">
                        {{ $latest ? $latest['created_at_formatted'] : 'Nenhum backup' }}
                    </div>
                    <p class="text-xs text-slate-400 font-mono truncate">
                        {{ $latest ? $latest['filename'] : 'Clique em Gerar Novo Backup' }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-sky-500/10 text-sky-400 flex items-center justify-center border border-sky-500/20 shrink-0">
                    <x-filament::icon icon="heroicon-o-clock" class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- 3. FILTROS DE VISUALIZAÇÃO --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="inline-flex rounded-2xl p-1 bg-slate-900 border border-slate-800 text-xs font-medium">
                <button 
                    wire:click="$set('filterScope', 'all')"
                    type="button"
                    class="px-4 py-2 rounded-xl transition cursor-pointer {{ $filterScope === 'all' ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'text-slate-400 hover:text-white' }}">
                    Todos os Backups ({{ $totalCount }})
                </button>
                <button 
                    wire:click="$set('filterScope', 'global')"
                    type="button"
                    class="px-4 py-2 rounded-xl transition cursor-pointer {{ $filterScope === 'global' ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'text-slate-400 hover:text-white' }}">
                    🌐 Base Completa ({{ $globalCount }})
                </button>
                <button 
                    wire:click="$set('filterScope', 'tenants')"
                    type="button"
                    class="px-4 py-2 rounded-xl transition cursor-pointer {{ $filterScope === 'tenants' ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'text-slate-400 hover:text-white' }}">
                    💈 Por Barbearia ({{ $tenantCount }})
                </button>
            </div>

            <div class="text-xs text-slate-400 flex items-center gap-1.5">
                <x-filament::icon icon="heroicon-o-information-circle" class="w-4 h-4 text-amber-400" />
                <span>Formato nativo <code>PostgreSQL Custom Dump (-Fc)</code> com compressão zlib.</span>
            </div>
        </div>

        {{-- 4. TABELA DE BACKUPS --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-950/90 overflow-hidden shadow-2xl">
            @if($backups->isEmpty())
                <div class="p-12 text-center">
                    <div class="h-16 w-16 mx-auto mb-4 rounded-2xl bg-slate-900 flex items-center justify-center text-slate-400 border border-slate-800">
                        <x-filament::icon icon="heroicon-o-archive-box" class="w-8 h-8" />
                    </div>
                    <h4 class="text-base font-bold text-white mb-1">Nenhum backup encontrado neste filtro</h4>
                    <p class="text-xs text-slate-400 max-w-md mx-auto mb-5">
                        Utilize o botão "+ Gerar Novo Backup" no topo da página para criar o primeiro dump.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-900/80 text-slate-300 border-b border-slate-800 uppercase tracking-wider text-[11px] font-bold">
                                <th class="px-5 py-4">Tipo</th>
                                <th class="px-5 py-4">Barbearia / Escopo</th>
                                <th class="px-5 py-4">Nome do Arquivo</th>
                                <th class="px-5 py-4">Tamanho</th>
                                <th class="px-5 py-4">Criado em</th>
                                <th class="px-5 py-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach($backups as $item)
                                <tr class="hover:bg-slate-900/40 transition">
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        @if($item['is_global'])
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                                <span>🌐 GLOBAL</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                <span>💈 BARBEARIA</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-white text-sm">
                                            {{ $item['barbershop_name'] }}
                                        </div>
                                        @if(!$item['is_global'])
                                            <span class="block text-[11px] font-mono text-slate-400 mt-0.5">slug: /{{ $item['scope'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-slate-300">
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800">
                                            {{ $item['filename'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-bold text-slate-200 whitespace-nowrap">
                                        {{ $item['size_formatted'] }}
                                    </td>
                                    <td class="px-5 py-4 text-slate-400 font-medium whitespace-nowrap">
                                        {{ $item['created_at_formatted'] }}
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <button
                                                wire:click="restoreBackup(@js($item['filename']))"
                                                wire:confirm="Tem certeza de que deseja restaurar este backup? Uma cópia de segurança preventiva será gerada automaticamente antes da restauração."
                                                type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 font-bold text-xs transition cursor-pointer active:scale-95">
                                                <x-filament::icon icon="heroicon-m-arrow-uturn-left" class="w-3.5 h-3.5" />
                                                <span>Restaurar</span>
                                            </button>
                                            <button
                                                wire:click="deleteBackup(@js($item['filename']))"
                                                wire:confirm="Excluir este arquivo de backup permanentemente do disco da VPS?"
                                                type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 font-bold text-xs transition cursor-pointer active:scale-95">
                                                <x-filament::icon icon="heroicon-m-trash" class="w-3.5 h-3.5" />
                                                <span>Excluir</span>
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

    </div>
</x-filament-panels::page>
