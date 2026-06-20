<?php

namespace App\Filament\Resources\StaffBanks\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffBankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Staff bank')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->label('Bank name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->maxLength(40)
                            ->helperText('Optional short code. It will be generated if left empty.'),
                        Toggle::make('is_active')
                            ->default(true),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
