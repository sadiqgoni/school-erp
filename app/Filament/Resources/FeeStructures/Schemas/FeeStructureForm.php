<?php

namespace App\Filament\Resources\FeeStructures\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeeStructureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fee structure')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('academic_year_id')->relationship('academicYear', 'name')->searchable()->preload()->required(),
                        Select::make('term_id')->relationship('term', 'name')->searchable()->preload(),
                        Select::make('school_class_id')->relationship('schoolClass', 'name')->searchable()->preload()->required(),
                        Select::make('fee_type_id')->relationship('feeType', 'name')->searchable()->preload()->required(),
                        TextInput::make('amount')->numeric()->prefix('NGN')->required(),
                        DatePicker::make('due_date'),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
