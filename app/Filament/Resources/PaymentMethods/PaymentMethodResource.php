<?php

namespace App\Filament\Resources\PaymentMethods;

use App\Filament\Exports\PaymentMethodExporter;
use App\Filament\Resources\PaymentMethods\Pages\ManagePaymentMethods;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class PaymentMethodResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-m-credit-card';

    protected static ?string $navigationLabel = 'Formas de Pagamento';

    protected static ?string $modelLabel = 'Forma de Pagamento';

    protected static ?string $pluralModelLabel = 'Formas de Pagamento';

    protected static string|UnitEnum|null $navigationGroup = 'Configurações Financeiras';

    protected static ?int $navigationSort = 51;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Forma de pagamento')
                    ->description('Cadastre como os recebimentos são identificados no caixa e nos relatórios.')
                    ->schema([
                        TextInput::make('name')->label('Nome')->placeholder('Ex.: PIX, dinheiro, cartão')
                            ->required()->maxLength(255)->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state, '_'))),
                        TextInput::make('slug')->label('Identificador')->required()->maxLength(255)->disabled()
                            ->unique(ignoreRecord: true)->helperText('Gerado automaticamente a partir do nome.'),
                        Toggle::make('is_active')->label('Ativa para novos recebimentos')->default(true)->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Situação')->trueLabel('Ativas')->falseLabel('Inativas')->placeholder('Todas'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar formas de pagamento')->exporter(PaymentMethodExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:PaymentMethod') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:PaymentMethod') ?? false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePaymentMethods::route('/'),
        ];
    }
}
