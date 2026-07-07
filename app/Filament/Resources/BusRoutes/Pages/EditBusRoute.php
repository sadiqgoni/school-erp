<?php

namespace App\Filament\Resources\BusRoutes\Pages;

use App\Filament\Resources\BusRoutes\BusRouteResource;
use App\Filament\Resources\Concerns\RedirectsToIndex;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusRoute extends EditRecord
{
    protected static string $resource = BusRouteResource::class;

    use RedirectsToIndex;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
