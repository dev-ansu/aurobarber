@php
    $tenant = filament()->getTenant();
@endphp

@if ($tenant)
    <div class="flex items-center gap-2 mr-3" x-data="{ copied: false }">
        <a 
            href="{{ url($tenant->slug) }}" 
            target="_blank" 
            title="Abrir página pública de agendamento em nova aba"
            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-500 border border-amber-500/30 text-xs font-bold transition"
        >
            <span>💈 barber.aurosoft.com.br/{{ $tenant->slug }}</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>

        <button 
            type="button"
            @click="navigator.clipboard.writeText('{{ url($tenant->slug) }}'); copied = true; setTimeout(() => copied = false, 2500)"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-semibold transition cursor-pointer"
            :title="copied ? 'Link copiado para a área de transferência!' : 'Copiar link da página para WhatsApp'"
        >
            <span x-show="!copied">📋 Copiar Link</span>
            <span x-show="copied" class="text-emerald-400 font-bold" x-cloak>✓ Link Copiado!</span>
        </button>
    </div>
@endif
