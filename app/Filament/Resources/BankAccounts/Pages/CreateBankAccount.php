<?php

namespace App\Filament\Resources\BankAccounts\Pages;

use App\Filament\Resources\BankAccounts\BankAccountResource;
use App\Filament\Resources\Concerns\RedirectsToIndex;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();

        return $data;
    }
}
