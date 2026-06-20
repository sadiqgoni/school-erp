<?php

namespace App\Filament\Resources\SalaryPostings\Pages;

use App\Filament\Resources\SalaryPostings\SalaryPostingResource;
use App\Models\SalaryPosting;
use App\Models\Staff;
use App\Support\PayrollCalculator;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ListSalaryPostings extends ListRecords
{
    protected static string $resource = SalaryPostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('postMonthlySalary')
                ->label('Post monthly salary')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->modalWidth('lg')
                ->schema([
                    DatePicker::make('payroll_month')
                        ->label('Payroll month')
                        ->native(false)
                        ->displayFormat('F Y')
                        ->default(now()->startOfMonth())
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalDescription('This creates or updates one salary snapshot for every active staff member with a valid salary setup for the selected month.')
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $month = Carbon::parse($data['payroll_month'])->startOfMonth()->toDateString();
                    $staffQuery = Staff::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('status', 'active')
                        ->where(function ($query): void {
                            $query->whereNotNull('salary_template_id')
                                ->orWhere('basic_salary', '>', 0);
                        });

                    $count = 0;

                    $staffQuery->orderBy('id')->each(function (Staff $staff) use ($month, &$count): void {
                        $postingData = PayrollCalculator::postingData($staff, $month, Filament::auth()->id());

                        SalaryPosting::query()->updateOrCreate(
                            [
                                'school_id' => $postingData['school_id'],
                                'staff_id' => $postingData['staff_id'],
                                'payroll_month' => $postingData['payroll_month'],
                            ],
                            $postingData,
                        );

                        $count++;
                    });

                    Notification::make()
                        ->success()
                        ->title('Monthly salary posted')
                        ->body("Posted {$count} staff salary snapshot(s) for ".Carbon::parse($month)->format('F Y').'.')
                        ->send();
                }),
            Action::make('monthReportPdf')
                ->label('Month report PDF')
                ->icon('heroicon-m-document-arrow-down')
                ->color('gray')
                ->schema([
                    DatePicker::make('payroll_month')
                        ->label('Payroll month')
                        ->native(false)
                        ->displayFormat('F Y')
                        ->default(now()->startOfMonth())
                        ->required(),
                ])
                ->action(fn (array $data) => redirect()->to(route('salary-postings.month-pdf', [
                    'school' => Filament::getTenant(),
                    'month' => Carbon::parse($data['payroll_month'])->startOfMonth()->toDateString(),
                ]))),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return SalaryPosting::query()
            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
            ->selectRaw('MIN(id) as id')
            ->selectRaw('school_id')
            ->selectRaw('payroll_month')
            ->selectRaw('COUNT(*) as staff_count')
            ->selectRaw('SUM(basic_salary) as basic_total')
            ->selectRaw('SUM(allowances_total) as earnings_total')
            ->selectRaw('SUM(deductions_total) as deductions_total')
            ->selectRaw('SUM(net_pay) as net_total')
            ->selectRaw("CASE WHEN MIN(status) = 'posted' AND MAX(status) = 'posted' THEN 'posted' ELSE 'draft' END as status")
            ->groupBy('school_id', 'payroll_month');
    }
}
