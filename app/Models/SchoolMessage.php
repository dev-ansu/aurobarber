<?php

namespace App\Models;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SchoolMessage extends Model
{
    protected $fillable = [
        'sender_school_id',
        'recipient_school_id',
        'sender_user_id',
        'recipient_user_id',
        'parent_id',
        'contract_id',
        'category',
        'subject',
        'body',
        'read_at',
        'read_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Criptografia ponta-a-ponta em repouso (AES-256-CBC via APP_KEY)
     * com fallback seguro para mensagens legadas em texto plano.
     */
    protected function body(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): string {
                if (blank($value)) {
                    return '';
                }

                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable) {
                    return $value;
                }
            },
            set: function (?string $value): string {
                if (blank($value)) {
                    return '';
                }

                return Crypt::encryptString($value);
            }
        );
    }

    protected static function booted(): void
    {
        static::created(function (SchoolMessage $message): void {
            $message->dispatchDatabaseNotifications();
        });
    }

    public function dispatchDatabaseNotifications(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $senderName = $this->sender?->name ?? 'Colaborador';
        $senderSchoolName = $this->senderSchool?->getFilamentName() ?? 'Rede';
        $targetSchoolId = $this->recipient_school_id;

        if ($this->recipient_user_id) {
            // Conversa direta 1-a-1 entre colaboradores
            $recipients = User::query()->whereKey($this->recipient_user_id)->get();
        } else {
            // Conversa entre Unidades Escolares ou Comunicado Geral da Rede (apenas colaboradores com Usuário ou super_admin)
            $recipientsQuery = User::query()
                ->where(function (Builder $staffFilter) {
                    $staffFilter->whereHas('collaborator', fn (Builder $cq) => $cq->where('active', true))
                        ->orWhereHas('roles', fn (Builder $rq) => $rq->where('name', 'super_admin'));
                })
                ->where(function (Builder $q) use ($targetSchoolId) {
                    if ($targetSchoolId) {
                        $q->whereHas('schools', fn (Builder $sq) => $sq->where('schools.id', $targetSchoolId))
                            ->orWhereHas('roles', fn (Builder $rq) => $rq->where('name', 'super_admin'));
                    } else {
                        if ($this->sender_school_id) {
                            $q->whereHas('schools', fn (Builder $sq) => $sq->where('schools.id', '!=', $this->sender_school_id))
                                ->orWhereHas('roles', fn (Builder $rq) => $rq->where('name', 'super_admin'));
                        }
                    }
                });

            $recipients = $recipientsQuery->get();

            if ($this->sender_user_id && $this->category !== 'transfer') {
                $others = $recipients->where('id', '!=', $this->sender_user_id);
                if ($others->isNotEmpty()) {
                    $recipients = $others;
                }
            }
        }

        if ($recipients->isEmpty()) {
            return;
        }

        $title = match (true) {
            $this->category === 'transfer' => $this->subject,
            $this->recipient_user_id !== null => "💬 Mensagem de {$senderName} ({$senderSchoolName})",
            $this->parent_id !== null => "💬 Nova resposta ({$senderSchoolName}): {$this->subject}",
            $this->isBroadcast() => "📢 Comunicado da Rede ({$senderSchoolName}): {$this->subject}",
            default => "✉️ Nova mensagem de {$senderSchoolName}: {$this->subject}",
        };

        $icon = match ($this->category) {
            'transfer' => 'heroicon-o-arrows-right-left',
            'urgent' => 'heroicon-o-exclamation-triangle',
            'pedagogical' => 'heroicon-o-academic-cap',
            'financial' => 'heroicon-o-banknotes',
            default => 'heroicon-o-chat-bubble-left-right',
        };

        $color = match ($this->category) {
            'transfer' => 'info',
            'urgent' => 'danger',
            'pedagogical' => 'primary',
            'financial' => 'warning',
            default => 'success',
        };

        $cleanBody = Str::limit(
            preg_replace('/\s+/', ' ', str_replace(['**', '📋', '- '], ['', '', '• '], strip_tags($this->body))),
            150
        );

        $urlSchoolId = $targetSchoolId
            ?: (School::query()->withoutGlobalScopes()->where('id', '!=', (int) $this->sender_school_id)->value('id')
                ?? $this->sender_school_id
                ?? 1);

        Notification::make()
            ->title($title)
            ->body($cleanBody)
            ->icon($icon)
            ->iconColor($color)
            ->actions([
                Action::make('open_messenger')
                    ->label('Abrir Chat da Rede')
                    ->url(url("/admin/{$urlSchoolId}/comunicacao-da-rede"))
                    ->markAsRead(),
            ])
            ->sendToDatabase($recipients);
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'general' => '📌 Comunicado Geral',
            'chat' => '💬 Chat Direto',
            'transfer' => '🔄 Transferência de Aluno / Lead',
            'pedagogical' => '🎓 Pedagógico / Acadêmico',
            'financial' => '💰 Financeiro / Cobrança',
            'urgent' => '🚨 Urgente / Diretoria',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categories()[$this->category] ?? '📌 Comunicado Geral';
    }

    public function senderSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'sender_school_id');
    }

    public function recipientSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'recipient_school_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function readBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by_user_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function isBroadcast(): bool
    {
        return $this->recipient_school_id === null && $this->recipient_user_id === null;
    }

    public function markAsRead(?int $userId = null): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->update([
            'read_at' => now(),
            'read_by_user_id' => $userId ?? auth()->id(),
        ]);
    }

    public function scopeRootMessages(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForSchoolInbox(Builder $query, int $schoolId, ?int $userId = null): Builder
    {
        return $query->where(function (Builder $q) use ($schoolId, $userId) {
            $q->where(function (Builder $sq) use ($schoolId) {
                $sq->whereNull('recipient_user_id')
                    ->where(function (Builder $inner) use ($schoolId) {
                        $inner->where('recipient_school_id', $schoolId)
                            ->orWhere(function (Builder $broadcastQuery) use ($schoolId) {
                                $broadcastQuery->whereNull('recipient_school_id')
                                    ->where(function (Builder $senderQuery) use ($schoolId) {
                                        $senderQuery->whereNull('sender_school_id')
                                            ->orWhere('sender_school_id', '!=', $schoolId);
                                    });
                            });
                    });
            });

            if ($userId) {
                $q->orWhere('recipient_user_id', $userId);
            }
        });
    }
}
