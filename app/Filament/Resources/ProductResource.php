<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Professional;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Serviços';

    protected static ?string $navigationLabel = 'Produtos & Estoque (Bar)';

    protected static ?string $modelLabel = 'Produto';

    protected static ?string $pluralModelLabel = 'Produtos & Estoque';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->isRestrictedBarber();
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = filament()->getTenant();
        $user = auth()->user();

        if (! $tenant instanceof Barbershop || ! $user?->canAccessTenant($tenant)) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('products.barbershop_id', $tenant->getKey());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Produto')
                    ->tabs([
                        Tab::make('Identificação do Produto')
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome do Produto / Bebida')
                                    ->placeholder('Ex: Pomada Modeladora Matte 120g, Cerveja Heineken Long Neck')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Select::make('category')
                                    ->label('Categoria')
                                    ->options([
                                        'bar' => '🍺 Bar & Bebidas (Cerveja, Refrigerante, Energético, Café)',
                                        'cosmetico' => '💈 Cosméticos & Cuidados (Pomada, Óleo de Barba, Minoxidil, Shampoo)',
                                        'insumo' => '🪒 Uso Interno / Insumos (Lâminas, Gola Higiênica, Toalhas)',
                                        'outros' => '📦 Outros Produtos',
                                    ])
                                    ->default('bar')
                                    ->required()
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label('Disponível para Venda / Consumo na Comanda')
                                    ->default(true)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Preços & Controle de Estoque')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                TextInput::make('price')
                                    ->label('Preço de Venda ao Cliente (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(15.00)
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('cost_price')
                                    ->label('Preço de Custo Unitário (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0.00)
                                    ->helperText('Opcional: usado para calcular a margem de lucro do produto.')
                                    ->columnSpanFull(),

                                TextInput::make('stock_quantity')
                                    ->label('Quantidade Atual em Estoque')
                                    ->numeric()
                                    ->default(10)
                                    ->required()
                                    ->suffix('unidades')
                                    ->columnSpanFull(),

                                TextInput::make('min_stock_alert')
                                    ->label('Alerta de Estoque Mínimo')
                                    ->numeric()
                                    ->default(5)
                                    ->required()
                                    ->suffix('unidades')
                                    ->helperText('O sistema destaca em amarelo/vermelho quando o estoque atingir este limite.')
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
                    ->label('Produto')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'bar' => '🍺 Bar & Bebidas',
                        'cosmetico' => '💈 Cosméticos',
                        'insumo' => '🪒 Insumo Interno',
                        default => '📦 Outros',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'bar' => 'warning',
                        'cosmetico' => 'info',
                        'insumo' => 'gray',
                        default => 'primary',
                    }),

                TextColumn::make('price')
                    ->label('Preço de Venda')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Estoque Atual')
                    ->badge()
                    ->color(fn (Product $record): string => $record->stock_quantity <= 0 ? 'danger' : ($record->isLowStock() ? 'warning' : 'success'))
                    ->formatStateUsing(fn (int $state, Product $record): string => $state <= 0 ? 'ESGOTADO (0)' : ($record->isLowStock() ? "{$state} un. (Baixo)" : "{$state} un."))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->actions([
                Action::make('vender_avulso')
                    ->label('Venda Rápida')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Product $record): bool => $record->is_active && $record->stock_quantity > 0)
                    ->form([
                        Tabs::make('VendaRapida')
                            ->tabs([
                                Tab::make('Registrar Venda Avulsa no Caixa')
                                    ->schema([
                                        TextInput::make('quantity')
                                            ->label('Quantidade Vendida')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->columnSpanFull(),

                                        Select::make('professional_id')
                                            ->label('Barbeiro / Vendedor (Gera Comissão)')
                                            ->placeholder('Venda da própria barbearia (Sem comissão)')
                                            ->options(fn (Product $record) => Professional::where('barbershop_id', $record->barbershop_id)->where('is_active', true)->pluck('name', 'id'))
                                            ->default(fn () => auth()->user()?->isRestrictedBarber() ? auth()->user()->resolveProfessionalId() : null)
                                            ->searchable()
                                            ->helperText(fn (Product $record) => "Comissão configurada no produto: {$record->commission_rate}%")
                                            ->columnSpanFull(),

                                        TextInput::make('client_name')
                                            ->label('Nome do Cliente (Opcional)')
                                            ->placeholder('Ex: Carlos Eduardo')
                                            ->columnSpanFull(),

                                        Select::make('payment_method')
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
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $qty = max(1, (int) ($data['quantity'] ?? 1));
                        $total = (float) $record->price * $qty;
                        $proId = !empty($data['professional_id']) ? (int) $data['professional_id'] : null;

                        $commRate = 0.0;
                        $commAmount = 0.0;
                        $proName = null;

                        if ($proId) {
                            $pro = Professional::find($proId);
                            if ($pro) {
                                $proName = $pro->display_name;
                                // Use product commission rate; fallback to professional's commission rate if product rate is 0
                                $commRate = (float) $record->commission_rate;
                                if ($commRate <= 0) {
                                    $commRate = (float) $pro->commission_rate;
                                }
                                $commAmount = round(($total * $commRate) / 100, 2);
                            }
                        }

                        $record->decrement('stock_quantity', $qty);

                        ProductSale::create([
                            'barbershop_id' => $record->barbershop_id,
                            'product_id' => $record->id,
                            'professional_id' => $proId,
                            'quantity' => $qty,
                            'unit_price' => $record->price,
                            'total_price' => $total,
                            'cost_price' => $record->cost_price,
                            'commission_rate' => $commRate,
                            'commission_amount' => $commAmount,
                            'commission_status' => $proId ? 'pending' : 'none',
                            'payment_method' => $data['payment_method'] ?? 'pix',
                            'client_name' => $data['client_name'] ?? null,
                            'notes' => $proName ? "Venda realizada pelo barbeiro {$proName}" : 'Venda direta balcão',
                        ]);

                        CashMovement::create([
                            'barbershop_id' => $record->barbershop_id,
                            'type' => 'in',
                            'category' => 'Venda de Produto',
                            'description' => "Venda de produto: {$qty}x {$record->name}" . ($proName ? " (Barbeiro: {$proName})" : ''),
                            'amount' => $total,
                            'payment_method' => $data['payment_method'] ?? 'pix',
                            'occurred_at' => now(),
                        ]);

                        $msg = "{$qty}x {$record->name} (R$ " . number_format($total, 2, ',', '.') . ") lançado no Caixa e baixado do estoque.";
                        if ($proName && $commAmount > 0) {
                            $msg .= " Comissão de R$ " . number_format($commAmount, 2, ',', '.') . " ({$commRate}%) gerada para {$proName}.";
                        }

                        Notification::make()
                            ->title('Venda de Produto registrada!')
                            ->body($msg)
                            ->success()
                            ->send();
                    }),

                Action::make('repor_estoque')
                    ->label('+ Estoque')
                    ->icon('heroicon-o-plus-circle')
                    ->color('info')
                    ->form([
                        Tabs::make('ReporEstoque')
                            ->tabs([
                                Tab::make('Entrada de Mercadoria')
                                    ->schema([
                                        TextInput::make('add_quantity')
                                            ->label('Quantidade a Adicionar ao Estoque')
                                            ->numeric()
                                            ->default(12)
                                            ->minValue(1)
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $add = max(1, (int) ($data['add_quantity'] ?? 1));
                        $record->increment('stock_quantity', $add);

                        Notification::make()
                            ->title('Estoque atualizado!')
                            ->body("Adicionadas +{$add} unidades de {$record->name}.")
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
