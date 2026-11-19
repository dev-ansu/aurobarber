<?php

use App\Rules\Turnstile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('turnstile allows validation when secret is missing in local or testing environment', function () {
    config()->set('services.turnstile.secret_key', null);

    $validator = Validator::make(
        ['token' => 'any-token'],
        ['token' => [new Turnstile()]]
    );

    expect($validator->passes())->toBeTrue();
});

test('turnstile fails when token is empty and secret is configured', function () {
    config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');

    $validator = Validator::make(
        ['token' => ''],
        ['token' => [new Turnstile()]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('token'))->toContain('A verificação de segurança é obrigatória.');
});

test('turnstile passes when cloudflare api returns success', function () {
    config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
            'action' => 'booking',
        ], 200),
    ]);

    $validator = Validator::make(
        ['token' => 'valid-turnstile-token'],
        ['token' => [new Turnstile('booking')]]
    );

    expect($validator->passes())->toBeTrue();
});

test('turnstile fails when cloudflare api rejects token', function () {
    config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ], 200),
    ]);

    $validator = Validator::make(
        ['token' => 'invalid-token'],
        ['token' => [new Turnstile()]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('token'))->toContain('A verificação de segurança falhou');
});
