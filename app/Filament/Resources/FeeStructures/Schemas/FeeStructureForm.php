<?php

namespace App\Filament\Resources\FeeStructures\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Repeater;
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
                        Toggle::make('is_active')->default(true),
                        Repeater::make('fee_items')
                            ->label('Fee types')
                            ->schema([
                                Select::make('fee_type_id')->relationship('feeType', 'name')->searchable()->preload()->required(),
                                TextInput::make('amount')
                                    ->prefix('NGN')
                                    ->required()
                                    ->placeholder('3,000.00')
                                    ->dehydrateStateUsing(fn ($state): string => self::sanitizeAmount($state)),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel('Add fee type')                    ->columns(2)

                            ->columnSpanFull(),
                    ])
                    ->columns(2)

                            ->columnSpanFull(),
            ]);
    }

    public static function sanitizeAmount(mixed $value): string
    {
        $normalized = preg_replace('/[^\d.]/', '', (string) $value) ?: '0';
        $parts = explode('.', $normalized);
        $whole = $parts[0] !== '' ? $parts[0] : '0';
        $decimal = isset($parts[1]) ? substr($parts[1], 0, 2) : '00';

        return number_format((float) "{$whole}.{$decimal}", 2, '.', '');
    }
}
