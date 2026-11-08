<?php

namespace App\Filament\Resources\CommercialLeads;

use App\Enums\ContactTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Resources\CommercialLeads\Pages\ManageCommercialLeads;
use App\Filament\Resources\Contracts\Pages\CreateContracts;
use App\Models\Collaborator;
use App\Models\CommercialLead;
use App\Models\CommercialLossReason;
use App\Models\Contact;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Services\CityService;
use App\Services\SchoolTransferService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class CommercialLeadResource extends Resource
{
    protected static ?string $model = CommercialLead::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $navigationLabel = 'Interessados';

    protected static ?string $modelLabel = 'interessado';

    protected static ?string $pluralModelLabel = 'interessados';

    protected static ?string $slug = 'commercial-leads';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pessoa interessada')->schema([
                Select::make('person_id')
                    ->label('Pessoa já cadastrada')
                    ->options(function (): array {
                        $tenantId = \Filament\Facades\Filament::getTenant()?->id;

                        return Person::query()
                            ->when($tenantId, function ($q) use ($tenantId) {
                                $q->where(function ($sub) use ($tenantId) {
                                    $sub->whereHas('schools', fn ($sq) => $sq->whereKey($tenantId))
                                        ->orWhereHas('contracts', fn ($cq) => $cq->where('school_id', $tenantId));
                                });
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()?->can('ViewAny:Person') ?? false)
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if (! $state || ! ($person = Person::find($state))) {
                            return;
                        }

                        $set('name', $person->name);
                        $set('birth_date', $person->birth_date?->toDateString());
                        $set('gender', $person->gender?->value ?? $person->gender);
                        $set('email', $person->email);
                    })
                    ->helperText('Opcional. Vincule quando a pessoa já existir no cadastro.'),
                TextInput::make('name')->label('Nome completo')->required()->maxLength(255),
                DatePicker::make('birth_date')->label('Data de nascimento')->maxDate(today()),
                Select::make('gender')->label('Gênero')->options(GenderEnum::class)->native(false),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->maxLength(255)
                    ->rule(new \App\Rules\ValidEmailDomain()),
                TextInput::make('phone')->label('Telefone/WhatsApp')->tel()->maxLength(40),
                Select::make('city_id')
                    ->label('Cidade')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => app(CityService::class)->search($search))
                    ->getOptionLabelUsing(fn ($value): ?string => $value ? app(CityService::class)->label((int) $value) : null)
                    ->helperText('Opcional. Se vazio, o relatório tenta usar o endereço principal da pessoa vinculada.'),
                TextInput::make('neighborhood')->label('Bairro')->maxLength(120),
            ])->columns(2)->columnSpanFull(),
            Section::make('Responsável (para menor de idade ou terceiro pagador)')
                ->description('Opcional. Preencha se o contato inicial foi feito pelos pais ou terceiro responsável.')
                ->collapsible()
                ->collapsed(fn (?CommercialLead $record): bool => empty($record?->responsible_name))
                ->schema([
                    TextInput::make('responsible_name')
                        ->label('Nome do Responsável')
                        ->placeholder('Ex: Maria Silva (Mãe)')
                        ->maxLength(255),
                    Select::make('responsible_relationship')
                        ->label('Parentesco')
                        ->options(RelationshipEnum::class)
                        ->native(false),
                    TextInput::make('responsible_phone')
                        ->label('Telefone/WhatsApp do Responsável')
                        ->tel()
                        ->maxLength(40),
                ])->columns(3)->columnSpanFull(),
            Section::make('Oportunidade e acompanhamento')->schema([
                Select::make('status')
                    ->label('Etapa')
                    ->options(function (?CommercialLead $record): array {
                        $statuses = collect(CommercialLead::statuses())->except('enrolled');

                        if ($record?->contract()->exists()) {
                            $statuses->put('enrolled', CommercialLead::statuses()['enrolled']);
                        }

                        return $statuses->all();
                    })
                    ->default('new')
                    ->required()
                    ->live()
                    ->disabled(fn (?CommercialLead $record): bool => $record?->contract()->exists() ?? false)
                    ->helperText('A etapa Matriculado é atualizada pelo sistema quando o contrato fica ativo.'),
                Select::make('commercial_loss_reason_id')
                    ->label('Motivo da Perda / Desistência')
                    ->options(function () {
                        $tenantId = Filament::getTenant()?->id;

                        return CommercialLossReason::query()
                            ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId))
                            ->where('active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('status') === 'lost')
                    ->visible(fn (Get $get): bool => $get('status') === 'lost'),
                Textarea::make('loss_notes')
                    ->label('Detalhes da Perda / Desistência')
                    ->placeholder('Explique os detalhes ou justificativa da desistência...')
                    ->rows(2)
                    ->visible(fn (Get $get): bool => $get('status') === 'lost'),
                Select::make('source')
                    ->label('Origem / Meio de captação')
                    ->options(CommercialLead::sources())
                    ->native(false),
                Select::make('formation_id')
                    ->label('Formação de interesse')
                    ->options(fn (): array => Formation::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
                Select::make('collaborator_id')
                    ->label('Responsável pelo atendimento')
                    ->options(fn (): array => Collaborator::query()->join('people', 'people.id', '=', 'collaborators.person_id')->where('collaborators.active', true)->orderBy('people.name')->pluck('people.name', 'collaborators.id')->all())
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('next_follow_up_at')
                    ->label('Próximo retorno')
                    ->native(false)
                    ->seconds(false),
                Textarea::make('notes')->label('Observações')->rows(4)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['collaborator.person', 'formation', 'contract', 'lossReason']))
            ->defaultSort('next_follow_up_at')
            ->columns([
                TextColumn::make('name')->label('Interessado')->searchable()->sortable(),
                TextColumn::make('phone')->label('Telefone')->searchable()->placeholder('—'),
                TextColumn::make('formation.name')->label('Interesse')->placeholder('Não definido')->toggleable(),
                TextColumn::make('gender')->label('Gênero')->placeholder('—')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Etapa')
                    ->formatStateUsing(fn (string $state, CommercialLead $record): string => $record->contract?->status === 'active'
                        ? 'Matriculado'
                        : (CommercialLead::statuses()[$state] ?? $state))
                    ->badge()
                    ->color(fn (string $state, CommercialLead $record): string => $record->contract?->status === 'active'
                        ? 'success'
                        : match ($state) {
                            'enrolled' => 'success',
                            'lost' => 'danger',
                            'proposal' => 'warning',
                            'visited' => 'primary',
                            'visit_scheduled' => 'info',
                            default => 'gray',
                        }),
                TextColumn::make('source')
                    ->label('Origem')
                    ->formatStateUsing(fn (?string $state): string => CommercialLead::sources()[$state] ?? $state ?? '—')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'website' => 'info',
                        'social_media' => 'warning',
                        'referral' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('lossReason.name')->label('Motivo da Perda')->placeholder('—')->toggleable(),
                TextColumn::make('collaborator.person.name')->label('Responsável')->placeholder('—')->toggleable(),
                TextColumn::make('next_follow_up_at')
                    ->label('Próximo retorno')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sem retorno marcado')
                    ->color(fn (CommercialLead $record): string => $record->next_follow_up_at?->isPast() && ! in_array($record->status, ['lost', 'enrolled'], true) ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('contract.contract_number')->label('Contrato')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Etapa')->options(CommercialLead::statuses()),
                SelectFilter::make('source')->label('Origem / Meio de captação')->options(CommercialLead::sources()),
                SelectFilter::make('commercial_loss_reason_id')
                    ->label('Motivo da Perda')
                    ->options(fn (): array => CommercialLossReason::query()->where('active', true)->pluck('name', 'id')->all()),
                SelectFilter::make('collaborator_id')
                    ->label('Responsável')
                    ->options(fn (): array => Collaborator::query()->join('people', 'people.id', '=', 'collaborators.person_id')->orderBy('people.name')->pluck('people.name', 'collaborators.id')->all()),
                Filter::make('overdue_follow_up')
                    ->label('Retorno atrasado')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now())->whereNotIn('status', ['lost', 'enrolled'])),
            ])
            ->recordActions([
                Action::make('enrollStudent')
                    ->label('Matricular Aluno')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->visible(fn (CommercialLead $record): bool => ! $record->contract()->exists())
                    ->authorize(fn (): bool => auth()->user()?->can('Create:Contract') ?? false)
                    ->modalHeading(fn (CommercialLead $record): string => "Iniciar Matrícula de {$record->name}")
                    ->modalDescription('Defina quem será o responsável financeiro pelo contrato e prossiga para o preenchimento.')
                    ->modalSubmitActionLabel('Prosseguir para Contrato')
                    ->form(fn (CommercialLead $record): array => [
                        Select::make('responsible_mode')
                            ->label('Quem é o responsável financeiro do contrato?')
                            ->options([
                                'student' => 'O próprio aluno (maior de idade / pagador)',
                                'other' => 'Outra pessoa (Pai, Mãe, Responsável Legal)',
                            ])
                            ->default(filled($record->responsible_name) ? 'other' : 'student')
                            ->live()
                            ->required(),
                        TextInput::make('responsible_name')
                            ->label('Nome do Responsável')
                            ->default($record->responsible_name)
                            ->required(fn (Get $get): bool => $get('responsible_mode') === 'other')
                            ->visible(fn (Get $get): bool => $get('responsible_mode') === 'other'),
                        Select::make('responsible_relationship')
                            ->label('Parentesco')
                            ->options(RelationshipEnum::class)
                            ->default($record->responsible_relationship?->value ?? $record->responsible_relationship ?? RelationshipEnum::Legal_guardian->value)
                            ->required(fn (Get $get): bool => $get('responsible_mode') === 'other')
                            ->visible(fn (Get $get): bool => $get('responsible_mode') === 'other'),
                        TextInput::make('responsible_phone')
                            ->label('Telefone/WhatsApp do Responsável')
                            ->tel()
                            ->default($record->responsible_phone)
                            ->visible(fn (Get $get): bool => $get('responsible_mode') === 'other'),
                    ])
                    ->action(function (CommercialLead $record, array $data) {
                        $tenantId = Filament::getTenant()?->id ?? $record->school_id;

                        // 1. Aluno
                        $studentPerson = $record->person;
                        if (! $studentPerson) {
                            $studentPerson = Person::create([
                                'name' => $record->name,
                                'email' => $record->email,
                                'birth_date' => $record->birth_date,
                                'gender' => $record->gender,
                            ]);

                            if (filled($record->phone)) {
                                Contact::create([
                                    'person_id' => $studentPerson->id,
                                    'type' => ContactTypeEnum::Celular,
                                    'value' => $record->phone,
                                    'is_primary' => true,
                                ]);
                            }

                            $record->forceFill(['person_id' => $studentPerson->id])->saveQuietly();
                        } else {
                            if (! $studentPerson->gender && $record->gender) {
                                $studentPerson->update(['gender' => $record->gender]);
                            }
                        }

                        if ($tenantId) {
                            $studentPerson->schools()->syncWithoutDetaching([$tenantId]);
                        }

                        // 2. Responsável Financeiro
                        if (($data['responsible_mode'] ?? 'student') === 'other' && filled($data['responsible_name'] ?? null)) {
                            $respPerson = Person::create([
                                'name' => trim($data['responsible_name']),
                            ]);

                            if (filled($data['responsible_phone'] ?? null)) {
                                Contact::create([
                                    'person_id' => $respPerson->id,
                                    'type' => ContactTypeEnum::Celular,
                                    'value' => trim($data['responsible_phone']),
                                    'is_primary' => true,
                                ]);
                            }

                            if ($tenantId) {
                                $respPerson->schools()->syncWithoutDetaching([$tenantId]);
                            }

                            $relEnum = RelationshipEnum::tryFrom($data['responsible_relationship'] ?? '') ?? RelationshipEnum::Legal_guardian;

                            $responsible = Responsible::create([
                                'person_id' => $respPerson->id,
                                'financial_responsible' => true,
                                'relationship' => $relEnum,
                            ]);
                        } else {
                            $responsible = Responsible::firstOrCreate(
                                [
                                    'person_id' => $studentPerson->id,
                                    'financial_responsible' => true,
                                ],
                                [
                                    'relationship' => RelationshipEnum::Legal_guardian,
                                ]
                            );
                        }

                        return redirect()->to(CreateContracts::getUrl([
                            'student_id' => $studentPerson->id,
                            'responsible_id' => $responsible->id,
                            'commercial_lead_id' => $record->id,
                            'collaborator_id' => $record->collaborator_id,
                        ]));
                    }),
                EditAction::make(),
                Action::make('transferLeadSchool')
                    ->label('Transferir Unidade')
                    ->icon('heroicon-m-arrows-right-left')
                    ->color('info')
                    ->modalHeading(fn (CommercialLead $record): string => "Encaminhar Lead ({$record->name}) para outra Unidade")
                    ->modalDescription('Transfere este interessado comercial para outra escola da rede dar continuidade ao atendimento.')
                    ->modalSubmitActionLabel('Encaminhar Lead')
                    ->visible(fn (CommercialLead $record): bool => ! $record->contract()->exists() && School::query()->withoutGlobalScopes()->count() > 1)
                    ->authorize(fn (): bool => (auth()->user()?->isSuperAdmin() ?? false) || (auth()->user()?->can('Update:CommercialLead') ?? false))
                    ->schema(fn (CommercialLead $record): array => [
                        Select::make('target_school_id')
                            ->label('Unidade de Destino')
                            ->options(fn (): array => School::query()
                                ->withoutGlobalScopes()
                                ->where('id', '!=', $record->school_id)
                                ->orderBy('trade_name')
                                ->get()
                                ->mapWithKeys(fn (School $s): array => [$s->id => $s->getFilamentName()])
                                ->all())
                            ->required()
                            ->native(false)
                            ->searchable(),
                        Textarea::make('reason')
                            ->label('Observação para o consultor da unidade de destino')
                            ->placeholder('Ex: Cliente mora próximo à unidade destino e prefere atendimento presencial lá.')
                            ->rows(3),
                        Toggle::make('send_notification')
                            ->label('Enviar alerta na Comunicação da Rede')
                            ->default(true),
                    ])
                    ->action(function (CommercialLead $record, array $data): void {
                        $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($data['target_school_id']);

                        app(SchoolTransferService::class)->transferLead(
                            lead: $record,
                            targetSchool: $targetSchool,
                            reason: $data['reason'] ?? null,
                            sendNotificationMessage: (bool) ($data['send_notification'] ?? true),
                        );

                        Notification::make()
                            ->title('Lead encaminhado com sucesso!')
                            ->body("O interessado {$record->name} foi transferido para {$targetSchool->getFilamentName()}.")
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->visible(fn (CommercialLead $record): bool => ! $record->contract()->exists()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('transferSelectedLeads')
                        ->label('Transferir Selecionados de Unidade')
                        ->icon('heroicon-m-arrows-right-left')
                        ->color('info')
                        ->visible(fn (): bool => School::query()->withoutGlobalScopes()->count() > 1)
                        ->authorize(fn (): bool => (auth()->user()?->isSuperAdmin() ?? false) || (auth()->user()?->can('Update:CommercialLead') ?? false))
                        ->modalHeading('Encaminhar Leads Selecionados em Lote para outra Unidade')
                        ->modalSubmitActionLabel('Encaminhar Leads')
                        ->deselectRecordsAfterCompletion()
                        ->schema([
                            Select::make('target_school_id')
                                ->label('Unidade de Destino')
                                ->options(function (): array {
                                    $tenantId = Filament::getTenant()?->id;

                                    return School::query()
                                        ->withoutGlobalScopes()
                                        ->when($tenantId, fn ($q) => $q->where('id', '!=', $tenantId))
                                        ->orderBy('trade_name')
                                        ->get()
                                        ->mapWithKeys(fn (School $s): array => [$s->id => $s->getFilamentName()])
                                        ->all();
                                })
                                ->required()
                                ->native(false)
                                ->searchable(),
                            Textarea::make('reason')
                                ->label('Observação para a unidade de destino')
                                ->rows(2),
                            Toggle::make('send_notification')
                                ->label('Enviar alerta na Comunicação da Rede')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($data['target_school_id']);
                            $service = app(SchoolTransferService::class);
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->contract()->exists() || (int) $record->school_id === (int) $targetSchool->id) {
                                    continue;
                                }

                                $service->transferLead(
                                    lead: $record,
                                    targetSchool: $targetSchool,
                                    reason: $data['reason'] ?? null,
                                    sendNotificationMessage: (bool) ($data['send_notification'] ?? true),
                                );
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} lead(s) encaminhado(s) com sucesso!")
                                ->body("Os interessados selecionados foram transferidos para {$targetSchool->getFilamentName()}.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCommercialLeads::route('/')];
    }
}
