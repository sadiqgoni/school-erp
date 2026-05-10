<?php

namespace App\Filament\Resources\ClassSections\Pages;

use App\Filament\Resources\ClassSections\ClassSectionResource;
use App\Filament\Resources\Concerns\RedirectsToIndex;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClassSection extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = ClassSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
