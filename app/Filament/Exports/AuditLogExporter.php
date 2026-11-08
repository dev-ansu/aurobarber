<?php

namespace App\Filament\Exports;

use App\Models\AuditLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AuditLogExporter extends Exporter
{
    protected static ?string $model = AuditLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('school_legal_name')
                ->label('Escola - Razão social')
                ->state(fn (array $options): string => (string) (data_get($options, 'school.legal_name') ?? ''))
                ->preventFormulaInjection(),
            ExportColumn::make('school_trade_name')
                ->label('Escola - Nome fantasia')
                ->state(fn (array $options): string => (string) (data_get($options, 'school.trade_name') ?? ''))
                ->preventFormulaInjection(),
            ExportColumn::make('school_cnpj')
                ->label('Escola - CNPJ')
                ->state(fn (array $options): string => (string) (data_get($options, 'school.cnpj') ?? ''))
                ->preventFormulaInjection(),
            ExportColumn::make('school_address')
                ->label('Escola - Endereço')
                ->state(fn (array $options): string => self::schoolAddress($options))
                ->preventFormulaInjection(),
            ExportColumn::make('school_email')
                ->label('Escola - E-mail')
                ->state(fn (array $options): string => (string) (data_get($options, 'school.email') ?? ''))
                ->preventFormulaInjection(),
            ExportColumn::make('school_phone')
                ->label('Escola - Telefone')
                ->state(fn (array $options): string => (string) (data_get($options, 'school.phone') ?? ''))
                ->preventFormulaInjection(),
            ExportColumn::make('school_partners')
                ->label('Escola - Sócios')
                ->state(fn (array $options): string => collect(data_get($options, 'school.partners', []))
                    ->map(fn (array $partner): string => trim(($partner['name'] ?? '').' - '.($partner['role'] ?? '')))
                    ->implode('; '))
                ->preventFormulaInjection(),
            ExportColumn::make('created_at')
                ->label('Data e hora')
                ->formatStateUsing(fn ($state): string => $state?->format('d/m/Y H:i:s') ?? ''),
            ExportColumn::make('contract.contract_number')
                ->label('Contrato')
                ->state(fn (AuditLog $record): string => $record->contract?->contract_number
                    ?? data_get($record->changes, 'before.contract_number', ''))
                ->preventFormulaInjection(),
            ExportColumn::make('contract.student.name')
                ->label('Aluno')
                ->state(fn (AuditLog $record): string => $record->contract?->student?->name ?? '')
                ->preventFormulaInjection(),
            ExportColumn::make('record_label')
                ->label('Registro')
                ->state(fn (AuditLog $record): string => $record->recordLabel())
                ->preventFormulaInjection(),
            ExportColumn::make('event_label')
                ->label('Ação')
                ->state(fn (AuditLog $record): string => $record->actionLabel())
                ->preventFormulaInjection(),
            ExportColumn::make('user.name')
                ->label('Operador')
                ->state(fn (AuditLog $record): string => $record->user?->name ?? 'Sistema / usuário removido')
                ->preventFormulaInjection(),
            ExportColumn::make('changes')
                ->label('Valores antes e depois')
                ->formatStateUsing(fn (mixed $state): string => is_array($state)
                    ? (json_encode($state, JSON_UNESCAPED_UNICODE) ?: '')
                    : (filled($state) ? (string) $state : ''))
                ->preventFormulaInjection(),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "A exportação da auditoria foi concluída: {$export->successful_rows} registro(s) exportado(s).";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " {$failedRowsCount} registro(s) não puderam ser exportados.";
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "auditoria-{$export->getKey()}";
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private static function schoolAddress(array $options): string
    {
        $school = data_get($options, 'school', []);

        return collect([
            trim(implode(', ', array_filter([
                data_get($school, 'street'),
                data_get($school, 'number'),
                data_get($school, 'complement'),
            ]))),
            data_get($school, 'neighborhood'),
            trim(implode(' - ', array_filter([
                data_get($school, 'city'),
                data_get($school, 'state'),
            ]))),
            data_get($school, 'postal_code'),
        ])->filter()->implode(', ');
    }
}
