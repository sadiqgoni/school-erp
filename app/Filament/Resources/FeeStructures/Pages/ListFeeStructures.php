<?php

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Support\ClassTabs;
use App\Models\FeeStructure;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeeStructures extends ListRecords
{
    protected static string $resource = FeeStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(FeeStructure::class, 'All fee structures');
    }
}
