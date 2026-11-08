<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Services\OuroModernoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class Contract extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'contract_number',
        'school_id',
        'student_id',
        'commercial_lead_id',
        'responsible_id',
        'collaborator_id',
        'start_date',
        'end_date',
        'total_value',
        'discount_value',
        'status',
        'is_flex',
        'is_free',
        'is_ead',
        'ead_student_id',
        'ead_synced_at',
        'ead_last_progress_percentage',
        'ead_last_access_at',
        'finished_at',
        'cancelled_at',
        'cancellation_reason',
        'suspended_at',
        'suspension_expected_return_date',
        'suspension_returned_at',
        'suspension_reason',
        'observations',
        'contract_type_id',
        'created_by_id',
        'school_snapshot',
    ];

    protected $casts = [
        'is_flex' => 'boolean',
        'is_free' => 'boolean',
        'is_ead' => 'boolean',
        'ead_synced_at' => 'datetime',
        'ead_last_access_at' => 'datetime',
        'ead_last_progress_percentage' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'finished_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'suspended_at' => 'datetime',
        'suspension_expected_return_date' => 'date',
        'suspension_returned_at' => 'datetime',
        'school_snapshot' => 'array',
    ];

    protected $attributes = [
        'total_value' => 0.00,
        'discount_value' => 0.00,
        'is_free' => false,
        'is_flex' => false,
        'is_ead' => false,
    ];

    public function isFlex(): bool
    {
        return $this->is_flex === true;
    }

    public function isEad(): bool
    {
        return $this->is_ead === true;
    }

    public function isFree(): bool
    {
        return $this->is_free === true
            || ((float) $this->total_value <= 0 && $this->installments()->count() === 0);
    }

    public function scopeFree($query)
    {
        return $query->where(function ($q) {
            $q->where('is_free', true)
                ->orWhere(function ($sq) {
                    $sq->where('total_value', '<=', 0)
                        ->doesntHave('installments');
                });
        });
    }

    public function scopeFlex($query)
    {
        return $query->where('is_flex', true);
    }

    public function scopeEad($query)
    {
        return $query->where('is_ead', true);
    }

    /**
     * Verifica se o contrato possui cursos mistos (Ouro Moderno + LMS Aurosoft)
     */
    public function hasMixedCourses(): bool
    {
        $courses = $this->items()
            ->with('contractCourses.course')
            ->get()
            ->flatMap(fn ($item) => $item->contractCourses)
            ->map(fn ($cc) => $cc->course)
            ->filter();

        $hasOuroModerno = $courses->contains(fn ($c) => $c->ouro_moderno_id !== null);
        $hasAurosoftLms = $courses->contains(fn ($c) => $c->ouro_moderno_id === null);

        return $hasOuroModerno && $hasAurosoftLms;
    }

    /* =========================================================================
     *  RELACIONAMENTOS E HISTÓRICOS
     * ========================================================================= */

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ContractStatusHistory::class, 'contract_id')->orderBy('created_at', 'desc');
    }

    /* =========================================================================
     *  MÉTODOS E ATRIBUTOS AUXILIARES
     * ========================================================================= */

    /**
     * Calcula dinamicamente o tempo de permanência do aluno em meses
     */
    public function durationInMonths(): Attribute
    {
        return Attribute::make(
            get: function () {
                $dataInicio = $this->start_date;
                if (! $dataInicio) {
                    return 0;
                }

                $dataFim = match ($this->status) {
                    'finished' => $this->finished_at ?? $this->end_date ?? now(),
                    'canceled' => $this->cancelled_at ?? now(),
                    default => now(),
                };

                return (int) $dataInicio->diffInMonths($dataFim);
            }
        );
    }

    /**
     * Retorna os dados da escola combinando o snapshot do contrato com o cadastro atual da escola.
     * Caso o snapshot possua campos nulos ou incompletos (ex: cidade/UF vazios),
     * estes são supridos pelo cadastro atual da escola no banco.
     *
     * @return array<string, mixed>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function getResolvedSchoolAttribute(): array
    {
        $liveSchool = $this->school?->toContractSnapshot()
            ?? (class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant()->toContractSnapshot() : null)
            ?? School::getActiveSchool()?->toContractSnapshot()
            ?? School::query()->with('partners')->first()?->toContractSnapshot()
            ?? [];
        $snapshot = $this->school_snapshot ?? [];

        return array_merge(
            $liveSchool,
            array_filter($snapshot, fn ($v) => filled($v))
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Contract $contract): void {
            $contract->created_by_id ??= Auth::id();
            $contract->school_id ??= (class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant()->id : null)
                ?? School::getActiveSchool()?->id;
            $contract->school_snapshot ??= ($contract->school_id ? School::find($contract->school_id) : School::getActiveSchool())?->toContractSnapshot();

            if (empty($contract->contract_type_id)) {
                $activeContractType = ContractType::getActiveForType(ContractType::TYPE_CONTRACT);

                if ($activeContractType) {
                    $contract->contract_type_id = $activeContractType->id;
                }
            }
        });

        static::deleting(function (Contract $contract): void {
            $hasPaid = $contract->installments()
                ->where(function (Builder $q): void {
                    $q->where('status', 'paid')
                        ->orWhereNotNull('paid_at')
                        ->orWhere('amount_paid', '>', 0);
                })
                ->exists();

            if ($hasPaid) {
                throw new \RuntimeException("Não é possível excluir este contrato pois ele possui parcela(s) com pagamento já registrado no caixa/financeiro. Para encerrar o vínculo, cancele o contrato.");
            }

            // Exclui individualmente as parcelas para disparar os eventos do Model e registrar na auditoria
            foreach ($contract->installments()->get() as $installment) {
                $installment->delete();
            }
        });

        static::saved(function (Contract $contract): void {
            if ($contract->wasRecentlyCreated || $contract->wasChanged('start_date')) {
                $contract->recalculateEstimatedEndDate();
            }

            if ($contract->wasRecentlyCreated || $contract->wasChanged(['commercial_lead_id', 'status', 'student_id'])) {
                $previousLeadId = $contract->wasRecentlyCreated
                    ? null
                    : $contract->getOriginal('commercial_lead_id');

                if ($previousLeadId && (int) $previousLeadId !== (int) $contract->commercial_lead_id) {
                    $previousLead = CommercialLead::query()->find($previousLeadId);

                    if ($previousLead && ! $previousLead->contract()->exists()) {
                        $previousLead->forceFill([
                            'status' => 'contacted',
                            'converted_at' => null,
                        ])->saveQuietly();

                        CommercialVisit::query()
                            ->where('commercial_lead_id', $previousLead->getKey())
                            ->update(['contract_id' => null]);
                    }
                }

                if ($contract->commercial_lead_id) {
                    $lead = CommercialLead::query()->find($contract->commercial_lead_id);

                    if ($lead) {
                        $isEnrolled = $contract->status === 'active'
                            || ($contract->status !== 'draft' && $lead->converted_at !== null);

                        $lead->forceFill([
                            'person_id' => $lead->person_id ?? $contract->student_id,
                            'status' => $isEnrolled ? 'enrolled' : 'proposal',
                            'converted_at' => $isEnrolled ? ($lead->converted_at ?? now()) : null,
                        ])->saveQuietly();

                        if ($isEnrolled) {
                            CommercialVisit::query()
                                ->where('commercial_lead_id', $lead->getKey())
                                ->update(['contract_id' => $contract->getKey()]);
                        }
                    }
                }
            }

            if ($contract->wasChanged('status') || ($contract->wasRecentlyCreated && $contract->status === 'active')) {
                $contract->syncStudentOuroModernoStatus();
            }
        });

        static::deleted(function (Contract $contract): void {
            $contract->syncStudentOuroModernoStatus();

            if (! $contract->commercial_lead_id) {
                return;
            }

            $lead = CommercialLead::query()->find($contract->commercial_lead_id);

            if ($lead && ! $lead->contract()->exists()) {
                $lead->forceFill([
                    'status' => 'contacted',
                    'converted_at' => null,
                ])->saveQuietly();

                CommercialVisit::query()
                    ->where('commercial_lead_id', $lead->getKey())
                    ->update(['contract_id' => null]);
            }
        });

        static::restored(function (Contract $contract): void {
            $contract->syncStudentOuroModernoStatus();
        });
    }

    /**
     * Sincroniza o status ativo ('S' / 'N') do aluno no LMS Ouro Moderno
     * com base na existência de contratos ativos no sistema.
     */
    public function syncStudentOuroModernoStatus(): void
    {
        $student = $this->student ?? Person::query()->find($this->student_id);

        if (! $student || ! $student->ouro_moderno_cod_int) {
            return;
        }

        $ouroModerno = app(OuroModernoService::class)->forSchool($this->school_id);

        if (! $ouroModerno->isConfigured()) {
            return;
        }

        $hasActiveContract = self::query()
            ->where('student_id', $student->getKey())
            ->where('status', 'active')
            ->exists();

        $activeFlag = $hasActiveContract ? 'S' : 'N';

        try {
            $ouroModerno->updateStudent((int) $student->ouro_moderno_cod_int, [
                'ativo' => $activeFlag,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Verifica se o contrato possui cursos ativos vinculados à Ouro Moderno
     */
    public function hasActiveOuroModernoCourse(?ContractItem $specificItem = null): bool
    {
        if ($specificItem) {
            $hasCourses = $specificItem->contractCourses()->exists()
                || ($specificItem->formation && $specificItem->formation->courses()->exists());

            if ($hasCourses) {
                return $specificItem->contractCourses()
                    ->where('status', '!=', 'completed')
                    ->whereHas('course', fn (Builder $q) => $q->whereNotNull('ouro_moderno_id'))
                    ->exists()
                    || (
                        ! $specificItem->contractCourses()->exists()
                        && $specificItem->formation
                        && $specificItem->formation->courses()->whereNotNull('ouro_moderno_id')->exists()
                    );
            }
        }

        return $this->items()->where(function (Builder $itemQuery) {
            $itemQuery->whereHas('contractCourses', function (Builder $ccQuery) {
                $ccQuery->where('status', '!=', 'completed')
                    ->whereHas('course', fn (Builder $cQuery) => $cQuery->whereNotNull('ouro_moderno_id'));
            })
                ->orWhere(function (Builder $formationQuery) {
                    $formationQuery->whereDoesntHave('contractCourses')
                        ->whereHas('formation.courses', fn (Builder $cQuery) => $cQuery->whereNotNull('ouro_moderno_id'));
                });
        })->exists();
    }

    /**
     * Verifica se o contrato possui cursos ativos cadastrados localmente (Aurosoft LMS)
     */
    public function hasActiveAurosoftLmsCourse(?ContractItem $specificItem = null): bool
    {
        if ($specificItem) {
            $hasCourses = $specificItem->contractCourses()->exists()
                || ($specificItem->formation && $specificItem->formation->courses()->exists());

            if ($hasCourses) {
                return $specificItem->contractCourses()
                    ->where('status', '!=', 'completed')
                    ->whereHas('course', fn (Builder $q) => $q->whereNull('ouro_moderno_id'))
                    ->exists()
                    || (
                        ! $specificItem->contractCourses()->exists()
                        && $specificItem->formation
                        && $specificItem->formation->courses()->whereNull('ouro_moderno_id')->exists()
                    );
            }
        }

        return $this->items()->where(function (Builder $itemQuery) {
            $itemQuery->whereHas('contractCourses', function (Builder $ccQuery) {
                $ccQuery->where('status', '!=', 'completed')
                    ->whereHas('course', fn (Builder $cQuery) => $cQuery->whereNull('ouro_moderno_id'));
            })
                ->orWhere(function (Builder $formationQuery) {
                    $formationQuery->whereDoesntHave('contractCourses')
                        ->whereHas('formation.courses', fn (Builder $cQuery) => $cQuery->whereNull('ouro_moderno_id'));
                });
        })->exists();
    }

    /**
     * Verifica se o contrato e aluno foram migrados / sincronizados com a Ouro Moderno
     */
    public function isMigratedToOuroModerno(): bool
    {
        $student = $this->student;
        if (! $student || ! $student->isMigratedToOuroModerno()) {
            return false;
        }

        return true;
    }

    /**
     * Registra a transição de status no histórico com o usuário logado
     */
    public function changeStatus(string $newStatus, ?string $reason = null): void
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => $newStatus,
            'finished_at' => $newStatus === 'finished' ? now() : $this->finished_at,
            'cancelled_at' => $newStatus === 'canceled' ? now() : $this->cancelled_at,
            'cancellation_reason' => $newStatus === 'canceled' ? $reason : $this->cancellation_reason,
        ]);

        $this->statusHistories()->create([
            'user_id' => Auth::id(),
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
        ]);
    }

    /**
     * Reativa o contrato (reversão de status após conclusão, cancelamento ou retorno de suspensão)
     */
    public function reactivate(?string $reason = null): void
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => 'active',
            'finished_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'suspension_returned_at' => $oldStatus === 'suspended' ? now() : $this->suspension_returned_at,
        ]);

        $prefix = match ($oldStatus) {
            'finished' => 'Reversão de status (Conclusão desfeita)',
            'canceled' => 'Reversão de status (Cancelamento desfeito)',
            'suspended' => 'Reversão de status (Retorno de trancamento)',
            default => 'Reversão de status (Reativação de contrato)',
        };

        $fullReason = filled($reason) ? "{$prefix}: {$reason}" : $prefix;

        $this->statusHistories()->create([
            'user_id' => Auth::id(),
            'from_status' => $oldStatus,
            'to_status' => 'active',
            'reason' => $fullReason,
        ]);
    }

    public function recalculateEstimatedEndDate(): void
    {
        $items = $this->items()
            ->with(['contractCourses.course', 'schedules'])
            ->get();

        if ($this->start_date === null || $items->isEmpty()) {
            $this->saveEstimatedEndDate(null);

            return;
        }

        $estimatedEndDates = [];
        $totalWorkload = 0;
        $totalScheduledHoursPerWeek = 0;

        foreach ($items as $item) {
            if ($item->formation_completed_at !== null) {
                $estimatedEndDates[] = $item->formation_completed_at->toDateString();

                continue;
            }

            $courses = $item->contractCourses;

            if ($courses->isEmpty() || $courses->contains(fn ($contractCourse) => $contractCourse->course->workload === null)) {
                $this->saveEstimatedEndDate(null);

                return;
            }

            $scheduledHoursPerWeek = $item->schedules->sum(function (StudentSchedule $schedule): float {
                $start = Carbon::parse($schedule->start_hour);
                $end = Carbon::parse($schedule->end_hour);

                return $start->diffInSeconds($end) / 3600;
            });

            if ($scheduledHoursPerWeek <= 0) {
                $this->saveEstimatedEndDate(null);

                return;
            }

            $totalWorkload += $courses->sum(fn ($contractCourse): int => $contractCourse->course->workload);
            $totalScheduledHoursPerWeek += $scheduledHoursPerWeek;
        }

        if ($totalWorkload > 0) {
            if ($totalScheduledHoursPerWeek <= 0) {
                $this->saveEstimatedEndDate(null);

                return;
            }

            $weeks = max(1, (int) ceil($totalWorkload / $totalScheduledHoursPerWeek));
            $estimatedEndDates[] = $this->start_date->copy()->addWeeks($weeks)->toDateString();
        }

        $this->saveEstimatedEndDate($estimatedEndDates === [] ? null : max($estimatedEndDates));
    }

    private function saveEstimatedEndDate(?string $estimatedEndDate): void
    {
        if ($this->end_date?->toDateString() === $estimatedEndDate) {
            return;
        }

        $this->forceFill(['end_date' => $estimatedEndDate])->saveQuietly();
    }

    // O Aluno é uma pessoa física (vinculado à tabela people)
    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function commercialLead(): BelongsTo
    {
        return $this->belongsTo(CommercialLead::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ContractHistory::class)->latest('occurred_at');
    }

    // O Responsável Financeiro
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Responsible::class, 'responsible_id');
    }

    // O Colaborador que vendeu o contrato
    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class, 'collaborator_id');
    }

    // Os cursos ou formações vinculados a este contrato
    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(
            StudentSchedule::class,
            ContractItem::class,
            'contract_id',
            'contract_item_id',
            'id',
            'id',
        );
    }

    // As parcelas/mensalidades/taxas deste contrato
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'contract_id')
            ->orderBy('installment_number', 'asc')
            ->orderBy('due_date', 'asc');
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->latest();
    }

    public function debtAgreements(): HasMany
    {
        return $this->hasMany(DebtAgreement::class)->latest();
    }

    public function lmsLessonProgress(): HasMany
    {
        return $this->hasMany(LmsStudentLessonProgress::class, 'contract_id');
    }

    public function makeupClasses(): HasMany
    {
        return $this->hasMany(StudentMakeupClass::class, 'contract_id');
    }

    /**
     * Verifica se o contrato possui parcelas em atraso (vencidas e com saldo devedor)
     */
    public function hasOverdueInstallments(): bool
    {
        return $this->installments()
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', today())
            ->whereRaw('(amount - discount + fine - COALESCE(amount_paid, 0)) > 0')
            ->exists();
    }
}
