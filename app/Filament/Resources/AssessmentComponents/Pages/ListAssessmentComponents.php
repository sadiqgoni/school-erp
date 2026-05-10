<?php

namespace App\Filament\Resources\AssessmentComponents\Pages;

use App\Filament\Resources\AssessmentComponents\AssessmentComponentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentComponents extends ListRecords
{
    protected static string $resource = AssessmentComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
