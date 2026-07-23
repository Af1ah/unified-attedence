<?php

namespace App\Filament\Tenant\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Tenant\Resources\DeviceResource\Pages;
use App\Filament\Tenant\Resources\DeviceResource\RelationManagers;

use App\Models\Device;
use App\Services\Attendance\DeviceCommandBuilder;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 1;

    protected static \UnitEnum|string|null $navigationGroup = 'Device Management';

    //

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Device Information')
                ->schema([
                    TextInput::make('serial_number')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),
                    TextInput::make('name')
                        ->maxLength(255),
                    Select::make('branch_id')
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('ip_address')
                        ->label('IP Address'),
                    TextInput::make('model'),
                    TextInput::make('firmware_version')
                        ->disabled(),
                    TextInput::make('push_version')
                        ->disabled(),
                    Select::make('status')
                        ->options([
                            'online' => 'Online',
                            'offline' => 'Offline',
                            'unknown' => 'Unknown',
                        ])
                        ->default('unknown'),
                    Select::make('punch_behavior')
                        ->options([
                            'device_state' => 'Device State (Default)',
                            'always_in' => 'Always In',
                            'always_out' => 'Always Out',
                            'auto' => 'Auto (Alternating)',
                        ])
                        ->default('device_state')
                        ->helperText('Determines if logs from this device are Check-In, Check-Out, or handled automatically.'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(fn (Device $record): string => $record->isOnline() ? 'online' : 'offline')
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_logs_count')
                    ->counts('attendanceLogs')
                    ->label('Logs'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'unknown' => 'Unknown',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('getInfo')
                    ->icon('heroicon-o-information-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Get Device Info')
                    ->modalDescription('Send a command to get device information.')
                    ->action(fn (Device $record) => app(DeviceCommandBuilder::class)->info($record)),
                Action::make('reboot')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reboot Device')
                    ->modalDescription('Are you sure you want to reboot this device?')
                    ->action(fn (Device $record) => app(DeviceCommandBuilder::class)->reboot($record)),
                Action::make('clearLogs')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear Device Logs')
                    ->modalDescription('Are you sure you want to clear all attendance logs on this device?')
                    ->action(fn (Device $record) => app(DeviceCommandBuilder::class)->clearAttendanceLogs($record)),
                Action::make('checkConnection')
                    ->label('Check ADMS Connection')
                    ->icon('heroicon-o-wifi')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Check Connection')
                    ->modalDescription('This will queue a CHECK command. The device should process it on its next poll.')
                    ->action(function (Device $record) {
                        app(DeviceCommandBuilder::class)->checkConnection($record);
                        \Filament\Notifications\Notification::make()->title('Command Queued')->body('Check connection command queued successfully.')->success()->send();
                    }),
                Action::make('syncTime')
                    ->label('Sync Time')
                    ->icon('heroicon-o-clock')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Device Time')
                    ->modalDescription('Queue a command to sync the device time with the server time.')
                    ->action(function (Device $record) {
                        app(DeviceCommandBuilder::class)->syncTime($record);
                        \Filament\Notifications\Notification::make()->title('Command Queued')->body('Time sync command queued successfully.')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttendanceLogsRelationManager::class,
            RelationManagers\CommandsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'view' => Pages\ViewDevice::route('/{record}'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}
