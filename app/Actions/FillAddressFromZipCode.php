<?php

namespace App\Actions;

use App\Services\CityService;
use App\Services\ViaCepService;

class FillAddressFromZipCode
{
    public function __construct(
        private ViaCepService $viaCep,
        private CityService $cityService,
    ) {}

    public function execute(string $zipCode): ?array
    {
        $address = $this->viaCep->search($zipCode);

        if (! $address) {
            return null;
        }

        $city = null;
        if (! empty($address['ibge_code'])) {
            $city = $this->cityService->findByIbgeCode($address['ibge_code']);
        }

        if (! $city && ! empty($address['city']) && ! empty($address['state'])) {
            $city = $this->cityService->findByNameAndState($address['city'], $address['state']);
        }

        $cityName = $city?->name ?? $address['city'] ?? null;
        $stateAcronym = $city?->state?->acronym ?? $address['state'] ?? null;

        return [
            'zip_code' => $address['zip_code'],
            'postal_code' => $address['zip_code'],
            'street' => $address['street'],
            'neighborhood' => $address['district'],
            'city_id' => $city?->id,
            'city' => $cityName,
            'state' => $stateAcronym,
            'complement' => $address['complement'],
        ];
    }
}
