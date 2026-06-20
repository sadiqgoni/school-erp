<x-filament-panels::page>
    @php
        $updatedAt = $postings->max('updated_at');
        $grossTotal = $summary['basic_total'] + $summary['earnings_total'];
    @endphp

    @once
        <style>
            .sheet-cell-line{min-height:1.4rem;display:flex;align-items:center}
            .sheet-payslip-link{display:inline-flex;align-items:center;justify-content:center;padding:.34rem .75rem;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);color:#fff;font-size:.72rem;font-weight:800;text-decoration:none}
            .sheet-payslip-link:hover{background:rgba(255,255,255,.22)}
        </style>
    @endonce

    <div class="p-4 md:p-6 bg-gray-50 dark:bg-gray-800 rounded-lg shadow">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col mb-4 md:mb-0">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-blue-600 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Payroll Sheet: {{ $sheet->name }}</h1>
                </div>

                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 flex flex-col space-y-1">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                        </svg>
                        <span>Total Staff Members: <span class="font-medium">{{ number_format($summary['staff_count']) }}</span></span>
                    </div>
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        <span>Showing Payroll for: <span class="font-medium">{{ $monthLabel }}</span></span>
                    </div>
                    @if ($updatedAt)
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <span>Last Updated: <span class="font-medium">{{ $updatedAt->format('F j, Y \a\t g:i A') }}</span></span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col space-y-4 mt-4 md:mt-0">
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md p-3 text-blue-700 dark:text-blue-300 text-center">
                    <div class="text-xs font-medium uppercase">Number of Staff</div>
                    <div class="font-bold text-lg">{{ number_format($summary['staff_count']) }}</div>
                </div>
            </div>
        </div>

        <section class="mb-10 bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <header class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-5">
                <h2 class="text-xl font-bold flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{ strtoupper($sheet->name) }} - {{ $monthLabel }}
                </h2>
                <div class="flex items-center mt-2">
                    <span class="bg-blue-900/50 text-blue-100 text-xs px-3 py-1 rounded-full">
                        {{ number_format($summary['staff_count']) }} Staff Members
                    </span>
                </div>
            </header>

            <div class="overflow-auto max-w-full">
                <table class="min-w-max divide-y divide-gray-300 dark:divide-gray-600 text-sm">
                    <thead>
                        <tr class="bg-slate-700 text-white uppercase text-xs tracking-wider">
                            <th class="sticky left-0 z-10 bg-slate-700 px-4 py-3 text-left font-semibold w-56">
                                <div class="sheet-cell-line">First Name</div>
                                <div class="sheet-cell-line">Middle Name</div>
                                <div class="sheet-cell-line">Surname</div>
                                <div class="sheet-cell-line">Designation</div>
                                <div class="sheet-cell-line">ID Card No</div>
                                <div class="sheet-cell-line">Anniversary</div>
                                <div class="sheet-cell-line">Division</div>
                                <div class="sheet-cell-line">Payslip</div>
                            </th>

                            @foreach ($staffProfiles as $profile)
                                <th class="px-4 py-3 text-center font-semibold whitespace-nowrap align-top">
                                    <div class="sheet-cell-line text-xs text-slate-300 font-normal">{{ $profile['first_name'] }}</div>
                                    <div class="sheet-cell-line text-xs text-slate-300 font-normal">{{ $profile['middle_name'] }}</div>
                                    <div class="sheet-cell-line text-xs text-slate-300 font-normal">{{ $profile['surname'] }}</div>
                                    <div class="sheet-cell-line text-xs text-slate-300 font-normal">{{ $profile['designation'] }}</div>
                                    <div class="sheet-cell-line text-xs text-slate-300 font-normal">{{ $profile['staff_number'] }}</div>
                                    <div class="sheet-cell-line text-xs text-slate-300 font-normal">{{ $profile['anniversary'] }}</div>
                                    <div class="sheet-cell-line text-xs text-slate-300 font-normal">{{ $profile['division'] }}</div>
                                    <div class="sheet-cell-line w-full justify-center">
                                        <a href="{{ $profile['payslip_url'] }}" target="_blank" class="sheet-payslip-link">Payslip</a>
                                    </div>
                                </th>
                            @endforeach

                            <th class="px-4 py-3 text-center font-semibold bg-blue-700 whitespace-nowrap">
                                <div class="sheet-cell-line justify-center">Sheet Total</div>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr class="bg-green-700 dark:bg-green-800 text-white">
                            <th class="sticky left-0 z-10 bg-green-700 dark:bg-green-800 px-4 py-2 text-left font-semibold uppercase text-xs">
                                Earnings
                            </th>
                            @foreach ($staffProfiles as $profile)
                                <td class="px-4 py-2 text-center">&nbsp;{{ $profile['grade_label'] }}</td>
                            @endforeach
                            <td class="px-4 py-2 text-center bg-green-800 dark:bg-green-900">&nbsp;Total</td>
                        </tr>

                        <tr class="hover:bg-green-50 dark:hover:bg-green-900/20">
                            <th class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-200">
                                Basic Salary
                            </th>
                            @foreach ($staffProfiles as $profile)
                                <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-200">
                                    ₦{{ number_format($profile['basic_salary'], 2) }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-center bg-green-50 dark:bg-green-900/20 font-semibold text-green-800 dark:text-green-300">
                                ₦{{ number_format($summary['basic_total'], 2) }}
                            </td>
                        </tr>

                        @foreach ($earningRows as $row)
                            <tr class="hover:bg-green-50 dark:hover:bg-green-900/20">
                                <th class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-200">
                                    {{ $row['label'] }}
                                </th>
                                @foreach ($row['values'] as $value)
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-200">
                                        ₦{{ number_format($value, 2) }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center bg-green-50 dark:bg-green-900/20 font-semibold text-green-800 dark:text-green-300">
                                    ₦{{ number_format($row['total'], 2) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-green-100 dark:bg-green-800/30 font-bold">
                            <th class="sticky left-0 z-10 bg-green-100 dark:bg-green-800/30 px-4 py-3 text-left text-gray-800 dark:text-gray-100">
                                Gross Pay
                            </th>
                            @foreach ($staffProfiles as $profile)
                                <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">
                                    ₦{{ number_format($profile['gross_pay'], 2) }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-center bg-green-200 dark:bg-green-700/50 text-green-900 dark:text-green-100">
                                ₦{{ number_format($grossTotal, 2) }}
                            </td>
                        </tr>

                        <tr class="bg-red-700 dark:bg-red-800 text-white">
                            <th class="sticky left-0 z-10 bg-red-700 dark:bg-red-800 px-4 py-2 text-left font-semibold uppercase text-xs">
                                Deductions
                            </th>
                            @foreach ($staffProfiles as $profile)
                                <td class="px-4 py-2 text-center">&nbsp;</td>
                            @endforeach
                            <td class="px-4 py-2 text-center bg-red-800 dark:bg-red-900">&nbsp;</td>
                        </tr>

                        @foreach ($deductionRows as $row)
                            <tr class="hover:bg-red-50 dark:hover:bg-red-900/20">
                                <th class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-200">
                                    {{ $row['label'] }}
                                </th>
                                @foreach ($row['values'] as $value)
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-200">
                                        ₦{{ number_format($value, 2) }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center bg-red-50 dark:bg-red-900/20 font-semibold text-red-800 dark:text-red-300">
                                    ₦{{ number_format($row['total'], 2) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-red-100 dark:bg-red-800/30 font-bold">
                            <th class="sticky left-0 z-10 bg-red-100 dark:bg-red-800/30 px-4 py-3 text-left text-gray-800 dark:text-gray-100">
                                Total Deductions
                            </th>
                            @foreach ($staffProfiles as $profile)
                                <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">
                                    ₦{{ number_format($profile['deductions_total'], 2) }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-center bg-red-200 dark:bg-red-700/50 text-red-900 dark:text-red-100">
                                ₦{{ number_format($summary['deductions_total'], 2) }}
                            </td>
                        </tr>

                        <tr class="bg-yellow-100 dark:bg-yellow-700/30 font-bold text-lg">
                            <th class="sticky left-0 z-10 bg-yellow-100 dark:bg-yellow-700/30 px-4 py-3 text-left text-gray-800 dark:text-gray-100">
                                Net Pay
                            </th>
                            @foreach ($staffProfiles as $profile)
                                <td class="px-4 py-3 text-center text-green-700 dark:text-green-300">
                                    ₦{{ number_format($profile['net_pay'], 2) }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-center bg-yellow-200 dark:bg-yellow-600/50 text-yellow-900 dark:text-yellow-100">
                                ₦{{ number_format($summary['net_total'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
