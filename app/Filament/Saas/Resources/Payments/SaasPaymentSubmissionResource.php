<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\Payments;

use App\Filament\Saas\Resources\Payments\Pages\ListSaasPaymentSubmissions;
use App\Models\SaasPaymentSubmission;
use App\Models\User;
use App\Services\SaasPaymentProofStorage;
use App\Services\SaasPaymentReviewService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class SaasPaymentSubmissionResource extends Resource
{
    protected static ?string $model = SaasPaymentSubmission::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Comprovantes Pix';

    protected static ?string $modelLabel = 'comprovante';

    protected static ?string $pluralModelLabel = 'comprovantes Pix';

    protected static string|UnitEnum|null $navigationGroup = 'Cobrança de Licenças';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = SaasPaymentSubmission::query()->where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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
                TextColumn::make('saasPlan.name')->label('Plano')->placeholder('Personalizado'),
                TextColumn::make('submitted_by_name')->label('Enviado por')->placeholder('—'),
                TextColumn::make('amount')->label('Valor')->money('BRL')->sortable(),
                TextColumn::make('proof_path')
                    ->label('Arquivo')
                    ->formatStateUsing(fn (?string $state, SaasPaymentSubmission $record): string => app(SaasPaymentProofStorage::class)->exists($record) ? 'Disponível' : 'Arquivo ausente')
                    ->badge()
                    ->color(fn (?string $state, SaasPaymentSubmission $record): string => app(SaasPaymentProofStorage::class)->exists($record) ? 'success' : 'danger'),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Recusado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Enviado em')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('review_note')->label('Observação')->wrap()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Situação')->options([
                    'pending' => 'Pendente',
                    'approved' => 'Aprovado',
                    'rejected' => 'Recusado',
                ]),
            ])
            ->recordActions([
                Action::make('view_proof')
                    ->label('Abrir comprovante')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->disabled(fn (SaasPaymentSubmission $record): bool => ! app(SaasPaymentProofStorage::class)->exists($record))
                    ->url(fn (SaasPaymentSubmission $record): string => URL::temporarySignedRoute(
                        'saas.billing.proofs.show',
                        now()->addMinutes(10),
                        ['submission' => $record->id],
                    ))
                    ->openUrlInNewTab(),
                Action::make('approve')
                    ->label('Confirmar e renovar')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SaasPaymentSubmission $record): bool => $record->status === 'pending')
                    ->form([
                        TextInput::make('confirmed_amount')
                            ->label('Valor recebido confirmado')
                            ->numeric()
                            ->prefix('R$')
                            ->minValue(0.01)
                            ->step(0.01)
                            ->default(fn (SaasPaymentSubmission $record): string => (string) $record->amount)
                            ->required(),
                        TextInput::make('renewal_days')
                            ->label('Dias de renovação')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(fn (SaasPaymentSubmission $record): int => $record->barbershop?->effectiveRenewalDays() ?? 30)
                            ->required(),
                        DatePicker::make('received_on')
                            ->label('Data do recebimento')
                            ->native(false)
                            ->default(now()->toDateString())
                            ->maxDate(now())
                            ->required(),
                    ])
                    ->action(function (SaasPaymentSubmission $record, array $data, SaasPaymentReviewService $review): void {
                        $days = (int) ($data['renewal_days'] ?? 30);
                        $review->approve($record, (string) $data['confirmed_amount'], (string) $data['received_on'], $days);

                        Notification::make()
                            ->title('Recebimento confirmado e licença renovada!')
                            ->body("O valor foi registrado nos recebimentos SaaS e a licença foi renovada por {$days} dias.")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Recusar')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (SaasPaymentSubmission $record): bool => $record->status === 'pending')
                    ->form([
                        Textarea::make('reason')->label('Motivo da recusa')->required()->maxLength(2000)->columnSpanFull(),
                    ])
                    ->action(function (SaasPaymentSubmission $record, array $data, SaasPaymentReviewService $review): void {
                        $review->reject($record, (string) $data['reason']);

                        Notification::make()
                            ->title('Comprovante recusado')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaasPaymentSubmissions::route('/'),
        ];
    }
}
