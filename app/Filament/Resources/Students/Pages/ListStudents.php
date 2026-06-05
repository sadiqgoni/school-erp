<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Support\ClassTabs;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Student;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add student'),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::studentEnrollment(Student::class, 'All students', 'enrollments');
    }
}
