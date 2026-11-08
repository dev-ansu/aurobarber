@php
    $tenant = filament()->getTenant();
@endphp

@if ($tenant)
    <div style="display: flex; align-items: center; gap: 8px; margin-right: 12px;" x-data="{ copied: false }">
        <a 
            href="{{ url($tenant->slug) }}" 
            target="_blank" 
            title="Abrir página pública de agendamento em nova aba"
            style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 10px; background-color: rgba(217, 119, 6, 0.15); color: #f59e0b; border: 1px solid rgba(217, 119, 6, 0.35); font-size: 11px; font-weight: 700; text-decoration: none; transition: all 0.2s;"
            onmouseover="this.style.backgroundColor='rgba(217, 119, 6, 0.25)'"
            onmouseout="this.style.backgroundColor='rgba(217, 119, 6, 0.15)'"
        >
            <span>💈 barber.aurosoft.com.br/{{ $tenant->slug }}</span>
            <svg style="width: 13px; height: 13px; min-width: 13px; min-height: 13px; display: inline-block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>

        <button 
            type="button"
            @click="navigator.clipboard.writeText('{{ url($tenant->slug) }}'); copied = true; setTimeout(() => copied = false, 2500)"
            style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border-radius: 10px; background-color: #1e293b; color: #cbd5e1; border: 1px solid #334155; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s;"
            onmouseover="this.style.backgroundColor='#334155'; this.style.color='#ffffff'"
            onmouseout="this.style.backgroundColor='#1e293b'; this.style.color='#cbd5e1'"
            :title="copied ? 'Link copiado para a área de transferência!' : 'Copiar link da página para WhatsApp'"
        >
            <span x-show="!copied">📋 Copiar Link</span>
            <span x-show="copied" style="color: #34d399; font-weight: 700;" x-cloak>✓ Link Copiado!</span>
        </button>
    </div>
@endif
