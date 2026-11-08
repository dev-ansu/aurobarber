<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContactTypeEnum: string implements HasLabel
{
    case Telefone = 'phone';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Celular = 'cellphone';
    case Instagram = 'instagram';
    case Telegram = 'telegram';
    case Outro = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Telefone => 'Telefone',
            self::Email => 'E-mail',
            self::WhatsApp => 'WhatsApp',
            self::Celular => 'Celular',
            self::Instagram => 'Instagram',
            self::Telegram => 'Telegram',
            self::Outro => 'Outro',
        };
    }

    public function getValidationRules(): array
    {
        return match ($this) {
            self::Telefone, self::WhatsApp, self::Celular => [
                'required',
                'string',
                'regex:/^(\+?55\s?)?(\(?\d{2}\)?\s?)?(\d{4,5}[-\s]?\d{4})$/',
            ],
            self::Email => ['required', 'email', 'max:255'],
            self::Instagram, self::Telegram, self::Outro => ['required', 'string', 'max:255'],
        };
    }
}
