<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $table = 'public.support_tickets';

    protected $fillable = [
        'protocol',
        'type',
        'category',
        'priority',
        'status',
        'school_id',
        'contract_id',
        'student_id',
        'opened_by_user_id',
        'assigned_to_user_id',
        'subject',
        'description',
        'exception_class',
        'error_message',
        'error_file_line',
        'error_hash',
        'request_url',
        'request_method',
        'request_payload',
        'stack_trace',
        'occurrences_count',
        'last_occurred_at',
        'resolved_at',
        'resolution_notes',
        'resolved_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'occurrences_count' => 'integer',
            'last_occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            if (empty($ticket->protocol)) {
                $prefix = $ticket->type === 'system_incident' ? 'INC' : 'TKT';
                $year = date('Y');
                $random = strtoupper(substr(uniqid(), -5));
                $ticket->protocol = "{$prefix}-{$year}-{$random}";
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at', 'asc');
    }

    // Scopes
    public function scopeStudentSupport(Builder $query): Builder
    {
        return $query->where('type', 'student_support');
    }

    public function scopeSchoolSupport(Builder $query): Builder
    {
        return $query->where('type', 'school_support');
    }

    public function scopeSystemIncidents(Builder $query): Builder
    {
        return $query->where('type', 'system_incident');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'in_progress', 'waiting_user']);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Aberto',
            'in_progress' => 'Em Atendimento',
            'waiting_user' => 'Aguardando Resposta',
            'resolved' => 'Resolvido',
            'closed' => 'Encerrado',
            default => ucfirst($this->status),
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'critical' => 'Urgente / Crítica',
            default => ucfirst($this->priority),
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'pedagogico' => 'Dúvida Pedagógica / Aulas',
            'financeiro' => 'Financeiro / Pagamento',
            'secretaria' => 'Secretaria / Documentos',
            'sistema_bug' => 'Problema Técnico no Sistema',
            'ouro_moderno' => 'Laboratório Interativo',
            'duvida_saas' => 'Suporte ao Sistema SaaS',
            default => ucfirst(str_replace('_', ' ', $this->category)),
        };
    }
}
