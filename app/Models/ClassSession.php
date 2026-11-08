<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['classroom_id', 'date', 'weekday', 'start_hour', 'end_hour'])]
class ClassSession extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'weekday' => 'integer',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function formattedStartHour(): string
    {
        return Carbon::parse($this->start_hour)->format('H:i');
    }

    public function formattedEndHour(): string
    {
        return Carbon::parse($this->end_hour)->format('H:i');
    }
}
