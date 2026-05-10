<?php

namespace App\Filament\Resources\AccountTransfers\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\AccountTransfers\AccountTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccountTransfer extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = AccountTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
