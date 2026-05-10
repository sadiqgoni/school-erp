<?php

namespace App\Filament\Resources\StaffRoles\Pages;

use App\Filament\Resources\StaffRoles\StaffRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffRole extends EditRecord
{
    protected static string $resource = StaffRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
