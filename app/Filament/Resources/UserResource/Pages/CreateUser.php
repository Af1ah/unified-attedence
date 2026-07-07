<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use App\Filament\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default password using card_number or fallback to pin
        $defaultPassword = $data['card_number'] ?? $data['pin'];
        $data['password'] = Hash::make($defaultPassword);
        
        // Force the user to change password upon first login
        $data['requires_password_change'] = true;

        return $data;
    }
}
