<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\LandingLeadMail;
use App\Models\LandingLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LandingLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_submit_landing_lead_and_dispatches_email(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Carlos Barbeiro',
            'barbershop_name' => 'Barbearia Estilo & Arte',
            'phone' => '85988776655',
            'email' => 'carlos@estiloearte.com.br',
            'city' => 'Fortaleza / CE',
            'chairs_count' => '3 cadeiras',
            'selected_plan' => 'Plano Mensal',
            'message' => 'Quero conhecer o sistema para 3 barbeiros.',
            'turnstile_token' => 'bypass',
        ];

        $response = $this->postJson(route('landing.lead.store'), $payload);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('landing_leads', [
            'email' => 'carlos@estiloearte.com.br',
            'barbershop_name' => 'Barbearia Estilo & Arte',
            'status' => 'novo',
        ]);

        Mail::assertSent(LandingLeadMail::class, function (LandingLeadMail $mail) {
            return $mail->hasTo(env('ADMIN_NOTIFICATION_EMAIL', 'dev.andersondesouzafeitosa@gmail.com'));
        });

        $lead = LandingLead::where('email', 'carlos@estiloearte.com.br')->first();
        $this->assertNotNull($lead);
        $this->assertStringContainsString('wa.me', $lead->getWhatsAppUrl());
        $this->assertStringContainsString('5585988776655', $lead->getCleanPhone());
    }

    public function test_honeypot_silently_ignores_spam_bots(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Bot Spammer',
            'barbershop_name' => 'Spam Shop',
            'phone' => '11999999999',
            'email' => 'spam@bot.com',
            'website_url' => 'https://spamlink.com',
        ];

        $response = $this->postJson(route('landing.lead.store'), $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('landing_leads', [
            'email' => 'spam@bot.com',
        ]);

        Mail::assertNothingSent();
    }
}
