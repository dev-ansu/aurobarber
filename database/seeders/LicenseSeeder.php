<?php

namespace Database\Seeders;

use App\Models\License;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cliente Pioneiro Ativo (ITEP - SG Consultoria)
        License::updateOrCreate(
            ['license_key' => 'itep_cursos'],
            [
                'client_name' => 'SG Consultoria e Treinamentos LTDA',
                'trade_name' => 'ITEP - Álvaro Weyne',
                'cnpj' => '58.888.791/0001-18',
                'slug' => 'itep',
                'contact_name' => 'Diretoria ITEP',
                'contact_email' => 'diretoria@itep.com.br',
                'type' => 'on_premise',
                'status' => 'active',
                'starts_at' => now()->startOfYear()->toDateString(),
                'expires_at' => now()->addDays(365)->toDateString(),
                'grace_period_days' => 5,
                'monthly_price' => 800.00,
                'max_schools' => 2,
                'schema_name' => 'public',
                'notes' => 'Cliente pioneiro da rede ITEP (Álvaro Weyne e Barra do Ceará). Valor mensal: R$ 800,00.',
            ]
        );

        // 2. Licença Homologada de Testes On-Premise
        License::updateOrCreate(
            ['license_key' => 'aurosoft_teste_escola'],
            [
                'client_name' => 'Escola Modelo de Teste On-Premise',
                'trade_name' => 'Aurosoft Cursos Teste',
                'cnpj' => '12.345.678/0001-99',
                'slug' => 'modelo-teste',
                'contact_name' => 'Coordenação Pedagógica',
                'contact_email' => 'teste@aurosoft.com.br',
                'type' => 'on_premise',
                'status' => 'active',
                'starts_at' => now()->startOfYear()->toDateString(),
                'expires_at' => now()->addDays(365)->toDateString(),
                'grace_period_days' => 5,
                'monthly_price' => 199.00,
                'max_schools' => 1,
                'schema_name' => 'public',
                'notes' => 'Licença oficial de testes e homologação on-premise.',
            ]
        );

        // 3. Licença de Teste Cloud SaaS (Multi-Tenant por Schema)
        License::updateOrCreate(
            ['license_key' => 'AURO-TESTE-COLEGIO-MODELO'],
            [
                'client_name' => 'Colégio Modelo Teste Ltda',
                'trade_name' => 'Colégio Modelo',
                'slug' => 'colegiomodelo',
                'contact_name' => 'Diretor Roberto',
                'contact_email' => 'roberto@colegiomodelo.com.br',
                'type' => 'cloud_saas',
                'status' => 'active',
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
                'grace_period_days' => 5,
                'monthly_price' => 250.00,
                'max_schools' => 1,
                'schema_name' => 'tenant_colegiomodelo',
                'notes' => 'Licença de testes do ambiente Cloud Multi-Tenant.',
            ]
        );
    }
}
