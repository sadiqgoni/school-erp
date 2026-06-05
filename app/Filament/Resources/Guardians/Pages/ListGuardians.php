<?php

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use App\Filament\Support\ClassTabs;
use App\Models\Guardian;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGuardians extends ListRecords
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::studentEnrollment(Guardian::class, 'All guardians', 'studentLinks.student.enrollments');
    }
}
