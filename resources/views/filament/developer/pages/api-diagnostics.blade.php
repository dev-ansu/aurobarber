<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Barra Superior --}}
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                    <x-heroicon-o-signal class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Central de Conectividade, APIs & Backups</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Teste em tempo real a comunicação com serviços externos, Tailscale Mesh, integradores e cópias de segurança.</p>
                </div>
            </div>

            <button
                wire:click="runAllTests"
                wire:loading.attr="disabled"
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm">
                <x-heroicon-o-arrow-path wire:loading.class="animate-spin" class="w-4 h-4" />
                <span>Testar Todas as Conexões</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Painel 1: Ouro Moderno LMS API (Suporte a Múltiplas Escolas / Unidades) --}}
            @php 
                $om = $apis['ouro_moderno'] ?? []; 
                $omItems = $om['items'] ?? [$om];
            @endphp
            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-academic-cap class="w-5 h-5 text-amber-500" />
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Ouro Moderno LMS API (Multi-Unidade)</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ ($om['status'] ?? '') === 'ok' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                        {{ strtoupper($om['status'] ?? 'AGUARDANDO') }}
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach($omItems as $item)
                        <div class="p-3 rounded-lg border border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-800/30 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between font-semibold">
                                <span class="text-gray-900 dark:text-white flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ ($item['status'] ?? '') === 'ok' ? 'bg-emerald-400' : (($item['status'] ?? '') === 'warning' ? 'bg-amber-400' : 'bg-rose-400') }}"></span>
                                    {{ $item['school_name'] ?? 'Unidade' }}
                                </span>
                                <span class="font-mono text-[11px] {{ ($item['status'] ?? '') === 'ok' ? 'text-emerald-500 dark:text-emerald-400' : 'text-amber-500' }}">
                                    {{ $item['latency_ms'] ? $item['latency_ms'].' ms' : 'Offline' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-gray-500">
                                <span>URL Base:</span>
                                <span class="font-mono text-gray-800 dark:text-gray-200 truncate max-w-[240px]">{{ $item['url'] ?? 'Não configurada' }}</span>
                            </div>

                            <div class="text-gray-600 dark:text-gray-400 text-[11px] pt-1 border-t border-gray-100 dark:border-gray-800">
                                {{ $item['message'] ?? 'Status não verificado.' }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <button
                    wire:click="testOuroModerno"
                    wire:loading.attr="disabled"
                    type="button"
                    class="w-full inline-flex justify-center items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition">
                    <x-heroicon-o-bolt class="w-4 h-4 text-amber-500" />
                    <span>Ping Teste em Todas as APIs Ouro Moderno</span>
                </button>
            </div>

            {{-- Painel 2: Tailscale VPN Mesh Privada --}}
            @php $ts = $apis['tailscale'] ?? []; @endphp
            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-globe-alt class="w-5 h-5 text-indigo-500" />
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rede Privada Tailscale (VPN Mesh)</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ ($ts['running'] ?? false) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }}">
                        {{ ($ts['running'] ?? false) ? 'ATIVO (ONLINE)' : 'STANDBY' }}
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Instalado no Sistema:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ ($ts['installed'] ?? false) ? 'Sim' : 'Não / Externo' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Nome / Domínio na Rede:</span>
                        <span class="font-mono text-gray-900 dark:text-white truncate max-w-xs">{{ $ts['domain'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">IP Tailscale:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $ts['ip'] ?? 'Local' }}</span>
                    </div>
                    <div class="py-1">
                        <span class="text-gray-500">Status da Rede:</span>
                        <p class="font-medium text-gray-800 dark:text-gray-200 mt-0.5">{{ $ts['funnel_status'] ?? 'Conectado via Tailscale Mesh' }}</p>
                    </div>
                </div>

                <button
                    wire:click="testTailscale"
                    wire:loading.attr="disabled"
                    type="button"
                    class="w-full inline-flex justify-center items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition">
                    <x-heroicon-o-bolt class="w-4 h-4 text-indigo-500" />
                    <span>Verificar Status Tailscale</span>
                </button>
            </div>

            {{-- Painel 3: Monitoramento de Backups & Segurança Preventiva --}}
            @php $backup = $apis['backup'] ?? []; @endphp
            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-circle-stack class="w-5 h-5 text-emerald-500" />
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Monitoramento de Backups da Escola</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ ($backup['is_healthy'] ?? false) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                        {{ ($backup['is_healthy'] ?? false) ? 'REGULAR' : 'ATENÇÃO' }}
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Último Backup Local:</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $backup['last_local_date'] ?? 'Nenhum gerado' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Cópias Salvas em Disco:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $backup['total_local'] ?? 0 }} arquivo(s)</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Backup Nuvem (Google Drive):</span>
                        <span class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $backup['drive_status'] ?? 'Não configurado' }}</span>
                    </div>
                    <div class="py-1">
                        <span class="text-gray-500">Diagnóstico Preventivo:</span>
                        <p class="font-medium text-gray-800 dark:text-gray-200 mt-0.5">{{ $backup['message'] ?? 'Verifique os backups.' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2">
                    <button
                        wire:click="dispatchBackupReminder"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex justify-center items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-600 text-white transition shadow-sm">
                        <x-heroicon-o-bell-alert class="w-4 h-4" />
                        <span>Disparar Aviso de Backup</span>
                    </button>

                    <a
                        href="/admin/backup-management"
                        class="inline-flex justify-center items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition">
                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                        <span>Abrir Backups</span>
                    </a>
                </div>
            </div>

            {{-- Painel 4: Servidor SMTP / E-mail & Internet --}}
            @php 
                $smtp = $apis['smtp'] ?? []; 
                $net = $apis['internet'] ?? [];
            @endphp
            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-envelope class="w-5 h-5 text-sky-500" />
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Servidor de E-mail (SMTP) & Internet</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ ($net['status'] ?? '') === 'online' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' }}">
                        {{ strtoupper($net['status'] ?? 'OFFLINE') }}
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Conexão Externa (Internet):</span>
                        <span class="font-bold text-emerald-500 dark:text-emerald-400">{{ ($net['status'] ?? '') === 'online' ? 'Ativa ('.$net['latency_ms'].' ms)' : 'Sem Conexão' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Host SMTP:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $smtp['host'] ?? '127.0.0.1' }}:{{ $smtp['port'] ?? 2525 }}</span>
                    </div>
                    <div class="py-1">
                        <span class="text-gray-500">Status SMTP:</span>
                        <p class="font-medium text-gray-800 dark:text-gray-200 mt-0.5">{{ $smtp['message'] ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2">
                    <button
                        wire:click="testSmtp"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex justify-center items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition">
                        <x-heroicon-o-bolt class="w-4 h-4 text-sky-500" />
                        <span>Testar SMTP</span>
                    </button>

                    <button
                        wire:click="testInternet"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex justify-center items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition">
                        <x-heroicon-o-bolt class="w-4 h-4 text-emerald-500" />
                        <span>Ping Internet</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
