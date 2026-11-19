<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\LandingLeadMail;
use App\Models\LandingLead;
use App\Models\SaasPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LandingLeadService
{
    /**
     * Registra o lead comercial e dispara a notificação por e-mail para o administrador.
     *
     * @param  array<string, mixed>  $data
     */
    public function capture(array $data, ?string $ip = null): ?LandingLead
    {
        // 1. Armadilha Honeypot anti-spam
        if (! empty($data['website_url'])) {
            return null;
        }

        // 2. Normalização do telefone para formato amigável
        $phone = (string) ($data['phone'] ?? '');
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 11) {
            $formattedPhone = '('.substr($digits, 0, 2).') '.substr($digits, 2, 5).'-'.substr($digits, 7);
        } elseif (strlen($digits) === 10) {
            $formattedPhone = '('.substr($digits, 0, 2).') '.substr($digits, 2, 4).'-'.substr($digits, 6);
        } else {
            $formattedPhone = $phone;
        }

        // 3. Resolução do plano selecionado
        $selectedPlanName = $data['selected_plan'] ?? null;
        $selectedPlanId = ! empty($data['selected_plan_id']) ? (int) $data['selected_plan_id'] : null;

        if ($selectedPlanId && empty($selectedPlanName)) {
            $plan = SaasPlan::find($selectedPlanId);
            if ($plan) {
                $selectedPlanName = $plan->name;
            }
        }

        // 4. Persistência do lead no banco de dados
        $lead = LandingLead::create([
            'name' => trim((string) ($data['name'] ?? '')),
            'barbershop_name' => trim((string) ($data['barbershop_name'] ?? '')),
            'phone' => $formattedPhone,
            'email' => strtolower(trim((string) ($data['email'] ?? ''))),
            'city' => ! empty($data['city']) ? trim((string) $data['city']) : null,
            'chairs_count' => $data['chairs_count'] ?? null,
            'selected_plan' => $selectedPlanName,
            'selected_plan_id' => $selectedPlanId,
            'message' => ! empty($data['message']) ? trim((string) $data['message']) : null,
            'status' => 'novo',
            'ip' => $ip,
        ]);

        // 5. Notificação por e-mail ao administrador (Resend SMTP)
        $this->notifyAdmin($lead);

        return $lead;
    }

    /**
     * Envia e-mail formatado para a administração sem quebrar a requisição em caso de instabilidade.
     */
    protected function notifyAdmin(LandingLead $lead): void
    {
        $adminEmail = env('ADMIN_NOTIFICATION_EMAIL', 'dev.andersondesouzafeitosa@gmail.com');

        try {
            Mail::to($adminEmail)->send(new LandingLeadMail($lead));
        } catch (\Throwable $e) {
            Log::error('Erro ao enviar e-mail de alerta do lead AuroBarber: '.$e->getMessage(), [
                'exception' => $e,
                'lead_id' => $lead->id,
            ]);
        }
    }
}
