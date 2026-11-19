<x-filament-panels::page>
    @php
        $score = $report['score'] ?? 0;
        $scoreColor = match(true) {
            $score >= 90 => 'success',
            $score >= 70 => 'warning',
            default => 'danger',
        };

        $scoreTextColor = match(true) {
            $score >= 90 => 'text-success-600 dark:text-success-400',
            $score >= 70 => 'text-warning-600 dark:text-warning-400',
            default => 'text-danger-600 dark:text-danger-400',
        };

        $scoreBgColor = match(true) {
            $score >= 90 => 'border-success-500/30 bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400',
            $score >= 70 => 'border-warning-500/30 bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400',
            default => 'border-danger-500/30 bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400',
        };
    @endphp

    <div class="space-y-6">
        {{-- 1. HEADER PRINCIPAL DO BARBERDOCTOR --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="max-w-3xl space-y-2">
                    <div class="flex items-center gap-2">
                        <x-filament::badge color="warning">
                            🩺 BarberDoctor • Telemetria &amp; Saúde
                        </x-filament::badge>
                        <x-filament::badge :color="$scoreColor">
                            Score {{ $score }}%
                        </x-filament::badge>
                    </div>

                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                        Diagnóstico Geral &amp; Saúde do Sistema
                    </h2>

                    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                        Varredura contínua de latência PostgreSQL, migrações, permissões de storage, criptografia Libsodium, resiliência de backups em disco e nuvem, integridade de filiais e filas em tempo real.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400 pt-1">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-success-500"></span>
                            Último exame: <strong class="text-gray-700 dark:text-gray-200">{{ $report['checked_at'] ?? 'Não executado' }}</strong>
                        </span>
                        <span>•</span>
                        <span>Tempo de resposta: <strong class="text-gray-700 dark:text-gray-200">{{ $report['execution_time_ms'] ?? 0 }}ms</strong></span>
                    </div>
                </div>

                {{-- Botões de Ação --}}
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button
                        wire:click="runDiagnostics"
                        wire:loading.attr="disabled"
                        color="primary"
                        icon="heroicon-m-sparkles"
                    >
                        <span wire:loading.remove wire:target="runDiagnostics">Examinar Agora</span>
                        <span wire:loading wire:target="runDiagnostics">Auditando...</span>
                    </x-filament::button>

                    <x-filament::button
                        wire:click="clearCache"
                        wire:loading.attr="disabled"
                        color="gray"
                        icon="heroicon-m-trash"
                        title="Limpar Caches do Laravel (optimize:clear)"
                    >
                        <span wire:loading.remove wire:target="clearCache">Otimizar Caches</span>
                        <span wire:loading wire:target="clearCache">Limpando...</span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- 2. CARDS DE KPIS DO DIAGNÓSTICO --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Card 1: Score Global --}}
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Score Clínico</span>
                    <div class="text-2xl font-bold {{ $scoreTextColor }}">
                        {{ $score }}%
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 font-medium">
                        {{ $report['status_label'] ?? 'Aguardando' }}
                    </span>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border {{ $scoreBgColor }}">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6" />
                </div>
            </div>

            {{-- Card 2: Exames Aprovados --}}
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-success-600 dark:text-success-400">Checagens OK</span>
                    <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                        {{ $report['summary']['passed'] ?? 0 }}
                        <span class="font-sans text-xs font-normal text-gray-500 dark:text-gray-400">/ {{ $report['summary']['total'] ?? 0 }} total</span>
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">itens em conformidade total</span>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-success-500/25 bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-6 w-6" />
                </div>
            </div>

            {{-- Card 3: Alertas Pendentes --}}
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-warning-600 dark:text-warning-400">Alertas / Otimizações</span>
                    <div class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                        {{ $report['summary']['warning'] ?? 0 }}
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">requerem atenção do suporte</span>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-warning-500/25 bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6" />
                </div>
            </div>

            {{-- Card 4: Falhas Críticas --}}
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-danger-600 dark:text-danger-400">Falhas Críticas</span>
                    <div class="text-2xl font-bold text-danger-600 dark:text-danger-400">
                        {{ $report['summary']['failed'] ?? 0 }}
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">interrupção de serviços</span>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-danger-500/25 bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                    <x-filament::icon icon="heroicon-o-x-circle" class="h-6 w-6" />
                </div>
            </div>
        </div>

        {{-- 3. PLANO DE AÇÃO / RECOMENDAÇÕES CLÍNICAS --}}
        @if(!empty($report['recommendations']))
            <div class="rounded-xl border border-warning-600/25 bg-warning-50/40 p-5 dark:border-warning-500/20 dark:bg-warning-500/10 space-y-3">
                <div class="flex items-center gap-2 text-warning-800 dark:text-warning-300 font-bold text-sm">
                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                    <span>Plano de Ação • Recomendações Prioritárias ({{ count($report['recommendations']) }})</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    @foreach($report['recommendations'] as $rec)
                        @php
                            $isCritical = $rec['severity'] === 'danger';
                            $recBorder = $isCritical ? 'border-danger-500/30 dark:border-danger-500/20' : 'border-warning-500/30 dark:border-warning-500/20';
                            $recBadgeColor = $isCritical ? 'danger' : 'warning';
                        @endphp
                        <div class="rounded-lg bg-white p-3.5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-1.5 border-l-4 {{ $isCritical ? 'border-l-danger-500' : 'border-l-warning-500' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-xs text-gray-950 dark:text-white">{{ $rec['title'] }}</span>
                                <x-filament::badge :color="$recBadgeColor" size="xs">
                                    {{ $isCritical ? 'Crítico' : 'Alerta' }}
                                </x-filament::badge>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">{{ $rec['action'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 4. FILTROS DE CATEGORIA --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex flex-wrap items-center gap-1 rounded-xl bg-white p-1 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
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
                    <x-filament::button
                        size="xs"
                        :color="$activeCategory === $key ? 'primary' : 'gray'"
                        wire:click="setCategory('{{ $key }}')"
                    >
                        {{ $label }}
                    </x-filament::button>
                @endforeach
            </div>

            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4 shrink-0 text-primary-500" />
                <span>Auditoria clínica em tempo real em conformidade com o ecossistema SaaS.</span>
            </div>
        </div>

        {{-- 5. GRADE DE CHECAGENS CLÍNICAS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->getFilteredChecks() as $check)
                @php
                    $status = $check['status'] ?? 'ok';
                    $badgeColor = match($status) {
                        'ok' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                        default => 'gray',
                    };
                    $badgeLabel = match($status) {
                        'ok' => 'Saudável',
                        'warning' => 'Alerta',
                        'danger' => 'Crítico',
                        default => 'Info',
                    };
                    $iconName = match($status) {
                        'ok' => 'heroicon-m-check-circle',
                        'warning' => 'heroicon-m-exclamation-triangle',
                        'danger' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-information-circle',
                    };
                    $iconColor = match($status) {
                        'ok' => 'text-success-500',
                        'warning' => 'text-warning-500',
                        'danger' => 'text-danger-500',
                        default => 'text-gray-400',
                    };
                    $leftBorder = match($status) {
                        'ok' => 'border-l-success-500',
                        'warning' => 'border-l-warning-500',
                        'danger' => 'border-l-danger-500',
                        default => 'border-l-gray-300 dark:border-l-gray-700',
                    };
                @endphp

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 border-l-4 {{ $leftBorder }} flex flex-col justify-between space-y-4 transition hover:shadow-md">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ $check['category'] }}
                            </span>
                            <x-filament::badge :color="$badgeColor" size="xs">
                                <x-slot name="icon">
                                    <x-filament::icon :icon="$iconName" class="h-3.5 w-3.5 {{ $iconColor }}" />
                                </x-slot>
                                {{ $badgeLabel }}
                            </x-filament::badge>
                        </div>

                        <h3 class="text-sm font-bold text-gray-950 dark:text-white leading-snug">
                            {{ $check['name'] }}
                        </h3>

                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                            {{ $check['details'] }}
                        </p>
                    </div>

                    @if(!empty($check['recommendation']))
                        <div class="pt-3 border-t border-gray-100 dark:border-white/10 text-xs text-warning-700 dark:text-warning-400 flex items-start gap-1.5">
                            <x-filament::icon icon="heroicon-m-arrow-right-circle" class="h-4 w-4 shrink-0 text-warning-500 mt-0.5" />
                            <span><strong>Ação sugerida:</strong> {{ $check['recommendation'] }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-gray-50 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                        <x-filament::icon icon="heroicon-o-funnel" class="h-6 w-6" />
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum exame clínico encontrado nesta categoria.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
