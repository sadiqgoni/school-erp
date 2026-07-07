<?php

namespace App\Filament\Resources\StudentDevices\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\StudentDevices\StudentDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentDevice extends EditRecord
{
    protected static string $resource = StudentDeviceResource::class;

    use RedirectsToIndex;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
