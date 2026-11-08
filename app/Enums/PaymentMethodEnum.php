<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethodEnum: string implements HasLabel
{
    case Pix = 'pix';
    case Money = 'money';
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case BankTransfer = 'bank_transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pix => 'PIX',
            self::Money => 'Dinheiro (Espécie)',
            self::CreditCard => 'Cartão de Crédito',
            self::DebitCard => 'Cartão de Débito',
            self::BankTransfer => 'Transferência / Depósito',
        };
    }
}
