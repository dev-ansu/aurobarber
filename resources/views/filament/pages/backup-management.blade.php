<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Status de Saúde dos Backups -->
        @php($status = $this->backupStatus())
        <div class="rounded-2xl p-5 shadow-sm ring-1 {{ ($status['is_healthy'] ?? false) ? 'bg-emerald-50/60 ring-emerald-600/20 text-emerald-950 dark:bg-emerald-950/20 dark:text-emerald-200 dark:ring-emerald-500/20' : 'bg-amber-50/70 ring-amber-600/20 text-amber-950 dark:bg-amber-950/20 dark:text-amber-200 dark:ring-amber-500/20' }}">
            <div class="flex items-start gap-4">
                <span class="text-3xl">{{ ($status['is_healthy'] ?? false) ? '🛡️' : '⚠️' }}</span>
                <div class="flex-1">
                    <h3 class="font-bold text-base">
                        {{ ($status['is_healthy'] ?? false) ? 'Proteção Local Ativa' : 'Atenção aos Backups do Sistema' }}
                    </h3>
                    <p class="text-sm mt-1">
                        {{ $status['message'] ?? '' }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-4 text-xs font-semibold">
                        <span>Total de backups armazenados: <strong>{{ $status['total_backups'] ?? 0 }}</strong> (Retenção automática: últimos 10)</span>
                        @if (!empty($status['last_backup_date']))
                            <span>Último backup local: <strong>{{ $status['last_backup_date'] }}</strong></span>
                        @endif
                        @if (!empty($status['cloud_backup_at']))
                            <span class="text-sky-700 dark:text-sky-300">☁️ Último envio ao Google Drive: <strong>{{ $status['cloud_backup_at'] }}</strong></span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <x-filament::section>
            <x-slot name="heading">Como funciona a proteção offline</x-slot>
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <p><strong>🌐 Escopo Multi-Unidade (Toda a Rede):</strong> Como todas as unidades escolares (Matriz e Filiais) compartilham o mesmo banco PostgreSQL unificado, <strong>um único backup salva automaticamente 100% dos dados de todas as escolas cadastradas</strong> (pessoas, contratos, financeiro, caixas, histórico LMS, comunicação da rede e anexos/logotipos de todas as unidades).</p>
                <p>Os arquivos de backup contêm a base completa de dados PostgreSQL e todos os documentos/arquivos enviados ao sistema (<code>storage/app/public</code>), compactados em formato ZIP íntegro.</p>
                <p><strong>☁️ Backup Silencioso em Nuvem (Google Drive 2TB):</strong> Quando configurado, cada backup criado localmente envia simultaneamente uma cópia criptografada e segura para a pasta de 2TB do Google Drive da escola. Se o servidor local sofrer qualquer dano físico ou queima de disco rígido, seus dados continuam 100% seguros na nuvem.</p>
                <p><strong>Dica de Segurança para Operação Local:</strong> O sistema mantém automaticamente os últimos 10 backups locais. Você também pode utilizar o botão <strong>"Copiar p/ Pen Drive"</strong> ao final do expediente para salvar uma cópia física avulsa.</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Backups disponíveis no disco local</x-slot>
            @php($backups = $this->backups())
            @if (count($backups))
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs font-bold text-gray-500 uppercase">
                                <th class="px-3 py-2.5">Arquivo</th>
                                <th class="px-3 py-2.5">Criado em</th>
                                <th class="px-3 py-2.5">Tamanho</th>
                                <th class="px-3 py-2.5 text-right">Ações de Proteção</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($backups as $backup)
                                <tr>
                                    <td class="px-3 py-3 font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <span>📦</span>
                                            <span>{{ $backup['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                                        {{ \Illuminate\Support\Carbon::parse($backup['created_at'])->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-3 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                        {{ number_format($backup['size'] / 1048576, 2, ',', '.') }} MB
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @can('Download:BackupManagement')
                                                <button type="button" wire:click="uploadToGoogleDrive('{{ $backup['name'] }}')" title="Enviar cópia diretamente para o Google Drive na nuvem" class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700 hover:bg-sky-100 dark:bg-sky-950/40 dark:text-sky-300">
                                                    ☁️ Enviar p/ Drive
                                                </button>
                                                <button type="button" wire:click="openExportModal('{{ $backup['name'] }}')" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                    💾 Copiar p/ Pen Drive
                                                </button>
                                                <x-filament::button
                                                    tag="a"
                                                    href="{{ route('admin.backups.download', ['filename' => $backup['name']]) }}"
                                                    size="sm"
                                                    color="gray"
                                                    icon="heroicon-o-arrow-down-tray"
                                                >
                                                    Baixar
                                                </x-filament::button>
                                            @endcan
                                            @if (auth()->user()?->hasRole('super_admin') || auth()->user()?->can('Restore:BackupManagement'))
                                                <button type="button" wire:click="deleteBackup('{{ $backup['name'] }}')" wire:confirm="Tem certeza que deseja excluir permanentemente este arquivo de backup?" class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300">
                                                    🗑️
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                    Ainda não há backups locais. Clique no botão "Criar backup agora" no topo para gerar o primeiro.
                </p>
            @endif
        </x-filament::section>
    </div>

    <!-- Modal Exportar para Pen Drive / Disco Externo -->
    @if ($showExportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>💾</span> Copiar Backup para Pen Drive ou Disco Externo
                    </h3>
                    <button type="button" wire:click="closeExportModal" class="text-gray-400 hover:text-gray-700">✕</button>
                </div>

                <div class="mt-4 space-y-3">
                    <p class="text-xs text-gray-500">
                        Arquivo selecionado: <strong class="text-gray-900 dark:text-white">{{ $exportFilename }}</strong>
                    </p>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Pasta de Destino (Pen Drive / Pasta Externa)
                        </label>
                        <input type="text" wire:model="exportDestination" placeholder="Ex: /media/ansu/PENDRIVE ou /mnt/backup" class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10 font-mono">
                        <p class="text-[11px] text-gray-400 mt-1">
                            No Linux, Pen Drives geralmente são montados automaticamente em <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">/media/usuario/NOME_DO_PENDRIVE</code> ou <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">/mnt/</code>.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-filament::button wire:click="closeExportModal" color="gray" size="sm">
                        Cancelar
                    </x-filament::button>
                    <x-filament::button wire:click="submitExport" color="success" size="sm" icon="heroicon-o-check">
                        Copiar Agora
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
