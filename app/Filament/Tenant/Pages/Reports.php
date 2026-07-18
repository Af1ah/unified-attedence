<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Report;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Pages\Page;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

class Reports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.tenant.pages.reports';

    protected function getReportFormSchema(): array
    {
        return [
            Grid::make(3)->schema([
                Group::make()->schema([
                    Section::make('Configuration')->schema([
                        TextInput::make('name')
                            ->label('Template Name')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('type')
                            ->label('Report Type')
                            ->options([
                                'regular' => 'Regular Report',
                                'summary' => 'Summary Report',
                                'absent' => 'Absent Report',
                                'work_hours' => 'Work Hours Report',
                            ])
                            ->default('regular')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('group_by')
                            ->label('Group By')
                            ->options([
                                'branch' => 'Branch Wise',
                                'department' => 'Department Wise',
                                'work_group' => 'Work Group Wise',
                            ])
                            ->placeholder('None')
                            ->columnSpanFull(),
                    ]),
                    
                    Section::make('Date Range')->schema([
                        Select::make('date_range')
                            ->label('Period')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'custom' => 'Custom',
                            ])
                            ->default('daily')
                            ->required()
                            ->live(),
                        Grid::make(2)->schema([
                            DatePicker::make('filters.from_date')
                                ->label('From Date')
                                ->required(fn (Get $get) => $get('date_range') === 'custom')
                                ->visible(fn (Get $get) => $get('date_range') === 'custom'),
                            DatePicker::make('filters.to_date')
                                ->label('To Date')
                                ->required(fn (Get $get) => $get('date_range') === 'custom')
                                ->visible(fn (Get $get) => $get('date_range') === 'custom'),
                        ]),
                    ]),
                ])->columnSpan(['sm' => 3, 'md' => 1]),

                Group::make()->schema([
                    Section::make('Filters')->schema([
                        Select::make('filters.branch_id')
                            ->label('Filter Branch')
                            ->options(Branch::all()->pluck('display_name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->live()
                            ->placeholder('All Branches'),
                        Select::make('filters.department_id')
                            ->label('Filter Department')
                            ->options(function (Get $get) {
                                $branchIds = $get('filters.branch_id');
                                if (empty($branchIds)) {
                                    return Department::pluck('name', 'id');
                                }
                                return Department::whereHas('branches', fn ($q) => $q->whereIn('branches.id', $branchIds))->pluck('name', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->placeholder('All Departments'),
                        Select::make('filters.schedule_id')
                            ->label('Filter Shifts and schedules')
                            ->options(Schedule::pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->placeholder('All Shifts and schedules'),
                        Select::make('user_ids')
                            ->label('Filter Employee')
                            ->options(User::select('id', 'name', 'pin')->get()->mapWithKeys(fn($user) => [$user->id => $user->name . ' (ID: ' . ($user->pin ?? $user->id) . ')']))
                            ->multiple()
                            ->searchable()
                            ->placeholder('All Employees'),
                        Select::make('filters.group')
                            ->label('Filter Designation / Group')
                            ->options(fn () => User::whereNotNull('group')->where('group', '!=', '')->distinct()->pluck('group', 'group')->toArray())
                            ->multiple()
                            ->searchable()
                            ->placeholder('All Designations'),
                    ])->columns(2),

                    Section::make('Settings')->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_template')
                                ->label('Save as Template')
                                ->default(true),
                            Toggle::make('filters.show_logo')
                                ->label('Show Company Logo')
                                ->default(true),
                        ]),
                    ]),
                ])->columnSpan(['sm' => 3, 'md' => 2]),
            ])
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create Report')
                ->modalWidth('6xl')
                ->form($this->getReportFormSchema())
                ->action(function (array $data) {
                    Report::create([
                        'name' => $data['name'],
                        'date_range' => $data['date_range'],
                        'group_by' => $data['group_by'] ?? null,
                        'filters' => $data['filters'] ?? [],
                        'type' => $data['type'],
                        'user_ids' => $data['user_ids'] ?? [],
                        'is_template' => $data['is_template'],
                        'status' => 'pending',
                    ]);
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Report::query()->where('is_template', true))
            ->columns([
                TextColumn::make('name')->label('Template Name'),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('date_range')->label('Period')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('last_calculated_at')->label('Last Updates')->dateTime(),
            ])
            ->actions([
                Action::make('recalculate')
                    ->label('Recalculate')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Report $record) {
                        $record->update([
                            'status' => 'calculating',
                        ]);
                    }),
                EditAction::make()
                    ->modalWidth('6xl')
                    ->form($this->getReportFormSchema()),
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (Report $record) => view('filament.tenant.pages.report-view', ['report' => $record]))
                    ->modalSubmitAction(false)
            ]);
    }
}
