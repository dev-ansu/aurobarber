<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SaasWebhookController;
use App\Http\Middleware\VerifySaasWebhookSecret;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de Integração com o Painel SaaS Master (Aurosoft)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/saas')
    ->middleware([VerifySaasWebhookSecret::class])
    ->group(function () {
        Route::post('/license-webhook', [SaasWebhookController::class, 'handle'])->name('saas.webhook');
    });
