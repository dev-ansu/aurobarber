<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;

class StoreLandingLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'turnstile_token' => [
                config('services.turnstile.site_key') ? 'required' : 'nullable',
                new Turnstile('landing_lead'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'barbershop_name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:160'],
            'city' => ['nullable', 'string', 'max:100'],
            'chairs_count' => ['nullable', 'string', 'max:60'],
            'selected_plan' => ['nullable', 'string', 'max:100'],
            'selected_plan_id' => ['nullable', 'integer', 'exists:saas_plans,id'],
            'message' => ['nullable', 'string', 'max:1000'],
            'website_url' => ['nullable', 'string', 'max:255'], // Honeypot silencioso
        ];
    }

    public function messages(): array
    {
        return [
            'turnstile_token.required' => 'A verificação de segurança é obrigatória. Por favor, complete o desafio.',
            'name.required' => 'Por favor, informe seu nome.',
            'barbershop_name.required' => 'Por favor, informe o nome da sua barbearia.',
            'phone.required' => 'Por favor, informe seu WhatsApp de contato.',
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um endereço de e-mail válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $turnstileToken = $this->input('turnstile_token') ?? $this->input('cf-turnstile-response');

        if ($turnstileToken !== null) {
            $this->merge(['turnstile_token' => $turnstileToken]);
        }
    }
}
