<?php

namespace App\Filament\Resources\StudentAttendances\Pages;

use App\Filament\Resources\StudentAttendances\StudentAttendanceResource;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentAttendances extends ManageRecords
{
    protected static string $resource = StudentAttendanceResource::class;
}
