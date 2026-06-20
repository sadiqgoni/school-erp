<x-filament-panels::page>
    @php
        $currentPath = request()->path();
        $isFinanceAccountSecretary = str_starts_with($currentPath, 'finance-account-secretary');
        
        // Dynamic route generation based on current panel
        $viewRoutePrefix = $isFinanceAccountSecretary ? 'filament.financeAccountSecretary' : 'filament.finance';
        
        // Get staff payroll information for this sheet
        $allLedgers = $record->payroll_cards()
            ->with(['StaffInfo', 'details.payrollItemType'])
            ->orderBy('payroll_date', 'desc')
            ->get();
            
        // Group ledgers by month
        $ledgersByMonth = $allLedgers->groupBy(function($ledger) {
            return $ledger->payroll_date->format('Y-m');
        });
        
        // Get all available months for the dropdown
        $availableMonths = $ledgersByMonth->keys()->map(function($month) {
            return [
                'value' => $month,
                'label' => date('F Y', strtotime($month))
            ];
        })->toArray();
        
        // Get selected month from request or default to latest
        $selectedMonth = request('month', $ledgersByMonth->keys()->first());

        // Get ledgers for selected month
        $ledgers = $ledgersByMonth->get($selectedMonth, collect());

        // Check if there are multiple payroll periods for this month
        $payrollPeriods = $ledgers->map(function($ledger) {
            return [
                'date' => $ledger->payroll_date->format('Y-m-d'),
                'label' => $ledger->payroll_date->format('M j, Y'),
                'short_label' => $ledger->payroll_date->format('M j')
            ];
        })->unique('date')->sortBy('date')->values();

        // Get selected payroll period (date) or default to first one
        $selectedPayrollPeriod = request('payroll_period', $payrollPeriods->first()['date'] ?? null);

        // Filter ledgers by selected payroll period if specified
        if ($selectedPayrollPeriod) {
            $ledgers = $ledgers->filter(function($ledger) use ($selectedPayrollPeriod) {
                return $ledger->payroll_date->format('Y-m-d') === $selectedPayrollPeriod;
            });
        }

        // Create staff data for display (no aggregation since we're showing individual payrolls)
        $staffMembersData = [];
        foreach ($ledgers as $ledger) {
            $staffInfo = $ledger->StaffInfo;

            $staffMembersData[] = [
                'staff_info' => $staffInfo,
                'ledger' => $ledger, // Single ledger for this payroll period
                'total_earnings' => $ledger->total_earnings,
                'total_deductions' => $ledger->total_deductions,
                'total_net_pay' => $ledger->net_pay,
                'total_employer_contributions' => $ledger->total_employer_contributions,
            ];
        }
        
        // Payroll data is now handled directly in the template using $staffMembersData

        // Get all payroll item types
        $payrollItemTypes = \App\Models\Finance\PayrollItemType::where('is_active', true)->get();
        $allEarningTypes = $payrollItemTypes->where('type', 'earning');
        // ->sortBy('name');
        $allDeductionTypes = $payrollItemTypes->where('type', 'deduction');
        $allEmployerContributionTypes = $payrollItemTypes->where('type', 'employer_contribution');

        // Initialize totals
        $sheetTotals = [
            'gross_pay' => 0,
            'total_deductions' => 0,
            'net_pay' => 0,
            'pfa' => 0,
            'earnings' => [],
            'deductions' => [],
        ];
        
        foreach ($allEarningTypes as $item) {
            $sheetTotals['earnings'][$item->id] = 0;
        }
        
        foreach ($allDeductionTypes as $item) {
            $sheetTotals['deductions'][$item->id] = 0;
        }
        
        // Determine which earning items have non-zero values
        $earningItemsWithValues = [];
        foreach ($allEarningTypes as $earningType) {
            $hasValue = false;
            foreach ($ledgers as $ledger) {
                $amount = $ledger->details->where('payroll_item_type_id', $earningType->id)->first()->amount ?? 0;
                if ($amount > 0) {
                    $hasValue = true;
                    break;
                }
            }
            $earningItemsWithValues[$earningType->id] = $hasValue;
        }
        
        // Determine which deduction items have non-zero values
        $deductionItemsWithValues = [];
        foreach ($allDeductionTypes as $deductionType) {
            $hasValue = false;
            foreach ($ledgers as $ledger) {
                $amount = $ledger->details->where('payroll_item_type_id', $deductionType->id)->first()->amount ?? 0;
                if ($amount > 0) {
                    $hasValue = true;
                    break;
                }
            }
            $deductionItemsWithValues[$deductionType->id] = $hasValue;
        }
        
        // Filter earning types - keep only those with values or the first 6
        $filteredEarningTypes = $allEarningTypes->filter(function ($item, $key) use ($earningItemsWithValues, $allEarningTypes) {
            return $earningItemsWithValues[$item->id] || $key < 6;
        });
        
        // Filter deduction types - keep only those with values or the first 6
        $filteredDeductionTypes = $allDeductionTypes->filter(function ($item, $key) use ($deductionItemsWithValues, $allDeductionTypes) {
            return $deductionItemsWithValues[$item->id] || $key < 6;
        });
        
        // Format the selected month for display
        $selectedMonthDisplay = date('F Y', strtotime($selectedMonth));
    @endphp



    <div class="p-4 md:p-6 bg-gray-50 dark:bg-gray-800 rounded-lg shadow">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col mb-4 md:mb-0">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-blue-600 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Payroll Sheet: {{ $record->name }}</h1>
                </div>
                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 flex flex-col space-y-1">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                        </svg>
                        <span>Total Staff Members: <span class="font-medium">{{ count($staffMembersData) }}</span></span>
                    </div>
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        <span>Showing Payroll for: <span class="font-medium">{{ $selectedMonthDisplay }}</span></span>
                    </div>
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        <span>Last Updated: <span class="font-medium">{{ $record->updated_at->format('F j, Y \a\t g:i A') }}</span></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons and Controls -->
            <div class="flex flex-col space-y-4 mt-4 md:mt-0">
                <!-- Staff count card -->
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md p-3 text-blue-700 dark:text-blue-300 text-center">
                    <div class="text-xs font-medium uppercase">Number of Staff</div>
                    <div class="font-bold text-lg">{{ count($staffMembersData) }}</div>
                </div>
                
                <!-- Action buttons container -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <!-- Month and Payroll Period selector form -->
                    <div class="col-span-full">
                        <form action="{{ route($viewRoutePrefix . '.resources.payroll-sheets.view', $record) }}" method="GET" class="w-auto flex flex-wrap items-center gap-2">
                            <!-- Month Selector -->
                            <div class="flex items-center space-x-2">
                                <label for="month-selector" class="text-sm font-medium text-gray-700 dark:text-gray-300">Month:</label>
                                <select name="month" id="month-selector" class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white text-sm">
                                    @foreach ($availableMonths as $month)
                                        <option value="{{ $month['value'] }}" {{ $selectedMonth == $month['value'] ? 'selected' : '' }}>
                                            {{ $month['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payroll Period Selector (only show if multiple periods exist) -->
                            @if($payrollPeriods->count() > 1)
                                <div class="flex items-center space-x-2">
                                    <label for="payroll-period-selector" class="text-sm font-medium text-gray-700 dark:text-gray-300">Payroll Period:</label>
                                    <select name="payroll_period" id="payroll-period-selector" class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white text-sm">
                                        @foreach ($payrollPeriods as $period)
                                            <option value="{{ $period['date'] }}" {{ $selectedPayrollPeriod == $period['date'] ? 'selected' : '' }}>
                                                {{ $period['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-xs font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:ring-offset-gray-800">
                                <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                                </svg>
                                Update
                            </button>
                        </form>
                    </div>
                    
                    <!-- Create New Salary Card button -->
                    <a href="#" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white font-medium rounded-md transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Create Salary Card
                    </a>
                    
                    <!-- Download PDF button -->
                    <a href="{{ route('payroll-sheet.download', ['record' => $record, 'month' => $selectedMonth]) }}" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white font-medium rounded-md transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Download PDF
                    </a>
                </div>
            </div>
        </div>

        @if ($ledgers->isEmpty())
            <div class="bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 dark:border-yellow-600 text-yellow-700 dark:text-yellow-300 p-4 rounded-sm" role="alert">
                <p class="font-bold">No Data</p>
                <p class="mb-3">No payroll data has been added to this sheet for {{ $selectedMonthDisplay }}.</p>
                <a href="{{ route($viewRoutePrefix . '.resources.payroll-ledgers.create', ['payroll_sheet_id' => $record->id]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white font-medium rounded-md transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Create First Salary Card
                </a>
            </div>
        @else
            <section class="mb-10 bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <header class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-5">
                    <h2 class="text-xl font-bold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ $record->name }} - {{ $selectedMonthDisplay }}
                        @if($payrollPeriods->count() > 1 && $selectedPayrollPeriod)
                            <span class="ml-2 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-sm rounded-md">
                                {{ \Carbon\Carbon::parse($selectedPayrollPeriod)->format('M j, Y') }}
                            </span>
                        @endif
                    </h2>
                    <div class="flex items-center mt-2">
                        <span class="bg-blue-900/50 text-blue-100 text-xs px-3 py-1 rounded-full">
                            {{ count($staffMembersData) }} Staff Members
                        </span>
                    </div>
                </header>

                <div class="overflow-auto max-w-full">
                    <!-- Transposed table with headers on left side and staff data as columns -->
                    <table class="min-w-max divide-y divide-gray-300 dark:divide-gray-600 text-sm">
                        <!-- Header row with staff names -->
                        <thead>
                            <tr class="bg-slate-700 text-white uppercase text-xs tracking-wider">
                                <th class="sticky left-0 z-10 bg-slate-700 px-4 py-3 text-left font-semibold w-48">
                                    <div class="mt-1">First Name</div>
                                    <div class="mt-1">Middle Name</div>
                                    <div class="mt-1">Surname</div>
                                    <div class="mt-1">Designation</div>
                                    <div class="mt-1">ID Card No</div>
                                    <div class="mt-1">Anniversary</div>
                                    <div class="mt-1">Division</div>
                                </th>

                                @foreach ($staffMembersData as $index => $staffData)
                                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">
                                        <div class="text-xs text-slate-300 font-normal mt-1"> {{ $staffData['staff_info']->first_name ?? '' }}</div>
                                        <div class="text-xs text-slate-300 font-normal mt-1"> {{ $staffData['staff_info']->middle_name ?? '' }}</div>
                                        <div class="text-xs text-slate-300 font-normal mt-1"> {{ $staffData['staff_info']->surname ?? '' }}</div>

                                        <div class="text-xs text-slate-300 font-normal mt-1"> {{ $staffData['staff_info']->designation ?? 'N/A' }}</div>
                                        <div class="text-xs text-slate-300 font-normal mt-1"> {{ $staffData['staff_info']->id_card_no ?? 'No ID' }}</div>
                                        <div class="text-xs text-slate-300 font-normal mt-1"> {{ $staffData['staff_info']->anniversary_month ?? 'N/A' }}</div>
                                        <div class="text-xs text-slate-300 font-normal mt-1"> {{ $staffData['staff_info']->division->name ?? 'N/A' }}</div>

                                        <!-- Add payslip download buttons for the single ledger -->
                                        <div class="mt-3 space-y-1">
                                            <a href="{{ route('payroll-sheet.download-payslip', $staffData['ledger']) }}" class="block bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white text-xs px-2 py-1 rounded-md transition-colors shadow-sm text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Payslip
                                            </a>

                                            @php
                                                $hasTelegram = $staffData['ledger']->StaffInfo->telegram_chat_id && $staffData['ledger']->StaffInfo->telegram_notifications;
                                            @endphp

                                            @if($hasTelegram)
                                                <button onclick="sendPayslipViaTelegram({{ $staffData['ledger']->id }}, {{ json_encode($staffData['ledger']->StaffInfo->full_name) }})"
                                                        class="block w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white text-xs px-2 py-1 rounded-md transition-colors shadow-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                    Telegram
                                                </button>
                                            @else
                                                <button disabled class="block w-full bg-gray-400 text-white text-xs px-2 py-1 rounded-md cursor-not-allowed opacity-60">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                                                    </svg>
                                                    No Telegram
                                                </button>
                                            @endif

                                            @php
                                                // Check if staff has a confidential personal folder
                                                $hasConfidentialFolder = \App\Models\EFileFolder::where('staff_id', $staffData['ledger']->StaffInfo->id ?? null)
                                                    ->where('folder_type', \App\Models\EFileFolder::TYPE_PERSONAL)
                                                    ->where('confidentiality_level', 'confidential')
                                                    ->exists();
                                            @endphp

                                            @if($hasConfidentialFolder)
                                                <button onclick="sendToPersonalFile({{ $staffData['ledger']->id }}, {{ json_encode($staffData['ledger']->StaffInfo->full_name) }})"
                                                        class="block w-full bg-purple-600 hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-600 text-white text-xs px-2 py-1 rounded-md transition-colors shadow-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Personal File
                                                </button>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                                <th class="px-4 py-3 text-center font-semibold bg-blue-700 whitespace-nowrap">
                                    Sheet Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Earnings section header -->
                            <tr class="bg-green-700 dark:bg-green-800 text-white">
                                <th class="sticky left-0 z-10 bg-green-700 dark:bg-green-800 px-4 py-2 text-left font-semibold uppercase text-xs">
                                    Earnings
                                </th>
                                @foreach ($staffMembersData as $staffData)
                                    <td class="px-4 py-2 text-center">
                                        &nbsp;GL {{ $staffData['ledger']->grade_step ?? 'N/A' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-2 text-center bg-green-800 dark:bg-green-900">
                                    &nbsp;Total
                                </td>
                            </tr>

                            <!-- Earnings item rows -->
                            @foreach ($filteredEarningTypes as $earningType)
                                @php
                                    $groupTotal = 0; // Reset group total for this earning type

                                    // Skip displaying Transport for management staff
                                    $isTransportForManagement = $earningType->name === 'Transport' &&
                                                              strtolower($record->name) === 'management';

                                    if ($isTransportForManagement) {
                                        // Still calculate the totals, just don't display the row
                                        foreach ($staffMembersData as $staffData) {
                                            $amount = $staffData['ledger']->details->where('payroll_item_type_id', $earningType->id)->first()->amount ?? 0;
                                            $groupTotal += $amount;
                                            $sheetTotals['earnings'][$earningType->id] = $groupTotal;
                                        }
                                        continue; // Skip to next earning type without rendering
                                    }
                                @endphp
                                <tr class="hover:bg-green-50 dark:hover:bg-green-900/20">
                                    <th class="sticky left-0 z-10 bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-green-900/20 px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-200">
                                        {{ $earningType->name }}
                                    </th>
                                    @foreach ($staffMembersData as $staffData)
                                        @php
                                            $amount = $staffData['ledger']->details->where('payroll_item_type_id', $earningType->id)->first()->amount ?? 0;
                                            $groupTotal += $amount;
                                            $sheetTotals['earnings'][$earningType->id] = $groupTotal;
                                        @endphp
                                        <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-200">
                                            ₦{{ number_format($amount, 2) }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-center bg-green-50 dark:bg-green-900/20 font-semibold text-green-800 dark:text-green-300">
                                        ₦{{ number_format($groupTotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                            
                            <!-- Gross Pay row -->
                            <tr class="bg-green-100 dark:bg-green-800/30 font-bold">
                                <th class="sticky left-0 z-10 bg-green-100 dark:bg-green-800/30 px-4 py-3 text-left text-gray-800 dark:text-gray-100">
                                    Gross Pay
                                </th>
                                @php
                                    $grossPayTotal = 0; // Reset the total
                                @endphp
                                @foreach ($staffMembersData as $staffData)
                                    @php
                                        $grossPayTotal += $staffData['ledger']->total_earnings;
                                        $sheetTotals['gross_pay'] = $grossPayTotal;
                                    @endphp
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">
                                        ₦{{ number_format($staffData['ledger']->total_earnings, 2) }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center bg-green-200 dark:bg-green-700/50 text-green-900 dark:text-green-100">
                                    ₦{{ number_format($grossPayTotal, 2) }}
                                </td>
                            </tr>

                            <!-- Deductions section header -->
                            <tr class="bg-red-700 dark:bg-red-800 text-white">
                                <th class="sticky left-0 z-10 bg-red-700 dark:bg-red-800 px-4 py-2 text-left font-semibold uppercase text-xs">
                                    Deductions
                                </th>
                                @foreach ($staffMembersData as $staffData)
                                    <td class="px-4 py-2 text-center">
                                        &nbsp;
                                    </td>
                                @endforeach
                                <td class="px-4 py-2 text-center bg-red-800 dark:bg-red-900">
                                    &nbsp;
                                </td>
                            </tr>

                            <!-- Deductions item rows -->
                            @foreach ($filteredDeductionTypes as $deductionType)
                                @php
                                    $groupTotal = 0; // Reset group total for this deduction type
                                @endphp
                                <tr class="hover:bg-red-50 dark:hover:bg-red-900/20">
                                    <th class="sticky left-0 z-10 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/20 px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-200">
                                        {{ $deductionType->name }}
                                    </th>
                                    @foreach ($staffMembersData as $staffData)
                                        @php
                                            $amount = $staffData['ledger']->details->where('payroll_item_type_id', $deductionType->id)->first()->amount ?? 0;
                                            $groupTotal += $amount;
                                            $sheetTotals['deductions'][$deductionType->id] = $groupTotal;
                                        @endphp
                                        <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-200">
                                            ₦{{ number_format($amount, 2) }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-center bg-red-50 dark:bg-red-900/20 font-semibold text-red-800 dark:text-red-300">
                                        ₦{{ number_format($groupTotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Total Deductions row -->
                            <tr class="bg-red-100 dark:bg-red-800/30 font-bold">
                                <th class="sticky left-0 z-10 bg-red-100 dark:bg-red-800/30 px-4 py-3 text-left text-gray-800 dark:text-gray-100">
                                    Total Deductions
                                </th>
                                @php
                                    $totalDeductionsSum = 0; // Reset the total
                                @endphp
                                @foreach ($staffMembersData as $staffData)
                                    @php
                                        $totalDeductionsSum += $staffData['ledger']->total_deductions;
                                        $sheetTotals['total_deductions'] = $totalDeductionsSum;
                                    @endphp
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">
                                        ₦{{ number_format($staffData['ledger']->total_deductions, 2) }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center bg-red-200 dark:bg-red-700/50 text-red-900 dark:text-red-100">
                                    ₦{{ number_format($totalDeductionsSum, 2) }}
                                </td>
                            </tr>

                            <!-- Net Pay row -->
                            <tr class="bg-yellow-100 dark:bg-yellow-700/30 font-bold text-lg">
                                <th class="sticky left-0 z-10 bg-yellow-100 dark:bg-yellow-700/30 px-4 py-3 text-left text-gray-800 dark:text-gray-100">
                                    Net Pay
                                </th>
                                @php
                                    $netPayTotal = 0; // Reset the total
                                @endphp
                                @foreach ($staffMembersData as $staffData)
                                    @php
                                        $netPayTotal += $staffData['ledger']->net_pay;
                                        $sheetTotals['net_pay'] = $netPayTotal;
                                    @endphp
                                    <td class="px-4 py-3 text-center text-green-700 dark:text-green-300">
                                        ₦{{ number_format($staffData['ledger']->net_pay, 2) }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center bg-yellow-200 dark:bg-yellow-600/50 text-yellow-900 dark:text-yellow-100">
                                    ₦{{ number_format($netPayTotal, 2) }}
                                </td>
                            </tr>
                            
                            <!-- Employer Contributions section header -->
                            {{-- <tr class="bg-blue-700 text-white">
                                <th class="sticky left-0 z-10 bg-blue-700 px-4 py-2 text-left font-semibold uppercase text-xs">
                                    Employer Contributions
                                </th> --}}
                                @foreach ($staffMembersData as $staffData)
                                    <td class="px-4 py-2 text-center">
                                        &nbsp;
                                    </td>
                                @endforeach
                                {{-- <td class="px-4 py-2 text-center bg-blue-800">
                                    &nbsp;
                                </td>
                            </tr> --}}

                            <!-- Employer Contributions item rows -->
                            @php
                                // Initialize total for all employer contributions
                                $employerContributionsTotal = 0;
                                $sheetTotals['employer_contributions'] = [];
                            @endphp
                            
                            @foreach ($allEmployerContributionTypes as $contributionType)
                                @php
                                    $groupTotal = 0; // Reset group total for this contribution type
                                @endphp
                                <tr class="hover:bg-blue-50">
                                    <th class="sticky left-0 z-10 bg-white hover:bg-blue-50 px-4 py-3 text-left font-medium">
                                        {{ $contributionType->name }}
                                    </th>
                                    @foreach ($staffMembersData as $staffData)
                                        @php
                                            $contributionDetail = $staffData['ledger']->details->where('payroll_item_type_id', $contributionType->id)->first();
                                            $amount = $contributionDetail ? $contributionDetail->amount : 0;
                                            $groupTotal += $amount;
                                            $sheetTotals['employer_contributions'][$contributionType->id] = $groupTotal;
                                        @endphp
                                        <td class="px-4 py-3 text-center text-gray-900">
                                            ₦{{ number_format($amount, 2) }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-center bg-blue-50 font-semibold text-blue-800">
                                        ₦{{ number_format($groupTotal, 2) }}
                                    </td>
                                </tr>
                                @php
                                    $employerContributionsTotal += $groupTotal;
                                @endphp
                            @endforeach

                            <!-- Total Employer Contributions row -->
                            <tr class="bg-blue-100 font-bold">
                                {{-- <th class="sticky left-0 z-10 bg-blue-100 px-4 py-3 text-left">
                                    Total Employer Contributions
                                </th> --}}
                                @php
                                    $ledgerContributionsTotal = 0; // Reset the per-ledger total
                                @endphp
                                @foreach ($staffMembersData as $staffData)
                                    @php
                                        $ledgerContributionsTotal = $staffData['ledger']->total_employer_contributions ?? 0;
                                        $sheetTotals['total_employer_contributions'] = $employerContributionsTotal;
                                    @endphp
                                    {{-- <td class="px-4 py-3 text-center text-blue-700">
                                        ₦{{ number_format($ledgerContributionsTotal, 2) }}
                                    </td> --}}
                                @endforeach
                                {{-- <td class="px-4 py-3 text-center bg-blue-200 text-blue-900">
                                    ₦{{ number_format($employerContributionsTotal, 2) }}
                                </td> --}}
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                @if($ledgers->isNotEmpty())
                <div class="p-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex flex-wrap gap-4 justify-end">
                        <div class="px-4 py-2 bg-green-50 border border-green-200 rounded-md">
                            <span class="text-xs text-green-600 font-medium">Sheet Gross Pay</span>
                            <div class="text-green-700 font-bold">₦{{ number_format($sheetTotals['gross_pay'], 2) }}</div>
                        </div>
                        <div class="px-4 py-2 bg-red-50 border border-red-200 rounded-md">
                            <span class="text-xs text-red-600 font-medium">Sheet Deductions</span>
                            <div class="text-red-700 font-bold">₦{{ number_format($sheetTotals['total_deductions'], 2) }}</div>
                        </div>
                        <div class="px-4 py-2 bg-yellow-50 border border-yellow-200 rounded-md">
                            <span class="text-xs text-yellow-600 font-medium">Sheet Net Pay</span>
                            <div class="text-yellow-700 font-bold">₦{{ number_format($sheetTotals['net_pay'], 2) }}</div>
                        </div>
                        <div class="px-4 py-2 bg-blue-50 border border-blue-200 rounded-md">
                            <span class="text-xs text-blue-600 font-medium">PFA Employer Contributions</span>
                            <div class="text-blue-700 font-bold">₦{{ number_format($sheetTotals['total_employer_contributions'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>
                @endif
            </section>
        @endif
    </div>

    <script>
        function sendPayslipViaTelegram(ledgerId, staffName) {
            console.log('Function called with:', {ledgerId, staffName, type: typeof staffName});
            
            // Ensure staffName is a string and escape special characters
            const safeName = String(staffName || 'Staff Member');
            
            if (!confirm(`Are you sure you want to send the payslip for ${safeName} via Telegram?`)) {
                return;
            }
            
            // Get the button that was clicked
            const button = event.target.closest('button');
            if (!button) {
                console.error('Button not found');
                alert('Error: Could not find button element');
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="animate-spin inline-block">⟳</span> Sending...';
            button.disabled = true;
            
            console.log('Making request for ledger ID:', ledgerId);
            
            // Make AJAX request to send payslip
            fetch(`{{ route('payroll.send-telegram') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    ledger_id: parseInt(ledgerId)
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Error response:', text);
                        throw new Error(`HTTP ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Success response:', data);
                
                if (data.success) {
                    // Show success notification
                    button.innerHTML = '<span class="text-green-300">✓ Sent!</span>';
                    button.className = button.className.replace('bg-blue-600', 'bg-green-600').replace('hover:bg-blue-700', 'hover:bg-green-700');
                    
                    // Reset button after 3 seconds
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.className = button.className.replace('bg-green-600', 'bg-blue-600').replace('hover:bg-green-700', 'hover:bg-blue-700');
                        button.disabled = false;
                    }, 3000);
                    
                    alert(`Payslip sent successfully to ${safeName} via Telegram!`);
                } else {
                    // Show error
                    button.innerHTML = originalText;
                    button.disabled = false;
                    console.error('Server error:', data);
                    alert('Error sending payslip: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                button.innerHTML = originalText;
                button.disabled = false;
                
                // More detailed error message
                if (error.message.includes('401')) {
                    alert('Error: You are not authenticated. Please log in and try again.');
                } else if (error.message.includes('403')) {
                    alert('Error: You do not have permission to perform this action.');
                } else if (error.message.includes('404')) {
                    alert('Error: The service endpoint was not found.');
                } else if (error.message.includes('419')) {
                    alert('Error: Your session has expired. Please refresh the page and try again.');
                } else if (error.message.includes('500')) {
                    alert('Error: Server error occurred. Please check the logs.');
                } else {
                    alert('Error sending payslip: ' + error.message + '. Please try again.');
                }
            });
        }

        function sendToPersonalFile(ledgerId, staffName) {
            console.log('Send to Personal File called with:', {ledgerId, staffName, type: typeof staffName});
            const safeName = String(staffName || 'Staff Member');
            // No folder type dialog, always confidential
            const button = event.target.closest('button');
            if (!button) {
                console.error('Button not found');
                alert('Error: Could not find button element');
                return;
            }
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="animate-spin inline-block">⟳</span> Sending...';
            button.disabled = true;
            fetch(`/payroll/send-to-personal-file/${ledgerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    confidential: true // Always confidential
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(`HTTP ${response.status}: ${text}`); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    button.innerHTML = '<span class="text-green-300">✓ Filed!</span>';
                    button.className = button.className.replace('bg-purple-600', 'bg-green-600').replace('hover:bg-purple-700', 'hover:bg-green-700');
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.className = button.className.replace('bg-green-600', 'bg-purple-600').replace('hover:bg-green-700', 'hover:bg-purple-700');
                        button.disabled = false;
                    }, 3000);
                    alert(`Payslip sent successfully to ${safeName}'s confidential personal file!`);
                } else {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    alert('Error sending to personal file: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                button.innerHTML = originalText;
                button.disabled = false;
                    alert('Error sending to personal file: ' + error.message + '. Please try again.');
            });
        }
    </script>
</x-filament-panels::page>



