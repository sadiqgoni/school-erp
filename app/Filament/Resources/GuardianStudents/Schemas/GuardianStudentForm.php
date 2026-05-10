<?php

namespace App\Filament\Resources\GuardianStudents\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GuardianStudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Guardian-student link')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('guardian_id')
                            ->relationship('guardian', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('student_id')
                            ->relationship('student', 'admission_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('relationship')
                            ->required()
                            ->default('guardian')
                            ->options([
                                'father' => 'Father',
                                'mother' => 'Mother',
                                'guardian' => 'Guardian',
                                'uncle' => 'Uncle',
                                'aunt' => 'Aunt',
                                'sibling' => 'Sibling',
                                'other' => 'Other',
                            ]),
                        Toggle::make('is_primary_contact')
                            ->label('Primary contact'),
                        Toggle::make('can_pick_up')
                            ->default(true),
                        Toggle::make('receives_sms')
                            ->default(true),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
