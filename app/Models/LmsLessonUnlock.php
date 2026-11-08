<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsLessonUnlock extends Model
{
    protected $table = 'lms_lesson_unlocks';

    protected $fillable = [
        'contract_id',
        'lesson_id',
        'unlocked_by_user_id',
        'unlocked_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'unlocked_date' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class);
    }

    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by_user_id');
    }
}
