<?php

namespace App\Filament\Resources\ResultTraitItems\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ResultTraitItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Result trait')
                ->description('Create affective and psychomotor items that class teachers will rate on each report card.')
                ->schema([
                    SchoolSelect::make(),
                    TextInput::make('name')
                        ->label('Trait name')
                        ->placeholder('e.g. Punctuality, Neatness, Handwriting')
                        ->required()
                        ->maxLength(255),
                    Select::make('category')
                        ->required()
                        ->default('affective')
                        ->options([
                            'affective' => 'Affective domain',
                            'psychomotor' => 'Psychomotor domain',
                        ]),
                    TextInput::make('max_rating')
                        ->label('Rating scale')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->default(5)
                        ->required(),
                    TextInput::make('position')
                        ->numeric()
                        ->default(1)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }
}
