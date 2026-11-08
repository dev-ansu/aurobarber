<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Barra Superior de Ações do Desenvolvedor --}}
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                    <x-heroicon-o-command-line class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Console Técnico de Observabilidade</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ambiente: <span class="font-mono font-medium text-indigo-600 dark:text-indigo-400">{{ app()->environment() }}</span> | Host: <span class="font-mono">{{ $serverHealth['hostname'] ?? 'N/A' }}</span></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    wire:click="refreshDiagnostics"
                    wire:loading.attr="disabled"
                    type="button"
                    class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm">
                    <x-heroicon-o-arrow-path wire:loading.class="animate-spin" class="w-4 h-4" />
                    <span>Atualizar Diagnóstico</span>
                </button>

                <button
                    wire:click="forceRefreshLicense"
                    wire:loading.attr="disabled"
                    type="button"
                    class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 transition">
                    <x-heroicon-o-key class="w-4 h-4 text-amber-500" />
                    <span>Verificar Licença</span>
                </button>
            </div>
        </div>

        {{-- Alerta de Adulteração de Código (Tampering Alert) se houver --}}
        @if(!empty($codeIntegrity['suspicious_changes']))
            <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 rounded-xl">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="text-sm font-bold text-red-900 dark:text-red-200">ALERTA CRÍTICO: Possível adulteração de código detectada!</h3>
                        <p class="text-xs text-red-700 dark:text-red-300 mt-1">Arquivos de segurança/licença foram modificados localmente neste servidor:</p>
                        <ul class="list-disc list-inside text-xs font-mono text-red-800 dark:text-red-200 mt-2 space-y-0.5">
                            @foreach($codeIntegrity['suspicious_changes'] as $suspicious)
                                <li>{{ $suspicious }}</li>
                            @endforeach
                        </ul>
                        <div class="mt-3">
                            <a href="{{ route('filament.developer.pages.code-integrity') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 dark:text-red-300 hover:underline">
                                <span>Ver diff detalhado na aba de Integridade</span>
                                <x-heroicon-m-arrow-right class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Grid Principal de 4 Cards de Saúde --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Servidor & PHP --}}
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Ambiente & PHP</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                        {{ $serverHealth['php_version'] ?? 'PHP 8.x' }}
                    </span>
                </div>
                <div class="text-xl font-bold text-gray-900 dark:text-white font-mono">
                    {{ $serverHealth['php_sapi'] ?? 'Web' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <p>OS: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $serverHealth['os'] ?? 'N/A' }}</span></p>
                    <p>Memória: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $serverHealth['memory_usage_mb'] ?? 0 }} MB</span> / <span class="font-mono">{{ $serverHealth['memory_limit'] ?? 'N/A' }}</span></p>
                </div>
            </div>

            {{-- Card 2: Armazenamento em Disco --}}
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Espaço em Disco</span>
                    @php
                        $percent = $serverHealth['disk_used_percent'] ?? 0;
                        $diskBadgeClass = $percent > 90 ? 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' : ($percent > 75 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300');
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $diskBadgeClass }}">
                        {{ $percent }}% Usado
                    </span>
                </div>
                <div class="text-xl font-bold text-gray-900 dark:text-white font-mono">
                    {{ $serverHealth['disk_free_gb'] ?? 'N/A' }} GB <span class="text-xs font-normal text-gray-500">livres</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                    <div class="h-2 rounded-full {{ $percent > 90 ? 'bg-red-500' : ($percent > 75 ? 'bg-amber-500' : 'bg-indigo-500') }}" style="width: {{ min(100, (float)$percent) }}%"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total: {{ $serverHealth['disk_total_gb'] ?? 'N/A' }} GB</p>
            </div>

            {{-- Card 3: Banco de Dados PostgreSQL --}}
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Banco de Dados</span>
                    @if(($dbHealth['status'] ?? '') === 'ok')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                            {{ $dbHealth['latency_ms'] ?? 0 }} ms
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300">
                            Erro
                        </span>
                    @endif
                </div>
                <div class="text-xl font-bold text-gray-900 dark:text-white capitalize">
                    {{ $dbHealth['driver'] ?? 'pgsql' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <p>Base: <span class="font-mono text-gray-700 dark:text-gray-300">{{ $dbHealth['database'] ?? 'N/A' }}</span></p>
                    <p>Tabelas: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $dbHealth['table_count'] ?? 0 }}</span> @if($dbHealth['size_mb']) | Tam: <span class="font-mono">{{ $dbHealth['size_mb'] }} MB</span>@endif</p>
                </div>
            </div>

            {{-- Card 4: Licença & Integridade --}}
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Status da Licença</span>
                    @php
                        $licStatus = $licenseHealth['status'] ?? 'unknown';
                        $licBadge = match($licStatus) {
                            'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                            'grace_period' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                            'disabled' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                            default => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $licBadge }}">
                        {{ strtoupper($licStatus) }}
                    </span>
                </div>
                <div class="text-base font-bold text-gray-900 dark:text-white font-mono">
                    {{ $licenseHealth['masked_key'] ?? 'N/A' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <p>Escola: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $licenseHealth['school_name'] ?? 'Padrão' }}</span></p>
                    <p>Expiração: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $licenseHealth['due_date'] ?? 'Sem limite' }}</span></p>
                </div>
            </div>
        </div>

        {{-- Seção: Status de Integridade do Repositório Git --}}
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-shield-check class="w-5 h-5 text-indigo-500" />
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Auditoria de Código & Integridade Git</h3>
                </div>
                <div>
                    @if($codeIntegrity['is_clean'])
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                            <x-heroicon-s-check-circle class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            Código Íntegro (Sem modificações locais)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                            <x-heroicon-s-exclamation-circle class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                            Atenção: {{ count($codeIntegrity['modified_files']) + count($codeIntegrity['untracked_files']) }} arquivos alterados
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <span class="text-gray-500 dark:text-gray-400">Branch Atual:</span>
                    <p class="font-mono font-bold text-gray-900 dark:text-white mt-0.5">{{ $codeIntegrity['branch'] ?? 'N/A' }}</p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <span class="text-gray-500 dark:text-gray-400">Commit Ativo:</span>
                    <p class="font-mono font-bold text-gray-900 dark:text-white mt-0.5">{{ $codeIntegrity['commit_hash'] ?? 'N/A' }} - {{ $codeIntegrity['commit_date'] ?? 'N/A' }}</p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <span class="text-gray-500 dark:text-gray-400">Mensagem:</span>
                    <p class="text-gray-900 dark:text-white truncate mt-0.5" title="{{ $codeIntegrity['commit_message'] ?? '' }}">{{ $codeIntegrity['commit_message'] ?? 'N/A' }}</p>
                </div>
            </div>

            @if(!$codeIntegrity['is_clean'])
                <div class="mt-2 text-xs">
                    <a href="{{ route('filament.developer.pages.code-integrity') }}" class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                        <span>Ver lista completa de arquivos alterados e diff</span>
                        <x-heroicon-m-arrow-right class="w-4 h-4" />
                    </a>
                </div>
            @endif
        </div>

        {{-- Seção: Conectividade de APIs e Serviços Externos --}}
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
            <div class="flex items-center gap-2">
                <x-heroicon-o-signal class="w-5 h-5 text-indigo-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Conectividade & APIs</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Ouro Moderno LMS --}}
                @php $om = $apisHealth['ouro_moderno'] ?? []; @endphp
                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Ouro Moderno LMS</span>
                        <span class="w-2.5 h-2.5 rounded-full {{ ($om['status'] ?? '') === 'ok' ? 'bg-emerald-500' : (($om['status'] ?? '') === 'warning' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $om['url'] ?? 'N/A' }}</p>
                    <p class="text-xs font-medium {{ ($om['status'] ?? '') === 'ok' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-600 dark:text-gray-300' }}">{{ $om['message'] ?? 'Aguardando' }}</p>
                </div>

                {{-- Tailscale / Funnel --}}
                @php $ts = $apisHealth['tailscale'] ?? []; @endphp
                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Tailscale / Funnel</span>
                        <span class="w-2.5 h-2.5 rounded-full {{ ($ts['running'] ?? false) ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">{{ $ts['domain'] ?? ($ts['ip'] ?? 'Local') }}</p>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $ts['funnel_status'] ?? $ts['message'] ?? 'N/A' }}</p>
                </div>

                {{-- SMTP / E-mail --}}
                @php $smtp = $apisHealth['smtp'] ?? []; @endphp
                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">SMTP / E-mail</span>
                        <span class="w-2.5 h-2.5 rounded-full {{ ($smtp['status'] ?? '') === 'ok' ? 'bg-emerald-500' : (($smtp['status'] ?? '') === 'local_or_disabled' ? 'bg-gray-400' : 'bg-red-500') }}"></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $smtp['host'] ?? 'N/A' }}:{{ $smtp['port'] ?? 0 }}</p>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $smtp['message'] ?? 'N/A' }}</p>
                </div>

                {{-- Internet Gateway --}}
                @php $net = $apisHealth['internet'] ?? []; @endphp
                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Acesso à Internet</span>
                        <span class="w-2.5 h-2.5 rounded-full {{ ($net['status'] ?? '') === 'online' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">DNS Gateway</p>
                    <p class="text-xs font-medium {{ ($net['status'] ?? '') === 'online' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600' }}">{{ $net['message'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
