<?php

namespace App\Filament\Resources\StudentDiscounts\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\StudentDiscounts\StudentDiscountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentDiscount extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = StudentDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
