<?php

namespace App\Filament\Resources\BusRoutes\Pages;

use App\Filament\Resources\BusRoutes\BusRouteResource;
use App\Filament\Resources\Concerns\RedirectsToIndex;
use Filament\Resources\Pages\CreateRecord;

class CreateBusRoute extends CreateRecord
{
    protected static string $resource = BusRouteResource::class;

    use RedirectsToIndex;
}
