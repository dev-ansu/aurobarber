<?php

namespace App\Services;

use App\Models\City;

class CityService
{
    public function findByIbgeCode(string $ibgeCode): ?City
    {
        return City::query()
            ->with('state')
            ->where('ibge_code', $ibgeCode)
            ->first();
    }

    public function findByNameAndState(string $cityName, string $stateAcronym): ?City
    {
        return City::query()
            ->with('state')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($cityName))])
            ->whereHas('state', fn ($q) => $q->whereRaw('UPPER(acronym) = ?', [strtoupper(trim($stateAcronym))]))
            ->first();
    }

    public function search(string $search): array
    {
        return City::query()
            ->with('state')
            ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (City $city) => [
                $city->id => "{$city->name} ({$city->state->acronym})",
            ])->toArray();
    }

    public function label(int $id): ?string
    {
        $city = City::query()
            ->with('state')
            ->find($id);

        if (! $city) {
            return null;
        }

        return "{$city->name} ({$city->state->acronym})";
    }
}
