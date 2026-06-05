<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Support\ClassTabs;
use App\Models\Enrollment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(Enrollment::class, 'All placements');
    }
}
