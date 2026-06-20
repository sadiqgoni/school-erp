<?php

namespace App\Filament\Resources\SalaryTemplates\Pages;

use App\Filament\Resources\SalaryTemplates\SalaryTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalaryTemplate extends EditRecord
{
    protected static string $resource = SalaryTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
