<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DevFakeDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = [
            'super_admin',
            'barbershop_owner',
            'barbershop_manager',
            'barber',
            'receptionist',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Administrador Master
        $admin = User::updateOrCreate(
            ['email' => 'admin@aurobarber.com.br'],
            [
                'name' => 'Admin AuroBarber',
                'password' => Hash::make('aurobarber123'),
                'role' => 'master_admin',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->assignRole('super_admin');

        // 2.1 Administrador Master Oficial (Anderson)
        $masterAnderson = User::updateOrCreate(
            ['email' => 'dev.andersondesouzafeitosa@gmail.com'],
            [
                'name' => 'Anderson Feitosa (SaaS Master)',
                'password' => Hash::make('AuroDev@2026!#Temp'),
                'role' => 'master_admin',
                'must_change_password' => true,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $masterAnderson->assignRole('super_admin');

        // 3. Proprietário Marinho
        $ownerMarinho = User::updateOrCreate(
            ['email' => 'marinho@barber.com.br'],
            [
                'name' => 'Marinho (Proprietário)',
                'password' => Hash::make('marinho123'),
                'role' => 'owner',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $ownerMarinho->assignRole('barbershop_owner');

        // 3.1 Unidade 1: Barbearia Marinho - Centro
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
                'brand_color' => '#d97706',
                'is_active' => true,
                'subscription_status' => 'active',
                'subscription_plan' => 'pro',
                'opening_hours' => [
                    'days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
                    'start_time' => '08:00',
                    'end_time' => '19:30',
                    'slot_interval' => 30,
                ],
            ]
        );

        // 3.2 Unidade 2: Barbearia Marinho - Aldeota
        $marinhoAldeota = Barbershop::updateOrCreate(
            ['slug' => 'marinho-aldeota'],
            [
                'name' => 'Barbearia Marinho - Aldeota',
                'owner_id' => $ownerMarinho->id,
                'parent_id' => $marinhoCentro->id,
                'headline' => 'Sua Barbearia Premium na Aldeota',
                'description' => 'Unidade executiva com lounge exclusivo e visagismo.',
                'city' => 'Fortaleza / CE',
                'address' => 'Av. Dom Luís, 500 - Aldeota',
                'whatsapp' => '85988888888',
                'brand_color' => '#0284c7',
                'is_active' => true,
                'subscription_status' => 'active',
                'subscription_plan' => 'pro',
                'opening_hours' => [
                    'days' => ['tue', 'wed', 'thu', 'fri', 'sat'],
                    'start_time' => '09:00',
                    'end_time' => '20:00',
                    'slot_interval' => 30,
                ],
            ]
        );

        $ownerMarinho->barbershop_id = $marinhoCentro->id;
        $ownerMarinho->save();
        $ownerMarinho->barbershops()->syncWithoutDetaching([
            $marinhoCentro->id => ['role' => 'owner', 'is_primary' => true],
            $marinhoAldeota->id => ['role' => 'owner', 'is_primary' => false],
        ]);

        // Barbeiros da Unidade Centro
        $proMarinho = Professional::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Marinho'],
            [
                'user_id' => $ownerMarinho->id,
                'nickname' => 'Marinho',
                'phone' => '85999999999',
                'commission_rate' => 50.00,
                'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
                'work_start_time' => '08:00:00',
                'work_end_time' => '19:00:00',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        $proLucas = Professional::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Lucas Barber'],
            [
                'nickname' => 'Lucas',
                'phone' => '85998887766',
                'commission_rate' => 50.00,
                'working_days' => ['tue', 'wed', 'thu', 'fri', 'sat'],
                'work_start_time' => '09:00:00',
                'work_end_time' => '19:00:00',
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        $proThiago = Professional::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Thiago Silva'],
            [
                'nickname' => 'Thiago',
                'phone' => '85997776655',
                'commission_rate' => 45.00,
                'working_days' => ['mon', 'wed', 'thu', 'fri', 'sat'],
                'work_start_time' => '10:00:00',
                'work_end_time' => '19:30:00',
                'display_order' => 3,
                'is_active' => true,
            ]
        );

        // Serviços Centro
        $srvDegrade = Service::updateOrCreate(
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

        $srvBarba = Service::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Barboterapia Completa'],
            [
                'description' => 'Toalha quente, hidratação profunda e alinhamento com navalha.',
                'price' => 35.00,
                'duration_minutes' => 30,
                'category' => 'barba',
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        $srvCombo = Service::updateOrCreate(
            ['barbershop_id' => $marinhoCentro->id, 'name' => 'Combo Corte + Barba'],
            [
                'description' => 'O visual completo com desconto exclusivo.',
                'price' => 65.00,
                'duration_minutes' => 55,
                'category' => 'combo',
                'display_order' => 3,
                'is_active' => true,
            ]
        );

        // 4. Barbeiros e Serviços Aldeota
        Professional::updateOrCreate(
            ['barbershop_id' => $marinhoAldeota->id, 'name' => 'Felipe Visagista'],
            [
                'nickname' => 'Felipe',
                'phone' => '85988881111',
                'commission_rate' => 55.00,
                'working_days' => ['tue', 'wed', 'thu', 'fri', 'sat'],
                'work_start_time' => '09:00:00',
                'work_end_time' => '19:00:00',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Service::updateOrCreate(
            ['barbershop_id' => $marinhoAldeota->id, 'name' => 'Corte Executivo Premium'],
            [
                'description' => 'Lavagem especial, corte estilizado e finalização.',
                'price' => 55.00,
                'duration_minutes' => 45,
                'category' => 'cabelo',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        // 5. Barbearia Vintage Club (Outro Cliente Isolado)
        $ownerVintage = User::updateOrCreate(
            ['email' => 'rodrigo@vintageclub.com.br'],
            [
                'name' => 'Rodrigo (Vintage Club)',
                'password' => Hash::make('vintage123'),
                'role' => 'owner',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $vintageShop = Barbershop::updateOrCreate(
            ['slug' => 'vintage-club'],
            [
                'name' => 'Barbearia Vintage Club',
                'owner_id' => $ownerVintage->id,
                'headline' => 'Estilo Clássico dos Anos 50 com Conforto Moderno',
                'description' => 'Cortes old school e toalha quente.',
                'city' => 'São Paulo / SP',
                'address' => 'Rua Augusta, 1500 - Consolação',
                'whatsapp' => '11977777777',
                'brand_color' => '#10b981',
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

        // ==========================================
        // 6. DADOS DE ATENDIMENTOS E MOVIMENTAÇÕES (MARINHO CENTRO)
        // ==========================================
        $clients = [
            ['name' => 'Rafael Albuquerque', 'phone' => '85991112233'],
            ['name' => 'Bruno Oliveira', 'phone' => '85992223344'],
            ['name' => 'Gustavo Henrique', 'phone' => '85993334455'],
            ['name' => 'Daniel Santos', 'phone' => '85994445566'],
            ['name' => 'Carlos Eduardo', 'phone' => '85995556677'],
            ['name' => 'Marcelo Vieira', 'phone' => '85996667788'],
            ['name' => 'Alexandre Rocha', 'phone' => '85997778899'],
            ['name' => 'Rodrigo Mendes', 'phone' => '85998889900'],
        ];

        $paymentMethods = ['pix', 'dinheiro', 'debito', 'credito'];
        $pros = [$proMarinho, $proLucas, $proThiago];
        $servicesList = [$srvDegrade, $srvBarba, $srvCombo];

        // 6.1 Histórico dos últimos 7 dias (para alimentar relatórios e gráficos)
        for ($day = 6; $day >= 1; $day--) {
            $date = now()->subDays($day);
            $dateStr = $date->format('Y-m-d');

            // 4 a 6 atendimentos concluídos por dia
            $dailyCount = rand(4, 6);
            for ($k = 0; $k < $dailyCount; $k++) {
                $client = $clients[array_rand($clients)];
                $pro = $pros[array_rand($pros)];
                $srv = $servicesList[array_rand($servicesList)];
                $method = $paymentMethods[array_rand($paymentMethods)];

                $hour = rand(9, 18);
                $minute = rand(0, 1) === 0 ? 0 : 30;
                $scheduledAt = Carbon::parse($dateStr)->setTime($hour, $minute, 0);
                $endsAt = $scheduledAt->copy()->addMinutes($srv->duration_minutes);

                $commissionRate = $pro->commission_rate;
                $commissionAmount = round(($srv->price * $commissionRate) / 100, 2);

                $appt = Appointment::create([
                    'barbershop_id' => $marinhoCentro->id,
                    'professional_id' => $pro->id,
                    'service_id' => $srv->id,
                    'client_name' => $client['name'],
                    'client_phone' => $client['phone'],
                    'scheduled_at' => $scheduledAt,
                    'ends_at' => $endsAt,
                    'status' => 'completed',
                    'service_price' => $srv->price,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'commission_status' => 'pending',
                    'payment_method' => $method,
                    'payment_status' => 'paid',
                    'paid_at' => $scheduledAt,
                ]);

                $appt->services()->sync([$srv->id => ['price' => $srv->price, 'duration_minutes' => $srv->duration_minutes]]);
            }

            // Movimentações de caixa passadas
            CashMovement::create([
                'barbershop_id' => $marinhoCentro->id,
                'user_id' => $ownerMarinho->id,
                'type' => 'opening',
                'amount' => 150.00,
                'description' => 'Fundo de troco inicial da gaveta',
                'date' => $dateStr,
            ]);

            if ($day % 2 === 0) {
                CashMovement::create([
                    'barbershop_id' => $marinhoCentro->id,
                    'user_id' => $ownerMarinho->id,
                    'type' => 'outflow',
                    'amount' => 35.00,
                    'description' => 'Compra de café e copos descartáveis',
                    'date' => $dateStr,
                ]);
            }
        }

        // 6.2 Atendimentos de HOJE (para Agenda Diária e Caixa do Dia)
        $todayStr = now()->format('Y-m-d');

        // Movimentação de hoje
        CashMovement::create([
            'barbershop_id' => $marinhoCentro->id,
            'user_id' => $ownerMarinho->id,
            'type' => 'opening',
            'amount' => 150.00,
            'description' => 'Fundo de troco inicial de hoje',
            'date' => $todayStr,
        ]);

        CashMovement::create([
            'barbershop_id' => $marinhoCentro->id,
            'user_id' => $ownerMarinho->id,
            'type' => 'outflow',
            'amount' => 25.00,
            'description' => 'Lâminas descartáveis na farmácia',
            'date' => $todayStr,
        ]);

        // 1 Corte Concluído hoje pago em Dinheiro
        $appt1 = Appointment::create([
            'barbershop_id' => $marinhoCentro->id,
            'professional_id' => $proMarinho->id,
            'service_id' => $srvDegrade->id,
            'client_name' => 'Rafael Albuquerque',
            'client_phone' => '85991112233',
            'scheduled_at' => Carbon::today()->setTime(9, 0),
            'ends_at' => Carbon::today()->setTime(9, 35),
            'status' => 'completed',
            'service_price' => 40.00,
            'commission_rate' => 50.00,
            'commission_amount' => 20.00,
            'commission_status' => 'pending',
            'payment_method' => 'dinheiro',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
        $appt1->services()->sync([$srvDegrade->id => ['price' => 40.00, 'duration_minutes' => 35]]);

        // 1 Corte Concluído hoje pago em PIX
        $appt2 = Appointment::create([
            'barbershop_id' => $marinhoCentro->id,
            'professional_id' => $proLucas->id,
            'service_id' => $srvCombo->id,
            'client_name' => 'Bruno Oliveira',
            'client_phone' => '85992223344',
            'scheduled_at' => Carbon::today()->setTime(10, 0),
            'ends_at' => Carbon::today()->setTime(10, 55),
            'status' => 'completed',
            'service_price' => 65.00,
            'commission_rate' => 50.00,
            'commission_amount' => 32.50,
            'commission_status' => 'pending',
            'payment_method' => 'pix',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
        $appt2->services()->sync([$srvCombo->id => ['price' => 65.00, 'duration_minutes' => 55]]);

        // 1 Corte Em Atendimento na cadeira
        $appt3 = Appointment::create([
            'barbershop_id' => $marinhoCentro->id,
            'professional_id' => $proMarinho->id,
            'service_id' => $srvBarba->id,
            'client_name' => 'Gustavo Henrique',
            'client_phone' => '85993334455',
            'scheduled_at' => Carbon::today()->setTime(14, 0),
            'ends_at' => Carbon::today()->setTime(14, 30),
            'status' => 'in_progress',
            'service_price' => 35.00,
            'commission_rate' => 50.00,
            'commission_amount' => 17.50,
            'commission_status' => 'pending',
            'payment_method' => null,
            'payment_status' => 'pending',
        ]);
        $appt3->services()->sync([$srvBarba->id => ['price' => 35.00, 'duration_minutes' => 30]]);

        // 2 Cortes Agendados para mais tarde
        $appt4 = Appointment::create([
            'barbershop_id' => $marinhoCentro->id,
            'professional_id' => $proLucas->id,
            'service_id' => $srvDegrade->id,
            'client_name' => 'Daniel Santos',
            'client_phone' => '85994445566',
            'scheduled_at' => Carbon::today()->setTime(16, 0),
            'ends_at' => Carbon::today()->setTime(16, 35),
            'status' => 'scheduled',
            'service_price' => 40.00,
            'commission_rate' => 50.00,
            'commission_amount' => 20.00,
            'commission_status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $appt4->services()->sync([$srvDegrade->id => ['price' => 40.00, 'duration_minutes' => 35]]);

        $appt5 = Appointment::create([
            'barbershop_id' => $marinhoCentro->id,
            'professional_id' => $proThiago->id,
            'service_id' => $srvCombo->id,
            'client_name' => 'Carlos Eduardo',
            'client_phone' => '85995556677',
            'scheduled_at' => Carbon::today()->setTime(17, 30),
            'ends_at' => Carbon::today()->setTime(18, 25),
            'status' => 'confirmed',
            'service_price' => 65.00,
            'commission_rate' => 45.00,
            'commission_amount' => 29.25,
            'commission_status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $appt5->services()->sync([$srvCombo->id => ['price' => 65.00, 'duration_minutes' => 55]]);

        // 6.3 Clientes Ausentes / Sumidos (há mais de 45 dias) para alimentar o relatório de recuperação
        $oldDate = now()->subDays(50);
        $oldAppt = Appointment::create([
            'barbershop_id' => $marinhoCentro->id,
            'professional_id' => $proMarinho->id,
            'service_id' => $srvCombo->id,
            'client_name' => 'Fábio Castro',
            'client_phone' => '85999001122',
            'scheduled_at' => $oldDate,
            'ends_at' => $oldDate->copy()->addMinutes(55),
            'status' => 'completed',
            'service_price' => 65.00,
            'commission_rate' => 50.00,
            'commission_amount' => 32.50,
            'commission_status' => 'paid',
            'payment_method' => 'pix',
            'payment_status' => 'paid',
            'paid_at' => $oldDate,
        ]);
        $oldAppt->services()->sync([$srvCombo->id => ['price' => 65.00, 'duration_minutes' => 55]]);
    }
}
