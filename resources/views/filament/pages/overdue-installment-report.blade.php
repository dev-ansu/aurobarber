<x-filament-panels::page>
    <x-report-document-header title="Relatório de parcelas em atraso" />
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        Parcelas pendentes ou parcialmente pagas, vencidas e com saldo em aberto. Use as faixas para priorizar cobranças.
    </div>

    {{ $this->table }}
</x-filament-panels::page>
