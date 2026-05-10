<?php

namespace App\Filament\Resources\ClassSubjects\Pages;

use App\Filament\Resources\ClassSubjects\ClassSubjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClassSubject extends EditRecord
{
    protected static string $resource = ClassSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
