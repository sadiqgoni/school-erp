<?php

namespace App\Filament\Resources\SalaryPostings\Pages;

use App\Filament\Resources\SalaryPostings\SalaryPostingResource;
use App\Models\PayrollSheet;
use App\Models\SalaryPosting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;

class ViewSalaryPostingMonth extends Page
{
    protected static string $resource = SalaryPostingResource::class;

    protected string $view = 'filament.resources.salary-postings.pages.view-salary-posting-month';

    public string $month;

    public function mount(string $month): void
    {
        $this->month = Carbon::parse($month)->startOfMonth()->toDateString();
    }

    public function getTitle(): string
    {
        return Carbon::parse($this->month)->format('F Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(SalaryPostingResource::getUrl('index')),
            Action::make('pdf')
                ->label('Monthly Report')
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
            ->with(['staff.payrollSheet'])
            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
            ->whereDate('payroll_month', $this->month)
            ->get();

        $sheets = PayrollSheet::query()
            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (PayrollSheet $sheet) use ($postings): array {
                $sheetPostings = $postings->filter(fn (SalaryPosting $posting): bool => $posting->staff?->payroll_sheet_id === $sheet->getKey())->values();

                return [
                    'sheet' => $sheet,
                    'staff_count' => $sheetPostings->count(),
                    'basic_total' => (float) $sheetPostings->sum('basic_salary'),
                    'earnings_total' => (float) $sheetPostings->sum('allowances_total'),
                    'deductions_total' => (float) $sheetPostings->sum('deductions_total'),
                    'net_total' => (float) $sheetPostings->sum('net_pay'),
                ];
            })
            ->filter(fn (array $sheet): bool => $sheet['staff_count'] > 0)
            ->values();

        return [
            'monthLabel' => Carbon::parse($this->month)->format('F Y'),
            'summary' => [
                'staff_count' => $postings->count(),
                'basic_total' => (float) $postings->sum('basic_salary'),
                'earnings_total' => (float) $postings->sum('allowances_total'),
                'deductions_total' => (float) $postings->sum('deductions_total'),
                'net_total' => (float) $postings->sum('net_pay'),
            ],
            'sheets' => $sheets,
        ];
    }
}
