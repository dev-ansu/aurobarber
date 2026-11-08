@if($hasSchool)
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-5">
            {{-- Topo do Card --}}
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                        <x-filament::icon icon="heroicon-o-globe-alt" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-black text-gray-900 dark:text-white">
                                Captação & Métricas da Vitrine Pública
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/15 text-emerald-400">
                                Mini-Site Ativo
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Dados de visitantes na internet, interesse em cursos e contatos gerados via WhatsApp.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        href="{{ $showcaseUrl }}"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-amber-500 text-slate-950 hover:bg-amber-400 transition shadow-sm active:scale-95"
                    >
                        <span>Acessar Vitrine da Escola</span>
                        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>

            {{-- 4 Cards de Métricas Rápidas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                {{-- Visitantes Únicos --}}
                <div class="p-3.5 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span class="font-semibold">Visitantes no Mês</span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 text-[10px] font-bold">Hoje: {{ $visitorsToday }}</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white mt-1.5">
                        {{ number_format($visitors30d, 0, ',', '.') }}
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5">Pessoas que visitaram seu catálogo</p>
                </div>

                {{-- Cliques no WhatsApp --}}
                <div class="p-3.5 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span class="font-semibold">Cliques no WhatsApp</span>
                        <span class="text-emerald-500 font-bold">💬 Direto</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-emerald-500 mt-1.5">
                        {{ number_format($whatsappClicks, 0, ',', '.') }}
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5">Iniciaram conversa com a secretaria</p>
                </div>

                {{-- Pré-Matrículas / Leads --}}
                <div class="p-3.5 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span class="font-semibold">Pré-Matrículas no CRM</span>
                        <span class="text-amber-500 font-bold">🎯 Funil</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-amber-500 mt-1.5">
                        {{ number_format($leadsCount, 0, ',', '.') }}
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5">Cadastrados no funil de vendas</p>
                </div>

                {{-- Taxa de Conversão --}}
                <div class="p-3.5 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span class="font-semibold">Conversão da Vitrine</span>
                        <span class="text-cyan-500 font-bold">📈 Resultado</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-cyan-500 mt-1.5">
                        {{ $conversionRate }}%
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5">Visitantes que viraram contato</p>
                </div>
            </div>

            {{-- Detalhamento: Cursos Mais Acessados & Origens --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                {{-- Formações Mais Acessadas --}}
                <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="text-amber-500">🔥</span>
                            <span>Cursos Mais Procurados</span>
                        </span>
                        <span class="text-[11px] text-gray-400">Últimos 30 dias</span>
                    </div>

                    @if(empty($topFormations))
                        <p class="text-xs text-gray-400 italic py-2">
                            Ainda não há acessos suficientes para montar o ranking de cursos.
                        </p>
                    @else
                        <div class="space-y-2.5">
                            @foreach($topFormations as $index => $formation)
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 min-w-0 pr-2">
                                        <span class="w-5 h-5 rounded-full bg-amber-500/10 text-amber-500 font-black text-[11px] flex items-center justify-center shrink-0">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200 truncate">
                                            {{ $formation['name'] }}
                                        </span>
                                    </div>
                                    <span class="font-mono text-gray-500 dark:text-gray-400 font-bold shrink-0">
                                        {{ $formation['views'] }} visualizações
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Principais Canais de Captação --}}
                <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="text-blue-500">📍</span>
                            <span>De onde vêm os interessados</span>
                        </span>
                        <span class="text-[11px] text-gray-400">Últimos 30 dias</span>
                    </div>

                    @if(empty($topSources))
                        <p class="text-xs text-gray-400 italic py-2">
                            Aguardando primeiras visitas para rastrear os canais de divulgação.
                        </p>
                    @else
                        <div class="space-y-2">
                            @foreach($topSources as $src)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-gray-700 dark:text-gray-300 capitalize flex items-center gap-1.5">
                                        @if($src['source'] === 'whatsapp')
                                            <span class="text-emerald-500">💬</span>
                                        @elseif($src['source'] === 'instagram')
                                            <span class="text-pink-500">📸</span>
                                        @elseif($src['source'] === 'google')
                                            <span class="text-blue-500">🔍</span>
                                        @elseif($src['source'] === 'facebook')
                                            <span class="text-blue-600">📘</span>
                                        @else
                                            <span class="text-gray-400">🔗</span>
                                        @endif
                                        {{ $src['source'] }}
                                    </span>
                                    <span class="font-mono font-bold text-gray-900 dark:text-white">
                                        {{ $src['count'] }} acessos
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
@endif
