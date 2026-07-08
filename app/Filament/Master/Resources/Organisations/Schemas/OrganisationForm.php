<?php

namespace App\Filament\Master\Resources\Organisations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganisationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('shortname')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                \Filament\Forms\Components\FileUpload::make('logo')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg', 'image/webp'])
                    ->maxSize(100)
                    ->directory('organisations/logos')
                    ->default(null),
                \Filament\Forms\Components\ColorPicker::make('brand_color')
                    ->default(null),
            ]);
    }
}
