<?php

namespace App\Filament\Resources\Concerns;

trait RedirectsToIndex
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
