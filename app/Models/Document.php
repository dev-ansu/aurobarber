<?php

namespace App\Models;

use App\Enums\DocumentTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('documents')]
#[Fillable(['person_id', 'type', 'value', 'value_hash', 'is_primary'])]
class Document extends Model
{
    protected $casts = [
        'type' => DocumentTypeEnum::class,
    ];

    public static function hashValue(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $clean = preg_replace('/[^a-zA-Z0-9]/', '', (string) $value);
        if ($clean === '') {
            return null;
        }

        return hash_hmac('sha256', strtolower($clean), (string) config('app.key'));
    }

    protected function value(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                try {
                    return \Illuminate\Support\Facades\Crypt::decryptString($value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    return $value;
                } catch (\Throwable) {
                    return $value;
                }
            },
            set: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                return \Illuminate\Support\Facades\Crypt::encryptString(trim($value));
            }
        );
    }

    public function scopeWhereDocumentValue(Builder $query, ?string $value): Builder
    {
        if (blank($value)) {
            return $query;
        }

        $clean = preg_replace('/[^a-zA-Z0-9]/', '', (string) $value);
        $hash = static::hashValue($clean);

        return $query->where(function (Builder $q) use ($value, $clean, $hash): void {
            if ($hash) {
                $q->where('value_hash', $hash);
            }
            if ($clean !== '') {
                $q->orWhere('value', $clean);
            }
            $q->orWhere('value', $value);
        });
    }

    protected static function booted(): void
    {
        static::saving(function (Document $document) {
            if (filled($document->value)) {
                $document->value_hash = static::hashValue($document->value);
            } else {
                $document->value_hash = null;
            }

            if ($document->is_primary) {
                Document::query()->where('person_id', $document->person_id)
                    ->where('id', '!=', $document->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
