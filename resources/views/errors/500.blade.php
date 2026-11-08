@extends('errors.layout')

@section('title', '500 — Erro no Servidor')
@section('code', 'Erro 500 &bull; Falha no Servidor')
@section('badge_class', 'bg-amber-500/10 text-amber-400 border border-amber-500/20')
@section('tag_class', 'bg-amber-500/10 text-amber-300 border border-amber-500/30')

@section('icon')
    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
    </svg>
@endsection

@section('heading', 'Instabilidade Temporária no Servidor')

@php
    $incidentProtocol = null;
    try {
        $recentIncident = \App\Models\SupportTicket::query()
            ->where('type', 'system_incident')
            ->latest('id')
            ->first();
        if ($recentIncident && $recentIncident->created_at->diffInMinutes(now()) <= 2) {
            $incidentProtocol = $recentIncident->protocol;
        }
    } catch (\Throwable) {
        // Silencioso
    }
@endphp

@section('message')
    Ocorreu uma falha inesperada durante o processamento da sua solicitação.
    @if($incidentProtocol)
        <div class="mt-3 inline-flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-xs font-semibold text-amber-300">
            <span>🛡️ Protocolo de Telemetria:</span>
            <span class="font-mono font-black text-amber-400 select-all">{{ $incidentProtocol }}</span>
        </div>
        <div class="mt-2 text-xs text-slate-400">
            Nossa equipe técnica já foi notificada automaticamente deste incidente.
        </div>
    @else
        O detalhe técnico foi registrado para análise da equipe.
    @endif
@endsection

@if(config('app.debug') && filled($exception?->getMessage()))
    @section('detail')
        <strong>Detalhe técnico (modo debug):</strong><br>
        {{ $exception->getMessage() }}
    @endsection
@endif

@section('actions')
    <button type="button"
            onclick="window.location.reload()"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-6 py-3 text-sm font-bold text-slate-950 transition shadow-lg shadow-amber-500/20 cursor-pointer">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        Tentar Novamente
    </button>

    <button type="button"
            onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ url('/') }}'"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-700/80 px-6 py-3 text-sm font-bold text-slate-200 transition cursor-pointer">
        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Página Anterior
    </button>

    <a href="{{ url('/') }}"
       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-700/80 px-6 py-3 text-sm font-bold text-slate-200 transition">
        Painel Principal
    </a>
@endsection

