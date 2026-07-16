<?php

namespace App\Filament\Tenant\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Tenant\Resources\UserResource\Pages;
use App\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'User';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'pin', 'email'];
    }

    public static function getGlobalSearchResultIcon(\Illuminate\Database\Eloquent\Model $record): string
    {
        return 'heroicon-o-user';
    }

    protected static ?string $pluralModelLabel = 'Users';

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Information')
                ->schema([
                    TextInput::make('pin')
                        ->label('User ID (PIN)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->autocomplete('off'),
                    TextInput::make('name')
                        ->autocomplete('off'),
                    TextInput::make('email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->nullable()
                        ->autocomplete('off'),
                    TextInput::make('card_number')
                        ->label('Card Number')
                        ->autocomplete('off'),
                    Select::make('privilege')
                        ->options([
                            0 => 'User',
                            14 => 'Admin',
                        ])
                        ->default(0),
                    TextInput::make('device_password')
                        ->label('Device Password (Numeric)')
                        ->numeric()
                        ->maxLength(8)
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password'),
                    Toggle::make('is_enabled')
                        ->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Enrollment Details')
                ->schema([
                    Select::make('branch_id')
                        ->relationship('branch', 'name')
                        ->label('Branch')
                        ->nullable(),
                    Select::make('department_id')
                        ->relationship('department', 'name')
                        ->label('Department')
                        ->nullable(),
                    TextInput::make('group')
                        ->label('Designation / Group')
                        ->datalist(fn () => \App\Models\User::whereNotNull('group')->where('group', '!=', '')->distinct()->pluck('group')->toArray())
                        ->autocomplete('off')
                        ->nullable(),
                ])
                ->columns(3)
                ->columnSpanFull()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pin')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('card_number')
                    ->label('Card')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('group')
                    ->label('Designation/Group')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('privilege')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 14 ? 'Admin' : 'User')
                    ->color(fn ($state): string => $state === 14 ? 'primary' : 'gray'),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('privilege')
                    ->options([
                        0 => 'User',
                        14 => 'Admin',
                    ])
                    ->default(0),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch'),
                Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department'),
                Tables\Filters\SelectFilter::make('group')
                    ->label('Designation / Group')
                    ->options(fn () => \App\Models\User::whereNotNull('group')->where('group', '!=', '')->distinct()->pluck('group', 'group')->toArray()),
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->default(true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('pushToDevice')
                        ->icon('heroicon-o-arrow-up-on-square')
                        ->color('success')
                        ->label('Push to Device')
                        ->form([
                            \Filament\Forms\Components\Select::make('device_id')
                                ->label('Select Device')
                                ->options(\App\Models\Device::all()->mapWithKeys(fn($d) => [$d->id => $d->name ?: $d->serial_number])->toArray())
                                ->default(fn() => \App\Models\Device::count() === 1 ? \App\Models\Device::first()->id : null)
                                ->hidden(fn() => \App\Models\Device::count() <= 1)
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $deviceId = $data['device_id'] ?? \App\Models\Device::first()?->id;
                            $device = \App\Models\Device::find($deviceId);
                            
                            if ($device) {
                                $result = app(\App\Services\Attendance\DirectDeviceService::class)->pushUsersToDevice($device, $records);
                                if ($result['status']) {
                                    \Filament\Notifications\Notification::make()->title('Success')->body($result['message'])->success()->send();
                                } else {
                                    \Filament\Notifications\Notification::make()->title('Failed')->body($result['message'])->danger()->send();
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    \Filament\Actions\BulkAction::make('deleteFromDevice')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->label('Delete from Device')
                        ->form([
                            \Filament\Forms\Components\Select::make('device_id')
                                ->label('Select Device')
                                ->options(\App\Models\Device::all()->mapWithKeys(fn($d) => [$d->id => $d->name ?: $d->serial_number])->toArray())
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $device = \App\Models\Device::find($data['device_id']);
                            
                            if ($device) {
                                $count = 0;
                                foreach ($records as $record) {
                                    app(\App\Services\Attendance\DeviceCommandBuilder::class)->deleteUser($device, $record->pin);
                                    $count++;
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Commands queued')
                                    ->body("{$count} users will be deleted from the device shortly.")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    \Filament\Actions\BulkAction::make('assignCategory')
                        ->label('Assign Category')
                        ->icon('heroicon-o-tag')
                        ->form([
                            \Filament\Forms\Components\Select::make('branch_id')
                                ->label('Branch')
                                ->options(\App\Models\Branch::pluck('name', 'id'))
                                ->nullable(),
                            \Filament\Forms\Components\Select::make('department_id')
                                ->label('Department')
                                ->options(\App\Models\Department::pluck('name', 'id'))
                                ->nullable(),
                            \Filament\Forms\Components\TextInput::make('group')
                                ->label('Group')
                                ->nullable(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $updateData = [];
                            if (array_key_exists('branch_id', $data)) {
                                $updateData['branch_id'] = $data['branch_id'];
                            }
                            if (array_key_exists('department_id', $data)) {
                                $updateData['department_id'] = $data['department_id'];
                            }
                            if (array_key_exists('group', $data)) {
                                $updateData['group'] = $data['group'];
                            }
                            
                            if (!empty($updateData)) {
                                foreach ($records as $record) {
                                    $record->update($updateData);
                                }
                                \Filament\Notifications\Notification::make()
                                    ->title('Success')
                                    ->body('Category assigned to selected users.')
                                    ->success()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
