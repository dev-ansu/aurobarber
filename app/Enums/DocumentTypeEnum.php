<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentTypeEnum: string implements HasLabel
{
    case RG = 'rg';
    case CPF = 'cpf';
    case CNH = 'cnh';
    case Passaporte = 'passport';
    case Outro = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::RG => 'RG',
            self::CPF => 'CPF',
            self::CNH => 'CNH',
            self::Passaporte => 'Passaporte',
            self::Outro => 'Outro',
        };
    }

    public function getValidationRules(): array
    {
        return match ($this) {
            self::RG => ['required', 'string', 'max:20'],
            self::CPF => ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/'],
            self::CNH => ['required', 'string', 'max:20'],
            self::Passaporte => ['required', 'string', 'max:20'],
            self::Outro => ['required', 'string', 'max:255'],
        };
    }
}
