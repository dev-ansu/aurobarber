<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Models\SaasSetting;
use App\Models\User;
use App\Services\SaasSupportContact;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class SaasSupportSettings extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Configurações SaaS';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Contato de suporte';

    protected static ?string $title = 'Contato de suporte ao cliente';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.saas.pages.support-settings';

    public string $supportPhone = '';

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public function mount(SaasSupportContact $supportContact): void
    {
        $this->supportPhone = $supportContact->phone();
    }

    public function save(): void
    {
        $this->supportPhone = trim($this->supportPhone);
        $this->validate([
            'supportPhone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9().\s-]*$/'],
        ]);

        $digits = preg_replace('/\D/', '', $this->supportPhone) ?? '';
        if ($digits !== '' && (strlen($digits) < 10 || strlen($digits) > 15)) {
            throw ValidationException::withMessages([
                'supportPhone' => 'Informe um telefone com DDD e, se necessário, código do país.',
            ]);
        }

        SaasSetting::query()->updateOrCreate(
            ['key' => 'support_phone'],
            ['value' => $digits],
        );

        $this->supportPhone = $digits;

        Notification::make()
            ->title('Contato de suporte atualizado')
            ->success()
            ->send();
    }
}
