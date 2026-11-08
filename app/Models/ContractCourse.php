<?php

namespace App\Models;

use App\Services\LmsProgressionService;
use App\Services\OuroModernoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ContractCourse extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => 'not_started',
        'progress_source' => 'manual',
    ];

    protected $table = 'contract_item_courses';

    protected $fillable = [
        'contract_item_id',
        'course_id',
        'order',
        'status',
        'is_unlocked',
        'grade',
        'completed_at',
        'progress_source',
        'lms_synced_at',
        'ouro_moderno_status',
        'ouro_moderno_status_checked_at',
        'ouro_moderno_lesson_status',
        'ouro_moderno_lesson_status_checked_at',
        'ouro_moderno_test_status',
        'ouro_moderno_test_status_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_unlocked' => 'boolean',
            'grade' => 'decimal:2',
            'completed_at' => 'datetime',
            'lms_synced_at' => 'datetime',
            'ouro_moderno_status' => 'array',
            'ouro_moderno_status_checked_at' => 'datetime',
            'ouro_moderno_lesson_status' => 'array',
            'ouro_moderno_lesson_status_checked_at' => 'datetime',
            'ouro_moderno_test_status' => 'array',
            'ouro_moderno_test_status_checked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ContractCourse $contractCourse): void {
            if (! in_array($contractCourse->status, ['not_started', 'in_progress', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Selecione um status válido para o curso.',
                ]);
            }

            if (! in_array($contractCourse->progress_source, ['manual', 'ouro_moderno', 'aurosoft'], true)) {
                throw ValidationException::withMessages([
                    'progress_source' => 'A origem do progresso do curso é inválida.',
                ]);
            }

            if ($contractCourse->status === 'completed' && $contractCourse->completed_at === null) {
                $contractCourse->completed_at = now();
            } elseif ($contractCourse->status !== 'completed') {
                $contractCourse->completed_at = null;
            }

            if ($contractCourse->grade !== null && ! is_numeric($contractCourse->grade)) {
                throw ValidationException::withMessages([
                    'grade' => 'A nota do curso deve ser numérica.',
                ]);
            }
        });

        static::saving(function (ContractCourse $contractCourse): void {
            $duplicateLinkExists = self::query()
                ->where('contract_item_id', $contractCourse->contract_item_id)
                ->where('course_id', $contractCourse->course_id)
                ->when(
                    $contractCourse->exists,
                    fn ($query) => $query->where('id', '!=', $contractCourse->getKey()),
                )
                ->exists();

            if ($duplicateLinkExists) {
                throw ValidationException::withMessages([
                    'course_id' => 'Este curso já está vinculado a esta formação do contrato.',
                ]);
            }
        });

        static::saved(function (ContractCourse $contractCourse): void {
            if (
                $contractCourse->wasRecentlyCreated
                || $contractCourse->wasChanged(['status', 'grade', 'completed_at', 'progress_source', 'order', 'is_unlocked'])
            ) {
                $contractCourse->progressHistories()->create([
                    'status' => $contractCourse->status,
                    'grade' => $contractCourse->grade,
                    'completed_at' => $contractCourse->completed_at,
                    'source' => $contractCourse->progress_source,
                ]);
            }

            $contractItem = $contractCourse->contractItem;
            $contract = $contractItem?->contract;

            if ($contractItem) {
                $contractItem->syncFormationCompletion();
            }
            if ($contract) {
                $contract->recalculateEstimatedEndDate();
                app(LmsProgressionService::class)->syncContractProgression($contract, $contractCourse);
            }
        });

        static::deleted(function (ContractCourse $contractCourse): void {
            $contractItem = $contractCourse->contractItem;
            $contract = $contractItem?->contract;

            if ($contractItem) {
                $contractItem->syncFormationCompletion();
            }
            if ($contract) {
                $contract->recalculateEstimatedEndDate();
                app(LmsProgressionService::class)->syncContractProgression($contract);
            }

            // Se for um curso da Ouro Moderno e o aluno estiver cadastrado na OM, desativa remotamente
            if ($contractCourse->course?->ouro_moderno_id && $contract?->student?->ouro_moderno_cod_int) {
                try {
                    $omService = app(OuroModernoService::class)->forSchool($contract->school_id);
                    if ($omService->isConfigured()) {
                        $omService->updateStudentCourse(
                            (int) $contract->student->ouro_moderno_cod_int,
                            (int) $contractCourse->course->ouro_moderno_id,
                            ['disponivel' => 0]
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning("Erro ao desativar curso desvinculado no LMS Ouro Moderno: {$e->getMessage()}");
                }
            }
        });
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class)->withTrashed();
    }

    public function contract(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Contract::class,
            ContractItem::class,
            'id',
            'id',
            'contract_item_id',
            'contract_id'
        );
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function progressHistories(): HasMany
    {
        return $this->hasMany(ContractCourseProgressHistory::class, 'contract_item_course_id');
    }
}
