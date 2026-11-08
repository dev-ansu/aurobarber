<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialAccount extends Model
{
    protected $table = 'financial_accounts';

    protected $fillable = [
        'school_id',
        'name',
        'type',
        'bank_name',
        'agency_number',
        'account_number',
        'initial_balance',
        'current_balance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'initial_balance' => 'float',
        'current_balance' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FinancialAccount $account): void {
            if (empty($account->school_id)) {
                $account->school_id = Filament::getTenant()?->id
                    ?? session('filament_tenant_id')
                    ?? School::query()->withoutGlobalScopes()->latest('id')->value('id');
            }

            if (empty($account->current_balance) && filled($account->initial_balance)) {
                $account->current_balance = $account->initial_balance;
            }
        });

        static::created(function (FinancialAccount $account): void {
            if ((float) $account->initial_balance > 0) {
                FinancialAccountTransaction::create([
                    'school_id' => $account->school_id,
                    'financial_account_id' => $account->id,
                    'type' => 'adjustment',
                    'amount' => (float) $account->initial_balance,
                    'balance_after' => (float) $account->initial_balance,
                    'description' => 'Saldo Inicial Cadastrado',
                    'source_type' => 'initial_balance',
                    'user_id' => Auth::id(),
                ]);
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function accountTransactions(): HasMany
    {
        return $this->hasMany(FinancialAccountTransaction::class, 'financial_account_id')->latest();
    }

    public function transactions(): HasMany
    {
        return $this->accountTransactions();
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'destination_account_id');
    }

    public function scopeForSchool(Builder $query, ?int $schoolId = null): Builder
    {
        $schoolId ??= Filament::getTenant()?->id ?? session('filament_tenant_id');

        return $query->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
    }

    /**
     * Adiciona valor ao saldo da conta e gera histórico de extrato.
     */
    public function deposit(
        float $amount,
        string $description = 'Depósito',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $relatedAccountId = null,
        ?int $userId = null
    ): FinancialAccountTransaction {
        $this->increment('current_balance', $amount);
        $newBalance = (float) $this->fresh()->current_balance;

        return FinancialAccountTransaction::create([
            'school_id' => $this->school_id,
            'financial_account_id' => $this->id,
            'type' => $relatedAccountId ? 'transfer_in' : 'deposit',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'related_account_id' => $relatedAccountId,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Deduz valor do saldo da conta e gera histórico de extrato.
     */
    public function withdraw(
        float $amount,
        string $description = 'Retirada',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $relatedAccountId = null,
        ?int $userId = null
    ): FinancialAccountTransaction {
        $this->decrement('current_balance', $amount);
        $newBalance = (float) $this->fresh()->current_balance;

        return FinancialAccountTransaction::create([
            'school_id' => $this->school_id,
            'financial_account_id' => $this->id,
            'type' => $relatedAccountId ? 'transfer_out' : 'withdrawal',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'related_account_id' => $relatedAccountId,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Transfere valor entre duas contas da escola de forma atômica.
     */
    public function transferTo(
        FinancialAccount $destinationAccount,
        float $amount,
        ?string $description = null,
        ?int $userId = null
    ): void {
        DB::transaction(function () use ($destinationAccount, $amount, $description, $userId) {
            $user = $userId ?? Auth::id();
            $descOut = $description ?: 'Transferência para '.$destinationAccount->name;
            $descIn = $description ?: 'Transferência recebida de '.$this->name;

            $this->withdraw(
                amount: $amount,
                description: $descOut,
                sourceType: 'internal_transfer',
                relatedAccountId: $destinationAccount->id,
                userId: $user
            );

            $destinationAccount->deposit(
                amount: $amount,
                description: $descIn,
                sourceType: 'internal_transfer',
                relatedAccountId: $this->id,
                userId: $user
            );
        });
    }

    /**
     * Ajusta saldo manualmente com justificativa e trilha no extrato.
     */
    public function adjustBalance(float $newBalance, string $justification, ?int $userId = null): FinancialAccountTransaction
    {
        $oldBalance = (float) $this->current_balance;
        $diff = $newBalance - $oldBalance;

        $this->update(['current_balance' => $newBalance]);

        return FinancialAccountTransaction::create([
            'school_id' => $this->school_id,
            'financial_account_id' => $this->id,
            'type' => 'adjustment',
            'amount' => abs($diff),
            'balance_after' => $newBalance,
            'description' => 'Ajuste de saldo: '.$justification.' ('.($diff >= 0 ? '+' : '-').'R$ '.number_format(abs($diff), 2, ',', '.').')',
            'source_type' => 'manual_adjustment',
            'user_id' => $userId ?? Auth::id(),
        ]);
    }
}
