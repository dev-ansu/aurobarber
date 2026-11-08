<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ContractItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_id',
        'formation_id',
        'order',
        'formation_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'formation_completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ContractItem $contractItem): void {
            if ($contractItem->formation_id === null) {
                throw ValidationException::withMessages([
                    'formation_id' => 'Um contrato deve conter uma formação.',
                ]);
            }
        });

        static::deleting(function (ContractItem $contractItem): void {
            if ($contractItem->schedules()->withTrashed()->exists()) {
                throw ValidationException::withMessages([
                    'formation_id' => 'Esta formação possui horários vinculados. Arquive os horários antes de removê-la.',
                ]);
            }
        });

        static::saved(function (ContractItem $contractItem): void {
            $contractItem->contract->recalculateEstimatedEndDate();
        });

        static::deleted(function (ContractItem $contractItem): void {
            $contractItem->contract->recalculateEstimatedEndDate();
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }

    public function contractCourses(): HasMany
    {
        return $this->hasMany(ContractCourse::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StudentSchedule::class);
    }

    public function completionHistories(): HasMany
    {
        return $this->hasMany(ContractItemCompletionHistory::class);
    }

    public function syncFormationCompletion(): void
    {
        $this->refresh();
        $courses = $this->contractCourses()
            ->get(['id', 'status', 'completed_at']);
        $completedAt = $courses->isNotEmpty() && $courses->every(
            fn (ContractCourse $course): bool => $course->status === 'completed',
        )
            ? $courses->max('completed_at')
            : null;

        $currentCompletionDate = $this->formation_completed_at;

        if ($currentCompletionDate === null && $completedAt === null) {
            return;
        }

        $this->forceFill(['formation_completed_at' => $completedAt])->saveQuietly();

        if ($currentCompletionDate !== null && $completedAt !== null) {
            return;
        }

        $this->completionHistories()->create([
            'completed_at' => $completedAt,
            'source' => 'course_progress',
        ]);
    }
}
