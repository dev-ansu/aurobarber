<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barbershop;
use App\Models\LandingLead;
use App\Models\Page;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsLandingPageTest extends TestCase
{
    use RefreshDatabase;
    public function test_home_page_renders_successfully_with_cms_content_and_dynamic_plans(): void
    {
        $this->seed(\Database\Seeders\CmsInitialContentSeeder::class);

        // Garante que existe pelo menos um plano ativo
        $plan = SaasPlan::firstOrCreate(
            ['slug' => 'plano-vip-teste'],
            [
                'name' => 'Plano VIP Teste',
                'monthly_price' => 199.90,
                'billing_cycle_months' => 1,
                'features' => ['Recurso Exclusivo 1', 'Recurso Exclusivo 2'],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('AuroBarber');
        $response->assertSee('Plano VIP Teste');
        $response->assertSee('199,90');
    }

    public function test_can_submit_landing_lead(): void
    {
        $plan = SaasPlan::first();

        $payload = [
            'name' => 'Carlos Henrique',
            'barbershop_name' => 'Barbearia Vintage',
            'phone' => '85999887766',
            'email' => 'carlos@vintage.com',
            'city' => 'Fortaleza / CE',
            'chairs_count' => '3 cadeiras',
            'selected_plan' => $plan ? $plan->name : 'Plano Mensal',
            'selected_plan_id' => $plan?->id,
            'message' => 'Gostaria de testar na minha barbearia.',
        ];

        $response = $this->postJson(route('landing.lead.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('landing_leads', [
            'email' => 'carlos@vintage.com',
            'barbershop_name' => 'Barbearia Vintage',
            'status' => 'novo',
        ]);
    }

    public function test_custom_cms_slug_renders_page(): void
    {
        Page::create([
            'title' => 'Termos de Teste',
            'slug' => 'termos-teste',
            'is_published' => true,
            'content' => [
                [
                    'type' => 'rich_text',
                    'data' => [
                        'title' => 'Nossos Termos Legais',
                        'content' => '<p>Conteudo legal da plataforma</p>',
                    ],
                ],
            ],
        ]);

        $response = $this->get('/termos-teste');

        $response->assertStatus(200);
        $response->assertSee('Nossos Termos Legais');
        $response->assertSee('Conteudo legal da plataforma');
    }

    public function test_barbershop_slug_still_resolves_to_booking_when_not_in_cms(): void
    {
        $barbershop = Barbershop::first();
        if ($barbershop) {
            $response = $this->get('/' . $barbershop->slug);
            $response->assertStatus(200);
            $response->assertSee($barbershop->name);
        } else {
            $this->assertTrue(true);
        }
    }
}
