<?php

namespace App\Filament\Resources\AttendanceLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AttendanceLogResource;

class ListAttendanceLogs extends ListRecords
{
    protected static string $resource = AttendanceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
