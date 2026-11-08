<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->boolean('is_ead')->default(false)->after('is_flex');
            $table->string('ead_student_id', 50)->nullable()->after('is_ead');
            $table->timestamp('ead_synced_at')->nullable()->after('ead_student_id');
            $table->decimal('ead_last_progress_percentage', 5, 2)->default(0)->after('ead_synced_at');
            $table->timestamp('ead_last_access_at')->nullable()->after('ead_last_progress_percentage');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('ouro_moderno_ead_enabled')->default(false);
            $table->text('ouro_moderno_ead_api_key')->nullable();
            $table->string('ouro_moderno_ead_unidade_id', 50)->nullable();
            $table->string('ouro_moderno_ead_unidade_nome', 150)->nullable();
        });

        // Permissões Shield
        $permissions = [
            'ViewAny:OuroModernoEad',
            'Sync:OuroModernoEad',
            'Enroll:OuroModernoEad',
            'AccessLogin:OuroModernoEad',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'is_ead',
                'ead_student_id',
                'ead_synced_at',
                'ead_last_progress_percentage',
                'ead_last_access_at',
            ]);
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'ouro_moderno_ead_enabled',
                'ouro_moderno_ead_api_key',
                'ouro_moderno_ead_unidade_id',
                'ouro_moderno_ead_unidade_nome',
            ]);
        });

        $permissions = [
            'ViewAny:OuroModernoEad',
            'Sync:OuroModernoEad',
            'Enroll:OuroModernoEad',
            'AccessLogin:OuroModernoEad',
        ];

        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->where('guard_name', 'web')->delete();
        }
    }
};

