<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SaasPaymentReceipts;

use App\Filament\Saas\Resources\SaasPaymentReceipts\Pages\ManageSaasPaymentReceipts;
use App\Models\SaasPaymentReceipt;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SaasPaymentReceiptResource extends Resource
{
    protected static ?string $model = SaasPaymentReceipt::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Recebimentos SaaS';

    protected static ?string $modelLabel = 'recebimento';

    protected static ?string $pluralModelLabel = 'recebimentos SaaS';

    protected static string|UnitEnum|null $navigationGroup = 'Cobrança de Licenças';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('received_on', 'desc')
            ->columns([
                TextColumn::make('barbershop.name')->label('Barbearia')->searchable()->sortable()->weight('bold'),
                TextColumn::make('amount')->label('Valor recebido')->money('BRL')->sortable(),
                TextColumn::make('received_on')->label('Recebido em')->date('d/m/Y')->sortable(),
                TextColumn::make('competence')->label('Competência')->date('m/Y')->sortable(),
                TextColumn::make('recorder.name')->label('Validado por')->placeholder('—'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSaasPaymentReceipts::route('/'),
        ];
    }
}
