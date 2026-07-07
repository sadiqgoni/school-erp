<?php

namespace App\Filament\Resources\TimetableEntries\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\TimetableEntries\TimetableEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTimetableEntry extends EditRecord
{
    protected static string $resource = TimetableEntryResource::class;

    use RedirectsToIndex;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
