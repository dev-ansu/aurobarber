<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Throwable;

#[Fillable(['school_id', 'provider', 'base_url', 'token', 'updated_by'])]
class IntegrationSetting extends Model
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    protected function token(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    // Chave APP_KEY diferente da origem do backup ou token corrompido:
                    // retorna nulo em vez de quebrar a aplicação com 500
                    return null;
                } catch (Throwable) {
                    return null;
                }
            },
            set: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                return Crypt::encryptString(trim($value));
            }
        );
    }
}
