<x-filament-panels::page>
    <x-report-document-header title="Relatório de formas de recebimento" />
    @php($summary = $this->summary)

    <div class="flex flex-col gap-6">
        <x-filament::section>
            <x-slot name="heading">Período analisado</x-slot>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Data inicial
                    <input type="date" wire:model.live="periodStart" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                    @error('periodStart') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="flex flex-col gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Data final
                    <input type="date" wire:model.live="periodEnd" class="fi-input-input block min-h-11 w-full rounded-lg border-none bg-white px-3 py-2.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-900 dark:text-white dark:ring-white/10">
                    @error('periodEnd') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total recebido</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format($summary['amount'], 2, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Recebimentos registrados</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($summary['count'], 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Forma mais usada</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['most_used'] ?? '—' }}</p>
                @if ($summary['most_used'])
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $summary['most_used_count'] }} recebimentos ({{ number_format($summary['most_used_share'], 1, ',', '.') }}%)</p>
                @endif
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Detalhamento por forma de recebimento</x-slot>
            <x-slot name="description">Inclui pagamentos de parcelas lançados no caixa e pagamentos sem lançamento de caixa quando a parcela guarda os dados do recebimento.</x-slot>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
