<?php

namespace App\Filament\Resources\StudentDiscounts\Pages;

use App\Filament\Resources\StudentDiscounts\StudentDiscountResource;
use App\Filament\Support\ClassTabs;
use App\Models\StudentDiscount;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentDiscounts extends ListRecords
{
    protected static string $resource = StudentDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::directOrStudentEnrollment(StudentDiscount::class, 'All discounts');
    }
}
