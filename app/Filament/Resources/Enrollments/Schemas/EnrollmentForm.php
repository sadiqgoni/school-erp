<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\Term;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Enrollment')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('student_id')
                            ->relationship('student', 'admission_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('term_ids')
                            ->label('Terms')
                            ->options(fn (): array => Term::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->orderBy('position')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->helperText('Select one term, multiple terms, or leave empty for a full-session placement.'),
                        Select::make('school_class_id')
                            ->relationship('schoolClass', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('class_section_id')
                            ->relationship('classSection', 'code')
                            ->searchable()
                            ->preload(),
                        DatePicker::make('enrolled_on'),
                        Select::make('status')
                            ->required()
                            ->default('active')
                            ->options([
                                'active' => 'Active',
                                'promoted' => 'Promoted',
                                'repeated' => 'Repeated',
                                'withdrawn' => 'Withdrawn',
                                'completed' => 'Completed',
                            ]),
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
