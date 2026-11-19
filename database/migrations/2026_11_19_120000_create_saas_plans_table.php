<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saas_plans')) {
            Schema::create('saas_plans', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 80);
                $table->string('slug', 90)->unique();
                $table->string('badge', 50)->nullable();
                $table->text('description')->nullable();
                $table->decimal('monthly_price', 10, 2);
                $table->unsignedInteger('billing_cycle_months')->default(1);
                $table->unsignedInteger('renewal_days')->default(30);
                $table->unsignedInteger('max_units')->default(1);
                $table->json('features')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });

            $now = now();
            DB::table('saas_plans')->insert([
                [
                    'name' => 'Plano Mensal',
                    'slug' => 'mensal',
                    'badge' => 'Flexível',
                    'description' => 'Renovação mensal flexível sem fidelidade. Todos os recursos liberados para toda a barbearia.',
                    'monthly_price' => 89.90,
                    'billing_cycle_months' => 1,
                    'renewal_days' => 30,
                    'max_units' => 1,
                    'features' => json_encode([
                        'Barbeiros e cadeiras ilimitados',
                        'Agendamentos online ilimitados',
                        'Página White-Label + App PWA',
                        'Comanda de bar, bebidas e cosméticos',
                        'Comissões automáticas + Recibo jurídico',
                        'Visão restrita do barbeiro no celular',
                        'Cartão fidelidade digital automático',
                    ], JSON_UNESCAPED_UNICODE),
                    'is_featured' => false,
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Plano Semestral',
                    'slug' => 'semestral',
                    'badge' => 'Mais Popular',
                    'description' => 'Economia garantida no ciclo de 6 meses com suporte prioritário e treinamento da equipe.',
                    'monthly_price' => 79.90,
                    'billing_cycle_months' => 6,
                    'renewal_days' => 180,
                    'max_units' => 1,
                    'features' => json_encode([
                        'Barbeiros e cadeiras ilimitados',
                        'Todos os recursos do sistema liberados',
                        'Visão restrita do barbeiro no celular',
                        'Comanda de bar e fechamento de caixa',
                        'Suporte prioritário no WhatsApp',
                        'Treinamento inicial da equipe',
                    ], JSON_UNESCAPED_UNICODE),
                    'is_featured' => true,
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Plano Anual',
                    'slug' => 'anual',
                    'badge' => 'Maior Economia',
                    'description' => 'Maior desconto para parceria anual com 2 meses grátis e suporte VIP direto.',
                    'monthly_price' => 69.90,
                    'billing_cycle_months' => 12,
                    'renewal_days' => 365,
                    'max_units' => 1,
                    'features' => json_encode([
                        'Barbeiros e cadeiras ilimitados',
                        '2 meses grátis inclusos no ciclo anual',
                        'Todos os recursos 100% liberados',
                        'QR Code de balcão e banners digitais',
                        'Suporte VIP direto no WhatsApp',
                    ], JSON_UNESCAPED_UNICODE),
                    'is_featured' => false,
                    'is_active' => true,
                    'sort_order' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (Schema::hasTable('barbershops') && ! Schema::hasColumn('barbershops', 'saas_plan_id')) {
            Schema::table('barbershops', function (Blueprint $table): void {
                $table->foreignId('saas_plan_id')
                    ->nullable()
                    ->after('license_status')
                    ->constrained('saas_plans')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('barbershops') && Schema::hasColumn('barbershops', 'saas_plan_id')) {
            Schema::table('barbershops', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('saas_plan_id');
            });
        }

        Schema::dropIfExists('saas_plans');
    }
};
