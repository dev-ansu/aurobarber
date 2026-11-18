<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Professional;
use App\Services\CheckoutService;
use App\Services\PaymentBreakdown;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
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

    public static function registerSaleAction(): Action
    {
        return Action::make('registerProductSale')
            ->label('Vender produtos')
            ->icon('heroicon-o-shopping-bag')
            ->color('success')
            ->modalHeading('Registrar venda de produtos')
            ->modalSubmitActionLabel('Registrar venda')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                TextInput::make('client_name')
                    ->label('Cliente (opcional)')
                    ->maxLength(255),
                Select::make('product_ids')
                    ->label('Produtos vendidos')
                    ->placeholder('Selecione um ou mais produtos')
                    ->options(fn (): array => Product::query()
                        ->where('barbershop_id', filament()->getTenant()?->id ?? 0)
                        ->where('is_active', true)
                        ->where('stock_quantity', '>', 0)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Product $product): array => [
                            $product->id => "{$product->name} — R$ ".number_format((float) $product->price, 2, ',', '.')." ({$product->stock_quantity} em estoque)",
                        ])
                        ->all())
                    ->multiple()
                    ->searchable()
                    ->live()
                    ->dynamicOptions(false)
                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                        $quantities = (array) $get('quantities');
                        $selectedIds = collect((array) $state)->map(fn ($id): int => (int) $id)->filter()->all();
                        foreach ($selectedIds as $id) {
                            if (! isset($quantities[$id]) || $quantities[$id] === '' || (int) $quantities[$id] < 1) {
                                $quantities[$id] = 1;
                            }
                        }
                        $set('quantities', $quantities);
                    })
                    ->required()
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema(function (Get $get): array {
                        $productIds = collect((array) $get('product_ids'))
                            ->map(fn ($id): int => (int) $id)
                            ->filter()
                            ->unique()
                            ->values();

                        if ($productIds->isEmpty()) {
                            return [];
                        }

                        $products = Product::query()
                            ->where('barbershop_id', filament()->getTenant()?->id ?? 0)
                            ->whereIn('id', $productIds)
                            ->get()
                            ->keyBy('id');

                        $fields = [];
                        foreach ($productIds as $id) {
                            $product = $products->get($id);
                            if (! $product) {
                                continue;
                            }

                            $fields[] = TextInput::make("quantities.{$id}")
                                ->label("Qtd: {$product->name}")
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->default(1)
                                ->formatStateUsing(fn ($state): int => max(1, (int) ($state ?? 1)))
                                ->live(onBlur: true);
                        }

                        return $fields;
                    })
                    ->columnSpanFull(),
                Placeholder::make('sale_total')
                    ->label('Total da venda')
                    ->content(function (Get $get): string {
                        $productIds = collect((array) $get('product_ids'))
                            ->map(fn ($id): int => (int) $id)
                            ->filter()
                            ->unique()
                            ->values();

                        if ($productIds->isEmpty()) {
                            return 'R$ 0,00';
                        }

                        $quantities = (array) $get('quantities');
                        $products = Product::query()
                            ->where('barbershop_id', filament()->getTenant()?->id ?? 0)
                            ->whereIn('id', $productIds)
                            ->get()
                            ->keyBy('id');

                        $total = 0.0;
                        foreach ($productIds as $id) {
                            $product = $products->get($id);
                            if (! $product) {
                                continue;
                            }
                            $qty = max(1, (int) ($quantities[$id] ?? 1));
                            $total += (float) $product->price * $qty;
                        }

                        return 'R$ '.number_format($total, 2, ',', '.');
                    })
                    ->columnSpanFull(),
                Section::make('Pagamento')
                    ->columns(2)
                    ->schema([
                        Select::make('payment_method')
                            ->label('Forma principal')
                            ->options([
                                'pix' => 'PIX',
                                'dinheiro' => 'Dinheiro',
                                'debito' => 'Cartão de débito',
                                'credito' => 'Cartão de crédito',
                            ])
                            ->default('pix')
                            ->required(),
                        Placeholder::make('payment_help')
                            ->label('Pagamento dividido')
                            ->content('Preencha os valores usados nas outras formas. O restante fica na forma principal.'),
                        TextInput::make('additional_pix_amount')
                            ->label('PIX adicional (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('additional_dinheiro_amount')
                            ->label('Dinheiro adicional (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('additional_debito_amount')
                            ->label('Cartão de débito adicional (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('additional_credito_amount')
                            ->label('Cartão de crédito adicional (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $tenant = filament()->getTenant();
                if (! $tenant) {
                    return;
                }

                app(CheckoutService::class)->registerStandaloneSale(
                    barbershop: $tenant,
                    productIds: (array) ($data['product_ids'] ?? []),
                    quantities: (array) ($data['quantities'] ?? []),
                    primaryPaymentMethod: (string) ($data['payment_method'] ?? 'pix'),
                    additionalAmounts: [
                        'pix' => $data['additional_pix_amount'] ?? 0,
                        'dinheiro' => $data['additional_dinheiro_amount'] ?? 0,
                        'debito' => $data['additional_debito_amount'] ?? 0,
                        'credito' => $data['additional_credito_amount'] ?? 0,
                    ],
                    clientName: $data['client_name'] ?? null,
                );

                Notification::make()
                    ->title('Venda registrada e estoque atualizado.')
                    ->success()
                    ->send();
            });
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
                                                'credito' => 'Cartão de Crédito',
                                                'debito' => 'Cartão de Débito',
                                            ])
                                            ->default('pix')
                                            ->required()
                                            ->columnSpanFull(),

                                        Section::make('Dividir pagamento entre outras formas')
                                            ->description('Informe valores complementares. O saldo restante será recebido pela forma principal selecionada acima.')
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('additional_pix_amount')
                                                    ->label('PIX adicional (R$)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0),
                                                TextInput::make('additional_dinheiro_amount')
                                                    ->label('Dinheiro adicional (R$)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0),
                                                TextInput::make('additional_debito_amount')
                                                    ->label('Cartão de débito adicional (R$)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0),
                                                TextInput::make('additional_credito_amount')
                                                    ->label('Cartão de crédito adicional (R$)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $qty = max(1, (int) ($data['quantity'] ?? 1));
                        $total = (float) $record->price * $qty;
                        $paymentDetails = PaymentBreakdown::fromAdditionalAmounts(
                            $total,
                            (string) ($data['payment_method'] ?? 'pix'),
                            [
                                'pix' => $data['additional_pix_amount'] ?? 0,
                                'dinheiro' => $data['additional_dinheiro_amount'] ?? 0,
                                'debito' => $data['additional_debito_amount'] ?? 0,
                                'credito' => $data['additional_credito_amount'] ?? 0,
                            ],
                        );
                        $proId = ! empty($data['professional_id']) ? (int) $data['professional_id'] : null;

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
                            'payment_method' => PaymentBreakdown::summaryMethod($paymentDetails),
                            'payment_details' => $paymentDetails,
                            'client_name' => $data['client_name'] ?? null,
                            'notes' => $proName ? "Venda realizada pelo barbeiro {$proName}" : 'Venda direta balcão',
                        ]);

                        CashMovement::create([
                            'barbershop_id' => $record->barbershop_id,
                            'user_id' => auth()->id(),
                            'type' => 'inflow',
                            'description' => "Venda de produto: {$qty}x {$record->name}".($proName ? " (Barbeiro: {$proName})" : ''),
                            'amount' => $total,
                            'date' => now()->toDateString(),
                        ]);

                        $msg = "{$qty}x {$record->name} (R$ ".number_format($total, 2, ',', '.').') lançado no Caixa e baixado do estoque.';
                        if ($proName && $commAmount > 0) {
                            $msg .= ' Comissão de R$ '.number_format($commAmount, 2, ',', '.')." ({$commRate}%) gerada para {$proName}.";
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
