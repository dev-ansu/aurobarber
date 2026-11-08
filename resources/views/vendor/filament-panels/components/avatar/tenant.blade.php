@props([
    'tenant' => filament()->getTenant(),
])

@php
    $src = filament()->getTenantAvatarUrl($tenant);
    $alt = __('filament-panels::layout.avatar.alt', ['name' => filament()->getTenantName($tenant)]);
@endphp

@if ($src)
    <div {{ $attributes->class(['flex items-center justify-center h-8 max-w-[72px] shrink-0 rounded-lg bg-white px-1.5 py-0.5 shadow-sm ring-1 ring-slate-900/10 dark:bg-slate-800 dark:ring-white/10 select-none overflow-hidden']) }}>
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            class="h-full w-auto max-h-6 object-contain"
            onerror="this.parentElement.style.display='none';"
        />
    </div>
@else
    <x-filament::avatar
        :circular="false"
        :src="$src"
        :alt="$alt"
        :attributes="
            \Filament\Support\prepare_inherited_attributes($attributes)
                ->class(['fi-tenant-avatar'])
        "
    />
@endif

