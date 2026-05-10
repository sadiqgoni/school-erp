<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();
        $data['recorded_by_id'] ??= auth()->id();

        return $data;
    }
}
