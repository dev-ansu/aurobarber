<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Agenda;
use App\Filament\Pages\BackupManagement;
use App\Filament\Pages\DreReport;
use App\Filament\Pages\SystemUpdate;
use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\Installments\InstallmentsResource;
use App\Filament\Resources\StudentAttendances\StudentAttendanceResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions-widget';

    public static function canView(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        return ContractsResource::canCreate()
            || Agenda::canAccess()
            || StudentAttendanceResource::canViewAny()
            || InstallmentsResource::canViewAny()
            || DreReport::canAccess()
            || BackupManagement::canAccess()
            || SystemUpdate::canAccess();
    }

    public function getPermissionsProperty(): array
    {
        return [
            'canCreateContract' => ContractsResource::canCreate(),
            'canManageAttendance' => Agenda::canAccess() || StudentAttendanceResource::canViewAny(),
            'canManageInstallments' => InstallmentsResource::canViewAny(),
            'canViewDre' => DreReport::canAccess(),
            'canManageBackup' => BackupManagement::canAccess(),
            'canManageUpdate' => SystemUpdate::canAccess(),
        ];
    }
}
