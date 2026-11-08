<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RelationshipEnum: string implements HasLabel
{
    case Mother = 'mother';
    case Father = 'father';

    case Grandmother = 'grandmother';
    case Grandfather = 'grandfather';

    case Aunt = 'auth';
    case Uncle = 'uncle';

    case Sister = 'sister';
    case Brother = 'brother';

    case Spouse = 'spouse';

    case Legal_guardian = 'legal_guardian';

    case Outro = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mother => 'Mãe',
            self::Father => 'Pai',
            self::Grandmother => 'Avó',
            self::Grandfather => 'Avô',
            self::Aunt => 'Tia',
            self::Uncle => 'Tio',
            self::Sister => 'Irmã',
            self::Brother => 'Irmão',
            self::Spouse => 'Cônjuge',
            self::Legal_guardian => 'Responsável legal',
            self::Outro => 'Outro',
        };
    }
}
