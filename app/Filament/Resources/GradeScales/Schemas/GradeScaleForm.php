<?php

namespace App\Filament\Resources\GradeScales\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GradeScaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Grade scale')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')->required()->default('Default')->maxLength(255),
                        TextInput::make('grade')->required()->maxLength(10),
                        TextInput::make('min_score')->numeric()->required(),
                        TextInput::make('max_score')->numeric()->required(),
                        TextInput::make('grade_point')->numeric(),
                        TextInput::make('remark')->maxLength(255),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
