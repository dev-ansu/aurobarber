<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Turnstile implements ValidationRule
{
    public bool $implicit = true;

    public function __construct(
        protected ?string $action = null
    ) {}

    /**
     * Valida o token gerado pelo Cloudflare Turnstile.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.turnstile.secret_key');

        // Se a chave secreta não foi configurada em ambiente local ou de teste, permite a validação passar
        if (empty($secretKey)) {
            if (app()->environment('local', 'testing') || app()->runningUnitTests()) {
                return;
            }

            Log::error('Cloudflare Turnstile secret key is not configured in production.');
            $fail('O serviço de verificação de segurança está temporariamente indisponível.');
            return;
        }

        if (empty($value) || ! is_string($value)) {
            $fail('A verificação de segurança é obrigatória.');
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful()) {
                Log::warning('Cloudflare Turnstile API HTTP failure', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $fail('Não foi possível verificar a segurança do formulário. Tente novamente.');
                return;
            }

            $data = $response->json();

            if (! isset($data['success']) || $data['success'] !== true) {
                Log::info('Cloudflare Turnstile challenge rejected', [
                    'ip' => request()->ip(),
                    'error_codes' => $data['error-codes'] ?? [],
                ]);
                $fail('A verificação de segurança falhou. Por favor, recarregue e tente novamente.');
                return;
            }

            // Opcional: validação de action esperada
            if ($this->action && isset($data['action']) && $data['action'] !== $this->action) {
                $fail('Origem da verificação de segurança inválida.');
            }
        } catch (\Throwable $e) {
            Log::error('Cloudflare Turnstile verification exception', [
                'error' => $e->getMessage(),
            ]);

            // Em caso de indisponibilidade extrema da API Cloudflare, em ambiente de testes ou locais não bloqueia
            if (app()->environment('local', 'testing')) {
                return;
            }

            $fail('Erro ao validar verificação anti-bot. Tente novamente em instantes.');
        }
    }
}
