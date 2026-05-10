<?php

namespace App\Filament\Resources\AccountTransfers\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\AccountTransfers\AccountTransferResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountTransfer extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = AccountTransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();
        $data['created_by_id'] ??= auth()->id();

        return $data;
    }
}
