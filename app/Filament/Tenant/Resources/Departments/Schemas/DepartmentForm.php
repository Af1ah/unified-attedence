<?php

namespace App\Filament\Tenant\Resources\Departments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
