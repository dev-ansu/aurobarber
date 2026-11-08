<?php

namespace App\Filament\Exports;

use App\Filament\Resources\Contracts\Tables\ContractsTable;
use App\Models\Contract;
use App\Services\FinancialCalculationService;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ContractExporter extends Exporter
{
    protected static ?string $model = Contract::class;

    public static function getColumns(): array
    {
        return [
            // Identificação do Contrato
            ExportColumn::make('contract_number')
                ->label('Nº Contrato')
                ->preventFormulaInjection(),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'active' => 'Ativo',
                    'suspended' => 'Suspenso',
                    'canceled' => 'Cancelado',
                    'finished' => 'Finalizado',
                    'draft' => 'Rascunho',
                    default => ucfirst($state),
                }),

            ExportColumn::make('start_date')
                ->label('Data de Início')
                ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y') : ''),

            ExportColumn::make('end_date')
                ->label('Previsão de Conclusão')
                ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y') : ''),

            ExportColumn::make('created_at')
                ->label('Cadastrado em')
                ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y H:i') : ''),

            ExportColumn::make('contractType.name')
                ->label('Modelo de Minuta / Documento')
                ->preventFormulaInjection(),

            ExportColumn::make('collaborator.person.name')
                ->label('Vendedor / Colaborador')
                ->state(fn (Contract $record): string => $record->collaborator?->person?->name ?? '')
                ->preventFormulaInjection(),

            // Dados do Aluno (Pessoa)
            ExportColumn::make('student.name')
                ->label('Aluno - Nome')
                ->preventFormulaInjection(),

            ExportColumn::make('student_cpf')
                ->label('Aluno - CPF')
                ->state(function (Contract $record): string {
                    $st = $record->student;
                    if (! $st) {
                        return '';
                    }
                    $cpf = filled($st->cpf) ? (string) $st->cpf : ($st->documents->firstWhere('type', 'cpf')?->value ?? ($st->documents->firstWhere('type', 'cpf')?->document_number ?? ''));
                    if (! \App\Services\DataMaskingService::canViewCpf()) {
                        return \App\Services\DataMaskingService::maskCpf($cpf);
                    }
                    return $cpf;
                })
                ->preventFormulaInjection(),

            ExportColumn::make('student_birth_date')
                ->label('Aluno - Nascimento')
                ->state(fn (Contract $record): string => $record->student?->birth_date ? Carbon::parse($record->student->birth_date)->format('d/m/Y') : ''),

            ExportColumn::make('student_email')
                ->label('Aluno - E-mail')
                ->state(function (Contract $record): string {
                    $email = (string) ($record->student?->email ?? '');
                    if (! \App\Services\DataMaskingService::canViewEmail()) {
                        return \App\Services\DataMaskingService::maskEmail($email);
                    }
                    return $email;
                })
                ->preventFormulaInjection(),

            ExportColumn::make('student_phone')
                ->label('Aluno - Telefone / WhatsApp')
                ->state(function (Contract $record): string {
                    if (! $record->student) {
                        return '';
                    }
                    $contacts = $record->student->contacts;
                    $phone = $contacts->first(function ($c) {
                        $type = is_object($c->type) ? ($c->type->value ?? '') : (string) $c->type;
                        return in_array(strtolower($type), ['whatsapp', 'cellphone', 'celular', 'phone', 'fixo', 'telefone']) && filled($c->value);
                    });
                    $val = $phone ? (string) $phone->value : '';
                    if (! \App\Services\DataMaskingService::canViewPhone()) {
                        return \App\Services\DataMaskingService::maskPhone($val);
                    }
                    return $val;
                })
                ->preventFormulaInjection(),

            ExportColumn::make('student_address')
                ->label('Aluno - Endereço Completo')
                ->state(function (Contract $record): string {
                    $addr = $record->student?->addresses->first();
                    if (! $addr) {
                        return '';
                    }
                    $logradouro = $addr->street ? "{$addr->street}, {$addr->number}" : '';
                    $bairro = $addr->neighborhood ? " - {$addr->neighborhood}" : '';
                    $state = $addr->city?->state?->acronym ?? '';
                    $cidade = $addr->city?->name ? " - {$addr->city->name}" . ($state ? "/{$state}" : '') : '';
                    $cepVal = $addr->zip_code ?: ($addr->postal_code ?? '');
                    $cep = $cepVal ? " (CEP: {$cepVal})" : '';
                    return trim($logradouro . $bairro . $cidade . $cep);
                })
                ->preventFormulaInjection(),

            // Responsável Financeiro
            ExportColumn::make('responsible.person.name')
                ->label('Responsável Financeiro - Nome')
                ->state(fn (Contract $record): string => $record->responsible?->person?->name ?? 'O próprio aluno')
                ->preventFormulaInjection(),

            ExportColumn::make('responsible_cpf')
                ->label('Responsável Financeiro - CPF')
                ->state(function (Contract $record): string {
                    $respPerson = $record->responsible?->person;
                    if (! $respPerson) {
                        return '';
                    }
                    $cpf = filled($respPerson->cpf) ? (string) $respPerson->cpf : ($respPerson->documents->firstWhere('type', 'cpf')?->value ?? ($respPerson->documents->firstWhere('type', 'cpf')?->document_number ?? ''));
                    if (! \App\Services\DataMaskingService::canViewCpf()) {
                        return \App\Services\DataMaskingService::maskCpf($cpf);
                    }
                    return $cpf;
                })
                ->preventFormulaInjection(),

            ExportColumn::make('responsible_phone')
                ->label('Responsável Financeiro - Telefone')
                ->state(function (Contract $record): string {
                    $respPerson = $record->responsible?->person;
                    if (! $respPerson) {
                        return '';
                    }
                    $contacts = $respPerson->contacts;
                    $phone = $contacts->first(function ($c) {
                        $type = is_object($c->type) ? ($c->type->value ?? '') : (string) $c->type;
                        return in_array(strtolower($type), ['whatsapp', 'cellphone', 'celular', 'phone', 'fixo', 'telefone']) && filled($c->value);
                    });
                    $val = $phone ? (string) $phone->value : '';
                    if (! \App\Services\DataMaskingService::canViewPhone()) {
                        return \App\Services\DataMaskingService::maskPhone($val);
                    }
                    return $val;
                })
                ->preventFormulaInjection(),

            ExportColumn::make('responsible_email')
                ->label('Responsável Financeiro - E-mail')
                ->state(function (Contract $record): string {
                    $email = (string) ($record->responsible?->person?->email ?? '');
                    if (! \App\Services\DataMaskingService::canViewEmail()) {
                        return \App\Services\DataMaskingService::maskEmail($email);
                    }
                    return $email;
                })
                ->preventFormulaInjection(),

            // Itens / Cursos / Formações (RelationManager: items)
            ExportColumn::make('formations_list')
                ->label('Formações / Cursos')
                ->state(function (Contract $record): string {
                    return $record->items
                        ->map(fn ($item) => $item->formation?->name)
                        ->filter()
                        ->unique()
                        ->join(', ') ?: 'Cursos Avulsos';
                })
                ->preventFormulaInjection(),

            ExportColumn::make('courses_list')
                ->label('Módulos Inclusos')
                ->state(function (Contract $record): string {
                    return $record->items
                        ->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))
                        ->filter()
                        ->unique()
                        ->join(', ');
                })
                ->preventFormulaInjection(),

            ExportColumn::make('total_workload')
                ->label('Carga Horária Total (h)')
                ->state(function (Contract $record): int {
                    return (int) $record->items
                        ->flatMap(fn ($item) => $item->contractCourses)
                        ->sum(fn ($cc) => (int) ($cc->course?->workload ?? 0));
                }),

            // Horários e Turmas (Relation: schedules)
            ExportColumn::make('schedules_summary')
                ->label('Horários e Turmas Agendadas')
                ->state(function (Contract $record): string {
                    $weekdays = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 0 => 'Dom'];
                    return $record->schedules
                        ->map(function ($s) use ($weekdays) {
                            $dia = $weekdays[$s->weekday] ?? "Dia {$s->weekday}";
                            $hora = substr((string) $s->start_hour, 0, 5) . '-' . substr((string) $s->end_hour, 0, 5);
                            $sala = $s->classroom?->name ? " ({$s->classroom->name})" : '';
                            return "{$dia} {$hora}{$sala}";
                        })
                        ->unique()
                        ->join('; ');
                })
                ->preventFormulaInjection(),

            // Financeiro e Parcelas (RelationManager: installments)
            ExportColumn::make('total_value')
                ->label('Valor Total Global (R$)')
                ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.')),

            ExportColumn::make('discount_value')
                ->label('Desconto (R$)')
                ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.')),

            ExportColumn::make('net_value')
                ->label('Valor Líquido (R$)')
                ->state(fn (Contract $record): string => number_format(max(0, (float) $record->total_value - (float) $record->discount_value), 2, ',', '.')),

            ExportColumn::make('installments_total_count')
                ->label('Qtd. Total de Parcelas')
                ->state(fn (Contract $record): int => $record->installments->count()),

            ExportColumn::make('installments_paid_count')
                ->label('Qtd. Parcelas Pagas')
                ->state(fn (Contract $record): int => $record->installments->where('status', 'paid')->count()),

            ExportColumn::make('installments_pending_count')
                ->label('Qtd. Parcelas Pendentes')
                ->state(fn (Contract $record): int => $record->installments->whereIn('status', ['pending', 'partial'])->count()),

            ExportColumn::make('total_paid_amount')
                ->label('Total Já Pago (R$)')
                ->state(function (Contract $record): string {
                    $paid = $record->installments->where('status', 'paid')->sum('amount');
                    return number_format((float) $paid, 2, ',', '.');
                }),

            ExportColumn::make('total_pending_amount')
                ->label('Saldo Pendente / Em Aberto (R$)')
                ->state(function (Contract $record): string {
                    $pending = $record->installments->whereIn('status', ['pending', 'partial'])->sum('amount');
                    return number_format((float) $pending, 2, ',', '.');
                }),

            // Trancamento e Suspensão
            ExportColumn::make('suspended_at')
                ->label('Trancamento - Data Inicial')
                ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y') : ''),

            ExportColumn::make('suspension_expected_return_date')
                ->label('Trancamento - Previsão de Retorno')
                ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y') : ''),

            ExportColumn::make('suspension_returned_at')
                ->label('Trancamento - Data Efetiva de Retorno')
                ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y') : ''),

            ExportColumn::make('suspension_reason')
                ->label('Trancamento - Motivo')
                ->preventFormulaInjection(),

            // Cancelamento / Rescisão
            ExportColumn::make('cancelled_at')
                ->label('Cancelamento - Data')
                ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y') : ''),

            ExportColumn::make('cancellation_reason')
                ->label('Cancelamento - Motivo da Rescisão')
                ->preventFormulaInjection(),

            ExportColumn::make('observations')
                ->label('Observações Internas')
                ->preventFormulaInjection(),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'contractType',
            'collaborator.person',
            'items.formation',
            'items.contractCourses.course',
            'schedules.classroom',
            'installments',
        ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "A exportação de contratos foi concluída: {$export->successful_rows} contrato(s) exportado(s).";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " {$failedRowsCount} contrato(s) não puderam ser exportados.";
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "contratos-{$export->getKey()}";
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }
}
