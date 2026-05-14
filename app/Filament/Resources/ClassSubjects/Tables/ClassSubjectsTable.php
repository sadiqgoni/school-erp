<?php

namespace App\Filament\Resources\ClassSubjects\Tables;

use App\Support\TeacherWorkspace;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClassSubjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                if (! TeacherWorkspace::isTeacher()) {
                    return $query;
                }

                $formClassIds = TeacherWorkspace::formClassIds();

                if ($formClassIds === []) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereIn('school_class_id', $formClassIds);
            })
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('staff.full_name')
                    ->label('Subject teacher')
                    ->placeholder('Not assigned')
                    ->toggleable()
                    ->description(fn ($record): ?string => $record->staff?->staff_number),
                TextColumn::make('weekly_periods')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_compulsory')
                    ->boolean()
                    ->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
                IconColumn::make('is_active')
                    ->boolean()
                    ->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                SelectFilter::make('school_class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
            ]);
    }
}
