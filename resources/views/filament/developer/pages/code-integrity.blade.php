<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Barra de Status e Ação --}}
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg {{ $integrity['is_clean'] ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' }}">
                    @if($integrity['is_clean'])
                        <x-heroicon-o-shield-check class="w-6 h-6" />
                    @else
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                    @endif
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $integrity['is_clean'] ? 'Repositório 100% Íntegro' : 'Alterações Locais Detectadas' }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Branch: <span class="font-mono font-medium text-indigo-600 dark:text-indigo-400">{{ $integrity['branch'] ?? 'N/A' }}</span> | 
                        Commit: <span class="font-mono">{{ $integrity['commit_hash'] ?? 'N/A' }}</span> ({{ $integrity['commit_date'] ?? 'N/A' }})
                    </p>
                </div>
            </div>

            <button
                wire:click="refreshIntegrity"
                wire:loading.attr="disabled"
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm">
                <x-heroicon-o-arrow-path wire:loading.class="animate-spin" class="w-4 h-4" />
                <span>Escanear Código Agora</span>
            </button>
        </div>

        {{-- Alerta de Adulteração em Arquivos Críticos --}}
        @if(!empty($integrity['suspicious_changes']))
            <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 rounded-xl space-y-2">
                <div class="flex items-center gap-2 text-red-700 dark:text-red-300 font-bold text-sm">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-600" />
                    <span>AVISO CRÍTICO: Arquivos sensíveis de segurança foram alterados localmente!</span>
                </div>
                <p class="text-xs text-red-600 dark:text-red-400">
                    Foi detectada modificação manual nos seguintes arquivos de controle ou validação de licença:
                </p>
                <div class="p-3 bg-white dark:bg-gray-900 rounded-lg border border-red-200 dark:border-red-900 text-xs font-mono text-red-700 dark:text-red-300">
                    @foreach($integrity['suspicious_changes'] as $file)
                        <div class="flex items-center gap-2 py-0.5">
                            <x-heroicon-m-x-circle class="w-4 h-4 text-red-500 flex-shrink-0" />
                            <span>{{ $file }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Lista de Arquivos Modificados / Não Rastreados --}}
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-document-magnifying-glass class="w-5 h-5 text-indigo-500" />
                <span>Status de Arquivos (Working Tree)</span>
            </h3>

            @if($integrity['is_clean'])
                <div class="p-8 text-center bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
                    <x-heroicon-o-check-circle class="w-10 h-10 text-emerald-500 mx-auto mb-2" />
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Nenhuma modificação local encontrada</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">O código fonte está idêntico à versão do repositório oficial. Ninguém alterou os arquivos de aplicação.</p>
                </div>
            @else
                <div class="space-y-4">
                    {{-- Modificados --}}
                    @if(!empty($integrity['modified_files']))
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Arquivos Modificados / Removidos:</h4>
                            <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-800">
                                        <tr>
                                            <th class="px-4 py-2.5 w-24">Código Git</th>
                                            <th class="px-4 py-2.5">Caminho do Arquivo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 font-mono">
                                        @foreach($integrity['modified_files'] as $item)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                                <td class="px-4 py-2">
                                                    <span class="inline-flex px-2 py-0.5 rounded font-bold text-xs {{ trim($item['code']) === 'M' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' }}">
                                                        {{ $item['code'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $item['file'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Não Rastreados (Untracked) --}}
                    @if(!empty($integrity['untracked_files']))
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Novos Arquivos Criados Localmente (Untracked):</h4>
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-800 rounded-lg font-mono text-xs text-gray-800 dark:text-gray-200 space-y-1">
                                @foreach($integrity['untracked_files'] as $untracked)
                                    <div class="flex items-center gap-2">
                                        <span class="text-indigo-500 font-bold">??</span>
                                        <span>{{ $untracked }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Resumo Diff Stat --}}
                    @if(!empty($integrity['diff_stat']))
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Estatística de Diferenças (git diff --stat):</h4>
                            <pre class="p-3 bg-gray-900 text-green-400 font-mono text-xs rounded-lg overflow-x-auto">{{ $integrity['diff_stat'] }}</pre>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Histórico Recente de Commits --}}
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-indigo-500" />
                <span>Últimos 10 Commits do Repositório</span>
            </h3>

            <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="px-4 py-2.5 w-28">Hash</th>
                            <th class="px-4 py-2.5 w-32">Data</th>
                            <th class="px-4 py-2.5">Mensagem do Commit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($integrity['recent_commits'] ?? [] as $commit)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-2 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $commit['hash'] }}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $commit['date'] }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white font-medium">{{ $commit['message'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

