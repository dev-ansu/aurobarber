<?php

namespace App\Filament\Exports;

use App\Models\Installment;

class InstallmentExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = Installment::class;

    protected static array $columns = [
        'contract.contract_number' => 'Contrato', 'contract.student.name' => 'Aluno',
        'financialType.name' => 'Tipo financeiro', 'installment_number' => 'Parcela',
        'total_installments' => 'Total de parcelas', 'amount' => 'Valor', 'amount_paid' => 'Valor pago',
        'due_date' => 'Vencimento', 'paid_at' => 'Pago em', 'status' => 'Situação',
        'paymentMethod.name' => 'Forma de pagamento',
    ];
}
