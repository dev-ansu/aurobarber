<?php

use App\Actions\FillAddressFromZipCode;
use App\Models\City;
use App\Models\School;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('fill address from zip code retrieves data from ViaCep and matches database city', function () {
    $state = State::create(['name' => 'São Paulo', 'acronym' => 'SP', 'ibge_code' => 35]);
    $city = City::create(['name' => 'Campinas', 'ibge_code' => '3509502', 'state_id' => $state->id]);

    Http::fake([
        'https://viacep.com.br/ws/13010000/json' => Http::response([
            'cep' => '13010-000',
            'logradouro' => 'Praça Bento Quirino',
            'complemento' => 'Centro',
            'bairro' => 'Centro',
            'localidade' => 'Campinas',
            'uf' => 'SP',
            'ibge' => '3509502',
        ], 200),
    ]);

    $action = app(FillAddressFromZipCode::class);
    $result = $action->execute('13010-000');

    expect($result)->not->toBeNull()
        ->and($result['zip_code'])->toBe('13010-000')
        ->and($result['postal_code'])->toBe('13010-000')
        ->and($result['street'])->toBe('Praça Bento Quirino')
        ->and($result['neighborhood'])->toBe('Centro')
        ->and($result['city'])->toBe('Campinas')
        ->and($result['state'])->toBe('SP')
        ->and($result['city_id'])->toBe($city->id);
});

test('fill address from zip code falls back to name and state match if ibge code is not found', function () {
    $state = State::create(['name' => 'Minas Gerais', 'acronym' => 'MG', 'ibge_code' => 31]);
    $city = City::create(['name' => 'Belo Horizonte', 'ibge_code' => '3106200', 'state_id' => $state->id]);

    Http::fake([
        'https://viacep.com.br/ws/30110000/json' => Http::response([
            'cep' => '30110-000',
            'logradouro' => 'Avenida Afonso Pena',
            'complemento' => '',
            'bairro' => 'Centro',
            'localidade' => 'Belo Horizonte',
            'uf' => 'MG',
            'ibge' => '9999999', // Código IBGE diferente do banco
        ], 200),
    ]);

    $action = app(FillAddressFromZipCode::class);
    $result = $action->execute('30110-000');

    expect($result)->not->toBeNull()
        ->and($result['city'])->toBe('Belo Horizonte')
        ->and($result['state'])->toBe('MG')
        ->and($result['city_id'])->toBe($city->id);
});

test('school model saving automatically resolves city and state if postal_code is provided and city is blank', function () {
    $state = State::create(['name' => 'Paraná', 'acronym' => 'PR', 'ibge_code' => 41]);
    City::create(['name' => 'Curitiba', 'ibge_code' => '4106902', 'state_id' => $state->id]);

    Http::fake([
        'https://viacep.com.br/ws/80010000/json' => Http::response([
            'cep' => '80010-000',
            'logradouro' => 'Rua XV de Novembro',
            'complemento' => '',
            'bairro' => 'Centro',
            'localidade' => 'Curitiba',
            'uf' => 'PR',
            'ibge' => '4106902',
        ], 200),
    ]);

    $school = School::create([
        'legal_name' => 'Escola Curitiba LTDA',
        'trade_name' => 'Escola Curitiba',
        'cnpj' => '11.222.333/0001-44',
        'postal_code' => '80010-000',
    ]);

    expect($school->city)->toBe('Curitiba')
        ->and($school->state)->toBe('PR')
        ->and($school->street)->toBe('Rua XV de Novembro');
});
