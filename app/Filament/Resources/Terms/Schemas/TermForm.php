<?php

namespace App\Filament\Resources\Terms\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TermForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Term details')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->placeholder('First Term')
                            ->required()
                            ->maxLength(50),
                        DatePicker::make('starts_on')
                            ->required(),
                        DatePicker::make('ends_on')
                            ->required()
                            ->after('starts_on'),
                        Toggle::make('is_current')
                            ->label('Current term'),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
