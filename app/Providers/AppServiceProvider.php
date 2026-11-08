<?php

namespace App\Providers;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Installment;
use App\Models\Person;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use App\Observers\AuditLogObserver;
use Filament\Events\TenantSet;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
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
        $installmentsReceipts = storage_path('app/public/installments/receipts');
        if (! is_dir($installmentsReceipts)) {
            @mkdir($installmentsReceipts, 0777, true);
        }
        $payablesReceipts = storage_path('app/public/payables/receipts');
        if (! is_dir($payablesReceipts)) {
            @mkdir($payablesReceipts, 0777, true);
        }

        putenv('TMP='.$tempDir);
        putenv('TEMP='.$tempDir);
        $_ENV['TMP'] = $tempDir;
        $_ENV['TEMP'] = $tempDir;
    }

    /**
     * Bootstrap any application services.
     */
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

        URL::defaults([
            'tenant' => 1,
        ]);

        Event::listen(TenantSet::class, function (TenantSet $event): void {
            URL::defaults([
                'tenant' => $event->getTenant(),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                $event->user->markOffline();
            }
        });

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->touchOnlinePresence();
            }
        });

        if (request()->header('x-forwarded-proto') === 'https'
            || str_contains(request()->header('host', ''), 'loca.lt')
            || str_contains(request()->header('host', ''), 'ngrok')
            || str_contains(request()->header('host', ''), 'trycloudflare.com')
            || str_contains(request()->header('host', ''), 'ts.net')
            || str_contains(request()->header('host', ''), 'pinggy.link')) {
            URL::forceScheme('https');
        }

        foreach ([
            Contract::class,
            Person::class,
            ContractItem::class,
            ContractCourse::class,
            StudentSchedule::class,
            StudentAttendance::class,
            Installment::class,
            CashRegister::class,
            CashTransaction::class,
        ] as $model) {
            $model::observe(AuditLogObserver::class);
        }

        // Padronizacao global dos seletores de data no sistema permitindo digitacao via teclado
        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker
                ->native(true);
        });

        DateTimePicker::configureUsing(function (DateTimePicker $dateTimePicker): void {
            $dateTimePicker
                ->native(true);
        });

        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->numbers()
                ->symbols();
        });
    }
}
