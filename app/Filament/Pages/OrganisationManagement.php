<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class OrganisationManagement extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.organisation-management';
}
