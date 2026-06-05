<?php

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Resources\FeeStructures\Schemas\FeeStructureForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeeStructure extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = FeeStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['fee_items'] = [[
            'fee_type_id' => $data['fee_type_id'] ?? null,
            'amount' => $data['amount'] ?? null,
        ]];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $item = $data['fee_items'][0] ?? [];

        $data['fee_type_id'] = $item['fee_type_id'] ?? $data['fee_type_id'] ?? null;
        $data['amount'] = FeeStructureForm::sanitizeAmount($item['amount'] ?? $data['amount'] ?? 0);
        $data['due_date'] = null;

        unset($data['fee_items']);

        return $data;
    }
}
