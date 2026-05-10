<?php

namespace App\Filament\Resources\SchoolClasses\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolClass extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
