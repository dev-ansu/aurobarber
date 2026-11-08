@extends('errors.layout')

@section('title', '403 — Acesso Não Autorizado')
@section('code', 'Erro 403 &bull; Acesso Negado')
@section('badge_class', 'bg-rose-500/10 text-rose-400 border border-rose-500/20')
@section('tag_class', 'bg-rose-500/10 text-rose-300 border border-rose-500/30')

@section('icon')
    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
    </svg>
@endsection

@section('heading', 'Acesso Não Autorizado')

@section('message')
    {{ $exception->getMessage() ?: 'Você não possui permissão para acessar este recurso ou página. Caso acredite que isso é um engano, solicite liberação ao administrador da unidade.' }}
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

