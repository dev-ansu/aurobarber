<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLandingLeadRequest;
use App\Services\LandingLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LandingLeadController extends Controller
{
    public function __construct(
        protected LandingLeadService $leadService
    ) {}

    public function store(StoreLandingLeadRequest $request): JsonResponse
    {
        try {
            $lead = $this->leadService->capture(
                data: $request->validated(),
                ip: $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => 'Solicitação registrada com sucesso! Entraremos em contato em breve.',
                'lead_id' => $lead?->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao registrar lead da landing page: '.$e->getMessage(), [
                'exception' => $e,
                'payload' => $request->except(['website_url', 'turnstile_token']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro interno ao registrar sua solicitação. Por favor, tente novamente.',
            ], 500);
        }
    }
}
