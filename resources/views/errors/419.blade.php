@extends('errors.layout')

@section('title', '419 — Sessão Expirada')
@section('code', 'Erro 419 &bull; Sessão Expirada')
@section('badge_class', 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20')
@section('tag_class', 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/30')

@section('icon')
    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
@endsection

@section('heading', 'Sua Sessão Expirou')

@section('message')
    A página ficou inativa por muito tempo ou o token de segurança expirou. Recarregue a página para continuar navegando em segurança.
@endsection

@section('actions')
    <button type="button"
            onclick="window.location.reload()"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-6 py-3 text-sm font-bold text-slate-950 transition shadow-lg shadow-amber-500/20 cursor-pointer">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        Recarregar e Continuar
    </button>

    <a href="{{ url('/') }}"
       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-700/80 px-6 py-3 text-sm font-bold text-slate-200 transition">
        Painel Principal
    </a>
@endsection

