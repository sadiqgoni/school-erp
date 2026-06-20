<?php

namespace App\Filament\Resources\SalaryPostings\Pages;

use App\Filament\Resources\SalaryPostings\SalaryPostingResource;
use App\Models\PayrollSheet;
use App\Models\SalaryPosting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;

class ViewSalaryPostingSheet extends Page
{
    protected static string $resource = SalaryPostingResource::class;

    protected string $view = 'filament.resources.salary-postings.pages.view-payroll-sheet';

    public string $month;

    public PayrollSheet $payrollSheet;

    public function mount(string $month, string $sheet): void
    {
        $this->month = Carbon::parse($month)->startOfMonth()->toDateString();

        $this->payrollSheet = PayrollSheet::query()
            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
            ->findOrFail($sheet);
    }

    public function getTitle(): string
    {
        return $this->payrollSheet->name.' Payroll Sheet';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(SalaryPostingResource::getUrl('month', ['month' => $this->month])),
            Action::make('pdf')
                ->label('Month Report')
                ->icon('heroicon-m-document-arrow-down')
                ->color('gray')
                ->url(route('salary-postings.month-pdf', [
                    'school' => Filament::getTenant(),
                    'month' => $this->month,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getViewData(): array
    {
        $postings = SalaryPosting::query()
            ->with(['staff.department'])
            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
            ->whereDate('payroll_month', $this->month)
            ->whereHas('staff', fn ($query) => $query->where('payroll_sheet_id', $this->payrollSheet->getKey()))
            ->orderBy('staff_name')
            ->get();

        $staffProfiles = $postings->map(function (SalaryPosting $posting): array {
            $staff = $posting->staff;

            return [
                'posting' => $posting,
                'first_name' => $staff?->first_name ?: $posting->staff_name,
                'middle_name' => $staff?->middle_name ?: '',
                'surname' => $staff?->last_name ?: '',
                'designation' => $staff?->job_title ?: '',
                'staff_number' => $posting->staff_number,
                'anniversary' => $staff?->hire_date?->format('F') ?: '',
                'division' => $staff?->department?->name ?: '',
                'grade_label' => collect([$posting->grade_level, $posting->step ? 'Step '.$posting->step : null])->filter()->implode(' / '),
                'basic_salary' => (float) $posting->basic_salary,
                'gross_pay' => (float) $posting->gross_pay,
                'deductions_total' => (float) $posting->deductions_total,
                'net_pay' => (float) $posting->net_pay,
                'payslip_url' => route('salary-postings.pdf', ['posting' => $posting]),
                'earnings' => collect($posting->allowance_breakdown ?? [])
                    ->mapWithKeys(fn (array $item): array => [$item['name'] => (float) ($item['amount'] ?? 0)])
                    ->all(),
                'deductions' => collect($posting->deduction_breakdown ?? [])
                    ->mapWithKeys(fn (array $item): array => [$item['name'] => (float) ($item['amount'] ?? 0)])
                    ->all(),
            ];
        })->values();

        $earningNames = $staffProfiles
            ->flatMap(fn (array $profile): array => array_keys($profile['earnings']))
            ->unique()
            ->values();

        $deductionNames = $staffProfiles
            ->flatMap(fn (array $profile): array => array_keys($profile['deductions']))
            ->unique()
            ->values();

        $earningRows = $earningNames->map(function (string $name) use ($staffProfiles): array {
            $values = $staffProfiles->map(fn (array $profile): float => (float) ($profile['earnings'][$name] ?? 0))->all();

            return [
                'label' => $name,
                'values' => $values,
                'total' => array_sum($values),
            ];
        })->all();

        $deductionRows = $deductionNames->map(function (string $name) use ($staffProfiles): array {
            $values = $staffProfiles->map(fn (array $profile): float => (float) ($profile['deductions'][$name] ?? 0))->all();

            return [
                'label' => $name,
                'values' => $values,
                'total' => array_sum($values),
            ];
        })->all();

        return [
            'sheet' => $this->payrollSheet,
            'monthLabel' => Carbon::parse($this->month)->format('F Y'),
            'postings' => $postings,
            'staffProfiles' => $staffProfiles,
            'earningRows' => $earningRows,
            'deductionRows' => $deductionRows,
            'summary' => [
                'staff_count' => $postings->count(),
                'basic_total' => (float) $postings->sum('basic_salary'),
                'earnings_total' => (float) $postings->sum('allowances_total'),
                'deductions_total' => (float) $postings->sum('deductions_total'),
                'net_total' => (float) $postings->sum('net_pay'),
            ],
        ];
    }
}
