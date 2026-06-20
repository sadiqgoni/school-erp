<?php

namespace App\Filament\Resources\StaffBanks\Pages;

use App\Filament\Resources\StaffBanks\StaffBankResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffBank extends EditRecord
{
    protected static string $resource = StaffBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
