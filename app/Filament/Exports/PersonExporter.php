<?php

namespace App\Filament\Exports;

use App\Models\Person;
use App\Services\FinancialCalculationService;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class PersonExporter extends Exporter
{
    protected static ?string $model = Person::class;

    public static function getColumns(): array
    {
        return [
            // Identificação e Dados Pessoais
            ExportColumn::make('name')
                ->label('Nome Completo')
                ->preventFormulaInjection(),

            ExportColumn::make('social_name')
                ->label('Nome Social')
                ->preventFormulaInjection(),

            ExportColumn::make('birth_date')
                ->label('Data de Nascimento')
                ->formatStateUsing(fn ($state): string => $state ? \Illuminate\Support\Carbon::parse($state)->format('d/m/Y') : ''),

            ExportColumn::make('cpf')
                ->label('CPF')
                ->state(function (Person $record): string {
                    $cpf = filled($record->cpf) ? (string) $record->cpf : '';
                    if (blank($cpf)) {
                        $doc = $record->documents->firstWhere('type', 'cpf');
                        $cpf = (string) ($doc?->value ?? ($doc?->document_number ?? ''));
                    }
                    if (! \App\Services\DataMaskingService::canViewCpf()) {
                        return \App\Services\DataMaskingService::maskCpf($cpf);
                    }
                    return $cpf;
                })
                ->preventFormulaInjection(),

            ExportColumn::make('email')
                ->label('E-mail Principal')
                ->state(function (Person $record): string {
                    $email = (string) ($record->email ?? '');
                    if (! \App\Services\DataMaskingService::canViewEmail()) {
                        return \App\Services\DataMaskingService::maskEmail($email);
                    }
                    return $email;
                })
                ->preventFormulaInjection(),

            ExportColumn::make('gender')
                ->label('Gênero')
                ->formatStateUsing(fn ($state): string => match (is_object($state) ? ($state->value ?? '') : (string) $state) {
                    'M', 'male' => 'Masculino',
                    'F', 'female' => 'Feminino',
                    'O', 'other' => 'Outro',
                    default => (string) ($state?->value ?? ($state ?? '')),
                }),

            // Contatos (Relation: contacts)
            ExportColumn::make('contacts_whatsapp')
                ->label('Contatos - WhatsApp / Celular Principal')
                ->state(function (Person $record): string {
                    $preferred = $record->contacts->first(function ($c) {
                        $type = is_object($c->type) ? ($c->type->value ?? '') : (string) $c->type;
                        return in_array(strtolower($type), ['whatsapp', 'cellphone', 'celular', 'phone']) && filled($c->value);
                    });
                    $val = $preferred ? (string) $preferred->value : '';
                    if (! \App\Services\DataMaskingService::canViewPhone()) {
                        return \App\Services\DataMaskingService::maskPhone($val);
                    }
                    return $val;
                })
                ->preventFormulaInjection(),

            ExportColumn::make('contacts_phones')
                ->label('Contatos - Todos os Telefones')
                ->state(function (Person $record): string {
                    $canPhone = \App\Services\DataMaskingService::canViewPhone();
                    return $record->contacts
                        ->filter(function ($c) {
                            $type = is_object($c->type) ? ($c->type->value ?? '') : (string) $c->type;
                            return in_array(strtolower($type), ['whatsapp', 'cellphone', 'celular', 'phone', 'telefone', 'fixo']) && filled($c->value);
                        })
                        ->pluck('value')
                        ->unique()
                        ->map(fn ($val) => $canPhone ? $val : \App\Services\DataMaskingService::maskPhone($val))
                        ->join('; ');
                })
                ->preventFormulaInjection(),

            ExportColumn::make('contacts_summary')
                ->label('Contatos - Lista Completa (Tipo: Valor)')
                ->state(function (Person $record): string {
                    return $record->contacts
                        ->filter(fn ($c) => filled($c->value))
                        ->map(function ($c) {
                            $type = is_object($c->type) ? ($c->type->value ?? '') : (string) $c->type;
                            return ucfirst($type) . ': ' . $c->value;
                        })
                        ->join('; ');
                })
                ->preventFormulaInjection(),

            // Endereços (Relation: addresses)
            ExportColumn::make('address_full')
                ->label('Endereço - Completo')
                ->state(function (Person $record): string {
                    $addr = $record->addresses->first();
                    if (! $addr) {
                        return '';
                    }
                    $logradouro = $addr->street ? "{$addr->street}, {$addr->number}" : '';
                    $compl = $addr->complement ? " ({$addr->complement})" : '';
                    $bairro = $addr->neighborhood ? " - {$addr->neighborhood}" : '';
                    $state = $addr->city?->state?->acronym ?? '';
                    $cidade = $addr->city?->name ? " - {$addr->city->name}" . ($state ? "/{$state}" : '') : '';
                    $cepVal = $addr->zip_code ?: ($addr->postal_code ?? '');
                    $cep = $cepVal ? " (CEP: {$cepVal})" : '';
                    return trim($logradouro . $compl . $bairro . $cidade . $cep);
                })
                ->preventFormulaInjection(),

            ExportColumn::make('address_street')
                ->label('Endereço - Logradouro / Rua')
                ->state(fn (Person $record): string => $record->addresses->first()?->street ?? '')
                ->preventFormulaInjection(),

            ExportColumn::make('address_number')
                ->label('Endereço - Número')
                ->state(fn (Person $record): string => (string) ($record->addresses->first()?->number ?? ''))
                ->preventFormulaInjection(),

            ExportColumn::make('address_complement')
                ->label('Endereço - Complemento')
                ->state(fn (Person $record): string => (string) ($record->addresses->first()?->complement ?? ''))
                ->preventFormulaInjection(),

            ExportColumn::make('address_neighborhood')
                ->label('Endereço - Bairro')
                ->state(fn (Person $record): string => (string) ($record->addresses->first()?->neighborhood ?? ''))
                ->preventFormulaInjection(),

            ExportColumn::make('address_city')
                ->label('Endereço - Cidade')
                ->state(fn (Person $record): string => (string) ($record->addresses->first()?->city?->name ?? ''))
                ->preventFormulaInjection(),

            ExportColumn::make('address_state')
                ->label('Endereço - Estado (UF)')
                ->state(fn (Person $record): string => (string) ($record->addresses->first()?->city?->state?->acronym ?? ''))
                ->preventFormulaInjection(),

            ExportColumn::make('address_postal_code')
                ->label('Endereço - CEP')
                ->state(fn (Person $record): string => (string) ($record->addresses->first()?->zip_code ?? ($record->addresses->first()?->postal_code ?? '')))
                ->preventFormulaInjection(),

            // Documentos (Relation: documents)
            ExportColumn::make('document_rg')
                ->label('Documentos - RG')
                ->state(function (Person $record): string {
                    $doc = $record->documents->firstWhere('type', 'rg');
                    return $doc?->value ?? ($doc?->document_number ?? '');
                })
                ->preventFormulaInjection(),

            ExportColumn::make('documents_summary')
                ->label('Documentos - Todos Cadastrados')
                ->state(function (Person $record): string {
                    return $record->documents
                        ->map(function ($d) {
                            $type = is_object($d->type) ? ($d->type->value ?? '') : (string) $d->type;
                            $val = $d->value ?: ($d->document_number ?? '');
                            return strtoupper($type) . ': ' . $val;
                        })
                        ->join('; ');
                })
                ->preventFormulaInjection(),

            // Vínculos & Contratos
            ExportColumn::make('contracts_count')
                ->label('Total de Contratos')
                ->state(fn (Person $record): int => $record->contracts->count()),

            ExportColumn::make('active_contracts')
                ->label('Contratos Ativos (Nº)')
                ->state(function (Person $record): string {
                    return $record->contracts->where('status', 'active')->pluck('contract_number')->join(', ');
                })
                ->preventFormulaInjection(),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'contacts',
            'addresses.city.state',
            'documents',
            'contracts',
        ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "A exportação de pessoas foi concluída: {$export->successful_rows} registro(s) exportado(s).";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " {$failedRowsCount} registro(s) não puderam ser exportados.";
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "pessoas-{$export->getKey()}";
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }
}
