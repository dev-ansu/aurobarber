<?php

use App\Models\CommercialLossReason;
use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_loss_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('commercial_leads', function (Blueprint $table) {
            $table->foreignId('commercial_loss_reason_id')->nullable()->after('status')->constrained('commercial_loss_reasons')->nullOnDelete();
            $table->text('loss_notes')->nullable()->after('commercial_loss_reason_id');
            $table->timestamp('lost_at')->nullable()->after('loss_notes');
        });

        // Popula os motivos padrão para as escolas existentes
        try {
            foreach (School::query()->withoutGlobalScopes()->get() as $school) {
                CommercialLossReason::ensureDefaultsForSchool($school->id);
            }
        } catch (\Throwable) {
            // Ignora falhas em ambientes vazios
        }
    }

    public function down(): void
    {
        Schema::table('commercial_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commercial_loss_reason_id');
            $table->dropColumn(['loss_notes', 'lost_at']);
        });

        Schema::dropIfExists('commercial_loss_reasons');
    }
};

