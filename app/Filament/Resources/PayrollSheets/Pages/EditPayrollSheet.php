<?php

namespace App\Filament\Resources\PayrollSheets\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\PayrollSheets\PayrollSheetResource;
use App\Models\Staff;
use Filament\Resources\Pages\EditRecord;

class EditPayrollSheet extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = PayrollSheetResource::class;

    protected array $staffIds = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['staff_ids'] = $this->record->staff()->pluck('id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->staffIds = $data['staff_ids'] ?? [];
        unset($data['staff_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        Staff::query()
            ->where('payroll_sheet_id', $this->record->getKey())
            ->update(['payroll_sheet_id' => null]);

        Staff::query()
            ->whereIn('id', $this->staffIds)
            ->update(['payroll_sheet_id' => $this->record->getKey()]);
    }
}
