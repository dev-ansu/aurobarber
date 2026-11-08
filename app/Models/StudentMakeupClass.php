<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class StudentMakeupClass extends Model
{
    protected $fillable = [
        'contract_id',
        'student_attendance_id',
        'classroom_id',
        'scheduled_by_user_id',
        'makeup_date',
        'start_hour',
        'end_hour',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'makeup_date' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(StudentAttendance::class, 'student_attendance_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by_user_id');
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
