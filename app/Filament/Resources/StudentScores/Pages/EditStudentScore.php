<?php

namespace App\Filament\Resources\StudentScores\Pages;

use App\Filament\Resources\StudentScores\StudentScoreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentScore extends EditRecord
{
    protected static string $resource = StudentScoreResource::class;

    public function getTitle(): string
    {
        return 'Edit Student Score';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
