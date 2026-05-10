<?php

namespace App\Filament\Resources\ClassSections\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\ClassSections\ClassSectionResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateClassSection extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = ClassSectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();

        return $data;
    }
}
