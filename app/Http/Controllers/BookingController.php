<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Professional;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Exibe a página pública da barbearia / filial.
     */
    public function show(string $slug): View
    {
        $barbershop = Barbershop::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'professionals' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
                'services' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
            ])
            ->firstOrFail();

        return view('barbershop-public', compact('barbershop'));
    }

    /**
     * Retorna os horários disponíveis (slots) para uma data, serviço e profissional.
     */
    public function getAvailableSlots(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'professional_id' => 'nullable|string',
        ]);

        $barbershop = Barbershop::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $service = Service::where('id', $request->service_id)->where('barbershop_id', $barbershop->id)->firstOrFail();

        $date = $request->date;
        $professionalId = $request->professional_id && $request->professional_id !== 'any'
            ? (int) $request->professional_id
            : null;

        // Profissionais aptos
        $professionals = $barbershop->professionals()->where('is_active', true);
        if ($professionalId) {
            $professionals->where('id', $professionalId);
        }
        $professionals = $professionals->get();

        if ($professionals->isEmpty()) {
            return response()->json(['slots' => []]);
        }

        // Horário de funcionamento base (08:00 às 19:30)
        $startTime = Carbon::createFromFormat('Y-m-d H:i', "{$date} 08:00");
        $endTime = Carbon::createFromFormat('Y-m-d H:i', "{$date} 19:30");
        $stepMinutes = 30; // Intervalos de 30 minutos

        $now = Carbon::now();
        $slots = [];

        $currentTime = $startTime->copy();

        while ($currentTime->lt($endTime)) {
            $slotStart = $currentTime->copy();
            $slotEnd = $slotStart->copy()->addMinutes($service->duration_minutes);

            // Não exibe horários que já passaram para o dia de hoje
            if ($slotStart->isPast()) {
                $currentTime->addMinutes($stepMinutes);
                continue;
            }

            // Verifica se pelo menos um profissional está livre neste slot
            $availableProf = null;

            foreach ($professionals as $prof) {
                $hasConflict = Appointment::where('professional_id', $prof->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('scheduled_at', $date)
                    ->where(function ($query) use ($slotStart, $slotEnd) {
                        $query->where(function ($q) use ($slotStart, $slotEnd) {
                            $q->where('scheduled_at', '<', $slotEnd)
                              ->where('ends_at', '>', $slotStart);
                        });
                    })
                    ->exists();

                if (!$hasConflict) {
                    $availableProf = $prof;
                    break;
                }
            }

            if ($availableProf !== null) {
                $slots[] = [
                    'time' => $slotStart->format('H:i'),
                    'professional_id' => $professionalId ? $availableProf->id : null,
                    'professional_name' => $availableProf->display_name,
                ];
            }

            $currentTime->addMinutes($stepMinutes);
        }

        return response()->json([
            'date' => $date,
            'slots' => $slots,
            'service_duration' => $service->duration_minutes,
        ]);
    }

    /**
     * Grava o agendamento no banco de dados e retorna link do WhatsApp para confirmação.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
            'professional_id' => 'nullable|string',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'client_name' => 'required|string|min:2|max:255',
            'client_phone' => 'required|string|min:8|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $barbershop = Barbershop::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $service = Service::where('id', $validated['service_id'])->where('barbershop_id', $barbershop->id)->firstOrFail();

        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', "{$validated['date']} {$validated['time']}");
        $endsAt = $scheduledAt->copy()->addMinutes($service->duration_minutes);

        // Bloqueio de horários passados
        if ($scheduledAt->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Este horário já passou. Por favor, selecione outro horário.',
            ], 422);
        }

        return DB::transaction(function () use ($barbershop, $service, $validated, $scheduledAt, $endsAt) {
            $professional = null;

            // Se escolheu um barbeiro específico
            if (!empty($validated['professional_id']) && $validated['professional_id'] !== 'any') {
                $profCandidate = Professional::where('id', $validated['professional_id'])
                    ->where('barbershop_id', $barbershop->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Verifica conflito
                $conflict = Appointment::where('professional_id', $profCandidate->id)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($scheduledAt, $endsAt) {
                        $query->where('scheduled_at', '<', $endsAt)
                              ->where('ends_at', '>', $scheduledAt);
                    })
                    ->exists();

                if ($conflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este horário acabou de ser reservado por outro cliente. Por favor, escolha outro horário.',
                    ], 422);
                }

                $professional = $profCandidate;
            } else {
                // Se escolheu "Qualquer Barbeiro", seleciona o primeiro livre
                $candidates = Professional::where('barbershop_id', $barbershop->id)
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->lockForUpdate()
                    ->get();

                foreach ($candidates as $candidate) {
                    $conflict = Appointment::where('professional_id', $candidate->id)
                        ->where('status', '!=', 'cancelled')
                        ->where(function ($query) use ($scheduledAt, $endsAt) {
                            $query->where('scheduled_at', '<', $endsAt)
                                  ->where('ends_at', '>', $scheduledAt);
                        })
                        ->exists();

                    if (!$conflict) {
                        $professional = $candidate;
                        break;
                    }
                }

                if (!$professional) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Todos os barbeiros estão ocupados neste horário. Por favor, escolha outro horário.',
                    ], 422);
                }
            }

            // Cálculo das comissões
            $commissionRate = $professional->commission_rate ?: 50.00;
            $commissionAmount = round(($service->price * $commissionRate) / 100, 2);

            // Grava o agendamento real no banco
            $appointment = Appointment::create([
                'barbershop_id' => $barbershop->id,
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'client_name' => trim($validated['client_name']),
                'client_phone' => trim($validated['client_phone']),
                'scheduled_at' => $scheduledAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed',
                'service_price' => $service->price,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'payment_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Formatação do link para WhatsApp
            $dateFormatted = $scheduledAt->format('d/m/Y');
            $timeFormatted = $scheduledAt->format('H:i');
            $priceFormatted = 'R$ ' . number_format($service->price, 2, ',', '.');

            $msg = "Olá! Acabei de agendar um horário na *{$barbershop->name}*!\n\n"
                 . "🔖 *Protocolo:* #{$appointment->id}\n"
                 . "✂️ *Serviço:* {$service->name} ({$priceFormatted})\n"
                 . "💈 *Barbeiro:* {$professional->display_name}\n"
                 . "📅 *Data:* {$dateFormatted}\n"
                 . "⏰ *Horário:* {$timeFormatted}\n"
                 . "👤 *Cliente:* {$appointment->client_name} ({$appointment->client_phone})";

            $whatsappUrl = null;
            if ($barbershop->formatted_whatsapp) {
                $whatsappUrl = 'https://api.whatsapp.com/send?phone=' . $barbershop->formatted_whatsapp . '&text=' . urlencode($msg);
            }

            return response()->json([
                'success' => true,
                'message' => 'Agendamento confirmado com sucesso!',
                'appointment' => [
                    'id' => $appointment->id,
                    'client_name' => $appointment->client_name,
                    'professional_name' => $professional->display_name,
                    'service_name' => $service->name,
                    'date' => $dateFormatted,
                    'time' => $timeFormatted,
                    'price' => $priceFormatted,
                ],
                'whatsapp_url' => $whatsappUrl,
            ]);
        });
    }
}
