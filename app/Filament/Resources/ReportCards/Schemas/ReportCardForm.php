<?php

namespace App\Filament\Resources\ReportCards\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report summary')
                    ->description('Generated from compiled results. Review totals, add comments, then approve or publish.')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('exam_id')->relationship('exam', 'name')->searchable()->preload()->required(),
                        Select::make('student_id')->relationship('student', 'admission_number')->searchable()->preload()->required(),
                        Select::make('academic_year_id')->relationship('academicYear', 'name')->searchable()->preload()->required(),
                        Select::make('term_id')->relationship('term', 'name')->searchable()->preload(),
                        TextInput::make('total_score')->numeric()->required(),
                        TextInput::make('average_score')->numeric()->required(),
                        TextInput::make('position')->numeric(),
                        Select::make('status')->required()->default('draft')->options([
                            'draft' => 'Draft',
                            'approved' => 'Approved',
                            'published' => 'Published',
                        ]),
                        DateTimePicker::make('published_at'),
                    ])
                    ->columns(2),
                Section::make('Comments')
                    ->schema([
                        Textarea::make('teacher_comment')->rows(4)->columnSpanFull(),
                        Textarea::make('principal_comment')->rows(4)->columnSpanFull(),
                    ]),
            ]);
    }
}
