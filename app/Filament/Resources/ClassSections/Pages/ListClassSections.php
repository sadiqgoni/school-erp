<?php

namespace App\Filament\Resources\ClassSections\Pages;

use App\Filament\Resources\ClassSections\ClassSectionResource;
use App\Filament\Support\ClassTabs;
use App\Models\ClassSection;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassSections extends ListRecords
{
    protected static string $resource = ClassSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(ClassSection::class, 'All arms');
    }
}
