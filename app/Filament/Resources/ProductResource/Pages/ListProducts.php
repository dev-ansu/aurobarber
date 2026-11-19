<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Pages\ProductPosPage;
use App\Filament\Resources\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openPos')
                ->label('Vender produtos (PDV)')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->url(fn (): string => ProductPosPage::getUrl())
                ->visible(fn (): bool => ProductPosPage::canAccess()),
            CreateAction::make()
                ->label('Novo Produto / Bebida')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function registerProductSaleAction(): Action
    {
        return ProductResource::registerSaleAction();
    }
}
