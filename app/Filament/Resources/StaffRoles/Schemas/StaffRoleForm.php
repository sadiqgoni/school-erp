<?php

namespace App\Filament\Resources\StaffRoles\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffRoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Staff role')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->label('Role name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Class Teacher, Form Teacher, Vice Principal, Bursar'),
                        Toggle::make('is_active')
                            ->default(true),
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->helperText('Describe what this role means in your school.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
