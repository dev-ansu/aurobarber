<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEmailDomain implements ValidationRule
{
    /**
     * Lista de domínios descartáveis/temporários mais comuns utilizados em spams e testes fakes.
     */
    protected const DISPOSABLE_DOMAINS = [
        'mailinator.com',
        '10minutemail.com',
        'tempmail.com',
        'guerrillamail.com',
        'yopmail.com',
        'throwawaymail.com',
        'sharklasers.com',
        'dispostable.com',
        'trashmail.com',
        'getairmail.com',
        'temp-mail.org',
        'fakeinbox.com',
        'generator.email',
        'maildrop.cc',
        'mohmal.com',
        'inboxkitten.com',
        'burnermail.io',
        'crazymailing.com',
        'emailondeck.com',
        'tmail.ws',
        'mytemp.email',
        'discard.email',
        'trashmail.net',
        'tempail.com',
    ];

    /**
     * Valida se o e-mail possui um domínio corporativo/pessoal válido, não descartável e com servidores MX ativos.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || blank($value)) {
            return;
        }

        $email = trim($value);

        // 1. Valida sintaxe básica do e-mail
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fail('O campo deve conter um endereço de e-mail com formato válido.');
            return;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            $fail('O endereço de e-mail informado é inválido.');
            return;
        }

        $username = mb_strtolower(trim($parts[0]));
        $domain = mb_strtolower(trim($parts[1]));

        // 2. Bloqueia nomes de usuário genéricos de teste (ex: teste@..., test@..., fake@...)
        if (in_array($username, ['teste', 'test', 'asdf', 'fake', 'fake123', 'naotem', 'nenhum', 'abc', '123', 'admin'], true)) {
            $fail('O endereço de e-mail informado parece fictício ou de teste. Por favor, utilize seu e-mail institucional ou pessoal real.');
            return;
        }

        // 3. Bloqueia provedores descartáveis/temporários conhecidos
        if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            $fail("O provedor '@{$domain}' é um serviço de e-mail temporário/descartável. Por favor, utilize um e-mail corporativo ou pessoal válido.");
            return;
        }

        // 4. Permite domínios internos locais do sistema (ex: usuario.1@aurosoft.local)
        if (str_ends_with($domain, '.local')) {
            return;
        }

        // 5. Bloqueia domínios de testes fakes comuns
        if (in_array($domain, ['teste.com', 'teste.com.br', 'fake.com', 'exemplo.com', 'example.com', 'test.com', 'exemplo.com.br'], true)) {
            $fail("O domínio '@{$domain}' não é aceito para cadastros.");
            return;
        }

        // Em ambientes de teste automatizado (PHPUnit/Pest), ignora verificação de rede externa
        if (app()->environment('testing')) {
            return;
        }

        // 4. Checagem de DNS / Servidor MX no mundo real
        if (function_exists('checkdnsrr')) {
            $hasMx = @checkdnsrr($domain, 'MX');
            $hasA = $hasMx || @checkdnsrr($domain, 'A');

            if (! $hasMx && ! $hasA) {
                $fail("O domínio '@{$domain}' não possui servidores de e-mail (registro DNS/MX) ativos para receber mensagens.");
            }
        }
    }
}
