<?php

namespace App\Filament\Resources\TeachingAssignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeachingAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('staff.full_name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(['staff.last_name'])
                    ->description(fn ($record): ?string => $record->staff?->staff_number),
                TextColumn::make('academicYear.name')
                    ->label('Academic year')
                    ->sortable(),
                TextColumn::make('schoolClass.name')->label('Class')->sortable(),
                TextColumn::make('classSection.name')->label('Arm')->placeholder('Whole class'),
                TextColumn::make('assignment_role')
                    ->label('Role')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'form_teacher' => 'Form teacher',
                        'assistant_form_teacher' => 'Assistant form teacher',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                IconColumn::make('is_class_teacher')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('academic_year_id')
                    ->label('Academic year')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('school_class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
