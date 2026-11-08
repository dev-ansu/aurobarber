<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Table('school_appointments')]
#[Fillable([
    'school_id',
    'title',
    'type',
    'priority',
    'status',
    'date',
    'start_time',
    'end_time',
    'all_day',
    'location',
    'description',
    'created_by_user_id',
    'assigned_to_user_id',
    'is_shared_with_all',
    'student_id',
    'contract_id',
    'completed_at',
])]
class SchoolAppointment extends Model
{
    public const TYPE_CANCELAMENTO = 'cancelamento';

    public const TYPE_REUNIAO_PAIS = 'reuniao_pais';

    public const TYPE_REUNIAO_EQUIPE = 'reuniao_equipe';

    public const TYPE_ATENDIMENTO = 'atendimento';

    public const TYPE_EVENTO_ESCOLA = 'evento_escola';

    public const TYPE_GERAL = 'geral';

    public const PRIORITY_BAIXA = 'baixa';

    public const PRIORITY_MEDIA = 'media';

    public const PRIORITY_ALTA = 'alta';

    public const PRIORITY_URGENTE = 'urgente';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELED = 'canceled';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'all_day' => 'boolean',
            'is_shared_with_all' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function scopeForUserOrShared(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId): void {
            $q->where('assigned_to_user_id', $userId)
                ->orWhere('created_by_user_id', $userId)
                ->orWhere('is_shared_with_all', true);
        });
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_CANCELAMENTO => 'Entrevista de Cancelamento',
            self::TYPE_REUNIAO_PAIS => 'Reunião com Pais / Aluno',
            self::TYPE_REUNIAO_EQUIPE => 'Reunião de Equipe',
            self::TYPE_ATENDIMENTO => 'Atendimento Geral / Financeiro',
            self::TYPE_EVENTO_ESCOLA => 'Evento da Escola',
            default => 'Compromisso Geral',
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_CANCELAMENTO => 'danger',
            self::TYPE_REUNIAO_PAIS => 'warning',
            self::TYPE_REUNIAO_EQUIPE => 'info',
            self::TYPE_ATENDIMENTO => 'primary',
            self::TYPE_EVENTO_ESCOLA => 'success',
            default => 'gray',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENTE => 'Urgente',
            self::PRIORITY_ALTA => 'Alta',
            self::PRIORITY_MEDIA => 'Média',
            default => 'Baixa',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENTE => 'danger',
            self::PRIORITY_ALTA => 'warning',
            self::PRIORITY_MEDIA => 'info',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'Realizado',
            self::STATUS_CANCELED => 'Cancelado',
            default => 'Agendado',
        };
    }

    public function getFormattedTimeAttribute(): string
    {
        if ($this->all_day) {
            return 'Dia inteiro';
        }

        if ($this->start_time && $this->end_time) {
            return Carbon::parse($this->start_time)->format('H:i').' às '.Carbon::parse($this->end_time)->format('H:i');
        }

        if ($this->start_time) {
            return Carbon::parse($this->start_time)->format('H:i');
        }

        return 'Horário a definir';
    }
}
