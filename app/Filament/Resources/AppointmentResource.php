<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Atendimento')
                    ->schema([
                        Forms\Components\Select::make('professional_id')
                            ->label('Barbeiro / Profissional')
                            ->relationship('professional', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

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
                            }),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Data e Horário')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'scheduled' => 'Agendado',
                                'confirmed' => 'Confirmado',
                                'in_progress' => 'Em Atendimento',
                                'completed' => 'Concluído',
                                'cancelled' => 'Cancelado',
                                'no_show' => 'Não Compareceu',
                            ])
                            ->default('scheduled')
                            ->required(),
                    ])->columns(2),

                Section::make('Dados do Cliente & Pagamento')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->label('Nome do Cliente')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_phone')
                            ->label('WhatsApp do Cliente')
                            ->tel()
                            ->required()
                            ->placeholder('(85) 99999-9999'),

                        Forms\Components\TextInput::make('service_price')
                            ->label('Valor do Serviço (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pagamento')
                            ->options([
                                'pix' => 'Pix',
                                'dinheiro' => 'Dinheiro',
                                'cartao_credito' => 'Cartão de Crédito',
                                'cartao_debito' => 'Cartão de Débito',
                            ]),

                        Forms\Components\Select::make('payment_status')
                            ->label('Status do Pagamento')
                            ->options([
                                'pending' => 'Pendente',
                                'paid' => 'Pago',
                            ])
                            ->default('pending'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->columnSpanFull(),
                    ])->columns(2),
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

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Serviço'),

                Tables\Columns\TextColumn::make('service_price')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('concluir')
                    ->label('Concluir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Appointment $record) => $record->status !== 'completed')
                    ->action(fn (Appointment $record) => $record->update(['status' => 'completed', 'payment_status' => 'paid'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
