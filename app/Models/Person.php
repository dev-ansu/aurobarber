<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('people')]
#[Fillable([
    'name', 'birth_date', 'social_name', 'email', 'cpf', 'cpf_hash', 'gender',
    'ouro_moderno_cod_int', 'ouro_moderno_usuario', 'ouro_moderno_senha', 'ouro_moderno_registered_at',
])]
class Person extends Model
{
    use HasFactory;

    protected $casts = [
        'birth_date' => 'date',
        'gender' => GenderEnum::class,
        'ouro_moderno_registered_at' => 'datetime',
    ];

    public static function hashCpf(?string $cpf): ?string
    {
        if (blank($cpf)) {
            return null;
        }

        $clean = preg_replace('/\D/', '', (string) $cpf);
        if ($clean === '') {
            return null;
        }

        return hash_hmac('sha256', $clean, (string) config('app.key'));
    }

    protected function cpf(): \Illuminate\Database\Eloquent\Casts\Attribute
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

                $clean = preg_replace('/\D/', '', (string) $value);
                if ($clean === '') {
                    return null;
                }

                return \Illuminate\Support\Facades\Crypt::encryptString($clean);
            }
        );
    }

    public function scopeWhereCpf(Builder $query, ?string $cpf): Builder
    {
        if (blank($cpf)) {
            return $query;
        }

        $clean = preg_replace('/\D/', '', (string) $cpf);
        $hash = static::hashCpf($clean);

        return $query->where(function (Builder $q) use ($cpf, $clean, $hash): void {
            if ($hash) {
                $q->where('cpf_hash', $hash);
            }
            if ($clean !== '') {
                $q->orWhere('cpf', $clean);
            }
            $q->orWhere('cpf', $cpf);
        });
    }

    public function scopeOrWhereCpf(Builder $query, ?string $cpf): Builder
    {
        if (blank($cpf)) {
            return $query;
        }

        $clean = preg_replace('/\D/', '', (string) $cpf);
        $hash = static::hashCpf($clean);

        return $query->orWhere(function (Builder $q) use ($cpf, $clean, $hash): void {
            if ($hash) {
                $q->where('cpf_hash', $hash);
            }
            if ($clean !== '') {
                $q->orWhere('cpf', $clean);
            }
            $q->orWhere('cpf', $cpf);
        });
    }

    protected function ouroModernoSenha(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                try {
                    return \Illuminate\Support\Facades\Crypt::decryptString($value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    return null;
                } catch (\Throwable) {
                    return null;
                }
            },
            set: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                return \Illuminate\Support\Facades\Crypt::encryptString($value);
            }
        );
    }

    protected static function booted(): void
    {
        static::saving(function (Person $person): void {
            if (filled($person->cpf)) {
                $clean = preg_replace('/\D/', '', (string) $person->cpf);
                $person->cpf_hash = static::hashCpf($clean);
            } else {
                $person->cpf_hash = null;
            }
        });

        static::addGlobalScope('exclude_system_admin', function (Builder $builder): void {
            $table = $builder->getModel()->getTable();

            $builder->where(function (Builder $q) use ($table): void {
                $q->whereNull("{$table}.email")
                    ->orWhereRaw("LOWER({$table}.email) != ?", ['admin@admin.com']);
            })->where(function (Builder $q) use ($table): void {
                $q->whereRaw("LOWER(TRIM({$table}.name)) != ?", ['admin'])
                    ->orWhereHas('contracts', function (Builder $contractQuery): void {
                        $contractQuery->withoutGlobalScopes();
                    })
                    ->orWhereHas('collaborators')
                    ->orWhereHas('responsibles');
            });
        });

        static::deleting(function (Person $person): void {
            if ($reason = $person->getDeletionBlockReason()) {
                throw new \RuntimeException($reason);
            }

            // 6. Limpa contratos já excluídos (soft-deleted) para liberar as restrições de chave estrangeira
            foreach ($person->contracts()->withTrashed()->get() as $contract) {
                foreach ($contract->installments()->get() as $installment) {
                    $installment->delete();
                }
                $contract->forceDelete();
            }

            // 7. Limpa registros de responsáveis vinculados à pessoa (e contratos soft-deleted associados)
            foreach ($person->responsibles()->withoutGlobalScopes()->get() as $resp) {
                foreach ($resp->contracts()->withTrashed()->get() as $contract) {
                    foreach ($contract->installments()->get() as $installment) {
                        $installment->delete();
                    }
                    $contract->forceDelete();
                }
                $resp->delete();
            }

            // 8. Desvincula escolas da tabela pivot
            $person->schools()->detach();
        });
    }

    public function getDeletionBlockReason(): ?string
    {
        // 1. Bloqueia se possuir movimentação contábil real (parcelas pagas em qualquer contrato)
        $hasPaidInContracts = \App\Models\Installment::query()
            ->whereIn('contract_id', function ($query): void {
                $query->select('id')
                    ->from('contracts')
                    ->where('student_id', $this->id);
            })
            ->where(function (Builder $q): void {
                $q->where('status', 'paid')
                    ->orWhereNotNull('paid_at')
                    ->orWhere('amount_paid', '>', 0);
            })
            ->exists();

        if ($hasPaidInContracts) {
            return 'Esta pessoa não pode ser excluída pois possui movimentação contábil (parcelas pagas e recibos emitidos). Para preservar o histórico fiscal da escola, cancele o contrato em vez de excluir a pessoa.';
        }

        // 2. Bloqueia se for responsável financeiro em contratos com movimentação contábil
        $hasPaidAsResponsible = \App\Models\Installment::query()
            ->whereIn('contract_id', function ($query): void {
                $query->select('c.id')
                    ->from('contracts as c')
                    ->join('responsibles as r', 'r.id', '=', 'c.responsible_id')
                    ->where('r.person_id', $this->id);
            })
            ->where(function (Builder $q): void {
                $q->where('status', 'paid')
                    ->orWhereNotNull('paid_at')
                    ->orWhere('amount_paid', '>', 0);
            })
            ->exists();

        if ($hasPaidAsResponsible) {
            return 'Esta pessoa é responsável financeira em contrato(s) com movimentação contábil ativa e não pode ser excluída.';
        }

        // 3. Bloqueia se for colaborador da escola
        if ($this->collaborators()->withoutGlobalScopes()->exists()) {
            return 'Esta pessoa está cadastrada como colaborador(a) da escola. Desvincule o cargo de colaborador antes de excluir.';
        }

        // 4. Bloqueia se tiver usuário de login associado
        if ($this->users()->exists()) {
            return 'Esta pessoa possui um usuário de acesso ao sistema associado. Exclua o usuário primeiro.';
        }

        return null;
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function responsibles(): HasMany
    {
        return $this->hasMany(Responsible::class);
    }

    public function collaborators(): HasMany
    {
        return $this->hasMany(Collaborator::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function commercialLeads(): HasMany
    {
        return $this->hasMany(CommercialLead::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'student_id');
    }

    public function studentContracts(): HasMany
    {
        return $this->contracts();
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_person');
    }

    /**
     * Verifica se o aluno foi migrado / cadastrado na Ouro Moderno
     */
    public function isMigratedToOuroModerno(): bool
    {
        return $this->ouro_moderno_registered_at !== null
            || filled($this->ouro_moderno_cod_int)
            || filled($this->ouro_moderno_usuario);
    }

    /**
     * Verifica se o aluno possui ao menos um contrato com status ativo
     */
    public function hasActiveContract(): bool
    {
        return $this->contracts()->where('status', 'active')->exists();
    }

    /**
     * Verifica se o aluno possui parcelas em atraso em algum dos seus contratos ativos
     */
    public function hasOverdueInstallments(): bool
    {
        return $this->contracts()
            ->where('status', 'active')
            ->whereHas('installments', function ($query) {
                $query->whereIn('status', ['pending', 'partial'])
                    ->whereDate('due_date', '<', today())
                    ->whereRaw('(amount - discount + fine - COALESCE(amount_paid, 0)) > 0');
            })
            ->exists();
    }
}
