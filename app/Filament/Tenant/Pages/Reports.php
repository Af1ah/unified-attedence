<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Report;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\User;
use App\Services\Attendance\ReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Carbon\Carbon;

class Reports extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.tenant.pages.reports';

    public $activeTab = 'daily';
    public $reportData = null;

    public ?array $filterData = [];

    public function mount()
    {
        $this->form->fill([
            'branch_id' => [],
            'department_id' => [],
            'user_ids' => [],
            'from_date' => now()->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
        ]);
        
        $this->generate();
    }

    public function form($form)
    {
        return $form
            ->schema([
                Select::make('branch_id')
                    ->label('Branch')
                    ->options(Branch::all()->pluck('display_name', 'id'))
                    ->multiple()->searchable(),
                Select::make('department_id')
                    ->label('Department')
                    ->options(Department::pluck('name', 'id'))
                    ->multiple()->searchable(),
                Select::make('user_ids')
                    ->label('Employees')
                    ->options(User::pluck('name', 'id'))
                    ->multiple()->searchable(),
                DatePicker::make('from_date')->label('From Date')->required(),
                DatePicker::make('to_date')->label('To Date')->required(),
            ])
            ->columns(5)
            ->statePath('filterData');
    }

    public function updatedActiveTab()
    {
        if ($this->activeTab === 'daily') {
            $this->filterData['from_date'] = now()->format('Y-m-d');
            $this->filterData['to_date'] = now()->format('Y-m-d');
        } elseif ($this->activeTab === 'weekly') {
            $this->filterData['from_date'] = now()->startOfWeek()->format('Y-m-d');
            $this->filterData['to_date'] = now()->endOfWeek()->format('Y-m-d');
        } elseif ($this->activeTab === 'monthly') {
            $this->filterData['from_date'] = now()->startOfMonth()->format('Y-m-d');
            $this->filterData['to_date'] = now()->endOfMonth()->format('Y-m-d');
        }
        
        if ($this->activeTab !== 'templates') {
            $this->generate();
        }
    }

    public function generate()
    {
        $data = $this->form->getState();
        
        $query = User::query();
        if (!empty($data['branch_id'])) {
            $query->whereIn('branch_id', $data['branch_id']);
        }
        if (!empty($data['department_id'])) {
            $query->whereIn('department_id', $data['department_id']);
        }
        if (!empty($data['user_ids'])) {
            $query->whereIn('id', $data['user_ids']);
        }
        
        $userIds = $query->pluck('id')->toArray();
        
        if (empty($userIds)) {
            $this->reportData = null;
            return;
        }

        $service = new ReportService();
        $this->reportData = $service->generateReport(
            $userIds, 
            $data['from_date'] ?? now()->format('Y-m-d'), 
            $data['to_date'] ?? now()->format('Y-m-d')
        );
    }

    public function downloadCsv()
    {
        $this->generate();
        if (!$this->reportData) return;
        
        $csvData = [];
        // Header
        $header = ['Employee'];
        foreach ($this->reportData['period'] as $date) {
            $header[] = $date['month_day'];
        }
        $header[] = 'Total Hrs';
        $header[] = 'Present';
        $header[] = 'Absent';
        $csvData[] = implode(',', $header);
        
        // Rows
        foreach ($this->reportData['data'] as $row) {
            $csvRow = ['"' . $row['user_name'] . '"'];
            foreach ($this->reportData['period'] as $date) {
                $d = $date['date'];
                $csvRow[] = $row['daily'][$d]['display'] ?? 'Absent';
            }
            $csvRow[] = $row['total_display'];
            $csvRow[] = $row['present'];
            $csvRow[] = $row['absent'];
            $csvData[] = implode(',', $csvRow);
        }
        
        return response()->streamDownload(function() use ($csvData) {
            echo implode("\n", $csvData);
        }, $this->activeTab . '_report.csv');
    }

    public function downloadPdf()
    {
        // We will just do a JS print for now, it's easier and looks better than raw DomPDF without styles
        $this->dispatch('print-report');
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
                \Filament\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (Report $record) => view('filament.tenant.pages.report-view', ['report' => $record]))
                    ->modalSubmitAction(false)
            ]);
    }
}
