<?php

namespace App\Filament\Resources\GuardianStudents\Pages;

use App\Filament\Resources\GuardianStudents\GuardianStudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGuardianStudents extends ListRecords
{
    protected static string $resource = GuardianStudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
