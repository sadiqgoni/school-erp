<?php

namespace App\Filament\Resources\PayrollSheets\Pages;

use App\Filament\Resources\PayrollSheets\PayrollSheetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollSheets extends ListRecords
{
    protected static string $resource = PayrollSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
