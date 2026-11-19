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
        Schema::table('barbershops', function (Blueprint $table): void {
            $table->unsignedInteger('max_units')->nullable()->after('saas_plan_id');
            $table->boolean('has_unrestricted_access')->default(false)->after('max_units');
            $table->unsignedInteger('max_professionals')->nullable()->after('has_unrestricted_access');
        });

        // Configuração especial do Cliente 0 pioneiro: Barbearia Marinho com acesso irrestrito permanente
        DB::table('barbershops')
            ->whereIn('slug', ['marinho', 'barbearia-marinho', 'marinho-centro', 'marinho-aldeota'])
            ->orWhere('name', 'like', '%Marinho%')
            ->update([
                'has_unrestricted_access' => true,
                'max_units' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table): void {
            $table->dropColumn([
                'max_units',
                'has_unrestricted_access',
                'max_professionals',
            ]);
        });
    }
};
