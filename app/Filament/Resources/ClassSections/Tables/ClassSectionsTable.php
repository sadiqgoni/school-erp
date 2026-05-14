<?php

namespace App\Filament\Resources\ClassSections\Tables;

use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\Staff;
use App\Models\TeachingAssignment;
use App\Models\Term;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ClassSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Arm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Arm code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('teaching_assignments_count')
                    ->counts('teachingAssignments')
                    ->label('Form teachers')
                    ->badge()
                    ->color('success'),
                IconColumn::make('is_active')
                    ->boolean(),
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
            ])
            ->recordActions([
                Action::make('assignFormTeachers')
                    ->label('Assign Form Teacher')
                    ->icon('heroicon-o-academic-cap')
                    ->modalHeading(fn (ClassSection $record): string => "Assign form teacher - {$record->schoolClass?->name} {$record->name}")
                    ->modalSubmitActionLabel('Save assignment')
                    ->schema(fn (ClassSection $record): array => [
                        Select::make('academic_year_id')
                            ->label('Academic year')
                            ->options(fn (): array => AcademicYear::query()
                                ->where('school_id', $record->school_id)
                                ->orderByDesc('starts_on')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('term_id')
                            ->label('Term')
                            ->options(fn (): array => Term::query()
                                ->where('school_id', $record->school_id)
                                ->orderBy('academic_year_id')
                                ->orderBy('position')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        Repeater::make('teachers')
                            ->label('Teachers')
                            ->schema([
                                Select::make('staff_id')
                                    ->label('Teacher')
                                    ->options(fn (): array => Staff::query()
                                        ->where('school_id', $record->school_id)
                                        ->where('staff_type', Staff::TYPE_TEACHING)
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Staff $staff): array => [$staff->getKey() => "{$staff->full_name} ({$staff->staff_number})"])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('assignment_role')
                                    ->label('Role')
                                    ->options([
                                        TeachingAssignment::ROLE_FORM_TEACHER => 'Form teacher',
                                        TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER => 'Assistant form teacher',
                                    ])
                                    ->default(TeachingAssignment::ROLE_FORM_TEACHER)
                                    ->required(),
                                Toggle::make('is_class_teacher')
                                    ->label('Primary arm teacher')
                                    ->default(true),
                                Toggle::make('is_active')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add another teacher')
                            ->columnSpanFull(),
                    ])
                    ->action(function (ClassSection $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            foreach ($data['teachers'] ?? [] as $row) {
                                if (blank($row['staff_id'] ?? null)) {
                                    continue;
                                }

                                TeachingAssignment::query()->updateOrCreate(
                                    [
                                        'school_id' => $record->school_id,
                                        'staff_id' => $row['staff_id'],
                                        'academic_year_id' => $data['academic_year_id'],
                                        'term_id' => $data['term_id'] ?? null,
                                        'school_class_id' => $record->school_class_id,
                                        'class_section_id' => $record->getKey(),
                                        'subject_id' => null,
                                    ],
                                    [
                                        'assignment_role' => $row['assignment_role'] ?? TeachingAssignment::ROLE_FORM_TEACHER,
                                        'is_class_teacher' => (bool) ($row['is_class_teacher'] ?? false),
                                        'is_active' => (bool) ($row['is_active'] ?? true),
                                    ],
                                );
                            }
                        });

                        Notification::make()
                            ->title('Form teacher assignment saved')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
