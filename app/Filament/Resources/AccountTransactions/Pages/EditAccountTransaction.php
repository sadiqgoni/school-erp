<?php

namespace App\Filament\Resources\AccountTransactions\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\AccountTransactions\AccountTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccountTransaction extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = AccountTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
