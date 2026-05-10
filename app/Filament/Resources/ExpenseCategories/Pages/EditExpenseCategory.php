<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseCategory extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
