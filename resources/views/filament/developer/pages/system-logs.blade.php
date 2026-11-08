<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Barra de Ferramentas e Filtros --}}
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                    <x-heroicon-o-document-text class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Auditoria de Logs em Tempo Real</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Exibindo os eventos mais recentes gravados em <span class="font-mono">storage/logs/laravel.log</span></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    wire:click="loadLogs"
                    wire:loading.attr="disabled"
                    type="button"
                    class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm">
                    <x-heroicon-o-arrow-path wire:loading.class="animate-spin" class="w-4 h-4" />
                    <span>Recarregar Logs</span>
                </button>

                <button
                    wire:click="clearAllLogs"
                    wire:confirm="Tem certeza de que deseja limpar o arquivo de logs?"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-red-50 hover:bg-red-100 text-red-700 dark:bg-red-950/40 dark:hover:bg-red-950 dark:text-red-300 transition">
                    <x-heroicon-o-trash class="w-4 h-4" />
                    <span>Limpar Logs</span>
                </button>
            </div>
        </div>

        {{-- Filtros Rápidos de Nível & Pesquisa --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-800 p-1 bg-white dark:bg-gray-900 shadow-sm text-xs">
                <button
                    type="button"
                    wire:click="filterByLevel('ALL')"
                    class="px-3 py-1.5 rounded-md font-medium transition {{ $levelFilter === 'ALL' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Todos
                </button>
                <button
                    type="button"
                    wire:click="filterByLevel('ERROR')"
                    class="px-3 py-1.5 rounded-md font-medium transition {{ $levelFilter === 'ERROR' ? 'bg-red-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Erros & Críticos
                </button>
                <button
                    type="button"
                    wire:click="filterByLevel('WARNING')"
                    class="px-3 py-1.5 rounded-md font-medium transition {{ $levelFilter === 'WARNING' ? 'bg-amber-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Avisos (Warning)
                </button>
                <button
                    type="button"
                    wire:click="filterByLevel('INFO')"
                    class="px-3 py-1.5 rounded-md font-medium transition {{ $levelFilter === 'INFO' ? 'bg-sky-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Informativos (Info)
                </button>
            </div>

            <div class="w-full sm:w-72">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Pesquisar mensagens nos logs..."
                    class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-800 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
        </div>

        {{-- Lista de Entradas de Log --}}
        <div class="space-y-3">
            @if(empty($logs))
                <div class="p-8 text-center bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                    <x-heroicon-o-check-circle class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Nenhum log encontrado para o filtro selecionado</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">O sistema está operando sem registros recentes de erro correspondentes.</p>
                </div>
            @else
                @foreach($logs as $index => $log)
                    @php
                        $level = $log['level'] ?? 'INFO';
                        $levelBadge = match($level) {
                            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 border-red-200 dark:border-red-900',
                            'WARNING' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-900',
                            'INFO', 'NOTICE' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border-sky-200 dark:border-sky-900',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                        };
                    @endphp
                    <div x-data="{ expanded: false }" class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-bold border {{ $levelBadge }}">
                                    {{ $level }}
                                </span>
                                <span class="text-xs font-mono text-gray-400">
                                    {{ $log['timestamp'] }}
                                </span>
                            </div>

                            @if(!empty($log['trace']))
                                <button
                                    @click="expanded = !expanded"
                                    type="button"
                                    class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline inline-flex items-center gap-1">
                                    <span x-text="expanded ? 'Ocultar Stack Trace' : 'Ver Detalhes do Trace'"></span>
                                    <x-heroicon-m-chevron-down class="w-3.5 h-3.5 transition transform" ::class="{ 'rotate-180': expanded }" />
                                </button>
                            @endif
                        </div>

                        <p class="text-xs font-mono text-gray-900 dark:text-gray-100 break-words font-medium">
                            {{ $log['message'] }}
                        </p>

                        @if(!empty($log['trace']))
                            <div x-show="expanded" x-collapse>
                                <pre class="p-3 bg-gray-950 text-gray-300 font-mono text-xs rounded-lg overflow-x-auto max-h-96 mt-2 leading-relaxed">{{ $log['trace'] }}</pre>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-filament-panels::page>

