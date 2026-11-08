<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Installment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ReceivedInstallmentExporter extends Exporter
{
    protected static ?string $model = Installment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('contract.contract_number')
                ->label('Nº Contrato')
                ->preventFormulaInjection(),

            ExportColumn::make('paid_at')
                ->label('Data Recebimento')
                ->formatStateUsing(fn ($state): string => $state ? $state->format('d/m/Y') : ''),

            ExportColumn::make('due_date')
                ->label('Vencimento Original')
                ->formatStateUsing(fn ($state): string => $state ? $state->format('d/m/Y') : ''),

            ExportColumn::make('installment_number')
                ->label('Parcela')
                ->state(fn (Installment $record): string => $record->installment_number ? "{$record->installment_number} / {$record->total_installments}" : 'Entrada / Avulsa'),

            ExportColumn::make('financialType.name')
                ->label('Tipo de Cobrança')
                ->preventFormulaInjection(),

            ExportColumn::make('amount_paid')
                ->label('Valor Recebido (R$)')
                ->state(fn (Installment $record): string => number_format((float) ($record->amount_paid ?? $record->amount), 2, ',', '.')),

            ExportColumn::make('amount')
                ->label('Valor Base (R$)')
                ->state(fn (Installment $record): string => number_format((float) $record->amount, 2, ',', '.')),

            ExportColumn::make('discount')
                ->label('Desconto (R$)')
                ->state(fn (Installment $record): string => number_format((float) $record->discount, 2, ',', '.')),

            ExportColumn::make('fine')
                ->label('Acréscimo / Multa (R$)')
                ->state(fn (Installment $record): string => number_format((float) $record->fine, 2, ',', '.')),

            ExportColumn::make('paymentMethod.name')
                ->label('Forma de Pagamento')
                ->formatStateUsing(fn ($state): string => $state ?: 'Não informada')
                ->preventFormulaInjection(),

            ExportColumn::make('contract.student.name')
                ->label('Nome do Aluno')
                ->preventFormulaInjection(),

            ExportColumn::make('student_cpf')
                ->label('CPF do Aluno')
                ->state(fn (Installment $record): string => self::formatCpf($record->contract?->student?->cpf)),

            ExportColumn::make('contract.student.birth_date')
                ->label('Nascimento do Aluno')
                ->formatStateUsing(fn ($state): string => $state ? $state->format('d/m/Y') : ''),

            ExportColumn::make('responsible_name')
                ->label('Nome do Responsável')
                ->state(fn (Installment $record): string => $record->contract?->responsible?->person?->name ?? 'Próprio Aluno')
                ->preventFormulaInjection(),

            ExportColumn::make('responsible_cpf')
                ->label('CPF do Responsável')
                ->state(fn (Installment $record): string => self::formatCpf($record->contract?->responsible?->person?->cpf ?? $record->contract?->student?->cpf)),

            ExportColumn::make('relationship')
                ->label('Grau de Parentesco')
                ->state(fn (Installment $record): string => $record->contract?->responsible?->relationship?->getLabel() ?? ($record->contract?->responsible ? 'Responsável' : 'Próprio Aluno')),

            ExportColumn::make('tomador_nome')
                ->label('Tomador Fiscal (ISS)')
                ->state(fn (Installment $record): string => $record->contract?->responsible?->person?->name ?? $record->contract?->student?->name ?? 'Não identificado')
                ->preventFormulaInjection(),

            ExportColumn::make('tomador_cpf')
                ->label('CPF Tomador Fiscal (ISS)')
                ->state(fn (Installment $record): string => self::formatCpf($record->contract?->responsible?->person?->cpf ?? $record->contract?->student?->cpf)),

            ExportColumn::make('tomador_endereco')
                ->label('Endereço / Bairro')
                ->state(function (Installment $record): string {
                    $person = $record->contract?->responsible?->person ?? $record->contract?->student;
                    $address = $person?->addresses?->firstWhere('is_primary', true) ?? $person?->addresses?->first();

                    if (! $address) {
                        return '';
                    }

                    return trim("{$address->street}, {$address->number} {$address->complement} - {$address->neighborhood}");
                }),

            ExportColumn::make('tomador_cidade_uf')
                ->label('Cidade / UF')
                ->state(function (Installment $record): string {
                    $person = $record->contract?->responsible?->person ?? $record->contract?->student;
                    $address = $person?->addresses?->firstWhere('is_primary', true) ?? $person?->addresses?->first();

                    if (! $address || ! $address->city) {
                        return 'Fortaleza / CE';
                    }

                    return "{$address->city->name} / ".($address->city->state?->code ?? 'CE');
                }),

            ExportColumn::make('tomador_cep')
                ->label('CEP')
                ->state(function (Installment $record): string {
                    $person = $record->contract?->responsible?->person ?? $record->contract?->student;
                    $address = $person?->addresses?->firstWhere('is_primary', true) ?? $person?->addresses?->first();

                    return $address?->zip_code ?? '';
                }),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'contract.student.addresses.city.state',
            'contract.responsible.person.addresses.city.state',
            'financialType',
            'paymentMethod',
        ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "O relatório de contas recebidas foi gerado com sucesso: {$export->successful_rows} registro(s) exportado(s).";
    }

    public function getFileName(Export $export): string
    {
        return "contas-recebidas-iss-{$export->getKey()}";
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }

    public static function formatCpf(?string $cpf): string
    {
        if (blank($cpf)) {
            return '';
        }

        if (! \App\Services\DataMaskingService::canViewCpf()) {
            return \App\Services\DataMaskingService::maskCpf($cpf);
        }

        $clean = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($clean) === 11) {
            return substr($clean, 0, 3).'.'.substr($clean, 3, 3).'.'.substr($clean, 6, 3).'-'.substr($clean, 9, 2);
        }

        if (strlen($clean) === 14) {
            return substr($clean, 0, 2).'.'.substr($clean, 2, 3).'.'.substr($clean, 5, 3).'/'.substr($clean, 8, 4).'-'.substr($clean, 12, 2);
        }

        return $cpf;
    }
}
