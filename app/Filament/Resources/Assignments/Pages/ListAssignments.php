<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Support\ClassTabs;
use App\Models\Assignment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssignments extends ListRecords
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New assignment')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(Assignment::class, 'All assignments');
    }
}
