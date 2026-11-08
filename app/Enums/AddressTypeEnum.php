<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AddressTypeEnum: string implements HasLabel
{
    case Residencial = 'residential';
    case Comercial = 'commercial';
    case Outro = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Residencial => 'Residencial',
            self::Comercial => 'Comercial',
            self::Outro => 'Outro',
        };
    }
}
