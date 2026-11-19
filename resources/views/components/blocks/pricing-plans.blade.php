@props([
    'data' => [],
    'plans' => collect(),
])

@php
    $badge = $data['section_badge'] ?? 'Investimento Acessível & Sem Pegadinhas';
    $title = $data['title'] ?? 'Escolha o plano ideal para o momento da sua barbearia';
    $subtitle = $data['subtitle'] ?? 'Todos os planos incluem página pública de agendamento, atalho PWA, cartão fidelidade e suporte direto.';
    $useDatabase = $data['use_database_plans'] ?? true;

    $saasPlans = $plans;
    if ($saasPlans->isEmpty() && !app()->runningUnitTests()) {
        try {
            $saasPlans = \App\Models\SaasPlan::query()->where('is_active', true)->orderBy('sort_order')->get();
        } catch (\Throwable) {
            $saasPlans = collect();
        }
    }
@endphp

<section id="planos" class="py-20 sm:py-28 bg-slate-900/40 border-b border-slate-800/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-14">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">{{ $badge }}</span>
            <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                {{ $title }}
            </h2>
            <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                {{ $subtitle }}
            </p>
        </div>

        @if($saasPlans->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto items-stretch">
                @foreach($saasPlans as $plan)
                    @php
                        $isRecommended = (bool) $plan->is_featured || str_contains(mb_strtolower($plan->name), 'semestral');
                        $badgeText = $plan->badge ?: ($isRecommended ? 'Mais Escolhido' : null);
                        $features = is_array($plan->features) ? $plan->features : [];
                    @endphp

                    <div @class([
                        'rounded-3xl p-7 flex flex-col justify-between transition duration-300 hover:-translate-y-1 relative',
                        'border-2 border-amber-500 bg-slate-950 shadow-2xl shadow-amber-500/10' => $isRecommended,
                        'border border-slate-800 bg-slate-950/90' => !$isRecommended,
                    ])>
                        @if(!empty($badgeText))
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-amber-500 bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 font-black text-[10px] uppercase tracking-wider shadow whitespace-nowrap">
                                {{ $badgeText }}
                            </div>
                        @endif

                        <div class="space-y-4">
                            @if(!empty($plan->badge) && !$isRecommended)
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $plan->badge }}</span>
                            @elseif($isRecommended)
                                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Plano Recomendado</span>
                            @else
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $plan->cycle_label }}</span>
                            @endif

                            <h3 class="text-xl font-black text-white">{{ $plan->name }}</h3>

                            <div class="flex items-baseline gap-1">
                                <span @class([
                                    'text-3xl font-black font-mono',
                                    'text-amber-400' => $isRecommended,
                                    'text-white' => !$isRecommended,
                                ])>
                                    R$ {{ number_format((float) $plan->monthly_price, 2, ',', '.') }}
                                </span>
                                <span class="text-xs text-slate-400">/mês</span>
                            </div>

                            @if(!empty($plan->description))
                                <p class="text-xs text-slate-400 leading-relaxed">{{ $plan->description }}</p>
                            @endif

                            @if((int) $plan->billing_cycle_months > 1)
                                <div class="text-[11px] text-amber-400/90 font-medium">
                                    Cobrado R$ {{ number_format($plan->cycle_total, 2, ',', '.') }} a cada {{ $plan->billing_cycle_months }} meses
                                </div>
                            @endif

                            <ul class="space-y-2.5 text-xs text-slate-300 pt-3 border-t border-slate-800">
                                @if(!empty($plan->max_units))
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Gestão de até <strong>{{ $plan->max_units }} {{ $plan->max_units === 1 ? 'unidade' : 'unidades' }}</strong></span>
                                    </li>
                                @endif

                                @forelse($features as $feature)
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @empty
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span><strong>Barbeiros & Cadeiras Ilimitados</strong></span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Agendamentos Online Ilimitados</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Página White-Label + App PWA</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Comanda de Bar, Bebidas & Cosméticos</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Comissões + Recibo Jurídico</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Visão Restrita do Barbeiro no Celular</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Cartão Fidelidade Digital Automático</span>
                                    </li>
                                @endforelse
                            </ul>
                        </div>

                        <button
                            type="button"
                            @click="selectedPlanName = '{{ $plan->name }} (R$ {{ number_format((float) $plan->monthly_price, 2, ',', '.') }}/mês)'; selectedPlanId = '{{ $plan->id }}'; leadModalOpen = true"
                            @class([
                                'mt-8 w-full py-3.5 rounded-xl font-black text-xs transition cursor-pointer active:scale-95',
                                'bg-amber-500 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 shadow-lg shadow-amber-500/25' => $isRecommended,
                                'border border-slate-700 hover:border-amber-400/60 bg-slate-800/90 hover:bg-slate-700/90 text-white hover:text-amber-300 font-bold shadow-md' => !$isRecommended,
                            ])
                        >
                            Contratar {{ $plan->name }} ➔
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-slate-400">
                <p>Nenhum plano ativo cadastrado no momento.</p>
            </div>
        @endif
    </div>
</section>
