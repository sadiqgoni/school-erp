<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\SchoolPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    SchoolPanelProvider::class,
];
