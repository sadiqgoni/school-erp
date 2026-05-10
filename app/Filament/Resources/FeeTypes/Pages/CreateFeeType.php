<?php

namespace App\Filament\Resources\FeeTypes\Pages;

use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Filament\Resources\Concerns\RedirectsToIndex;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateFeeType extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = FeeTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();

        return $data;
    }
}
