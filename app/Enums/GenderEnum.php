<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GenderEnum: string implements HasLabel
{
    case Masculino = 'male';
    case Feminino = 'female';
    case Outro = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Masculino => 'Masculino',
            self::Feminino => 'Feminino',
            self::Outro => 'Outro',
        };
    }
}
