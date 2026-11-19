<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Banner Principal do BarberDoctor -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-850 to-amber-950 p-6 sm:p-8 text-white border border-slate-800 shadow-2xl">
            <div class="relative z-10 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                <div class="space-y-3 flex-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold uppercase tracking-wider border border-amber-500/30">
                        <span>🩺</span>
                        <span>BarberDoctor • Robô Clínico de Saúde</span>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                        Diagnóstico Geral & Saúde do Aurobarber
                    </h2>

                    <p class="text-sm text-slate-300 max-w-2xl leading-relaxed">
                        Varredura contínua de telemetria, integridade do banco PostgreSQL, permissões de storage, criptografia Libsodium, resiliência de backups locais e externos, integridade de redes de filiais e filas em tempo real.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 pt-1">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Último check-up: <strong class="text-slate-200">{{ $report['checked_at'] ?? 'Não executado' }}</strong>
                        </span>
                        <span>•</span>
                        <span>Tempo de resposta: <strong class="text-slate-200">{{ $report['execution_time_ms'] ?? 0 }}ms</strong></span>
                    </div>
                </div>

                <!-- Score Card & Ações -->
                <div class="flex flex-col sm:flex-row xl:flex-col items-center gap-4 shrink-0 bg-slate-900/80 p-5 rounded-2xl border border-slate-700/60 backdrop-blur-md">
                    <div class="flex items-center gap-4">
                        @php
                            $score = $report['score'] ?? 0;
                            $scoreColor = match(true) {
                                $score >= 90 => 'text-emerald-400 border-emerald-500/40 bg-emerald-500/10',
                                $score >= 70 => 'text-amber-400 border-amber-500/40 bg-amber-500/10',
                                default => 'text-rose-400 border-rose-500/40 bg-rose-500/10',
                            };
                        @endphp
                        <div class="w-20 h-20 rounded-2xl border-2 flex flex-col items-center justify-center font-black {{ $scoreColor }}">
                            <span class="text-2xl">{{ $score }}%</span>
                            <span class="text-[9px] uppercase tracking-wider">Score</span>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-white">{{ $report['status_label'] ?? 'Aguardando' }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">
                                <span class="text-emerald-400 font-semibold">{{ $report['summary']['passed'] ?? 0 }} OK</span> • 
                                <span class="text-amber-400 font-semibold">{{ $report['summary']['warning'] ?? 0 }} Alertas</span> • 
                                <span class="text-rose-400 font-semibold">{{ $report['summary']['failed'] ?? 0 }} Críticos</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full pt-2">
                        <button
                            wire:click="runDiagnostics"
                            wire:loading.attr="disabled"
                            type="button"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-amber-500/20 active:scale-95 disabled:opacity-50">
                            <x-filament::icon icon="heroicon-m-sparkles" class="w-4 h-4" wire:loading.remove wire:target="runDiagnostics" />
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4 animate-spin" wire:loading wire:target="runDiagnostics" />
                            <span wire:loading.remove wire:target="runDiagnostics">Examinar Agora</span>
                            <span wire:loading wire:target="runDiagnostics">Auditando...</span>
                        </button>

                        <button
                            wire:click="clearCache"
                            wire:loading.attr="disabled"
                            type="button"
                            title="Limpar Caches do Laravel (optimize:clear)"
                            class="inline-flex items-center justify-center p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition active:scale-95 disabled:opacity-50">
                            <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4" wire:loading.remove wire:target="clearCache" />
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4 animate-spin" wire:loading wire:target="clearCache" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas & Recomendações Prioritárias -->
        @if(!empty($report['recommendations']))
            <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-5 space-y-3">
                <div class="flex items-center gap-2 text-amber-300 font-bold text-sm uppercase tracking-wider">
                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-5 h-5" />
                    <span>Plano de Ação • Recomendações Clínicas ({{ count($report['recommendations']) }})</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    @foreach($report['recommendations'] as $rec)
                        <div class="p-3 rounded-lg border {{ $rec['severity'] === 'danger' ? 'border-rose-500/30 bg-rose-500/10 text-rose-200' : 'border-amber-500/30 bg-amber-500/10 text-amber-200' }} text-xs space-y-1">
                            <div class="font-bold flex items-center justify-between">
                                <span>{{ $rec['title'] }}</span>
                                <span class="uppercase text-[9px] px-1.5 py-0.5 rounded font-bold {{ $rec['severity'] === 'danger' ? 'bg-rose-500/30 text-rose-300' : 'bg-amber-500/30 text-amber-300' }}">
                                    {{ $rec['severity'] === 'danger' ? 'Crítico' : 'Alerta' }}
                                </span>
                            </div>
                            <div class="opacity-90 leading-relaxed">{{ $rec['action'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Filtros por Categoria -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            @php
                $categories = [
                    'all' => 'Todos os Exames',
                    'Infraestrutura' => 'Infraestrutura & BD',
                    'Segurança & Perímetro' => 'Segurança & Cripto',
                    'Resiliência & Backups' => 'Backups & Recuperação',
                    'Multi-Barbearias & Licenças' => 'Barbearias & Filiais',
                    'Filas & Assincronismo' => 'Filas & Jobs',
                ];
            @endphp
            @foreach($categories as $key => $label)
                <button
                    wire:click="setCategory('{{ $key }}')"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $activeCategory === $key ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <!-- Grade de Verificações Clínicas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->getFilteredChecks() as $check)
                @php
                    $cardStatus = match($check['status'] ?? 'ok') {
                        'ok' => [
                            'border' => 'border-emerald-500/30 dark:border-emerald-500/20',
                            'badge' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                            'icon' => 'heroicon-m-check-circle',
                            'iconColor' => 'text-emerald-500',
                            'label' => 'Saudável',
                        ],
                        'warning' => [
                            'border' => 'border-amber-500/40 dark:border-amber-500/30',
                            'badge' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
                            'icon' => 'heroicon-m-exclamation-triangle',
                            'iconColor' => 'text-amber-500',
                            'label' => 'Alerta',
                        ],
                        'danger' => [
                            'border' => 'border-rose-500/40 dark:border-rose-500/30',
                            'badge' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30',
                            'icon' => 'heroicon-m-x-circle',
                            'iconColor' => 'text-rose-500',
                            'label' => 'Crítico',
                        ],
                        default => [
                            'border' => 'border-slate-300 dark:border-slate-800',
                            'badge' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                            'icon' => 'heroicon-m-information-circle',
                            'iconColor' => 'text-slate-400',
                            'label' => 'Info',
                        ],
                    };
                @endphp

                <div class="rounded-xl border {{ $cardStatus['border'] }} bg-white dark:bg-slate-900 p-5 shadow-sm space-y-3 flex flex-col justify-between transition hover:shadow-md">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                {{ $check['category'] }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $cardStatus['badge'] }}">
                                <x-filament::icon :icon="$cardStatus['icon']" class="w-3 h-3 {{ $cardStatus['iconColor'] }}" />
                                <span>{{ $cardStatus['label'] }}</span>
                            </span>
                        </div>

                        <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-snug">
                            {{ $check['name'] }}
                        </h3>

                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            {{ $check['details'] }}
                        </p>
                    </div>

                    @if(!empty($check['recommendation']))
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-amber-600 dark:text-amber-400">
                            <strong>Ação sugerida:</strong> {{ $check['recommendation'] }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full p-8 text-center text-slate-400">
                    Nenhum exame clínico encontrado nesta categoria.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
