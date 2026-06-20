<?php

namespace App\Filament\Resources\SalaryTemplates\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalaryTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Salary scale')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Grade Level 08 Step 01'),
                        TextInput::make('code')
                            ->maxLength(50)
                            ->helperText('Example: GL08-S01. Generated from name if left empty.'),
                        TextInput::make('grade_level')
                            ->label('Grade level')
                            ->maxLength(50),
                        TextInput::make('step')
                            ->maxLength(50),
                        TextInput::make('monthly_basic')
                            ->label('Monthly basic')
                            ->numeric()
                            ->prefix('NGN')
                            ->required()
                            ->default(0),
                        TextInput::make('annual_basic')
                            ->label('Annual basic')
                            ->numeric()
                            ->prefix('NGN')
                            ->default(0),
                        Toggle::make('is_active')->default(true),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
