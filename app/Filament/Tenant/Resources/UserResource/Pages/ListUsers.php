<?php

namespace App\Filament\Tenant\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Tenant\Resources\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('syncToDevice')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->label('Sync Users')
                ->action(function () {
                    $devices = \App\Models\Device::all();
                    $successCount = 0;
                    $errors = [];
                    
                    if ($devices->isEmpty()) {
                        \Filament\Notifications\Notification::make()->title('No Devices')->body('There are no devices to sync from.')->warning()->send();
                        return;
                    }

                    foreach ($devices as $device) {
                        $result = app(\App\Services\Attendance\DirectDeviceService::class)->syncUsersFromDevice($device);
                        
                        if ($result['status']) {
                            $successCount++;
                        } else {
                            $errors[] = ($device->name ?: $device->serial_number) . ": " . $result['message'];
                        }
                    }
                    
                    if ($successCount > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Success')
                            ->body("Successfully synced users from {$successCount} device(s).")
                            ->success()
                            ->send();
                    }
                    
                    if (count($errors) > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Some syncs failed')
                            ->body(implode(', ', $errors))
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
