<?php

namespace App\Filament\Resources\AccountTransactions\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\AccountTransactions\AccountTransactionResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountTransaction extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = AccountTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();
        $data['created_by_id'] ??= auth()->id();

        return $data;
    }
}
