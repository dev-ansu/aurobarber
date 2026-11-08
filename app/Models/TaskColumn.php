<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_board_id',
        'name',
        'color',
        'sort_order',
        'is_done_column',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_done_column' => 'boolean',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class, 'task_board_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(TaskCard::class)->orderBy('sort_order')->orderBy('id');
    }
}
