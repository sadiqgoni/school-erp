<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;

class SchoolSelect
{
    public static function make(): Select
    {
        return Select::make('school_id')
            ->label('School')
            ->relationship('school', 'name')
            ->searchable()
            ->preload()
            ->required(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin')
            ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin');
    }
}
