<?php

namespace App\Providers;

use App\Models\Barbershop;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $tempDir = storage_path('app/private/tmp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        $livewireTmp = storage_path('app/private/livewire-tmp');
        if (! is_dir($livewireTmp)) {
            @mkdir($livewireTmp, 0777, true);
        }

        putenv('TMP='.$tempDir);
        putenv('TEMP='.$tempDir);
        $_ENV['TMP'] = $tempDir;
        $_ENV['TEMP'] = $tempDir;
    }

    public function boot(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            if ($user->role === 'master_admin') {
                return true;
            }

            $tenant = filament()->getTenant();
            if ($user->role === 'owner' && $tenant instanceof Barbershop) {
                return $user->canAccessTenant($tenant);
            }

            // Garante que barbeiros com visão restrita sempre possam acessar sua própria Agenda e Comissões
            if (method_exists($user, 'isRestrictedBarber') && $user->isRestrictedBarber()) {
                $barberAllowedAbilities = [
                    'ViewAny:Appointment',
                    'View:Appointment',
                    'Create:Appointment',
                    'Update:Appointment',
                    'View:DailySchedule',
                    'View:CommissionsPage',
                ];
                if (in_array($ability, $barberAllowedAbilities, true)) {
                    return true;
                }
            }

            return null;
        });

        Request::macro('hasValidSignature', function ($absolute = true, $ignoreQuery = []) {
            /** @var Request $this */
            return app('url')->hasValidSignature($this, $absolute, $ignoreQuery)
                || app('url')->hasValidRelativeSignature($this, $ignoreQuery);
        });

        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker->native(true);
        });

        DateTimePicker::configureUsing(function (DateTimePicker $dateTimePicker): void {
            $dateTimePicker->native(true);
        });

        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        if (class_exists(RoleResource::class)) {
            RoleResource::scopeToTenant(false);
        }
    }
}
