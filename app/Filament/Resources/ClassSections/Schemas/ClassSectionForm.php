<?php

namespace App\Filament\Resources\ClassSections\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClassSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Class arm')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('school_class_id')
                            ->relationship('schoolClass', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Arm name')
                            ->placeholder('A')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('code')
                            ->label('Arm code')
                            ->placeholder('JSS1-A')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('capacity')
                            ->numeric()
                            ->minValue(1),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
