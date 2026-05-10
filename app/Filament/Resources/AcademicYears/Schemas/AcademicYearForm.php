<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Academic year')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->placeholder('2026/2027')
                            ->required()
                            ->maxLength(30),
                        DatePicker::make('starts_on')
                            ->required(),
                        DatePicker::make('ends_on')
                            ->required()
                            ->after('starts_on'),
                        Toggle::make('is_current')
                            ->label('Current year'),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
