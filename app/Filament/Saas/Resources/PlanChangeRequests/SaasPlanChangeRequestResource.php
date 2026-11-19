<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\PlanChangeRequests;

use App\Filament\Saas\Resources\PlanChangeRequests\Pages\ListSaasPlanChangeRequests;
use App\Models\SaasPlanChangeRequest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class SaasPlanChangeRequestResource extends Resource
{
    protected static ?string $model = SaasPlanChangeRequest::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationLabel = 'Solicitações de Plano';

    protected static ?string $modelLabel = 'solicitação de plano';

    protected static ?string $pluralModelLabel = 'solicitações de plano';

    protected static string|UnitEnum|null $navigationGroup = 'Cobrança de Licenças';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = SaasPlanChangeRequest::query()->where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('barbershop.name')->label('Barbearia')->searchable()->sortable()->weight('bold'),
                TextColumn::make('currentPlan.name')->label('Plano atual')->placeholder('Personalizado'),
                TextColumn::make('requestedPlan.name')->label('Plano solicitado')->badge()->color('primary'),
                TextColumn::make('requested_by_name')->label('Solicitado por')->placeholder('—'),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'contacted' => 'Em contato',
                        'approved' => 'Aplicado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'contacted' => 'info',
                        'approved' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Solicitado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Situação')->options([
                    'pending' => 'Pendente',
                    'contacted' => 'Em contato',
                    'approved' => 'Aplicado',
                    'cancelled' => 'Cancelado',
                ]),
            ])
            ->recordActions([
                Action::make('apply_plan')
                    ->label('Aplicar plano')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SaasPlanChangeRequest $record): bool => in_array($record->status, ['pending', 'contacted'], true))
                    ->action(function (SaasPlanChangeRequest $record): void {
                        $barbershop = $record->barbershop;
                        $plan = $record->requestedPlan;
                        if ($barbershop && $plan) {
                            $barbershop->update([
                                'saas_plan_id' => $plan->id,
                                'monthly_price' => $plan->monthly_price,
                            ]);
                        }

                        $record->update(['status' => 'approved']);

                        Notification::make()
                            ->title('Plano aplicado com sucesso!')
                            ->body("A barbearia {$barbershop?->name} foi atualizada para o {$plan?->name}.")
                            ->success()
                            ->send();
                    }),
                Action::make('cancel_request')
                    ->label('Cancelar')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (SaasPlanChangeRequest $record): bool => in_array($record->status, ['pending', 'contacted'], true))
                    ->action(function (SaasPlanChangeRequest $record): void {
                        $record->update(['status' => 'cancelled']);

                        Notification::make()
                            ->title('Solicitação cancelada')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaasPlanChangeRequests::route('/'),
        ];
    }
}
