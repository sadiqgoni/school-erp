<?php

namespace App\Filament\Resources\PayrollItemTypes\Pages;

use App\Filament\Resources\PayrollItemTypes\PayrollItemTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditPayrollItemType extends EditRecord
{
    protected static string $resource = PayrollItemTypeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CreatePayrollItemType::normalizePayrollItemData($data);
    }
}
