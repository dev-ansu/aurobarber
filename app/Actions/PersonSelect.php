<?php

namespace App\Actions;

use App\Models\Person;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;

class PersonSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable();

        $this->getSearchResultsUsing(
            function (string $search): array {
                $tenantId = Filament::getTenant()?->id;

                return Person::query()
                    ->when($tenantId, function ($q) use ($tenantId): void {
                        $q->where(function ($sub) use ($tenantId): void {
                            $sub->whereHas('schools', fn ($sq) => $sq->where('schools.id', $tenantId))
                                ->orWhereHas('contracts', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $tenantId))
                                ->orWhereHas('responsibles.contracts', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $tenantId));
                        });
                    })
                    ->where(function ($q) use ($search): void {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhereCpf($search);
                    })
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn (Person $person): array => [
                        $person->id => $person->cpf ? "{$person->name} - {$person->cpf}" : $person->name,
                    ])
                    ->toArray();
            }
        );

        $this->hidden(fn (string $operation): bool => $operation == 'edit');

        $this->getOptionLabelUsing(
            function ($value): ?string {
                $person = Person::find($value);

                return $person ? "{$person->name} - {$person->cpf}" : null;
            }
        );
    }
}
