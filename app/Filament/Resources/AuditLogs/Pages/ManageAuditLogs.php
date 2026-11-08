<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\School;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageAuditLogs extends ManageRecords
{
    protected static string $resource = AuditLogResource::class;

    public ?string $activeTab = 'contracts';

    public function getTabs(): array
    {
        return [
            'contracts' => Tab::make('Contratos')
                ->icon('heroicon-m-document-text')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where(fn (Builder $query): Builder => $query
                        ->whereNotNull('contract_id')
                        ->orWhere('auditable_type', Contract::class))
                    ->whereNotIn('auditable_type', [
                        Installment::class,
                        CashTransaction::class,
                        CashRegister::class,
                        \App\Models\Person::class,
                    ])),
            'finance' => Tab::make('Financeiro')
                ->icon('heroicon-m-banknotes')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('auditable_type', [
                        Installment::class,
                        CashTransaction::class,
                        CashRegister::class,
                    ])),
            'people' => Tab::make('Pessoas')
                ->icon('heroicon-m-user-group')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('auditable_type', \App\Models\Person::class)),
        ];
    }

    public function getSubheading(): ?string
    {
        $school = (class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant() : null)
            ?? School::getActiveSchool()
            ?? School::query()->first();

        if (! $school) {
            return 'Cadastre os dados da escola para incluí-los também nas exportações.';
        }

        return implode(' · ', array_filter([
            $school->trade_name ?: $school->legal_name,
            $school->cnpj ? "CNPJ: {$school->cnpj}" : null,
        ]));
    }
}
