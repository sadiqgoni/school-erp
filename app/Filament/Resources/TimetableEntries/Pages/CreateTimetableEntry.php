<?php

namespace App\Filament\Resources\TimetableEntries\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\TimetableEntries\TimetableEntryResource;
use App\Support\TeacherWorkspace;
use Filament\Resources\Pages\CreateRecord;

class CreateTimetableEntry extends CreateRecord
{
    protected static string $resource = TimetableEntryResource::class;

    use RedirectsToIndex;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Period added to the timetable';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_class_id'] ??= TeacherWorkspace::lockedFormClassId();
        $data['class_section_id'] ??= TeacherWorkspace::lockedFormSectionId();
        $data['staff_id'] ??= TeacherWorkspace::currentStaff()?->getKey();

        return $data;
    }
}
