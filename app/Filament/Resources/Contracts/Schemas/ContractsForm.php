<?php

namespace App\Filament\Resources\Contracts\Schemas;

use App\Models\Collaborator;
use App\Models\CommercialLead;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Person;
use App\Models\Responsible;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ContractsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação e Vigência')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('contract_number')
                                    ->label('Número do Contrato')
                                    ->placeholder('Gerado automaticamente')
                                    ->disabled() // Deixamos desabilitado pois será injetado no salvamento
                                    ->dehydrated(false), // Impede envio de campo nulo do form desabilitado

                                Select::make('status')
                                    ->label('Situação Operacional')
                                    ->options(function ($record) {
                                        if ($record === null) {
                                            return [
                                                'draft' => 'Rascunho',
                                                'active' => 'Ativo',
                                            ];
                                        }

                                        $statusAtual = $record->status;
                                        if ($statusAtual === 'draft') {
                                            return [
                                                'draft' => 'Rascunho',
                                                'active' => 'Ativo',
                                            ];
                                        }

                                        return match ($statusAtual) {
                                            'active' => ['active' => 'Ativo'],
                                            'suspended' => ['suspended' => 'Suspenso'],
                                            'canceled' => ['canceled' => 'Cancelado'],
                                            'finished' => ['finished' => 'Finalizado'],
                                            default => [$statusAtual => ucfirst($statusAtual)],
                                        };
                                    })
                                    ->default('draft')
                                    ->disabled(fn ($record) => $record !== null && $record->status !== 'draft')
                                    ->dehydrated() // Garante que o valor seja enviado no submit
                                    ->required(),

                                Select::make('collaborator_id')
                                    ->label('Vendedor / Captador')
                                    ->options(function ($record) {
                                        $currentId = $record?->collaborator_id;

                                        return Collaborator::query()
                                            ->with('person')
                                            ->where(function ($query) use ($currentId) {
                                                $query->where('active', true);
                                                if ($currentId) {
                                                    $query->orWhere('id', $currentId);
                                                }
                                            })
                                            ->whereHas('person')
                                            ->get()
                                            ->mapWithKeys(fn (Collaborator $c): array => [$c->id => $c->person->name])
                                            ->all();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->disabled(function ($record): bool {
                                        if ($record === null || $record->status === 'draft') {
                                            return false;
                                        }

                                        if (blank($record->collaborator_id)) {
                                            return false;
                                        }

                                        return ! (auth()->user()?->can('ChangeCollaborator:Contract') ?? false);
                                    })
                                    ->dehydrated(function ($record): bool {
                                        if ($record === null || $record->status === 'draft') {
                                            return true;
                                        }

                                        if (blank($record->collaborator_id)) {
                                            return true;
                                        }

                                        return auth()->user()?->can('ChangeCollaborator:Contract') ?? false;
                                    })
                                    ->helperText(function ($record): ?string {
                                        if ($record !== null && $record->status !== 'draft' && filled($record->collaborator_id)) {
                                            $canChange = auth()->user()?->can('ChangeCollaborator:Contract') ?? false;

                                            return $canChange
                                                ? 'Captador/vendedor consolidado neste contrato. Você possui permissão para alterá-lo.'
                                                : 'Captador/vendedor consolidado neste contrato (bloqueado para edição).';
                                        }

                                        return null;
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('student_id')
                                    ->label('Aluno (Pessoa)')
                                    ->options(function () {
                                        $tenantId = Filament::getTenant()?->id;

                                        return Person::query()
                                            ->when($tenantId, function ($q) use ($tenantId) {
                                                $q->where(function ($sub) use ($tenantId) {
                                                    $sub->whereHas('schools', fn ($sq) => $sq->whereKey($tenantId))
                                                        ->orWhereHas('contracts', fn ($cq) => $cq->where('school_id', $tenantId));
                                                });
                                            })
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function (Person $p) {
                                                $birth = $p->birth_date ? ' • Nasc: '.$p->birth_date->format('d/m/Y') : '';

                                                return [$p->id => $p->name.$birth];
                                            });
                                    })
                                    ->disabled(fn ($record) => $record !== null && $record->status !== 'draft')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if (! $state) {
                                            return;
                                        }
                                        if (! $get('responsible_id')) {
                                            $resp = Responsible::where('person_id', $state)->where('financial_responsible', true)->first();
                                            if ($resp) {
                                                $set('responsible_id', $resp->id);
                                            }
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->hintAction(
                                        Action::make('novaPessoa')
                                            ->label('+ Cadastrar Nova Pessoa')
                                            ->icon('heroicon-o-user-plus')
                                            ->color('primary')
                                            ->url(fn (): string => route('filament.admin.resources.people.create', [
                                                'tenant' => Filament::getTenant()?->id,
                                                'redirect_to_contract' => 1,
                                            ]))
                                    )
                                    ->suffixAction(
                                        Action::make('goToPerson')
                                            ->icon(fn (Get $get) => filled($get('student_id')) ? 'heroicon-o-pencil-square' : 'heroicon-o-user-plus')
                                            ->tooltip(fn (Get $get) => filled($get('student_id')) ? 'Ir para cadastro completo da pessoa' : 'Cadastrar nova pessoa')
                                            ->url(function (Get $get): string {
                                                $studentId = $get('student_id');
                                                $tenantId = Filament::getTenant()?->id;

                                                if (filled($studentId)) {
                                                    return route('filament.admin.resources.people.edit', [
                                                        'tenant' => $tenantId,
                                                        'record' => $studentId,
                                                    ]);
                                                }

                                                return route('filament.admin.resources.people.create', [
                                                    'tenant' => $tenantId,
                                                    'redirect_to_contract' => 1,
                                                ]);
                                            })
                                            ->openUrlInNewTab()
                                    )
                                    ->helperText(function (Get $get) {
                                        $studentId = $get('student_id');
                                        if (! $studentId) {
                                            return 'A data de nascimento do aluno cadastrada é a senha de acesso inicial dele no Portal e no Totem.';
                                        }
                                        $person = Person::find($studentId);
                                        $birthStr = $person?->birth_date ? $person->birth_date->format('d/m/Y') : 'Não cadastrada';

                                        return "Data de nascimento do aluno: {$birthStr} (Senha inicial de acesso ao Portal do Aluno e Totem).";
                                    }),

                                Select::make('commercial_lead_id')
                                    ->label('Interessado de origem')
                                    ->options(function (Get $get, $record): array {
                                        $studentId = $get('student_id') ?? $record?->student_id;
                                        $currentLeadId = $record?->commercial_lead_id;

                                        if (! $studentId) {
                                            return [];
                                        }

                                        return CommercialLead::query()
                                            ->where(function ($query) use ($studentId, $currentLeadId): void {
                                                $query->where(function ($available) use ($studentId): void {
                                                    $available
                                                        ->whereNull('person_id')
                                                        ->orWhere('person_id', $studentId);
                                                })->whereDoesntHave('contract');

                                                if ($currentLeadId) {
                                                    $query->orWhere('id', $currentLeadId);
                                                }
                                            })
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->disabled(function ($record): bool {
                                        if ($record === null || $record->status === 'draft') {
                                            return false;
                                        }

                                        if (blank($record->commercial_lead_id)) {
                                            return false;
                                        }

                                        return ! (auth()->user()?->can('ChangeCommercialLead:Contract') ?? false);
                                    })
                                    ->dehydrated(function ($record): bool {
                                        if ($record === null || $record->status === 'draft') {
                                            return true;
                                        }

                                        if (blank($record->commercial_lead_id)) {
                                            return true;
                                        }

                                        return auth()->user()?->can('ChangeCommercialLead:Contract') ?? false;
                                    })
                                    ->visible(fn (): bool => auth()->user()?->can('ViewAny:CommercialLead') ?? false)
                                    ->helperText(function ($record): string {
                                        if ($record !== null && $record->status !== 'draft' && filled($record->commercial_lead_id)) {
                                            $canChange = auth()->user()?->can('ChangeCommercialLead:Contract') ?? false;

                                            return $canChange
                                                ? 'Interessado de origem consolidado neste contrato. Você possui permissão para alterá-lo.'
                                                : 'Interessado de origem vinculado e consolidado neste contrato (bloqueado para edição).';
                                        }

                                        return 'Ao salvar ou ativar o contrato com o interessado vinculado, ele será marcado como matriculado e as visitas serão associadas.';
                                    }),

                                Select::make('responsible_id')
                                    ->label('Responsável Financeiro')
                                    ->disabled(fn ($record) => $record !== null && $record->status !== 'draft')
                                    ->options(function () {
                                        $tenantId = Filament::getTenant()?->id;

                                        return Responsible::query()
                                            ->join('people', 'responsibles.person_id', '=', 'people.id')
                                            ->when($tenantId, function ($q) use ($tenantId) {
                                                $q->where(function ($sub) use ($tenantId) {
                                                    $sub->whereExists(function ($sq) use ($tenantId) {
                                                        $sq->selectRaw('1')
                                                            ->from('school_person')
                                                            ->whereColumn('school_person.person_id', 'people.id')
                                                            ->where('school_person.school_id', $tenantId);
                                                    })->orWhereExists(function ($cq) use ($tenantId) {
                                                        $cq->selectRaw('1')
                                                            ->from('contracts')
                                                            ->whereColumn('contracts.responsible_id', 'responsibles.id')
                                                            ->where('contracts.school_id', $tenantId);
                                                    });
                                                });
                                            })
                                            ->orderBy('people.name')
                                            ->pluck('people.name', 'responsibles.id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->suffixAction(
                                        Action::make('viewResponsibleModal')
                                            ->label('Ver dados')
                                            ->icon('heroicon-m-chevron-right')
                                            ->tooltip('Visualizar dados completos do responsável financeiro')
                                            ->color('primary')
                                            ->visible(fn (Get $get) => filled($get('responsible_id')))
                                            ->modalHeading(fn (Get $get) => 'Dados do Responsável: '.(Responsible::find($get('responsible_id'))?->person?->name ?? ''))
                                            ->modalDescription('Dados cadastrais, parentesco, contatos e endereço do responsável.')
                                            ->modalSubmitActionLabel('Editar Cadastro Completo')
                                            ->action(function (Get $get, $livewire): void {
                                                $resp = Responsible::find($get('responsible_id'));
                                                if ($resp?->person_id) {
                                                    $livewire->redirect(route('filament.admin.resources.people.edit', [
                                                        'tenant' => Filament::getTenant()?->id,
                                                        'record' => $resp->person_id,
                                                    ]));
                                                }
                                            })
                                            ->modalCancelActionLabel('Fechar')
                                            ->infolist(function (Get $get) {
                                                $resp = Responsible::with(['person.addresses.city.state', 'person.contacts'])->find($get('responsible_id'));
                                                $person = $resp?->person;

                                                return [
                                                    Section::make('Identificação')
                                                        ->schema([
                                                            TextEntry::make('person_name')
                                                                ->label('Nome Completo')
                                                                ->state($person?->name ?? '—')
                                                                ->weight('bold'),
                                                            TextEntry::make('person_cpf')
                                                                ->label('CPF')
                                                                ->state($person?->cpf ?? '—'),
                                                            TextEntry::make('person_email')
                                                                ->label('E-mail')
                                                                ->state($person?->email ?? '—'),
                                                            TextEntry::make('relationship')
                                                                ->label('Parentesco com o Aluno')
                                                                ->state($resp?->relationship?->getLabel() ?? '—')
                                                                ->badge(),
                                                        ])->columns(2),
                                                    Section::make('Contatos')
                                                        ->schema([
                                                            TextEntry::make('contacts_list')
                                                                ->label('Telefones e Contatos')
                                                                ->state(function () use ($person) {
                                                                    if (! $person || $person->contacts->isEmpty()) {
                                                                        return 'Nenhum contato cadastrado.';
                                                                    }

                                                                    return $person->contacts->map(fn ($c) => "{$c->type->getLabel()}: {$c->value}".($c->is_primary ? ' (Principal)' : ''))->implode("\n");
                                                                })
                                                                ->lineClamp(10),
                                                        ]),
                                                    Section::make('Endereço')
                                                        ->schema([
                                                            TextEntry::make('address_info')
                                                                ->label('Endereço')
                                                                ->state(function () use ($person) {
                                                                    $addr = $person?->addresses->firstWhere('is_primary', true) ?? $person?->addresses->first();
                                                                    if (! $addr) {
                                                                        return 'Nenhum endereço cadastrado.';
                                                                    }
                                                                    $city = $addr->city ? "{$addr->city->name} - {$addr->city->state?->acronym}" : '';

                                                                    return "{$addr->street}, Nº {$addr->number}".($addr->complement ? " ({$addr->complement})" : '')." - Bairro {$addr->neighborhood}, {$city}".($addr->zip_code ? " (CEP: {$addr->zip_code})" : '');
                                                                }),
                                                        ]),
                                                ];
                                            })
                                    )
                                    ->required(fn (Get $get): bool => ! (bool) $get('is_free')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Data de Início')
                                    ->default(now())
                                    ->required(),

                                DatePicker::make('end_date')
                                    ->label('Data de Término (Estimada)'),

                                Toggle::make('is_flex')
                                    ->label('Aluno Flex (Acesso Livre aos Cursos)')
                                    ->helperText('Permite ao aluno cursar qualquer disciplina de suas formações nos horários agendados, sem bloqueio de dia da semana por formação.')
                                    ->default(false),

                                Toggle::make('is_ead')
                                    ->label('Aluno EAD (Cursos Online / Em Casa)')
                                    ->helperText('Habilita o provisionamento e o acompanhamento de notas/progresso na plataforma EAD da Ouro Moderno.')
                                    ->default(false),

                                Toggle::make('is_free')
                                    ->label('Curso Gratuito / Aluno Bolsista')
                                    ->helperText('Define o contrato como isento de mensalidades. O responsável financeiro torna-se opcional e não gera parcelas.')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function (bool $state, Set $set): void {
                                        if ($state) {
                                            $set('total_value', 0);
                                            $set('qty_installments', 0);
                                            $set('installments', []);
                                        }
                                    }),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Regras do Parcelamento Financeiro')
                    ->description('Escolha se deseja dividir o valor total ou multiplicar o valor da parcela pela quantidade de vezes.')
                    ->hidden(fn (Get $get): bool => (bool) $get('is_free'))
                    ->disabled(fn ($record) => $record !== null && $record->status !== 'draft')
                    ->schema([
                        Grid::make(1)->schema([
                            Radio::make('calculation_mode')
                                ->label('Modo de Cálculo')
                                ->options([
                                    'divide' => 'Dividir: Informar o Valor Total e dividir pela quantidade de parcelas',
                                    'multiply' => 'Multiplicar: Informar o Valor de Cada Parcela e multiplicar pela quantidade',
                                ])
                                ->default('divide')
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularSimulacao($get, $set)),
                        ]),

                        Grid::make(4)
                            ->schema([
                                Select::make('simulation_financial_type_id')
                                    ->label('Tipo Padrão das Parcelas')
                                    ->options(fn () => FinancialType::where('is_active', true)->pluck('name', 'id'))
                                    ->default(fn () => FinancialType::where('slug', 'mensalidade')->value('id') ?? FinancialType::where('slug', 'tuition')->value('id') ?? FinancialType::value('id'))
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularSimulacao($get, $set))
                                    ->required(fn ($record, Get $get) => $record === null && ! $get('is_free')),

                                TextInput::make('total_value')
                                    ->label('Valor Total do Contrato')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->disabled(fn (Get $get) => $get('calculation_mode') === 'multiply')
                                    ->dehydrated(fn () => true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularSimulacao($get, $set)),

                                TextInput::make('installment_value')
                                    ->label('Valor de Cada Parcela')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->dehydrated(false)
                                    ->visible(fn (Get $get) => $get('calculation_mode') === 'multiply')
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularSimulacao($get, $set)),

                                TextInput::make('qty_installments')
                                    ->label('Quantidade de Parcelas')
                                    ->type('number')
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularSimulacao($get, $set)),

                                DatePicker::make('first_due_date')
                                    ->label('Primeiro Vencimento')
                                    ->default(now())
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularSimulacao($get, $set)),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Cronograma de Mensalidades e Taxas Avulsas')
                    ->description('Veja a simulação gerada abaixo. Você pode ajustar vencimentos, reordenar e adicionar cobranças extras (ex: Matrícula).')
                    ->hidden(fn (Get $get): bool => (bool) $get('is_free'))
                    ->schema([
                        Repeater::make('installments')
                            ->relationship('installments', fn (Builder $query) => $query->orderBy('installment_number', 'asc')->orderBy('due_date', 'asc'))
                            ->label('Mensalidades e Taxas')
                            ->orderColumn('installment_number')
                            ->reorderableWithButtons()
                            ->reorderableWithDragAndDrop(true)
                            ->itemLabel(function (array $state): ?string {
                                $num = $state['installment_number'] ?? null;
                                $total = $state['total_installments'] ?? null;
                                $type = ! empty($state['financial_type_id']) ? FinancialType::find($state['financial_type_id'])?->name : null;
                                $statusLabel = match ($state['status'] ?? 'pending') {
                                    'paid' => '✓ Paga',
                                    'partial' => '⏳ Parcial',
                                    'canceled' => '✗ Cancelada',
                                    default => '⚪ Pendente',
                                };
                                $prefix = $type ? "{$type} — " : '';
                                $parcela = $num ? "Parcela {$num}" . ($total ? "/{$total}" : '') : 'Cobrança Avulsa';

                                return "{$prefix}{$parcela} [{$statusLabel}]";
                            })
                            ->headerActions([
                                Action::make('renumber')
                                    ->label('Renumerar Parcelas (1 a N)')
                                    ->icon('heroicon-m-arrows-up-down')
                                    ->color('gray')
                                    ->tooltip('Numera sequencialmente as cobranças de 1 a N conforme a ordem visual na tela')
                                    ->action(function (Get $get, Set $set): void {
                                        $items = $get('installments') ?? [];
                                        if (empty($items) || ! is_array($items)) {
                                            return;
                                        }
                                        $total = count($items);
                                        $i = 1;
                                        foreach ($items as $key => $item) {
                                            $items[$key]['installment_number'] = $i;
                                            $items[$key]['total_installments'] = $total;
                                            $i++;
                                        }
                                        $set('installments', $items);
                                        Notification::make()
                                            ->success()
                                            ->title('Parcelas Renumeradas')
                                            ->body("Todas as {$total} cobranças foram renumeradas sequencialmente de 1 a {$total}.")
                                            ->send();
                                    }),
                            ])
                            ->extraItemActions([
                                Action::make('receipt')
                                    ->icon('heroicon-m-document-text')
                                    ->tooltip('Imprimir Recibo')
                                    ->color('primary')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemData = $component->getState()[$arguments['item']] ?? [];

                                        return in_array($itemData['status'] ?? null, ['paid', 'partial']);
                                    })
                                    ->url(function (array $arguments, Repeater $component): ?string {
                                        $itemData = $component->getState()[$arguments['item']] ?? [];
                                        $id = $itemData['id'] ?? null;

                                        return $id ? route('installments.receipt', $id) : null;
                                    }, shouldOpenInNewTab: true),

                                Action::make('financial')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Abrir no Financeiro (Baixar / Estornar / Auditoria)')
                                    ->color('gray')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemData = $component->getState()[$arguments['item']] ?? [];

                                        return ! empty($itemData['id']);
                                    })
                                    ->url(function (array $arguments, Repeater $component): ?string {
                                        $itemData = $component->getState()[$arguments['item']] ?? [];
                                        $id = $itemData['id'] ?? null;
                                        if (! $id) {
                                            return null;
                                        }
                                        $tenant = filament()->getTenant();

                                        return route('filament.admin.resources.installments.view', [
                                            'tenant' => $tenant,
                                            'record' => $id,
                                        ]);
                                    }, shouldOpenInNewTab: true),
                            ])
                            ->schema([
                                Select::make('financial_type_id')
                                    ->label('Tipo de Cobrança')
                                    ->relationship('financialType', 'name')
                                    ->options(fn () => FinancialType::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('installment_number')
                                    ->label('Nº Parcela')
                                    ->numeric()
                                    ->default(function (Get $get) {
                                        $installments = $get('../../installments') ?? [];

                                        // Recolhe todos os números de parcelas já existentes e limpa vazios
                                        $numbers = collect($installments)
                                            ->pluck('installment_number')
                                            ->filter()
                                            ->map(fn ($val) => (int) $val);

                                        // Se não houver nenhum, começa em 1. Senão, pega o maior e soma 1.
                                        return $numbers->isEmpty() ? 1 : $numbers->max() + 1;
                                    })
                                    ->required(),

                                TextInput::make('total_installments')
                                    ->label('Total de Parcelas')
                                    ->numeric()
                                    ->default(function (Get $get) {
                                        $installments = $get('../../installments') ?? [];

                                        $numbers = collect($installments)
                                            ->pluck('total_installments')
                                            ->filter()
                                            ->map(fn ($val) => (int) $val);

                                        return $numbers->isEmpty() ? 1 : $numbers->max() + 1;
                                    })->readOnly()
                                    ->required(),

                                TextInput::make('amount')
                                    ->label('Valor')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->disabled(fn (Get $get): bool => $get('status') === 'paid')
                                    ->dehydrated()
                                    ->required(),

                                DatePicker::make('due_date')
                                    ->label('Vencimento')
                                    ->disabled(fn (Get $get): bool => $get('status') === 'paid')
                                    ->dehydrated()
                                    ->required(),

                                Select::make('status')
                                    ->label('Situação')
                                    ->options([
                                        'pending' => 'Pendente',
                                        'paid' => 'Paga',
                                        'partial' => 'Parcial',
                                        'canceled' => 'Cancelada',
                                    ])
                                    ->default('pending')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(6)
                            ->addActionLabel('Adicionar Cobrança Avulsa')
                            ->deleteAction(function (Action $action) {
                                $action
                                    ->requiresConfirmation()
                                    ->modalHeading('Excluir parcela?')
                                    ->modalDescription('Tem certeza de que deseja excluir esta parcela do contrato? Essa ação não pode ser desfeita.')
                                    ->modalSubmitActionLabel('Sim, excluir parcela');

                                $action->before(function (array $arguments, Repeater $component) use ($action): void {
                                    $itemKey = $arguments['item'] ?? null;
                                    $currentLine = $component->getState()[$itemKey] ?? null;
                                    $installmentId = is_array($currentLine) ? ($currentLine['id'] ?? null) : null;

                                    if (! is_numeric($installmentId)) {
                                        return;
                                    }

                                    $user = filament()->auth()->user();
                                    $installment = Installment::find($installmentId);

                                    if ($user && $installment && $user->can('delete', $installment)) {
                                        return;
                                    }

                                    Notification::make()
                                        ->danger()
                                        ->title('Exclusão não permitida')
                                        ->body('Parcelas já salvas só podem ser excluídas se você tiver permissão e elas não estiverem pagas nem vinculadas a transações.')
                                        ->send();

                                    $action->halt();
                                });
                            }),
                    ])->columnSpanFull(),

                Section::make('Termos e Notas Adicionais')
                    ->schema([
                        Textarea::make('observations')
                            ->label('Observações Internas')
                            ->rows(3),
                    ])->columnSpanFull(),
            ]);
    }

    private static function calcularSimulacao(Get $get, Set $set): void
    {
        // Bloqueia a simulação apenas se for um registro já existente no banco que NÃO esteja em rascunho
        $recordId = $get('id');
        $status = $get('status');

        if ($recordId && $status !== 'draft') {
            Notification::make()
                ->title('Ação não permitida')
                ->body('O financeiro só pode ser recalculado enquanto o contrato estiver em modo Rascunho.')
                ->danger()
                ->send();

            return;
        }

        if ((bool) $get('is_free')) {
            $set('total_value', 0);
            $set('installments', []);

            return;
        }

        $mode = $get('calculation_mode') ?? 'divide';
        $qtdParcelas = intval($get('qty_installments'));
        $primeiroVencimento = $get('first_due_date');

        if ($qtdParcelas <= 0 || ! $primeiroVencimento) {
            return;
        }

        if ($mode === 'multiply') {
            $valorParcela = floatval($get('installment_value'));
            if ($valorParcela <= 0) {
                return;
            }
            $valorTotal = round($valorParcela * $qtdParcelas, 2);
            $set('total_value', $valorTotal);
        } else {
            $valorTotal = floatval($get('total_value'));
            if ($valorTotal <= 0) {
                return;
            }
            $valorParcela = round($valorTotal / $qtdParcelas, 2);
            $set('installment_value', $valorParcela);
        }

        // Pega o tipo financeiro escolhido pelo usuário nas regras de parcelamento
        $financialTypeId = $get('simulation_financial_type_id')
            ?? FinancialType::where('slug', 'mensalidade')->value('id')
            ?? FinancialType::where('slug', 'tuition')->value('id')
            ?? FinancialType::value('id');

        $dataBase = Carbon::parse($primeiroVencimento);
        $simulacao = [];

        for ($i = 1; $i <= $qtdParcelas; $i++) {
            $simulacao[] = [
                'financial_type_id' => $financialTypeId,
                'installment_number' => $i,
                'total_installments' => $qtdParcelas,
                'amount' => $valorParcela,
                'due_date' => $dataBase->copy()->addMonths($i - 1)->format('Y-m-d'),
                'status' => 'pending',
            ];
        }

        $set('installments', $simulacao);
    }
}
