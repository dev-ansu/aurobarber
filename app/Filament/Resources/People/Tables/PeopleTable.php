<?php

namespace App\Filament\Resources\People\Tables;

use App\Enums\GenderEnum;
use App\Filament\Exports\PersonExporter;
use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Filament\Resources\Contracts\ContractsResource;
use App\Models\Contract;
use App\Models\Person;
use App\Models\School;
use App\Services\SchoolTransferService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PeopleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->description(fn (Person $record) => $record->social_name ? "Nome Social: {$record->social_name}" : null)
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->label('Data de Nascimento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('social_name')
                    ->label('Nome Social')
                    ->hidden()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskEmail($state))
                    ->searchable(),
                TextColumn::make('cpf')
                    ->label('CPF')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskCpf($state))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $sub) use ($search): void {
                            $sub->whereCpf($search);
                        });
                    }),
                TextColumn::make('gender')
                    ->label('Gênero')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('gender')->label('Gênero')->options(collect(GenderEnum::cases())
                    ->mapWithKeys(fn (GenderEnum $gender): array => [$gender->value => $gender->getLabel()])
                    ->all()),
                Filter::make('birth_date')->label('Nascimento')->schema([
                    DatePicker::make('from')->label('De'), DatePicker::make('until')->label('Até'),
                ])->columns(2)->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('birth_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('birth_date', '<=', $date))),
                Filter::make('is_financial_responsible')
                    ->label('Apenas Responsáveis Financeiros')
                    ->query(fn (Builder $query): Builder => $query->whereHas('responsibles')),
                Filter::make('has_active_contract')
                    ->label('Apenas Alunos com Contrato Ativo')
                    ->query(fn (Builder $query): Builder => $query->whereHas('contracts', fn (Builder $cq) => $cq->where('status', 'active'))),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Visualizar'),
                EditAction::make()->iconButton()->tooltip('Editar'),
                ActionGroup::make([
                    \App\Services\DataMaskingService::makeRevealModalAction('revealCpf', 'CPF', fn (Person $record): string => $record->cpf ?: 'CPF não informado'),
                    Action::make('createContract')
                        ->label('Criar Contrato')
                        ->icon('heroicon-o-document-plus')
                        ->color('primary')
                        ->visible(fn (): bool => ContractsResource::canCreate())
                        ->url(fn (Person $record): string => route('filament.admin.resources.contracts.create', [
                            'student_id' => $record->id,
                        ])),
                    Action::make('createCollaborator')
                        ->label('Criar Colaborador')
                        ->icon('heroicon-o-briefcase')
                        ->color('info')
                        ->visible(fn (): bool => CollaboratorResource::canCreate())
                        ->url(fn (Person $record): string => route('filament.admin.resources.collaborators.create', [
                            'person_id' => $record->id,
                        ])),
                    Action::make('transferPersonSchool')
                        ->label('Transferir Unidade')
                        ->icon('heroicon-m-arrows-right-left')
                        ->color('warning')
                        ->visible(fn (): bool => School::query()->withoutGlobalScopes()->count() > 1)
                        ->authorize(fn (): bool => (Auth::user()?->isSuperAdmin() ?? false) || (Auth::user()?->can('Update:Person') ?? false))
                        ->modalHeading(fn (Person $record): string => "Transferir {$record->name} para outra Unidade")
                        ->modalDescription('Transfere esta pessoa para a unidade de destino. Caso possua contratos vinculados nesta unidade, você pode escolher se deseja transferir os contratos e parcelas em aberto juntos.')
                        ->modalSubmitActionLabel('Confirmar Transferência')
                        ->schema(fn (Person $record): array => [
                            Radio::make('transfer_contracts')
                                ->label('⚠️ Esta pessoa possui Contrato(s) vinculado(s) nesta unidade. O que deseja transferir?')
                                ->options([
                                    '1' => 'Transferir TUDO: Pessoa + Contratos, Progresso LMS e Financeiro em aberto (parcelas já pagas ficam na origem)',
                                    '0' => 'Transferir APENAS o cadastro da Pessoa (manter contratos e financeiro na unidade atual)',
                                ])
                                ->default('1')
                                ->required()
                                ->visible(function () use ($record): bool {
                                    $tenantId = Filament::getTenant()?->id;

                                    return Contract::query()
                                        ->withoutGlobalScopes()
                                        ->where('student_id', $record->id)
                                        ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId))
                                        ->exists();
                                }),
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
                                ->label('Motivo / Observações para a unidade de destino')
                                ->rows(2),
                            Toggle::make('keep_origin_link')
                                ->label('Manter vínculo também nesta unidade de origem (compartilhar)')
                                ->default(false),
                            Toggle::make('send_notification')
                                ->label('Notificar a unidade de destino na Comunicação da Rede')
                                ->default(true),
                        ])
                        ->action(function (Person $record, array $data): void {
                            $originSchool = Filament::getTenant() instanceof School ? Filament::getTenant() : null;
                            $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($data['target_school_id']);
                            $transferContracts = ! isset($data['transfer_contracts']) || (string) $data['transfer_contracts'] === '1';

                            app(SchoolTransferService::class)->transferPerson(
                                person: $record,
                                targetSchool: $targetSchool,
                                originSchool: $originSchool,
                                reason: $data['reason'] ?? null,
                                keepOriginPersonLink: (bool) ($data['keep_origin_link'] ?? false),
                                sendNotificationMessage: (bool) ($data['send_notification'] ?? true),
                                transferContracts: $transferContracts,
                            );

                            Notification::make()
                                ->title('Transferência realizada com sucesso!')
                                ->body($transferContracts
                                    ? "{$record->name} (junto com contratos e financeiro em aberto) foi transferido(a) para {$targetSchool->getFilamentName()}."
                                    : "O cadastro de {$record->name} foi transferido/vinculado à unidade {$targetSchool->getFilamentName()} (contratos mantidos na origem).")
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->before(function (DeleteAction $action, Person $record): void {
                            if ($reason = $record->getDeletionBlockReason()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Não foi possível excluir')
                                    ->body($reason)
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('transferSelectedPeople')
                        ->label('Transferir Selecionados de Unidade')
                        ->icon('heroicon-m-arrows-right-left')
                        ->color('info')
                        ->visible(fn (): bool => School::query()->withoutGlobalScopes()->count() > 1)
                        ->authorize(fn (): bool => (Auth::user()?->isSuperAdmin() ?? false) || (Auth::user()?->can('Update:Person') ?? false))
                        ->modalHeading('Transferir Pessoas Selecionadas em Lote para outra Unidade')
                        ->modalDescription('Selecione o destino e defina se eventuais contratos e parcelas em aberto também devem ser transferidos (valores já pagos permanecem na unidade de origem).')
                        ->modalSubmitActionLabel('Confirmar Transferência em Lote')
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
                            Radio::make('transfer_contracts')
                                ->label('Caso alguma pessoa selecionada possua Contrato(s) nesta unidade:')
                                ->options([
                                    '1' => 'Transferir TUDO (Cadastros + Contratos + LMS + Financeiro em aberto, mantendo pagos na origem)',
                                    '0' => 'Transferir APENAS o cadastro das Pessoas (manter contratos na unidade de origem)',
                                ])
                                ->default('1')
                                ->required(),
                            Textarea::make('reason')
                                ->label('Motivo / Observação da transferência em lote')
                                ->rows(2),
                            Toggle::make('keep_origin_link')
                                ->label('Manter vínculo também nesta unidade de origem (compartilhar)')
                                ->default(false),
                            Toggle::make('send_notification')
                                ->label('Notificar a unidade de destino na Comunicação da Rede')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $originSchool = Filament::getTenant() instanceof School ? Filament::getTenant() : null;
                            $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($data['target_school_id']);
                            $transferContracts = ((string) ($data['transfer_contracts'] ?? '1')) === '1';
                            $service = app(SchoolTransferService::class);
                            $count = 0;

                            foreach ($records as $record) {
                                $service->transferPerson(
                                    person: $record,
                                    targetSchool: $targetSchool,
                                    originSchool: $originSchool,
                                    reason: $data['reason'] ?? null,
                                    keepOriginPersonLink: (bool) ($data['keep_origin_link'] ?? false),
                                    sendNotificationMessage: (bool) ($data['send_notification'] ?? true),
                                    transferContracts: $transferContracts,
                                );
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} pessoa(s) transferida(s)!")
                                ->body($transferContracts
                                    ? "Os cadastros, contratos e parcelas em aberto foram transferidos para {$targetSchool->getFilamentName()}."
                                    : "Os cadastros das pessoas foram transferidos para {$targetSchool->getFilamentName()} (contratos mantidos na origem).")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar pessoas')->exporter(PersonExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Person') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Person') ?? false),
            ]);
    }
}
