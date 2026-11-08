<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\CashTransaction;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Installment;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuditLogObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created', [], $this->trackedAttributes($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        $previous = $model->getPrevious();
        $this->record(
            $model,
            'updated',
            $this->trackedAttributes(Arr::only($previous, array_keys($changes))),
            $this->trackedAttributes($changes),
        );
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', $this->trackedAttributes($model->getAttributes()), []);
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored', [], $this->trackedAttributes($model->getAttributes()));
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function record(Model $model, string $event, array $before, array $after): void
    {
        $contract = match (true) {
            $model instanceof Contract => $model,
            $model instanceof ContractItem => $model->contract,
            $model instanceof ContractCourse => $model->contractItem?->contract,
            $model instanceof StudentSchedule => $model->contractItem?->contract,
            $model instanceof StudentAttendance => $model->studentSchedule?->contractItem?->contract,
            $model instanceof Installment => $model->contract,
            $model instanceof CashTransaction => $model->installment?->contract,
            default => null,
        };

        $contractId = $contract?->getKey();
        if ($contractId && ! Contract::withoutGlobalScopes()->withTrashed()->whereKey($contractId)->exists()) {
            $contractId = null;
        }

        // Higienização LGPD: Mascara campos altamente confidenciais nos logs de alteração
        $sensitiveKeys = ['cpf', 'rg', 'pix', 'password', 'token', 'secret', 'ouro_moderno_senha', 'remember_token'];
        foreach ($sensitiveKeys as $sKey) {
            if (isset($before[$sKey]) && filled($before[$sKey])) {
                $before[$sKey] = $sKey === 'cpf' ? \App\Services\DataMaskingService::maskCpf((string) $before[$sKey]) : '********';
            }
            if (isset($after[$sKey]) && filled($after[$sKey])) {
                $after[$sKey] = $sKey === 'cpf' ? \App\Services\DataMaskingService::maskCpf((string) $after[$sKey]) : '********';
            }
        }

        $changesData = [
            'before' => $before,
            'after' => $after,
        ];

        if (isset($model->auditLogReason) && filled($model->auditLogReason)) {
            $changesData['reason'] = $model->auditLogReason;
        }

        $schoolId = \Filament\Facades\Filament::getTenant()?->id
            ?? ($contract?->school_id)
            ?? ($model instanceof \App\Models\School ? $model->id : ($model->school_id ?? null));

        if (! $schoolId && $model instanceof \App\Models\Person) {
            $schoolId = $model->schools()->first()?->id;
        }

        if (! $schoolId && session()->has('current_school_id')) {
            $schoolId = session('current_school_id');
        }

        if (! $schoolId && Auth::check()) {
            $schoolId = Auth::user()?->school_id;
        }

        if (! $schoolId) {
            $schoolId = \App\Models\School::query()->value('id');
        }

        $logData = [
            'contract_id' => $contractId,
            'user_id' => Auth::id() ?? ($model->auditLogUserId ?? null),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'changes' => $changesData,
        ];

        // Verificação segura: só envia school_id se a coluna já existir no schema atual do banco
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('audit_logs', 'school_id')) {
                $logData['school_id'] = $schoolId;
            }
        } catch (\Throwable) {
            // Em caso de falha de verificação de schema, continua sem school_id
        }

        try {
            AuditLog::create($logData);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Falha ao gravar AuditLog: ' . $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function trackedAttributes(array $attributes): array
    {
        return Arr::except($attributes, [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
            'password',
        ]);
    }
}
