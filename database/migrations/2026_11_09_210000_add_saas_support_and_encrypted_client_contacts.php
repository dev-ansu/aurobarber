<?php

use App\Services\SensitiveClientData;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('barbershop_clients', function (Blueprint $table): void {
            $table->dropIndex(['phone']);
            $table->text('phone')->change();
            $table->string('phone_hash', 64)->nullable()->index();
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->text('client_phone')->change();
            $table->text('client_email')->nullable()->change();
            $table->string('client_phone_hash', 64)->nullable()->index();
            $table->string('client_email_hash', 64)->nullable()->index();
        });

        Schema::create('sensitive_data_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('barbershop_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type');
            $table->string('subject_id');
            $table->json('fields');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['barbershop_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Permission::findOrCreate('View:SensitiveClientData', 'web');
    }

    public function down(): void
    {
        $this->decryptColumn('barbershop_clients', 'phone');
        $this->decryptColumn('appointments', 'client_phone');
        $this->decryptColumn('appointments', 'client_email');

        Schema::dropIfExists('sensitive_data_access_logs');

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['client_phone_hash']);
            $table->dropIndex(['client_email_hash']);
            $table->dropColumn(['client_phone_hash', 'client_email_hash']);
            $table->string('client_phone')->nullable()->change();
            $table->string('client_email')->nullable()->change();
        });

        Schema::table('barbershop_clients', function (Blueprint $table): void {
            $table->dropIndex(['phone_hash']);
            $table->dropColumn('phone_hash');
            $table->string('phone', 30)->change();
            $table->index('phone');
        });

        Schema::dropIfExists('saas_settings');

        Permission::query()
            ->where('name', 'View:SensitiveClientData')
            ->where('guard_name', 'web')
            ->delete();
    }

    private function decryptColumn(string $table, string $column): void
    {
        DB::table($table)
            ->select(['id', $column])
            ->orderBy('id')
            ->chunkById(200, function ($records) use ($table, $column): void {
                foreach ($records as $record) {
                    $value = SensitiveClientData::decrypt($record->{$column});

                    if ($value !== $record->{$column}) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update([$column => $value]);
                    }
                }
            });
    }
};
