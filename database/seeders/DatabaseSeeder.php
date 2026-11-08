<?php

namespace Database\Seeders;

use App\Models\Barbershop;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. USUÁRIO ADMINISTRADOR MASTER (Aurosoft)
        // ==========================================
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@aurobarber.com.br'],
            [
                'name' => 'Admin AuroBarber',
                'password' => Hash::make('aurobarber123'),
                'role' => 'master_admin',
                'is_active' => true,
            ]
        );

        // ==========================================
        // 2. CLIENTE 1: REDE MARINHO (Multi-Unidades)
        // ==========================================
        $ownerMarinho = User::updateOrCreate(
            ['email' => 'marinho@barber.com.br'],
            [
                'name' => 'Marinho (Proprietário)',
                'password' => Hash::make('marinho123'),
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        // 2.1 Unidade 1: Barbearia Marinho - Matriz Centro
        $marinhoCentro = Barbershop::updateOrCreate(
            ['slug' => 'marinho'],
            [
                'name' => 'Barbearia Marinho - Centro',
                'owner_id' => $ownerMarinho->id,
                'headline' => 'Tradição, Estilo e Barboterapia de Alto Nível',
                'description' => 'Ambiente climatizado, cerveja gelada e os melhores profissionais para cuidar do seu visual com conforto e pontualidade.',
                'city' => 'Fortaleza / CE',
                'address' => 'Av. Principal, 123 - Centro',
                'whatsapp' => '85999999999',
                'brand_color' => '#d97706', // Âmbar Dourado
                'is_active' => true,
                'subscription_status' => 'active',
                'subscription_plan' => 'pro',
            ]
        );

        // 2.2 Unidade 2: Barbearia Marinho - Filial Aldeota
        $marinhoAldeota = Barbershop::updateOrCreate(
            ['slug' => 'marinho-aldeota'],
            [
                'name' => 'Barbearia Marinho - Aldeota',
                'owner_id' => $ownerMarinho->id,
                'parent_id' => $marinhoCentro->id,
                'headline' => 'Sua Barbearia Premium na Aldeota',
                'description' => 'Unidade executiva com lounge exclusivo, espaço café e especialistas em visagismo.',
                'city' => 'Fortaleza / CE',
                'address' => 'Av. Dom Luís, 500 - Aldeota',
                'whatsapp' => '85988888888',
                'brand_color' => '#0284c7', // Azul Executivo
                'is_active' => true,
                'subscription_status' => 'active',
                'subscription_plan' => 'pro',
            ]
        );

        // Vincula o usuário Marinho a ambas as unidades na tabela pivô
        $ownerMarinho->barbershop_id = $marinhoCentro->id;
        $ownerMarinho->save();
        $ownerMarinho->barbershops()->syncWithoutDetaching([
            $marinhoCentro->id => ['role' => 'owner', 'is_primary' => true],
            $marinhoAldeota->id => ['role' => 'owner', 'is_primary' => false],
        ]);

        // Barbeiros da Unidade Centro
        Professional::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Marinho'],
            [
                'user_id' => $ownerMarinho->id,
                'nickname' => 'Marinho',
                'phone' => '85999999999',
                'commission_rate' => 50.00,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Professional::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Lucas Barber'],
            [
                'nickname' => 'Lucas',
                'commission_rate' => 50.00,
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        // Barbeiros da Unidade Aldeota
        Professional::updateOrCreate(
            ['barbershop_id' => $marinhoAldeota->id, 'name' => 'Felipe Visagista'],
            [
                'nickname' => 'Felipe',
                'phone' => '85988881111',
                'commission_rate' => 55.00,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Professional::updateOrCreate(
            ['barbershop_id' => $marinhoAldeota->id, 'name' => 'Gabriel Navalha'],
            [
                'nickname' => 'Gabriel',
                'phone' => '85988882222',
                'commission_rate' => 50.00,
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        // Serviços da Unidade Centro
        Service::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Corte Degradê / Social'],
            [
                'description' => 'Corte moderno com tesoura e máquina, finalizado com navalha e pomada.',
                'price' => 40.00,
                'duration_minutes' => 35,
                'category' => 'cabelo',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Service::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Barboterapia Completa'],
            [
                'description' => 'Toalha quente, massagem facial, hidratação de barba e alinhamento com navalhete.',
                'price' => 35.00,
                'duration_minutes' => 30,
                'category' => 'barba',
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        Service::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Combo Corte + Barba'],
            [
                'description' => 'O visual completo com desconto exclusivo. Cabelo e barba impecáveis.',
                'price' => 65.00,
                'duration_minutes' => 55,
                'category' => 'combo',
                'display_order' => 3,
                'is_active' => true,
            ]
        );

        // Serviços da Unidade Aldeota
        Service::updateOrCreate(
            ['barbershop_id' => $marinhoAldeota->id, 'name' => 'Corte Executivo Premium'],
            [
                'description' => 'Lavagem especial, corte estilizado conforme formato do rosto e finalização importada.',
                'price' => 55.00,
                'duration_minutes' => 45,
                'category' => 'cabelo',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Service::updateOrCreate(
            ['barbershop_id' => $marinhoAldeota->id, 'name' => 'Barboterapia & Spa Facial'],
            [
                'description' => 'Toalhas aromatizadas quentes, esfoliação facial e hidratação profunda com óleos nobres.',
                'price' => 50.00,
                'duration_minutes' => 40,
                'category' => 'barba',
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        // ==========================================
        // 3. CLIENTE 2: OUTRA BARBEARIA (Isolamento Total)
        // ==========================================
        $ownerVintage = User::updateOrCreate(
            ['email' => 'rodrigo@vintageclub.com.br'],
            [
                'name' => 'Rodrigo (Vintage Club)',
                'password' => Hash::make('vintage123'),
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        $vintageShop = Barbershop::updateOrCreate(
            ['slug' => 'vintage-club'],
            [
                'name' => 'Barbearia Vintage Club',
                'owner_id' => $ownerVintage->id,
                'headline' => 'Estilo Clássico dos Anos 50 com Conforto Moderno',
                'description' => 'Cortes old school, toalha quente e atendimento personalizado para cavalheiros.',
                'city' => 'São Paulo / SP',
                'address' => 'Rua Augusta, 1500 - Consolação',
                'whatsapp' => '11977777777',
                'brand_color' => '#10b981', // Verde Esmeralda
                'is_active' => true,
                'subscription_status' => 'active',
                'subscription_plan' => 'pro',
            ]
        );

        $ownerVintage->barbershop_id = $vintageShop->id;
        $ownerVintage->save();
        $ownerVintage->barbershops()->syncWithoutDetaching([
            $vintageShop->id => ['role' => 'owner', 'is_primary' => true],
        ]);

        Professional::updateOrCreate(
            ['barbershop_id' => $vintageShop->id, 'name' => 'Rodrigo Vintage'],
            [
                'nickname' => 'Rodrigo',
                'commission_rate' => 60.00,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Service::updateOrCreate(
            ['barbershop_id' => $vintageShop->id, 'name' => 'Corte Clássico Retrô'],
            [
                'description' => 'Pompadour, Slick Back ou Side Part finalizado com pomada fosca.',
                'price' => 50.00,
                'duration_minutes' => 40,
                'category' => 'cabelo',
                'display_order' => 1,
                'is_active' => true,
            ]
        );
    }
}
