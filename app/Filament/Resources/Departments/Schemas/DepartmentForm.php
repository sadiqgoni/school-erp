<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Examples: Academics, Administration, Accounts, ICT, Transport, Security.'),
                        Toggle::make('is_active')
                            ->default(true),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
