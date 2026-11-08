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
        // 1. Barbearia Piloto (Barbearia Marinho)
        $barbershop = Barbershop::updateOrCreate(
            ['slug' => 'marinho'],
            [
                'name' => 'Barbearia Marinho',
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

        // 2. Usuário Administrador Master da Plataforma
        User::updateOrCreate(
            ['email' => 'admin@aurobarber.com.br'],
            [
                'name' => 'Admin AuroBarber',
                'password' => Hash::make('aurobarber123'),
                'role' => 'master_admin',
                'is_active' => true,
            ]
        );

        // 3. Usuário Dono da Barbearia Marinho
        $ownerUser = User::updateOrCreate(
            ['email' => 'marinho@barber.com.br'],
            [
                'name' => 'Marinho Dono',
                'barbershop_id' => $barbershop->id,
                'password' => Hash::make('marinho123'),
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        // 4. Barbeiros / Profissionais da Equipe
        $marinhoPro = Professional::updateOrCreate(
            ['barbershop_id' => $barbershop->id, 'name' => 'Marinho'],
            [
                'user_id' => $ownerUser->id,
                'nickname' => 'Marinho',
                'phone' => '85999999999',
                'commission_rate' => 50.00,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Professional::updateOrCreate(
            ['barbershop_id' => $barbershop->id, 'name' => 'Lucas Barber'],
            [
                'nickname' => 'Lucas',
                'commission_rate' => 50.00,
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        Professional::updateOrCreate(
            ['barbershop_id' => $barbershop->id, 'name' => 'Thiago Silva'],
            [
                'nickname' => 'Thiago',
                'commission_rate' => 45.00,
                'display_order' => 3,
                'is_active' => true,
            ]
        );

        // 5. Serviços do Catálogo
        Service::updateOrCreate(
            ['barbershop_id' => $barbershop->id, 'name' => 'Corte Degradê / Social'],
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
            ['barbershop_id' => $barbershop->id, 'name' => 'Barboterapia Completa'],
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
            ['barbershop_id' => $barbershop->id, 'name' => 'Combo Corte + Barba'],
            [
                'description' => 'O visual completo com desconto exclusivo. Cabelo e barba impecáveis.',
                'price' => 65.00,
                'duration_minutes' => 55,
                'category' => 'combo',
                'display_order' => 3,
                'is_active' => true,
            ]
        );

        Service::updateOrCreate(
            ['barbershop_id' => $barbershop->id, 'name' => 'Design de Sobrancelha'],
            [
                'description' => 'Limpeza e alinhamento com navalhete ou pinça.',
                'price' => 15.00,
                'duration_minutes' => 15,
                'category' => 'tratamentos',
                'display_order' => 4,
                'is_active' => true,
            ]
        );
    }
}
