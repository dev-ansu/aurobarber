<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Intervalo de Almoço dos Profissionais
        Schema::table('professionals', function (Blueprint $table) {
            if (!Schema::hasColumn('professionals', 'has_lunch_break')) {
                $table->boolean('has_lunch_break')->default(false)->after('work_end_time');
                $table->time('lunch_start_time')->nullable()->default('12:00:00')->after('has_lunch_break');
                $table->time('lunch_end_time')->nullable()->default('13:00:00')->after('lunch_start_time');
            }
        });

        // 2. Configurações de Fidelidade e Retorno na Barbearia
        Schema::table('barbershops', function (Blueprint $table) {
            if (!Schema::hasColumn('barbershops', 'loyalty_enabled')) {
                $table->boolean('loyalty_enabled')->default(true)->after('is_active');
                $table->integer('loyalty_target_cuts')->default(10)->after('loyalty_enabled');
                $table->integer('return_reminder_days')->default(20)->after('loyalty_target_cuts');
            }
        });

        // 3. Produtos e Estoque (Bar / Cosméticos)
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
                $table->string('name');
                $table->string('category')->default('bar'); // bar, cosmetico, insumo, outros
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('cost_price', 10, 2)->default(0);
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_stock_alert')->default(5);
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Consumo de Produtos na Comanda do Agendamento
        if (!Schema::hasTable('appointment_products')) {
            Schema::create('appointment_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total_price', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        // 5. Campos de Consumo, Bloqueio de Agenda e Avaliação no Agendamento
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'products_total')) {
                $table->decimal('products_total', 10, 2)->default(0)->after('service_price');
                $table->boolean('is_blocked_slot')->default(false)->after('status');
                $table->unsignedTinyInteger('rating')->nullable()->after('notes');
                $table->text('rating_comment')->nullable()->after('rating');
                $table->timestamp('rated_at')->nullable()->after('rating_comment');
            }
        });

        // 6. CRM de Clientes da Barbearia (Retorno, Aniversariantes e Fidelidade)
        if (!Schema::hasTable('barbershop_clients')) {
            Schema::create('barbershop_clients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
                $table->string('name');
                $table->string('phone', 30)->index();
                $table->date('birth_date')->nullable();
                $table->text('notes')->nullable();
                $table->integer('loyalty_stamps')->default(0);
                $table->integer('loyalty_rewards_redeemed')->default(0);
                $table->integer('total_visits')->default(0);
                $table->decimal('total_spent', 10, 2)->default(0);
                $table->timestamp('last_visit_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershop_clients');
        Schema::dropIfExists('appointment_products');
        Schema::dropIfExists('products');
    }
};
