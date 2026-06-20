<?php

namespace App\Filament\Resources\PayrollSheets\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\PayrollSheets\PayrollSheetResource;
use App\Models\Staff;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollSheet extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = PayrollSheetResource::class;

    protected array $staffIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->staffIds = $data['staff_ids'] ?? [];
        unset($data['staff_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        Staff::query()
            ->whereIn('id', $this->staffIds)
            ->update(['payroll_sheet_id' => $this->record->getKey()]);
    }
}
