<?php

namespace App\Filament\Resources\FeeTypes\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\FeeTypes\FeeTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeeType extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = FeeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
