<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Banner Principal do AuroDoctor -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-850 to-indigo-950 p-6 sm:p-8 text-white border border-slate-800 shadow-xl">
            <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
                <x-filament::icon icon="heroicon-o-shield-check" class="w-80 h-80 text-amber-400" />
            </div>

            <div class="relative z-10 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                <div class="space-y-2 flex-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold uppercase tracking-wider border border-amber-500/30">
                        <span>🩺</span>
                        <span>AuroDoctor • Robô Clínico Multi-Tenant</span>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                        Diagnóstico Geral & Saúde do Sistema
                    </h2>

                    <p class="text-sm text-slate-300 max-w-xl leading-relaxed">
                        Execute exames clínicos direcionados: analise a infraestrutura global da VPS ou selecione uma instituição parceira específica para auditar seu banco de dados, motor financeiro e integrações isoladas.
                    </p>
                </div>

                <div class="flex flex-col gap-2.5 shrink-0" style="width: 100%; max-width: 380px;">
                    <!-- Seletor de Tenant / Escola -->
                    <div>
                        <label class="block text-[11px] font-bold text-amber-300 uppercase tracking-wider mb-1">
                            Instituição / Alvo
                        </label>
                        <select
                            wire:model.live="selectedTenant"
                            class="w-full rounded-xl bg-slate-800/90 border border-slate-700 text-white text-xs font-bold px-3 py-2.5 focus:border-amber-400 focus:ring focus:ring-amber-400/20 shadow-sm transition">
                            @foreach($this->getAvailableTenants() as $key => $label)
                                <option value="{{ $key }}" class="bg-slate-900 text-white">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Botões de Ação Emparelhados -->
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="runDiagnostics"
                            wire:loading.attr="disabled"
                            type="button"
                            style="flex: 1;"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20 hover:brightness-110 active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="runDiagnostics">
                                <x-filament::icon icon="heroicon-m-sparkles" class="w-4 h-4 text-slate-950" />
                            </span>
                            <span wire:loading wire:target="runDiagnostics" class="animate-spin">
                                <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4 text-slate-950" />
                            </span>
                            <span wire:loading.remove wire:target="runDiagnostics">Examinar Agora</span>
                            <span wire:loading wire:target="runDiagnostics">Examinando...</span>
                        </button>

                        <button
                            wire:click="applySystemUpdate"
                            wire:loading.attr="disabled"
                            type="button"
                            title="Atualizador Completo do Sistema: puxa código novo do Git (origin/saas-prod), aplica migrações em todos os bancos de escolas e limpa caches automaticamente"
                            style="flex: 1.2;"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-black text-xs shadow-md shadow-indigo-600/25 transition disabled:opacity-50 cursor-pointer active:scale-95">
                            <span wire:loading.remove wire:target="applySystemUpdate">🚀</span>
                            <span wire:loading wire:target="applySystemUpdate" class="animate-spin">
                                <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4 text-white" />
                            </span>
                            <span wire:loading.remove wire:target="applySystemUpdate">Atualizar Sistema</span>
                            <span wire:loading wire:target="applySystemUpdate">Atualizando...</span>
                        </button>
                    </div>

                    <!-- Ação secundária: Apenas Migrações (sem git pull) -->
                    <div class="flex justify-end pt-0.5">
                        <button
                            wire:click="runMigrations"
                            wire:loading.attr="disabled"
                            type="button"
                            title="Apenas aplica migrações no banco central e schemas das escolas sem puxar novo código"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-amber-300 transition py-0.5 cursor-pointer">
                            <span wire:loading.remove wire:target="runMigrations">⚡ Apenas sincronizar migrações de banco</span>
                            <span wire:loading wire:target="runMigrations" class="animate-pulse text-amber-300">Sincronizando bancos...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if($report)
            <!-- Card de Diagnóstico do Paciente -->
            <div class="rounded-2xl border {{ $report['status'] === 'healthy' ? 'border-emerald-500/40 bg-emerald-500/5' : ($report['status'] === 'warning' ? 'border-amber-500/40 bg-amber-500/5' : 'border-rose-500/40 bg-rose-500/5') }} p-6 shadow-sm transition">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl {{ $report['status'] === 'healthy' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : ($report['status'] === 'warning' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30' : 'bg-rose-500 text-white shadow-lg shadow-rose-500/30') }}">
                            @if($report['status'] === 'healthy')
                                <x-filament::icon icon="heroicon-m-check-badge" class="h-10 w-10" />
                            @elseif($report['status'] === 'warning')
                                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-10 w-10" />
                            @else
                                <x-filament::icon icon="heroicon-m-x-circle" class="h-10 w-10" />
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold uppercase tracking-wider {{ $report['status'] === 'healthy' ? 'text-emerald-500' : ($report['status'] === 'warning' ? 'text-amber-500' : 'text-rose-500') }}">
                                    Diagnóstico Clínico
                                </span>
                                @php
                                    $allTenants = $this->getAvailableTenants();
                                    $currentTenantLabel = $allTenants[$report['target_tenant'] ?? 'global'] ?? 'Global';
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-500 border border-amber-500/20">
                                    {{ $currentTenantLabel }}
                                </span>
                                <span class="text-xs text-gray-400">• {{ \Carbon\Carbon::parse($report['executed_at'])->format('d/m/Y H:i:s') }} ({{ $report['duration_seconds'] }}s)</span>
                            </div>

                            <h3 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white mt-0.5">
                                @if($report['status'] === 'healthy')
                                    🎉 Seu sistema está 100% saudável e operacional!
                                @elseif($report['status'] === 'warning')
                                    ⚠️ Sistema operacional com alertas de atenção
                                @else
                                    🚨 Atenção: Foram encontradas falhas que necessitam correção!
                                @endif
                            </h3>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="px-5 py-3 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-center shadow-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Score de Saúde</span>
                            <div class="text-2xl font-black {{ $report['health_score'] >= 90 ? 'text-emerald-500' : ($report['health_score'] >= 70 ? 'text-amber-500' : 'text-rose-500') }}">
                                {{ $report['health_score'] }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grade de Métricas Rápidas -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total de Checagens</span>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $report['total_checks'] }}</div>
                </div>

                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Aprovados</span>
                    <div class="text-2xl font-black text-emerald-500 mt-1">{{ $report['passed'] }}</div>
                </div>

                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs">
                    <span class="text-xs font-semibold text-amber-500 uppercase tracking-wider">Alertas</span>
                    <div class="text-2xl font-black text-amber-500 mt-1">{{ $report['warnings'] }}</div>
                </div>

                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs">
                    <span class="text-xs font-semibold text-rose-500 uppercase tracking-wider">Falhas Críticas</span>
                    <div class="text-2xl font-black {{ $report['failed'] > 0 ? 'text-rose-500' : 'text-gray-400' }} mt-1">{{ $report['failed'] }}</div>
                </div>
            </div>

            <!-- Filtros por Categoria -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                @php
                    $categories = [
                        'all' => 'Todas as Checagens',
                        'database' => '🗄️ Banco & Estrutura',
                        'crud' => '👥 Cadastros (Faker)',
                        'financial' => '💰 Motor Financeiro',
                        'routes' => '🌐 Rotas & Telas',
                        'integrations' => '🔌 Integrações & APIs',
                        'simulation' => '⚡ Simulações Atômicas',
                        'telemetry' => '🛡️ Telemetria & Incidentes',
                    ];
                @endphp

                @foreach($categories as $key => $label)
                    <button
                        wire:click="setCategory('{{ $key }}')"
                        type="button"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $activeCategory === $key ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:border-amber-500/40' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <!-- Lista de Checagens Detalhadas -->
            <div class="space-y-3">
                @foreach($report['checks'] as $check)
                    @if($activeCategory === 'all' || $activeCategory === $check['category'])
                        <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border {{ $check['status'] === 'passed' ? 'border-gray-100 dark:border-gray-800' : ($check['status'] === 'warning' ? 'border-amber-500/40 bg-amber-500/5' : 'border-rose-500/50 bg-rose-500/5') }} shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 transition hover:border-gray-300 dark:hover:border-gray-700">
                            <div class="flex items-start gap-3.5">
                                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $check['status'] === 'passed' ? 'bg-emerald-500/10 text-emerald-500' : ($check['status'] === 'warning' ? 'bg-amber-500/10 text-amber-500' : 'bg-rose-500/10 text-rose-500') }}">
                                    @if($check['status'] === 'passed')
                                        <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                                    @elseif($check['status'] === 'warning')
                                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4" />
                                    @else
                                        <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                                    @endif
                                </div>

                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $check['name'] }}
                                        </h4>
                                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500">
                                            {{ $check['duration_ms'] }} ms
                                        </span>
                                    </div>
                                    <p class="text-xs {{ $check['status'] === 'failed' ? 'text-rose-600 dark:text-rose-400 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $check['message'] }}
                                    </p>

                                    @if(!empty($check['error']))
                                        <div class="mt-2 p-3 rounded-xl bg-rose-950/40 border border-rose-500/30 text-rose-300 font-mono text-[11px] overflow-x-auto select-all">
                                            {{ $check['error'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="shrink-0 flex items-center justify-end gap-2">
                                @if($check['id'] === 'telemetry_incidents' && $check['status'] !== 'passed')
                                    <button
                                        wire:click="resolveIncidents"
                                        type="button"
                                        title="Marca o incidente de telemetria como auditado e resolvido"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                                        <x-filament::icon icon="heroicon-m-check-circle" class="w-3.5 h-3.5 text-emerald-400" />
                                        <span>Resolver Incidente</span>
                                    </button>
                                @endif

                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $check['status'] === 'passed' ? 'bg-emerald-500/15 text-emerald-500' : ($check['status'] === 'warning' ? 'bg-amber-500/15 text-amber-500' : 'bg-rose-500/15 text-rose-500') }}">
                                    {{ $check['status'] === 'passed' ? 'Aprovado' : ($check['status'] === 'warning' ? 'Alerta' : 'Falhou') }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <!-- Estado Inicial: Nenhum exame executado ainda -->
            <div class="p-12 text-center rounded-2xl bg-white dark:bg-gray-900 border border-dashed border-gray-300 dark:border-gray-800">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-500 mb-4">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-8 w-8" />
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Nenhum diagnóstico registrado recentemente</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 mb-6">
                    Clique no botão abaixo para o robô percorrer todo o sistema, testar o banco, CRUDs com Faker e emitir o relatório de saúde.
                </p>
                <button
                    wire:click="runDiagnostics"
                    type="button"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 text-slate-950 font-bold text-sm shadow-md hover:bg-amber-400 transition">
                    <x-filament::icon icon="heroicon-m-sparkles" class="w-4 h-4 text-slate-950" />
                    <span>Executar Primeira Varredura Agora</span>
                </button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
