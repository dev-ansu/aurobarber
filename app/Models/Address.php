<?php

namespace App\Models;

use App\Enums\AddressTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('addresses')]
#[Fillable(['person_id', 'type', 'street', 'number', 'complement', 'neighborhood', 'city_id', 'zip_code', 'is_primary'])]
class Address extends Model
{
    protected $casts = [
        'type' => AddressTypeEnum::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (Address $address) {
            if ($address->is_primary) {
                Address::query()->where('person_id', $address->person_id)
                    ->where('id', '!=', $address->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
