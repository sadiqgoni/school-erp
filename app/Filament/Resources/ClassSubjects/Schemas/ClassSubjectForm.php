<?php

namespace App\Filament\Resources\ClassSubjects\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\Staff;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClassSubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Class subject assignment')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('school_class_id')
                            ->relationship('schoolClass', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('staff_id')
                            ->label('Subject teacher')
                            ->options(fn (): array => Staff::query()
                                ->where('staff_type', Staff::TYPE_TEACHING)
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Staff $staff): array => [$staff->getKey() => "{$staff->full_name} ({$staff->staff_number})"])
                                ->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('weekly_periods')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->required(),
                        Toggle::make('is_compulsory')
                            ->default(true),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
