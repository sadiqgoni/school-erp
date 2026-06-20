<?php

namespace App\Filament\Resources\StudentDiscounts\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentDiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Discount')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Examples: Scholarship, Sibling Discount, Staff Child Discount.'),
                        Select::make('type')
                            ->required()
                            ->default('fixed')
                            ->options([
                                'fixed' => 'Fixed amount',
                                'percentage' => 'Percentage',
                            ]),
                        TextInput::make('value')->numeric()->required()->prefix('NGN / %'),
                        DatePicker::make('starts_on'),
                        DatePicker::make('ends_on'),
                        Toggle::make('is_active')->default(true),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
