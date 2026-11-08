<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

#[Table('classrooms')]
#[Fillable(['school_id', 'name', 'description', 'capacity', 'opening_time', 'closing_time', 'active'])]
class Classroom extends Model
{
    use BelongsToSchool;

    protected $attributes = [
        'opening_time' => '08:00:00',
        'closing_time' => '23:00:00',
    ];

    protected static function booted(): void
    {
        static::saving(function (Classroom $classroom): void {
            $openingTime = Carbon::parse($classroom->opening_time);
            $closingTime = Carbon::parse($classroom->closing_time);

            if (
                $openingTime->minute !== 0
                || $openingTime->second !== 0
                || $closingTime->minute !== 0
                || $closingTime->second !== 0
            ) {
                throw ValidationException::withMessages([
                    'opening_time' => 'O funcionamento da sala deve usar horas cheias.',
                ]);
            }

            if ($openingTime->greaterThanOrEqualTo($closingTime)) {
                throw ValidationException::withMessages([
                    'closing_time' => 'O encerramento deve ser posterior à abertura.',
                ]);
            }

            $hasOutOfHoursSchedule = $classroom->exists
                && $classroom->isDirty(['opening_time', 'closing_time'])
                && $classroom->schedules()
                    ->where(function ($query) use ($classroom): void {
                        $query
                            ->where('start_hour', '<', $classroom->opening_time)
                            ->orWhere('end_hour', '>', $classroom->closing_time);
                    })
                    ->exists();

            if ($hasOutOfHoursSchedule) {
                throw ValidationException::withMessages([
                    'opening_time' => 'Ajuste ou arquive os horários semanais antes de alterar o funcionamento da sala.',
                ]);
            }
        });

        static::deleting(function (Classroom $classroom): void {
            if (
                StudentSchedule::withTrashed()->whereBelongsTo($classroom)->exists()
                || $classroom->sessions()->exists()
            ) {
                throw ValidationException::withMessages([
                    'classroom' => 'Esta sala está vinculada a horários registrados e não pode ser removida.',
                ]);
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StudentSchedule::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }
}
