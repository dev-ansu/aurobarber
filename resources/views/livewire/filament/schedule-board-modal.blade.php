<div>
    @if ($canAccessBoard)
        @if ($mode === 'sidebar')
            <div
                class="lg:hidden px-3 py-1.5 mb-1"
                x-on:click="if (window.matchMedia('(max-width: 1024px)').matches && typeof $store !== 'undefined' && $store.sidebar) { $store.sidebar.close() }"
            >
                <div class="w-full">
                    {{ $this->scheduleBoardAction }}
                </div>
                <x-filament-actions::modals />
            </div>
        @else
            <div class="hidden lg:flex items-center">
                {{ $this->scheduleBoardAction }}
                <x-filament-actions::modals />
            </div>
        @endif
    @endif
</div>
