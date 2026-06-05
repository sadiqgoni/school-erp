<?php

namespace App\Filament\Resources\SchoolClasses\Tables;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Support\StudentClassPlacement;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SchoolClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->label('Section')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sections.name')
                    ->label('Arm names')
                    ->badge()
                    ->separator(',')
                    ->placeholder('No arms')
                    ->toggleable(),
                TextColumn::make('sections_count')
                    ->counts('sections')
                    ->label('Arms')
                    ->badge()
                    ->color('info'),
                TextColumn::make('teaching_assignments_count')
                    ->counts('teachingAssignments')
                    ->label('Form teachers')
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Students')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
            ])
            ->recordActions([
                Action::make('addStudents')
                    ->label('Add Students')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalHeading(fn (SchoolClass $record): string => "Add students to {$record->name}")
                    ->modalSubmitActionLabel('Save placements')
                    ->schema(fn (SchoolClass $record): array => [
                        Select::make('academic_year_id')
                            ->label('Academic year')
                            ->options(fn (): array => AcademicYear::query()
                                ->where('school_id', $record->school_id)
                                ->orderByDesc('starts_on')
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => AcademicYear::query()
                                ->where('school_id', $record->school_id)
                                ->where('is_current', true)
                                ->value('id'))
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
                            ->default(fn (): ?int => Term::query()
                                ->where('school_id', $record->school_id)
                                ->where('is_current', true)
                                ->value('id'))
                            ->searchable()
                            ->preload(),
                        Select::make('class_section_id')
                            ->label('Arm')
                            ->options(fn (): array => $record->sections()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->visible(fn (): bool => $record->sections()->exists())
                            ->searchable()
                            ->preload(),
                        Select::make('student_ids')
                            ->label('Students')
                            ->multiple()
                            ->options(fn (): array => Student::query()
                                ->where('school_id', $record->school_id)
                                ->where('status', 'active')
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Student $student): array => [$student->getKey() => "{$student->full_name} ({$student->admission_number})"])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        DatePicker::make('enrolled_on')
                            ->label('Placement date')
                            ->default(today()),
                        Select::make('status')
                            ->default('active')
                            ->options([
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'transferred' => 'Transferred',
                                'withdrawn' => 'Withdrawn',
                            ])
                            ->required(),
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                    ])
                    ->action(function (SchoolClass $record, array $data): void {
                        $saved = app(StudentClassPlacement::class)->placeStudents($record, $data);

                        Notification::make()
                            ->success()
                            ->title('Students added')
                            ->body("Saved {$saved} student placement(s) for {$record->name}.")
                            ->send();
                    }),
                Action::make('assignTeachers')
                    ->label('Assign Form Teachers')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->hidden(fn (SchoolClass $record): bool => $record->sections()->exists())
                    ->modalHeading(fn (SchoolClass $record): string => "Assign form teachers to {$record->name}")
                    ->modalDescription('Add one or two form teachers for this class. If the class is later split into arms, use the Arms screen instead.')
                    ->modalWidth('5xl')
                    ->fillForm(fn (): array => [
                        'rows' => [
                            ['assignment_role' => 'form_teacher', 'is_class_teacher' => true, 'is_active' => true],
                        ],
                    ])
                    ->schema(fn (SchoolClass $record): array => [
                        Select::make('academic_year_id')
                            ->label('Academic year')
                            ->options(AcademicYear::query()->where('school_id', $record->school_id)->orderByDesc('starts_on')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('term_id')
                            ->label('Term')
                            ->options(Term::query()->where('school_id', $record->school_id)->orderBy('academic_year_id')->orderBy('position')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Repeater::make('rows')
                            ->label('Form teacher assignments')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->schema([
                                Select::make('staff_id')
                                    ->label('Teacher')
                                    ->options(Staff::query()
                                        ->where('school_id', $record->school_id)
                                        ->where('staff_type', Staff::TYPE_TEACHING)
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Staff $staff): array => [$staff->getKey() => "{$staff->full_name} ({$staff->staff_number})"])
                                        ->all())
                                    ->searchable()
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
                                    ->label('Primary class teacher')
                                    ->default(true),
                                Toggle::make('is_active')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add teacher'),
                    ])
                    ->action(function (SchoolClass $record, array $data): void {
                        if ($record->sections()->exists()) {
                            Notification::make()
                                ->warning()
                                ->title('Use Arms instead')
                                ->body('This class has arms already. Assign teachers from the Arms screen so each arm gets the correct teachers.')
                                ->send();

                            return;
                        }

                        $saved = 0;

                        foreach ($data['rows'] ?? [] as $row) {
                            if (blank($row['staff_id'] ?? null)) {
                                continue;
                            }

                            TeachingAssignment::query()->updateOrCreate(
                                [
                                    'school_id' => $record->school_id,
                                    'staff_id' => $row['staff_id'],
                                    'academic_year_id' => $data['academic_year_id'],
                                    'school_class_id' => $record->getKey(),
                                    'class_section_id' => null,
                                    'assignment_role' => $row['assignment_role'] ?? TeachingAssignment::ROLE_FORM_TEACHER,
                                ],
                                [
                                    'term_id' => $data['term_id'] ?: null,
                                    'subject_id' => null,
                                    'is_class_teacher' => (bool) ($row['is_class_teacher'] ?? false),
                                    'is_active' => (bool) ($row['is_active'] ?? true),
                                ],
                            );

                            $saved++;
                        }

                        Notification::make()
                            ->success()
                            ->title('Form teachers assigned')
                            ->body("Saved {$saved} form teacher assignment(s) for {$record->name}.")
                            ->send();
                    }),
                Action::make('manageArms')
                    ->label('Arms')
                    ->icon('heroicon-o-squares-plus')
                    ->color('info')
                    ->fillForm(fn (SchoolClass $record): array => [
                        'has_arms' => $record->sections()->exists(),
                        'section_labels_text' => $record->sections()->orderBy('name')->pluck('name')->implode(', '),
                        'section_capacity' => $record->sections()->value('capacity') ?? 40,
                    ])
                    ->schema([
                        Toggle::make('has_arms')
                            ->label('This class has arms')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('section_labels_text')
                            ->label('Arm names')
                            ->placeholder('A, B, C')
                            ->helperText('Use commas. Example: A, B, C or Safari, Achievers, Gold.'),
                        TextInput::make('section_capacity')
                            ->label('Arm capacity')
                            ->numeric()
                            ->default(40)
                            ->minValue(1),
                    ])
                    ->action(function (SchoolClass $record, array $data): void {
                        if (! ($data['has_arms'] ?? false)) {
                            $record->sections()->delete();

                            Notification::make()
                                ->success()
                                ->title('Arms updated')
                                ->body('All arms were removed from this class.')
                                ->send();

                            return;
                        }

                        $armNames = collect(explode(',', (string) ($data['section_labels_text'] ?? '')))
                            ->map(fn (string $label): string => trim($label))
                            ->filter()
                            ->unique()
                            ->values();

                        $keepCodes = [];

                        foreach ($armNames as $armName) {
                            $code = "{$record->code}-".Str::upper(Str::slug($armName, ''));
                            $keepCodes[] = $code;

                            $record->sections()->updateOrCreate(
                                [
                                    'school_id' => $record->school_id,
                                    'code' => $code,
                                ],
                                [
                                    'name' => $armName,
                                    'capacity' => $data['section_capacity'] ?: 40,
                                    'is_active' => true,
                                ],
                            );
                        }

                        $record->sections()
                            ->whereNotIn('code', $keepCodes)
                            ->delete();

                        Notification::make()
                            ->success()
                            ->title('Arms updated')
                            ->body($armNames->isEmpty() ? 'No arms saved.' : 'Arm names saved successfully.')
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
