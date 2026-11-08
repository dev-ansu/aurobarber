<?php

namespace App\Models;

use App\Enums\ContactTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('contacts')]
#[Fillable(['person_id', 'type', 'value', 'is_primary'])]
class Contact extends Model
{
    protected $casts = [
        'type' => ContactTypeEnum::class,
    ];

    protected static function booted(): void
    {
        // This event listener ensures that when a contact is saved and marked as primary, all other contacts for the same person are updated to not be primary.
        static::saving(function (Contact $contact) {
            if ($contact->is_primary) {
                Contact::query()->where('person_id', $contact->person_id) // Filter contacts belonging to the same person
                    ->where('id', '!=', $contact->id) // Exclude the current contact from the update
                    ->where('is_primary', true) // Filter only contacts that are currently marked as primary
                    ->update(['is_primary' => false]); // Set is_primary to false for all other contacts of the same person
            }
        });

    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
