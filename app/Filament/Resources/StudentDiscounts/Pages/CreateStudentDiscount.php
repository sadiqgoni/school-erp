<?php

namespace App\Filament\Resources\StudentDiscounts\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\StudentDiscounts\StudentDiscountResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentDiscount extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = StudentDiscountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();

        return $data;
    }
}
