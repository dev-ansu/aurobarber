<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ViaCepService
{
    private const BASE_URL = 'https://viacep.com.br/ws';

    public function search(string $zipCode): ?array
    {

        try {

            $zipCode = preg_replace('/\D/', '', $zipCode);

            if (strlen($zipCode) !== 8) {
                return null;
            }

            $response = Http::get(
                sprintf('%s/%s/json', self::BASE_URL, $zipCode)
            )->throw();

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (isset($data['erro'])) {
                return null;
            }

            return [
                'zip_code' => $data['cep'],
                'street' => $data['logradouro'],
                'district' => $data['bairro'],
                'city' => $data['localidade'],
                'state' => $data['uf'],
                'ibge_code' => $data['ibge'],
                'complement' => $data['complemento'],
            ];

        } catch (RequestException $e) {
            report($e);

            return null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }

    }
}
