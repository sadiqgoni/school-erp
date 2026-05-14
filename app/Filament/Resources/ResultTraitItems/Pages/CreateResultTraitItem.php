<?php

namespace App\Filament\Resources\ResultTraitItems\Pages;

use App\Filament\Resources\ResultTraitItems\ResultTraitItemResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateResultTraitItem extends CreateRecord
{
    protected static string $resource = ResultTraitItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
