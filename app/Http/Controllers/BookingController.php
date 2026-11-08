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
     * Retorna os horários disponíveis (slots) para uma data, serviço(s) e profissional.
     */
    public function getAvailableSlots(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'service_id' => 'nullable|integer',
            'service_ids' => 'nullable|array',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'professional_id' => 'nullable|string',
        ]);

        $barbershop = Barbershop::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Extrai a lista de serviços solicitados
        $serviceIds = (array) ($request->service_ids ?: ($request->service_id ? [$request->service_id] : []));
        if (empty($serviceIds)) {
            return response()->json(['slots' => [], 'message' => 'Nenhum serviço selecionado.'], 422);
        }

        $services = Service::whereIn('id', $serviceIds)->where('barbershop_id', $barbershop->id)->get();
        if ($services->isEmpty()) {
            return response()->json(['slots' => []]);
        }

        $totalDuration = $services->sum('duration_minutes') ?: 30;
        $date = $request->date;
        $professionalId = $request->professional_id && $request->professional_id !== 'any'
            ? (int) $request->professional_id
            : null;

        // Profissionais ativos
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
        $stepMinutes = 30;

        $slots = [];
        $currentTime = $startTime->copy();

        while ($currentTime->lt($endTime)) {
            $slotStart = $currentTime->copy();
            $slotEnd = $slotStart->copy()->addMinutes($totalDuration);

            // Não exibe horários passados para hoje
            if ($slotStart->isPast()) {
                $currentTime->addMinutes($stepMinutes);
                continue;
            }

            // Verifica se pelo menos um profissional está livre durante todo o intervalo do corte
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
            'total_duration' => $totalDuration,
            'services_count' => $services->count(),
        ]);
    }

    /**
     * Grava o agendamento no banco de dados e retorna link do WhatsApp para confirmação.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'nullable|integer',
            'service_ids' => 'nullable|array',
            'professional_id' => 'nullable|string',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'client_name' => 'required|string|min:2|max:255',
            'client_phone' => 'required|string|min:8|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $barbershop = Barbershop::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Extrai serviços
        $serviceIds = (array) ($validated['service_ids'] ?? ($validated['service_id'] ? [$validated['service_id']] : []));
        if (empty($serviceIds)) {
            return response()->json(['success' => false, 'message' => 'Selecione pelo menos um serviço.'], 422);
        }

        $services = Service::whereIn('id', $serviceIds)->where('barbershop_id', $barbershop->id)->get();
        if ($services->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Serviços não encontrados.'], 422);
        }

        $totalDuration = $services->sum('duration_minutes') ?: 30;
        $totalPrice = $services->sum('price');
        $primaryService = $services->first();

        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', "{$validated['date']} {$validated['time']}");
        $endsAt = $scheduledAt->copy()->addMinutes($totalDuration);

        // Bloqueio de horários passados
        if ($scheduledAt->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Este horário já passou. Por favor, selecione outro horário.',
            ], 422);
        }

        return DB::transaction(function () use ($barbershop, $services, $primaryService, $totalPrice, $totalDuration, $validated, $scheduledAt, $endsAt) {
            $professional = null;

            // Se escolheu um barbeiro específico
            if (!empty($validated['professional_id']) && $validated['professional_id'] !== 'any') {
                $profCandidate = Professional::where('id', $validated['professional_id'])
                    ->where('barbershop_id', $barbershop->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Verifica conflito para a duração total dos serviços combinados
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
                // Se escolheu "Primeiro Disponível", seleciona o primeiro barbeiro livre
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
                        'message' => 'Todos os barbeiros estão ocupados neste horário para a duração dos serviços escolhidos. Por favor, escolha outro horário.',
                    ], 422);
                }
            }

            // Cálculo das comissões com base no valor total
            $commissionRate = $professional->commission_rate ?: 50.00;
            $commissionAmount = round(($totalPrice * $commissionRate) / 100, 2);

            // Grava o agendamento
            $appointment = Appointment::create([
                'barbershop_id' => $barbershop->id,
                'professional_id' => $professional->id,
                'service_id' => $primaryService->id,
                'client_name' => trim($validated['client_name']),
                'client_phone' => trim($validated['client_phone']),
                'scheduled_at' => $scheduledAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed',
                'service_price' => $totalPrice,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'payment_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Vincula todos os serviços na tabela pivô appointment_services
            $syncData = [];
            foreach ($services as $srv) {
                $syncData[$srv->id] = [
                    'price' => $srv->price,
                    'duration_minutes' => $srv->duration_minutes,
                ];
            }
            $appointment->services()->sync($syncData);

            // Formatação da mensagem para o WhatsApp
            $dateFormatted = $scheduledAt->format('d/m/Y');
            $timeFormatted = $scheduledAt->format('H:i');
            $priceFormatted = 'R$ ' . number_format($totalPrice, 2, ',', '.');

            $servicesLines = [];
            foreach ($services as $s) {
                $servicesLines[] = "• {$s->name} (R$ " . number_format($s->price, 2, ',', '.') . " - {$s->duration_minutes} min)";
            }
            $servicesText = implode("\n", $servicesLines);

            $msg = "Olá! Acabei de agendar um horário na *{$barbershop->name}*!\n\n"
                 . "🔖 *Protocolo:* #{$appointment->id}\n"
                 . "✂️ *Serviço(s):*\n{$servicesText}\n"
                 . "💈 *Barbeiro:* {$professional->display_name}\n"
                 . "📅 *Data:* {$dateFormatted}\n"
                 . "⏰ *Horário:* {$timeFormatted} (Duração: {$totalDuration} min)\n"
                 . "💰 *Total:* {$priceFormatted}\n"
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
                    'services_summary' => $services->pluck('name')->join(' + '),
                    'services_count' => $services->count(),
                    'total_duration' => $totalDuration,
                    'date' => $dateFormatted,
                    'time' => $timeFormatted,
                    'price' => $priceFormatted,
                ],
                'whatsapp_url' => $whatsappUrl,
            ]);
        });
    }
}
