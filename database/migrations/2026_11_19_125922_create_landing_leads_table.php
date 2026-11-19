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
        if (DB::connection()->getDriverName() === 'pgsql'
            && DB::selectOne('SELECT current_schema() AS schema')->schema !== 'public') {
            return;
        }

        Schema::create('landing_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('barbershop_name', 160);
            $table->string('email', 160);
            $table->string('phone', 40);
            $table->string('city', 100)->nullable();
            $table->string('chairs_count', 60)->nullable();
            $table->string('selected_plan', 100)->nullable();
            $table->foreignId('selected_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('novo')->index(); // novo, em_contato, agendado, convertido, arquivado
            $table->text('notes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->foreignId('converted_barbershop_id')->nullable()->constrained('barbershops')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql'
            && DB::selectOne('SELECT current_schema() AS schema')->schema !== 'public') {
            return;
        }

        Schema::dropIfExists('landing_leads');
    }
};
