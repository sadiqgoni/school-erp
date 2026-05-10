<?php

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\Term;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $schoolId = $tenant?->getKey();

        $studentsQuery = Student::query();
        $classesQuery = SchoolClass::query();
        $invoicesQuery = StudentInvoice::query();
        $paymentsQuery = FeePayment::query();
        $expensesQuery = Expense::query();

        if ($schoolId) {
            $studentsQuery->where('school_id', $schoolId);
            $classesQuery->where('school_id', $schoolId);
            $invoicesQuery->where('school_id', $schoolId);
            $paymentsQuery->where('school_id', $schoolId);
            $expensesQuery->where('school_id', $schoolId);
        }

        $studentsCount = $studentsQuery->count();
        $activeStudentsCount = (clone $studentsQuery)->where('status', 'active')->count();
        $classesCount = $classesQuery->count();
        $activeClassesCount = (clone $classesQuery)->where('is_active', true)->count();
        $invoicesCount = $invoicesQuery->count();
        $successfulInvoicesCount = (clone $invoicesQuery)->where('status', 'paid')->count();
        $paymentsCount = $paymentsQuery->count();
        $successfulPaymentsCount = (clone $paymentsQuery)->where('status', 'confirmed')->count();
        $expensesCount = $expensesQuery->count();
        $processedExpensesCount = (clone $expensesQuery)->whereIn('status', ['approved', 'paid'])->count();

        $currentYear = AcademicYear::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_current', true)
            ->first();

        $currentTerm = Term::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_current', true)
            ->first();

        return [
            Stat::make('Session', $currentYear?->name ?? 'Not set')
                ->description($currentTerm?->name ?? 'No current term')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('primary')
                ->chart([1, 2, 3, 4, 5]),
            Stat::make('Students', $studentsCount)
                ->description("{$activeStudentsCount} Active Students")
                ->descriptionIcon(Heroicon::OutlinedAcademicCap)
                ->color('success')
                ->chart([0, 1, 1, 1, $studentsCount]),
            Stat::make('Classes', $classesCount)
                ->description("{$activeClassesCount} Active Classes")
                ->descriptionIcon(Heroicon::OutlinedBuildingLibrary)
                ->color('info')
                ->chart([0, 1, 2, 3, $classesCount]),
            Stat::make('Invoices', $invoicesCount)
                ->description("{$successfulInvoicesCount} Successful Invoices")
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('warning')
                ->chart([0, 1, 2, 3, $invoicesCount]),
            Stat::make('Payments', $paymentsCount)
                ->description("{$successfulPaymentsCount} Successful Payments")
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->chart([0, 1, 2, 3, $paymentsCount]),
            Stat::make('Expenses', $expensesCount)
                ->description("{$processedExpensesCount} Processed Expenses")
                ->descriptionIcon(Heroicon::OutlinedWallet)
                ->color('warning')
                ->chart([0, 1, 2, 3, $expensesCount]),
        ];
    }
}
