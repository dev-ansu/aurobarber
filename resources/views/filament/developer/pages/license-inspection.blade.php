<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
                    <x-heroicon-o-key class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Auditoria da Licença Aurosoft</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Acompanhamento de validade, chave ativada e comunicação com o webhook central.</p>
                </div>
            </div>

            <button
                wire:click="forceCheck"
                wire:loading.attr="disabled"
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm">
                <x-heroicon-o-arrow-path wire:loading.class="animate-spin" class="w-4 h-4" />
                <span>Forçar Verificação Imediata</span>
            </button>
        </div>

        {{-- Detalhes da Licença --}}
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Estado Atual da Licença</h3>
                @php
                    $status = $license['status'] ?? 'unknown';
                    $badge = match($status) {
                        'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                        'grace_period' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                        'disabled' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                        default => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300',
                    };
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $badge }}">
                    {{ strtoupper($status) }}
                </span>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 rounded-lg text-xs space-y-1">
                <span class="text-gray-500">Mensagem do Servidor de Licenciamento:</span>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $license['message'] ?? 'Sem mensagem.' }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-1">
                    <span class="text-gray-500">Chave de Licença (Ofuscada):</span>
                    <p class="font-mono font-bold text-sm text-gray-900 dark:text-white">{{ $license['masked_key'] ?? 'N/A' }}</p>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-1">
                    <span class="text-gray-500">Escola Registrada:</span>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $license['school_name'] ?? 'Não especificada' }}</p>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-1">
                    <span class="text-gray-500">Data de Vencimento / Renovação:</span>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $license['due_date'] ?? 'Sem limite (Permanente / Local)' }}</p>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-1">
                    <span class="text-gray-500">Dias de Carência (Grace Period):</span>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $license['grace_days'] ?? 5 }} dias</p>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-1.5 md:col-span-2">
                    <span class="text-gray-500 font-medium">Servidor que Autenticou Esta Licença:</span>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            {{ $license['validated_via'] ?? ($license['webhook_url'] ?? 'Validado automaticamente') }}
                        </span>
                    </div>
                </div>

                @if(!empty($license['verification_servers']))
                    <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-lg space-y-2 md:col-span-2">
                        <span class="text-gray-500 font-medium">Ordem de Contingência & Redundância de Servidores:</span>
                        <div class="space-y-1.5">
                            @foreach($license['verification_servers'] as $idx => $serverUrl)
                                <div class="flex items-center gap-2 text-xs font-mono">
                                    <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 font-bold">
                                        #{{ $idx + 1 }}
                                    </span>
                                    <span class="text-gray-700 dark:text-gray-300 truncate {{ ($license['validated_via'] ?? '') === $serverUrl ? 'text-emerald-400 font-bold' : '' }}">
                                        {{ $serverUrl }}
                                    </span>
                                    @if(($license['validated_via'] ?? '') === $serverUrl)
                                        <span class="text-[10px] uppercase font-bold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.2 rounded">
                                            Em Uso
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>

