<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarbershopClientResource\Pages;
use App\Models\BarbershopClient;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BarbershopClientResource extends Resource
{
    protected static ?string $model = BarbershopClient::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';

    protected static string | \UnitEnum | null $navigationGroup = 'Atendimento';

    protected static ?string $navigationLabel = 'Clientes, Retorno & Fidelidade';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes & Fidelidade';

    protected static ?int $navigationSort = 15;

    protected static ?string $tenantRelationshipName = 'clients';

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->isRestrictedBarber();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Cliente')
                    ->tabs([
                        Tab::make('Dados do Cliente & Aniversário')
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome do Cliente')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('phone')
                                    ->label('WhatsApp do Cliente')
                                    ->tel()
                                    ->placeholder('85999999999')
                                    ->required()
                                    ->maxLength(30)
                                    ->columnSpanFull(),

                                DatePicker::make('birth_date')
                                    ->label('Data de Nascimento / Aniversário (Opcional)')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Permite listar o cliente nos Aniversariantes do Mês para mandar parabéns no WhatsApp.')
                                    ->columnSpanFull(),

                                Textarea::make('notes')
                                    ->label('Preferências de Corte & Observações')
                                    ->placeholder('Ex: Degradê na zero alta, risca na sobrancelha direita, gosta de café expresso.')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Cartão Fidelidade & Histórico')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                TextInput::make('loyalty_stamps')
                                    ->label('Selos Acumulados no Cartão Fidelidade Atual')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('selos')
                                    ->helperText('Aumenta automaticamente +1 a cada atendimento concluído.')
                                    ->columnSpanFull(),

                                TextInput::make('loyalty_rewards_redeemed')
                                    ->label('Prêmios / Cortes Grátis Já Resgatados')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Cliente')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (BarbershopClient $record): ?string => $record->notes ? \Illuminate\Support\Str::limit($record->notes, 45) : null),

                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('total_visits')
                    ->label('Visitas')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (int $state): string => "{$state} cortes")
                    ->sortable(),

                TextColumn::make('loyalty_stamps')
                    ->label('Cartão Fidelidade')
                    ->badge()
                    ->color(function (BarbershopClient $record): string {
                        $target = (int) ($record->barbershop?->loyalty_target_cuts ?: 10);
                        return $record->loyalty_stamps >= $target ? 'success' : 'warning';
                    })
                    ->formatStateUsing(function (int $state, BarbershopClient $record): string {
                        $target = (int) ($record->barbershop?->loyalty_target_cuts ?: 10);
                        if ($state >= $target) {
                            return "🎁 PRÊMIO LIBERADO ({$state}/{$target})";
                        }
                        return "⭐ {$state} / {$target} selos";
                    })
                    ->sortable(),

                TextColumn::make('last_visit_at')
                    ->label('Último Corte / Retorno')
                    ->badge()
                    ->color(function (BarbershopClient $record): string {
                        $days = $record->daysSinceLastVisit();
                        $limit = (int) ($record->barbershop?->return_reminder_days ?: 20);
                        if ($days === null) {
                            return 'gray';
                        }
                        return $days >= $limit ? 'danger' : 'success';
                    })
                    ->formatStateUsing(function (BarbershopClient $record): string {
                        $days = $record->daysSinceLastVisit();
                        if ($days === null) {
                            return 'Sem registro';
                        }
                        if ($days === 0) {
                            return 'Hoje';
                        }
                        $limit = (int) ($record->barbershop?->return_reminder_days ?: 20);
                        return $days >= $limit ? "⏰ Há {$days} dias (Chamar!)" : "Há {$days} dia(s)";
                    })
                    ->sortable(),

                TextColumn::make('total_spent')
                    ->label('Total Investido')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('birth_date')
                    ->label('Aniversário')
                    ->date('d/m')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('needs_return')
                    ->label('🔁 Hora de Voltar (+20 dias sem cortar)')
                    ->query(function (Builder $query): Builder {
                        $days = (int) (filament()->getTenant()?->return_reminder_days ?: 20);
                        return $query->whereNotNull('last_visit_at')
                            ->where('last_visit_at', '<=', now()->subDays($days));
                    }),

                Filter::make('loyalty_ready')
                    ->label('🎁 Com Prêmio Fidelidade Liberado')
                    ->query(function (Builder $query): Builder {
                        $target = (int) (filament()->getTenant()?->loyalty_target_cuts ?: 10);
                        return $query->where('loyalty_stamps', '>=', $target);
                    }),

                Filter::make('birthday_month')
                    ->label('🎂 Aniversariantes deste Mês')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('birth_date', now()->month)),
            ])
            ->actions([
                Action::make('whatsapp_retorno')
                    ->label('Chamar WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (BarbershopClient $record): string {
                        $digits = preg_replace('/\D/', '', $record->phone);
                        $phone = str_starts_with($digits, '55') ? $digits : '55' . $digits;
                        $shop = $record->barbershop;
                        $shopName = $shop?->name ?? 'nossa Barbearia';
                        $bookingUrl = $shop ? url("/{$shop->slug}") : url('/');
                        $days = $record->daysSinceLastVisit();
                        $target = (int) ($shop?->loyalty_target_cuts ?: 10);

                        if ($record->loyalty_stamps >= $target) {
                            $msg = "Fala *{$record->name}*! 🎉 Você completou *{$target} selos* no Cartão Fidelidade da *{$shopName}* e ganhou um atendimento especial! Agende seu horário pelo link: {$bookingUrl}";
                        } elseif ($record->birth_date && $record->birth_date->month === now()->month && $record->birth_date->day === now()->day) {
                            $msg = "Parabéns *{$record->name}*! 🎂💈 A equipe da *{$shopName}* deseja muitas felicidades e sucesso! Passa aqui essa semana pra dar aquele trato no visual: {$bookingUrl}";
                        } elseif ($days !== null && $days >= 15) {
                            $msg = "Fala *{$record->name}*, tudo tranquilo? 💈 Já faz *{$days} dias* do seu último trato aqui na *{$shopName}*! Bora garantir seu horário dessa semana? Agende em 1 minuto: {$bookingUrl}";
                        } else {
                            $msg = "Olá *{$record->name}*! Tudo certo? Equipe da *{$shopName}* falando aqui. 💈✂️ Link rápido da nossa agenda: {$bookingUrl}";
                        }

                        return 'https://wa.me/' . $phone . '?text=' . urlencode($msg);
                    })
                    ->openUrlInNewTab(),

                Action::make('resgatar_fidelidade')
                    ->label('Resgatar Prêmio 🎁')
                    ->icon('heroicon-o-gift')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resgatar Prêmio do Cartão Fidelidade')
                    ->modalDescription('Isso descontará a meta de selos atual e contabilizará +1 prêmio entregue ao cliente.')
                    ->visible(function (BarbershopClient $record): bool {
                        $target = (int) ($record->barbershop?->loyalty_target_cuts ?: 10);
                        return $record->loyalty_stamps >= $target;
                    })
                    ->action(function (BarbershopClient $record): void {
                        $target = (int) ($record->barbershop?->loyalty_target_cuts ?: 10);
                        $record->update([
                            'loyalty_stamps' => max(0, $record->loyalty_stamps - $target),
                            'loyalty_rewards_redeemed' => $record->loyalty_rewards_redeemed + 1,
                        ]);

                        Notification::make()
                            ->title('Prêmio Fidelidade resgatado com sucesso! 🎁')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarbershopClients::route('/'),
            'create' => Pages\CreateBarbershopClient::route('/create'),
            'edit' => Pages\EditBarbershopClient::route('/{record}/edit'),
        ];
    }
}
