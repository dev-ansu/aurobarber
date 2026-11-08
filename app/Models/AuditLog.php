<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'school_id',
        'contract_id',
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'created' => 'Criado',
            'updated' => 'Alterado',
            'deleted' => 'Removido',
            'restored' => 'Restaurado',
            'view_sensitive_data' => 'Consulta de Dado Pessoal (LGPD)',
            default => $this->event,
        };
    }

    public function actionLabel(): string
    {
        if ($this->auditable_type === Installment::class) {
            if ($this->event === 'deleted') {
                return 'Parcela removida';
            }

            if ($this->event === 'created') {
                return 'Parcela gerada';
            }

            if ($this->event === 'updated') {
                $changes = $this->getAttribute('changes') ?? [];
                $status = data_get($changes, 'after.status');
                $statusBefore = data_get($changes, 'before.status');

                if (
                    (($statusBefore === 'paid' || $statusBefore === 'partial') && $status === 'pending')
                    || (filled(data_get($changes, 'reason')) && $status === 'pending')
                    || str_contains(data_get($changes, 'after.notes', ''), 'Estorno de baixa')
                ) {
                    return 'Estorno de baixa (Pagamento revertido)';
                }

                if ($status === 'partial') {
                    return 'Baixa parcial registrada';
                }

                if ($status === 'paid' || data_get($changes, 'after.amount_paid') !== null) {
                    return 'Baixa de parcela registrada';
                }

                if ($status === 'canceled') {
                    return 'Cancelamento de parcela';
                }
            }

            return "Parcela {$this->eventLabel()}";
        }

        if ($this->auditable_type === StudentAttendance::class) {
            if ($this->event === 'created') {
                return 'Chamada gerada';
            }

            $changes = $this->getAttribute('changes') ?? [];
            $beforeStatus = data_get($changes, 'before.status');
            $afterStatus = data_get($changes, 'after.status');

            if ($beforeStatus && $afterStatus && $beforeStatus !== $afterStatus) {
                $from = $beforeStatus === 'present' ? 'Presença' : 'Falta';
                $to = $afterStatus === 'present' ? 'Presença' : 'Falta';
                return "Frequência alterada ({$from} ➔ {$to})";
            }

            return $afterStatus === 'present'
                ? 'Presença confirmada'
                : 'Falta registrada';
        }

        if ($this->auditable_type === CashTransaction::class) {
            $changes = $this->getAttribute('changes') ?? [];
            $type = data_get($changes, 'after.type') ?? data_get($changes, 'before.type');
            $desc = data_get($changes, 'after.description') ?? data_get($changes, 'before.description', '');
            if (str_contains(mb_strtolower((string) $desc), 'estorno')) {
                return 'Estorno debitado no caixa';
            }
            if ($type === 'output') {
                return 'Saída de caixa lançada';
            }
            return $this->event === 'created'
                ? 'Movimentação lançada no caixa'
                : 'Movimentação '.$this->eventLabel();
        }

        if ($this->auditable_type === CashRegister::class) {
            return 'Caixa '.$this->eventLabel();
        }

        if ($this->auditable_type === Person::class) {
            return match ($this->event) {
                'created' => 'Pessoa cadastrada',
                'updated' => 'Pessoa alterada',
                'deleted' => 'Pessoa excluída',
                'restored' => 'Pessoa restaurada',
                default => 'Pessoa '.$this->eventLabel(),
            };
        }

        if ($this->auditable_type === Contract::class) {
            if ($this->event === 'restored') {
                return 'Reversão de status (Contrato restaurado)';
            }

            if ($this->event === 'deleted') {
                return 'Contrato excluído';
            }

            if ($this->event === 'created') {
                return 'Contrato cadastrado';
            }

            if ($this->event === 'updated') {
                $changes = $this->getAttribute('changes') ?? [];
                $afterStatus = data_get($changes, 'after.status');
                $beforeStatus = data_get($changes, 'before.status');

                if ($afterStatus === 'active' && in_array($beforeStatus, ['finished', 'canceled', 'suspended'], true)) {
                    return match ($beforeStatus) {
                        'finished' => 'Reversão de status (Conclusão desfeita)',
                        'canceled' => 'Reversão de status (Cancelamento desfeito)',
                        'suspended' => 'Reversão de status (Retorno de trancamento)',
                        default => 'Reversão de status (Contrato reativado)',
                    };
                }

                if ($afterStatus === 'finished') {
                    return 'Contrato concluído';
                }

                if ($afterStatus === 'canceled') {
                    return 'Contrato cancelado';
                }

                if ($afterStatus === 'suspended') {
                    return 'Contrato trancado';
                }

                if ($afterStatus === 'active' && $beforeStatus === 'draft') {
                    return 'Contrato ativado';
                }
            }

            return "Contrato {$this->eventLabel()}";
        }

        return $this->eventLabel();
    }

    public function recordLabel(): string
    {
        $record = $this->auditable;
        $before = data_get($this->getAttribute('changes'), 'before', []);

        if ($record instanceof Installment) {
            $number = $record->installment_number
                ? "{$record->installment_number} / {$record->total_installments}"
                : 'Avulsa';

            return "Parcela {$number}";
        }

        if ($this->auditable_type === Installment::class) {
            $number = data_get($before, 'installment_number')
                ? data_get($before, 'installment_number').' / '.data_get($before, 'total_installments')
                : 'Avulsa';

            return "Parcela {$number}";
        }

        if ($record instanceof CashTransaction) {
            return $record->description ?: 'Movimentação de caixa';
        }

        if ($record instanceof CashRegister) {
            return $record->name;
        }

        if ($record instanceof Contract) {
            return "Contrato {$record->contract_number}";
        }

        if ($this->auditable_type === Contract::class) {
            return 'Contrato '.data_get($before, 'contract_number', $this->auditable_id);
        }

        if ($record instanceof Person) {
            return "Pessoa {$record->name}";
        }

        if ($this->auditable_type === Person::class) {
            $name = data_get($before, 'name') ?? data_get($this->getAttribute('changes'), 'after.name', $this->auditable_id);

            return "Pessoa {$name}";
        }

        if ($record instanceof ContractItem) {
            return 'Formação do contrato';
        }

        if ($record instanceof ContractCourse) {
            return 'Curso do contrato';
        }

        if ($record instanceof StudentSchedule) {
            return 'Horário';
        }

        if ($record instanceof StudentAttendance) {
            return 'Chamada de '.($record->studentSchedule?->contractItem?->contract?->student?->name ?? 'aluno')
                .' em '.$record->attendance_date?->format('d/m/Y');
        }

        return class_basename($this->auditable_type);
    }
}
