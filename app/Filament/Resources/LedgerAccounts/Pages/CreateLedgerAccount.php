<?php

namespace App\Filament\Resources\LedgerAccounts\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLedgerAccount extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = LedgerAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();

        return $data;
    }
}
