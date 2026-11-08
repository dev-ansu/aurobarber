<div wire:init="autoCheck" wire:poll.10m="autoCheck">
    @if ($status && !empty($status['has_update']) && !$dismissed)
        <!-- Barra de Notificação Superior (Banner Fino) -->
        <div class="relative isolate flex items-center justify-between gap-x-6 overflow-hidden bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 px-4 py-2.5 sm:px-6 shadow-md z-40 text-white animate-fade-in">
            <div class="flex items-center gap-x-3 text-sm leading-6">
                <span class="flex h-2 w-2 rounded-full bg-white animate-ping"></span>
                <span class="text-base">🚀</span>
                <p>
                    <strong class="font-bold">Há uma nova atualização do Aurosoft disponível!</strong>
                    <span class="hidden md:inline ml-1">Foram publicadas {{ $status['commits_behind'] }} melhorias na branch principal.</span>
                </p>
            </div>

            <div class="flex items-center gap-x-2">
                <button
                    type="button"
                    wire:click="openModal"
                    class="rounded-lg bg-white/25 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-white/35 backdrop-blur-sm transition"
                >
                    Ver novidades
                </button>

                <button
                    type="button"
                    wire:click="applyUpdate"
                    wire:confirm="Deseja atualizar o Aurosoft agora? Um backup completo de segurança será gerado automaticamente antes da instalação."
                    class="rounded-lg bg-white px-3 py-1 text-xs font-bold text-amber-700 shadow-sm hover:bg-amber-50 transition"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="applyUpdate">Atualizar Agora</span>
                    <span wire:loading wire:target="applyUpdate">Atualizando...</span>
                </button>

                <button
                    type="button"
                    wire:click="dismiss"
                    title="Dispensar aviso"
                    class="p-1 text-white/80 hover:text-white transition ml-1"
                >
                    <span class="sr-only">Fechar</span>
                    ✕
                </button>
            </div>
        </div>
    @elseif ($backupStatus && !($backupStatus['is_healthy'] ?? true) && !$backupDismissed)
        <!-- Barra de Notificação de Backup Atrasado -->
        <div class="relative isolate flex items-center justify-between gap-x-6 overflow-hidden bg-gradient-to-r from-rose-600 via-rose-500 to-amber-600 px-4 py-2.5 sm:px-6 shadow-md z-40 text-white animate-fade-in">
            <div class="flex items-center gap-x-3 text-sm leading-6">
                <span class="text-base">⚠️</span>
                <p>
                    <strong class="font-bold">Atenção aos Backups do Sistema:</strong>
                    <span class="hidden md:inline ml-1">
                        @if (($backupStatus['total_backups'] ?? 0) === 0)
                            Nenhum backup local foi encontrado. Recomendamos gerar uma cópia de segurança imediatamente!
                        @else
                            O último backup foi realizado há {{ $backupStatus['last_backup_age_hours'] ?? 'muitas' }} horas. Mantenha os dados da escola protegidos!
                        @endif
                    </span>
                </p>
            </div>

            <div class="flex items-center gap-x-2">
                <button
                    type="button"
                    wire:click="createBackupNow"
                    class="rounded-lg bg-white px-3 py-1 text-xs font-bold text-rose-700 shadow-sm hover:bg-rose-50 transition"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="createBackupNow">🛡️ Criar Backup Agora</span>
                    <span wire:loading wire:target="createBackupNow">Criando backup...</span>
                </button>

                <a
                    href="{{ route('filament.admin.pages.backup-management') }}"
                    class="rounded-lg bg-white/20 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-white/30 backdrop-blur-sm transition"
                >
                    Gerenciar
                </a>

                <button
                    type="button"
                    wire:click="dismissBackupAlert"
                    title="Lembrar mais tarde"
                    class="p-1 text-white/80 hover:text-white transition ml-1"
                >
                    <span class="sr-only">Fechar</span>
                    ✕
                </button>
            </div>
        </div>
    @endif

    <!-- Modal Centralizado de Atualização do Sistema -->
    @if ($modalOpen && !empty($status['has_update']))
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-950/70 p-4 backdrop-blur-sm transition-opacity animate-fade-in">
            <div class="relative w-full max-w-xl rounded-3xl bg-white p-6 md:p-8 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-gray-900 dark:text-gray-100">
                <!-- Botão Fechar -->
                <button
                    type="button"
                    x-on:click="open = false"
                    wire:click="closeModal"
                    class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                    title="Fechar"
                >
                    <span class="text-xl leading-none">✕</span>
                </button>

                <!-- Cabeçalho do Modal -->
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-3xl text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        🚀
                    </div>
                    <div>
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            Nova Versão Disponível
                        </span>
                        <h3 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                            Atualização do Sistema Aurosoft
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Canal oficial: <strong>{{ $status['target_branch'] }}</strong> • {{ $status['commits_behind'] }} nova(s) melhoria(s)
                        </p>
                    </div>
                </div>

                <!-- Mensagem Principal -->
                <div class="mt-5 space-y-4 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                    <p>
                        Uma nova atualização foi homologada com correções e novas funcionalidades para a sua escola.
                        <strong>Gostaria de fazer a atualização agora?</strong>
                    </p>

                    <!-- Novidades / Commits -->
                    @if (!empty($status['new_commits']))
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                O que mudou nesta atualização:
                            </h4>
                            <ul class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400 font-mono max-h-36 overflow-y-auto">
                                @foreach (array_slice($status['new_commits'], 0, 5) as $idx => $commitMsg)
                                    <li wire:key="commit-{{ $idx }}" class="flex items-start gap-2">
                                        <span class="text-amber-500">•</span>
                                        <span>{{ $commitMsg }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Aviso de Segurança -->
                    <div class="flex items-center gap-3 rounded-2xl bg-emerald-50/80 p-3.5 text-xs text-emerald-950 dark:bg-emerald-950/30 dark:text-emerald-300 border border-emerald-600/20">
                        <span class="text-xl">🛡️</span>
                        <div>
                            <p class="font-bold">Backup 100% Automático e Seguro</p>
                            <p class="text-[11px] text-emerald-800/80 dark:text-emerald-300/80 mt-0.5">
                                O sistema criará uma cópia completa dos seus dados antes de aplicar qualquer alteração.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Rodapé / Botões de Ação -->
                <div class="mt-7 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        x-on:click="open = false"
                        wire:click="closeModal"
                        class="inline-flex justify-center rounded-xl px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                    >
                        Lembrar Mais Tarde
                    </button>

                    <button
                        type="button"
                        wire:click="applyUpdate"
                        wire:confirm="Confirmar a atualização do sistema agora? O processo é rápido e seguro."
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-amber-500/20 hover:from-amber-600 hover:to-orange-600 transition"
                    >
                        <span wire:loading.remove wire:target="applyUpdate">🚀 Atualizar Agora</span>
                        <span wire:loading wire:target="applyUpdate">Atualizando o sistema... aguarde</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
