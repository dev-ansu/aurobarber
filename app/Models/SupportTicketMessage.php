<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketMessage extends Model
{
    protected $table = 'public.support_ticket_messages';

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'person_id',
        'author_name',
        'author_type',
        'message',
        'is_internal_note',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'is_internal_note' => 'boolean',
            'attachments' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
