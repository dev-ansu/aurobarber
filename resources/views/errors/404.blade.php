@extends('errors.layout')

@section('title', '404 — Página Não Encontrada')
@section('code', 'Erro 404 &bull; Não Encontrado')
@section('badge_class', 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20')
@section('tag_class', 'bg-cyan-500/10 text-cyan-300 border border-cyan-500/30')

@section('icon')
    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6" />
    </svg>
@endsection

@section('heading', 'Página Não Encontrada')

@section('message')
    {{ $exception->getMessage() ?: 'O link acessado não existe, foi removido ou o registro foi excluído do sistema. Verifique o endereço digitado.' }}
@endsection

@section('actions')
    <button type="button"
            onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ url('/') }}'"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-6 py-3 text-sm font-bold text-slate-950 transition shadow-lg shadow-amber-500/20 cursor-pointer">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Voltar para a Página Anterior
    </button>

    <a href="{{ url('/') }}"
       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-700/80 px-6 py-3 text-sm font-bold text-slate-200 transition">
        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
        Painel Principal
    </a>
@endsection

