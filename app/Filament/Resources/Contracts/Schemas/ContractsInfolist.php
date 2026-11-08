<?php

namespace App\Filament\Resources\Contracts\Schemas;

use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContractsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Contrato')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('contract_number')
                                    ->label('Número do Contrato')
                                    ->weight('bold')
                                    ->copyable(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'draft' => 'gray',
                                        'suspended' => 'warning',
                                        'canceled' => 'danger',
                                        'finished' => 'info',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'active' => 'Ativo',
                                        'draft' => 'Rascunho',
                                        'suspended' => 'Suspenso',
                                        'canceled' => 'Cancelado',
                                        'finished' => 'Finalizado',
                                    }),

                                TextEntry::make('collaborator.person.name')
                                    ->label('Vendedor / Colaborador')
                                    ->placeholder('Nenhum vinculado'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('student.name')
                                    ->label('Aluno (Estudante)')
                                    ->weight('bold')
                                    ->suffixAction(
                                        Action::make('viewStudentPerson')
                                            ->icon('heroicon-m-identification')
                                            ->color('primary')
                                            ->tooltip('Ver dados cadastrais da Pessoa (Aluno)')
                                            ->modalHeading(fn (Contract $record): string => 'Ficha Cadastral: ' . ($record->student?->name ?? 'Aluno'))
                                            ->modalDescription('Dados cadastrais e informações de contato registradas no módulo de Pessoas.')
                                            ->modalSubmitActionLabel('Abrir Cadastro Completo em Pessoas')
                                            ->action(function (Contract $record, $livewire): void {
                                                if ($record->student_id) {
                                                    $url = route('filament.admin.resources.people.edit', ['record' => $record->student_id]);
                                                    $livewire->js("window.open('{$url}', '_blank');");
                                                }
                                            })
                                            ->schema([
                                                Section::make('Identificação')->schema([
                                                    Grid::make(3)->schema([
                                                        TextInput::make('student_name_modal')
                                                            ->label('Nome Completo')
                                                            ->default(fn (Contract $record): ?string => $record->student?->name)
                                                            ->readOnly(),
                                                        TextInput::make('student_cpf_modal')
                                                            ->label('CPF')
                                                            ->default(fn (Contract $record): string => $record->student?->cpf ?: ($record->student?->documents?->firstWhere('type', 'cpf')?->document_number ?? 'Não informado'))
                                                            ->readOnly(),
                                                        TextInput::make('student_birth_modal')
                                                            ->label('Data de Nascimento')
                                                            ->default(fn (Contract $record): string => $record->student?->birth_date?->format('d/m/Y') ?? 'Não informada')
                                                            ->readOnly(),
                                                    ]),
                                                    Grid::make(2)->schema([
                                                        TextInput::make('student_email_modal')
                                                            ->label('E-mail')
                                                            ->default(fn (Contract $record): ?string => $record->student?->email ?? 'Não informado')
                                                            ->readOnly(),
                                                        TextInput::make('student_phones_modal')
                                                            ->label('Telefones / Contatos')
                                                            ->default(function (Contract $record): string {
                                                                $record->loadMissing('student.contacts');
                                                                return $record->student?->contacts->pluck('value')->join(', ') ?: 'Nenhum telefone cadastrado';
                                                            })
                                                            ->readOnly(),
                                                    ]),
                                                ]),
                                                Section::make('Endereço Residencial')->schema([
                                                    TextInput::make('student_address_modal')
                                                        ->label('Endereço')
                                                        ->default(function (Contract $record): string {
                                                            $record->loadMissing('student.addresses.city.state');
                                                            $addr = $record->student?->addresses?->first();
                                                            if (! $addr) {
                                                                return 'Nenhum endereço cadastrado';
                                                            }
                                                            $logradouro = $addr->street ? "{$addr->street}, {$addr->number}" : '';
                                                            $bairro = $addr->neighborhood ? " - {$addr->neighborhood}" : '';
                                                            $cidade = $addr->city?->name ? " - {$addr->city->name}/{$addr->city->state?->abbreviation}" : '';
                                                            $cep = $addr->postal_code ? " (CEP: {$addr->postal_code})" : '';
                                                            return $logradouro . $bairro . $cidade . $cep;
                                                        })
                                                        ->readOnly(),
                                                ]),
                                            ])
                                    ),

                                TextEntry::make('student.email')
                                    ->label('E-mail do Aluno')
                                    ->copyable()
                                    ->icon('heroicon-m-envelope')
                                    ->placeholder('Não informado'),

                                TextEntry::make('student.birth_date')
                                    ->label('Data de Nascimento (Senha)')
                                    ->date('d/m/Y')
                                    ->placeholder('Não informada')
                                    ->badge()
                                    ->color('info')
                                    ->helperText('Senha de acesso do aluno no Portal e Totem.'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('student.cpf')
                                    ->label('CPF do Aluno')
                                    ->placeholder('Não informado')
                                    ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskCpf($state))
                                    ->suffixAction(
                                        \App\Services\DataMaskingService::makeRevealModalAction('revealStudentCpfInfolist', 'CPF do Aluno', fn (Contract $record): string => (string) ($record->student?->cpf ?? ''))
                                    ),

                                TextEntry::make('student.ouro_moderno_usuario')
                                    ->label('Usuário no Totem / LMS')
                                    ->copyable()
                                    ->placeholder('Não informado'),

                                TextEntry::make('responsible.person.name')
                                    ->label('Responsável Financeiro')
                                    ->placeholder('O próprio aluno')
                                    ->suffixAction(
                                        Action::make('viewResponsiblePerson')
                                            ->icon('heroicon-m-identification')
                                            ->color('primary')
                                            ->tooltip('Ver dados cadastrais da Pessoa (Responsável)')
                                            ->visible(fn (Contract $record): bool => (bool) $record->responsible?->person_id)
                                            ->modalHeading(fn (Contract $record): string => 'Ficha Cadastral: ' . ($record->responsible?->person?->name ?? 'Responsável'))
                                            ->modalDescription('Dados cadastrais e informações de contato registradas no módulo de Pessoas.')
                                            ->modalSubmitActionLabel('Abrir Cadastro Completo em Pessoas')
                                            ->action(function (Contract $record, $livewire): void {
                                                $personId = $record->responsible?->person_id;
                                                if ($personId) {
                                                    $url = route('filament.admin.resources.people.edit', ['record' => $personId]);
                                                    $livewire->js("window.open('{$url}', '_blank');");
                                                }
                                            })
                                            ->schema([
                                                Section::make('Identificação')->schema([
                                                    Grid::make(3)->schema([
                                                        TextInput::make('resp_name_modal')
                                                            ->label('Nome Completo')
                                                            ->default(fn (Contract $record): ?string => $record->responsible?->person?->name)
                                                            ->readOnly(),
                                                        TextInput::make('resp_cpf_modal')
                                                            ->label('CPF')
                                                            ->default(fn (Contract $record): string => $record->responsible?->person?->cpf ?: ($record->responsible?->person?->documents?->firstWhere('type', 'cpf')?->document_number ?? 'Não informado'))
                                                            ->readOnly(),
                                                        TextInput::make('resp_birth_modal')
                                                            ->label('Data de Nascimento')
                                                            ->default(fn (Contract $record): string => $record->responsible?->person?->birth_date?->format('d/m/Y') ?? 'Não informada')
                                                            ->readOnly(),
                                                    ]),
                                                    Grid::make(2)->schema([
                                                        TextInput::make('resp_email_modal')
                                                            ->label('E-mail')
                                                            ->default(fn (Contract $record): ?string => $record->responsible?->person?->email ?? 'Não informado')
                                                            ->readOnly(),
                                                        TextInput::make('resp_phones_modal')
                                                            ->label('Telefones / Contatos')
                                                            ->default(function (Contract $record): string {
                                                                $record->loadMissing('responsible.person.contacts');
                                                                return $record->responsible?->person?->contacts->pluck('value')->join(', ') ?: 'Nenhum telefone cadastrado';
                                                            })
                                                            ->readOnly(),
                                                    ]),
                                                ]),
                                                Section::make('Endereço Residencial')->schema([
                                                    TextInput::make('resp_address_modal')
                                                        ->label('Endereço')
                                                        ->default(function (Contract $record): string {
                                                            $record->loadMissing('responsible.person.addresses.city.state');
                                                            $addr = $record->responsible?->person?->addresses?->first();
                                                            if (! $addr) {
                                                                return 'Nenhum endereço cadastrado';
                                                            }
                                                            $logradouro = $addr->street ? "{$addr->street}, {$addr->number}" : '';
                                                            $bairro = $addr->neighborhood ? " - {$addr->neighborhood}" : '';
                                                            $cidade = $addr->city?->name ? " - {$addr->city->name}/{$addr->city->state?->abbreviation}" : '';
                                                            $cep = $addr->postal_code ? " (CEP: {$addr->postal_code})" : '';
                                                            return $logradouro . $bairro . $cidade . $cep;
                                                        })
                                                        ->readOnly(),
                                                ]),
                                            ])
                                    ),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('start_date')
                                    ->label('Data de Início')
                                    ->date('d/m/Y'),

                                TextEntry::make('end_date')
                                    ->label('Data de Término (Estimada)')
                                    ->date('d/m/Y')
                                    ->placeholder('-'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Acompanhamento Pedagógico EAD (Ouro Moderno Online)')
                    ->icon('heroicon-o-academic-cap')
                    ->visible(fn ($record) => (bool) $record?->is_ead)
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('ead_student_id')
                                    ->label('ID do Aluno no EAD')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable()
                                    ->placeholder('Não vinculado'),

                                TextEntry::make('ead_last_progress_percentage')
                                    ->label('Progresso Geral EAD')
                                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 1).'%')
                                    ->badge()
                                    ->color(fn ($state) => (float) $state >= 100 ? 'success' : ((float) $state > 0 ? 'info' : 'gray')),

                                TextEntry::make('ead_synced_at')
                                    ->label('Última Sincronização')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Nunca'),

                                TextEntry::make('auto_login_preview')
                                    ->label('Acesso ao Portal EAD')
                                    ->state(fn ($record): string => 'Abrir Portal do Aluno')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->color('primary')
                                    ->url(fn ($record): ?string => app(\App\Services\OuroModernoEadService::class)->getStudentAutoLoginUrl($record), shouldOpenInNewTab: true),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Valores do Contrato')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('total_value')
                                    ->label('Valor Global do Curso')
                                    ->money('BRL'),

                                TextEntry::make('discount_value')
                                    ->label('Desconto Concedido')
                                    ->money('BRL'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Cronograma Financeiro Atual')
                    ->description('Lista de parcelas e taxas avulsas geradas para este contrato.')
                    ->schema([
                        RepeatableEntry::make('installments')
                            ->label(false)
                            ->schema([
                                TextEntry::make('financialType.name')
                                    ->label('Tipo')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('installment_number')
                                    ->label('Nº Parcela')
                                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} / {$record->total_installments}" : 'Avulsa'),

                                TextEntry::make('amount')
                                    ->label('Valor Base')
                                    ->money('BRL'),

                                TextEntry::make('due_date')
                                    ->label('Vencimento')
                                    ->date('d/m/Y'),

                                TextEntry::make('status')
                                    ->label('Situação')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'partial' => 'info',
                                        'canceled' => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'paid' => 'Paga',
                                        'pending' => 'Pendente',
                                        'partial' => 'Parcial',
                                        'canceled' => 'Cancelada',
                                    }),

                                TextEntry::make('receipt_link')
                                    ->label('Recibo ao Aluno')
                                    ->state(fn ($record): string => in_array($record->status, ['paid', 'partial']) ? 'Imprimir Recibo' : '—')
                                    ->color(fn ($record): string => in_array($record->status, ['paid', 'partial']) ? 'primary' : 'gray')
                                    ->icon(fn ($record): ?string => in_array($record->status, ['paid', 'partial']) ? 'heroicon-m-document-text' : null)
                                    ->url(fn ($record): ?string => in_array($record->status, ['paid', 'partial']) ? route('installments.receipt', $record) : null, shouldOpenInNewTab: true),
                                Section::make('Auditoria da parcela')
                                    ->schema([
                                        RepeatableEntry::make('auditLogs')
                                            ->label(false)
                                            ->schema([
                                                Grid::make(4)
                                                    ->schema([
                                                        TextEntry::make('event')
                                                            ->label('Ação')
                                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                                'created' => 'Gerada',
                                                                'updated' => 'Alterada',
                                                                'deleted' => 'Removida',
                                                                'restored' => 'Restaurada',
                                                                default => $state,
                                                            }),
                                                        TextEntry::make('user.name')
                                                            ->label('Usuário')
                                                            ->placeholder('Sistema / usuário removido'),
                                                        TextEntry::make('created_at')
                                                            ->label('Data e hora')
                                                            ->dateTime('d/m/Y H:i:s'),
                                                        TextEntry::make('changes')
                                                            ->label('Valores antes e depois')
                                                            ->formatStateUsing(fn (mixed $state): string => is_array($state)
                                                                ? (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—')
                                                                : (filled($state) ? (string) $state : '—'))
                                                            ->copyable()
                                                            ->columnSpanFull(),
                                                    ]),
                                            ])
                                            ->contained(false),
                                    ])
                                    ->collapsible()
                                    ->collapsed()
                                    ->columnSpanFull(),
                            ])
                            ->columns(5),
                    ])->columnSpanFull(),

                Section::make('Minuta e Cláusulas Contratuais')
                    ->description('Texto legal aplicado com base na versão do tipo de contrato vigente na data de emissão.')
                    ->schema([
                        TextEntry::make('contractType.name')
                            ->label('Modelo Utilizado')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('contractType.version')
                            ->label('Versão')
                            ->badge(),

                        TextEntry::make('contractType.content')
                            ->label(false)
                            ->html() // Renderiza o HTML gerado pelo RichEditor do ContractType
                            ->columnSpanFull(),
                    ])->columnSpanFull()->collapsible(),

                Section::make('Notas e Auditoria')
                    ->schema([
                        TextEntry::make('observations')
                            ->label('Observações Internas')
                            ->placeholder('Sem observações cadastradas.')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                Section::make('Dados de auditoria')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('createdBy.name')
                                    ->label('Cadastrado por')
                                    ->placeholder('Usuário não registrado'),
                                TextEntry::make('created_at')
                                    ->label('Cadastrado em')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Última atualização')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                        RepeatableEntry::make('auditLogs')
                            ->label('Histórico de alterações')
                            ->schema([
                                TextEntry::make('event')
                                    ->label('Ação')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'created' => 'Criado',
                                        'updated' => 'Alterado',
                                        'deleted' => 'Removido',
                                        'restored' => 'Restaurado',
                                        default => $state,
                                    }),
                                TextEntry::make('auditable_type')
                                    ->label('Registro')
                                    ->formatStateUsing(fn (string $state): string => match (class_basename($state)) {
                                        'Contract' => 'Contrato',
                                        'ContractItem' => 'Formação contratada',
                                        'ContractCourse' => 'Curso do aluno',
                                        'StudentSchedule' => 'Horário',
                                        'Installment' => 'Parcela',
                                        'CashTransaction' => 'Movimentação de caixa',
                                        default => class_basename($state),
                                    }),
                                TextEntry::make('user.name')
                                    ->label('Usuário')
                                    ->placeholder('Sistema / usuário removido'),
                                TextEntry::make('created_at')
                                    ->label('Data e hora')
                                    ->dateTime('d/m/Y H:i:s'),
                                TextEntry::make('changes')
                                    ->label('Valores antes e depois')
                                    ->formatStateUsing(fn (mixed $state): string => is_array($state)
                                        ? (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—')
                                        : (filled($state) ? (string) $state : '—'))
                                    ->copyable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->contained(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

            ]);
    }
}
