<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('barbershop_id')->nullable()->after('id')->constrained('barbershops')->nullOnDelete();
            $table->string('role')->default('barber')->after('password'); // master_admin, owner, barber, receptionist
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_url')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['barbershop_id']);
            $table->dropColumn(['barbershop_id', 'role', 'phone', 'avatar_url', 'is_active']);
        });
    }
};
