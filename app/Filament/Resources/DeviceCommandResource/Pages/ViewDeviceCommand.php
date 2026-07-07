<?php

namespace App\Filament\Resources\DeviceCommandResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\DeviceCommandResource;

class ViewDeviceCommand extends ViewRecord
{
    protected static string $resource = DeviceCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
