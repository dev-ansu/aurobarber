<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\LandingLeadMail;
use App\Models\LandingLead;
use App\Models\SaasPlan;
use App\Rules\Turnstile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LandingLeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // 1. Armadilha Honeypot anti-spam
        if (! empty($request->input('website_url'))) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação registrada com sucesso! Entraremos em contato em breve.',
            ]);
        }

        $turnstileToken = $request->input('turnstile_token') ?? $request->input('cf-turnstile-response');
        $request->merge(['turnstile_token' => $turnstileToken]);

        $validated = $request->validate([
            'turnstile_token' => [
                config('services.turnstile.site_key') ? 'required' : 'nullable',
                new Turnstile('landing_lead'),
            ],
            'name' => 'required|string|max:120',
            'barbershop_name' => 'required|string|max:160',
            'phone' => 'required|string|max:40',
            'email' => 'required|email|max:160',
            'city' => 'nullable|string|max:100',
            'chairs_count' => 'nullable|string|max:60',
            'selected_plan' => 'nullable|string|max:100',
            'selected_plan_id' => 'nullable|integer|exists:saas_plans,id',
            'message' => 'nullable|string|max:1000',
        ], [
            'turnstile_token.required' => 'A verificação de segurança é obrigatória. Por favor, complete o desafio.',
        ]);

        // Formatação amigável do telefone (caso venham apenas números)
        $digits = preg_replace('/\D/', '', (string) $validated['phone']);
        if (strlen($digits) === 11) {
            $formattedPhone = '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
        } elseif (strlen($digits) === 10) {
            $formattedPhone = '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6);
        } else {
            $formattedPhone = $validated['phone'];
        }

        // Se informou selected_plan_id mas não passou selected_plan, recupera o nome
        $selectedPlanName = $validated['selected_plan'] ?? null;
        if (! empty($validated['selected_plan_id']) && empty($selectedPlanName)) {
            $plan = SaasPlan::find($validated['selected_plan_id']);
            if ($plan) {
                $selectedPlanName = $plan->name;
            }
        }

        try {
            // 2. Salva no banco de dados para consulta no Painel SaaS Master
            $lead = LandingLead::create([
                'name' => $validated['name'],
                'barbershop_name' => $validated['barbershop_name'],
                'phone' => $formattedPhone,
                'email' => strtolower(trim($validated['email'])),
                'city' => $validated['city'] ?? null,
                'chairs_count' => $validated['chairs_count'] ?? null,
                'selected_plan' => $selectedPlanName,
                'selected_plan_id' => $validated['selected_plan_id'] ?? null,
                'message' => $validated['message'] ?? null,
                'status' => 'novo',
                'ip' => $request->ip(),
            ]);

            // 3. Dispara e-mail de alerta para o administrador (idêntico ao Aurosoft)
            $adminEmail = env('ADMIN_NOTIFICATION_EMAIL', 'dev.andersondesouzafeitosa@gmail.com');

            try {
                Mail::to($adminEmail)->send(new LandingLeadMail($lead));
            } catch (\Throwable $mailError) {
                Log::error('Erro ao enviar e-mail de notificação de lead AuroBarber: ' . $mailError->getMessage(), [
                    'exception' => $mailError,
                    'lead_id' => $lead->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Solicitação registrada com sucesso! Entraremos em contato em breve.',
                'lead_id' => $lead->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar lead da landing page: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro interno ao registrar sua solicitação. Por favor, tente novamente.',
            ], 500);
        }
    }
}
