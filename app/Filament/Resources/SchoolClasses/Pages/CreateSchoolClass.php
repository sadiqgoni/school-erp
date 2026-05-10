<?php

namespace App\Filament\Resources\SchoolClasses\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateSchoolClass extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = SchoolClassResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();
        $data['level'] ??= 1;

        return $data;
    }
}
