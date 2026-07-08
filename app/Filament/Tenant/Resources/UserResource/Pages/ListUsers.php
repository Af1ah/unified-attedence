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
                ->form([
                    \Filament\Forms\Components\Select::make('device_id')
                        ->label('Select Device')
                        ->options(\App\Models\Device::pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $device = \App\Models\Device::find($data['device_id']);
                    
                    if ($device) {
                        $records = \App\Models\User::all();
                        $count = 0;
                        foreach ($records as $record) {
                            app(\App\Services\Attendance\DeviceCommandBuilder::class)->addUser($device, [
                                'pin' => $record->pin,
                                'name' => $record->name,
                                'card' => $record->card_number,
                                'privilege' => $record->privilege,
                                'group' => $record->group,
                                'password' => $record->device_password,
                            ]);
                            $count++;
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Commands queued')
                            ->body("{$count} users will be synced to the device shortly.")
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
