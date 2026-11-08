<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Métricas Rápidas Principais -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- MRR -->
            <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Receita Mensal (MRR)</span>
                    <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                        R$ {{ number_format($monthlyRecurringRevenue, 2, ',', '.') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Mensalidades ativas somadas</p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600">
                    <x-filament::icon icon="heroicon-o-banknotes" class="h-6 w-6" />
                </div>
            </div>

            <!-- Escolas / Clientes Ativos -->
            <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Escolas Ativas</span>
                    <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                        {{ $activeLicenses }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $totalClients }} instituições cadastradas</p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-6 w-6" />
                </div>
            </div>

            <!-- Schemas PostgreSQL Isolados na Nuvem -->
            <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Schemas na Nuvem</span>
                    <h3 class="text-2xl font-black text-cyan-600 dark:text-cyan-400 mt-1">
                        {{ $provisionedSchemasCount }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Bancos isolados ativos</p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-600">
                    <x-filament::icon icon="heroicon-o-circle-stack" class="h-6 w-6" />
                </div>
            </div>

            <!-- AuroDoctor (Saúde do Sistema) -->
            <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Saúde do Sistema</span>
                    <h3 class="text-2xl font-black {{ ($doctorReport['status'] ?? '') === 'critical' ? 'text-rose-500' : 'text-emerald-500' }} mt-1">
                        {{ $doctorReport['health_score'] ?? 100 }}%
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ ($doctorReport['status'] ?? 'healthy') === 'healthy' ? '100% Operacional' : ($doctorReport['failed'] ?? 0).' falhas detectadas' }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl {{ ($doctorReport['status'] ?? '') === 'critical' ? 'bg-rose-500/10 text-rose-500' : 'bg-emerald-500/10 text-emerald-500' }} flex items-center justify-center">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6" />
                </div>
            </div>
        </div>

        <!-- SEÇÃO: RAIO-X DE CAPTAÇÃO & TRÁFEGO • LANDING PAGE AUROSOFT -->
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-500">
                        <x-filament::icon icon="heroicon-o-chart-bar-square" class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>Raio-X de Captação • Landing Page Aurosoft</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-purple-500/15 text-purple-400">
                                Motor Próprio • Imune a AdBlock
                            </span>
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Monitoramento nativo de visitas, cliques de demonstração e leads gerados em aurosoft.com.br.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                        Últimos 30 dias
                    </span>
                    <a
                        href="{{ url('/') }}"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3.5 h-3.5" />
                        <span>Abrir Landing Page</span>
                    </a>
                </div>
            </div>

            <div class="p-5 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Visitantes Únicos -->
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Visitantes Únicos</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 text-[10px] font-bold">Hoje: {{ $landingVisitorsToday }}</span>
                        </div>
                        <h5 class="text-2xl font-black text-gray-900 dark:text-white mt-2">
                            {{ number_format($landingVisitors30d, 0, ',', '.') }}
                        </h5>
                        <p class="text-[11px] text-gray-400 mt-0.5">Sem repetição no mesmo dia</p>
                    </div>

                    <!-- Cliques na Demonstração -->
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Testes de Demonstração</span>
                            <span class="text-amber-500 font-bold">🎯 Interesse</span>
                        </div>
                        <h5 class="text-2xl font-black text-amber-500 mt-2">
                            {{ number_format($landingDemoClicks, 0, ',', '.') }}
                        </h5>
                        <p class="text-[11px] text-gray-400 mt-0.5">Acessos ao ambiente interativo</p>
                    </div>

                    <!-- Leads Solicitados -->
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Leads Qualificados</span>
                            <span class="text-emerald-500 font-bold">📋 Formulário</span>
                        </div>
                        <h5 class="text-2xl font-black text-emerald-500 mt-2">
                            {{ number_format($landingLeadsSubmitted, 0, ',', '.') }}
                        </h5>
                        <p class="text-[11px] text-gray-400 mt-0.5">Contatos reais recebidos</p>
                    </div>

                    <!-- Taxa de Conversão -->
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Taxa de Conversão</span>
                            <span class="text-cyan-500 font-bold">📈 Desempenho</span>
                        </div>
                        <h5 class="text-2xl font-black text-cyan-500 mt-2">
                            {{ $landingConversionRate }}%
                        </h5>
                        <p class="text-[11px] text-gray-400 mt-0.5">Visitas ➔ Demonstração/Lead</p>
                    </div>
                </div>

                <!-- Detalhamento de Origens & Dispositivos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                    <!-- Principais Origens -->
                    <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800/80">
                        <h6 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-m-globe-alt" class="w-4 h-4 text-gray-400" />
                            <span>Principais Canais de Origem</span>
                        </h6>
                        @if(empty($landingTopSources))
                            <p class="text-xs text-gray-400 italic py-2">Aguardando primeiras visitas para mapear origens...</p>
                        @else
                            <div class="space-y-2">
                                @foreach($landingTopSources as $source)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-medium text-gray-600 dark:text-gray-300 capitalize flex items-center gap-1.5">
                                            @if($source['source'] === 'whatsapp')
                                                <span class="text-emerald-500">💬</span>
                                            @elseif($source['source'] === 'instagram')
                                                <span class="text-pink-500">📸</span>
                                            @elseif($source['source'] === 'google')
                                                <span class="text-blue-500">🔍</span>
                                            @else
                                                <span class="text-gray-400">🔗</span>
                                            @endif
                                            {{ $source['source'] }}
                                        </span>
                                        <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $source['count'] }} acessos</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Dispositivos -->
                    <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800/80">
                        <h6 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-m-device-phone-mobile" class="w-4 h-4 text-gray-400" />
                            <span>Dispositivos dos Diretores</span>
                        </h6>
                        <div class="grid grid-cols-2 gap-3 text-center">
                            <div class="p-3 rounded-lg bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                                <span class="text-lg block">📱</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white mt-1 block">
                                    {{ $landingDevices['mobile'] ?? 0 }} acessos
                                </span>
                                <span class="text-[10px] text-gray-400">Smartphones</span>
                            </div>
                            <div class="p-3 rounded-lg bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                                <span class="text-lg block">💻</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white mt-1 block">
                                    {{ $landingDevices['desktop'] ?? 0 }} acessos
                                </span>
                                <span class="text-[10px] text-gray-400">Computador / Desktop</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO: AURODOCTOR • SAÚDE & DIAGNÓSTICO DO SAAS -->
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500">
                        <x-filament::icon icon="heroicon-o-shield-check" class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>AuroDoctor • Robô Clínico do SaaS</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ ($doctorReport['status'] ?? 'healthy') === 'healthy' ? 'bg-emerald-500/15 text-emerald-500' : 'bg-rose-500/15 text-rose-500' }}">
                                {{ ($doctorReport['status'] ?? 'healthy') === 'healthy' ? 'Sistema Saudável' : 'Atenção Necessária' }}
                            </span>
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Varredura automática contínua de integridade do banco, CRUDs com Faker, motor financeiro e rotas.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        wire:click="runDoctor"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-amber-500 text-slate-950 hover:bg-amber-400 transition shadow-sm active:scale-95 disabled:opacity-50">
                        <span wire:loading.remove wire:target="runDoctor">
                            <x-filament::icon icon="heroicon-m-sparkles" class="w-4 h-4 text-slate-950" />
                        </span>
                        <span wire:loading wire:target="runDoctor" class="animate-spin">
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4 text-slate-950" />
                        </span>
                        <span wire:loading.remove wire:target="runDoctor">Executar Varredura Agora</span>
                        <span wire:loading wire:target="runDoctor">Varrendo...</span>
                    </button>
                    <a
                        href="{{ url('/doctor') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <x-filament::icon icon="heroicon-o-document-chart-bar" class="w-4 h-4" />
                        <span>Ver Relatório Clínico Completo</span>
                    </a>
                </div>
            </div>

            @if($doctorReport)
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 bg-gray-50/50 dark:bg-gray-800/30">
                    <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                        <span class="text-[11px] text-gray-400 uppercase font-semibold">Total de Testes</span>
                        <div class="text-lg font-black text-gray-800 dark:text-white mt-0.5">{{ $doctorReport['total_checks'] }} checagens</div>
                    </div>
                    <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                        <span class="text-[11px] text-emerald-500 uppercase font-semibold">Testes Aprovados</span>
                        <div class="text-lg font-black text-emerald-500 mt-0.5">{{ $doctorReport['passed'] }} OK</div>
                    </div>
                    <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                        <span class="text-[11px] text-rose-500 uppercase font-semibold">Falhas Críticas</span>
                        <div class="text-lg font-black {{ $doctorReport['failed'] > 0 ? 'text-rose-500' : 'text-gray-400' }} mt-0.5">{{ $doctorReport['failed'] }} falhas</div>
                    </div>
                    <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                        <span class="text-[11px] text-gray-400 uppercase font-semibold">Último Exame</span>
                        <div class="text-xs font-bold text-gray-700 dark:text-gray-300 mt-1">
                            {{ \Carbon\Carbon::parse($doctorReport['executed_at'])->diffForHumans() }} ({{ $doctorReport['duration_seconds'] }}s)
                        </div>
                    </div>
                </div>
            @else
                <div class="p-6 text-center text-sm text-gray-500">
                    Nenhum exame recente executado pelo AuroDoctor. Clique em <strong>"Executar Varredura Agora"</strong> para avaliar a saúde do SaaS.
                </div>
            @endif
        </div>

        <!-- Seção: Ambiente de Demonstração & Tenants PostgreSQL Isolados -->
        <div class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 text-2xl shrink-0">
                        🧪
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                Ambiente de Demonstração (Demo Tenant)
                            </h2>
                            @if($demoTenantInfo)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Ativo & Isolado
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-500/10 text-gray-400 border border-gray-500/20">
                                    Aguardando Inicialização
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Schema PostgreSQL <code class="text-amber-500 font-mono">"demo"</code> 100% isolado do banco principal. Perfeito para demonstrar o sistema para novas escolas sem misturar dados.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        wire:click="resetDemo"
                        wire:confirm="Tem certeza que deseja resetar o ambiente Demo? Todas as alterações feitas durante testes serão recriadas do zero."
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-gray-950 shadow-md transition">
                        <x-filament::icon icon="heroicon-o-arrow-path" wire:loading.class="animate-spin" class="w-4 h-4" />
                        <span wire:loading.remove>⚡ Resetar Demo em 1 Clique</span>
                        <span wire:loading>Resetando Schema Demo...</span>
                    </button>

                    <a
                        href="{{ route('demo.enter') }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-100 border border-gray-700 shadow-sm transition">
                        <span>🚀 Abrir Aurosoft Demo</span>
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50/50 dark:bg-gray-950/40">
                <!-- Card 1: Métricas do Schema -->
                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 space-y-3">
                    <div class="text-xs font-bold uppercase tracking-wider text-gray-400">Status Técnico do Schema</div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-gray-400">PostgreSQL Schema:</span>
                            <span class="font-mono font-bold text-amber-500">demo</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-gray-400">Tabelas Criadas:</span>
                            <span class="font-bold text-gray-200">{{ $demoTenantInfo['tables_count'] ?? 0 }} tabelas</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-400">Espaço em Disco:</span>
                            <span class="font-mono font-bold text-emerald-400">{{ $demoTenantInfo['size'] ?? '0 MB' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Credenciais do Prospect -->
                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 space-y-3">
                    <div class="text-xs font-bold uppercase tracking-wider text-gray-400">Acesso de Demonstração</div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-gray-400">E-mail:</span>
                            <span class="font-mono font-bold text-indigo-400 select-all">demo@aurosoft.com.br</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-gray-400">Senha:</span>
                            <span class="font-mono font-bold text-indigo-400 select-all">demo123</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-400">Perfil:</span>
                            <span class="font-bold text-gray-200">Super Admin (Acesso Total)</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Como Apresentar para o Cliente -->
                <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-gray-400">Guia de Demonstração Comercial</div>
                    <p class="text-xs text-gray-400 leading-relaxed">
                        Para apresentar a uma nova escola, abra o <strong class="text-white">Tailscale Funnel</strong> ou compartilhe o link <code class="text-amber-400">/demo/entrar</code>.
                    </p>
                    <p class="text-[11px] text-gray-500">
                        O prospect pode testar matrículas, notas e relatórios à vontade. Ao terminar, basta clicar em <strong class="text-amber-400">Resetar Demo</strong> para restaurar os dados originais.
                    </p>
                </div>
            </div>
        </div>

        <!-- SEÇÃO: TODOS OS SCHEMAS DE TENANTS ATIVOS NO POSTGRESQL -->
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-cyan-500/10 text-cyan-500">
                        <x-filament::icon icon="heroicon-o-circle-stack" class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white">Schemas de Clientes no PostgreSQL (Multi-Tenancy)</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Cada escola na nuvem possui seu próprio banco de dados isolado via Schema nativo do PostgreSQL.</p>
                    </div>
                </div>

                <a
                    href="{{ url('/licenses') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-200 transition">
                    <span>Gerenciar Licenças & Provisionamento</span>
                    <x-filament::icon icon="heroicon-o-chevron-right" class="w-4 h-4" />
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-950/60 text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-800 font-semibold">
                        <tr>
                            <th class="px-5 py-3">Nome do Schema</th>
                            <th class="px-5 py-3">Tipo / Uso</th>
                            <th class="px-5 py-3 text-center">Tabelas</th>
                            <th class="px-5 py-3 text-center">Tamanho em Disco</th>
                            <th class="px-5 py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-300">
                        @forelse($allTenants as $tenant)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <td class="px-5 py-3 font-mono font-bold text-amber-400">
                                    {{ $tenant['name'] }}
                                </td>
                                <td class="px-5 py-3">
                                    @if($tenant['is_demo'])
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            <x-filament::icon icon="heroicon-o-beaker" class="w-3 h-3" />
                                            <span>Demonstração Pública</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                            <x-filament::icon icon="heroicon-o-academic-cap" class="w-3 h-3" />
                                            <span>Escola em Produção</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center font-semibold text-gray-300">
                                    {{ $tenant['tables_count'] }} tabelas
                                </td>
                                <td class="px-5 py-3 text-center font-mono font-semibold text-emerald-400">
                                    {{ $tenant['size'] }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($tenant['is_demo'])
                                        <a
                                            href="{{ route('demo.enter') }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-xs text-amber-400 hover:underline font-semibold">
                                            <span>Abrir Demo</span>
                                            <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3.5 h-3.5" />
                                        </a>
                                    @else
                                        <a
                                            href="{{ url('/licenses') }}"
                                            class="inline-flex items-center gap-1 text-xs text-cyan-400 hover:underline font-semibold">
                                            <span>Ver Licença</span>
                                            <x-filament::icon icon="heroicon-o-chevron-right" class="w-3.5 h-3.5" />
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-6 text-center text-gray-500">
                                    Nenhum schema de tenant encontrado no banco de dados além do schema public.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
