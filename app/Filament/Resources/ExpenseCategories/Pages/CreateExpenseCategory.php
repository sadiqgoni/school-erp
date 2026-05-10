<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseCategory extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = ExpenseCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();

        return $data;
    }
}
