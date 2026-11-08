@php
    $telemetry = $license->telemetry ?? [];
    $hasTelemetry = !empty($telemetry);
    $diskUsed = $telemetry['disk_used_percent'] ?? null;
    $diskFree = $telemetry['disk_free_gb'] ?? null;
    $diskTotal = $telemetry['disk_total_gb'] ?? null;
    $isOnline = $license->last_checked_at && $license->last_checked_at->diffInMinutes(now()) <= 15;
@endphp

<div class="space-y-6 text-sm">
    @if(!$hasTelemetry)
        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-5 text-center">
            <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-amber-500/20 text-amber-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h4 class="font-bold text-amber-300">Aguardando Primeiro Envio de Telemetria</h4>
            <p class="mt-1 text-xs text-amber-200/80 max-w-md mx-auto">
                Assim que o servidor da escola (ou o atualizador) realizar a próxima checagem de licença, os dados de hardware, disco, banco e versão serão exibidos automaticamente aqui.
            </p>
        </div>
    @else
        {{-- ALERTA DE DISCO CRÍTICO CASO ESTEJA ACIMA DE 85% --}}
        @if($diskUsed !== null && $diskUsed >= 85)
            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 p-3.5 flex items-center gap-3">
                <div class="p-2 rounded-lg bg-rose-500/20 text-rose-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h5 class="text-xs font-bold text-rose-300 uppercase tracking-wide">Alerta de Armazenamento Crítico na Escola</h5>
                    <p class="text-xs text-rose-200/90 mt-0.5">
                        O servidor da escola está com <strong>{{ $diskUsed }}%</strong> do disco ocupado (restando apenas <strong>{{ $diskFree }} GB</strong> livres). Recomendamos limpeza preventiva de arquivos temporários antes que o sistema trave.
                    </p>
                </div>
            </div>
        @endif

        {{-- CARD SUPERIOR: STATUS GERAL E CONEXÃO --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-800 bg-gray-900/60 p-4">
                <span class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Status do Servidor</span>
                <div class="mt-2 flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        @if($isOnline)
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        @else
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                        @endif
                    </span>
                    <span class="font-bold text-base {{ $isOnline ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ $isOnline ? 'Online (Ativo)' : 'Sem Contato Recente' }}
                    </span>
                </div>
                <div class="mt-1 text-xs text-gray-400">
                    Último contato: {{ $license->last_checked_at?->diffForHumans() ?? 'Nunca' }}
                </div>
            </div>

            <div class="rounded-xl border border-gray-800 bg-gray-900/60 p-4">
                <span class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Rede Privada Tailscale</span>
                <div class="mt-2 font-mono text-sm text-cyan-400 truncate" title="{{ $telemetry['tailscale_domain'] ?? $telemetry['tailscale_ip'] ?? 'Não informado' }}">
                    {{ $telemetry['tailscale_domain'] ?? $telemetry['tailscale_ip'] ?? 'Conectado' }}
                </div>
                <div class="mt-1 text-xs text-gray-400 flex items-center justify-between">
                    <span>IP: {{ $telemetry['tailscale_ip'] ?? ($license->last_ip ?? 'N/A') }}</span>
                    @if(!empty($telemetry['tailscale_domain']) || !empty($telemetry['tailscale_ip']))
                        @php
                            $targetUrl = !empty($telemetry['tailscale_domain']) 
                                ? (str_starts_with($telemetry['tailscale_domain'], 'http') ? $telemetry['tailscale_domain'] : "http://{$telemetry['tailscale_domain']}:8000")
                                : "http://{$telemetry['tailscale_ip']}:8000";
                        @endphp
                        <a href="{{ $targetUrl }}" target="_blank" class="text-indigo-400 hover:underline inline-flex items-center gap-1 font-semibold">
                            Acessar &rarr;
                        </a>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-gray-800 bg-gray-900/60 p-4">
                <span class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Versão do Sistema (Git)</span>
                <div class="mt-2 flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded font-mono text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                        {{ $telemetry['git_commit'] ?? 'Desconhecido' }}
                    </span>
                    <span class="text-xs text-gray-400">({{ $telemetry['git_branch'] ?? 'main' }})</span>
                </div>
                <div class="mt-1 text-xs text-gray-400 truncate" title="{{ $telemetry['git_message'] ?? '' }}">
                    {{ $telemetry['git_message'] ?? 'Commit ativo' }}
                </div>
            </div>
        </div>

        {{-- SEÇÃO 2: MÉTRICAS DE HARDWARE, DISCO & POSTGRESQL --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- ARMAZENAMENTO & SISTEMA --}}
            <div class="rounded-xl border border-gray-800 bg-gray-900/40 p-4 space-y-3">
                <h5 class="font-bold text-gray-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16M9 11h6m-6 4h4"/></svg>
                    Armazenamento & Recursos da Máquina
                </h5>

                @if($diskUsed !== null)
                    <div>
                        <div class="flex justify-between text-xs text-gray-400 mb-1">
                            <span>Espaço em Disco</span>
                            <span class="font-semibold {{ $diskUsed > 85 ? 'text-rose-400' : 'text-emerald-400' }}">{{ $diskUsed }}% em uso</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $diskUsed > 85 ? 'bg-rose-500' : ($diskUsed > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $diskUsed) }}%"></div>
                        </div>
                        <div class="mt-1 text-[11px] text-gray-500 flex justify-between">
                            <span>{{ $diskFree }} GB livres</span>
                            <span>Total: {{ $diskTotal }} GB</span>
                        </div>
                    </div>
                @endif

                <div class="pt-2 grid grid-cols-2 gap-2 text-xs border-t border-gray-800/80">
                    <div>
                        <span class="text-gray-400">Hostname:</span>
                        <div class="font-mono text-gray-200">{{ $telemetry['hostname'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <span class="text-gray-400">Sistema Operacional:</span>
                        <div class="text-gray-200 truncate" title="{{ $telemetry['os'] ?? 'N/A' }}">{{ $telemetry['os'] ?? 'Windows' }}</div>
                    </div>
                    <div>
                        <span class="text-gray-400">Versão PHP:</span>
                        <div class="font-mono text-gray-200">{{ $telemetry['php_version'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <span class="text-gray-400">Uso de Memória RAM:</span>
                        <div class="text-gray-200">{{ !empty($telemetry['memory_usage_mb']) ? $telemetry['memory_usage_mb'].' MB' : 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- BANCO DE DADOS & BACKUPS --}}
            <div class="rounded-xl border border-gray-800 bg-gray-900/40 p-4 space-y-3">
                <h5 class="font-bold text-gray-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                    Banco de Dados & Backups Locais
                </h5>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="rounded-lg bg-gray-800/50 p-2.5">
                        <span class="text-gray-400">Tamanho da Base:</span>
                        <div class="font-bold text-base text-gray-100 mt-0.5">
                            {{ !empty($telemetry['db_size_mb']) ? $telemetry['db_size_mb'].' MB' : 'N/A' }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-800/50 p-2.5">
                        <span class="text-gray-400">Latência do Banco:</span>
                        <div class="font-bold text-base text-emerald-400 mt-0.5">
                            {{ !empty($telemetry['db_latency_ms']) ? $telemetry['db_latency_ms'].' ms' : '< 2 ms' }}
                        </div>
                    </div>
                </div>

                <div class="pt-2 text-xs border-t border-gray-800/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Último Backup Local:</span>
                        <span class="font-bold {{ ($telemetry['backup_healthy'] ?? false) ? 'text-emerald-400' : 'text-amber-400' }}">
                            {{ $telemetry['backup_last_date'] ?? 'Monitorado na próxima checagem' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-gray-400">Hardware ID:</span>
                        <div class="font-mono text-[11px] text-gray-300 truncate" title="{{ $license->hardware_id }}">
                            {{ $license->hardware_id ?? 'Não vinculado' }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-400">Integridade dos Arquivos:</span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            @if(($telemetry['is_clean'] ?? true) === true)
                                <span class="inline-flex items-center gap-1 text-emerald-400 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Código Original Intacto
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-amber-400 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    Arquivos locais com alterações
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
