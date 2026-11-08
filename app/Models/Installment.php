<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Installment extends Model
{
    public ?string $auditLogReason = null;

    public ?int $auditLogUserId = null;

    protected $fillable = [
        'school_id',
        'contract_id',
        'financial_type_id',
        'payment_method_id',
        'installment_number',
        'total_installments',
        'amount',
        'discount',
        'fine',
        'amount_paid',
        'due_date',
        'paid_at',
        'status',
        'receipt_path',
        'punctuality_discount_applied',
        'notes',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Installment $installment): void {
            if (empty($installment->school_id)) {
                if (! empty($installment->contract_id)) {
                    $installment->school_id = Contract::query()
                        ->withoutGlobalScopes()
                        ->whereKey($installment->contract_id)
                        ->value('school_id');
                }

                $installment->school_id ??= Filament::getTenant()?->id
                    ?? School::query()->withoutGlobalScopes()->latest('id')->value('id');
            }
        });

        static::updated(function (self $installment): void {
            if ($installment->wasChanged('receipt_path') && $oldPath = $installment->getOriginal('receipt_path')) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($oldPath);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
        });

        static::deleted(function (self $installment): void {
            if ($installment->receipt_path) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($installment->receipt_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($installment->receipt_path);
            }
        });
    }

    // Indica que os campos de data devem ser tratados como objetos Carbon
    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'date',
    ];

    public function getStudentNameAttribute(): string
    {
        return $this->contract?->student?->name ?? 'Aluno não vinculado';
    }

    public function processarBaixa(array $data): void
    {
        // Validação de segurança: Impede que o valor pago seja maior que o total devido
        if (isset($data['status']) && in_array($data['status'], ['paid', 'partial'])) {
            $amount = floatval($data['amount'] ?? $this->amount);
            $discount = floatval($data['discount'] ?? $this->discount);
            $fine = floatval($data['fine'] ?? $this->fine);
            $valorRealRecebido = floatval($data['amount_paid'] ?? 0);

            $valorLiquidoDevido = max(0, ($amount - $discount) + $fine);

            if ($valorRealRecebido > $valorLiquidoDevido) {
                Notification::make()
                    ->title('Erro no Valor Recebido')
                    ->body('O valor pago (R$ '.number_format($valorRealRecebido, 2, ',', '.').') não pode ser maior que o total devido (R$ '.number_format($valorLiquidoDevido, 2, ',', '.').').')
                    ->danger()
                    ->send();

                return; // Interrompe a execução caso ultrapasse
            }
        }

        // Se a parcela NÃO estiver paga, permite atualizar o vencimento (acordo/prorrogação)
        if ($this->status !== 'paid') {
            $this->due_date = $data['due_date'] ?? $this->due_date;
        }

        $this->fill($data);

        $novaDataVencimento = $data['remaining_due_date'] ?? $this->due_date; // Data a ser repassada caso gere saldo restante

        // Se o status for de pagamento (paid ou partial)
        if (in_array($this->status, ['paid', 'partial']) || ($data['status'] ?? null) === 'paid') {
            $isPartialSplit = false;
            $novoNumeroParcela = null;
            $valorRealRecebido = 0;

            DB::transaction(function () use (&$isPartialSplit, &$novoNumeroParcela, &$valorRealRecebido, $novaDataVencimento, $data) {
                $caixa = ! empty($data['cash_register_id'])
                    ? CashRegister::find($data['cash_register_id'])
                    : CashRegister::where('user_id', Auth::id())
                        ->where('status', 'open')
                        ->when($this->contract?->school_id, fn ($q, $sid) => $q->where('school_id', $sid))
                        ->first();

                $valorRealRecebido = floatval($this->amount_paid);
                $valorLiquidoDevido = max(0, (floatval($this->amount) - floatval($this->discount)) + floatval($this->fine));

                if ($valorRealRecebido < $valorLiquidoDevido && $valorRealRecebido > 0) {
                    $this->status = 'partial';
                } else {
                    $this->status = 'paid';
                    if (empty($this->paid_at)) {
                        $this->paid_at = now();
                    }
                }

                $this->save();

                // Lançamento no Caixa Diário
                if ($valorRealRecebido > 0 && $caixa) {
                    $numContrato = $this->contract?->contract_number ?? '#';
                    $nomeAluno = $this->contract?->student?->name ?? 'Aluno';
                    $financialType = $this->financialType?->name ?? '';
                    CashTransaction::create([
                        'cash_register_id' => $caixa->id,
                        'user_id' => Auth::id(),
                        'financial_type_id' => $this->financial_type_id,
                        'installment_id' => $this->id,
                        'type' => 'input',
                        'amount' => $valorRealRecebido,
                        'payment_method_id' => $this->payment_method_id,
                        'description' => "Recebimento de {$financialType} | Contrato: {$numContrato} | Aluno: {$nomeAluno}",
                    ]);

                    $caixa->increment('current_balance', $valorRealRecebido);
                }

                // Desmembramento de saldo restante (se pagamento parcial)
                if ($this->status === 'partial') {
                    $saldoDevedorFaltante = $valorLiquidoDevido - $valorRealRecebido;

                    if ($saldoDevedorFaltante > 0) {
                        $this->update([
                            'amount' => $valorRealRecebido,
                            'status' => 'paid',
                            'paid_at' => $this->paid_at ?? now(),
                            'receipt_path' => $data['receipt_path'] ?? $this->receipt_path,
                        ]);

                        $novoNumeroParcela = $this->installment_number + 1;
                        $novoTotalParcelas = max($this->total_installments, $novoNumeroParcela);

                        $novaParcela = self::create([
                            'school_id' => $this->school_id ?? $this->contract?->school_id,
                            'contract_id' => $this->contract_id,
                            'financial_type_id' => $this->financial_type_id,
                            'payment_method_id' => null,
                            'installment_number' => $novoNumeroParcela,
                            'total_installments' => $novoTotalParcelas,
                            'amount' => $saldoDevedorFaltante,
                            'due_date' => $novaDataVencimento, // Herda a data ajustada no form
                            'status' => 'pending',
                            'discount' => 0.00,
                            'fine' => 0.00,
                            'amount_paid' => null,
                            'paid_at' => null,
                        ]);

                        // Reorganiza e renumera automaticamente todas as parcelas do contrato por ordem cronológica de vencimento
                        if ($this->contract_id) {
                            $contractInstallments = self::where('contract_id', $this->contract_id)
                                ->orderBy('due_date', 'asc')
                                ->orderBy('id', 'asc')
                                ->get();

                            $newTotal = $contractInstallments->count();
                            $seq = 1;
                            foreach ($contractInstallments as $ci) {
                                $ci->forceFill([
                                    'installment_number' => $seq,
                                    'total_installments' => $newTotal,
                                ])->saveQuietly();

                                if ($ci->id === $novaParcela->id) {
                                    $novoNumeroParcela = $seq;
                                }
                                $seq++;
                            }
                        }

                        $isPartialSplit = true;
                    }
                }
            });

            if ($isPartialSplit) {
                Notification::make()
                    ->title('Diferença de Saldo Detectada')
                    ->body('R$ '.number_format($valorRealRecebido, 2, ',', '.')." inseridos no caixa. A parcela {$novoNumeroParcela} foi gerada para o dia ".Carbon::parse($novaDataVencimento)->format('d/m/Y').'.')
                    ->warning()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Baixa Integral Registrada')
                ->body('O valor total de R$ '.number_format($valorRealRecebido, 2, ',', '.').' foi computado no seu Caixa Diário.')
                ->success()
                ->send();
        } else {
            // Caso tenha apenas alterado a data (acordo) sem dar baixa
            $this->save();

            Notification::make()
                ->title('Vencimento Atualizado')
                ->body('A data de vencimento foi alterada com sucesso para '.Carbon::parse($this->due_date)->format('d/m/Y').'.')
                ->success()
                ->send();
        }
    }

    public function scopeForSchool(Builder $query, ?int $schoolId = null): Builder
    {
        $schoolId ??= Filament::getTenant()?->id;

        if (! $schoolId) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        return $query->where(function (Builder $q) use ($table, $schoolId): void {
            $q->where("{$table}.school_id", $schoolId)
                ->orWhere(function (Builder $sub) use ($table, $schoolId): void {
                    $sub->whereNull("{$table}.school_id")
                        ->whereHas('contract', fn (Builder $cq) => $cq->withoutGlobalScopes()->where('school_id', $schoolId));
                });
        });
    }

    // Vinculo direto com a escola (permite manter parcelas pagas na escola de origem ao transferir contrato)
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    // Vinculo com o contrato de origem
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id')->withoutGlobalScopes();
    }

    // Histórico de transações vinculadas a esta parcela
    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'installment_id');
    }

    public function isDeletable(): bool
    {
        return $this->status !== 'paid' && ! $this->transactions()->exists();
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    public function financialType(): BelongsTo
    {
        return $this->belongsTo(FinancialType::class, 'financial_type_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    /**
     * Cancela a parcela com registro de motivo, data e usuário responsável.
     */
    public function cancel(string $reason, ?int $userId = null): void
    {
        if (! in_array($this->status, ['pending', 'partial'], true)) {
            throw new \DomainException('Apenas parcelas pendentes ou com baixa parcial podem ser canceladas.');
        }

        $userId ??= auth()->id();
        $userName = auth()->user()?->name ?? 'Sistema';
        $timestamp = now()->format('d/m/Y H:i');

        $cancellationEntry = "[{$timestamp}] Cancelada por {$userName}: {$reason}";
        $currentNotes = trim((string) $this->notes);
        $updatedNotes = $currentNotes !== '' ? "{$currentNotes}\n{$cancellationEntry}" : $cancellationEntry;

        $this->update([
            'status' => 'canceled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
            'cancelled_by_id' => $userId,
            'notes' => $updatedNotes,
        ]);
    }

    /**
     * Estorna a baixa de uma parcela paga indevidamente, revertendo o status para pendente,
     * limpando o valor recebido, gerando saída no caixa ou na conta bancária e registrando a auditoria.
     */
    public function estornarBaixa(
        string $reason,
        ?int $cashRegisterId = null,
        ?int $financialAccountId = null,
        ?int $userId = null
    ): void {
        if (! in_array($this->status, ['paid', 'partial'], true)) {
            throw new \DomainException('Apenas parcelas com baixa confirmada ou parcial podem ser estornadas.');
        }

        $userId ??= Auth::id() ?? auth()->id();
        $user = User::find($userId);
        $userName = $user?->name ?? (auth()->user()?->name ?? 'Sistema');
        $valorEstornado = (float) ($this->amount_paid ?: $this->amount);
        $valorFormatado = number_format($valorEstornado, 2, ',', '.');
        $timestamp = now()->format('d/m/Y H:i');

        DB::transaction(function () use ($reason, $cashRegisterId, $financialAccountId, $userId, $userName, $valorEstornado, $valorFormatado, $timestamp): void {
            $numContrato = $this->contract?->contract_number ?? '#';
            $nomeAluno = $this->contract?->student?->name ?? 'Aluno';
            $financialType = $this->financialType?->name ?? 'Parcela';

            // 1. Estorno em Conta Bancária / Cofre (se selecionado)
            if ($financialAccountId && $valorEstornado > 0) {
                $financialAccount = FinancialAccount::find($financialAccountId);
                if ($financialAccount) {
                    $financialAccount->withdraw(
                        amount: $valorEstornado,
                        description: "Estorno de recebimento de {$financialType} | Contrato: {$numContrato} | Aluno: {$nomeAluno} | Motivo: {$reason}",
                        sourceType: self::class,
                        sourceId: $this->id,
                        userId: $userId
                    );
                }
            } elseif ($cashRegisterId !== null || $valorEstornado > 0) {
                // 2. Estorno em Caixa Diário
                $caixa = $cashRegisterId
                    ? CashRegister::find($cashRegisterId)
                    : CashRegister::where('user_id', $userId)
                        ->where('status', 'open')
                        ->when($this->contract?->school_id, fn ($q, $sid) => $q->where('school_id', $sid))
                        ->first();

                // Se o operador não tiver caixa próprio aberto, busca qualquer caixa aberto da escola
                if (! $caixa && $this->contract?->school_id) {
                    $caixa = CashRegister::where('school_id', $this->contract->school_id)
                        ->where('status', 'open')
                        ->first();
                }

                if ($caixa && $valorEstornado > 0) {
                    CashTransaction::create([
                        'cash_register_id' => $caixa->id,
                        'user_id' => $userId,
                        'financial_type_id' => $this->financial_type_id,
                        'installment_id' => $this->id,
                        'type' => 'output',
                        'amount' => $valorEstornado,
                        'payment_method_id' => $this->payment_method_id,
                        'description' => "Estorno de recebimento de {$financialType} | Contrato: {$numContrato} | Aluno: {$nomeAluno} | Motivo: {$reason}",
                    ]);

                    $caixa->decrement('current_balance', $valorEstornado);
                }
            }

            // 3. Histórico nas observações internas da parcela
            $refundEntry = "[{$timestamp}] Estorno de baixa (R$ {$valorFormatado}) realizado por {$userName}: {$reason}";
            $currentNotes = trim((string) $this->notes);
            $updatedNotes = $currentNotes !== '' ? "{$currentNotes}\n{$refundEntry}" : $refundEntry;

            // 4. Informa campos para o AuditLogObserver
            $this->auditLogReason = $reason;
            $this->auditLogUserId = $userId;

            // 5. Se foi uma baixa parcial com parcela restante desmembrada pendente, reagrupa
            if ($this->getOriginal('status') === 'partial') {
                $splitInstallment = self::query()
                    ->where('contract_id', $this->contract_id)
                    ->where('installment_number', $this->installment_number + 1)
                    ->where('status', 'pending')
                    ->whereNull('amount_paid')
                    ->first();

                if ($splitInstallment) {
                    $saldoFaltante = (float) $splitInstallment->amount;
                    $this->amount = (float) $this->amount + $saldoFaltante;
                    $splitInstallment->delete();
                }
            }

            // 6. Reverte a parcela para o estado Pendente
            $this->update([
                'status' => 'pending',
                'amount_paid' => null,
                'paid_at' => null,
                'punctuality_discount_applied' => false,
                'notes' => $updatedNotes,
            ]);
        });
    }
}
