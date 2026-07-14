<?php

namespace App\Filament\Tenant\Resources\Schedules\Schemas;

use Filament\Schemas\Schema;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Basic Details')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\Toggle::make('status')
                            ->default(true)
                            ->required(),
                        \Filament\Forms\Components\MorphToSelect::make('target')
                            ->types([
                                \Filament\Forms\Components\MorphToSelect\Type::make(\App\Models\Branch::class)->titleAttribute('name'),
                                \Filament\Forms\Components\MorphToSelect\Type::make(\App\Models\Department::class)->titleAttribute('name'),
                                \Filament\Forms\Components\MorphToSelect\Type::make(\App\Models\TaskGroup::class)->titleAttribute('name'),
                            ])
                            ->searchable()
                            ->preload(),
                        \Filament\Forms\Components\DatePicker::make('valid_from'),
                        \Filament\Forms\Components\DatePicker::make('valid_to'),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Weekly Schedule (Default)')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(3)->schema(
                            collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->map(function ($day) {
                                $key = strtolower($day);
                                $isWeekend = in_array($day, ['Saturday', 'Sunday']);
                                return \Filament\Schemas\Components\Fieldset::make($day)->schema([
                                    \Filament\Forms\Components\Toggle::make("rules.weekly.{$key}.is_working")
                                        ->label('Working Day')
                                        ->default(! $isWeekend)
                                        ->live(),
                                    \Filament\Forms\Components\TimePicker::make("rules.weekly.{$key}.start")
                                        ->label('Start')
                                        ->default('09:00')
                                        ->visible(fn ($get) => $get("rules.weekly.{$key}.is_working")),
                                    \Filament\Forms\Components\TimePicker::make("rules.weekly.{$key}.end")
                                        ->label('End')
                                        ->default('18:00')
                                        ->visible(fn ($get) => $get("rules.weekly.{$key}.is_working")),
                                ])->columns(1);
                            })->toArray()
                        )
                    ]),

                \Filament\Schemas\Components\Section::make('Special Schedules')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('rules.special')
                            ->label('Custom Dates or Ranges')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('name')->required(),
                                \Filament\Forms\Components\Toggle::make('is_working')->label('Working Day')->default(false)->live(),
                                \Filament\Forms\Components\DatePicker::make('start_date')->required(),
                                \Filament\Forms\Components\DatePicker::make('end_date')->label('End Date (Optional)'),
                                \Filament\Forms\Components\TimePicker::make('start')->label('Start Time')->visible(fn ($get) => $get('is_working')),
                                \Filament\Forms\Components\TimePicker::make('end')->label('End Time')->visible(fn ($get) => $get('is_working')),
                            ])->columns(3)
                    ])
            ]);
    }
}
