<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LandingLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingLeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'barbershop_name' => 'required|string|max:160',
            'phone' => 'required|string|max:40',
            'email' => 'required|email|max:160',
            'city' => 'nullable|string|max:100',
            'chairs_count' => 'nullable|string|max:60',
            'selected_plan' => 'nullable|string|max:100',
            'selected_plan_id' => 'nullable|integer|exists:saas_plans,id',
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            $lead = LandingLead::create([
                'name' => $validated['name'],
                'barbershop_name' => $validated['barbershop_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'city' => $validated['city'] ?? null,
                'chairs_count' => $validated['chairs_count'] ?? null,
                'selected_plan' => $validated['selected_plan'] ?? null,
                'selected_plan_id' => $validated['selected_plan_id'] ?? null,
                'message' => $validated['message'] ?? null,
                'status' => 'novo',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitação registrada com sucesso! Entraremos em contato via WhatsApp.',
                'lead_id' => $lead->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar lead da landing page: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro interno ao registrar sua solicitação. Por favor, fale conosco no WhatsApp.',
            ], 500);
        }
    }
}
