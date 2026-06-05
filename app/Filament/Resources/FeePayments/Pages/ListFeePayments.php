<?php

namespace App\Filament\Resources\FeePayments\Pages;

use App\Filament\Resources\FeePayments\FeePaymentResource;
use App\Filament\Support\ClassTabs;
use App\Models\FeePayment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeePayments extends ListRecords
{
    protected static string $resource = FeePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::studentEnrollment(FeePayment::class, 'All payments');
    }
}
