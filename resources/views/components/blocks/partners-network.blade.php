@props([
    'data' => [],
    'barbershops' => collect(),
])

@php
    $badge = $data['section_badge'] ?? 'Rede de Barbearias Parceiras';
    $title = $data['title'] ?? 'Barbearias de elite que confiam no AuroBarber';
    $subtitle = $data['subtitle'] ?? 'Conheça estabelecimentos que transformaram sua gestão e o atendimento aos seus clientes.';
    $showSearch = $data['show_search'] ?? true;

    $shops = $barbershops;
    if ($shops->isEmpty() && !app()->runningUnitTests()) {
        try {
            $shops = \App\Models\Barbershop::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->whereNotNull('slug')
                ->with(['branches' => fn ($q) => $q->where('is_active', true)->whereNotNull('slug')->orderBy('name')])
                ->orderBy('name')
                ->get();
        } catch (\Throwable) {
            $shops = collect();
        }
    } else {
        $matrizes = $shops->whereNull('parent_id');
        if ($matrizes->isNotEmpty()) {
            $shops = $matrizes;
        }
    }
@endphp

@if($shops->isNotEmpty())
    <section id="barbearias" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80" x-data="{ searchQuery: '' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">{{ $badge }}</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                    {{ $title }}
                </h2>
                <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                    {{ $subtitle }}
                </p>

                @if($showSearch)
                    <div class="mt-6 max-w-md mx-auto">
                        <input
                            type="text"
                            x-model="searchQuery"
                            placeholder="Buscar barbearia ou cidade..."
                            class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-500 transition"
                        />
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-6xl mx-auto">
                @foreach($shops as $shop)
                    @php
                        $logoUrl = !empty($shop->logo_url)
                            ? (str_starts_with($shop->logo_url, 'http') ? $shop->logo_url : asset('storage/' . $shop->logo_url))
                            : null;
                    @endphp
                    <div
                        x-show="!searchQuery || '{{ mb_strtolower($shop->name . ' ' . $shop->city) }}'.includes(searchQuery.toLowerCase())"
                        class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-amber-500/40 transition flex items-center justify-between gap-4 group"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $shop->name }}" class="w-12 h-12 rounded-xl object-cover shrink-0 border border-slate-800">
                            @else
                                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl shrink-0">
                                    💈
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <h3 class="text-sm font-bold text-white truncate group-hover:text-amber-400 transition">{{ $shop->name }}</h3>
                                    @if($shop->branches && $shop->branches->isNotEmpty())
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-300 border border-amber-500/30">
                                            Rede • {{ 1 + $shop->branches->count() }} un.
                                        </span>
                                    @endif
                                </div>
                                @if(!empty($shop->city))
                                    <span class="text-[11px] text-slate-400 block truncate">📍 {{ $shop->city }}</span>
                                @else
                                    <span class="text-[11px] text-slate-500 block truncate">Unidade Ativa</span>
                                @endif
                            </div>
                        </div>

                        <a
                            href="{{ url('/' . $shop->slug) }}"
                            class="px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800 hover:bg-amber-500 hover:text-slate-950 hover:border-amber-500 text-white text-xs font-bold transition shrink-0 whitespace-nowrap"
                        >
                            Agendar &rarr;
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
