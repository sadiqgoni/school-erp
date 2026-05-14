<?php

namespace App\Filament\Resources\StudentScores\Tables;

use App\Support\TeacherWorkspace;
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
            ->modifyQueryUsing(function ($query) {
                $user = Filament::auth()->user();
                $tenant = Filament::getTenant();

                if (! TeacherWorkspace::isTeacher()) {
                    return $query;
                }

                $staffId = TeacherWorkspace::currentStaff()?->getKey();
                $formAssignments = TeacherWorkspace::formAssignments();
                $formClassIds = $formAssignments->pluck('school_class_id')->filter()->unique()->values()->all();
                $formArmIds = $formAssignments->pluck('class_section_id')->filter()->unique()->values()->all();

                return $query
                    ->where(function ($query) use ($staffId, $tenant, $formClassIds, $formArmIds): void {
                        $query->where('staff_id', $staffId ?: 0);

                        if ($formClassIds !== []) {
                            $query->orWhereHas('student.enrollments', function ($query) use ($tenant, $formClassIds, $formArmIds): void {
                                $query
                                    ->where('school_id', $tenant?->getKey())
                                    ->whereIn('school_class_id', $formClassIds)
                                    ->where('status', 'active')
                                    ->when($formArmIds !== [], fn ($query) => $query->whereIn('class_section_id', $formArmIds));
                            });
                        }
                    });
            })
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
                EditAction::make()
                    ->visible(fn (): bool => ! Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'teacher')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn (): bool => ! Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'teacher')),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No scores entered yet')
            ->emptyStateDescription('Use Enter Scores to load a class list and record scores for a subject component.')
            ->striped();
    }
}
