<?php

namespace App\Filament\Resources\StudentScores\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentScoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Student score')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('exam_id')->relationship('exam', 'name')->searchable()->preload()->required(),
                        Select::make('assessment_component_id')->relationship('assessmentComponent', 'name')->searchable()->preload()->required(),
                        Select::make('student_id')->relationship('student', 'admission_number')->searchable()->preload()->required(),
                        Select::make('subject_id')->relationship('subject', 'name')->searchable()->preload()->required(),
                        Select::make('staff_id')->relationship('staff', 'staff_number')->searchable()->preload(),
                        TextInput::make('score')->numeric()->required(),
                        Select::make('status')->required()->default('draft')->options([
                            'draft' => 'Draft',
                            'submitted' => 'Submitted',
                            'approved' => 'Approved',
                        ]),
                        Textarea::make('remarks')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
