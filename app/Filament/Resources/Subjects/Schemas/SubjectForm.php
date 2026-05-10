<?php

namespace App\Filament\Resources\Subjects\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subject')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->placeholder('Mathematics')
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('school_id', Filament::getTenant()?->getKey()),
                            )
                            ->maxLength(120),
                        TextInput::make('code')
                            ->placeholder('MTH')
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('school_id', Filament::getTenant()?->getKey()),
                            )
                            ->maxLength(30),
                        TextInput::make('department')
                            ->placeholder('Sciences')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
