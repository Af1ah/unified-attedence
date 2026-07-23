<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item wire:click="$set('activeTab', 'daily')" :active="$activeTab === 'daily'">Daily Report</x-filament::tabs.item>
        <x-filament::tabs.item wire:click="$set('activeTab', 'weekly')" :active="$activeTab === 'weekly'">Weekly Report</x-filament::tabs.item>
        <x-filament::tabs.item wire:click="$set('activeTab', 'monthly')" :active="$activeTab === 'monthly'">Monthly Report</x-filament::tabs.item>
        <x-filament::tabs.item wire:click="$set('activeTab', 'templates')" :active="$activeTab === 'templates'">Saved Templates</x-filament::tabs.item>
    </x-filament::tabs>

    @if($activeTab === 'templates')
        {{ $this->table }}
    @else
        <form wire:submit="generate">
            {{ $this->form }}
            
            <div class="mt-4 flex gap-4">
                <x-filament::button type="submit" color="primary">Apply Filters & Preview</x-filament::button>
                <x-filament::button wire:click="downloadCsv" color="success" icon="heroicon-o-document-arrow-down">Download CSV</x-filament::button>
                <x-filament::button wire:click="downloadPdf" type="button" color="danger" icon="heroicon-o-document-arrow-down" x-on:click="window.print()">Print PDF</x-filament::button>
            </div>
        </form>

        @if($reportData)
            <div class="mt-8 overflow-x-auto bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10" id="report-print-area">
                <div class="p-4 border-b border-gray-200 dark:border-white/10">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        {{ ucfirst($activeTab) }} Attendance Report
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($filterData['from_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($filterData['to_date'])->format('M d, Y') }}
                    </p>
                </div>
                
                <table class="report-table min-w-full divide-y divide-gray-200 dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Employee</th>
                            @foreach ($reportData['period'] as $date)
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $date['day'] }}<br>
                                    <span class="text-xs font-normal text-gray-500">{{ $date['month_day'] }}</span>
                                </th>
                            @endforeach
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Total Hrs</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Present</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Absent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach ($reportData['data'] as $row)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300 font-medium">
                                {{ $row['user_name'] }}
                            </td>
                            @foreach ($reportData['period'] as $date)
                                @php
                                    $d = $date['date'];
                                    $dayData = $row['daily'][$d] ?? null;
                                @endphp
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    @if($dayData && $dayData['status'] === 'P')
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            {{ $dayData['display'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                            Absent
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-white font-bold">{{ $row['total_display'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-green-600 font-bold">{{ $row['present'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-red-600 font-bold">{{ $row['absent'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <style>
                .report-table {
                    border-collapse: collapse;
                    width: 100%;
                }
                .report-table th, .report-table td {
                    border: 1px solid #e5e7eb;
                    padding: 0.75rem;
                    text-align: left;
                }
                .dark .report-table th, .dark .report-table td {
                    border-color: rgba(255, 255, 255, 0.1);
                }
                .report-table thead {
                    background-color: #f9fafb;
                }
                .dark .report-table thead {
                    background-color: rgba(255, 255, 255, 0.05);
                }
                @media print {
                    body * {
                        visibility: hidden;
                    }
                    #report-print-area, #report-print-area * {
                        visibility: visible;
                    }
                    #report-print-area {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                        box-shadow: none !important;
                    }
                    .inline-flex {
                        border: 1px solid #ccc;
                    }
                }
            </style>
        @endif
    @endif
</x-filament-panels::page>
