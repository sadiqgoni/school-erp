<?php

namespace App\Filament\Pages;

use App\Models\Enrollment;
use App\Support\TeacherWorkspace;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MyStudents extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'My Class Students';

    protected static ?string $title = 'My Class Students';

    protected static string|\UnitEnum|null $navigationGroup = 'Teacher Portal';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.my-students';

    public static function canAccess(): bool
    {
        return TeacherWorkspace::isTeacher();
    }

    protected function getViewData(): array
    {
        $staff = TeacherWorkspace::currentStaff();
        $assignments = TeacherWorkspace::formAssignments();

        if (! $staff || $assignments->isEmpty()) {
            return [
                'staff' => $staff,
                'assignments' => $assignments,
                'studentsByClass' => collect(),
            ];
        }

        $studentsByClass = $assignments
            ->mapWithKeys(function ($assignment) {
                $enrollments = Enrollment::query()
                    ->with(['student', 'schoolClass', 'classSection'])
                    ->where('school_id', $assignment->school_id)
                    ->where('academic_year_id', $assignment->academic_year_id)
                    ->where('school_class_id', $assignment->school_class_id)
                    ->when($assignment->class_section_id, fn ($query, $armId) => $query->where('class_section_id', $armId))
                    ->where('status', 'active')
                    ->get()
                    ->sortBy(fn (Enrollment $enrollment): string => $enrollment->student?->last_name.' '.$enrollment->student?->first_name);

                $label = collect([
                    $assignment->schoolClass?->name,
                    $assignment->classSection?->name,
                ])->filter()->join(' ');

                return [$assignment->getKey() => [
                    'label' => $label,
                    'session' => $assignment->academicYear?->name,
                    'term' => $assignment->term?->name,
                    'enrollments' => $enrollments,
                ]];
            });

        return [
            'staff' => $staff,
            'assignments' => $assignments,
            'studentsByClass' => $studentsByClass,
        ];
    }
}
