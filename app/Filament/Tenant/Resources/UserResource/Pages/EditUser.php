<?php

namespace App\Filament\Tenant\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use App\Filament\Tenant\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('reset_password')
                ->label('Reset Password')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $defaultPassword = $this->record->card_number ?? $this->record->pin;
                    $this->record->update([
                        'password' => Hash::make($defaultPassword),
                        'requires_password_change' => true,
                    ]);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
