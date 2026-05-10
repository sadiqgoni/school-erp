<?php

namespace App\Filament\Resources\SchoolClasses\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Students';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student_id')
            ->columns([
                ImageColumn::make('student.photo_path')
                    ->label('')
                    ->disk('public')
                    ->visibility('public')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Student&background=E2E8F0&color=334155')
                    ->toggleable(),
                TextColumn::make('student.admission_number')
                    ->label('Admission no.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->description(fn ($record): string => collect([
                        $record->student?->gender ? ucfirst($record->student->gender) : null,
                        $record->student?->date_of_birth?->format('d M Y'),
                    ])->filter()->implode('  ·  ')),
                TextColumn::make('classSection.name')
                    ->label('Arm')
                    ->placeholder('No arm')
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Session')
                    ->sortable(),
                TextColumn::make('term.name')
                    ->placeholder('All terms')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'transferred', 'withdrawn' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('enrolled_on')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'transferred' => 'Transferred',
                    'withdrawn' => 'Withdrawn',
                ]),
                SelectFilter::make('class_section_id')
                    ->label('Arm')
                    ->relationship('classSection', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('viewStudent')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record): string => StudentResource::getUrl('view', ['record' => $record->student_id]))
                    ->openUrlInNewTab(false),
            ]);
    }
}
