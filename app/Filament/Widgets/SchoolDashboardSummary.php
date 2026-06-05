<?php

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Term;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class SchoolDashboardSummary extends Widget
{
    protected string $view = 'filament.widgets.school-dashboard-summary';

    protected static ?int $sort = -20;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return ! $user?->hasSchoolRole(Filament::getTenant(), ['teacher', 'parent']);
    }

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();
        $schoolId = $tenant?->getKey();

        $currentYear = AcademicYear::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_current', true)
            ->first();

        $currentTerm = Term::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($currentYear, fn ($query) => $query->where('academic_year_id', $currentYear->getKey()))
            ->where('is_current', true)
            ->first();

        $nextTerm = Term::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($currentYear, fn ($query) => $query->where('academic_year_id', $currentYear->getKey()))
            ->when($currentTerm, fn ($query) => $query->where('position', '>', $currentTerm->position))
            ->orderBy('position')
            ->first();

        $studentsQuery = Student::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId));

        $activeEnrollments = Enrollment::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($currentYear, fn ($query) => $query->where('academic_year_id', $currentYear->getKey()))
            ->where('status', 'active')
            ->count();

        return [
            'schoolName' => $tenant?->baseSchoolName() ?? 'School Dice',
            'divisionName' => $tenant?->divisionLabel(),
            'sessionName' => $currentYear?->name ?? 'Not set',
            'termName' => $currentTerm?->name ?? 'No current term',
            'studentCards' => [
                [
                    'label' => 'Pending Admissions',
                    'value' => (clone $studentsQuery)->where('status', 'pending')->count(),
                    'description' => 'Students awaiting admission.',
                    'icon' => 'heroicon-o-user-plus',
                ],
                [
                    'label' => 'Active Enrollments',
                    'value' => $activeEnrollments,
                    'description' => 'Students enrolled for '.($currentYear?->name ?? 'the active session').'.',
                    'icon' => 'heroicon-o-academic-cap',
                ],
                [
                    'label' => 'Students Population',
                    'value' => (clone $studentsQuery)->count(),
                    'description' => 'Students admitted so far.',
                    'icon' => 'heroicon-o-user-group',
                ],
                [
                    'label' => 'Parents / Guardians',
                    'value' => Guardian::query()
                        ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                        ->count(),
                    'description' => 'Linked family contacts.',
                    'icon' => 'heroicon-o-users',
                ],
                [
                    'label' => 'Teachers',
                    'value' => Staff::query()
                        ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                        ->where('staff_type', Staff::TYPE_TEACHING)
                        ->count(),
                    'description' => 'Teaching staff in this section.',
                    'icon' => 'heroicon-o-identification',
                ],
            ],
            'calendarCards' => [
                [
                    'label' => 'Current Term',
                    'value' => $currentTerm?->name ?? 'Not set',
                    'range' => $this->dateRange($currentTerm?->starts_on, $currentTerm?->ends_on),
                    'icon' => 'heroicon-o-calendar-days',
                ],
                [
                    'label' => 'Next Term',
                    'value' => $nextTerm?->name ?? 'Not set',
                    'range' => $this->dateRange($nextTerm?->starts_on, $nextTerm?->ends_on),
                    'icon' => 'heroicon-o-calendar',
                ],
                [
                    'label' => 'Active Session',
                    'value' => $currentYear?->name ?? 'Not set',
                    'range' => $this->dateRange($currentYear?->starts_on, $currentYear?->ends_on),
                    'icon' => 'heroicon-o-calendar-days',
                ],
            ],
        ];
    }

    protected function dateRange(mixed $startsOn, mixed $endsOn): string
    {
        if (! $startsOn || ! $endsOn) {
            return 'Dates not set';
        }

        return $startsOn->format('jS M, Y').' to '.$endsOn->format('jS M, Y');
    }
}
