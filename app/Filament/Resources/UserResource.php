<?php

namespace App\Filament\Resources;

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
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'User';

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
                        ->required()
                        ->unique(ignoreRecord: true),
                    TextInput::make('name'),
                    TextInput::make('email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->nullable(),
                    TextInput::make('card_number')
                        ->label('Card Number'),
                    Select::make('privilege')
                        ->options([
                            0 => 'User',
                            14 => 'Admin',
                        ])
                        ->default(0),
                    TextInput::make('group'),
                    Toggle::make('is_enabled')
                        ->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),
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
                    ->label('Card'),
                Tables\Columns\TextColumn::make('privilege')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 14 ? 'Admin' : 'User')
                    ->color(fn ($state): string => $state === 14 ? 'primary' : 'gray'),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('attendance_logs_count')
                    ->counts('attendanceLogs')
                    ->label('Attendance Count'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('privilege')
                    ->options([
                        0 => 'User',
                        14 => 'Admin',
                    ]),
                Tables\Filters\TernaryFilter::make('is_enabled'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                \Filament\Tables\Actions\Action::make('pushToDevice')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('success')
                    ->label('Push to Device')
                    ->form([
                        \Filament\Forms\Components\Select::make('device_id')
                            ->label('Select Device')
                            ->options(\App\Models\Device::pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $device = \App\Models\Device::find($data['device_id']);
                        
                        if ($device) {
                            app(\App\Services\Attendance\DeviceCommandBuilder::class)->addUser($device, [
                                'pin' => $record->pin,
                                'name' => $record->name,
                                'card' => $record->card_number,
                                'privilege' => $record->privilege,
                                'group' => $record->group,
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Command queued')
                                ->body('The user will be synced to the device shortly.')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
