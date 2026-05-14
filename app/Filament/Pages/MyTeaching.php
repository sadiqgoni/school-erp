<?php

namespace App\Filament\Pages;

use App\Support\TeacherWorkspace;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MyTeaching extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'My Classes & Subjects';

    protected static ?string $title = 'My Classes & Subjects';

    protected static string|\UnitEnum|null $navigationGroup = 'Teacher Portal';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.my-teaching';

    public static function canAccess(): bool
    {
        return TeacherWorkspace::isTeacher();
    }

    protected function getViewData(): array
    {
        $staff = TeacherWorkspace::currentStaff();

        if (! $staff) {
            return [
                'staff' => null,
                'formAssignments' => collect(),
                'subjectAssignments' => collect(),
                'subjectGroups' => collect(),
            ];
        }

        return [
            'staff' => $staff,
            'formAssignments' => TeacherWorkspace::formAssignments(),
            'subjectAssignments' => TeacherWorkspace::subjectAssignments(),
            'subjectGroups' => TeacherWorkspace::subjectAssignments()
                ->groupBy(fn ($assignment): string => (string) $assignment->subject?->name),
        ];
    }
}
