<?php

namespace App\Filament\Resources\ResultTraitItems\Pages;

use App\Filament\Resources\ResultTraitItems\ResultTraitItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResultTraitItem extends EditRecord
{
    protected static string $resource = ResultTraitItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
