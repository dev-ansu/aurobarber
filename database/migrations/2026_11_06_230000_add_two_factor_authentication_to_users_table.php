<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'two_factor_enabled')) {
                    $table->boolean('two_factor_enabled')->default(false)->after('password');
                }
                if (! Schema::hasColumn('users', 'two_factor_code')) {
                    $table->string('two_factor_code', 10)->nullable()->after('two_factor_enabled');
                }
                if (! Schema::hasColumn('users', 'two_factor_expires_at')) {
                    $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
                }
            });
        }

        if (! Schema::hasTable('user_trusted_devices')) {
            Schema::create('user_trusted_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_token_hash', 64)->index();
                $table->string('name')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_trusted_devices');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columns = ['two_factor_enabled', 'two_factor_code', 'two_factor_expires_at'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
