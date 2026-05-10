<?php

namespace App\Filament\Resources\Exams\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Exam')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('academic_year_id')->relationship('academicYear', 'name')->searchable()->preload()->required(),
                        Select::make('term_id')->relationship('term', 'name')->searchable()->preload(),
                        TextInput::make('name')->required()->maxLength(255),
                        Select::make('type')->required()->default('term')->options([
                            'term' => 'Term exam',
                            'midterm' => 'Midterm',
                            'mock' => 'Mock',
                            'entrance' => 'Entrance',
                        ]),
                        DatePicker::make('starts_on'),
                        DatePicker::make('ends_on')->after('starts_on'),
                        Select::make('status')->required()->default('draft')->options([
                            'draft' => 'Draft',
                            'open' => 'Open',
                            'locked' => 'Locked',
                            'published' => 'Published',
                        ]),
                        Textarea::make('remarks')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
