<?php

namespace App\Providers;

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
            if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                return true;
            }

            return null;
        });

        Request::macro('hasValidSignature', function ($absolute = true, $ignoreQuery = []) {
            /** @var Request $this */
            return app('url')->hasValidSignature($this, $absolute, $ignoreQuery)
                || app('url')->hasValidRelativeSignature($this, $ignoreQuery);
        });

        if (request()->header('x-forwarded-proto') === 'https'
            || str_contains(request()->header('host', ''), 'barber.aurosoft.com.br')) {
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
    }
}
