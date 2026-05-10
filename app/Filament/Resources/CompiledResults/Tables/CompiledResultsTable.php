<?php

namespace App\Filament\Resources\CompiledResults\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompiledResultsTable
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
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('grade')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state): string => match ($state) {
                        'A', 'A1' => 'success',
                        'B', 'B2', 'B3' => 'info',
                        'C', 'C4', 'C5', 'C6' => 'warning',
                        'F', 'F9' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('position')
                    ->label('Subject pos.')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('remark')
                    ->searchable()
                    ->placeholder('Not graded'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'compiled' ? 'success' : 'gray'),
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
            ->emptyStateHeading('No compiled results yet')
            ->emptyStateDescription('Run Compile Results after teachers submit scores.')
            ->striped();
    }
}
