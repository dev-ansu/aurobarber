<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Atendimento';

    protected static ?string $navigationLabel = 'Agendamentos';

    protected static ?string $modelLabel = 'Agendamento';

    protected static ?string $pluralModelLabel = 'Agendamentos';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->isRestrictedBarber()) {
            $proId = $user->resolveProfessionalId() ?? 0;
            $query->where('professional_id', $proId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Agendamento')
                    ->tabs([
                        Tab::make('Atendimento')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Forms\Components\Select::make('professional_id')
                                    ->label('Barbeiro / Profissional')
                                    ->relationship('professional', 'name')
                                    ->default(fn () => auth()->user()?->isRestrictedBarber() ? auth()->user()->resolveProfessionalId() : null)
                                    ->disabled(fn (): bool => (bool) auth()->user()?->isRestrictedBarber())
                                    ->dehydrated()
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('service_id')
                                    ->label('Serviço')
                                    ->relationship('service', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($service = Service::find($state)) {
                                            $set('service_price', $service->price);
                                        }
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\DateTimePicker::make('scheduled_at')
                                    ->label('Data e Horário')
                                    ->required()
                                    ->default(now())
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('status')
                                    ->label('Status do Agendamento')
                                    ->options([
                                        'scheduled' => 'Agendado',
                                        'confirmed' => 'Confirmado',
                                        'in_progress' => 'Em Atendimento',
                                        'completed' => 'Concluído',
                                        'cancelled' => 'Cancelado',
                                        'no_show' => 'Não Compareceu',
                                    ])
                                    ->default('scheduled')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Cliente')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\TextInput::make('client_name')
                                    ->label('Nome do Cliente')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('client_phone')
                                    ->label('WhatsApp do Cliente')
                                    ->tel()
                                    ->required()
                                    ->placeholder('(85) 99999-9999')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Observações do Corte / Preferências')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Financeiro')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\TextInput::make('service_price')
                                    ->label('Valor do Serviço (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('payment_method')
                                    ->label('Forma de Pagamento')
                                    ->options([
                                        'pix' => 'Pix',
                                        'dinheiro' => 'Dinheiro',
                                        'cartao_credito' => 'Cartão de Crédito',
                                        'cartao_debito' => 'Cartão de Débito',
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('payment_status')
                                    ->label('Status do Pagamento')
                                    ->options([
                                        'pending' => 'Pendente',
                                        'paid' => 'Pago',
                                    ])
                                    ->default('pending')
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
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Data / Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client_phone')
                    ->label('WhatsApp')
                    ->searchable(),

                Tables\Columns\TextColumn::make('professional.name')
                    ->label('Barbeiro')
                    ->sortable(),

                Tables\Columns\TextColumn::make('services_summary')
                    ->label('Serviço(s)')
                    ->getStateUsing(fn (Appointment $record) => $record->services_summary)
                    ->wrap(),

                Tables\Columns\TextColumn::make('service_price')
                    ->label('Total')
                    ->getStateUsing(fn (Appointment $record): float => (float) $record->service_price + (float) $record->products_total)
                    ->money('BRL')
                    ->description(fn (Appointment $record): ?string => $record->products_total > 0 ? '+ R$ ' . number_format((float) $record->products_total, 2, ',', '.') . ' em produtos' : null)
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Avaliação')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('⭐', $state) : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'confirmed' => 'info',
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Agendado',
                        'confirmed' => 'Confirmado',
                        'in_progress' => 'Em Atendimento',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                        default => ucfirst($state),
                    }),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Agendado',
                        'confirmed' => 'Confirmado',
                        'in_progress' => 'Em Atendimento',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('professional_id')
                    ->label('Barbeiro')
                    ->relationship('professional', 'name'),
            ])
            ->actions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function (Appointment $record): string {
                        $digits = preg_replace('/\D/', '', (string) $record->client_phone);
                        $phone = str_starts_with($digits, '55') ? $digits : '55' . $digits;
                        $dateStr = $record->scheduled_at ? $record->scheduled_at->format('d/m \à\s H:i') : '';
                        $shopName = $record->barbershop?->name ?? 'Barbearia';

                        if ($record->status === 'completed') {
                            $rateUrl = url("/avaliar/{$record->id}");
                            $msg = "Olá *{$record->client_name}*! Obrigado pela preferência hoje na *{$shopName}*. 💈✂️\n\nAvalie seu atendimento rapidinho (leva 5 segundos): {$rateUrl}";
                        } else {
                            $msg = "Olá *{$record->client_name}*! Confirmando seu horário na *{$shopName}* para *{$dateStr}* ({$record->services_summary}). Tudo certo? 💈✂️";
                        }

                        return 'https://wa.me/' . $phone . '?text=' . urlencode($msg);
                    })
                    ->openUrlInNewTab(),

                Action::make('concluir')
                    ->label('Concluir & Comanda')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn (Appointment $record) => $record->status !== 'completed' && !$record->is_blocked_slot)
                    ->form([
                        Tabs::make('CheckoutComanda')
                            ->tabs([
                                Tab::make('Pagamento & Comanda')
                                    ->icon('heroicon-o-banknotes')
                                    ->schema([
                                        Forms\Components\Select::make('payment_method')
                                            ->label('Forma de Pagamento')
                                            ->options([
                                                'pix' => 'PIX',
                                                'dinheiro' => 'Dinheiro',
                                                'cartao_credito' => 'Cartão de Crédito',
                                                'cartao_debito' => 'Cartão de Débito',
                                            ])
                                            ->default('pix')
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('consumed_product_ids')
                                            ->label('Adicionar Consumo do Bar / Produtos na Comanda (Opcional)')
                                            ->options(fn () => \App\Models\Product::where('barbershop_id', filament()->getTenant()?->id)
                                                ->where('is_active', true)
                                                ->where('stock_quantity', '>', 0)
                                                ->get()
                                                ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} — R$ " . number_format((float) $p->price, 2, ',', '.') . " ({$p->stock_quantity} em estoque)"]))
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Os produtos selecionados serão somados ao total e baixados automaticamente do estoque.')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (Appointment $record, array $data): void {
                        $productIds = (array) ($data['consumed_product_ids'] ?? []);
                        $productsTotal = 0.0;

                        if (!empty($productIds)) {
                            $products = \App\Models\Product::whereIn('id', $productIds)->get();
                            foreach ($products as $prod) {
                                $productsTotal += (float) $prod->price;
                                $record->products()->attach($prod->id, [
                                    'quantity' => 1,
                                    'unit_price' => $prod->price,
                                    'total_price' => $prod->price,
                                ]);
                                $prod->decrement('stock_quantity', 1);
                            }
                        }

                        $commRate = (float) ($record->professional?->commission_rate ?? 50.0);
                        $commAmount = round(((float) $record->service_price * $commRate) / 100, 2);

                        $record->update([
                            'status' => 'completed',
                            'payment_status' => 'paid',
                            'payment_method' => $data['payment_method'] ?? 'pix',
                            'paid_at' => now(),
                            'products_total' => (float) $record->products_total + $productsTotal,
                            'commission_rate' => $commRate,
                            'commission_amount' => $commAmount,
                            'commission_status' => 'pending',
                        ]);

                        $grandTotal = (float) $record->service_price + (float) $record->products_total;

                        \App\Models\CashMovement::create([
                            'barbershop_id' => $record->barbershop_id,
                            'appointment_id' => $record->id,
                            'type' => 'in',
                            'category' => 'Atendimento',
                            'description' => "Atendimento #{$record->id} - {$record->client_name} ({$record->services_summary})",
                            'amount' => $grandTotal,
                            'payment_method' => $data['payment_method'] ?? 'pix',
                            'occurred_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Atendimento concluído e lançado no Caixa!')
                            ->body('Total recebido: R$ ' . number_format($grandTotal, 2, ',', '.'))
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
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
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
