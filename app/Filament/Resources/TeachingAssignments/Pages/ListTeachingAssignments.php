<?php

namespace App\Filament\Resources\TeachingAssignments\Pages;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Filament\Support\ClassTabs;
use App\Models\TeachingAssignment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeachingAssignments extends ListRecords
{
    protected static string $resource = TeachingAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(TeachingAssignment::class, 'All assignments');
    }
}
