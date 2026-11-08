<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_board_id',
        'task_column_id',
        'title',
        'description',
        'priority',
        'sector',
        'due_date',
        'assigned_to_all',
        'sort_order',
        'created_by_user_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'assigned_to_all' => 'boolean',
            'sort_order' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class, 'task_board_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'task_column_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_card_user');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null || ($this->column && $this->column->is_done_column);
    }

    public function isOverdue(): bool
    {
        if (! $this->due_date || $this->isCompleted()) {
            return false;
        }

        return $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public function isDueToday(): bool
    {
        if (! $this->due_date || $this->isCompleted()) {
            return false;
        }

        return $this->due_date->isToday();
    }

    /**
     * @return array{total: int, completed: int, percent: int}
     */
    public function checklistProgress(): array
    {
        $items = $this->checklists;
        $total = $items->count();

        if ($total === 0) {
            return ['total' => 0, 'completed' => 0, 'percent' => 0];
        }

        $completed = $items->where('is_completed', true)->count();
        $percent = (int) round(($completed / $total) * 100);

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => $percent,
        ];
    }

    public function priorityDetails(): array
    {
        return match ($this->priority) {
            'urgent' => [
                'label' => 'Urgente',
                'badge' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 ring-1 ring-rose-500/20',
                'dot' => 'bg-rose-500',
            ],
            'high' => [
                'label' => 'Alta',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 ring-1 ring-amber-500/20',
                'dot' => 'bg-amber-500',
            ],
            'medium' => [
                'label' => 'Média',
                'badge' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 ring-1 ring-sky-500/20',
                'dot' => 'bg-sky-500',
            ],
            'low' => [
                'label' => 'Baixa',
                'badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-1 ring-gray-500/20',
                'dot' => 'bg-gray-400',
            ],
            default => [
                'label' => ucfirst($this->priority ?? 'Média'),
                'badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-1 ring-gray-500/20',
                'dot' => 'bg-gray-400',
            ],
        };
    }

    public function sectorDetails(): ?array
    {
        if (! $this->sector) {
            return null;
        }

        return match ($this->sector) {
            'pedagogico' => [
                'label' => 'Pedagógico',
                'badge' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 ring-1 ring-purple-500/20',
            ],
            'administrativo' => [
                'label' => 'Administrativo',
                'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 ring-1 ring-blue-500/20',
            ],
            'comercial' => [
                'label' => 'Comercial',
                'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 ring-1 ring-emerald-500/20',
            ],
            'financeiro' => [
                'label' => 'Financeiro',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 ring-1 ring-amber-500/20',
            ],
            'gestao' => [
                'label' => 'Gestão',
                'badge' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 ring-1 ring-indigo-500/20',
            ],
            default => [
                'label' => ucfirst($this->sector),
                'badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-1 ring-gray-500/20',
            ],
        };
    }

    public function moveToColumn(TaskColumn $newColumn, ?int $sortOrder = null): void
    {
        $this->task_column_id = $newColumn->id;
        $this->task_board_id = $newColumn->task_board_id;

        if ($sortOrder !== null) {
            $this->sort_order = $sortOrder;
        }

        if ($newColumn->is_done_column && $this->completed_at === null) {
            $this->completed_at = now();
        } elseif (! $newColumn->is_done_column) {
            $this->completed_at = null;
        }

        $this->save();
    }
}
