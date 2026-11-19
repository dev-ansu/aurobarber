@props([
    'barbershop',
])

@php
    $brandColor = $barbershop->brand_color ?: '#d97706';
@endphp

<style>
    :root {
        --brand-color: {{ $brandColor }};
    }
    .bg-brand { background-color: var(--brand-color) !important; }
    .text-brand { color: var(--brand-color) !important; }
    .border-brand { border-color: var(--brand-color) !important; }
    .ring-brand { --tw-ring-color: var(--brand-color) !important; }
    [x-cloak] { display: none !important; }

    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #020617; }
    ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 9999px; }
    ::-webkit-scrollbar-thumb:hover { background: #334155; }
</style>
