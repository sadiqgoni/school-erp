<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\StudentInvoice;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class FinanceSnapshot extends ChartWidget
{
    protected ?string $heading = 'Finance Snapshot';

    protected ?string $description = 'Invoice, receipt, and expense position';

    protected string $color = 'success';

    protected function getData(): array
    {
        $tenant = Filament::getTenant();

        $invoicesQuery = StudentInvoice::query();
        $paymentsQuery = FeePayment::query();
        $expensesQuery = Expense::query();

        if ($tenant) {
            $invoicesQuery->whereBelongsTo($tenant, 'school');
            $paymentsQuery->whereBelongsTo($tenant, 'school');
            $expensesQuery->whereBelongsTo($tenant, 'school');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Amount',
                    'data' => [
                        $invoicesQuery->sum('total'),
                        $paymentsQuery->sum('amount'),
                        $expensesQuery->sum('amount'),
                    ],
                    'backgroundColor' => ['#0f766e', '#2563eb', '#f97316'],
                    'borderColor' => ['#0f766e', '#2563eb', '#f97316'],
                ],
            ],
            'labels' => ['Invoiced', 'Received', 'Expenses'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
