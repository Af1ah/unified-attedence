<?php

namespace App\Filament\Tenant\Resources\DeviceCommandResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Tenant\Resources\DeviceCommandResource;

class ListDeviceCommands extends ListRecords
{
    protected static string $resource = DeviceCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
