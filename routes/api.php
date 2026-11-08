<?php

use App\Http\Controllers\Api\ClientTelemetryController;
use App\Http\Controllers\Api\LicenseVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Aurosoft
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Verificação remota de licença (SaaS / On-Premise) - Suporta GET e POST
    Route::match(['get', 'post'], '/license/verify', [LicenseVerificationController::class, 'verify'])
        ->middleware('throttle:120,1')
        ->name('api.license.verify');

    // Telemetria interna e diagnóstico sob demanda via Tailscale
    Route::match(['get', 'post'], '/internal/telemetry', [ClientTelemetryController::class, 'report'])
        ->middleware('throttle:60,1')
        ->name('api.internal.telemetry');

    // Distribuição de Atualizações Híbridas (SaaS Packages / ZIP)
    Route::match(['get', 'post'], '/saas/updates/check', [\App\Http\Controllers\Api\SystemUpdateApiController::class, 'check'])
        ->middleware('throttle:120,1')
        ->name('api.saas.updates.check');

    Route::get('/saas/updates/download', [\App\Http\Controllers\Api\SystemUpdateApiController::class, 'download'])
        ->middleware('throttle:30,1')
        ->name('api.saas.updates.download');

    // Rastreamento de tráfego e conversão (imune a AdBlockers e 100% LGPD)
    Route::post('/portal/track', function (\Illuminate\Http\Request $request) {
        $scope = (string) $request->input('scope', 'aurosoft_landing');
        $eventType = (string) $request->input('event_type', 'page_view');
        $schoolId = $request->input('school_id') ? (int) $request->input('school_id') : null;
        $formationId = $request->input('formation_id') ? (int) $request->input('formation_id') : null;
        $eventLabel = $request->input('event_label') ? (string) $request->input('event_label') : null;

        \App\Services\PortalTrafficTracker::log(
            $request,
            $scope,
            $eventType,
            $schoolId,
            $formationId,
            $eventLabel
        );

        return response()->json(['success' => true]);
    })->middleware('throttle:300,1')->name('api.portal.track');
});
