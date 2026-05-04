<?php

namespace App\Filament\Resources\Schools\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('School profile')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(30),
                        TextInput::make('slug')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('address')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Location and setup')
                    ->schema([
                        TextInput::make('city')
                            ->maxLength(255),
                        TextInput::make('state')
                            ->maxLength(255),
                        TextInput::make('country')
                            ->required()
                            ->maxLength(255),
                        ColorPicker::make('primary_color')
                            ->required(),
                        Select::make('subscription_plan')
                            ->required()
                            ->options([
                                'trial' => 'Trial',
                                'starter' => 'Starter',
                                'professional' => 'Professional',
                                'enterprise' => 'Enterprise',
                            ]),
                        TextInput::make('student_limit')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
