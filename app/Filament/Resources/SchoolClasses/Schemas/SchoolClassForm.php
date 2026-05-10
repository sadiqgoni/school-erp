<?php

namespace App\Filament\Resources\SchoolClasses\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Class')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->placeholder('JSS 1')
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('school_id', Filament::getTenant()?->getKey()),
                            )
                            ->maxLength(80),
                        TextInput::make('code')
                            ->placeholder('JSS1')
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('school_id', Filament::getTenant()?->getKey()),
                            )
                            ->maxLength(30),
                        TextInput::make('department')
                            ->label('Section')
                            ->placeholder('Secondary')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
