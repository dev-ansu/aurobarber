<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_card_id',
        'title',
        'is_completed',
        'sort_order',
        'completed_at',
        'completed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'sort_order' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(TaskCard::class, 'task_card_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function toggleComplete(?int $userId = null): void
    {
        $this->is_completed = ! $this->is_completed;
        $this->completed_at = $this->is_completed ? now() : null;
        $this->completed_by_user_id = $this->is_completed ? ($userId ?: auth()->id()) : null;
        $this->save();
    }
}
