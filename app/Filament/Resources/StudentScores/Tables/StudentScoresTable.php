<?php

namespace App\Filament\Resources\StudentScores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentScoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'student',
                        fn ($query) => $query
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('admission_number', 'like', "%{$search}%"),
                    ))
                    ->weight('semibold')
                    ->description(fn ($record): ?string => $record->student?->admission_number),
                TextColumn::make('exam.name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): ?string => $record->exam?->term?->name),
                TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assessmentComponent.code')
                    ->label('Component')
                    ->badge()
                    ->description(fn ($record): ?string => $record->assessmentComponent ? "{$record->assessmentComponent->max_score} marks" : null),
                TextColumn::make('score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'info',
                        'approved' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('school')->relationship('school', 'name')->searchable()->preload(),
                SelectFilter::make('exam_id')->label('Exam')->relationship('exam', 'name')->searchable()->preload(),
                SelectFilter::make('subject_id')->relationship('subject', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No scores entered yet')
            ->emptyStateDescription('Use Enter Scores to load a class list and record scores for a subject component.')
            ->striped();
    }
}
