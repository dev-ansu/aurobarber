<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractCourseProgressHistory extends Model
{
    protected $fillable = [
        'contract_item_course_id',
        'status',
        'grade',
        'completed_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function contractCourse(): BelongsTo
    {
        return $this->belongsTo(ContractCourse::class, 'contract_item_course_id')->withTrashed();
    }
}
