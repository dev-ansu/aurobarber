<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'name',
    'person_id',
    'city_id',
    'neighborhood',
    'birth_date',
    'gender',
    'email',
    'phone',
    'responsible_name',
    'responsible_phone',
    'responsible_relationship',
    'source',
    'formation_id',
    'collaborator_id',
    'status',
    'commercial_loss_reason_id',
    'loss_notes',
    'lost_at',
    'next_follow_up_at',
    'converted_at',
    'notes',
])]
class CommercialLead extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => GenderEnum::class,
            'responsible_relationship' => RelationshipEnum::class,
            'next_follow_up_at' => 'datetime',
            'converted_at' => 'datetime',
            'lost_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            'new' => 'Novo contato',
            'contacted' => 'Em contato',
            'visit_scheduled' => 'Visita agendada',
            'visited' => 'Visita realizada',
            'proposal' => 'Proposta apresentada',
            'enrolled' => 'Contrato assinado / Matriculado',
            'lost' => 'Perdido / Desistência',
        ];
    }

    public static function sources(): array
    {
        return [
            'website' => 'Site / Vitrine Online',
            'social_media' => 'Redes Sociais (Instagram/Facebook)',
            'referral' => 'Indicação',
            'walk_in' => 'Visita espontânea',
            'phone' => 'Telefone / WhatsApp',
            'event' => 'Evento / Ação externa',
            'other' => 'Outro',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(CommercialVisit::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    public function lossReason(): BelongsTo
    {
        return $this->belongsTo(CommercialLossReason::class, 'commercial_loss_reason_id');
    }
}
