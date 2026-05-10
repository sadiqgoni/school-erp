<?php

namespace App\Filament\Resources\AssessmentComponents\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssessmentComponentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assessment component')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('exam_id')->relationship('exam', 'name')->searchable()->preload()->required(),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('code')->required()->maxLength(40),
                        TextInput::make('max_score')->numeric()->required(),
                        TextInput::make('position')->numeric()->required()->default(1),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
