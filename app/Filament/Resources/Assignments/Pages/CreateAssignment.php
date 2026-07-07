<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Support\TeacherWorkspace;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    use RedirectsToIndex;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $schoolId = Filament::getTenant()?->getKey() ?? $data['school_id'] ?? null;

        $data['staff_id'] ??= TeacherWorkspace::currentStaff()?->getKey();
        $data['school_class_id'] ??= TeacherWorkspace::lockedFormClassId();
        $data['class_section_id'] ??= TeacherWorkspace::lockedFormSectionId();

        $data['academic_year_id'] ??= AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->value('id');

        $data['term_id'] ??= Term::query()
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->value('id');

        return $data;
    }
}
