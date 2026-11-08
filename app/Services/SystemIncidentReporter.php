<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemIncidentReporter
{
    /**
     * Intercepta e registra uma exceção como um Incidente de Sistema automático
     */
    public function report(Throwable $e): ?SupportTicket
    {
        try {
            // Ignora exceções esperadas de validação e 404
            if ($e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException ||
                $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            $exceptionClass = get_class($e);
            $errorMessage = $e->getMessage() ?: 'Erro de processamento não especificado.';
            $file = $e->getFile();
            $line = $e->getLine();
            $fileLine = basename($file) . ":{$line}";

            // Gera hash único baseado na classe de erro, arquivo e linha para agrupar ocorrências
            $errorHash = md5("{$exceptionClass}|{$file}|{$line}");

            $request = request();
            $requestUrl = $request ? $request->fullUrl() : 'CLI / Background';
            $requestMethod = $request ? $request->method() : 'CLI';

            // Sanitiza parâmetros sensíveis (senhas, tokens)
            $payload = [];
            if ($request) {
                $payload = $request->except(['password', 'password_confirmation', 'token', '_token']);
            }

            // Identifica o contexto da escola e usuário
            $school = null;
            if (class_exists(\Filament\Facades\Filament::class)) {
                try {
                    $school = \Filament\Facades\Filament::getTenant();
                } catch (Throwable) {
                    // Ignore
                }
            }

            if (! $school instanceof School) {
                $school = School::getActiveSchool() ?? School::getHeadquarters();
            }

            $user = Auth::user();

            // Verifica se já existe um incidente aberto para esse mesmo erro
            $existing = SupportTicket::query()
                ->where('type', 'system_incident')
                ->where('error_hash', $errorHash)
                ->whereIn('status', ['open', 'in_progress'])
                ->latest('updated_at')
                ->first();

            if ($existing) {
                $existing->increment('occurrences_count');
                $existing->update([
                    'last_occurred_at' => now(),
                    'request_url' => $requestUrl,
                    'request_payload' => $payload,
                ]);

                return $existing;
            }

            // Cria um novo chamado de incidente
            $subject = "Falha Técnica: {$exceptionClass} em {$fileLine}";
            $description = "Ocorrência capturada automaticamente pelo monitor de telemetria.\nMensagem: {$errorMessage}\nURL: {$requestUrl}";

            $createdTicket = SupportTicket::create([
                'type' => 'system_incident',
                'category' => 'sistema_bug',
                'priority' => 'critical',
                'status' => 'open',
                'school_id' => $school?->id,
                'opened_by_user_id' => $user?->id,
                'subject' => $subject,
                'description' => $description,
                'exception_class' => $exceptionClass,
                'error_message' => $errorMessage,
                'error_file_line' => $fileLine,
                'error_hash' => $errorHash,
                'request_url' => $requestUrl,
                'request_method' => $requestMethod,
                'request_payload' => $payload,
                'stack_trace' => substr($e->getTraceAsString(), 0, 10000), // Limita primeiros 10KB de stack
                'occurrences_count' => 1,
                'last_occurred_at' => now(),
            ]);

            // Notifica os administradores do SaaS Master no sino do painel
            try {
                $superAdmins = \App\Models\User::withoutGlobalScopes()
                    ->where(function ($q) {
                        $q->where('is_super_admin', true)->orWhere('is_admin', true);
                    })
                    ->get();

                foreach ($superAdmins as $admin) {
                    $notif = \Filament\Notifications\Notification::make()
                        ->title("🚨 Incidente Crítico: {$createdTicket->protocol}")
                        ->body("{$createdTicket->subject} ({$createdTicket->error_file_line})")
                        ->danger()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('Ver Incidente')
                                ->url(url('/tickets-and-incidents')),
                        ]);
                    
                    // Envio síncrono imediato para não depender de fila assíncrona
                    $admin->notifyNow($notif->toDatabase());
                }
            } catch (Throwable) {
                // Silencioso se notificações de banco não estiverem ativas
            }

            return $createdTicket;
        } catch (Throwable $reporterError) {
            // Se o próprio reporter falhar, grava no log local sem interromper o fluxo original
            Log::error('Falha no SystemIncidentReporter: ' . $reporterError->getMessage());

            return null;
        }
    }
}
