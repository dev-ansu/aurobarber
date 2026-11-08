<?php

namespace App\Filament\Resources\Collaborators\Tables;

use App\Actions\ToggleStatusAction;
use App\Filament\Exports\CollaboratorExporter;
use App\Models\Collaborator;
use App\Services\OuroModernoEducatorSyncService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CollaboratorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('person.name')
                    ->label('Colaborador')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Collaborator $record): ?string => $record->person?->email ? ($record->person->email . ($record->person->cpf ? ' • CPF: ' . $record->person->cpf : '')) : null),
                TextColumn::make('person.email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('admission_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('dismissal_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Cargo')
                    ->searchable(),
                TextColumn::make('formatted_commission_rule')
                    ->label('Comissão / Regra')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Sem comissão' ? 'gray' : 'success'),
                TextColumn::make('pix')
                    ->label('Chave PIX')
                    ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskPix($state))
                    ->searchable(),
                IconColumn::make('is_educator')
                    ->label('Educador')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
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
                TernaryFilter::make('is_educator')->label('Educador LMS')->trueLabel('Apenas Educadores')->falseLabel('Não Educadores')->placeholder('Todos'),
                TernaryFilter::make('active')->label('Situação')->trueLabel('Ativos')->falseLabel('Inativos')->placeholder('Todos'),
                SelectFilter::make('role')->label('Cargo')->options(fn (): array => Collaborator::query()
                    ->whereNotNull('role')->distinct()->orderBy('role')->pluck('role', 'role')->all())->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('createUser')
                    ->label('Criar Usuário')
                    ->tooltip('Criar conta de usuário no sistema para este colaborador')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn (Collaborator $record): bool => \App\Filament\Resources\Users\UserResource::canCreate() && ! \App\Models\User::where('person_id', $record->person_id)->exists())
                    ->url(fn (Collaborator $record): string => route('filament.admin.resources.users.create', [
                        'person_id' => $record->person_id,
                    ])),
                Action::make('editUser')
                    ->label('Editar Usuário')
                    ->tooltip('Editar conta de usuário deste colaborador')
                    ->icon('heroicon-o-user')
                    ->color('gray')
                    ->visible(fn (Collaborator $record): bool => ($u = \App\Models\User::where('person_id', $record->person_id)->first()) && \App\Filament\Resources\Users\UserResource::canEdit($u))
                    ->url(fn (Collaborator $record): string => route('filament.admin.resources.users.edit', [
                        'record' => \App\Models\User::where('person_id', $record->person_id)->first()->id,
                    ])),
                Action::make('viewPersonData')
                    ->label('')
                    ->tooltip('Ver dados da pessoa')
                    ->icon('heroicon-m-user')
                    ->color('gray')
                    ->modalHeading(fn (Collaborator $record): string => "Dados da Pessoa — " . ($record->person?->name ?? 'Colaborador'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->infolist(fn (Collaborator $record): array => [
                        \Filament\Schemas\Components\Section::make('Identificação e Contato')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('person.name')->label('Nome Completo')->weight('bold'),
                                \Filament\Infolists\Components\TextEntry::make('person.email')->label('E-mail Principal')->copyable()->placeholder('—'),
                                \Filament\Infolists\Components\TextEntry::make('person.cpf')->label('CPF')->copyable()->placeholder('—'),
                                \Filament\Infolists\Components\TextEntry::make('person.birth_date')->label('Data de Nascimento')->date('d/m/Y')->placeholder('—'),
                                \Filament\Infolists\Components\TextEntry::make('role')->label('Cargo')->badge(),
                                \Filament\Infolists\Components\TextEntry::make('pix')
                                    ->label('Chave PIX')
                                    ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskPix($state))
                                    ->placeholder('—'),
                            ])->columns(2),
                    ]),
                Action::make('syncOuroModerno')
                    ->label('Sincronizar Ouro Moderno')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Collaborator $record): bool => (bool) $record->is_educator)
                    ->form([
                        TextInput::make('password')
                            ->label('Senha no LMS Ouro Moderno')
                            ->default(fn (Collaborator $record): ?string => $record->person?->ouro_moderno_senha ?: 'prof123')
                            ->required()
                            ->helperText('Defina a senha com a qual o educador terá acesso aos cursos na Ouro Moderno.'),
                    ])
                    ->action(function (Collaborator $record, array $data, OuroModernoEducatorSyncService $syncService): void {
                        try {
                            $result = $syncService->sync($record, $data['password']);
                            Notification::make()
                                ->title('Educador sincronizado com a Ouro Moderno!')
                                ->body("Usuário '{$result['username']}' matriculado em {$result['courses_enrolled']} curso(s) com sucesso.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erro ao sincronizar educador')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                ToggleStatusAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar colaboradores')->exporter(CollaboratorExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Collaborator') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Collaborator') ?? false),
            ]);
    }
}
