<x-filament-panels::page>
    @php($status = $this->updateStatus)

    <div class="space-y-6">
        @if (!empty($status['error']))
            <div class="rounded-2xl p-5 shadow-sm ring-1 bg-rose-50/70 ring-rose-600/20 text-rose-950 dark:bg-rose-950/20 dark:text-rose-200 dark:ring-rose-500/20">
                <div class="flex items-start gap-4">
                    <span class="text-3xl">⚠️</span>
                    <div>
                        <h3 class="font-bold text-base">Atenção na verificação de atualizações</h3>
                        <p class="text-sm mt-1">{{ $status['error'] }}</p>
                    </div>
                </div>
            </div>
        @elseif ($status['has_update'])
            <div class="rounded-2xl p-6 shadow-sm ring-1 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 text-amber-950 dark:from-amber-950/30 dark:to-orange-950/30 dark:text-amber-100 dark:border-amber-800">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <span class="text-4xl">🚀</span>
                        <div>
                            <h3 class="text-lg font-bold text-amber-950 dark:text-amber-100">
                                Há uma nova atualização disponível!
                            </h3>
                            <p class="text-sm text-amber-900/80 dark:text-amber-200/80 mt-1">
                                Foram encontradas <strong>{{ $status['commits_behind'] }} melhoria(s)</strong> prontas na branch principal (<code>{{ $status['target_branch'] }}</code>).
                            </p>
                            <p class="text-xs text-amber-800/70 dark:text-amber-300/70 mt-1">
                                Um backup completo de segurança será gerado automaticamente antes da instalação.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 min-w-[220px]">
                        @if ($this->isEncryptedEnv && ! $this->hasEnvKey)
                            <div class="rounded-xl border border-amber-300 bg-amber-100/60 p-3 text-left dark:border-amber-700 dark:bg-amber-900/30">
                                <label class="block text-xs font-bold text-amber-950 dark:text-amber-200 mb-1">
                                    Chave (.env.encrypted):
                                </label>
                                <input
                                    type="password"
                                    wire:model="envEncryptionKey"
                                    placeholder="base64:..."
                                    class="w-full text-xs font-mono rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-slate-900 dark:text-white"
                                />
                                <p class="text-[10px] text-amber-800 dark:text-amber-300 mt-1 leading-tight">
                                    Necessária para decriptar e recarregar novos provedores e rotas no cache.
                                </p>
                            </div>
                        @endif

                        <x-filament::button
                            wire:click="applyUpdate"
                            wire:confirm="Tem certeza que deseja atualizar o sistema agora? Um backup completo de segurança será criado automaticamente antes de aplicar as mudanças."
                            size="lg"
                            color="warning"
                            icon="heroicon-o-arrow-path-rounded-square"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="applyUpdate">Atualizar Sistema Agora</span>
                            <span wire:loading wire:target="applyUpdate">Atualizando o sistema... aguarde</span>
                        </x-filament::button>
                    </div>
                </div>

                @if (!empty($status['new_commits']))
                    <div class="mt-5 pt-4 border-t border-amber-200/60 dark:border-amber-800/60">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900 dark:text-amber-200 mb-2">
                            Novidades desta atualização:
                        </h4>
                        <ul class="space-y-1 text-xs text-amber-900/90 dark:text-amber-100 font-mono">
                            @foreach ($status['new_commits'] as $commitMsg)
                                <li class="flex items-start gap-2">
                                    <span class="text-amber-600 dark:text-amber-400">•</span>
                                    <span>{{ $commitMsg }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-2xl p-5 shadow-sm ring-1 bg-emerald-50/60 ring-emerald-600/20 text-emerald-950 dark:bg-emerald-950/20 dark:text-emerald-200 dark:ring-emerald-500/20">
                <div class="flex items-start gap-4">
                    <span class="text-3xl">✅</span>
                    <div>
                        <h3 class="font-bold text-base">Seu Aurosoft está atualizado!</h3>
                        <p class="text-sm mt-1">
                            Você está utilizando a versão mais recente homologada na branch <strong>{{ $status['target_branch'] }}</strong>.
                        </p>
                        <p class="text-xs mt-2 text-emerald-800/80 dark:text-emerald-300/80">
                            Última verificação realizada em: <strong>{{ $status['checked_at'] }}</strong>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Card de Informações da Versão Atual -->
        <x-filament::section>
            <x-slot name="heading">Informações da Instalação Atual</x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/40">
                    <span class="text-xs text-gray-500 uppercase font-semibold">Branch Atual</span>
                    <p class="mt-1 font-mono font-bold text-gray-900 dark:text-white">{{ $status['current_branch'] }}</p>
                </div>

                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/40">
                    <span class="text-xs text-gray-500 uppercase font-semibold">Canal de Atualização</span>
                    <p class="mt-1 font-mono font-bold text-gray-900 dark:text-white">{{ $status['target_branch'] }}</p>
                </div>

                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/40">
                    <span class="text-xs text-gray-500 uppercase font-semibold">Versão Ativa (Commit)</span>
                    <p class="mt-1 font-mono font-bold text-gray-900 dark:text-white">{{ $status['current_commit']['short_hash'] }}</p>
                    <p class="text-[11px] text-gray-500 truncate mt-0.5" title="{{ $status['current_commit']['message'] }}">
                        {{ $status['current_commit']['message'] }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/40">
                    <span class="text-xs text-gray-500 uppercase font-semibold">Data da Instalação</span>
                    <p class="mt-1 text-gray-900 dark:text-white font-medium">{{ $status['current_commit']['date'] }}</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <span class="font-medium text-gray-600 dark:text-gray-300">Conexão com repositório oficial GitHub: <strong>Online</strong></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        Aurosoft v{{ $status['system_version'] ?? '1.5.0' }} "{{ $status['version_codename'] ?? 'Elrond' }}" • Edição Multi-Unidades
                    </span>
                </div>
            </div>
        </x-filament::section>

        <!-- Reversão para Versão Anterior (Downgrade) -->
        @php($prev = $this->previousVersion)
        @php($snapshot = $this->lastUpdateSnapshot)
        @php($recentVersions = $this->recentVersions)

        <x-filament::section>
            <x-slot name="heading">Reversão de Versão (Downgrade de Segurança)</x-slot>
            <x-slot name="description">
                Caso uma atualização recente apresente incompatibilidade com seu ambiente, você pode retornar para a versão anterior com backup automático.
            </x-slot>

            <div class="space-y-4">
                @if ($prev)
                    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Versão Imediatamente Anterior</span>
                            <p class="mt-1 text-sm font-bold font-mono text-gray-950 dark:text-white">
                                {{ $prev['short_hash'] }} — <span class="font-normal font-sans">{{ $prev['message'] }}</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">Gravada em {{ $prev['date'] }}</p>
                            @if (!empty($snapshot['backup_file']))
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">
                                    📦 Backup pré-atualização disponível: <code>{{ $snapshot['backup_file'] }}</code>
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                            <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model="restoreDatabaseOnDowngrade" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                <span>Restaurar banco pré-atualização</span>
                            </label>

                            <x-filament::button
                                wire:click="applyDowngrade('{{ $prev['full_hash'] }}')"
                                wire:confirm="Deseja realmente voltar o sistema para a versão anterior ({{ $prev['short_hash'] }})? Um backup de segurança do estado atual será criado antes da reversão."
                                color="gray"
                                icon="heroicon-o-arrow-uturn-left"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="applyDowngrade">Reverter para {{ $prev['short_hash'] }}</span>
                                <span wire:loading wire:target="applyDowngrade">Revertendo versão... aguarde</span>
                            </x-filament::button>
                        </div>
                    </div>
                @endif

                @if (!empty($recentVersions) && count($recentVersions) > 1)
                    <details class="text-xs text-gray-600 dark:text-gray-400 pt-2">
                        <summary class="cursor-pointer font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                            Ver histórico de versões recentes disponíveis ({{ count($recentVersions) }} versões)
                        </summary>
                        <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-950 text-gray-600 dark:text-gray-400">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold">Commit</th>
                                        <th class="px-3 py-2 font-semibold">Descrição</th>
                                        <th class="px-3 py-2 font-semibold">Data</th>
                                        <th class="px-3 py-2 font-semibold text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($recentVersions as $ver)
                                        <tr>
                                            <td class="px-3 py-2 font-mono font-bold">{{ $ver['short_hash'] }}</td>
                                            <td class="px-3 py-2">{{ $ver['message'] }}</td>
                                            <td class="px-3 py-2 text-gray-500">{{ $ver['date'] }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <x-filament::button
                                                    size="xs"
                                                    color="gray"
                                                    wire:click="applyDowngrade('{{ $ver['full_hash'] }}')"
                                                    wire:confirm="Deseja voltar para a versão {{ $ver['short_hash'] }} ({{ $ver['message'] }})?"
                                                >
                                                    Voltar para esta
                                                </x-filament::button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endif
            </div>
        </x-filament::section>

        <!-- Como funciona -->
        <x-filament::section>
            <x-slot name="heading">Como funciona a atualização inteligente</x-slot>
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <ul class="list-disc pl-5 space-y-1 text-gray-600 dark:text-gray-300">
                    <li><strong>Sem necessidade de Git manual:</strong> O sistema verifica automaticamente as novidades na branch <code>{{ $status['target_branch'] }}</code> sem você precisar digitar comandos no terminal.</li>
                    <li><strong>Proteção Pré-Atualização:</strong> Antes de aplicar qualquer arquivo novo, o sistema cria um backup completo do banco de dados e dos uploads em <a href="{{ route('filament.admin.pages.backup-management') }}" class="text-primary-600 underline font-semibold">Backup e restauração</a>.</li>
                    <li><strong>Migrações e Otimização Automáticas:</strong> Ao concluir o download, as tabelas do banco de dados são atualizadas e os caches do sistema são renovados automaticamente.</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

