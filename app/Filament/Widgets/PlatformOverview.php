<?php

namespace App\Filament\Widgets;

use App\Models\FeePayment;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    protected function getStats(): array
    {
        // This widget only renders in the admin panel (canView() above), where
        // the BelongsToSchool tenant scope is already inert — no need to strip it.
        $schools = School::query()->portalWorkspaces();

        $totalSchools = (clone $schools)->count();
        $activeSchools = (clone $schools)->where('is_active', true)->count();
        $expiredSchools = (clone $schools)
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', now())
            ->count();
        $newThisMonth = (clone $schools)->where('created_at', '>=', now()->startOfMonth())->count();

        $totalStudents = Student::query()->count();
        $activeStudents = Student::query()->where('status', 'active')->count();

        $totalStaff = Staff::query()->count();

        $totalUsers = User::query()->count();

        $totalPaymentsReceived = FeePayment::query()
            ->where('status', 'confirmed')
            ->sum('amount');

        return [
            Stat::make('Schools', $totalSchools)
                ->description("{$activeSchools} active · {$newThisMonth} new this month")
                ->descriptionIcon(Heroicon::OutlinedBuildingLibrary)
                ->color('primary'),
            Stat::make('Subscriptions expired', $expiredSchools)
                ->description($expiredSchools > 0 ? 'Needs renewal follow-up' : 'All schools current')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($expiredSchools > 0 ? 'danger' : 'success'),
            Stat::make('Students', $totalStudents)
                ->description("{$activeStudents} active across the platform")
                ->descriptionIcon(Heroicon::OutlinedAcademicCap)
                ->color('success'),
            Stat::make('Staff', $totalStaff)
                ->description('Across all schools')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('info'),
            Stat::make('Login accounts', $totalUsers)
                ->description('Admins, teachers, parents combined')
                ->descriptionIcon(Heroicon::OutlinedShieldCheck)
                ->color('gray'),
            Stat::make('Payments received', 'NGN '.number_format((float) $totalPaymentsReceived, 2))
                ->description('All confirmed payments, all schools')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),
        ];
    }
}
